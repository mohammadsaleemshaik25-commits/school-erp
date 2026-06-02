<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    /**
     * Display formal list of transactions/receipts
     */
    public function index(Request $request): View
    {
        $receipts = Receipt::with('payment.feeAccount.student', 'payment.collector')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('fees.receipts.index', compact('receipts'));
    }

    /**
     * Renders standard receipt or thermal printable formats (80mm)
     */
    public function show(int $id): View
    {
        $receipt = Receipt::with('payment.feeAccount.student', 'payment.feeAccount.academicYear', 'payment.collector')
            ->findOrFail($id);

        // Check if thermal request flag is active
        if (request()->has('print_format') && request()->get('print_format') === 'thermal') {
            return view('fees.receipts.thermal', compact('receipt'));
        }

        return view('fees.receipts.show', compact('receipt'));
    }
}