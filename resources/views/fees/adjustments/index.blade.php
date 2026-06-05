@extends('fees.layout')

@section('title', 'Fee Adjustments & Concessions')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Fee Adjustments & Concessions</h1>
        <div class="d-flex gap-2">
            <span class="badge bg-white text-primary border border-primary px-3 py-2 rounded-pill shadow-sm">
                <i class="bi bi-info-circle me-1"></i> Approval Workflow Active
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
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

    <!-- Request Form (Clerk/Admin Only) -->
    @if(in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN', 'CLERK', 'PRINCIPAL', 'CORRESPONDENT'], true))
        <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
            <div class="card-header bg-primary text-white py-3">
                <h6 class="m-0 fw-bold"><i class="bi bi-plus-circle me-2"></i> New Concession / Waiver Request</h6>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('fees.adjustments.store') }}" method="POST" id="adjustmentForm">
                    @csrf
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-2">Student Search <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-primary"><i class="bi bi-search text-primary"></i></span>
                                <select name="account_id" id="student_search" class="form-select border-primary shadow-none" required></select>
                            </div>
                            <div id="student_details" class="mt-3 p-3 bg-light rounded-3 border d-none">
                                <div class="row g-2">
                                    <div class="col-6 small text-muted">Class: <span id="det_class" class="text-dark fw-bold">-</span></div>
                                    <div class="col-6 small text-muted">Current Due: <span id="det_due" class="text-danger fw-bold">₹0</span></div>
                                    <div class="col-6 small text-muted">Tuition Fee: <span id="det_tuition" class="text-dark">₹0</span></div>
                                    <div class="col-6 small text-muted">Already Waived: <span id="det_waived" class="text-info">₹0</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Adjustment Type <span class="text-danger">*</span></label>
                                    <select name="adjustment_type" class="form-select border-primary shadow-none" required>
                                        <option value="CONCESSION">CONCESSION</option>
                                        <option value="WAIVER">WAIVER</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Disc %</label>
                                    <input type="number" name="discount_percent" id="disc_percent" class="form-control border-primary shadow-none" step="0.01" min="0" max="100" placeholder="e.g. 10">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Amount (₹)</label>
                                    <input type="number" name="discount_amount" id="disc_amount" class="form-control border-primary shadow-none" step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Reason for Request <span class="text-danger">*</span></label>
                                    <input type="text" name="reason" class="form-control border-primary shadow-none" required placeholder="Describe why this concession is needed...">
                                </div>
                                <div class="col-md-12 text-end mt-4">
                                    <button type="submit" class="btn btn-primary px-5 fw-bold rounded-pill shadow-sm">
                                        <i class="bi bi-send-fill me-2"></i> Submit Concession Request
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Concession History -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> Request History & Approval Queue</h6>
            <form action="{{ route('fees.adjustments.index') }}" method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm rounded-pill px-3 border-primary shadow-none" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>PENDING</option>
                    <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>APPROVED</option>
                    <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>REJECTED</option>
                </select>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm rounded-pill px-3 border-primary shadow-none" placeholder="Search Student/Adm No...">
                <button type="submit" class="btn btn-primary btn-sm rounded-circle"><i class="bi bi-search"></i></button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small text-uppercase fw-bold">
                    <tr>
                        <th class="ps-4">Student</th>
                        <th>Type</th>
                        <th class="text-end">Percentage</th>
                        <th class="text-end">Amount</th>
                        <th>Reason</th>
                        <th>Requester</th>
                        <th class="text-center">Status</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adj)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark">{{ $adj->feeAccount->enrollment->student->student_name }}</div>
                                <div class="small text-muted">{{ $adj->feeAccount->enrollment->student->admission_no }} | {{ $adj->feeAccount->enrollment->classRoom->class_name }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $adj->adjustment_type }}</span>
                            </td>
                            <td class="text-end fw-bold">{{ $adj->discount_percent }}%</td>
                            <td class="text-end fw-bold text-primary">₹{{ number_format($adj->discount_amount, 2) }}</td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $adj->reason }}">{{ $adj->reason }}</div>
                                @if($adj->rejection_reason)
                                    <div class="small text-danger mt-1">Rejection: {{ $adj->rejection_reason }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="small fw-semibold">{{ $adj->requester->full_name ?? $adj->requester->username }}</div>
                                <div class="small text-muted">{{ $adj->created_at->format('d M Y') }}</div>
                            </td>
                            <td class="text-center">
                                @php
                                    $statusClass = match($adj->approval_status) {
                                        'PENDING' => 'bg-warning',
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
                                    @if(in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN', 'PRINCIPAL', 'CORRESPONDENT']))
                                        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" 
                                                onclick="openApprovalModal('{{ $adj->adjustment_id }}', '{{ $adj->feeAccount->enrollment->student->student_name }}', '{{ number_format($adj->discount_amount, 2) }}')">
                                            Decide
                                        </button>
                                    @else
                                        <span class="text-muted small italic">Awaiting Approval</span>
                                    @endif
                                @else
                                    <div class="small text-muted">
                                        {{ $adj->approval_status === 'APPROVED' ? 'Approved' : 'Rejected' }} by<br>
                                        {{ $adj->approver->username ?? 'System' }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted italic">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No concession requests found.
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

@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2 for Student Search
        $('#student_search').select2({
            placeholder: 'Type Student Name or Admission No...',
            minimumInputLength: 2,
            ajax: {
                url: '{{ route("fees.adjustments.search") }}',
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            templateResult: formatStudent,
            templateSelection: formatStudentSelection
        });

        function formatStudent (student) {
            if (student.loading) return student.text;
            return $(`
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="fw-bold">${student.text}</div>
                        <div class="small text-muted">${student.class}</div>
                    </div>
                    <div class="text-danger fw-bold">Due: ₹${student.due}</div>
                </div>
            `);
        }

        function formatStudentSelection (student) {
            return student.text || student.placeholder;
        }

        // Auto-fill details and handle percentage calculation
        $('#student_search').on('select2:select', function (e) {
            const data = e.params.data;
            $('#student_details').removeClass('d-none');
            $('#det_class').text(data.class);
            $('#det_due').text('₹' + data.due);
            $('#det_tuition').text('₹' + data.tuition);
            $('#det_waived').text('₹' + data.waived);
            
            window.selectedTuition = parseFloat(data.tuition);
            calculateAmount();
        });

        $('#disc_percent').on('input', function() {
            calculateAmount();
        });

        function calculateAmount() {
            const percent = parseFloat($('#disc_percent').val());
            if (percent > 0 && window.selectedTuition > 0) {
                const amount = (window.selectedTuition * percent / 100).toFixed(2);
                $('#disc_amount').val(amount);
            }
        }

        // Toggle rejection reason block
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
</script>
<style>
    .select2-container--default .select2-selection--single {
        border: 1px solid #0d6efd;
        height: 38px;
        border-radius: 0.375rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>
@endpush
