@extends('fees.layout')

@section('title', 'Promotion History Report')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Promotion History Report</h1>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm">
                <i class="bi bi-printer me-2"></i> Print
            </button>
            <a href="{{ route('promotions.index') }}" class="btn btn-primary btn-sm px-3 rounded-pill shadow-sm">
                <i class="bi bi-arrow-left me-2"></i> Back to Promotions
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <form action="{{ route('promotions.report') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-2">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-2">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Filter History</button>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('promotions.report') }}" class="btn btn-light border w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- History Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase fw-bold">
                    <tr>
                        <th class="ps-4">Date & Time</th>
                        <th>Processed By</th>
                        <th>Details</th>
                        <th>Target Info</th>
                        <th class="pe-4">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">{{ $log->created_at->format('d M Y') }}</div>
                                <div class="small text-muted">{{ $log->created_at->format('h:i A') }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $log->user->full_name ?? $log->user->username }}</div>
                                <div class="small text-muted text-uppercase" style="font-size: 0.65rem;">{{ $log->user->role->role_name ?? 'STAFF' }}</div>
                            </td>
                            <td>
                                <div class="small">{{ $log->old_value }}</div>
                            </td>
                            <td>
                                <div class="small text-primary fw-bold">{{ $log->new_value }}</div>
                            </td>
                            <td class="pe-4">
                                <span class="badge bg-light text-muted font-monospace">{{ $log->ip_address }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-clock-history fs-1 text-muted d-block mb-3"></i>
                                <span class="text-muted">No promotion history found.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
