<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBooksFeeRequest;
use App\Services\FinanceService;
use Exception;

class BooksFeeController extends Controller
{
    protected FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Updates books fee state based on school purchase decisions
     */
    public function update(UpdateBooksFeeRequest $request, int $accountId)
    {
        try {
            $this->financeService->updateBooksDecision(
                $accountId,
                $request->validated()['books_status'],
                auth()->id()
            );

            return redirect()
                ->back()
                ->with('success', 'Books purchase decision finalized.');
        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}