@extends('fees.layout')

@section('title', 'Admission Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Admissions Management</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admissions.create') }}" class="btn btn-primary shadow-sm px-4 rounded-pill">
                <i class="bi bi-plus-lg me-2"></i> New Admission
            </a>
            <button class="btn btn-outline-secondary px-4 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-file-earmark-excel me-2"></i> Bulk Import
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4" id="kpi-container">
        <div class="col-md">
            <div class="card border-0 shadow-sm rounded-4 border-start border-primary border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">Total Students</div>
                    <div class="h3 fw-bold mb-0 text-primary" id="stat-total-students">...</div>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card border-0 shadow-sm rounded-4 border-start border-success border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">This Month</div>
                    <div class="h3 fw-bold mb-0 text-success" id="stat-month-admissions">...</div>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card border-0 shadow-sm rounded-4 border-start border-warning border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">Pending Verify</div>
                    <div class="h3 fw-bold mb-0 text-warning" id="stat-pending-verification">...</div>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card border-0 shadow-sm rounded-4 border-start border-danger border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">Missing Docs</div>
                    <div class="h3 fw-bold mb-0 text-danger" id="stat-missing-docs">...</div>
                </div>
            </div>
        </div>
        <div class="col-md">
            <div class="card border-0 shadow-sm rounded-4 border-start border-info border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">Missing Photos</div>
                    <div class="h3 fw-bold mb-0 text-info" id="stat-missing-photos">...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Student Finder -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden finder-container">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                        <i class="bi bi-person-search text-primary fs-5"></i>
                    </div>
                    <div>
                        <h6 class="m-0 fw-bold text-dark">Live Student Finder</h6>
                        <small class="text-muted">Search by Name, Admission No, or Parent Name</small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <select id="filterClass" class="form-select form-select-sm rounded-pill px-3 shadow-none finder-filter">
                        <option value="">All Classes</option>
                        @foreach($classes as $cls)
                            <option value="{{ $cls->class_id }}">{{ $cls->class_name }}</option>
                        @endforeach
                    </select>
                    <select id="filterStatus" class="form-select form-select-sm rounded-pill px-3 shadow-none finder-filter">
                        <option value="">All Statuses</option>
                        <option value="DRAFT">DRAFT</option>
                        <option value="SUBMITTED">SUBMITTED</option>
                        <option value="DOCUMENT VERIFIED">VERIFIED</option>
                        <option value="APPROVED">APPROVED</option>
                        <option value="ADMITTED">ADMITTED</option>
                        <option value="REJECTED">REJECTED</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-4 bg-light bg-opacity-50">
            <div class="search-wrapper mb-4">
                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border">
                    <span class="input-group-text bg-white border-0 ps-4 text-primary"><i class="bi bi-search"></i></span>
                    <input type="text" id="finderInput" class="form-control border-0 py-3 shadow-none" 
                           placeholder="Type to search students..." autocomplete="off">
                </div>
            </div>

            <!-- Finder Results Table -->
            <div id="finderResults" class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-white rounded-3 overflow-hidden shadow-sm">
                    <thead class="bg-light text-muted small text-uppercase fw-bold">
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Admission No</th>
                            <th>Class / Section</th>
                            <th>Father Name</th>
                            <th>Contact</th>
                            <th>Admission Date</th>
                            <th class="text-center">Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="finderResultsBody">
                        @foreach($admissions as $admission)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="photo-mini rounded-circle overflow-hidden me-2 flex-shrink-0" style="width: 35px; height: 35px; background: #eee;">
                                            @if($admission->student->photo_path)
                                                <img src="{{ asset('storage/' . $admission->student->photo_path) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center h-100 text-muted small">?</div>
                                            @endif
                                        </div>
                                        <div class="fw-bold text-dark">{{ $admission->student->student_name }}</div>
                                    </div>
                                </td>
                                <td class="fw-semibold text-primary">{{ $admission->student->admission_no }}</td>
                                <td>{{ $admission->classRoom->class_name }} / {{ $admission->section->section_name ?? 'N/A' }}</td>
                                <td>{{ $admission->student->father_name }}</td>
                                <td>{{ $admission->student->phone_primary }}</td>
                                <td>{{ $admission->created_at->format('d M Y') }}</td>
                                <td class="text-center">
                                    @php
                                        $statusClass = match($admission->admission_status) {
                                            'APPROVED', 'ADMITTED' => 'bg-success',
                                            'DRAFT' => 'bg-secondary',
                                            'SUBMITTED' => 'bg-primary',
                                            'DOCUMENT VERIFIED' => 'bg-warning text-dark',
                                            'REJECTED' => 'bg-danger',
                                            default => 'bg-light text-dark'
                                        };
                                    @endphp
                                    <span class="badge rounded-pill {{ $statusClass }} px-3">
                                        {{ $admission->admission_status }}
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('admissions.show', $admission->admission_id) }}" class="btn btn-white btn-sm border shadow-sm px-3 rounded-pill">
                                        <i class="bi bi-eye text-primary me-1"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Loading Spinner (Hidden) -->
            <div id="finderLoading" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Searching admissions...</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        const finderInput = $('#finderInput');
        const finderResultsBody = $('#finderResultsBody');
        const finderLoading = $('#finderLoading');
        const finderTable = $('#finderResults');
        const filterClass = $('#filterClass');
        const filterStatus = $('#filterStatus');
        let searchTimeout = null;

        // Load Dashboard Stats
        function loadStats() {
            $.get("{{ route('admissions.stats') }}", function(data) {
                $('#stat-total-students').text(data.total_students);
                $('#stat-month-admissions').text(data.month_admissions);
                $('#stat-pending-verification').text(data.pending_verification);
                $('#stat-missing-docs').text(data.missing_docs);
                $('#stat-missing-photos').text(data.missing_photos);
            });
        }
        loadStats();

        function performSearch() {
            const q = finderInput.val().trim();
            const class_id = filterClass.val();
            const status = filterStatus.val();

            finderLoading.removeClass('d-none');
            finderTable.addClass('opacity-50');

            $.ajax({
                url: "{{ route('admissions.finder') }}",
                data: { q, class_id, status },
                success: function(data) {
                    finderLoading.addClass('d-none');
                    finderTable.removeClass('opacity-50');
                    
                    let html = '';
                    if (data.length === 0) {
                        html = '<tr><td colspan="8" class="text-center py-5 text-muted italic"><i class="bi bi-inbox fs-1 d-block mb-2"></i>No admissions found.</td></tr>';
                    } else {
                        data.forEach(adm => {
                            const photo = adm.photo_url 
                                ? `<img src="${adm.photo_url}" alt="" style="width: 100%; height: 100%; object-fit: cover;">`
                                : `<div class="d-flex align-items-center justify-content-center h-100 text-muted small bg-light">?</div>`;

                            let statusClass = 'bg-light text-dark';
                            if (adm.status === 'APPROVED' || adm.status === 'ADMITTED') statusClass = 'bg-success';
                            else if (adm.status === 'DRAFT') statusClass = 'bg-secondary';
                            else if (adm.status === 'SUBMITTED') statusClass = 'bg-primary';
                            else if (adm.status === 'DOCUMENT VERIFIED') statusClass = 'bg-warning text-dark';
                            else if (adm.status === 'REJECTED') statusClass = 'bg-danger';

                            html += `
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="photo-mini rounded-circle overflow-hidden me-2 flex-shrink-0" style="width: 35px; height: 35px; background: #eee;">
                                                ${photo}
                                            </div>
                                            <div class="fw-bold text-dark">${adm.student_name}</div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold text-primary">${adm.admission_no}</td>
                                    <td>${adm.class_name} / ${adm.section_name}</td>
                                    <td>${adm.father_name}</td>
                                    <td>${adm.phone_primary}</td>
                                    <td>${adm.admission_date}</td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill ${statusClass} px-3">${adm.status}</span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <a href="/admissions/${adm.admission_id}" class="btn btn-white btn-sm border shadow-sm px-3 rounded-pill">
                                            <i class="bi bi-eye text-primary me-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    finderResultsBody.html(html);
                }
            });
        }

        finderInput.on('input', function() {
            if (this.value.length >= 2 || this.value.length === 0) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 300);
            }
        });

        $('.finder-filter').on('change', performSearch);
    });
</script>
@endpush
@endsection
