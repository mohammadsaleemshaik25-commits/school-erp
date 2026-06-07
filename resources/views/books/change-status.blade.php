@extends('fees.layout')

@section('title', 'Change Books Status')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 fw-bold text-dark">Change Books Status</h1>
                <a href="{{ route('books.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                    <i class="bi bi-arrow-left me-2"></i> Back to List
                </a>
            </div>

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-info-circle me-2 text-primary"></i> Student Information</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Student Name</label>
                            <div class="h6 fw-bold mb-0" id="target-student-name">{{ $account->enrollment->student->student_name }}</div>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Admission No</label>
                            <div class="h6 fw-bold mb-0 text-primary">{{ $account->enrollment->student->admission_no }}</div>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Class & Section</label>
                            <div class="h6 fw-bold mb-0">{{ $account->enrollment->classRoom->class_name }} - {{ $account->enrollment->section->section_name }}</div>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Academic Year</label>
                            <div class="h6 fw-bold mb-0">{{ $account->enrollment->academicYear->year_name }}</div>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Books Fee</label>
                            <div class="h6 fw-bold mb-0">₹{{ number_format($account->books_fee, 2) }}</div>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Current Status</label>
                            @php
                                $badgeClass = match($account->books_status) {
                                    'PENDING' => 'bg-warning',
                                    'SCHOOL' => 'bg-primary',
                                    'OUTSIDE' => 'bg-secondary',
                                    'BOOKS_PAID' => 'bg-success',
                                    default => 'bg-light text-dark'
                                };
                            @endphp
                            <span class="badge rounded-pill {{ $badgeClass }} px-3">{{ $account->books_status }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i> Make Decision</h5>
                </div>
                <div class="card-body p-4">
                    <form id="decisionForm" action="{{ route('books.update', $account->account_id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-2">Select New Status <span class="text-danger">*</span></label>
                            <div class="row g-3">
                                @if($account->books_status === 'PENDING' || in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN'], true))
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="books_status" id="status_school" value="SCHOOL" {{ old('books_status') === 'SCHOOL' ? 'checked' : '' }} required>
                                        <label class="btn btn-outline-primary w-100 py-3 rounded-4 d-flex flex-column align-items-center" for="status_school">
                                            <i class="bi bi-shop fs-2 mb-2"></i>
                                            <span class="fw-bold">SCHOOL</span>
                                            <span class="small opacity-75">Books from school</span>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="books_status" id="status_outside" value="OUTSIDE" {{ old('books_status') === 'OUTSIDE' ? 'checked' : '' }} required>
                                        <label class="btn btn-outline-secondary w-100 py-3 rounded-4 d-flex flex-column align-items-center" for="status_outside">
                                            <i class="bi bi-bag-x fs-2 mb-2"></i>
                                            <span class="fw-bold">OUTSIDE</span>
                                            <span class="small opacity-75">Books from outside</span>
                                        </label>
                                    </div>
                                @endif
                                @if(in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN'], true))
                                    <div class="col-md-12 mt-3">
                                        <input type="radio" class="btn-check" name="books_status" id="status_pending" value="PENDING" {{ old('books_status') === 'PENDING' ? 'checked' : '' }} required>
                                        <label class="btn btn-outline-warning w-100 py-3 rounded-4 d-flex flex-column align-items-center" for="status_pending">
                                            <i class="bi bi-clock-history fs-2 mb-2"></i>
                                            <span class="fw-bold">RESET TO PENDING</span>
                                            <span class="small opacity-75">Admin Override</span>
                                        </label>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-2">Security Confirmation <span class="text-danger">*</span></label>
                            <div class="p-3 bg-light rounded-3 border">
                                <p class="small text-muted mb-2">Please type the student's full name to confirm this decision:</p>
                                <div class="fw-bold text-dark mb-2 h5">{{ $account->enrollment->student->student_name }}</div>
                                <input type="text" name="confirm_student_name" id="confirm_student_name" class="form-control form-control-lg border-primary shadow-none" placeholder="Type name here..." autocomplete="off" required>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="submitBtn" class="btn btn-primary py-3 fw-bold rounded-pill shadow-sm">
                                <i class="bi bi-check-circle me-2"></i> Confirm and Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alert alert-info mt-4 rounded-4 border-0 shadow-sm p-3 d-flex align-items-center">
                <i class="bi bi-info-circle-fill fs-4 me-3 text-info"></i>
                <div class="small">
                    <strong>Note:</strong> Status changes for Books are audited. Changes to 'OUTSIDE' will remove the books fee from the ledger. Changes to 'SCHOOL' will make the books fee collectible.
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('decisionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const confirmName = document.getElementById('confirm_student_name').value;
        const targetName = document.getElementById('target-student-name').innerText;
        const selectedStatus = document.querySelector('input[name="books_status"]:checked').value;

        if (confirmName.trim().toUpperCase() !== targetName.trim().toUpperCase()) {
            Swal.fire({
                title: 'Name Mismatch',
                text: 'The entered name does not match the student name. Please type it exactly as shown.',
                icon: 'error',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        Swal.fire({
            title: 'Confirm Decision',
            text: `Are you sure you want to change the books status to ${selectedStatus}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Update Status'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
</script>
@endpush
@endsection
