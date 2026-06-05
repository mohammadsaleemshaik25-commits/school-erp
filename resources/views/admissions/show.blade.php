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
                    <div class="badge rounded-pill bg-success px-3 mb-4">ACTIVE STUDENT</div>
                    
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
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-file-earmark-text me-2 text-primary"></i> Documents</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($admission->student->documents as $doc)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-semibold text-dark">{{ str_replace('_', ' ', $doc->document_type) }}</div>
                                    <div class="small text-muted">{{ $doc->uploaded_at->format('d M Y') }}</div>
                                </div>
                                <div class="btn-group">
                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-light btn-sm rounded-circle border">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ asset('storage/' . $doc->file_path) }}" download class="btn btn-light btn-sm rounded-circle border ms-1">
                                        <i class="bi bi-download"></i>
                                    </a>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-4 text-muted">
                                No documents uploaded.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Column: Details & Finance -->
        <div class="col-lg-8">
            <!-- Student Details -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-info-circle me-2 text-primary"></i> Personal Information</h5>
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
                                    <div class="fw-bold">{{ $admission->admission_date ? \Carbon\Carbon::parse($admission->admission_date)->format('d M Y') : '-' }}</div>
                                    <div class="small text-muted">Created: {{ $admission->created_at ? \Carbon\Carbon::parse($admission->created_at)->format('d M Y, h:i A') : '-' }}</div>
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
        Swal.fire({
            title: 'Confirm Identity',
            text: `Are you sure you want to view the full ${type.toUpperCase()} number? This action will be audited.`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, reveal it'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`${type}-value`).innerText = fullValue;
                // Optional: Automatically re-mask after some time or on click
            }
        });
    }
</script>
@endpush
@endsection
