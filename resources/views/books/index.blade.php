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

    @if(count($newAdmissions) > 0)
        <div class="alert alert-info border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center p-4">
            <div class="bg-info bg-opacity-10 p-3 rounded-circle me-4">
                <i class="bi bi-info-circle-fill fs-3 text-info"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1">New Approved Admissions</h5>
                <p class="mb-0 text-muted">There are {{ count($newAdmissions) }} newly approved admissions awaiting a books decision to create their fee accounts.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
            <div class="card-header bg-info bg-opacity-10 py-3 border-bottom">
                <h6 class="m-0 fw-bold text-info"><i class="bi bi-person-plus me-2"></i> Finalize New Admissions</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Class & Section</th>
                            <th>Academic Year</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($newAdmissions as $adm)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $adm->student->student_name }}</div>
                                    <div class="small text-muted">{{ $adm->student->admission_no }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $adm->classRoom->class_name }}</span>
                                    <span class="badge bg-light text-dark border">{{ $adm->section->section_name }}</span>
                                </td>
                                <td>{{ $adm->academicYear->year_name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 rounded-pill">APPROVED</span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-info text-white btn-sm px-4 rounded-pill shadow-sm fw-bold" 
                                            onclick="openFinalizeModal('{{ $adm->admission_id }}', '{{ $adm->student->student_name }}', '{{ $adm->student->admission_no }}')">
                                        <i class="bi bi-check2-circle me-1"></i> Finalize Fee Account
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Results Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> Pending Decisions (Existing Accounts)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Student</th>
                        <th>Class & Section</th>
                        <th>Academic Year</th>
                        <th class="text-center">Status</th>
                        <th>Decision By</th>
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
                            <td class="text-center">
                                <span class="badge rounded-pill bg-warning text-dark px-3">PENDING</span>
                            </td>
                            <td>
                                <span class="text-muted small">Not set</span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="{{ route('books.edit', $account->account_id) }}" class="btn btn-white btn-sm border shadow-sm px-3 rounded-pill">
                                    <i class="bi bi-pencil-square me-1"></i> Change Status
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-check2-circle fs-1 text-success d-block mb-3"></i>
                                <span class="text-muted">No pending decisions for existing accounts.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Finalize Admission Modal -->
<div class="modal fade" id="finalizeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-info text-white py-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-shield-check me-2"></i> Finalize Admission & Fee Account</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="finalizeForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning small border-0 mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        This action will create the student's enrollment and fee account. This cannot be easily reversed.
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Student Details</label>
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="fw-bold text-dark" id="modal_student_name">Loading...</div>
                            <div class="small text-muted" id="modal_admission_no">...</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Books Purchase Decision <span class="text-danger">*</span></label>
                        <div class="row g-2 mt-1">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="books_status" id="status_school" value="SCHOOL" required>
                                <label class="btn btn-outline-primary w-100 py-3 rounded-3" for="status_school">
                                    <i class="bi bi-shop fs-4 d-block mb-1"></i>
                                    From School
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="books_status" id="status_outside" value="OUTSIDE" required>
                                <label class="btn btn-outline-secondary w-100 py-3 rounded-3" for="status_outside">
                                    <i class="bi bi-cart-x fs-4 d-block mb-1"></i>
                                    From Outside
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase">Type Student Name to Confirm <span class="text-danger">*</span></label>
                        <input type="text" name="confirm_student_name" class="form-control border-info" required placeholder="Type name exactly as shown above">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-link text-muted text-decoration-none px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info px-4 fw-bold rounded-pill shadow-sm text-white">
                        <i class="bi bi-check-lg me-2"></i> Create Fee Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openFinalizeModal(id, name, admNo) {
        $('#modal_student_name').text(name);
        $('#modal_admission_no').text(admNo);
        $('#finalizeForm').attr('action', '/books-decisions/admission/' + id);
        new bootstrap.Modal(document.getElementById('finalizeModal')).show();
    }
</script>
@endpush
@endsection
