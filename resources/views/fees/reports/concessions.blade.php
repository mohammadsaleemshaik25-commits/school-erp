@extends('fees.layout')

@section('title', 'Concession Summary Report')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Concession Summary Report</h1>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm">
                <i class="bi bi-printer me-2"></i> Print Report
            </button>
        </div>
    </div>

    <!-- Summary Dashboard -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white rounded-4 overflow-hidden h-100">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem;">Total Requested</div>
                    <div class="h3 fw-bold mb-0">{{ number_format($summary['total_requested']) }}</div>
                    <i class="bi bi-file-earmark-text position-absolute end-0 bottom-0 opacity-25 me-n2 mb-n2" style="font-size: 4rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success text-white rounded-4 overflow-hidden h-100">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem;">Total Approved</div>
                    <div class="h3 fw-bold mb-0">{{ number_format($summary['total_approved']) }}</div>
                    <i class="bi bi-check-circle position-absolute end-0 bottom-0 opacity-25 me-n2 mb-n2" style="font-size: 4rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger text-white rounded-4 overflow-hidden h-100">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem;">Total Rejected</div>
                    <div class="h3 fw-bold mb-0">{{ number_format($summary['total_rejected']) }}</div>
                    <i class="bi bi-x-circle position-absolute end-0 bottom-0 opacity-25 me-n2 mb-n2" style="font-size: 4rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white rounded-4 overflow-hidden h-100">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem;">Total Concession Amount</div>
                    <div class="h3 fw-bold mb-0">₹{{ number_format($summary['total_amount'], 2) }}</div>
                    <i class="bi bi-currency-rupee position-absolute end-0 bottom-0 opacity-25 me-n2 mb-n2" style="font-size: 4rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-search me-2 text-primary"></i> Filter Report</h6>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('fees.reports.concessions') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Student Name</label>
                        <input type="text" name="student_name" value="{{ request('student_name') }}" class="form-control" placeholder="Search by name...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Academic Year</label>
                        <select name="academic_year_id" class="form-select">
                            <option value="">All Years</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->academic_year_id }}" {{ $selectedYearId == $year->academic_year_id ? 'selected' : '' }}>
                                    {{ $year->year_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Class</label>
                        <select name="class_id" class="form-select">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->class_id }}" {{ $selectedClassId == $class->class_id ? 'selected' : '' }}>
                                    {{ $class->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="PENDING" {{ request('status') == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                            <option value="APPROVED" {{ request('status') == 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                            <option value="REJECTED" {{ request('status') == 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Start Date</label>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">End Date</label>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-primary flex-grow-1">Apply Filters</button>
                            <a href="{{ route('fees.reports.concessions') }}" class="btn btn-light border">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase fw-bold">
                    <tr>
                        <th class="ps-4 py-3">Date</th>
                        <th class="py-3">Student</th>
                        <th class="py-3">Class</th>
                        <th class="py-3">Type</th>
                        <th class="py-3 text-end">Amount</th>
                        <th class="py-3">Requester</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="pe-4 py-3">Approver / Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adj)
                        <tr>
                            <td class="ps-4 small">{{ $adj->created_at->format('d-m-Y') }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $adj->feeAccount->enrollment->student->student_name ?? 'N/A' }}</div>
                                <div class="small text-muted font-monospace">{{ $adj->feeAccount->enrollment->student->admission_no ?? '-' }}</div>
                            </td>
                            <td>{{ $adj->feeAccount->enrollment->classRoom->class_name ?? '-' }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $adj->adjustment_type }}</span></td>
                            <td class="text-end fw-bold font-monospace text-primary">₹{{ number_format($adj->discount_amount, 2) }}</td>
                            <td>
                                <div class="small fw-semibold">{{ $adj->requester->username ?? 'N/A' }}</div>
                            </td>
                            <td class="text-center">
                                @php
                                    $statusClass = match($adj->approval_status) {
                                        'PENDING' => 'bg-warning',
                                        'APPROVED' => 'bg-success',
                                        'REJECTED' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge rounded-pill {{ $statusClass }} px-3">
                                    {{ $adj->approval_status }}
                                </span>
                            </td>
                            <td class="pe-4 small">
                                @if($adj->approval_status === 'APPROVED')
                                    <span class="text-success">Approved by {{ $adj->approver->username ?? 'N/A' }}</span>
                                @elseif($adj->approval_status === 'REJECTED')
                                    <span class="text-danger">Rejected: {{ $adj->rejection_reason }}</span>
                                @else
                                    <span class="text-muted italic">Awaiting decision</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                <span class="text-muted">No concession records found.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
