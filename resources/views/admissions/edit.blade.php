@extends('fees.layout')

@section('title', 'Edit Admission - ' . $admission->student->student_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Edit Admission Details</h1>
        <a href="{{ route('admissions.show', $admission->admission_id) }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
            <i class="bi bi-arrow-left me-2"></i> Back to Details
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admissions.update', $admission->admission_id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="row g-4">
            <!-- Left Column: Personal Information -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i> Student Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <!-- Read Only Fields -->
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Admission Number</label>
                                <input type="text" value="{{ $admission->student->admission_no }}" class="form-control bg-light" readonly disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Academic Year / Class</label>
                                <input type="text" value="{{ $admission->academicYear->year_name }} - {{ $admission->classRoom->class_name }}" class="form-control bg-light" readonly disabled>
                            </div>

                            <hr class="my-4 opacity-10">

                            <!-- Editable Fields -->
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Student Name <span class="text-danger">*</span></label>
                                <input type="text" name="student_name" value="{{ old('student_name', $admission->student->student_name) }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" name="dob" value="{{ old('dob', $admission->student->dob->format('Y-m-d')) }}" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Father's Name <span class="text-danger">*</span></label>
                                <input type="text" name="father_name" value="{{ old('father_name', $admission->student->father_name) }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Mother's Name <span class="text-danger">*</span></label>
                                <input type="text" name="mother_name" value="{{ old('mother_name', $admission->student->mother_name) }}" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Guardian Name</label>
                                <input type="text" name="guardian_name" value="{{ old('guardian_name', $admission->student->guardian_name) }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Primary Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone_primary" value="{{ old('phone_primary', $admission->student->phone_primary) }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Secondary Phone</label>
                                <input type="text" name="phone_secondary" value="{{ old('phone_secondary', $admission->student->phone_secondary) }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                                <input type="email" name="email" value="{{ old('email', $admission->student->email) }}" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Current Address <span class="text-danger">*</span></label>
                                <textarea name="address" rows="3" class="form-control" required>{{ old('address', $admission->student->address) }}</textarea>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Remarks</label>
                                <input type="text" name="remarks" value="{{ old('remarks', $admission->remarks) }}" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Management Section -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-file-earmark-arrow-up me-2 text-primary"></i> Document Management</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            @php
                                $docTypes = ['TC', 'AADHAAR', 'BIRTH_CERTIFICATE', 'INCOME_CERTIFICATE', 'CASTE_CERTIFICATE', 'MARKS_MEMO', 'STUDY_CERTIFICATE', 'OTHER'];
                                $existingDocs = $admission->student->documents->pluck('file_path', 'document_type')->toArray();
                                $docIds = $admission->student->documents->pluck('document_id', 'document_type')->toArray();
                            @endphp

                            @foreach($docTypes as $type)
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 bg-light">
                                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">{{ str_replace('_', ' ', $type) }}</label>
                                        
                                        @if(isset($existingDocs[$type]))
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-check text-success fs-4 me-2"></i>
                                                    <span class="small text-muted">File Uploaded</span>
                                                </div>
                                                <div class="btn-group">
                                                    <a href="{{ asset('storage/' . $existingDocs[$type]) }}" target="_blank" class="btn btn-sm btn-white border" title="View">
                                                        <i class="bi bi-eye text-primary"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-white border text-danger" title="Delete" 
                                                            onclick="confirmDeleteDocument('{{ $docIds[$type] }}')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="small text-muted mb-2">Replace document:</div>
                                        @endif
                                        
                                        <input type="file" name="documents[{{ $type }}]" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Photo & Action -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom text-center">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-camera me-2 text-primary"></i> Student Photo</h5>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="mb-4">
                            @if($admission->student->photo_path)
                                <div id="photo-preview-container" class="position-relative d-inline-block">
                                    <img id="photo-preview" src="{{ asset('storage/' . $admission->student->photo_path) }}" class="rounded-circle border border-4 border-white shadow-sm" width="180" height="180" style="object-fit: cover;">
                                    <div class="position-absolute bottom-0 end-0">
                                        <span class="badge bg-success rounded-pill border border-2 border-white">CURRENT</span>
                                    </div>
                                </div>
                            @else
                                <div id="photo-preview" class="rounded-circle bg-light border border-4 border-white shadow-sm mx-auto d-flex align-items-center justify-content-center text-muted" style="width: 180px; height: 180px;">
                                    <i class="bi bi-person display-1"></i>
                                </div>
                            @endif
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase d-block mb-2">Replace / Upload Photo</label>
                            <input type="file" name="photo" id="photo-input" class="form-control" accept="image/png, image/jpeg, image/jpg">
                            <div class="small text-muted mt-2">Max: 2MB. JPG, PNG allowed.</div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 bg-primary text-white sticky-top" style="top: 20px;">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2"></i> Update Security</h6>
                        <p class="small opacity-75 mb-4">You are about to update sensitive student information. All changes are tracked in the audit logs.</p>
                        
                        <button type="submit" class="btn btn-white w-100 fw-bold text-primary py-3 rounded-3 shadow-sm mb-3">
                            <i class="bi bi-check-circle-fill me-2"></i> Save All Changes
                        </button>
                        
                        <a href="{{ route('admissions.show', $admission->admission_id) }}" class="btn btn-outline-white w-100 py-2 rounded-3 text-white border-white opacity-75">
                            Cancel Changes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Hidden Delete Form -->
    <form id="delete-document-form" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Photo preview logic
    document.getElementById('photo-input').addEventListener('change', function(e) {
        const preview = document.getElementById('photo-preview');
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    preview.innerHTML = `<img src="${e.target.result}" class="rounded-circle border border-4 border-white shadow-sm" width="180" height="180" style="object-fit: cover;">`;
                    preview.classList.remove('bg-light', 'text-muted', 'd-flex');
                }
            }
            reader.readAsDataURL(file);
        }
    });

    // Document delete confirmation
    function confirmDeleteDocument(docId) {
        Swal.fire({
            title: 'Delete Document?',
            text: "This action cannot be undone and will be logged.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('delete-document-form');
                form.action = `/admissions/documents/${docId}`;
                form.submit();
            }
        });
    }
</script>
@endpush
@endsection
