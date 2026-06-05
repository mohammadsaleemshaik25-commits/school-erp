@extends('fees.layout')

@section('title', 'Process Student Promotion')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Step 2: Process Promotion</h1>
        <a href="{{ route('promotions.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-arrow-left me-2"></i> Back to Selection
        </a>
    </div>

    <form action="{{ route('promotions.store') }}" method="POST" id="promotionForm">
        @csrf
        
        <div class="row g-4">
            <!-- Left: Student List -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-people me-2 text-primary"></i> Students to Process ({{ count($enrollments) }})</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label small fw-bold" for="selectAll">Select All</label>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 600px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase fw-bold">
                                <tr>
                                    <th class="ps-4">Select</th>
                                    <th>Adm No</th>
                                    <th>Student Name</th>
                                    <th>Father's Name</th>
                                    <th>Section</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrollments as $enrollment)
                                    <tr>
                                        <td class="ps-4">
                                            <input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]" value="{{ $enrollment->student_id }}">
                                        </td>
                                        <td class="fw-bold">{{ $enrollment->student->admission_no }}</td>
                                        <td>{{ $enrollment->student->student_name }}</td>
                                        <td>{{ $enrollment->student->father_name }}</td>
                                        <td>{{ $enrollment->section->section_name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Target Selection & Actions -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="m-0 fw-bold text-dark"><i class="bi bi-bullseye me-2 text-primary"></i> Promotion Action</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Action Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="PROMOTED">PROMOTED (Next Class)</option>
                                <option value="DETAINED">DETAINED (Same Class)</option>
                                <option value="TRANSFERRED">TRANSFERRED (Leaving School)</option>
                                <option value="DROPPED">DROPPED (Discontinued)</option>
                            </select>
                        </div>

                        <div id="targetFields">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Target Academic Year <span class="text-danger">*</span></label>
                                <select name="target_academic_year_id" class="form-select">
                                    <option value="">Select Target Year</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->academic_year_id }}">{{ $year->year_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Target Class <span class="text-danger">*</span></label>
                                <select name="target_class_id" id="target_class_id" class="form-select">
                                    <option value="">Select Target Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->class_id }}">{{ $class->class_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Target Section <span class="text-danger">*</span></label>
                                <select name="target_section_id" id="target_section_id" class="form-select">
                                    <option value="">Select Target Section</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->section_id }}" data-class="{{ $section->class_id }}">
                                            {{ $section->section_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-info small rounded-3 mt-4 mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            For <strong>PROMOTED</strong> and <strong>DETAINED</strong>, a new enrollment and fee account will be created automatically.
                        </div>

                        @if(in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN']))
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-sm" id="submitBtn" disabled>
                            <i class="bi bi-check-circle-fill me-2"></i> Confirm Promotion
                        </button>
                        @else
                        <div class="alert alert-danger small rounded-3 text-center">
                            <i class="bi bi-shield-lock-fill me-2"></i> Access Denied. Only Administrators can finalize promotions.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('selectAll');
        const studentCheckboxes = document.querySelectorAll('.student-checkbox');
        const submitBtn = document.getElementById('submitBtn');
        const statusSelect = document.getElementById('status');
        const targetFields = document.getElementById('targetFields');
        const targetClassSelect = document.getElementById('target_class_id');
        const targetSectionSelect = document.getElementById('target_section_id');
        const sections = Array.from(targetSectionSelect.options);

        // Select All functionality
        selectAll.addEventListener('change', function() {
            studentCheckboxes.forEach(cb => cb.checked = this.checked);
            updateSubmitButton();
        });

        studentCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateSubmitButton);
        });

        function updateSubmitButton() {
            const selectedCount = document.querySelectorAll('.student-checkbox:checked').length;
            submitBtn.disabled = selectedCount === 0;
            submitBtn.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i> Confirm ${selectedCount} Promotions`;
        }

        // Status change logic
        statusSelect.addEventListener('change', function() {
            const status = this.value;
            if (status === 'TRANSFERRED' || status === 'DROPPED') {
                targetFields.style.display = 'none';
                document.querySelectorAll('#targetFields select').forEach(s => s.required = false);
            } else {
                targetFields.style.display = 'block';
                document.querySelectorAll('#targetFields select').forEach(s => s.required = true);
            }
        });

        // Target section filter
        targetClassSelect.addEventListener('change', function() {
            const classId = this.value;
            targetSectionSelect.innerHTML = '<option value="">Select Target Section</option>';
            sections.forEach(option => {
                if (option.getAttribute('data-class') === classId) {
                    targetSectionSelect.appendChild(option.cloneNode(true));
                }
            });
        });

        // Form confirmation
        document.getElementById('promotionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const selectedCount = document.querySelectorAll('.student-checkbox:checked').length;
            const status = statusSelect.value;

            Swal.fire({
                title: 'Confirm Promotion',
                text: `You are about to process ${selectedCount} students as ${status}. This action is permanent and will generate new fee records.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, proceed!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });
</script>
@endpush
@endsection
