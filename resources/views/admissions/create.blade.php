@extends('fees.layout')

@section('title', 'New Admission')

@section('content')
<div class="container-fluid pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 fw-bold text-dark">New Admission Form</h1>
            <div class="d-flex align-items-center gap-2 mt-1">
                <span id="admission-status-badge" class="badge bg-secondary rounded-pill px-3 small">DRAFT</span>
                <span id="admission-no-display" class="text-muted small">Admission No: --</span>
            </div>
        </div>
        <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
            <i class="bi bi-arrow-left me-2"></i> Back to List
        </a>
    </div>

    <!-- Form Completion Progress Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-muted">Form Completion</span>
                <span id="completion-percentage" class="small fw-bold text-primary">0%</span>
            </div>
            <div class="progress" style="height: 8px;">
                <div id="completion-bar" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-2" id="completion-items">
                <span class="badge bg-light text-muted small" data-section="student-info">Student Information</span>
                <span class="badge bg-light text-muted small" data-section="parent-info">Parent Information</span>
                <span class="badge bg-light text-muted small" data-section="address-info">Address Information</span>
                <span class="badge bg-light text-muted small" data-section="academic-info">Academic Information</span>
                <span class="badge bg-light text-muted small" data-section="photo">Photo Uploaded</span>
                <span class="badge bg-light text-muted small" data-section="documents">Documents <span id="doc-count">0/5</span></span>
            </div>
        </div>
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admissions.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="row g-4">
            <!-- Student Information -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-person-circle me-2 text-primary"></i> Student Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="student_name" value="{{ old('student_name') }}" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" name="dob" value="{{ old('dob') }}" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select" required>
                                    <option value="">Select</option>
                                    <option value="MALE" {{ old('gender') === 'MALE' ? 'selected' : '' }}>Male</option>
                                    <option value="FEMALE" {{ old('gender') === 'FEMALE' ? 'selected' : '' }}>Female</option>
                                    <option value="OTHER" {{ old('gender') === 'OTHER' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Nationality</label>
                                <input type="text" name="nationality" value="{{ old('nationality', 'Indian') }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Blood Group</label>
                                <select name="blood_group" class="form-select">
                                    <option value="">Select</option>
                                    <option value="A+" {{ old('blood_group') === 'A+' ? 'selected' : '' }}>A+</option>
                                    <option value="A-" {{ old('blood_group') === 'A-' ? 'selected' : '' }}>A-</option>
                                    <option value="B+" {{ old('blood_group') === 'B+' ? 'selected' : '' }}>B+</option>
                                    <option value="B-" {{ old('blood_group') === 'B-' ? 'selected' : '' }}>B-</option>
                                    <option value="AB+" {{ old('blood_group') === 'AB+' ? 'selected' : '' }}>AB+</option>
                                    <option value="AB-" {{ old('blood_group') === 'AB-' ? 'selected' : '' }}>AB-</option>
                                    <option value="O+" {{ old('blood_group') === 'O+' ? 'selected' : '' }}>O+</option>
                                    <option value="O-" {{ old('blood_group') === 'O-' ? 'selected' : '' }}>O-</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Religion</label>
                                <input type="text" name="religion" value="{{ old('religion') }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">Select</option>
                                    <option value="General" {{ old('category') === 'General' ? 'selected' : '' }}>General</option>
                                    <option value="OBC" {{ old('category') === 'OBC' ? 'selected' : '' }}>OBC</option>
                                    <option value="SC" {{ old('category') === 'SC' ? 'selected' : '' }}>SC</option>
                                    <option value="ST" {{ old('category') === 'ST' ? 'selected' : '' }}>ST</option>
                                    <option value="EWS" {{ old('category') === 'EWS' ? 'selected' : '' }}>EWS</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Aadhaar Number <span class="text-danger">*</span></label>
                                <input type="text" name="aadhaar_no" value="{{ old('aadhaar_no') }}" class="form-control" required placeholder="12-digit number">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Parent Information -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-people me-2 text-primary"></i> Parent Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Father's Name <span class="text-danger">*</span></label>
                                <input type="text" name="father_name" value="{{ old('father_name') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Mother's Name <span class="text-danger">*</span></label>
                                <input type="text" name="mother_name" value="{{ old('mother_name') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Guardian's Name</label>
                                <input type="text" name="guardian_name" value="{{ old('guardian_name') }}" class="form-control" placeholder="If different from parents">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Occupation</label>
                                <input type="text" name="occupation" value="{{ old('occupation') }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Annual Income</label>
                                <input type="number" name="annual_income" value="{{ old('annual_income') }}" class="form-control" placeholder="Annual income in INR">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">PEN Number <span class="text-danger">*</span></label>
                                <input type="text" name="pen_no" value="{{ old('pen_no') }}" class="form-control" required placeholder="Unique PEN No">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Primary Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone_primary" value="{{ old('phone_primary') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Secondary Phone</label>
                                <input type="text" name="phone_secondary" value="{{ old('phone_secondary') }}" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="example@mail.com">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-geo-alt me-2 text-primary"></i> Address Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Current Address <span class="text-danger">*</span></label>
                                <textarea name="address" rows="3" class="form-control" required>{{ old('address') }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Permanent Address</label>
                                <textarea name="permanent_address" rows="3" class="form-control">{{ old('permanent_address') }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Village</label>
                                <input type="text" name="village" value="{{ old('village') }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">District</label>
                                <input type="text" name="district" value="{{ old('district') }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">State</label>
                                <input type="text" name="state" value="{{ old('state') }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">PIN Code</label>
                                <input type="text" name="pin_code" value="{{ old('pin_code') }}" class="form-control" placeholder="6-digit PIN">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Academic Details -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-mortarboard me-2 text-primary"></i> Academic Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Academic Year <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-select" required>
                                    <option value="">Select Year</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->academic_year_id }}" {{ old('academic_year_id') == $year->academic_year_id ? 'selected' : '' }}>
                                            {{ $year->year_name }} {{ $year->is_active ? '(Active)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Class <span class="text-danger">*</span></label>
                                <select name="class_id" id="class_id" class="form-select" required>
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->class_id }}" {{ old('class_id') == $class->class_id ? 'selected' : '' }}>
                                            {{ $class->class_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Section <span class="text-danger">*</span></label>
                                <select name="section_id" id="section_id" class="form-select" required>
                                    <option value="">Select Section</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->section_id }}" data-class="{{ $section->class_id }}" {{ old('section_id') == $section->section_id ? 'selected' : '' }}>
                                            {{ $section->section_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Admission Date <span class="text-danger">*</span></label>
                                <input type="date" name="admission_date" value="{{ old('admission_date', date('Y-m-d')) }}" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Previous School</label>
                                <input type="text" name="previous_school" value="{{ old('previous_school') }}" class="form-control" placeholder="Last attended school">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Previous Class</label>
                                <input type="text" name="previous_class" value="{{ old('previous_class') }}" class="form-control" placeholder="Class in previous school">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Remarks</label>
                                <input type="text" name="remarks" value="{{ old('remarks') }}" class="form-control" placeholder="Any additional notes...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Uploads & Action -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-camera me-2 text-primary"></i> Photo</h5>
                    </div>
                    <div class="card-body p-4 text-center">
                        <div class="mb-3">
                            <div id="photo-preview" class="rounded-circle bg-light border mx-auto d-flex align-items-center justify-content-center" style="width: 150px; height: 150px; overflow: hidden;">
                                <i class="bi bi-person text-muted display-4"></i>
                            </div>
                        </div>
                        <input type="file" name="photo" id="photo-input" class="form-control" accept="image/png, image/jpeg, image/jpg">
                        <div class="small text-muted mt-2">JPG, JPEG or PNG. Max 2MB.</div>
                        <button type="button" id="crop-btn" class="btn btn-primary btn-sm rounded-pill mt-2 d-none" data-bs-toggle="modal" data-bs-target="#cropModal">
                            <i class="bi bi-crop me-1"></i> Crop Photo
                        </button>
                        <input type="hidden" name="cropped_photo_data" id="cropped-photo-data">
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-file-earmark-arrow-up me-2 text-primary"></i> Documents</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Birth Certificate</label>
                            <input type="file" name="documents[BIRTH_CERTIFICATE]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Transfer Certificate (TC)</label>
                            <input type="file" name="documents[TC]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Student Aadhaar</label>
                            <input type="file" name="documents[STUDENT_AADHAAR]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Parent Aadhaar</label>
                            <input type="file" name="documents[PARENT_AADHAAR]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold text-muted text-uppercase">Other Document</label>
                            <input type="file" name="documents[OTHER]" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="small text-muted mt-3">
                            <i class="bi bi-info-circle me-1"></i> Accepted formats: PDF, JPG, PNG (Max 2MB each)
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-file-earmark-text me-2 text-primary"></i> Document Status</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex justify-content-between align-items-center" data-doc-type="PHOTO">
                                <span class="small fw-bold">Photo</span>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill small" id="doc-status-PHOTO">
                                    <i class="bi bi-dash-circle me-1"></i> Not Uploaded
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" data-doc-type="BIRTH_CERTIFICATE">
                                <span class="small fw-bold">Birth Certificate</span>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill small" id="doc-status-BIRTH_CERTIFICATE">
                                    <i class="bi bi-dash-circle me-1"></i> Not Uploaded
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" data-doc-type="TC">
                                <span class="small fw-bold">Transfer Certificate</span>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill small" id="doc-status-TC">
                                    <i class="bi bi-dash-circle me-1"></i> Not Uploaded
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" data-doc-type="STUDENT_AADHAAR">
                                <span class="small fw-bold">Student Aadhaar</span>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill small" id="doc-status-STUDENT_AADHAAR">
                                    <i class="bi bi-dash-circle me-1"></i> Not Uploaded
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" data-doc-type="PARENT_AADHAAR">
                                <span class="small fw-bold">Parent Aadhaar</span>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill small" id="doc-status-PARENT_AADHAAR">
                                    <i class="bi bi-dash-circle me-1"></i> Not Uploaded
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Sticky Action Bar -->
    <div class="fixed-bottom bg-white border-top shadow-lg" style="z-index: 1000;">
        <div class="container-fluid py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div id="auto-save-status" class="small text-muted">
                        <i class="bi bi-cloud me-1"></i> Auto-save enabled
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="reset-btn" class="btn btn-outline-secondary px-4 rounded-pill">
                        <i class="bi bi-arrow-counterclockwise me-2"></i> Reset
                    </button>
                    <button type="button" id="save-draft-btn" class="btn btn-primary px-4 rounded-pill">
                        <i class="bi bi-save me-2"></i> Save Draft
                    </button>
                    <button type="submit" id="submit-btn" class="btn btn-success px-4 rounded-pill">
                        <i class="bi bi-check-circle me-2"></i> Submit Admission
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let cropper;
    let originalImage;
    let hasUnsavedChanges = false;
    let autoSaveInterval;
    let admissionId = null;

    // Form completion tracking
    function updateCompletion() {
        const sections = {
            'student-info': ['student_name', 'dob', 'gender'],
            'parent-info': ['father_name', 'mother_name', 'phone_primary'],
            'address-info': ['address'],
            'academic-info': ['academic_year_id', 'class_id', 'section_id'],
            'photo': ['photo-input'],
            'documents': ['documents[PHOTO]', 'documents[BIRTH_CERTIFICATE]', 'documents[TC]', 'documents[STUDENT_AADHAAR]', 'documents[PARENT_AADHAAR]']
        };

        let completed = 0;
        let total = Object.keys(sections).length;

        Object.keys(sections).forEach(section => {
            const fields = sections[section];
            let sectionComplete = false;

            if (section === 'documents') {
                const docCount = document.querySelectorAll('input[type="file"][name^="documents"]').length;
                if (docCount > 0) sectionComplete = true;
            } else {
                fields.forEach(field => {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (input && input.value) sectionComplete = true;
                });
            }

            if (sectionComplete) {
                completed++;
                document.querySelector(`[data-section="${section}"]`).classList.remove('bg-light', 'text-muted');
                document.querySelector(`[data-section="${section}"]`).classList.add('bg-success', 'text-white');
            } else {
                document.querySelector(`[data-section="${section}"]`).classList.remove('bg-success', 'text-white');
                document.querySelector(`[data-section="${section}"]`).classList.add('bg-light', 'text-muted');
            }
        });

        const percentage = Math.round((completed / total) * 100);
        document.getElementById('completion-bar').style.width = percentage + '%';
        document.getElementById('completion-percentage').textContent = percentage + '%';
    }

    // Document status tracking
    function updateDocumentStatus(docType, status) {
        const statusEl = document.getElementById(`doc-status-${docType}`);
        if (!statusEl) return;

        statusEl.className = 'badge rounded-pill small';

        switch(status) {
            case 'uploaded':
                statusEl.classList.add('bg-info', 'bg-opacity-10', 'text-info', 'border', 'border-info', 'border-opacity-25');
                statusEl.innerHTML = '<i class="bi bi-cloud-upload me-1"></i> Uploaded';
                break;
            case 'verified':
                statusEl.classList.add('bg-success', 'bg-opacity-10', 'text-success', 'border', 'border-success', 'border-opacity-25');
                statusEl.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Verified';
                break;
            case 'missing':
                statusEl.classList.add('bg-danger', 'bg-opacity-10', 'text-danger', 'border', 'border-danger', 'border-opacity-25');
                statusEl.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i> Missing';
                break;
            default:
                statusEl.classList.add('bg-secondary', 'bg-opacity-10', 'text-secondary', 'border', 'border-secondary', 'border-opacity-25');
                statusEl.innerHTML = '<i class="bi bi-dash-circle me-1"></i> Not Uploaded';
        }

        updateDocCount();
    }

    function updateDocCount() {
        const docInputs = document.querySelectorAll('input[type="file"][name^="documents"]');
        let count = 0;
        docInputs.forEach(input => {
            if (input.files.length > 0) count++;
        });
        document.getElementById('doc-count').textContent = count + '/5';
    }

    // Photo input change
    document.getElementById('photo-input').addEventListener('change', function(e) {
        const preview = document.getElementById('photo-preview');
        const cropBtn = document.getElementById('crop-btn');
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                originalImage = e.target.result;
                preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
                cropBtn.classList.remove('d-none');
                updateDocumentStatus('PHOTO', 'uploaded');
                updateCompletion();
                hasUnsavedChanges = true;
            }
            reader.readAsDataURL(file);
        }
    });

    // Document input changes
    document.querySelectorAll('input[type="file"][name^="documents"]').forEach(input => {
        input.addEventListener('change', function(e) {
            const docType = this.name.replace('documents[', '').replace(']', '');
            if (this.files.length > 0) {
                updateDocumentStatus(docType, 'uploaded');
            }
            updateCompletion();
            hasUnsavedChanges = true;
        });
    });

    // Form input changes
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('change', function() {
            updateCompletion();
            hasUnsavedChanges = true;
        });
    });

    // Initialize cropper when modal opens
    document.getElementById('cropModal').addEventListener('shown.bs.modal', function() {
        const image = document.getElementById('crop-image');
        image.src = originalImage;
        cropper = new Cropper(image, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.8,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
        });
    });

    // Destroy cropper when modal closes
    document.getElementById('cropModal').addEventListener('hidden.bs.modal', function() {
        if (cropper) {
            cropper.destroy();
        }
    });

    // Crop and save
    document.getElementById('save-crop').addEventListener('click', function() {
        if (cropper) {
            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 300,
            });
            const croppedData = canvas.toDataURL('image/jpeg', 0.9);
            document.getElementById('cropped-photo-data').value = croppedData;
            document.getElementById('photo-preview').innerHTML = `<img src="${croppedData}" style="width: 100%; height: 100%; object-fit: cover;">`;
            bootstrap.Modal.getInstance(document.getElementById('cropModal')).hide();
            hasUnsavedChanges = true;
        }
    });

    // Filter sections based on class
    const classSelect = document.getElementById('class_id');
    const sectionSelect = document.getElementById('section_id');
    const sections = Array.from(sectionSelect.options);

    classSelect.addEventListener('change', function() {
        const classId = this.value;
        sectionSelect.innerHTML = '<option value="">Select Section</option>';

        sections.forEach(option => {
            if (option.getAttribute('data-class') === classId) {
                sectionSelect.appendChild(option.cloneNode(true));
            }
        });
        updateCompletion();
    });

    // Initial filter if class is selected
    if (classSelect.value) {
        classSelect.dispatchEvent(new Event('change'));
    }

    // Save Draft functionality
    document.getElementById('save-draft-btn').addEventListener('click', function() {
        const form = document.querySelector('form');
        const formData = new FormData(form);
        formData.append('save_as_draft', 'true');

        document.getElementById('auto-save-status').innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Saving...';

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                admissionId = data.admission_id;
                document.getElementById('admission-no-display').textContent = 'Admission No: ' + data.admission_no;
                document.getElementById('auto-save-status').innerHTML = '<i class="bi bi-check-circle me-1"></i> Draft Saved';
                hasUnsavedChanges = false;

                Swal.fire({
                    icon: 'success',
                    title: 'Draft Saved',
                    text: `Admission No: ${data.admission_no}\nStatus: DRAFT\nYou may continue editing later.`,
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        })
        .catch(error => {
            document.getElementById('auto-save-status').innerHTML = '<i class="bi bi-cloud me-1"></i> Auto-save enabled';
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to save draft'
            });
        });
    });

    // Reset form
    document.getElementById('reset-btn').addEventListener('click', function() {
        if (hasUnsavedChanges) {
            Swal.fire({
                title: 'Reset Form?',
                text: 'All unsaved changes will be lost',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, reset'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.querySelector('form').reset();
                    hasUnsavedChanges = false;
                    updateCompletion();
                    // Reset document statuses
                    ['PHOTO', 'BIRTH_CERTIFICATE', 'TC', 'STUDENT_AADHAAR', 'PARENT_AADHAAR'].forEach(type => {
                        updateDocumentStatus(type, 'not_uploaded');
                    });
                }
            });
        } else {
            document.querySelector('form').reset();
            updateCompletion();
        }
    });

    // Auto-save every 30 seconds
    function startAutoSave() {
        autoSaveInterval = setInterval(() => {
            if (hasUnsavedChanges && admissionId) {
                document.getElementById('save-draft-btn').click();
            }
        }, 30000);
    }

    // Unsaved changes warning
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        updateCompletion();
        startAutoSave();
    });
</script>
@endpush

<!-- Crop Photo Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-crop me-2"></i> Crop Photo</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3" style="max-height: 400px; overflow: hidden;">
                    <img id="crop-image" style="max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer bg-light border-0 py-3">
                <button type="button" class="btn btn-link text-muted text-decoration-none px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="save-crop" class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm">
                    <i class="bi bi-check-lg me-2"></i> Save Crop
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
