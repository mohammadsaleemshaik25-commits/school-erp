@extends('fees.layout')

@section('title', 'Books Purchase Decisions')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Books Purchase Decisions</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('books.report') }}" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm">
                <i class="bi bi-bar-chart me-2"></i> Decision Reports
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-body p-4">
            <form action="{{ route('books.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Search Student</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 ps-0" placeholder="Name or Admission No...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Status</label>
                        <select name="books_status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="PENDING" {{ request('books_status') === 'PENDING' ? 'selected' : '' }}>PENDING</option>
                            <option value="SCHOOL" {{ request('books_status') === 'SCHOOL' ? 'selected' : '' }}>SCHOOL</option>
                            <option value="OUTSIDE" {{ request('books_status') === 'OUTSIDE' ? 'selected' : '' }}>OUTSIDE</option>
                            <option value="BOOKS_PAID" {{ request('books_status') === 'BOOKS_PAID' ? 'selected' : '' }}>BOOKS_PAID</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Class</label>
                        <select name="class_id" class="form-select">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->class_id }}" {{ request('class_id') == $class->class_id ? 'selected' : '' }}>{{ $class->class_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Academic Year</label>
                        <select name="academic_year_id" class="form-select">
                            <option value="">All Years</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->academic_year_id }}" {{ request('academic_year_id') == $year->academic_year_id ? 'selected' : '' }}>{{ $year->year_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                            <a href="{{ route('books.index') }}" class="btn btn-light border">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Results Table -->
    @if($accounts === null)
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body text-center py-5">
                <i class="bi bi-search fs-1 text-muted d-block mb-3"></i>
                <h5 class="text-muted mb-2">Enter search criteria to view students</h5>
                <p class="text-muted small">Use the filters above to search for students by name, admission number, class, or academic year.</p>
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Class & Section</th>
                            <th>Academic Year</th>
                            <th>Current Status</th>
                            <th>Decision By</th>
                            <th>Decision Date</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $account->enrollment->student->student_name }}</div>
                                    <div class="small text-muted">{{ $account->enrollment->student->admission_no }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $account->enrollment->classRoom->class_name }}</span>
                                    <span class="badge bg-light text-dark border">{{ $account->enrollment->section->section_name }}</span>
                                </td>
                                <td>{{ $account->enrollment->academicYear->year_name }}</td>
                                <td>
                                    @php
                                        $badgeClass = match($account->books_status) {
                                            'PENDING' => 'bg-warning',
                                            'SCHOOL' => 'bg-primary',
                                            'OUTSIDE' => 'bg-secondary',
                                            'BOOKS_PAID' => 'bg-success',
                                            default => 'bg-light text-dark'
                                        };
                                    @endphp
                                    <span class="badge rounded-pill {{ $badgeClass }} px-3">
                                        {{ $account->books_status }}
                                    </span>
                                </td>
                                <td>
                                    @if($account->decisionMaker)
                                        <div class="small fw-semibold">{{ $account->decisionMaker->full_name ?? $account->decisionMaker->username }}</div>
                                    @else
                                        <span class="text-muted small">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    @if($account->books_decision_date)
                                        <div class="small text-muted">{{ $account->books_decision_date->format('d M Y, h:i A') }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if($account->books_status !== 'BOOKS_PAID' || in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN'], true))
                                        <a href="{{ route('books.edit', $account->account_id) }}" class="btn btn-white btn-sm border shadow-sm px-3 rounded-pill">
                                            <i class="bi bi-pencil-square me-1"></i> Change Status
                                        </a>
                                    @else
                                        <button class="btn btn-light btn-sm border disabled px-3 rounded-pill" title="Status Locked">
                                            <i class="bi bi-lock-fill"></i> Locked
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                    <span class="text-muted">No students found matching your criteria.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($accounts->hasPages())
                <div class="card-footer bg-white py-3">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
