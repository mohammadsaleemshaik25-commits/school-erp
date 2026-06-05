<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBooksFeeRequest;
use App\Services\BooksDecisionService;
use Exception;

class BooksFeeController extends Controller
{
    protected BooksDecisionService $booksService;

    public function __construct(BooksDecisionService $booksService)
    {
        $this->booksService = $booksService;
    }

    /**
     * Updates books fee state based on school purchase decisions
     */
    public function update(UpdateBooksFeeRequest $request, int $accountId)
    {
        try {
            $this->booksService->updateDecision(
                $accountId,
                $request->validated()['books_status'],
                auth()->id(),
                $request->ip()
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