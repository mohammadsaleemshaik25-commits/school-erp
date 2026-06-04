<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectPaymentRequest;
use App\Services\FinanceService;
use App\Models\StudentFeeAccount;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Exception;

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
        $searchResults = null;

        // Fetch classrooms and sections for search filter dropdowns
        $classes = ClassRoom::all();
        $sections = Section::all();

        // Scenario A: Clerk selected a student and loaded their checkout ledger
        if ($request->filled('account_id')) {
            $account = StudentFeeAccount::with(['enrollment.student', 'enrollment.academicYear'])
                ->findOrFail($request->get('account_id'));
        } 
        // Scenario B: Clerk is running a search on the desk
        elseif ($request->anyFilled(['q', 'admission_no', 'student_name', 'class_id', 'section_id', 'academic_year_id'])) {
            $selectedYearId = $request->get('academic_year_id');
            if (!$selectedYearId) {
                $activeYear = AcademicYear::where('is_active', true)->first();
                $selectedYearId = $activeYear ? $activeYear->academic_year_id : null;
            }

            $query = StudentFeeAccount::with(['enrollment.student', 'enrollment.academicYear', 'enrollment.classRoom', 'enrollment.section'])
                ->whereHas('enrollment', function($q) use ($selectedYearId) {
                    $q->where('academic_year_id', $selectedYearId);
                });

            // Universal Search Box (q)
            if ($request->filled('q')) {
                $q = $request->q;
                $query->whereHas('enrollment.student', function ($sub) use ($q) {
                    $sub->where(function($inner) use ($q) {
                        $inner->where('admission_no', 'like', "%{$q}%")
                              ->orWhere('student_name', 'like', "%{$q}%")
                              ->orWhere('father_name', 'like', "%{$q}%")
                              ->orWhere('mother_name', 'like', "%{$q}%")
                              ->orWhere('guardian_name', 'like', "%{$q}%")
                              ->orWhere('phone_primary', 'like', "%{$q}%")
                              ->orWhere('phone_secondary', 'like', "%{$q}%");
                    });
                });
            }

            // Individual Filters (These are now in enrollment or enrollment.student)
            if ($request->filled('admission_no') || $request->filled('student_name')) {
                $query->whereHas('enrollment.student', function ($sub) use ($request) {
                    if ($request->filled('admission_no')) {
                        $sub->where('admission_no', 'like', "%{$request->admission_no}%");
                    }
                    if ($request->filled('student_name')) {
                        $sub->where('student_name', 'like', "%{$request->student_name}%");
                    }
                });
            }

            if ($request->filled('class_id') || $request->filled('section_id')) {
                $query->whereHas('enrollment', function ($sub) use ($request) {
                    if ($request->filled('class_id')) {
                        $sub->where('class_id', $request->class_id);
                    }
                    if ($request->filled('section_id')) {
                        $sub->where('section_id', $request->section_id);
                    }
                });
            }

            $searchResults = $query->paginate(15)->appends($request->all());
        }

        return view('fees.collect', compact('account', 'searchResults', 'classes', 'sections'));
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
}