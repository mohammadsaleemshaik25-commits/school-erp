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
        $query = StudentFeeAdjustment::with(['feeAccount.student', 'requester', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('approval_status', $request->status);
        }

        $adjustments = $query->paginate(15);

        return view('fees.adjustments.index', compact('adjustments'));
    }

    /**
     * Create concession / waiver request
     */
    public function store(StoreFeeAdjustmentRequest $request)
    {
        try {
            $this->financeService->requestAdjustment($request->validated(), auth()->id());

            return redirect()
                ->back()
                ->with('success', 'Concession request logged successfully. Awaiting administrative review.');
        } catch (Exception $e) {
            return redirect()
                ->back()
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