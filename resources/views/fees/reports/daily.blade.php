@extends('fees.layout')

@section('title', 'Daily Revenue Collection')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h1 class="h3 mb-0 text-gray-800">Daily Revenue Collection</h1>
    <form action="{{ route('reports.daily') }}" method="GET" class="d-flex gap-2 align-items-center">
        <input type="date" name="date" value="{{ $dateStr }}" class="form-control form-control-sm shadow-sm">
        <button type="submit" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-filter me-1"></i> Filter Date
        </button>
    </form>
</div>

<!-- Summary Card -->
<div class="card bg-primary text-white shadow-sm mb-4">
    <div class="card-body d-flex justify-content-between align-items-center py-4">
        <div>
            <h6 class="text-white-50 text-uppercase small fw-bold mb-1">Total Collections Recorded for {{ \Carbon\Carbon::parse($dateStr)->format('d F Y') }}</h6>
            <p class="h2 fw-extrabold font-monospace mb-0">₹{{ number_format($totalCollected, 2) }}</p>
        </div>
        <button onclick="window.print()" class="btn btn-light btn-sm fw-bold shadow-sm d-print-none">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 font-weight-bold text-primary small text-uppercase">Collection Log</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small fw-bold text-muted text-uppercase px-4">Receipt No</th>
                    <th class="small fw-bold text-muted text-uppercase">Student</th>
                    <th class="small fw-bold text-muted text-uppercase text-center">Mode</th>
                    <th class="small fw-bold text-muted text-uppercase">Recorded By</th>
                    <th class="small fw-bold text-muted text-uppercase text-end px-4">Amount</th>
                </tr>
            </thead>
            <tbody class="font-monospace small">
                @forelse($payments as $p)
                    <tr>
                        <td class="px-4 fw-bold text-primary">
                            {{ $p->receipt->receipt_number ?? 'N/A' }}
                        </td>
                        <td class="font-sans fw-semibold">
                            {{ $p->feeAccount->student->student_name }}
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill {{ $p->payment_mode === 'UPI' ? 'bg-info-subtle text-info border border-info' : 'bg-success-subtle text-success border border-success' }}">
                                {{ $p->payment_mode }}
                            </span>
                        </td>
                        <td class="font-sans text-muted">
                            {{ $p->collector->full_name ?? $p->collector->username }}
                        </td>
                        <td class="text-end fw-bold px-4">
                            ₹{{ number_format($p->amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted font-sans">
                            <i class="bi bi-calendar-x h1 d-block mb-2"></i>
                            No collections recorded for this date.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection