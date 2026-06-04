@extends('fees.layout')

@section('title', 'Student Ledger - ' . ($account->student?->student_name ?? 'N/A'))

@section('content')
<div class="container-fluid">

    <div class="text-center mb-4">
        <img src="{{ asset('build/assets/school/logo.png') }}"
             alt="School Logo"
             style="height:80px;width:auto;">

        <h3 class="fw-bold mt-2 mb-0">
            VIKAS HIGH SCHOOL
        </h3>

        <p class="text-muted mb-0">
            Student Financial Ledger
        </p>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
        <div>
            <h1 class="h4 mb-0 fw-bold text-dark">Student Ledger</h1>
            <p class="text-muted small mb-0">Financial history for {{ $account->student?->student_name }}</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Ledger
            </button>
            <a href="{{ route('fees.collect', ['account_id' => $account->account_id]) }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-currency-rupee me-1"></i> Collect Fee
            </a>
        </div>
    </div>

    {{-- Student Profile & Summary Card --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-person-badge me-2 text-primary"></i>Student Profile</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="row mb-2">
                                <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Admission No</div>
                                <div class="col-7 fw-bold font-monospace text-primary">{{ $account->student?->admission_no ?? '-' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Student Name</div>
                                <div class="col-7 fw-bold text-dark">{{ $account->student?->student_name ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Father's Name</div>
                                <div class="col-7 fw-semibold">{{ $account->student?->father_name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row mb-2">
                                <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Class / Section</div>
                                <div class="col-7 fw-bold text-dark">{{ optional($account->classRoom)->class_name ?? '-' }} / {{ optional($account->section)->section_name ?? '-' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Academic Year</div>
                                <div class="col-7 fw-bold">{{ optional($account->academicYear)->year_name ?? '-' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Books Status</div>
                                <div class="col-7">
                                    <span class="badge rounded-pill px-2 py-1 {{ $account->books_status === 'BOOKS_PAID' ? 'bg-success' : ($account->books_status === 'SCHOOL' ? 'bg-primary' : ($account->books_status === 'OUTSIDE' ? 'bg-info' : 'bg-warning')) }}">
                                        {{ $account->books_status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-top border-primary border-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="m-0 fw-bold text-dark">Financial Summary</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Due</span>
                        <span class="fw-bold font-monospace">₹{{ number_format($account->total_due, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Paid</span>
                        <span class="fw-bold font-monospace text-success">₹{{ number_format($account->total_paid, 2) }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold text-dark">Outstanding</span>
                        <span class="fw-bold font-monospace text-danger h5 mb-0">₹{{ number_format($account->remaining_balance, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment History Table --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">Payment History</h6>
            <div class="badge bg-light text-dark border fw-normal">{{ $account->payments->count() }} Transactions</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-uppercase fw-bold text-muted" style="letter-spacing: 0.5px;">
                        <th class="px-4 py-3">Receipt No.</th>
                        <th class="py-3">Date</th>
                        <th class="py-3">Component</th>
                        <th class="py-3 text-end">Amount</th>
                        <th class="py-3 text-center">Mode</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3">Collected By</th>
                        <th class="px-4 py-3 text-end d-print-none">Action</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse ($account->payments as $payment)
                        <tr class="{{ $payment->status === 'CANCELLED' ? 'table-light text-muted' : '' }}">
                            <td class="px-4 fw-bold font-monospace">
                                @if($payment->receipt)
                                    <a href="{{ route('fees.receipts.show', $payment->receipt->receipt_id) }}" class="text-primary text-decoration-none">
                                        {{ $payment->receipt->receipt_number }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $payment->payment_date->format('d-m-Y H:i') }}</td>
                            <td>
                                <span class="badge bg-light text-dark border fw-normal">{{ $payment->fee_component_type }}</span>
                            </td>
                            <td class="text-end fw-bold font-monospace">₹{{ number_format($payment->amount, 2) }}</td>
                            <td class="text-center">{{ $payment->payment_mode }}</td>
                            <td class="text-center">
                                <span class="badge rounded-pill py-1 px-3 
                                    {{ $payment->status === 'SUCCESS' ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger' }}">
                                    {{ $payment->status }}
                                </span>
                            </td>
                            <td>{{ $payment->collector?->full_name ?? $payment->collector?->username ?? '-' }}</td>
                            <td class="px-4 text-end d-print-none">
                                @if($payment->receipt)
                                    <a class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm" href="{{ route('fees.receipts.show', $payment->receipt->receipt_id) }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                No payment records found for this account.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Summary Totals Card --}}
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">
                <div class="small text-uppercase fw-bold text-muted mb-1" style="font-size: 0.65rem;">Books Paid</div>
                <div class="h5 fw-bold mb-0 text-dark">₹{{ number_format($summary['books_paid'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">
                <div class="small text-uppercase fw-bold text-muted mb-1" style="font-size: 0.65rem;">Tuition Paid</div>
                <div class="h5 fw-bold mb-0 text-dark">₹{{ number_format($summary['tuition_paid'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">
                <div class="small text-uppercase fw-bold text-muted mb-1" style="font-size: 0.65rem;">Cancelled</div>
                <div class="h5 fw-bold mb-0 text-danger">₹{{ number_format($summary['cancelled_payments'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-dark text-white">
                <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.65rem;">Outstanding Balance</div>
                <div class="h5 fw-bold mb-0">₹{{ number_format($summary['outstanding'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="mt-5 text-center d-none d-print-block">

    <img src="{{ asset('build/assets/school/logo.png') }}"
         alt="School Logo"
         style="height:70px;width:auto;margin-bottom:10px;">

    <h5 class="fw-bold">
        VIKAS HIGH SCHOOL
    </h5>

    <hr>

    <p class="small text-muted">
        This is a system-generated student ledger report.
    </p>

    <p class="small text-muted">
        Printed on: {{ now()->format('d-m-Y H:i:s') }}
    </p>

</div>
        <hr>
        <p class="small text-muted">This is a system-generated student ledger report.</p>
        <p class="small text-muted">Printed on: {{ now()->format('d-m-Y H:i:s') }}</p>
    </div>
</div>

<style>
@media print {
    body { background: white !important; }
    .container-fluid { width: 100% !important; padding: 0 !important; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
    .bg-light { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; }
    .badge { border: 1px solid #ccc !important; color: black !important; background: transparent !important; }
}
</style>
@endsection
