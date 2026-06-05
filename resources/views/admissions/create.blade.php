@extends('fees.layout')

@section('title', 'New Admission')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">New Admission Form</h1>
        <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
            <i class="bi bi-arrow-left me-2"></i> Back to List
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

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Father's Name <span class="text-danger">*</span></label>
                                <input type="text" name="father_name" value="{{ old('father_name') }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Mother's Name <span class="text-danger">*</span></label>
                                <input type="text" name="mother_name" value="{{ old('mother_name') }}" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Aadhaar Number <span class="text-danger">*</span></label>
                                <input type="text" name="aadhaar_no" value="{{ old('aadhaar_no') }}" class="form-control" required placeholder="12-digit number">
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

                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Current Address <span class="text-danger">*</span></label>
                                <textarea name="address" rows="3" class="form-control" required>{{ old('address') }}</textarea>
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
                            <div class="col-md-8">
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
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="m-0 fw-bold text-dark"><i class="bi bi-file-earmark-arrow-up me-2 text-primary"></i> Documents</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Aadhaar Card</label>
                            <input type="file" name="documents[AADHAAR]" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Birth Certificate</label>
                            <input type="file" name="documents[BIRTH_CERTIFICATE]" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Transfer Certificate (TC)</label>
                            <input type="file" name="documents[TC]" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold text-muted text-uppercase">Other Document</label>
                            <input type="file" name="documents[OTHER]" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 bg-primary text-white">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">Admission Process</h6>
                        <p class="small opacity-75 mb-4">By submitting this form, you will automatically create:</p>
                        <ul class="small opacity-75 mb-4 ps-3">
                            <li>Student Profile</li>
                            <li>Current Enrollment</li>
                            <li>Fee Ledger Account</li>
                            <li>Admission Record</li>
                        </ul>
                        <button type="submit" class="btn btn-white w-100 fw-bold text-primary py-3 rounded-3 shadow-sm">
                            <i class="bi bi-check-circle-fill me-2"></i> Confirm Admission
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('photo-input').addEventListener('change', function(e) {
        const preview = document.getElementById('photo-preview');
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
            }
            reader.readAsDataURL(file);
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
    });

    // Initial filter if class is selected
    if (classSelect.value) {
        classSelect.dispatchEvent(new Event('change'));
    }
</script>
@endpush
@endsection
