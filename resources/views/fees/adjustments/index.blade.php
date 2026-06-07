@extends('fees.layout')

@section('title', 'Concession Management')

@section('content')
@php
    $roleName = strtoupper(auth()->user()->role->role_name ?? '');
    $isManagement = in_array($roleName, ['ADMIN', 'ADMINISTRATOR', 'PRINCIPAL', 'CORRESPONDENT']);
    $isClerk = $roleName === 'CLERK';
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">{{ $isClerk ? 'My Concession Requests' : 'Concession Management' }}</h1>
        <div class="d-flex gap-2">
            @if($isManagement)
                <span class="badge bg-white text-primary border border-primary px-3 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-shield-lock me-1"></i> Centralized Control
                </span>
            @else
                <span class="badge bg-white text-success border border-success px-3 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-person-check me-1"></i> Clerk Portal
                </span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('success_clerk'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4 p-4" role="alert">
            <div class="d-flex align-items-start">
                <div class="bg-success text-white rounded-circle p-2 me-3">
                    <i class="bi bi-check-lg fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">✓ {{ session('success_clerk')['message'] }}</h5>
                    <div class="mb-2">
                        <span class="badge bg-white text-success border border-success px-2 py-1">Request ID: #{{ session('success_clerk')['id'] }}</span>
                        <span class="badge bg-warning text-dark px-2 py-1 ms-1">Status: {{ session('success_clerk')['status'] }}</span>
                    </div>
                    <p class="mb-0 text-muted small">{{ session('success_clerk')['detail'] }}</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <h6 class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Please correct the following errors:</h6>
            <ul class="mb-0 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Dashboard Metrics -->
    @if($isManagement)
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 border-start border-primary border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">Total Requested</div>
                    <div class="h3 fw-bold mb-0 text-primary">{{ number_format($stats['total_requested']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 border-start border-success border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">Total Approved</div>
                    <div class="h3 fw-bold mb-0 text-success">{{ number_format($stats['total_approved']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 border-start border-danger border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">Total Rejected</div>
                    <div class="h3 fw-bold mb-0 text-danger">{{ number_format($stats['total_rejected']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 border-start border-info border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">Total Concession (₹)</div>
                    <div class="h3 fw-bold mb-0 text-info">₹{{ number_format($stats['total_approved_amount'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Student Finder Section -->
    @if($isManagement)
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden finder-container">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                        <i class="bi bi-person-search text-primary fs-5"></i>
                    </div>
                    <div>
                        <h6 class="m-0 fw-bold text-dark">Student Finder</h6>
                        <small class="text-muted">Live search by Name, Admission No, or Parent Name</small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <select id="filterClass" class="form-select form-select-sm rounded-pill px-3 shadow-none finder-filter">
                        <option value="">All Classes</option>
                        @foreach($classes as $cls)
                            <option value="{{ $cls->class_id }}">{{ $cls->class_name }}</option>
                        @endforeach
                    </select>
                    <select id="filterSection" class="form-select form-select-sm rounded-pill px-3 shadow-none finder-filter">
                        <option value="">All Sections</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->section_id }}">{{ $sec->section_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-4 bg-light bg-opacity-50">
            <div class="search-wrapper mb-4">
                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border">
                    <span class="input-group-text bg-white border-0 ps-4 text-primary"><i class="bi bi-search"></i></span>
                    <input type="text" id="finderInput" class="form-control border-0 py-3 shadow-none" 
                           placeholder="Find student to apply/review concession..." autocomplete="off">
                </div>
            </div>

            <!-- Finder Results Grid -->
            <div id="finderResults" class="row g-4">
                <div class="col-12 text-center py-5">
                    <div class="opacity-50 mb-3">
                        <i class="bi bi-search" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="text-muted fw-normal">Start typing student name, admission number, or parent name.</h5>
                </div>
            </div>

            <!-- Loading Spinner (Hidden) -->
            <div id="finderLoading" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Searching students...</p>
            </div>
        </div>
    </div>
    @endif

    <!-- History & Reports Section -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3">
            <div class="row align-items-center g-3">
                <div class="col-md-4">
                    <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> {{ $isClerk ? 'My Request History' : 'History & Approval Queue' }}</h6>
                </div>
                <div class="col-md-8">
                    <form action="{{ route('fees.adjustments.index') }}" method="GET" class="row g-2 justify-content-end">
                        @if($isManagement)
                        <div class="col-auto">
                            <select name="class_id" class="form-select form-select-sm rounded-pill px-3 border-primary shadow-none" onchange="this.form.submit()">
                                <option value="">All Classes</option>
                                @foreach($classes as $cls)
                                    <option value="{{ $cls->class_id }}" {{ request('class_id') == $cls->class_id ? 'selected' : '' }}>{{ $cls->class_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <select name="section_id" class="form-select form-select-sm rounded-pill px-3 border-primary shadow-none" onchange="this.form.submit()">
                                <option value="">All Sections</option>
                                @foreach($sections as $sec)
                                    <option value="{{ $sec->section_id }}" {{ request('section_id') == $sec->section_id ? 'selected' : '' }}>{{ $sec->section_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-auto">
                            <select name="status" class="form-select form-select-sm rounded-pill px-3 border-primary shadow-none" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>PENDING</option>
                                <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                                <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control form-control-sm rounded-pill px-3 border-primary shadow-none" onchange="this.form.submit()">
                        </div>
                        <div class="col-auto">
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control form-control-sm rounded-pill px-3 border-primary shadow-none" onchange="this.form.submit()">
                        </div>
                        <div class="col-auto">
                            <div class="input-group input-group-sm">
                                <input type="text" name="q" value="{{ request('q') }}" class="form-control rounded-pill-start px-3 border-primary shadow-none" placeholder="Search History...">
                                <button type="submit" class="btn btn-primary rounded-pill-end"><i class="bi bi-search"></i></button>
                            </div>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('fees.adjustments.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-none">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase fw-bold">
                    <tr>
                        <th class="ps-4">Student</th>
                        <th class="text-end">{{ $isClerk ? 'Requested Amount' : 'Amount' }}</th>
                        <th>Reason</th>
                        <th>{{ $isClerk ? 'Request Date' : 'Requester' }}</th>
                        <th class="text-center">Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adj)
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="photo-mini rounded-circle overflow-hidden me-2 flex-shrink-0" style="width: 35px; height: 35px; background: #eee;">
                                        @if($adj->feeAccount->enrollment->student->photo_path)
                                            <img src="{{ asset('storage/' . $adj->feeAccount->enrollment->student->photo_path) }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <div class="d-flex align-items-center justify-content-center h-100 text-muted small">?</div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $adj->feeAccount->enrollment->student->student_name }}</div>
                                        <div class="small text-muted">{{ $adj->feeAccount->enrollment->student->admission_no }} | {{ $adj->feeAccount->enrollment->classRoom->class_name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end fw-bold text-primary">₹{{ number_format($adj->discount_amount, 2) }}</td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $adj->reason }}">{{ $adj->reason }}</div>
                                @if($adj->rejection_reason)
                                    <div class="small text-danger mt-1"><i class="bi bi-x-circle me-1"></i>Rejected: {{ $adj->rejection_reason }}</div>
                                @endif
                            </td>
                            <td>
                                @if($isClerk)
                                    <div class="small fw-semibold">{{ $adj->created_at->format('d M Y') }}</div>
                                    <div class="small text-muted">{{ $adj->created_at->format('h:i A') }}</div>
                                @else
                                    <div class="small fw-semibold">{{ $adj->requester->full_name ?? $adj->requester->username }}</div>
                                    <div class="small text-muted">{{ $adj->created_at->format('d M Y') }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $statusClass = match($adj->approval_status) {
                                        'PENDING' => 'bg-warning text-dark',
                                        'APPROVED' => 'bg-success',
                                        'REJECTED' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge rounded-pill {{ $statusClass }} px-3">
                                    {{ $adj->approval_status }}
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                @if($adj->approval_status === 'PENDING')
                                    @if($isManagement)
                                        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" 
                                                onclick="openApprovalModal('{{ $adj->adjustment_id }}', '{{ $adj->feeAccount->enrollment->student->student_name }}', '{{ number_format($adj->discount_amount, 2) }}')">
                                            Decide
                                        </button>
                                    @else
                                        <span class="text-warning small fw-bold"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                                    @endif
                                @else
                                    <div class="small text-muted">
                                        @if($adj->approval_status === 'APPROVED')
                                            <div class="text-success fw-bold"><i class="bi bi-check2-all me-1"></i>Approved</div>
                                            <div class="x-small">By: {{ $adj->approver->username ?? 'System' }}</div>
                                            <div class="x-small">On: {{ $adj->approved_at ? \Carbon\Carbon::parse($adj->approved_at)->format('d/m/Y') : '' }}</div>
                                        @else
                                            <div class="text-danger fw-bold"><i class="bi bi-x-circle me-1"></i>Rejected</div>
                                            <div class="x-small">By: {{ $adj->approver->username ?? 'System' }}</div>
                                            <div class="x-small">On: {{ $adj->approved_at ? \Carbon\Carbon::parse($adj->approved_at)->format('d/m/Y') : '' }}</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted italic">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No concession requests found matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($adjustments->hasPages())
            <div class="card-footer bg-white border-top py-3">
                {{ $adjustments->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-0 py-3">
                <h6 class="modal-title fw-bold text-dark"><i class="bi bi-shield-check me-2 text-primary"></i> Concession Decision</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approvalForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="small text-muted mb-4">You are reviewing a concession request for <strong id="modal_student_name"></strong> for an amount of <strong class="text-primary">₹<span id="modal_amount"></span></strong>.</p>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Final Decision</label>
                        <select name="status" class="form-select border-primary shadow-none" required id="decision_select">
                            <option value="APPROVED">APPROVE REQUEST</option>
                            <option value="REJECTED">REJECT REQUEST</option>
                        </select>
                    </div>

                    <div class="mb-0" id="rejection_block" style="display: none;">
                        <label class="form-label small fw-bold text-muted text-uppercase">Rejection Reason</label>
                        <textarea name="decision_remarks" class="form-control border-danger shadow-none" rows="2" placeholder="Explain why the request was rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-link text-muted text-decoration-none px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm">Confirm Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .finder-container { transition: all 0.3s ease; }
    .search-wrapper .input-group:focus-within { border-color: var(--bs-primary) !important; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; }
    
    .student-card {
        cursor: pointer;
        transition: all 0.2s ease;
        height: 100%;
        border: 1px solid rgba(0,0,0,0.05);
        background: #fff;
    }
    .student-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,0.1) !important;
        border-color: var(--bs-primary);
    }
    .student-card .photo-container {
        width: 60px;
        height: 60px;
        background: #f8f9fa;
        border: 2px solid #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .student-card .photo-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .student-card .student-name {
        font-size: 0.95rem;
        color: #1a1a1a;
        margin-bottom: 2px;
    }
    .student-card .admission-no {
        font-size: 0.8rem;
        font-family: var(--bs-font-monospace);
        color: var(--bs-primary);
    }
    .student-card .info-row {
        font-size: 0.75rem;
        margin-bottom: 2px;
    }
    .student-card .stat-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 8px;
        margin-top: 10px;
    }
    .student-card .stat-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        font-weight: bold;
        color: #888;
        display: block;
    }
    .student-card .stat-value {
        font-size: 0.85rem;
        font-weight: bold;
        color: #333;
    }
    .x-small { font-size: 0.7rem; }
</style>

@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        const finderInput = $('#finderInput');
        const finderResults = $('#finderResults');
        const finderLoading = $('#finderLoading');
        const filterClass = $('#filterClass');
        const filterSection = $('#filterSection');
        let searchTimeout = null;

        function performSearch() {
            const q = finderInput.val().trim();
            const class_id = filterClass.val();
            const section_id = filterSection.val();

            if (q.length < 2 && !class_id && !section_id) {
                finderResults.html(`
                    <div class="col-12 text-center py-5">
                        <div class="opacity-50 mb-3">
                            <i class="bi bi-search" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-muted fw-normal">Start typing student name, admission number, or parent name.</h5>
                    </div>
                `).removeClass('d-none');
                return;
            }

            finderLoading.removeClass('d-none');
            finderResults.addClass('d-none');

            $.ajax({
                url: "{{ route('fees.adjustments.finder') }}",
                data: { q, class_id, section_id },
                success: function(data) {
                    finderLoading.addClass('d-none');
                    finderResults.removeClass('d-none');
                    
                    if (data.length === 0) {
                        finderResults.html(`
                            <div class="col-12 text-center py-5">
                                <div class="opacity-50 mb-3">
                                    <i class="bi bi-person-x" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="text-muted fw-normal">No students found matching your search.</h5>
                            </div>
                        `);
                        return;
                    }

                    let html = '';
                    data.forEach(student => {
                        const photo = student.photo_url 
                            ? `<img src="${student.photo_url}" alt="">`
                            : `<div class="d-flex align-items-center justify-content-center h-100 text-muted small bg-light">?</div>`;

                        let statusBadge = '';
                        let actionButton = '';
                        const isManagement = {{ $isManagement ? 'true' : 'false' }};
                        
                        if (student.current_status === 'PENDING') {
                            statusBadge = '<span class="badge bg-warning text-dark rounded-pill px-2">PENDING</span>';
                            if (isManagement) {
                                actionButton = `<button class="btn btn-primary btn-sm rounded-pill w-100 mt-2 shadow-sm fw-bold" onclick="event.stopPropagation(); openApprovalModal('${student.adjustment_id}', '${student.student_name}', '${student.concession_amount}')">DECIDE</button>`;
                            }
                        } else if (student.current_status === 'APPROVED') {
                            statusBadge = '<span class="badge bg-success rounded-pill px-2">APPROVED</span>';
                        } else if (student.current_status === 'REJECTED') {
                            statusBadge = '<span class="badge bg-danger rounded-pill px-2">REJECTED</span>';
                        }

                        html += `
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="card student-card border-0 shadow-sm rounded-4 p-3" onclick="openConcessionRequestModal('${student.account_id}', '${student.student_name}', '${student.admission_no}', '${student.class_name}', '${student.tuition_fee}', '${student.outstanding_amount}')">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="photo-container rounded-circle overflow-hidden me-2 flex-shrink-0">
                                            ${photo}
                                        </div>
                                        <div class="overflow-hidden flex-grow-1">
                                            <div class="student-name fw-bold text-truncate">${student.student_name}</div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="admission-no">${student.admission_no}</span>
                                                ${statusBadge}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="info-grid mb-2">
                                        <div class="info-row d-flex justify-content-between">
                                            <span class="text-muted">Class / Sec:</span>
                                            <span class="fw-semibold">${student.class_name} / ${student.section_name}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between">
                                            <span class="text-muted">Parent Name:</span>
                                            <span class="fw-semibold text-truncate ms-2">${student.father_name}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between">
                                            <span class="text-muted">Contact No:</span>
                                            <span class="fw-semibold">${student.phone_primary}</span>
                                        </div>
                                    </div>
                                    <div class="stat-box row g-0">
                                        <div class="col-4 text-center border-end">
                                            <span class="stat-label">Tuition</span>
                                            <span class="stat-value">₹${student.tuition_fee}</span>
                                        </div>
                                        <div class="col-4 text-center border-end">
                                            <span class="stat-label">Concession</span>
                                            <span class="stat-value text-success">₹${student.concession_amount}</span>
                                        </div>
                                        <div class="col-4 text-center">
                                            <span class="stat-label">Outstanding</span>
                                            <span class="stat-value text-danger">₹${student.outstanding_amount}</span>
                                        </div>
                                    </div>
                                    ${actionButton}
                                </div>
                            </div>
                        `;
                    });
                    finderResults.html(html);
                }
            });
        }

        finderInput.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 300);
        });

        $('.finder-filter').on('change', performSearch);

        // Decision logic
        $('#decision_select').on('change', function() {
            if ($(this).val() === 'REJECTED') {
                $('#rejection_block').slideDown();
                $('[name="decision_remarks"]').attr('required', true);
            } else {
                $('#rejection_block').slideUp();
                $('[name="decision_remarks"]').attr('required', false);
            }
        });
    });

    function openApprovalModal(adjId, studentName, amount) {
        $('#modal_student_name').text(studentName);
        $('#modal_amount').text(amount);
        $('#approvalForm').attr('action', `/fees/adjustments/${adjId}/decide`);
        new bootstrap.Modal(document.getElementById('approvalModal')).show();
    }

    function openConcessionRequestModal(accountId, studentName, admissionNo, className, tuitionFee, outstandingAmount) {
        // Reuse global modal from layout
        if (typeof openConcessionModal === 'function') {
            openConcessionModal({
                account_id: accountId,
                student_name: studentName,
                admission_no: admissionNo,
                class_name: className,
                tuition_fee: tuitionFee,
                current_due: outstandingAmount
            });
        }
    }
</script>
@endpush
