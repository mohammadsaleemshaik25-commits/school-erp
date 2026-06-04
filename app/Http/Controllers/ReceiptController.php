<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    protected FinanceService $financeService;

    public function __construct(FinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Display formal list of transactions/receipts
     */
    public function index(Request $request): View
    {
        $role = strtoupper(optional(auth()->user()->role)->role_name ?? '');
        $query = Receipt::with('payment.feeAccount.enrollment.student', 'payment.collector')
            ->orderBy('receipt_id', 'desc');

        // CLERK Restriction: Only show own receipts
        if ($role === 'CLERK') {
            $query->whereHas('payment', function($q) {
                $q->where('collected_by', auth()->id());
            });
        }

        // Search Filter
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($inner) use ($q) {
                $inner->where('receipt_number', 'like', "%{$q}%")
                      ->orWhereHas('payment.feeAccount.enrollment.student', function($sub) use ($q) {
                          $sub->where('student_name', 'like', "%{$q}%")
                              ->orWhere('admission_no', 'like', "%{$q}%")
                              ->orWhere('phone_primary', 'like', "%{$q}%")
                              ->orWhere('phone_secondary', 'like', "%{$q}%");
                      });
            });
        }

        // Payment Mode Filter
        if ($request->filled('payment_mode')) {
            $query->whereHas('payment', function($sub) use ($request) {
                $sub->where('payment_mode', $request->payment_mode);
            });
        }

        // Date Range Filter
        if ($request->filled('start_date')) {
            $query->whereHas('payment', function($sub) use ($request) {
                $sub->whereDate('payment_date', '>=', $request->start_date);
            });
        }
        if ($request->filled('end_date')) {
            $query->whereHas('payment', function($sub) use ($request) {
                $sub->whereDate('payment_date', '<=', $request->end_date);
            });
        }

        $receipts = $query->paginate(20)->appends($request->all());

        return view('fees.receipts.index', compact('receipts'));
    }

    /**
     * Renders standard receipt or thermal printable formats (80mm)
     */
    public function show(int $receiptId): View
    {
        $receipt = Receipt::with('payment.feeAccount.enrollment.student', 'payment.feeAccount.enrollment.academicYear', 'payment.collector')
            ->findOrFail($receiptId);

        // Check if thermal request flag is active
        if (request()->has('print_format') && request()->get('print_format') === 'thermal') {
            return view('fees.receipts.thermal', compact('receipt'));
        }

        return view('fees.receipts.show', compact('receipt'));
    }

    /**
     * Handle receipt reprinting
     */
    public function reprint(int $receiptId)
    {
        try {
            $this->financeService->reprintReceipt($receiptId, auth()->id());
            
            return redirect()
                ->route('fees.receipts.show', $receiptId)
                ->with('success', 'Receipt marked for duplicate printing.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}