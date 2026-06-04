@extends('fees.layout')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Dashboard Overview</h1>
        <p class="text-muted small mb-0">Live KPIs for {{ $role === 'CLERK' ? 'your daily collections' : 'school operations and fee performance' }}.</p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        @if($role === 'CLERK')
            <a href="{{ route('fees.reports.closing') }}" class="btn btn-dark btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-file-earmark-check me-1"></i> Daily Closing Report
            </a>
        @endif
        <div class="text-end ms-3">
            <div class="small text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">Session User</div>
            <div class="fw-bold text-primary">{{ auth()->user()->username }} ({{ $role }})</div>
        </div>
    </div>
</div>

@if($role === 'CLERK')
        {{-- CLERK SPECIFIC DASHBOARD --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-primary text-white h-100 overflow-hidden position-relative">
                    <div class="card-body p-3">
                        <div class="small text-uppercase fw-bold opacity-75 mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Today's Collection</div>
                        <div class="h4 fw-bold mb-0">₹{{ number_format($todayCollection, 2) }}</div>
                        <i class="bi bi-currency-rupee position-absolute end-0 bottom-0 opacity-25" style="font-size: 4rem; margin-right: -10px; margin-bottom: -15px;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-white h-100 overflow-hidden position-relative border-start border-success border-4">
                    <div class="card-body p-3">
                        <div class="small text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">My Receipts (Today)</div>
                        <div class="h4 fw-bold mb-0 text-success">{{ $clerkReceipts }}</div>
                        <i class="bi bi-receipt position-absolute end-0 bottom-0 text-success opacity-10" style="font-size: 4rem; margin-right: -10px; margin-bottom: -15px;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-white h-100 overflow-hidden position-relative border-start border-danger border-4">
                    <div class="card-body p-3">
                        <div class="small text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Cancelled Receipts</div>
                        <div class="h4 fw-bold mb-0 text-danger">{{ $cancelledReceipts }}</div>
                        <i class="bi bi-x-circle position-absolute end-0 bottom-0 text-danger opacity-10" style="font-size: 4rem; margin-right: -10px; margin-bottom: -15px;"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-white h-100 overflow-hidden position-relative border-start border-info border-4">
                    <div class="card-body p-3">
                        <div class="small text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Transactions</div>
                        <div class="h4 fw-bold mb-0 text-info">{{ $transactionCount }}</div>
                        <i class="bi bi-list-check position-absolute end-0 bottom-0 text-info opacity-10" style="font-size: 4rem; margin-right: -10px; margin-bottom: -15px;"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- RECENT TRANSACTIONS --}}
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark">Your Recent Transactions</h6>
                <a href="{{ route('fees.receipts.index') }}" class="btn btn-sm btn-link text-decoration-none small fw-bold">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small text-uppercase fw-bold text-muted" style="letter-spacing: 0.5px;">
                            <th class="px-4 py-3">Receipt No.</th>
                            <th class="py-3">Student Name</th>
                            <th class="py-3 text-end">Amount</th>
                            <th class="py-3 text-center">Mode</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-end">Date</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($recentTransactions as $tx)
                            <tr>
                                <td class="px-4 fw-bold text-primary font-monospace">{{ $tx->receipt?->receipt_number ?? 'N/A' }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $tx->feeAccount?->enrollment?->student?->student_name ?? 'N/A' }}</div>
                                    <div class="small text-muted font-monospace" style="font-size: 0.7rem;">{{ $tx->feeAccount?->enrollment?->student?->admission_no ?? '-' }}</div>
                                </td>
                                <td class="text-end fw-bold font-monospace">₹{{ number_format($tx->amount, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border fw-normal">{{ $tx->payment_mode }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill py-1 px-3 
                                        {{ $tx->status === 'SUCCESS' ? 'bg-success-subtle text-success border border-success' : 'bg-danger-subtle text-danger border border-danger' }}">
                                        {{ $tx->status }}
                                    </span>
                                </td>
                                <td class="px-4 text-end text-muted">{{ $tx->payment_date?->format('d M, h:i A') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No transactions found for today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- EXISTING DASHBOARD FOR OTHER ROLES --}}
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-white p-4 h-100 border-start border-primary border-4">
                    <p class="text-sm text-muted text-uppercase fw-bold small mb-2">Total Students</p>
                    <p class="h2 font-semibold text-indigo-900 mb-0">{{ number_format($totalStudents) }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-white p-4 h-100 border-start border-success border-4">
                    <p class="text-sm text-muted text-uppercase fw-bold small mb-2">Today's Collection</p>
                    <p class="h2 font-semibold text-indigo-900 mb-0">₹{{ number_format((float) $todayCollection, 2) }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-white p-4 h-100 border-start border-danger border-4">
                    <p class="text-sm text-muted text-uppercase fw-bold small mb-2">Pending Fees</p>
                    <p class="h2 font-semibold text-indigo-900 mb-0">₹{{ number_format((float) $pendingFees, 2) }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-white p-4 h-100 border-start border-warning border-4">
                    <p class="text-sm text-muted text-uppercase fw-bold small mb-2">Academic Years</p>
                    <p class="h2 font-semibold text-indigo-900 mb-0">{{ number_format($academicYears) }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
