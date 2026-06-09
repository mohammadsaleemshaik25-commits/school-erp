@extends('fees.layout')

@section('title', 'Fee Collection - Search Students')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0 fw-bold text-dark">
            <i class="bi bi-cash-stack me-2 text-primary"></i>Fee Collection
        </h1>
        <!-- <a href="{{ route('fees.collect') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-2"></i> Old Interface
        </a> -->
    </div>

    <!-- Search Interface -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="m-0 fw-bold">Search Students</h6>
                    <small class="text-muted">Search by Name, Admission No, or Parent Name</small>
                </div>
                <div class="col-auto d-flex gap-2">
                    <select id="filterClass" class="form-select form-select-sm rounded-pill">
                        <option value="">All Classes</option>
                        @foreach($classes as $cls)
                            <option value="{{ $cls->class_id }}">{{ $cls->class_name }}</option>
                        @endforeach
                    </select>
                    <select id="filterSection" class="form-select form-select-sm rounded-pill">
                        <option value="">All Sections</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->section_id }}">{{ $sec->section_name }}</option>
                        @endforeach
                    </select>
                    <select id="filterAcademicYear" class="form-select form-select-sm rounded-pill">
                        <option value="">All Years</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->academic_year_id }}">{{ $year->year_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-4 bg-light bg-opacity-50">
            <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border mb-4">
                <span class="input-group-text bg-white border-0 ps-4 text-primary"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="form-control border-0 py-3 shadow-none" 
                       placeholder="Type student name or admission number..." autocomplete="off">
            </div>

            <div id="searchResults" class="row g-4">
                <div class="col-12 text-center py-5">
                    <div class="opacity-50 mb-3"><i class="bi bi-search" style="font-size: 3rem;"></i></div>
                    <h5 class="text-muted fw-normal">Start typing to find students.</h5>
                </div>
            </div>

            <div id="searchLoading" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Searching students...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let searchTimeout;

    function performSearch() {
        clearTimeout(searchTimeout);

        const query = $('#searchInput').val().trim();

        const hasFilters =
            $('#filterClass').val() ||
            $('#filterSection').val() ||
            $('#filterAcademicYear').val();

        // Allow empty query to fetch all, but prompt for 2 characters if they only type 1
        if (query.length > 0 && query.length < 2 && !hasFilters) {

            $('#searchResults').html(`
                <div class="col-12 text-center py-5">
                    <div class="opacity-50 mb-3">
                        <i class="bi bi-search" style="font-size:3rem;"></i>
                    </div>
                    <h5 class="text-muted fw-normal">
                        Start typing to find students (min 2 characters).
                    </h5>
                </div>
            `);

            return;
        }

        $('#searchLoading').removeClass('d-none');
        $('#searchResults').addClass('d-none');

        searchTimeout = setTimeout(function() {

            $.ajax({
                url: '{{ route("fees-collection.search") }}',
                type: 'GET',
                data: {
                    q: query,
                    class_id: $('#filterClass').val(),
                    section_id: $('#filterSection').val(),
                    academic_year_id: $('#filterAcademicYear').val()
                },

                success: function(response) {

                    $('#searchLoading').addClass('d-none');
                    $('#searchResults').removeClass('d-none');

                // Extract array whether the backend returns raw array or a paginated object
                const students = response.data ? response.data : response;

                if (!students || !students.length) {

                        $('#searchResults').html(`
                            <div class="col-12 text-center py-5">
                                <div class="opacity-50 mb-3">
                                    <i class="bi bi-emoji-frown" style="font-size:3rem;"></i>
                                </div>
                                <h5 class="text-muted fw-normal">
                                    No students found.
                                </h5>
                            </div>
                        `);

                        return;
                    }

                    let html = '';

                students.forEach(function(student) {

                        const photoUrl = student.photo_path
                            ? '/storage/' + student.photo_path
                            : 'https://via.placeholder.com/150?text=No+Photo';


                        console.log(student);
                        html += `
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border-0 shadow-sm rounded-4"
                                     style="cursor:pointer"
                                     onclick="window.location.href='/fees-collection/workspace/${student.student_id}'">

                                    <div class="row g-0">

                                        <div class="col-4">
                                            <img src="${photoUrl}"
                                                 class="img-fluid w-100 h-100"
                                                 style="object-fit:cover;min-height:120px;">
                                        </div>

                                        <div class="col-8">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h6 class="fw-bold mb-1 text-truncate" title="${student.student_name}">
                                                        ${student.student_name}
                                                    </h6>
                                                    <div class="ms-2 text-danger fw-bold small text-nowrap">
                                                        ₹${new Intl.NumberFormat('en-IN').format(student.total_due || 0)}
                                                    </div>
                                                </div>
                                                <span class="badge bg-light text-dark border">
                                                    ${student.admission_no}
                                                </span>

                                                <div class="small text-muted mt-2">
                                                ${student.class_name || ''}
                                                    -
                                                ${student.section_name || ''}
                                                </div>

                                                <div class="small text-muted">
                                                ${student.academic_year || ''}
                                                </div>

                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    $('#searchResults').html(html);
                },

                error: function() {

                    $('#searchLoading').addClass('d-none');
                    $('#searchResults').removeClass('d-none');

                    $('#searchResults').html(`
                        <div class="col-12 text-center py-5">
                            <div class="text-danger mb-3">
                                <i class="bi bi-exclamation-triangle"
                                   style="font-size:3rem;"></i>
                            </div>
                            <h5 class="text-muted">
                                Error searching students.
                            </h5>
                        </div>
                    `);
                }
            });

        }, 300);
    }

    // Bind to events
    $('#searchInput').on('input', performSearch);
    $('#filterClass,#filterSection,#filterAcademicYear').on('change', performSearch);

    // Trigger initial search to load default list immediately
    performSearch();
});
</script>
@endpush