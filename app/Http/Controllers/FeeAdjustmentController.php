<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeeAdjustmentRequest;
use App\Http\Requests\DecideFeeAdjustmentRequest;
use App\Services\FinanceService;
use App\Models\StudentFeeAdjustment;
use Illuminate\Http\Request;
use Exception;

class FeeAdjustmentController extends Controller
{
    protected FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * View requested adjustments / concessions
     */
    public function index(Request $request)
    {
        $query = StudentFeeAdjustment::with(['feeAccount.enrollment.student', 'feeAccount.enrollment.classRoom', 'requester', 'approver'])
            ->orderBy('created_at', 'desc');

        // Status Filter
        if ($request->filled('status')) {
            $query->where('approval_status', $request->status);
        }

        // Search Filter
        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereHas('feeAccount.enrollment.student', function ($sub) use ($q) {
                $sub->where('student_name', 'like', "%{$q}%")
                    ->orWhere('admission_no', 'like', "%{$q}%");
            });
        }

        // Date Filters
        if ($request->filled('date_range')) {
            if ($request->date_range === 'today') {
                $query->whereDate('created_at', \Carbon\Carbon::today());
            } elseif ($request->date_range === 'month') {
                $query->whereMonth('created_at', \Carbon\Carbon::now()->month)
                      ->whereYear('created_at', \Carbon\Carbon::now()->year);
            }
        }

        // Dashboard Card Stats
        $stats = [
            'pending_count' => StudentFeeAdjustment::where('approval_status', 'PENDING')->count(),
            'approved_count' => StudentFeeAdjustment::where('approval_status', 'APPROVED')->count(),
            'rejected_count' => StudentFeeAdjustment::where('approval_status', 'REJECTED')->count(),
            'total_approved_amount' => StudentFeeAdjustment::where('approval_status', 'APPROVED')->sum('discount_amount'),
        ];

        $adjustments = $query->paginate(15)->withQueryString();

        return view('fees.adjustments.index', compact('adjustments', 'stats'));
    }

    /**
     * AJAX Search for Student Fee Accounts
     */
    public function searchAccounts(Request $request)
    {
        $q = $request->get('q');
        
        $accounts = \App\Models\StudentFeeAccount::with(['enrollment.student', 'enrollment.classRoom', 'enrollment.section'])
            ->where('status', '!=', 'PAID') // Only search for students with due
            ->whereHas('enrollment.student', function($query) use ($q) {
                $query->where('student_name', 'like', "%{$q}%")
                      ->orWhere('admission_no', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get()
            ->map(function($account) {
                return [
                    'id' => $account->account_id,
                    'text' => $account->enrollment->student->student_name . " (" . $account->enrollment->student->admission_no . ")",
                    'student_name' => $account->enrollment->student->student_name,
                    'admission_no' => $account->enrollment->student->admission_no,
                    'class' => $account->enrollment->classRoom->class_name,
                    'section' => $account->enrollment->section->section_name ?? '-',
                    'due' => $account->remaining_balance,
                    'tuition' => $account->final_tuition_fee,
                    'books' => $account->books_fee_applied,
                    'waived' => $account->waived_amount
                ];
            });

        return response()->json($accounts);
    }

    /**
     * Create concession / waiver request
     */
    public function store(StoreFeeAdjustmentRequest $request)
    {
        try {
            $data = $request->validated();
            
            // If discount percentage is provided but amount is not, calculate the amount
            if (empty($data['discount_amount']) && !empty($data['discount_percent'])) {
                $account = \App\Models\StudentFeeAccount::findOrFail($data['account_id']);
                $baseFee = (float) $account->final_tuition_fee;
                $data['discount_amount'] = round(($baseFee * (float)$data['discount_percent']) / 100, 2);
            }

            $this->financeService->requestAdjustment($data, auth()->id());

            return redirect()
                ->route('fees.adjustments.index')
                ->with('success', 'Concession request logged successfully. Awaiting administrative review.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Authorize decision on concession (Principal/Correspondent only)
     */
    public function decide(DecideFeeAdjustmentRequest $request, int $adjustmentId)
    {
        try {
            $adjustment = $this->financeService->decideAdjustment(
                $adjustmentId,
                $request->validated()['status'],
                $request->validated()['decision_remarks'] ?? null,
                auth()->id()
            );

            return redirect()
                ->back()
                ->with('success', 'Concession request #' . $adjustmentId . ' has been ' . strtolower($adjustment->approval_status) . '.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}