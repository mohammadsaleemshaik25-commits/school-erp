<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentFeeAccount;
use App\Models\StudentEnrollment;
use App\Models\FeeComponent;
use App\Models\StudentFeeComponentAccount;
use App\Models\FeeWaiver;
use App\Models\StudentFeeComponentSelection;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\StudentFeeAdjustment;
use Exception;

class FeeCollectionController extends Controller
{
    protected FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Display the fee collection search interface
     */
    public function index()
    {
        $classes = ClassRoom::all();
        $sections = Section::all();
        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();

        return view('fees-collection.search', compact('classes', 'sections', 'academicYears'));
    }

    /**
     * Search students with photos for fee collection
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim($request->get('q', ''));
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        $academicYearId = $request->get('academic_year_id');

        // Build query with manual joins instead of using currentEnrollment relationship
        $query = Student::select('students.*', 'classes.class_name', 'sections.section_name', 'academic_years.year_name', 'student_enrollments.enrollment_id', 'student_fee_accounts.total_due')
            ->leftJoin('student_enrollments', 'students.student_id', '=', 'student_enrollments.student_id')
            ->leftJoin('student_fee_accounts', 'student_enrollments.enrollment_id', '=', 'student_fee_accounts.enrollment_id')
            ->leftJoin('academic_years', 'student_enrollments.academic_year_id', '=', 'academic_years.academic_year_id')
            ->leftJoin('classes', 'student_enrollments.class_id', '=', 'classes.class_id')
            ->leftJoin('sections', 'student_enrollments.section_id', '=', 'sections.section_id')
            ->where('students.status', 'ACTIVE')
            ->where('student_enrollments.status', 'ACTIVE');

        if (!empty($term)) {
            $query->where(function($q) use ($term) {
               $q->where('students.student_name', 'like', '%' . $term . '%')
                 ->orWhere('students.admission_no', 'like', '%' . $term . '%')
                 ->orWhere('students.father_name', 'like', '%' . $term . '%')
                 ->orWhere('students.phone_primary', 'like', '%' . $term . '%');
            });
        }

        if (!empty($classId)) {
            $query->where('student_enrollments.class_id', $classId);
        }

        if (!empty($sectionId)) {
            $query->where('student_enrollments.section_id', $sectionId);
        }

        if (!empty($academicYearId)) {
            $query->where('student_enrollments.academic_year_id', $academicYearId);
        }

        $students = $query->orderByDesc('academic_years.start_date')
            ->limit(20)
            ->get()
            ->unique('student_id'); // Remove duplicates if student has multiple enrollments

        return response()->json($students->map(function ($student) {
            return [
                'student_id' => $student->student_id,
                'student_name' => $student->student_name,
                'admission_no' => $student->admission_no,
                'photo_path' => $student->photo_path,
                'class_name' => $student->class_name ?? 'N/A',
                'section_name' => $student->section_name ?? 'N/A',
                'academic_year' => $student->year_name ?? 'N/A',
                'enrollment_id' => $student->enrollment_id,
                'total_due' => $student->total_due ?? 0,
            ];
        })->values());
    }

    /**
     * Display the student fee workspace
     */
    public function workspace(Request $request, $studentId)
    {
        $student = Student::findOrFail($studentId);

        // Get active enrollment with relationships using manual query
        $enrollment = StudentEnrollment::with(['feeAccount', 'classRoom', 'section', 'academicYear'])
            ->where('student_id', $studentId)
            ->where('status', 'ACTIVE')
            ->orderBy('enrollment_id', 'desc')
            ->first();

        if (!$enrollment) {
            return redirect()->route('fees-collection.index')
                ->with('error', 'Student has no active enrollment.');
        }

        // Get or create fee account
        $feeAccount = $enrollment->feeAccount;

        if (!$feeAccount) {
            return redirect()->route('fees-collection.index')
                ->with('error', 'Fee account not found for student. Please contact administrator.');
        }

        // Load component accounts
        $componentAccounts = StudentFeeComponentAccount::with('component')
            ->where('enrollment_id', $enrollment->enrollment_id)
            ->get()
            ->keyBy('component_id');

        // Get component selections
        $componentSelections = StudentFeeComponentSelection::where('enrollment_id', $enrollment->enrollment_id)
            ->get()
            ->keyBy('component_id');

        // Get available components by category
        $tuitionComponents = FeeComponent::where('category', 'TUITION')->where('status', 'ACTIVE')->get();
        $bookComponents = FeeComponent::where('category', 'BOOKS')->where('status', 'ACTIVE')->get();
        $storeComponents = FeeComponent::where('category', 'STORE')->where('status', 'ACTIVE')->get();
        $carryForwardComponents = FeeComponent::where('category', 'CARRY_FORWARD')->where('status', 'ACTIVE')->get();

        // Get payment history
        $payments = Payment::with('receipt', 'collector')
            ->where('account_id', $feeAccount->account_id)
            ->where('status', 'SUCCESS')
            ->orderBy('payment_date', 'desc')
            ->get();

        // Check user role for previous balance access
        $user = auth()->user();
        $canManagePreviousBalances = in_array(strtoupper($user->role->role_name ?? ''), ['PRINCIPAL', 'CORRESPONDENT', 'ADMIN', 'ADMINISTRATOR']);
        $tuitionAccounts = $componentAccounts->filter(function ($account) {
    return $account->component &&
        $account->component->category === 'TUITION';
});

$bookAccounts = $componentAccounts->filter(function ($account) {
    return $account->component &&
        $account->component->category === 'BOOKS';
});

$otherAccounts = $componentAccounts->filter(function ($account) {
    return in_array(
        $account->component?->category,
        ['STORE', 'ADMISSION']
    );
});

$previousAccounts = $componentAccounts->filter(function ($account) {
    return $account->component &&
        $account->component->category === 'CARRY_FORWARD';
});

$tuitionSummary = [
    'amount' => $tuitionAccounts->sum('amount'),
    'concession' => $tuitionAccounts->sum('concession_amount'),
    'paid' => $tuitionAccounts->sum('paid_amount'),
    'balance' => $tuitionAccounts->sum('balance_amount'),
];

$bookSummary = [
    'amount' => $bookAccounts->sum('amount'),
    'concession' => $bookAccounts->sum('concession_amount'),
    'paid' => $bookAccounts->sum('paid_amount'),
    'balance' => $bookAccounts->sum('balance_amount'),
];

      return view('fees-collection.workspace', compact(
    'student',
    'enrollment',
    'feeAccount',
    'componentAccounts',
    'componentSelections',
    'tuitionComponents',
    'bookComponents',
    'storeComponents',
    'carryForwardComponents',
    'payments',
    'canManagePreviousBalances',

    'tuitionSummary',
    'bookSummary',
    'tuitionAccounts',
    'bookAccounts',
    'otherAccounts',
    'previousAccounts'
));
        }
    /**
     * Update book fee selections
     */
    public function updateBookSelections(Request $request): JsonResponse
    {
        $request->validate([
            'enrollment_id' => 'required|integer|exists:student_enrollments,enrollment_id',
            'selections' => 'required|array',
            'selections.*.component_id' => 'required|integer|exists:fee_components,component_id',
            'selections.*.selected' => 'required|boolean',
            'selections.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            $enrollmentId = $request->enrollment_id;
            $selections = $request->selections;
            $userId = auth()->id();

            foreach ($selections as $selection) {
                $componentId = $selection['component_id'];
                $selected = $selection['selected'];
                $amount = $selection['amount'];

                if ($selected) {
                    // Create or update selection
                    StudentFeeComponentSelection::updateOrCreate(
                        [
                            'enrollment_id' => $enrollmentId,
                            'component_id' => $componentId,
                        ],
                        [
                            'amount' => $amount,
                            'selected_by' => $userId,
                            'selected_at' => now(),
                        ]
                    );

                    // Ensure component account exists
                    StudentFeeComponentAccount::updateOrCreate(
                        [
                            'enrollment_id' => $enrollmentId,
                            'component_id' => $componentId,
                        ],
                        [
                            'student_id' => StudentEnrollment::find($enrollmentId)->student_id,
                            'amount' => $amount,
                            'balance_amount' => $amount,
                        ]
                    );
                } else {
                    // Remove selection
                    StudentFeeComponentSelection::where('enrollment_id', $enrollmentId)
                        ->where('component_id', $componentId)
                        ->delete();
                }
            }

            // Recalculate fee account totals
            $this->recalculateFeeAccount($enrollmentId);

            return response()->json([
                'success' => true,
                'message' => 'Book fee selections updated successfully.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating selections: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show payment collection form
     */
    public function showPaymentForm(Request $request, $accountId)
    {
        $feeAccount = StudentFeeAccount::with([
            'enrollment.student',
            'enrollment.academicYear',
            'enrollment.classRoom',
            'enrollment.section'
        ])->findOrFail($accountId);

        $componentAccounts = StudentFeeComponentAccount::with('component')
            ->where('enrollment_id', $feeAccount->enrollment_id)
            ->where('balance_amount', '>', 0)
            ->get();

        $totalDue = $componentAccounts->sum('balance_amount');

        return view('fees-collection.payment', compact(
            'feeAccount',
            'componentAccounts',
            'totalDue'
        ));
    }

    /**
     * Process payment collection
     */
    public function collectPayment(Request $request): JsonResponse
    {
        $request->validate([
       
            'account_id' => 'required|integer|exists:student_fee_accounts,account_id',
            'tuition_payment' => 'nullable|numeric|min:0',
            'books_payment' => 'nullable|numeric|min:0',
            'other_payment' => 'nullable|numeric|min:0',
            'previous_payment' => 'nullable|numeric|min:0',
            'payment_mode' => 'required|string|in:CASH,UPI',
            'transaction_reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:255',
        ]);
        if (
    $request->payment_mode === 'UPI' &&
    blank($request->transaction_reference)
) {
    return response()->json([
        'success' => false,
        'message' => 'UPI reference number is required.'
    ], 422);
}

        try {
            $totalAmount =
    (float)$request->tuition_payment +
    (float)$request->books_payment +
    (float)$request->other_payment +
    (float)$request->previous_payment;

    if ($totalAmount <= 0) {
    throw new \Exception('Payment amount must be greater than zero.');
    }

    $data = [
        'account_id' => $request->account_id,
        'amount' => $totalAmount,
        'payment_mode' => $request->payment_mode,
        'transaction_reference' => $request->transaction_reference,
        'remarks' => $request->remarks,
                ];

            $feeAccount = StudentFeeAccount::with([
            'enrollment.feeComponentAccounts.component'
            ])->findOrFail($request->account_id);

            $allocations = [];

            $tuitionAmount = (float) $request->tuition_payment;

if ($tuitionAmount > 0) {

    $tuitionAccounts = $feeAccount->enrollment
        ->feeComponentAccounts
        ->filter(function ($account) {
            return $account->component &&
                   $account->component->category === 'TUITION';
        })
        ->sortBy(function ($account) {

            return match ($account->component->component_code) {
                'TERM1' => 1,
                'TERM2' => 2,
                'TERM3' => 3,
                default => 99
            };
        });

    $remaining = $tuitionAmount;

    foreach ($tuitionAccounts as $account) {

        if ($remaining <= 0) {
            break;
        }

        if ($account->balance_amount <= 0) {
            continue;
        }

        $allocate = min(
            $remaining,
            (float)$account->balance_amount
        );

        $allocations[$account->id] = $allocate;

        $remaining -= $allocate;
    }

    if ($remaining > 0) {
        throw new Exception(
            'Tuition payment exceeds outstanding tuition balance.'
        );
    }
}

$booksAmount = (float) $request->books_payment;

if ($booksAmount > 0) {

    $bookAccounts = $feeAccount->enrollment
        ->feeComponentAccounts
        ->filter(function ($account) {
            return $account->component &&
                   $account->component->category === 'BOOKS';
        });

    $remaining = $booksAmount;

    foreach ($bookAccounts as $account) {

        if ($remaining <= 0) {
            break;
        }

        if ($account->balance_amount <= 0) {
            continue;
        }

        $allocate = min(
            $remaining,
            (float)$account->balance_amount
        );

        $allocations[$account->id] =
            ($allocations[$account->id] ?? 0)
            + $allocate;

        $remaining -= $allocate;
    }

    if ($remaining > 0) {
        throw new Exception(
            'Books payment exceeds outstanding books balance.'
        );
    }
}
            $data['collected_by'] = auth()->id();
            $data['payment_date'] = now();

            // Transform component_allocations from ['id' => ['amount' => 'value']] to ['component_account_id' => id, 'amount' => value]
          $data['allocations'] = $allocations;

            // Process payment through FinanceService
            $payment = $this->financeService->collectPayment($data, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Payment collected successfully.',
                'payment_id' => $payment->payment_id,
                'receipt_id' => $payment->receipt?->receipt_id,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error collecting payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Close previous balance (Principal/Correspondent only)
     */
    public function closePreviousBalance(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|integer|exists:student_fee_accounts,account_id',
            'component_id' => 'required|integer|exists:fee_components,component_id',
            'action' => 'required|string|in:close,waive',
            'reason' => 'required|string|min:5|max:255',
        ]);

        // Check authorization
        $user = auth()->user();
        $role = strtoupper($user->role->role_name ?? '');
        if (!in_array($role, ['PRINCIPAL', 'CORRESPONDENT', 'ADMIN', 'ADMINISTRATOR'])) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action.'
            ], 403);
        }

        try {
            $accountId = $request->account_id;
            $componentId = $request->component_id;
            $action = $request->action;
            $reason = $request->reason;

            $feeAccount = StudentFeeAccount::findOrFail($accountId);
            $componentAccount = StudentFeeComponentAccount::where('enrollment_id', $feeAccount->enrollment_id)
                ->where('component_id', $componentId)
                ->firstOrFail();

            if ($action === 'waive') {
                // Waive the balance
                $componentAccount->waiver_amount = $componentAccount->balance_amount;
                $componentAccount->balance_amount = 0;
                $componentAccount->status = 'WAIVED';
                $componentAccount->save();
            } else {
                // Close the balance (mark as paid)
                $componentAccount->paid_amount += $componentAccount->balance_amount;
                $componentAccount->balance_amount = 0;
                $componentAccount->status = 'PAID';
                $componentAccount->save();
            }

            // Recalculate fee account
            $this->recalculateFeeAccount($feeAccount->enrollment_id);

            return response()->json([
                'success' => true,
                'message' => "Previous balance {$action}d successfully."
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the student ledger for a specific student.
     */
    public function ledger($studentId)
    {
        $student = Student::findOrFail($studentId);

        // Get active enrollment
        $enrollment = StudentEnrollment::with(['feeAccount', 'classRoom', 'section', 'academicYear'])
            ->where('student_id', $studentId)
            ->where('status', 'ACTIVE')
            ->orderBy('enrollment_id', 'desc')
            ->firstOrFail();

        $feeAccount = $enrollment->feeAccount;

        // 1. Calculate Summary Stats
        $componentAccounts = StudentFeeComponentAccount::where('enrollment_id', $enrollment->enrollment_id)->get();
        $totalCharges = $componentAccounts->sum('amount');
        $totalConcession = $componentAccounts->sum('concession_amount');
        $totalPaid = $componentAccounts->sum('paid_amount');
        $totalWaiver = $componentAccounts->sum('waiver_amount');
        $outstanding = $componentAccounts->sum('balance_amount');

        $summary = [
            'total_charges' => $totalCharges,
            'total_concession' => $totalConcession,
            'total_paid' => $totalPaid,
            'total_waiver' => $totalWaiver,
            'outstanding' => $outstanding,
        ];

        // 2. Fetch Transactions for Ledger
        $transactions = collect();

        // Payments
        $payments = Payment::with(['receipt', 'collector'])
            ->where('account_id', $feeAccount->account_id)
            ->get();

        foreach ($payments as $payment) {
            if ($payment->status === 'SUCCESS') {
                $transactions->push([
                    'date' => $payment->payment_date,
                    'type' => 'PAYMENT',
                    'description' => "Fee Payment - Receipt #" . ($payment->receipt->receipt_number ?? 'N/A') . " ({$payment->payment_mode})",
                    'amount' => $payment->amount,
                    'reference' => $payment->receipt->receipt_number ?? 'N/A',
                    'performed_by' => $payment->collector->full_name ?? 'System',
                    'sort_date' => $payment->payment_date,
                    'raw_type' => 'PAYMENT'
                ]);
            } elseif ($payment->status === 'CANCELLED') {
                $transactions->push([
                    'date' => $payment->updated_at,
                    'type' => 'RECEIPT CANCELLATION',
                    'description' => "Cancelled Receipt #" . ($payment->receipt->receipt_number ?? 'N/A') . " - Reason: " . ($payment->receipt->cancellation_reason ?? 'No reason'),
                    'amount' => -$payment->amount,
                    'reference' => $payment->receipt->receipt_number ?? 'N/A',
                    'performed_by' => $payment->receipt->cancelledByUser->full_name ?? 'System',
                    'sort_date' => $payment->updated_at,
                    'raw_type' => 'CANCELLATION'
                ]);
            }
        }

        // Concessions (Approved Adjustments)
        $concessions = StudentFeeAdjustment::with(['approver', 'component'])
            ->where('account_id', $feeAccount->account_id)
            ->where('approval_status', 'APPROVED')
            ->get();

        foreach ($concessions as $concession) {
            $transactions->push([
                'date' => $concession->approved_at,
                'type' => 'CONCESSION',
                'description' => "Concession: " . ($concession->component->component_name ?? 'General') . " - " . $concession->reason,
                'amount' => $concession->discount_amount,
                'reference' => 'CON-' . $concession->adjustment_id,
                'performed_by' => $concession->approver->full_name ?? 'System',
                'sort_date' => $concession->approved_at,
                'raw_type' => 'CONCESSION'
            ]);
        }

        // Waivers
        $waivers = FeeWaiver::with(['approver', 'component'])
            ->where('enrollment_id', $enrollment->enrollment_id)
            ->get();

        foreach ($waivers as $waiver) {
            $transactions->push([
                'date' => $waiver->approved_at,
                'type' => 'WAIVER',
                'description' => "Waiver: " . ($waiver->component->component_name ?? 'General') . " - " . $waiver->reason,
                'amount' => $waiver->waiver_amount,
                'reference' => 'WAV-' . $waiver->waiver_id,
                'performed_by' => $waiver->approver->full_name ?? 'System',
                'sort_date' => $waiver->approved_at,
                'raw_type' => 'WAIVER'
            ]);
        }

        // 3. Sort and Calculate Running Balance
        $sortedTransactions = $transactions->sortBy('sort_date')->values();
        
        $runningBalance = $totalCharges;
        $ledgerEntries = [];

        // Initial Entry: Fee Charges
        $ledgerEntries[] = [
            'date' => $enrollment->created_at,
            'type' => 'CHARGES',
            'description' => 'Total Fee Charges for ' . $enrollment->academicYear->year_name,
            'amount' => $totalCharges,
            'running_balance' => $runningBalance,
            'performed_by' => 'System',
            'reference' => 'N/A'
        ];

        foreach ($sortedTransactions as $tx) {
            if ($tx['raw_type'] === 'PAYMENT' || $tx['raw_type'] === 'CONCESSION' || $tx['raw_type'] === 'WAIVER') {
                $runningBalance -= abs($tx['amount']);
            } elseif ($tx['raw_type'] === 'CANCELLATION') {
                $runningBalance += abs($tx['amount']);
            }

            $ledgerEntries[] = array_merge($tx, ['running_balance' => $runningBalance]);
        }

        // Reverse for display (newest first)
        $ledgerEntries = array_reverse($ledgerEntries);

        return view('fees-collection.ledger', compact('student', 'enrollment', 'summary', 'ledgerEntries'));
    }

    /**
     * Show concession request form
     */
    public function concessionRequest($studentId)
    {
        $student = Student::findOrFail($studentId);
        $enrollment = StudentEnrollment::where('student_id', $studentId)
            ->where('status', 'ACTIVE')
            ->firstOrFail();

        $componentAccounts = StudentFeeComponentAccount::with('component')
            ->where('enrollment_id', $enrollment->enrollment_id)
            ->where('balance_amount', '>', 0)
            ->get();

        $requests = StudentFeeAdjustment::with(['component', 'requester', 'approver'])
            ->where('account_id', $enrollment->feeAccount->account_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('fees-collection.concession-request', compact('student', 'enrollment', 'componentAccounts', 'requests'));
    }

    /**
     * Store a new concession request
     */
    public function storeConcessionRequest(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'component_id' => 'required|exists:fee_components,component_id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|min:5|max:255',
        ]);

        try {
            $student = Student::findOrFail($request->student_id);
            $enrollment = StudentEnrollment::where('student_id', $student->student_id)
                ->where('status', 'ACTIVE')
                ->firstOrFail();

            $data = [
                'account_id' => $enrollment->feeAccount->account_id,
                'component_id' => $request->component_id,
                'discount_amount' => $request->amount,
                'adjustment_type' => 'CONCESSION',
                'reason' => $request->reason,
            ];

            $this->financeService->requestAdjustment($data, auth()->id());

            return redirect()->back()->with('success', 'Concession request submitted successfully and is pending approval.');

        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Recalculate fee account totals
     */
    private function recalculateFeeAccount($enrollmentId)
    {
        $feeAccount = StudentFeeAccount::where('enrollment_id', $enrollmentId)->first();
        if (!$feeAccount) return;

        // Get all component accounts
        $componentAccounts = StudentFeeComponentAccount::where('enrollment_id', $enrollmentId)->get();

        $totalAmount = $componentAccounts->sum('amount');
        $totalPaid = $componentAccounts->sum('paid_amount');
        $totalWaived = $componentAccounts->sum('waiver_amount');
        $totalBalance = $componentAccounts->sum('balance_amount');

        $feeAccount->net_fee = $totalAmount - $totalWaived;
        $feeAccount->total_due = $totalBalance;
        $feeAccount->save();
    }
}
