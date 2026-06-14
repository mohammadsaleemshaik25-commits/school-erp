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
        $students = $query
    ->orderByDesc('student_enrollments.enrollment_id')
    ->get()
    ->unique('student_id')
    ->values();

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

        // Get prices for optional items
        $classFeeComponents = \App\Models\ClassFeeComponent::where('academic_year_id', $enrollment->academic_year_id)
            ->where('class_id', $enrollment->class_id)
            ->get()
            ->keyBy('component_id');

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

        // Business Rule: BOOKS category includes Exam Fee, School Diary, etc.
        $bookAccounts = $componentAccounts->filter(function ($account) {
            if (!$account->component) return false;
            
            return $account->component->category === 'BOOKS' || 
                   in_array($account->component->component_name, ['Exam Fee', 'School Diary', 'Note Books', 'Student File', 'Text Books']) ||
                   in_array($account->component->component_code, ['EXAM', 'DIARY', 'NOTE', 'FILE', 'BOOKS']);
        });

        // Business Rule: STORE category includes Belt, Tie, T-Shirt
       $otherAccounts = $componentAccounts->filter(function ($account) {

    if (!$account->component) {
        return false;
    }

    return $account->component->category === 'STORE';
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
    'classFeeComponents',
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
    public function updateSelections(Request $request): JsonResponse
    {
        $request->validate([
            'enrollment_id' => 'required|integer|exists:student_enrollments,enrollment_id',
            'selections' => 'required|array',
            'selections.*.component_id' => 'required|integer|exists:fee_components,component_id',
            'selections.*.selected' => 'nullable',
            'selections.*.amount' => 'required|numeric|min:0',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            $enrollmentId = $request->enrollment_id;
            $studentId = StudentEnrollment::where(
            'enrollment_id',
            $enrollmentId
            )->value('student_id');
            
            $selections = $request->selections;
            $userId = auth()->id();

            foreach ($selections as $selection) {
                $componentId = $selection['component_id'];
                $selected = filter_var($selection['selected'], FILTER_VALIDATE_BOOLEAN);
                $amount = $selection['amount'];

                if ($selected) {
                    // Create or update selection

                  StudentFeeComponentSelection::updateOrCreate(
                     [
                        'enrollment_id' => $enrollmentId,
                        'component_id' => $componentId,
                     ],
                     [
                     'student_id' => $studentId, // Use variable from above
                     'amount' => $amount,
                     'selected_by' => $userId,
                     'selected_at' => now(),
                      ]
                      );

                    // Ensure component account exists WITHOUT resetting paid balances
$componentAccount = StudentFeeComponentAccount::where(
    'enrollment_id',
    $enrollmentId
)
->where(
    'component_id',
    $componentId
)
->first();

if (!$componentAccount) {

    StudentFeeComponentAccount::create([
        'student_id'      => $studentId,
        'enrollment_id'   => $enrollmentId,
        'component_id'    => $componentId,
        'amount'          => $amount,
        'paid_amount'     => 0,
        'concession_amount' => 0,
        'waiver_amount'   => 0,
        'balance_amount'  => $amount,
    ]);

} else {

    // If payments already exist, NEVER recreate balance
    if (
        $componentAccount->paid_amount > 0 ||
        $componentAccount->concession_amount > 0 ||
        $componentAccount->waiver_amount > 0
    ) {

        // Keep existing accounting untouched
        continue;
    }

    // Only update unpaid components
    $componentAccount->amount = $amount;
    $componentAccount->balance_amount =
        max(
            0,
            $amount
            - $componentAccount->paid_amount
            - $componentAccount->concession_amount
            - $componentAccount->waiver_amount
        );

    $componentAccount->save();
}
                } else {
                    // Deselection logic with safety check
                    $componentAccount = StudentFeeComponentAccount::with('component')->where('enrollment_id', $enrollmentId)
                        ->where('component_id', $componentId)
                        ->first();

                    if ($componentAccount) {
                        if ($componentAccount->paid_amount > 0 || $componentAccount->concession_amount > 0 || $componentAccount->waiver_amount > 0) {
                            throw new Exception("Cannot remove '{$componentAccount->component->component_name}' because transactions already exist.");
                        }
                        // Safe to delete the account
                        $componentAccount->delete();
                    }

                    // Always delete the selection record
                    StudentFeeComponentSelection::where('enrollment_id', $enrollmentId)
                        ->where('component_id', $componentId)
                        ->delete();
                }
            }

            // Recalculate fee account totals
            $this->recalculateFeeAccount($enrollmentId);

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fee selections updated successfully.'
            ]);

        } catch (Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
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
        // Determine if the request is from the categorized workspace or the component-wise payment form
        $isCategorizedPayment = $request->has('tuition_payment') || $request->has('books_payment') || $request->has('other_payment') || $request->has('previous_payment');
        $isComponentWisePayment = $request->has('component_allocations');

        if (!$isCategorizedPayment && !$isComponentWisePayment) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment request. No payment amounts or allocations provided.'
            ], 400);
        }

        $rules = [
            'account_id' => 'required|integer|exists:student_fee_accounts,account_id',
            'payment_mode' => 'required|string|in:CASH,UPI,NEFT,CHEQUE,CARD', // Added more modes from payment.blade.php
            'transaction_reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:255',
        ];

        if ($isCategorizedPayment) {
            $rules = array_merge($rules, [
                'tuition_payment' => 'nullable|numeric|min:0',
                'books_payment' => 'nullable|numeric|min:0',
                'other_payment' => 'nullable|numeric|min:0',
                'previous_payment' => 'nullable|numeric|min:0',
                'store_component_ids' => 'nullable|string',
            ]);
        } elseif ($isComponentWisePayment) {
            $rules = array_merge($rules, [
                'component_allocations' => 'required|json',
            ]);
        }

        $request->validate($rules);
        
        if ($request->payment_mode === 'UPI' && blank($request->transaction_reference)) {
            return response()->json([
                'success' => false,
                'message' => 'UPI reference number is required.'
            ], 422);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $feeAccount = StudentFeeAccount::with(['enrollment'])->findOrFail($request->account_id);
            $allocations = [];
            $totalAmount = 0;

            if ($isComponentWisePayment) {
                // Handle component-wise allocations from payment.blade.php
                $requestedAllocations = json_decode($request->component_allocations, true);
                if (!is_array($requestedAllocations)) {
                    throw new Exception('Invalid component allocations format.');
                }

                foreach ($requestedAllocations as $allocation) {
                    if (!isset($allocation['component_account_id']) || !isset($allocation['amount'])) {
                        throw new Exception('Invalid allocation entry.');
                    }
                    $componentAccountId = $allocation['component_account_id'];
                    $amount = (float) $allocation['amount'];

                    if ($amount > 0) {
                        $allocations[$componentAccountId] = $amount;
                        $totalAmount += $amount;
                    }
                }

                if ($totalAmount <= 0) {
                    throw new Exception('Payment amount must be greater than zero.');
                }

            } else { // $isCategorizedPayment from workspace.blade.php
                $storeComponentIds = $request->filled('store_component_ids') ? array_map('intval', explode(',', $request->store_component_ids)) : [];

                // Process newly selected store items first
                if (!empty($storeComponentIds)) {
                    $classFeeComponents = \App\Models\ClassFeeComponent::where('academic_year_id', $feeAccount->enrollment->academic_year_id)
                        ->where('class_id', $feeAccount->enrollment->class_id)
                        ->whereIn('component_id', $storeComponentIds)
                        ->get()
                        ->keyBy('component_id');

                    foreach ($storeComponentIds as $id) {
                        $componentPrice = $classFeeComponents->get($id)->amount;
                        if ($componentPrice === null) {
                            throw new Exception("Price not found for store component ID: {$id}");
                        }

                        // Find existing component account or create a new one
                        $componentAccount = StudentFeeComponentAccount::where('enrollment_id', $feeAccount->enrollment->enrollment_id)
                            ->where('component_id', $id)
                            ->first();

                        if (!$componentAccount) {
                            // If it's a new store item selection, create a component account for it
                            $componentAccount = StudentFeeComponentAccount::create([
                                'enrollment_id' => $feeAccount->enrollment->enrollment_id,
                                'student_id' => $feeAccount->enrollment->student_id,
                                'component_id' => $id,
                                'amount' => $componentPrice,
                                'balance_amount' => $componentPrice,
                                'status' => 'PENDING',
                            ]);
                        } else {
                            // For repeatable store items, increment the amounts for the new purchase.
                            // If it already had a balance, this adds the new purchase on top of it.
                            $componentAccount->amount += $componentPrice;
                            $componentAccount->balance_amount += $componentPrice;
                            $componentAccount->status = 'PENDING'; // Ensure status is pending
                            $componentAccount->save();
                        }
                    }
                }

                // Reload fee account with all components, including newly created ones
                $feeAccount->load('enrollment.feeComponentAccounts.component');

                $tuitionAmount = (float) $request->tuition_payment;
                if ($tuitionAmount > 0) {
                    $tuitionAccounts = $feeAccount->enrollment
                        ->feeComponentAccounts
                        ->filter(function ($account) {
                            return $account->component && $account->component->category === 'TUITION';
                        })
                        ->sortBy(function ($account) {
                            return match ($account->component->component_code) {
                                'TERM1' => 1, 'TERM2' => 2, 'TERM3' => 3, default => 99
                            };
                        });

                    $remaining = $tuitionAmount;
                    foreach ($tuitionAccounts as $account) {
                        if ($remaining <= 0) break;
                        if ($account->balance_amount <= 0) continue;
                        $allocate = min($remaining, (float)$account->balance_amount);
                        $allocations[$account->id] = ($allocations[$account->id] ?? 0) + $allocate;
                        $remaining -= $allocate;
                    }
                    if ($remaining > 0) {
                        throw new Exception('Tuition payment exceeds outstanding tuition balance.');
                    }
                }

                $booksAmount = (float) $request->books_payment;
                if ($booksAmount > 0) {
                    $bookAccounts = $feeAccount->enrollment
                        ->feeComponentAccounts
                        ->filter(function ($account) {
                            if (!$account->component) return false;
                            return $account->component->category === 'BOOKS' || 
                                   in_array($account->component->component_name, ['Exam Fee', 'School Diary', 'Note Books', 'Student File', 'Text Books']) ||
                                   in_array($account->component->component_code, ['EXAM', 'DIARY', 'NOTE', 'FILE', 'BOOKS']);
                        });

                    $remaining = $booksAmount;
                    foreach ($bookAccounts as $account) {
                        if ($remaining <= 0) break;
                        if ($account->balance_amount <= 0) continue;
                        $allocate = min($remaining, (float)$account->balance_amount);
                        $allocations[$account->id] = ($allocations[$account->id] ?? 0) + $allocate;
                        $remaining -= $allocate;
                    }
                    if ($remaining > 0) {
                        throw new Exception('Books payment exceeds outstanding books balance.');
                    }
                }

                $otherAmount = (float) $request->other_payment; // This is the total other amount including newly added store items
                if ($otherAmount > 0) {
                    $otherAccounts = $feeAccount->enrollment
                        ->feeComponentAccounts
                        ->filter(function ($account) {
                            if (!$account->component) return false;
                            
                            // Re-apply the same logic to exclude books
                            $isBook = $account->component->category === 'BOOKS' || 
                                     in_array($account->component->component_name, ['Exam Fee', 'School Diary', 'Note Books', 'Student File', 'Text Books']) ||
                                     in_array($account->component->component_code, ['EXAM', 'DIARY', 'NOTE', 'FILE', 'BOOKS']);
                            
                            if ($isBook) return false;

                            return in_array($account->component->category, ['STORE', 'ADMISSION']) ||
                                   in_array($account->component->component_name, ['Belt', 'Tie', 'T-Shirt']) ||
                                   in_array($account->component->component_code, ['BELT', 'TIE', 'TSHIRT']);
                        });

                    $remaining = $otherAmount;
                    foreach ($otherAccounts as $account) {
                        if ($remaining <= 0) break;
                        if ($account->balance_amount <= 0) continue;
                        $allocate = min($remaining, (float)$account->balance_amount);
                        $allocations[$account->id] = ($allocations[$account->id] ?? 0) + $allocate;
                        $remaining -= $allocate;
                    }
                    if ($remaining > 0) {
                        throw new Exception('Other fees payment exceeds outstanding other fees balance.');
                    }
                }

                $previousAmount = (float) $request->previous_payment;
                if ($previousAmount > 0) {
                    $previousAccounts = $feeAccount->enrollment
                        ->feeComponentAccounts
                        ->filter(function ($account) {
                            return $account->component && $account->component->category === 'CARRY_FORWARD';
                        });

                    $remaining = $previousAmount;
                    foreach ($previousAccounts as $account) {
                        if ($remaining <= 0) break;
                        if ($account->balance_amount <= 0) continue;
                        $allocate = min($remaining, (float)$account->balance_amount);
                        $allocations[$account->id] = ($allocations[$account->id] ?? 0) + $allocate;
                        $remaining -= $allocate;
                    }
                    if ($remaining > 0) {
                        throw new Exception('Previous balance payment exceeds outstanding previous balance.');
                    }
                }
                $totalAmount = (float)$request->tuition_payment + (float)$request->books_payment + (float)$request->other_payment + (float)$request->previous_payment;
                if ($totalAmount <= 0) {
                    throw new Exception('Payment amount must be greater than zero.');
                }
            }

            $data = [
                'account_id' => $request->account_id,
                'amount' => $totalAmount, // Use the calculated total amount
                'payment_mode' => $request->payment_mode,
                'transaction_reference' => $request->transaction_reference,
                'remarks' => $request->remarks,
                'collected_by' => auth()->id(),
                'payment_date' => now(),
                'allocations' => $allocations,
            ];

            // Process payment through FinanceService
            $payment = $this->financeService->collectPayment($data, auth()->id());

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment collected successfully.',
                'payment_id' => $payment->payment_id,
                'receipt_id' => $payment->receipt?->receipt_id,
            ]);

        } catch (Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
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
                // Use the finance service to create a formal adjustment
                $this->financeService->requestAdjustment([
                    'account_id' => $feeAccount->account_id,
                    'component_id' => $componentId,
                    'adjustment_type' => 'WAIVER',
                    'discount_amount' => $componentAccount->balance_amount,
                    'reason' => $reason,
                ], $user->user_id, true); // Auto-approve
            } else {
                // "Closing" is also a form of waiver for accounting purposes.
                $this->financeService->requestAdjustment([
                    'account_id' => $feeAccount->account_id,
                    'component_id' => $componentId,
                    'adjustment_type' => 'WAIVER',
                    'discount_amount' => $componentAccount->balance_amount,
                    'reason' => "Balance Closed: " . $reason,
                ], $user->user_id, true); // Auto-approve
            }

            // Recalculation is handled by the service, but we can trigger it again for safety.
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
