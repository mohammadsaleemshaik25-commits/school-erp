@extends('fees.layout')

@section('title', 'Admission Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Admission Management</h1>
        <a href="{{ route('admissions.create') }}" class="btn btn-primary shadow-sm px-4 rounded-pill">
            <i class="bi bi-plus-lg me-2"></i> New Admission
        </a>
    </div>

    <!-- Enhanced Filters -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-filter me-2 text-primary"></i> Advanced Search</h6>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admissions.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Admission No</label>
                        <input type="text" name="admission_no" value="{{ request('admission_no') }}" class="form-control" placeholder="ADM001">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Student Name</label>
                        <input type="text" name="student_name" value="{{ request('student_name') }}" class="form-control" placeholder="Search name...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Father's Name</label>
                        <input type="text" name="father_name" value="{{ request('father_name') }}" class="form-control" placeholder="Father's name...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Mobile Number</label>
                        <input type="text" name="phone_primary" value="{{ request('phone_primary') }}" class="form-control" placeholder="Primary mobile...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Aadhaar No</label>
                        <input type="text" name="aadhaar_no" value="{{ request('aadhaar_no') }}" class="form-control" placeholder="Aadhaar No">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">PEN Number</label>
                        <input type="text" name="pen_no" value="{{ request('pen_no') }}" class="form-control" placeholder="PEN No">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Class</label>
                        <select name="class_id" class="form-select">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->class_id }}" {{ request('class_id') == $class->class_id ? 'selected' : '' }}>
                                    {{ $class->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Status</label>
                        <select name="admission_status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="APPROVED" {{ request('admission_status') === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                            <option value="PENDING" {{ request('admission_status') === 'PENDING' ? 'selected' : '' }}>PENDING</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-primary flex-grow-1">Apply Filters</button>
                            <a href="{{ route('admissions.index') }}" class="btn btn-light border">Reset</a>
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

    <!-- Admission List -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Adm No</th>
                        <th>Student Name</th>
                        <th>Class & Section</th>
                        <th>Father / Contact</th>
                        <th>Admission Date</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admissions as $admission)
                        <tr>
                            <td class="ps-4 fw-bold text-primary">{{ $admission->student->admission_no }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($admission->student->photo_path)
                                        <img src="{{ asset('storage/' . $admission->student->photo_path) }}" class="rounded-circle me-3" width="40" height="40" style="object-fit: cover;">
                                    @else
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="bi bi-person"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-bold text-dark">{{ $admission->student->student_name }}</div>
                                        <div class="small text-muted">{{ $admission->student->masked_aadhaar }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $admission->classRoom->class_name }}</span>
                                <span class="badge bg-light text-dark border">{{ $admission->section->section_name }}</span>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">{{ $admission->student->father_name }}</div>
                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i> {{ $admission->student->phone_primary }}</div>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($admission->admission_date)->format('d-m-Y') }}</td>
                            <td>
                                <span class="badge rounded-pill {{ $admission->admission_status === 'APPROVED' ? 'bg-success' : 'bg-warning' }}">
                                    {{ $admission->admission_status }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm">
                                    <a href="{{ route('admissions.show', $admission->admission_id) }}" class="btn btn-white btn-sm border" title="View">
                                        <i class="bi bi-eye text-primary"></i>
                                    </a>
                                    <a href="{{ route('admissions.edit', $admission->admission_id) }}" class="btn btn-white btn-sm border" title="Edit">
                                        <i class="bi bi-pencil text-warning"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                <span class="text-muted">No admissions found matching your criteria.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($admissions->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $admissions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
