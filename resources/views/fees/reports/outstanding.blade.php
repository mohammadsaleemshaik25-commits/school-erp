@extends('fees.layout')

@section('title', 'Outstanding Fee Report')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Outstanding Fee Report</h1>
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
                    <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem;">Students with Due</div>
                    <div class="h3 fw-bold mb-0">{{ number_format($summary['total_students_with_due']) }}</div>
                    <i class="bi bi-people position-absolute end-0 bottom-0 opacity-25 me-n2 mb-n2" style="font-size: 4rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger text-white rounded-4 overflow-hidden h-100">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem;">Total Outstanding</div>
                    <div class="h3 fw-bold mb-0">₹{{ number_format($summary['total_outstanding'], 2) }}</div>
                    <i class="bi bi-currency-rupee position-absolute end-0 bottom-0 opacity-25 me-n2 mb-n2" style="font-size: 4rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning text-dark rounded-4 overflow-hidden h-100">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem;">Total Books Due</div>
                    <div class="h3 fw-bold mb-0">₹{{ number_format($summary['total_books_due'], 2) }}</div>
                    <i class="bi bi-book position-absolute end-0 bottom-0 opacity-25 me-n2 mb-n2" style="font-size: 4rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-info text-white rounded-4 overflow-hidden h-100">
                <div class="card-body p-4">
                    <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem;">Total Tuition Due</div>
                    <div class="h3 fw-bold mb-0">₹{{ number_format($summary['total_tuition_due'], 2) }}</div>
                    <i class="bi bi-mortarboard position-absolute end-0 bottom-0 opacity-25 me-n2 mb-n2" style="font-size: 4rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-search me-2 text-primary"></i> Search & Filters</h6>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('fees.reports.outstanding') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Student Name</label>
                        <input type="text" name="student_name" value="{{ request('student_name') }}" class="form-control" placeholder="Search by name...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Admission No</label>
                        <input type="text" name="admission_no" value="{{ request('admission_no') }}" class="form-control" placeholder="ADM001">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Father's Name</label>
                        <input type="text" name="father_name" value="{{ request('father_name') }}" class="form-control" placeholder="Search father's name...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Aadhaar No</label>
                        <input type="text" name="aadhaar_no" value="{{ request('aadhaar_no') }}" class="form-control" placeholder="12-digit number">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Mobile Number</label>
                        <input type="text" name="phone_primary" value="{{ request('phone_primary') }}" class="form-control" placeholder="Search mobile...">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Academic Year</label>
                        <select name="academic_year_id" class="form-select">
                            @foreach($academicYears as $year)
                                <option value="{{ $year->academic_year_id }}" {{ $selectedYearId == $year->academic_year_id ? 'selected' : '' }}>
                                    {{ $year->year_name }} {{ $year->is_active ? '(Active)' : '' }}
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
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-primary flex-grow-1">Apply Filters</button>
                            <a href="{{ route('fees.reports.outstanding') }}" class="btn btn-light border">Reset</a>
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
                        <th class="ps-4 py-3">Student</th>
                        <th class="py-3">Parent Info</th>
                        <th class="py-3">Class/Section</th>
                        <th class="py-3 text-end">Total Due</th>
                        <th class="py-3 text-end">Total Paid</th>
                        <th class="py-3 text-end">Outstanding</th>
                        <th class="pe-4 py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        @if($account->student?->photo_path)
                                            <img src="{{ asset('storage/' . $account->student->photo_path) }}" class="rounded-circle border" width="45" height="45" style="object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                <i class="bi bi-person fs-5"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $account->student?->student_name ?? 'N/A' }}</div>
                                        <div class="small text-muted font-monospace">{{ $account->student?->admission_no ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">{{ $account->student?->father_name ?? 'N/A' }}</div>
                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i> {{ $account->student?->phone_primary ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $account->enrollment?->classRoom?->class_name ?? '-' }}</span>
                                <span class="badge bg-light text-dark border">{{ $account->enrollment?->section?->section_name ?? '-' }}</span>
                            </td>
                            <td class="text-end font-monospace">₹{{ number_format($account->total_due, 2) }}</td>
                            <td class="text-end font-monospace text-success">₹{{ number_format($account->total_paid, 2) }}</td>
                            <td class="text-end font-monospace fw-bold text-danger">
                                <div class="d-flex flex-column align-items-end">
                                    ₹{{ number_format($account->remaining_balance, 2) }}
                                    @if($account->remaining_balance > 10000)
                                        <span class="badge bg-warning text-dark rounded-pill mt-1" style="font-size: 0.65rem;">HIGH DUE</span>
                                    @endif
                                </div>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="btn-group">
                                    <a href="{{ route('fees.collect', ['account_id' => $account->account_id]) }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" title="Collect Fee">
                                        <i class="bi bi-wallet2"></i>
                                    </a>
                                    <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 ms-1 shadow-sm" title="Request Concession"
                                            onclick="openConcessionModal({
                                                account_id: '{{ $account->account_id }}',
                                                student_name: '{{ addslashes($account->student->student_name) }}',
                                                admission_no: '{{ $account->student->admission_no }}',
                                                class_name: '{{ $account->enrollment->classRoom->class_name }}',
                                                tuition_fee: '{{ $account->final_tuition_fee }}',
                                                current_due: '{{ $account->remaining_balance }}'
                                            })">
                                        <i class="bi bi-percent"></i>
                                    </button>
                                    <a href="{{ route('fees.ledger', $account->account_id) }}" class="btn btn-white btn-sm border rounded-pill px-3 ms-1 shadow-sm" title="View Ledger">
                                        <i class="bi bi-journal-text"></i>
                                    </a>
                                    <button class="btn btn-white btn-sm border rounded-pill px-3 ms-1 shadow-sm" title="Print Due Slip">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </button>
                                    @if(in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN']))
                                    <form action="{{ route('admissions.destroy', $account->student?->admission_id ?? 0) }}" method="POST" class="d-inline" onsubmit="return confirm('CRITICAL WARNING: This will permanently delete the student and all records. Proceed?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-white btn-sm border rounded-pill px-3 ms-1 shadow-sm text-danger" title="Delete Student">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                <span class="text-muted">No outstanding fee records found.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($accounts->hasPages())
            <div class="card-footer bg-white py-3 border-top">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
