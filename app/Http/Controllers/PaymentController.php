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
        if ($request->filled('student_fee_account_id')) {
            $account = StudentFeeAccount::with(['student', 'academicYear'])
                ->findOrFail($request->get('student_fee_account_id'));
        } 
        // Scenario B: Clerk is running a search on the desk
        elseif ($request->anyFilled(['admission_no', 'student_name', 'class_id', 'section_id'])) {
            $activeYear = AcademicYear::where('is_active', true)->first();
            $yearId = $activeYear ? $activeYear->academic_year_id : null;

            $query = StudentFeeAccount::with(['student', 'academicYear'])
                ->where('academic_year_id', $yearId);

            // Dynamically apply search constraints matching multi-field request
            $query->whereHas('student', function ($sub) use ($request) {
                if ($request->filled('admission_no')) {
                    $sub->where('admission_no', $request->admission_no);
                }
                if ($request->filled('student_name')) {
                    $sub->where('student_name', 'like', "%{$request->student_name}%");
                }
            });

            if ($request->filled('class_id')) {
                $query->where('class_id', $request->class_id);
            }

            if ($request->filled('section_id')) {
                $query->where('section_id', $request->section_id);
            }

            $searchResults = $query->paginate(15)->appends($request->all());
        }

        return view('fees.collect', compact('account', 'searchResults', 'classes', 'sections'));
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

            return redirect()
                ->route('fees.receipts.show', $payment->receipt->id)
                ->with('success', 'Payment of ₹' . number_format($payment->amount, 2) . ' received.');

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
    public function cancel(Request $request, int $id)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255'
        ]);

        try {
            $payment = $this->financeService->cancelPayment(
                $id,
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