@extends('fees.layout')

@section('title', 'Student Promotion Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Student Promotion Management</h1>
        <a href="{{ route('promotions.report') }}" class="btn btn-outline-primary btn-sm px-3 rounded-pill shadow-sm">
            <i class="bi bi-journal-text me-2"></i> Promotion History
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show rounded-3 mb-4" role="alert">
            {{ session('warning') }}
            @if(session('errors_list'))
                <ul class="mt-2 mb-0 small">
                    @foreach(session('errors_list') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-arrow-up-right-circle me-2"></i> Step 1: Select Source & Target</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('promotions.process') }}" method="GET">
                        <div class="row g-4">
                            <!-- Source Section -->
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold text-muted text-uppercase small mb-3 border-bottom pb-2">Source (Current)</h6>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Academic Year <span class="text-danger">*</span></label>
                                    <select name="source_academic_year_id" class="form-select" required>
                                        <option value="">Select Year</option>
                                        @foreach($academicYears as $year)
                                            <option value="{{ $year->academic_year_id }}" {{ $year->is_active ? 'selected' : '' }}>
                                                {{ $year->year_name }} {{ $year->is_active ? '(Active)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Class <span class="text-danger">*</span></label>
                                    <select name="source_class_id" id="source_class_id" class="form-select" required>
                                        <option value="">Select Class</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->class_id }}">{{ $class->class_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small fw-bold">Section</label>
                                    <select name="source_section_id" id="source_section_id" class="form-select">
                                        <option value="">All Sections</option>
                                        @foreach($sections as $section)
                                            <option value="{{ $section->section_id }}" data-class="{{ $section->class_id }}">
                                                {{ $section->section_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Info/Instruction Section -->
                            <div class="col-md-6 d-flex flex-column justify-content-center bg-light rounded-3 p-4">
                                <div class="text-center">
                                    <i class="bi bi-info-circle display-4 text-primary mb-3"></i>
                                    <h6 class="fw-bold text-dark">Promotion Instructions</h6>
                                    <ul class="text-start small text-muted mt-3">
                                        <li>Select the current class and year of the students.</li>
                                        <li>In the next step, you can select individual students and their promotion status.</li>
                                        <li><strong>PROMOTED:</strong> Moves student to the next year/class and creates a new fee account.</li>
                                        <li><strong>DETAINED:</strong> Keeps student in the same class for the next year.</li>
                                    </ul>
                                </div>
                            </div>

                            @php
                                $canProcess = in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN']);
                            @endphp

                            @if($canProcess)
                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm">
                                    Load Student List <i class="bi bi-arrow-right ms-2"></i>
                                </button>
                            </div>
                            @else
                            <div class="col-12 text-center mt-4">
                                <div class="alert alert-warning d-inline-block px-4 rounded-pill small">
                                    <i class="bi bi-lock-fill me-2"></i> Only Administrators can process promotions.
                                </div>
                            </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.getElementById('source_class_id');
        const sectionSelect = document.getElementById('source_section_id');
        const sections = Array.from(sectionSelect.options);

        classSelect.addEventListener('change', function() {
            const classId = this.value;
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            
            sections.forEach(option => {
                if (option.getAttribute('data-class') === classId) {
                    sectionSelect.appendChild(option.cloneNode(true));
                }
            });
        });
    });
</script>
@endpush
@endsection
