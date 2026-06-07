@extends('fees.layout')

@section('title', 'Books Decision Reports')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Books Decision Reports</h1>
        <a href="{{ route('books.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
            <i class="bi bi-arrow-left me-2"></i> Back to Management
        </a>
    </div>

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white rounded-4 overflow-hidden h-100">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-bold opacity-75 mb-1">Total Books Revenue</div>
                    <div class="h3 fw-bold mb-0">₹{{ number_format($stats['total_revenue'], 2) }}</div>
                    <div class="small mt-2 opacity-75">Collected: ₹{{ number_format($stats['total_collected'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm bg-white border-start border-warning border-4 rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="small text-muted text-uppercase fw-bold mb-1">Pending</div>
                    <div class="h4 fw-bold mb-0 text-warning">{{ $stats['pending_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm bg-white border-start border-primary border-4 rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="small text-muted text-uppercase fw-bold mb-1">School</div>
                    <div class="h4 fw-bold mb-0 text-primary">{{ $stats['school_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm bg-white border-start border-secondary border-4 rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="small text-muted text-uppercase fw-bold mb-1">Outside</div>
                    <div class="h4 fw-bold mb-0 text-secondary">{{ $stats['outside_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm bg-white border-start border-success border-4 rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="small text-muted text-uppercase fw-bold mb-1">Paid</div>
                    <div class="h4 fw-bold mb-0 text-success">{{ $stats['paid_count'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="card border-0 shadow-sm mb-4 rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('books.report') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-2">Report Type</label>
                    <select name="type" class="form-select border-primary">
                        <option value="PENDING" {{ $type === 'PENDING' ? 'selected' : '' }}>Pending Decisions</option>
                        <option value="SCHOOL" {{ $type === 'SCHOOL' ? 'selected' : '' }}>Purchased from School</option>
                        <option value="OUTSIDE" {{ $type === 'OUTSIDE' ? 'selected' : '' }}>Purchased Outside</option>
                        <option value="BOOKS_PAID" {{ $type === 'BOOKS_PAID' ? 'selected' : '' }}>Books Fully Paid</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase mb-2">Filter by Class</label>
                    <select name="class_id" class="form-select">
                        <option value="">All Classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->class_id }}" {{ request('class_id') == $class->class_id ? 'selected' : '' }}>{{ $class->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                </div>
                <div class="col-md-2">
                    <button type="button" onclick="window.print()" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-printer me-2"></i> Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Data -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark">
                Results for: 
                <span class="text-primary">{{ str_replace('_', ' ', $type) }}</span>
                @if(request('class_id'))
                    (Class: {{ $classes->where('class_id', request('class_id'))->first()->class_name ?? '' }})
                @endif
            </h6>
            <span class="badge bg-light text-dark border">{{ count($accounts) }} Records Found</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Adm No</th>
                        <th>Student Name</th>
                        <th>Class & Section</th>
                        <th>Books Fee</th>
                        <th>Amount Paid</th>
                        <th>Balance</th>
                        <th class="pe-4">Decision Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td class="ps-4 fw-bold">{{ $account->enrollment->student->admission_no }}</td>
                            <td>{{ $account->enrollment->student->student_name }}</td>
                            <td>{{ $account->enrollment->classRoom->class_name }} - {{ $account->enrollment->section->section_name }}</td>
                            <td class="fw-semibold">₹{{ number_format($account->books_fee, 2) }}</td>
                            <td class="text-success fw-semibold">₹{{ number_format($account->payments()->where('status', 'SUCCESS')->sum('books_fee_paid'), 2) }}</td>
                            <td class="text-danger fw-semibold">₹{{ number_format(max(0, $account->books_fee_applied - $account->payments()->where('status', 'SUCCESS')->sum('books_fee_paid')), 2) }}</td>
                            <td class="pe-4 text-muted small">
                                {{ $account->books_decision_date ? $account->books_decision_date->format('d M Y') : 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No records found for the selected filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
