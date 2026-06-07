@extends('fees.layout')

@section('title', 'Admission Details - ' . $admission->student->student_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">Admissions</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $admission->student->admission_no }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 fw-bold text-dark">Admission Details</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admissions.edit', $admission->admission_id) }}" class="btn btn-outline-warning btn-sm px-3 rounded-pill shadow-sm">
                <i class="bi bi-pencil me-2"></i> Edit Student
            </a>
            @if(in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN']))
            <form action="{{ route('admissions.destroy', $admission->admission_id) }}" method="POST" class="d-inline" onsubmit="return confirm('CRITICAL WARNING: This will permanently delete the student, all enrollments, documents, and fee accounts. This action cannot be undone and will be audited. Proceed?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm px-3 rounded-pill shadow-sm">
                    <i class="bi bi-trash me-2"></i> Delete
                </button>
            </form>
            @endif
            <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm">
                <i class="bi bi-arrow-left me-2"></i> Back to List
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left Column: Student Profile & Documents -->
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden text-center">
                <div class="bg-primary py-5"></div>
                <div class="card-body p-4" style="margin-top: -60px;">
                    <div class="mb-3">
                        @if($admission->student->photo_path)
                            <img src="{{ asset('storage/' . $admission->student->photo_path) }}" class="rounded-circle border border-4 border-white shadow-sm mx-auto" width="120" height="120" style="object-fit: cover;">
                        @else
                            <div class="rounded-circle border border-4 border-white shadow-sm bg-light mx-auto d-flex align-items-center justify-content-center text-muted" style="width: 120px; height: 120px;">
                                <i class="bi bi-person display-4"></i>
                            </div>
                        @endif
                    </div>
                    <h4 class="fw-bold mb-1">{{ $admission->student->student_name }}</h4>
                    <p class="text-primary fw-semibold mb-3">{{ $admission->student->admission_no }}</p>
                    
                    @php
                        $statusBadgeClass = match($admission->admission_status) {
                            'ADMITTED'          => 'bg-success',
                            'APPROVED'          => 'bg-info',
                            'DOCUMENT VERIFIED' => 'bg-primary',
                            'SUBMITTED'         => 'bg-warning text-dark',
                            'DRAFT'             => 'bg-secondary',
                            'REJECTED'          => 'bg-danger',
                            default             => 'bg-secondary'
                        };
                    @endphp
                    <div class="badge rounded-pill {{ $statusBadgeClass }} px-3 mb-4">{{ $admission->admission_status }}</div>

                    <!-- Workflow Action Buttons -->
                    @if(in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN', 'PRINCIPAL', 'CORRESPONDENT']))
                        @if(in_array($admission->admission_status, ['DRAFT', 'SUBMITTED']))
                            <div class="d-grid mb-2">
                                <form action="{{ route('admissions.verify', $admission->admission_id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100 rounded-pill shadow-sm">
                                        <i class="bi bi-shield-check me-2"></i> Verify Documents
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if(in_array($admission->admission_status, ['DOCUMENT VERIFIED', 'SUBMITTED']))
                            <div class="d-grid mb-2">
                                <form action="{{ route('admissions.approve', $admission->admission_id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100 rounded-pill shadow-sm">
                                        <i class="bi bi-check-circle me-2"></i> Approve Admission
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if($admission->admission_status === 'APPROVED')
                            <div class="d-grid mb-2">
                                <form action="{{ route('admissions.admit', $admission->admission_id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-info text-white w-100 rounded-pill shadow-sm">
                                        <i class="bi bi-person-check me-2"></i> Admit Student
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if(!in_array($admission->admission_status, ['ADMITTED', 'REJECTED']))
                            <div class="d-grid mb-4">
                                <button class="btn btn-outline-danger w-100 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="bi bi-x-circle me-2"></i> Reject Admission
                                </button>
                            </div>
                        @endif
                    @endif
                    
                    <div class="row g-0 border-top pt-3 mt-2">
                        <div class="col-6 border-end">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Class</div>
                            <div class="fw-bold text-dark">{{ $admission->classRoom->class_name }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Section</div>
                            <div class="fw-bold text-dark">{{ $admission->section->section_name }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-file-earmark-text me-2 text-primary"></i> Document Management</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @php
                            $requiredDocs = [
                                'PHOTO' => 'Student Photo',
                                'BIRTH_CERTIFICATE' => 'Birth Certificate',
                                'TC' => 'Transfer Certificate',
                                'STUDENT_AADHAAR' => 'Student Aadhaar',
                                'PARENT_AADHAAR' => 'Parent Aadhaar'
                            ];
                            $uploadedDocs = $admission->student->documents->keyBy('document_type');
                            $userRole = strtoupper(optional(auth()->user()->role)->role_name ?? '');
                            $isManagement = in_array($userRole, ['ADMINISTRATOR', 'ADMIN', 'PRINCIPAL', 'CORRESPONDENT']);
                            $isClerkOrManagement = in_array($userRole, ['CLERK', 'ADMINISTRATOR', 'ADMIN', 'PRINCIPAL', 'CORRESPONDENT']);
                        @endphp

                        @foreach($requiredDocs as $type => $label)
                            @php
                                $doc = $uploadedDocs->get($type);
                                $isMandatory = in_array($type, \App\Models\Student::MANDATORY_DOCS);
                                $statusBadge = '';
                                $statusIcon = '';
                                if ($doc) {
                                    if ($doc->verification_status === 'VERIFIED') {
                                        $statusBadge = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                                        $statusIcon = 'bi-check-circle-fill';
                                        $statusText = 'Verified';
                                    } elseif ($doc->verification_status === 'REJECTED') {
                                        $statusBadge = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                                        $statusIcon = 'bi-x-circle-fill';
                                        $statusText = 'Rejected';
                                    } else {
                                        $statusBadge = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                                        $statusIcon = 'bi-clock-fill';
                                        $statusText = 'Uploaded';
                                    }
                                } else {
                                    $statusBadge = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                                    $statusIcon = 'bi-x-circle-fill';
                                    $statusText = 'Missing';
                                }
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-semibold text-dark">
                                        {{ $label }}
                                        <span class="small fw-normal text-muted">({{ $isMandatory ? 'Mandatory' : 'Optional' }})</span>
                                    </div>
                                    <span class="badge {{ $statusBadge }} rounded-pill small mt-1">
                                        <i class="bi {{ $statusIcon }} me-1"></i> {{ $statusText }}
                                    </span>
                                    @if($doc && $doc->remarks)
                                        <div class="small text-muted mt-1">{{ $doc->remarks }}</div>
                                    @endif
                                </div>
                                <div class="btn-group">
                                    @if($doc)
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-light btn-sm rounded-circle border shadow-sm" title="View">
                                            <i class="bi bi-eye text-primary"></i>
                                        </a>
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" download class="btn btn-light btn-sm rounded-circle border ms-1 shadow-sm" title="Download">
                                            <i class="bi bi-download text-success"></i>
                                        </a>
                                    @endif

                                    @if($isClerkOrManagement)
                                        <button class="btn btn-light btn-sm rounded-circle border ms-1 shadow-sm"
                                                onclick="openUploadModal('{{ $type }}', '{{ $label }}')" title="Upload/Replace">
                                            <i class="bi bi-cloud-upload text-info"></i>
                                        </button>
                                    @endif

                                    @if($isManagement && $doc && $doc->verification_status !== 'VERIFIED')
                                        <button class="btn btn-light btn-sm rounded-circle border ms-1 shadow-sm"
                                                onclick="verifyDocument('{{ $doc->document_id }}')" title="Verify Document">
                                            <i class="bi bi-shield-check text-success"></i>
                                        </button>
                                        <button class="btn btn-light btn-sm rounded-circle border ms-1 shadow-sm"
                                                onclick="openRejectDocModal('{{ $doc->document_id }}')" title="Reject Document">
                                            <i class="bi bi-x-circle text-danger"></i>
                                        </button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Column: Details & Finance -->
        <div class="col-lg-8">
            <!-- Student Details -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-info-circle me-2 text-primary"></i> Personal Information</h5>
                    @if($admission->transferred_from_admission_id)
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill small">
                            <i class="bi bi-arrow-left-right me-1"></i> Transferred From: #{{ $admission->transferredFrom->student->admission_no ?? 'N/A' }}
                        </span>
                    @endif
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Date of Birth</div>
                            <div class="fw-semibold">{{ \Carbon\Carbon::parse($admission->student->dob)->format('d M Y') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Gender</div>
                            <div class="fw-semibold">{{ $admission->student->gender }}</div>
                        </div>
                        
                        <!-- Masked Aadhaar -->
                        <div class="col-md-4">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Aadhaar No</div>
                            <div class="d-flex align-items-center">
                                <span class="fw-semibold me-2" id="aadhaar-value">{{ $admission->student->masked_aadhaar }}</span>
                                @if($admission->student->canViewSensitiveData())
                                    <button class="btn btn-sm btn-link p-0 text-decoration-none" onclick="revealValue('aadhaar', '{{ $admission->student->aadhaar_no }}')">
                                        <i class="bi bi-eye small"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Masked PEN -->
                        <div class="col-md-4">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">PEN No</div>
                            <div class="d-flex align-items-center">
                                <span class="fw-semibold me-2" id="pen-value">{{ $admission->student->masked_pen }}</span>
                                @if($admission->student->canViewSensitiveData())
                                    <button class="btn btn-sm btn-link p-0 text-decoration-none" onclick="revealValue('pen', '{{ $admission->student->pen_no }}')">
                                        <i class="bi bi-eye small"></i>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Father's Name</div>
                            <div class="fw-semibold">{{ $admission->student->father_name }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Mother's Name</div>
                            <div class="fw-semibold">{{ $admission->student->mother_name }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Guardian Name</div>
                            <div class="fw-semibold">{{ $admission->student->guardian_name ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Primary Phone</div>
                            <div class="fw-semibold text-primary"><i class="bi bi-telephone me-1"></i> {{ $admission->student->phone_primary }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Secondary Phone</div>
                            <div class="fw-semibold">{{ $admission->student->phone_secondary ?? '-' }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Email</div>
                            <div class="fw-semibold">{{ $admission->student->email ?? '-' }}</div>
                        </div>
                        <div class="col-md-12">
                            <div class="small text-muted text-uppercase mb-1" style="font-size: 0.7rem;">Address</div>
                            <div class="fw-semibold">{{ $admission->student->address }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Timeline -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> Admission Timeline</h6>
                </div>
                <div class="card-body p-4 bg-light bg-opacity-50">
                    <div class="timeline-steps d-flex justify-content-between">
                        <!-- Created -->
                        <div class="timeline-step text-center flex-fill">
                            <div class="step-icon mx-auto rounded-circle bg-white border border-2 border-primary d-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                                <i class="bi bi-plus-lg text-primary"></i>
                            </div>
                            <div class="small fw-bold">Draft</div>
                            <div class="x-small text-muted">{{ $admission->created_at->format('d/m/y') }}</div>
                        </div>

                        <!-- Submitted -->
                        <div class="timeline-step text-center flex-fill">
                            <div class="step-icon mx-auto rounded-circle bg-white border border-2 {{ $admission->admission_status !== 'DRAFT' ? 'border-primary' : 'text-muted' }} d-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                                <i class="bi bi-file-earmark-arrow-up {{ $admission->admission_status !== 'DRAFT' ? 'text-primary' : '' }}"></i>
                            </div>
                            <div class="small fw-bold {{ $admission->admission_status === 'DRAFT' ? 'text-muted' : '' }}">Submitted</div>
                            <div class="x-small text-muted">{{ $admission->created_at->format('d/m/y') }}</div>
                        </div>

                        <!-- Verified -->
                        <div class="timeline-step text-center flex-fill">
                            <div class="step-icon mx-auto rounded-circle bg-white border border-2 {{ $admission->verified_at ? 'border-primary' : 'text-muted' }} d-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                                <i class="bi bi-shield-check {{ $admission->verified_at ? 'text-primary' : '' }}"></i>
                            </div>
                            <div class="small fw-bold {{ !$admission->verified_at ? 'text-muted' : '' }}">Verified</div>
                            <div class="x-small text-muted">{{ $admission->verified_at ? $admission->verified_at->format('d/m/y') : '-' }}</div>
                        </div>

                        <!-- Approved -->
                        <div class="timeline-step text-center flex-fill">
                            <div class="step-icon mx-auto rounded-circle bg-white border border-2 {{ $admission->approved_at ? 'border-primary' : 'text-muted' }} d-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                                <i class="bi bi-check-lg {{ $admission->approved_at ? 'text-primary' : '' }}"></i>
                            </div>
                            <div class="small fw-bold {{ !$admission->approved_at ? 'text-muted' : '' }}">Approved</div>
                            <div class="x-small text-muted">{{ $admission->approved_at ? $admission->approved_at->format('d/m/y') : '-' }}</div>
                        </div>

                        <!-- Admitted -->
                        <div class="timeline-step text-center flex-fill">
                            <div class="step-icon mx-auto rounded-circle bg-white border border-2 {{ $admission->admitted_at ? 'border-primary' : 'text-muted' }} d-flex align-items-center justify-content-center mb-2" style="width: 40px; height: 40px;">
                                <i class="bi bi-person-check {{ $admission->admitted_at ? 'text-primary' : '' }}"></i>
                            </div>
                            <div class="small fw-bold {{ !$admission->admitted_at ? 'text-muted' : '' }}">Admitted</div>
                            <div class="x-small text-muted">{{ $admission->admitted_at ? $admission->admitted_at->format('d/m/y') : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Finance Summary -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-wallet2 me-2 text-primary"></i> Fee Account Summary</h5>
                    @if($feeAccount)
                        <a href="{{ route('fees.ledger', $feeAccount->account_id) }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                            Go to Ledger <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    @endif
                </div>
                <div class="card-body p-4">
                    @if($feeAccount)
                        <div class="row g-4">
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded-3 text-center">
                                    <div class="small text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Net Tuition Fee</div>
                                    <div class="h5 fw-bold mb-0">₹{{ number_format($feeAccount->final_tuition_fee, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded-3 text-center">
                                    <div class="small text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Total Paid</div>
                                    <div class="h5 fw-bold mb-0 text-success">₹{{ number_format($feeAccount->total_paid, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded-3 text-center border-start border-warning border-4">
                                    <div class="small text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Remaining Due</div>
                                    <div class="h5 fw-bold mb-0 text-warning">₹{{ number_format($feeAccount->remaining_balance, 2) }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded-3 text-center">
                                    <div class="small text-muted text-uppercase mb-1" style="font-size: 0.65rem;">Books Status</div>
                                    <div class="h6 fw-bold mb-0">
                                        @if($feeAccount->books_from_school)
                                            <span class="text-info">SCHOOL</span>
                                        @else
                                            <span class="text-secondary">OUTSIDE</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-exclamation-triangle text-warning fs-1 d-block mb-2"></i>
                            <p class="text-muted mb-0">Fee account not found for this enrollment.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Audit Info -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light border">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-white p-2 rounded-3 me-3 border">
                                    <i class="bi bi-person-check text-success"></i>
                                </div>
                                <div>
                                    <div class="small text-muted text-uppercase" style="font-size: 0.65rem;">Approved By</div>
                                    <div class="fw-bold">{{ $admission->approver->full_name ?? $admission->approver->username ?? 'System' }}</div>
                                    <div class="small text-muted">{{ $admission->approved_at ? \Carbon\Carbon::parse($admission->approved_at)->format('d M Y, h:i A') : '-' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-white p-2 rounded-3 me-3 border">
                                    <i class="bi bi-calendar-event text-info"></i>
                                </div>
                                <div>
                                    <div class="small text-muted text-uppercase" style="font-size: 0.65rem;">Admission Date</div>
                                    <div class="fw-bold">{{ optional($admission->student->admission_date ?? $admission->created_at)->format('d M Y') ?? '-' }}</div>
                                    <div class="small text-muted">Created: {{ $admission->created_at ? $admission->created_at->format('d M Y, h:i A') : '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function revealValue(type, fullValue) {
        // Use API endpoint for audit logging
        fetch(`{{ route('admissions.reveal-sensitive', $admission->admission_id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ field: type })
        })
        .then(response => response.json())
        .then(data => {
            if (data.value) {
                Swal.fire({
                    title: `${type.toUpperCase()} Number`,
                    text: data.value,
                    icon: 'success',
                    confirmButtonColor: '#0d6efd'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'Error',
                text: 'Failed to reveal sensitive data',
                icon: 'error'
            });
        });
    }

    function verifyDocument(documentId) {
        Swal.fire({
            title: 'Verify Document',
            text: 'Are you sure you want to mark this document as verified?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, verify it'
        }).then((result) => {
            if (result.isConfirmed) {
                // Call API to verify document
                fetch(`/admissions/documents/${documentId}/verify`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Verified!',
                            text: 'Document has been verified successfully',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to verify document',
                        icon: 'error'
                    });
                });
            }
        });
    }
</script>
@endpush

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-cloud-upload me-2"></i> Upload Document</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admissions.documents.store', $admission->admission_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="document_type" id="modal_doc_type">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Document Type</label>
                        <input type="text" id="modal_doc_label" class="form-control bg-light border-0" readonly>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase">Select File <span class="text-danger">*</span></label>
                        <input type="file" name="document" class="form-control border-primary" required accept=".jpg,.jpeg,.png,.pdf">
                        <div class="form-text small">Accepted formats: JPG, PNG, PDF (Max 2MB)</div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-link text-muted text-decoration-none px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-check-lg me-2"></i> Start Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Document Modal -->
<div class="modal fade" id="rejectDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white py-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i> Reject Document</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectDocForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="4" required placeholder="Describe why this document is rejected (e.g. blurry scan, incorrect information)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-link text-muted text-decoration-none px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-x-lg me-2"></i> Reject Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Admission Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white py-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i> Reject Admission</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admissions.reject', $admission->admission_id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-warning rounded-3 mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This action will reject the admission. The student will not be enrolled.
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-link text-muted text-decoration-none px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-x-lg me-2"></i> Reject Admission
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openUploadModal(type, label) {
        document.getElementById('modal_doc_type').value = type;
        document.getElementById('modal_doc_label').value = label;
        new bootstrap.Modal(document.getElementById('uploadModal')).show();
    }

    function openRejectDocModal(documentId) {
        const form = document.getElementById('rejectDocForm');
        form.action = `/admissions/documents/${documentId}/reject`;
        new bootstrap.Modal(document.getElementById('rejectDocModal')).show();
    }
</script>
@endsection
