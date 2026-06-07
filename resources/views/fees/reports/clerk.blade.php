@extends('fees.layout')

@section('title', 'Clerk Wise Performance')

@section('content')
<div class="container-fluid">
<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <div>
        <h1 class="h4 mb-0 fw-bold">Clerk Collection Performance</h1>
        <p class="text-muted small mb-0">Summary of revenue collected by each staff member.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form action="{{ route('fees.reports.clerk') }}" method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted text-uppercase">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control shadow-none">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted text-uppercase">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control shadow-none">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 rounded-pill">
                    <i class="bi bi-filter me-2"></i> Update Report
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr class="small text-uppercase fw-bold text-muted" style="letter-spacing: 0.5px;">
                    <th class="px-4 py-3">Clerk / Staff Name</th>
                    <th class="py-3 text-center">Receipts Generated</th>
                    <th class="py-3 text-end px-4">Total Revenue Collected</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clerkStats as $stat)
                    <tr>
                        <td class="px-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 35px; height: 35px;">
                                    {{ strtoupper(substr($stat->collector->username ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $stat->collector->full_name ?? $stat->collector->username }}</div>
                                    <div class="small text-muted" style="font-size: 0.7rem;">Staff ID: #{{ $stat->collected_by }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center fw-semibold">{{ number_format($stat->receipt_count) }}</td>
                        <td class="text-end px-4 fw-bold text-primary font-monospace">₹{{ number_format($stat->total_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted">
                            <i class="bi bi-person-x fs-1 d-block mb-3 opacity-25"></i>
                            No collection data found for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($clerkStats->isNotEmpty())
                <tfoot class="bg-light fw-bold border-top">
                    <tr>
                        <td class="px-4 py-3">Total Institutional Revenue</td>
                        <td class="text-center py-3">{{ number_format($clerkStats->sum('receipt_count')) }}</td>
                        <td class="text-end px-4 py-3 font-monospace text-primary h5 mb-0">₹{{ number_format($clerkStats->sum('total_amount'), 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
</div>
@endsection
