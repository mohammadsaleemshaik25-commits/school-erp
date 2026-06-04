@extends('fees.layout')

@section('title', 'Daily Collection Log')

@section('content')
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <div>
        <h1 class="h4 mb-0 fw-bold">Daily Collection Log</h1>
        <p class="text-muted small mb-0">Collections recorded on {{ \Carbon\Carbon::parse($dateStr)->format('d F Y') }}.</p>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('fees.reports.daily') }}" method="GET" class="d-flex gap-2">
            <input type="date" name="date" value="{{ $dateStr }}" class="form-control form-control-sm rounded-pill px-3 shadow-none">
            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-filter me-1"></i> Filter
            </button>
        </form>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm d-print-none">
            <i class="bi bi-printer me-1"></i> Print
        </button>
    </div>
</div>

{{-- Summary Card --}}
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white overflow-hidden position-relative">
    <div class="card-body p-4 d-flex justify-content-between align-items-center">
        <div>
            <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem; letter-spacing: 1px;">Total Revenue for {{ \Carbon\Carbon::parse($dateStr)->format('M d, Y') }}</div>
            <div class="h2 fw-bold mb-0">₹{{ number_format($totalCollected, 2) }}</div>
        </div>
        <div class="text-end">
            <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem;">Transactions</div>
            <div class="h3 fw-bold mb-0">{{ $payments->count() }}</div>
        </div>
        <i class="bi bi-cash-stack position-absolute end-0 top-0 opacity-10" style="font-size: 6rem; margin-right: -20px; margin-top: -20px;"></i>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr class="small text-uppercase fw-bold text-muted" style="letter-spacing: 0.5px;">
                    <th class="px-4 py-3">Receipt No.</th>
                    <th class="py-3">Student Name</th>
                    <th class="py-3 text-center">Mode</th>
                    <th class="py-3">Collector</th>
                    <th class="px-4 py-3 text-end">Amount</th>
                </tr>
            </thead>
            <tbody class="small font-monospace">
                @forelse($payments as $p)
                    <tr>
                        <td class="px-4 fw-bold text-primary">{{ $p->receipt?->receipt_number ?? 'N/A' }}</td>
                        <td class="font-sans">
                            <div class="fw-bold text-dark">{{ $p->feeAccount?->student?->student_name ?? 'N/A' }}</div>
                            <div class="small text-muted" style="font-size: 0.7rem;">{{ $p->feeAccount?->student?->admission_no ?? '-' }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border fw-normal">{{ $p->payment_mode }}</span>
                        </td>
                        <td class="font-sans text-muted small">
                            {{ $p->collector?->full_name ?? $p->collector?->username ?? 'N/A' }}
                        </td>
                        <td class="px-4 text-end fw-bold text-dark">
                            ₹{{ number_format($p->amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted font-sans">
                            <i class="bi bi-calendar-x fs-1 d-block mb-3 opacity-25"></i>
                            No successful collections recorded for this date.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection