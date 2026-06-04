@extends('fees.layout')

@section('title', 'Daily Closing Report')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
        <div>
            <h1 class="h4 mb-0 fw-bold text-dark">Clerk Daily Closing Report</h1>
            <p class="text-muted small mb-0">Summary of collections for the selected date</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Report
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 d-print-none">
        <div class="card-body p-3">
            <form action="{{ route('fees.reports.closing') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Select Date</label>
                    <input type="date" name="date" value="{{ $dateStr }}" class="form-control form-control-sm shadow-none">
                </div>
                @if(count($clerks) > 0)
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Select Clerk</label>
                    <select name="clerk_id" class="form-select form-select-sm shadow-none">
                        @foreach($clerks as $clerk)
                            <option value="{{ $clerk->user_id }}" {{ $selectedClerk?->user_id == $clerk->user_id ? 'selected' : '' }}>
                                {{ $clerk->full_name ?? $clerk->username }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-primary text-white">
                <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Total Collection</div>
                <div class="h2 fw-bold mb-0">₹{{ number_format($stats['total_collection'], 2) }}</div>
                <div class="mt-3 small opacity-75">
                    Successful: {{ $stats['successful_count'] }} | Cancelled: {{ $stats['cancelled_count'] }}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-start border-success border-4">
                <div class="small text-uppercase fw-bold text-muted mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Mode Breakdown</div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Cash:</span>
                    <span class="fw-bold text-success">₹{{ number_format($stats['cash_total'], 2) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">UPI:</span>
                    <span class="fw-bold text-primary">₹{{ number_format($stats['upi_total'], 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-start border-info border-4">
                <div class="small text-uppercase fw-bold text-muted mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Component Breakdown</div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Tuition:</span>
                    <span class="fw-bold text-dark">₹{{ number_format($stats['tuition_total'], 2) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Books:</span>
                    <span class="fw-bold text-dark">₹{{ number_format($stats['books_total'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Tables --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="m-0 fw-bold text-dark">Successful Transactions</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-uppercase fw-bold text-muted" style="letter-spacing: 0.5px;">
                        <th class="px-4 py-3">Receipt</th>
                        <th class="py-3">Student</th>
                        <th class="py-3">Mode</th>
                        <th class="py-3 text-end">Books</th>
                        <th class="py-3 text-end">Tuition</th>
                        <th class="px-4 py-3 text-end">Total</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="px-4 fw-bold font-monospace">{{ $payment->receipt?->receipt_number ?? '-' }}</td>
                            <td>{{ $payment->feeAccount?->student?->student_name ?? 'N/A' }}</td>
                            <td>{{ $payment->payment_mode }}</td>
                            <td class="text-end font-monospace">₹{{ number_format($payment->books_fee_paid, 2) }}</td>
                            <td class="text-end font-monospace">₹{{ number_format($payment->tuition_fee_paid, 2) }}</td>
                            <td class="px-4 text-end fw-bold font-monospace">₹{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No successful payments today.</td></tr>
                    @endforelse
                </tbody>
                @if($payments->count() > 0)
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td colspan="3" class="px-4 py-2 text-end">TOTALS</td>
                        <td class="text-end py-2">₹{{ number_format($stats['books_total'], 2) }}</td>
                        <td class="text-end py-2">₹{{ number_format($stats['tuition_total'], 2) }}</td>
                        <td class="px-4 text-end py-2">₹{{ number_format($stats['total_collection'], 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    @if($cancelledPayments->count() > 0)
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-top border-danger border-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="m-0 fw-bold text-danger">Cancelled Transactions</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 text-muted">
                <thead class="bg-light">
                    <tr class="small text-uppercase fw-bold text-muted">
                        <th class="px-4 py-3">Receipt</th>
                        <th class="py-3">Student</th>
                        <th class="py-3">Reason</th>
                        <th class="px-4 py-3 text-end">Amount</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @foreach ($cancelledPayments as $payment)
                        <tr>
                            <td class="px-4 font-monospace">{{ $payment->receipt?->receipt_number ?? '-' }}</td>
                            <td>{{ $payment->feeAccount?->student?->student_name ?? 'N/A' }}</td>
                            <td>{{ $payment->receipt?->cancellation_reason ?? '-' }}</td>
                            <td class="px-4 text-end font-monospace">₹{{ number_format($payment->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="mt-5 text-center d-none d-print-block">
        <hr>
        <h5 class="fw-bold">VIKAS HIGH SCHOOL</h5>
        <p class="small mb-1">Daily Closing Report - {{ Carbon\Carbon::parse($dateStr)->format('d-m-Y') }}</p>
        <p class="small mb-4">Clerk: {{ $selectedClerk?->full_name ?? $selectedClerk?->username }}</p>
        
        <div class="d-flex justify-content-around mt-5">
            <div style="border-top: 1px solid #000; width: 200px; padding-top: 5px;">Clerk Signature</div>
            <div style="border-top: 1px solid #000; width: 200px; padding-top: 5px;">Principal Signature</div>
        </div>
        <p class="mt-4 small text-muted">Printed on: {{ now()->format('d-m-Y H:i:s') }}</p>
    </div>
</div>

<style>
@media print {
    body { background: white !important; }
    .container-fluid { width: 100% !important; padding: 0 !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; margin-bottom: 20px !important; }
    .bg-primary { background-color: #0d6efd !important; -webkit-print-color-adjust: exact; color: white !important; }
    .text-white { color: white !important; }
}
</style>
@endsection
