@extends('fees.layout')

@section('title', 'Fee Adjustments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h1 class="h3 mb-0 text-gray-800">Fee Adjustment & Concession Desk</h1>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary small text-uppercase">Pending & Logged Requests</h6>
        <div class="btn-group btn-group-sm shadow-sm" role="group">
            <a href="{{ route('fees.adjustments.index') }}" class="btn btn-outline-secondary {{ !request('status') ? 'active' : '' }}">All</a>
            <a href="{{ route('fees.adjustments.index', ['status' => 'PENDING']) }}" class="btn btn-outline-secondary {{ request('status') === 'PENDING' ? 'active' : '' }}">Pending</a>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="small fw-bold text-muted text-uppercase px-4">Student</th>
                    <th class="small fw-bold text-muted text-uppercase">Type</th>
                    <th class="small fw-bold text-muted text-uppercase text-end">Amount</th>
                    <th class="small fw-bold text-muted text-uppercase px-3">Reason</th>
                    <th class="small fw-bold text-muted text-uppercase text-center">Status</th>
                    <th class="small fw-bold text-muted text-uppercase text-end px-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($adjustments as $adj)
                    <tr>
                        <td class="px-4">
                            <div class="fw-bold text-dark">{{ $adj->feeAccount?->student?->student_name ?? 'N/A' }}</div>
                            <div class="small text-muted font-monospace">Adm No: {{ $adj->feeAccount?->student?->admission_no ?? '-' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold text-primary small">{{ $adj->adjustment_type }}</div>
                        </td>
                        <td class="text-end font-monospace fw-bold text-danger">
                            -₹{{ number_format($adj->discount_amount, 2) }}
                        </td>
                        <td class="px-3">
                            <div class="small text-truncate" style="max-width: 200px;" title="{{ $adj->reason }}">
                                {{ $adj->reason }}
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge rounded-pill 
                                {{ $adj->approval_status === 'APPROVED' ? 'bg-success-subtle text-success border border-success' : ($adj->approval_status === 'REJECTED' ? 'bg-danger-subtle text-danger border border-danger' : 'bg-warning-subtle text-warning border border-warning') }}">
                                {{ $adj->approval_status }}
                            </span>
                        </td>
                        <td class="text-end px-4">
                            @if($adj->approval_status === 'PENDING' && in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN', 'PRINCIPAL', 'CORRESPONDENT'], true))
                                <form action="{{ route('fees.adjustments.decide', $adj->adjustment_id) }}" method="POST" class="d-flex gap-1 justify-content-end align-items-center">
                                    @csrf
                                    <input type="text" name="decision_remarks" placeholder="Remarks..." class="form-control form-control-sm" style="width: 120px;">
                                    <button type="submit" name="status" value="APPROVED" class="btn btn-success btn-sm shadow-sm" title="Approve">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button type="submit" name="status" value="REJECTED" class="btn btn-danger btn-sm shadow-sm" title="Reject">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-light text-muted border">No Action</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox h1 d-block mb-2"></i>
                            No concession adjustment logs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white py-3">
        {{ $adjustments->links() }}
    </div>
</div>
@endsection