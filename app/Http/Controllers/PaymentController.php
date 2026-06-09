<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectPaymentRequest;
use App\Services\FinanceService;
use App\Models\StudentFeeAccount;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use App\Models\Payment;
use Exception;
use App\Models\Student;   // ADD THIS

class PaymentController extends Controller
{
    protected FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Show the fee collection dashboard, load search results, or fetch a specific ledger account.
     */
    public function create(Request $request)
    {
        $account = null;

        // Fetch classrooms and sections for search filter dropdowns
        $classes = ClassRoom::all();
        $sections = Section::all();

        $previousAccounts = collect();
        $allAccounts = collect();
        $componentAccounts = collect();
        // Scenario: Clerk selected a student and loaded their checkout ledger
        if ($request->filled('account_id')) {
            $account = StudentFeeAccount::with(['enrollment.student', 'enrollment.academicYear', 'enrollment.classRoom', 'enrollment.section'])
                ->findOrFail($request->get('account_id'));

            $studentId = $account->enrollment->student_id;
            $currentAY = $account->enrollment->academicYear;
            
            $allAccounts = StudentFeeAccount::whereHas('enrollment', function($q) use ($studentId) {
                    $q->where('student_id', $studentId);
                })
                ->with(['enrollment.student', 'enrollment.academicYear', 'enrollment.classRoom', 'enrollment.section'])
                ->get();
                
            $previousAccounts = $allAccounts->filter(function($acc) use ($currentAY) {
                $ay = $acc->enrollment->academicYear;
                return $ay && $ay->start_date < $currentAY->start_date;
            })->sortBy(function($acc) {
                return $acc->enrollment->academicYear->start_date;
            });

            if ($account->enrollment) {
                $account->load(['enrollment.feeComponentAccounts.component']);
                $componentAccounts = $account->enrollment->feeComponentAccounts;
            }
        } 

        // Dashboard stats for fee collection page
        $todayCollection = Payment::whereDate('payment_date', today())
            ->where('status', '!=', 'CANCELLED')
            ->sum('amount');

        $clerkReceipts = Payment::whereDate('payment_date', today())
            ->where('collected_by', auth()->id())
            ->where('status', 'SUCCESS')
            ->count();

        $cancelledReceipts = Payment::whereDate('payment_date', today())
            ->where('status', 'CANCELLED')
            ->count();

        $totalDue = StudentFeeAccount::sum('total_due');

        $totalPaid = Payment::where('status', 'SUCCESS')
            ->sum('amount');

        $totalPending = max(0, $totalDue - $totalPaid);

        return view('fees.collect', compact(
            'account',
            'classes',
            'sections',
            'todayCollection',
            'clerkReceipts',
            'cancelledReceipts',
            'totalPending',
            'previousAccounts',
            'allAccounts',
            'componentAccounts'
        ));
    }

    /**
     * Display the student ledger for a specific fee account.
     */
    public function ledger(StudentFeeAccount $account)
    {
        $account->load([
            'enrollment.student',
            'enrollment.academicYear',
            'enrollment.classRoom',
            'enrollment.section',
            'payments' => function($q) {
                $q->with('receipt', 'collector')->orderBy('payment_date', 'desc');
            }
        ]);

        $summary = [
            'books_paid' => $account->payments()->where('status', 'SUCCESS')->sum('books_fee_paid'),
            'tuition_paid' => $account->payments()->where('status', 'SUCCESS')->sum('tuition_fee_paid'),
            'cancelled_payments' => $account->payments()->where('status', 'CANCELLED')->sum('amount'),
            'total_paid' => $account->total_paid,
            'outstanding' => $account->remaining_balance
        ];

        return view('fees.ledger', compact('account', 'summary'));
    }

    /**
     * Process fee payment collection
     */
    public function store(CollectPaymentRequest $request)
    {
        try {
            $payment = $this->financeService->collectPayment(
                $request->validated(),
                auth()->id()
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment registered successfully.',
                    'data' => $payment
                ], 201);
            }

            if ($payment->receipt) {
                return redirect()
                    ->route('fees.receipts.show', $payment->receipt->receipt_id)
                    ->with('success', 'Payment recorded and receipt generated.');
            }

            return redirect()
                ->route('fees.collect')
                ->with('success', 'Payment recorded successfully.');

        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel a payment transaction
     */
    public function cancel(Request $request, int $paymentId)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255'
        ]);

        try {
            $payment = $this->financeService->cancelPayment(
                $paymentId,
                $request->get('cancellation_reason'),
                auth()->id()
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment transaction marked as cancelled.',
                    'data' => $payment
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'Payment transaction marked as cancelled.');

        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function searchStudents(Request $request)
    {
        $term = trim($request->get('query', $request->get('term', '')));

        if (empty($term)) {
            return response()->json([]);
        }

        // Search for active fee accounts matching the student name or admission number
        $accounts = StudentFeeAccount::with(['enrollment.student'])
            ->whereHas('enrollment.student', function ($query) use ($term) {
                $query->where('student_name', 'like', '%' . $term . '%')
                      ->orWhere('admission_no', 'like', '%' . $term . '%');
            })
            ->limit(20)
            ->get();

        return response()->json(
            $accounts->map(function ($account) {
                return [
                    'id' => $account->account_id,
                    'text' => $account->enrollment->student->student_name . 
                             ' (' . $account->enrollment->student->admission_no . ')'
                ];
            })->values()
        );
    }

    /**
     * Professional Student Finder AJAX Endpoint
     */
    public function finder(Request $request)
    { 
        $q = trim($request->get('q', ''));
        $classId = $request->get('class_id');
        $sectionId = $request->get('section_id');
        $gender = $request->get('gender');

        // If everything is empty, return empty results
        if (empty($q) && empty($classId) && empty($sectionId) && empty($gender)) {
            return response()->json([]);
        }

        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) return response()->json([]);

        $query = StudentFeeAccount::with([
                'enrollment.student', 
                'enrollment.classRoom', 
                'enrollment.section'
            ])
            ->whereHas('enrollment', function($query) use ($activeYear, $classId, $sectionId) {
                $query->where('academic_year_id', $activeYear->academic_year_id);
                if ($classId) $query->where('class_id', $classId);
                if ($sectionId) $query->where('section_id', $sectionId);
            });

        if ($gender) {
            $query->whereHas('enrollment.student', function($query) use ($gender) {
                $query->where('gender', $gender);
            });
        }

        if (!empty($q)) {
            $query->whereHas('enrollment.student', function ($query) use ($q) {
                $query->where(function($inner) use ($q) {
                    // Name Prioritization: Starts with q, then matches q anywhere
                    $inner->where('student_name', 'like', "{$q}%")
                          ->orWhere('student_name', 'like', "%{$q}%")
                          ->orWhere('admission_no', 'like', "{$q}%")
                          ->orWhere('father_name', 'like', "%{$q}%")
                          ->orWhere('mother_name', 'like', "%{$q}%")
                          ->orWhere('guardian_name', 'like', "%{$q}%");
                });
            });
            
            // Order results to prioritize "starts with" name
            $query->join('student_enrollments', 'student_fee_accounts.enrollment_id', '=', 'student_enrollments.enrollment_id')
                  ->join('students', 'student_enrollments.student_id', '=', 'students.student_id')
                  ->orderByRaw("CASE 
                        WHEN students.student_name LIKE ? THEN 1 
                        WHEN students.student_name LIKE ? THEN 2 
                        WHEN students.admission_no LIKE ? THEN 3 
                        ELSE 4 
                    END ASC", ["{$q}%", "%{$q}%", "{$q}%"])
                  ->select('student_fee_accounts.*');
        }

        $results = $query->limit(20)->get();

        return response()->json(
            $results->map(function ($acc) {
                $student = $acc->enrollment->student;
                return [
                    'account_id' => $acc->account_id,
                    'student_name' => strtoupper($student->student_name),
                    'admission_no' => $student->admission_no,
                    'class_name' => $acc->enrollment->classRoom->class_name,
                    'section_name' => $acc->enrollment->section->section_name ?? 'N/A',
                    'gender' => $student->gender,
                    'father_name' => $student->father_name,
                    'mother_name' => $student->mother_name,
                    'phone_primary' => $student->phone_primary,
                    'photo_url' => $student->photo_path ? asset('storage/' . $student->photo_path) : null,
                ];
            })
        );
    }

    public function closePreviousFee(Request $request, StudentFeeAccount $account)
    {
        $userRole = strtoupper(optional(auth()->user()->role)->role_name ?? '');
        if (!in_array($userRole, ['ADMIN', 'ADMINISTRATOR', 'PRINCIPAL', 'CORRESPONDENT'])) {
            return redirect()->back()->with('error', 'Unauthorized action. Only Admin, Principal, and Correspondent can close previous fees.');
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:255'
        ]);

        $account->status = 'CLOSED';
        $account->closed_at = now();
        $account->save();

        // Sync component accounts
        if ($account->enrollment && $account->enrollment->feeComponentAccounts()->count() > 0) {
            foreach ($account->enrollment->feeComponentAccounts as $compAcc) {
                if ($compAcc->balance_amount > 0) {
                    $compAcc->paid_amount += $compAcc->balance_amount;
                    $compAcc->recalculateBalance();
                    $compAcc->save();
                }
            }
        }

        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'PREVIOUS_FEE_CLOSED',
            'table_name' => 'student_fee_accounts',
            'record_id' => $account->account_id,
            'new_value' => json_encode([
                'reason' => $request->input('reason'),
                'user' => auth()->user()->full_name ?? auth()->user()->username,
                'date' => now()->toDateTimeString(),
            ]),
            'ip_address' => $request->ip()
        ]);

        return redirect()->back()->with('success', 'Previous academic year fee closed successfully.');
    }

    public function waivePreviousFee(Request $request, StudentFeeAccount $account)
    {
        $userRole = strtoupper(optional(auth()->user()->role)->role_name ?? '');
        if (!in_array($userRole, ['ADMIN', 'ADMINISTRATOR', 'PRINCIPAL', 'CORRESPONDENT'])) {
            return redirect()->back()->with('error', 'Unauthorized action. Only Admin, Principal, and Correspondent can waive previous fees.');
        }

        $request->validate([
            'reason' => 'required|string|min:5|max:255'
        ]);

        $remaining = $account->remaining_balance;
        $account->waived_amount = (float) $account->waived_amount + $remaining;
        $account->waived_by = auth()->id();
        $account->waived_date = now();
        $account->recalculateTotals();
        $account->save();

        // Sync component accounts and create fee_waivers
        if ($account->enrollment && $account->enrollment->feeComponentAccounts()->count() > 0) {
            foreach ($account->enrollment->feeComponentAccounts as $compAcc) {
                $compBal = (float)$compAcc->balance_amount;
                if ($compBal > 0) {
                    \App\Models\FeeWaiver::create([
                        'student_id' => $account->enrollment->student_id,
                        'enrollment_id' => $account->enrollment_id,
                        'component_id' => $compAcc->component_id,
                        'waiver_amount' => $compBal,
                        'reason' => $request->input('reason') . " (Legacy Waive)",
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);

                    $compAcc->waiver_amount += $compBal;
                    $compAcc->recalculateBalance();
                    $compAcc->save();
                }
            }
        }

        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'PREVIOUS_FEE_WAIVED',
            'table_name' => 'student_fee_accounts',
            'record_id' => $account->account_id,
            'new_value' => json_encode([
                'reason' => $request->input('reason'),
                'amount_waived' => $remaining,
                'user' => auth()->user()->full_name ?? auth()->user()->username,
                'date' => now()->toDateTimeString(),
            ]),
            'ip_address' => $request->ip()
        ]);

        return redirect()->back()->with('success', 'Previous academic year fee waived successfully.');
    }
}