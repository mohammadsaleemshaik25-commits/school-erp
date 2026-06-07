@extends('fees.layout')

@section('title', 'Document Verification Queue')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">Admissions</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Verification Queue</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 fw-bold text-dark">Document Verification Queue</h1>
        </div>
        <div>
            <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill shadow-sm">
                <i class="bi bi-arrow-left me-2"></i> Back to Admissions
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm mb-4 rounded-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admissions.verification-queue') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-start-0" 
                               value="{{ $search }}" placeholder="Search by Student Name or Admission No...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="document_type" class="form-select bg-light">
                        <option value="">All Document Types</option>
                        @foreach($documentTypes as $key => $label)
                            <option value="{{ $key }}" @selected($docType === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">
                        <i class="bi bi-filter me-1"></i> Apply Filters
                    </button>
                    @if($search || $docType)
                        <a href="{{ route('admissions.verification-queue') }}" class="btn btn-outline-secondary rounded-pill">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Main List Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clock-fill me-2 text-warning"></i> Documents Pending Verification</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bg-white">
                    <thead class="bg-light text-muted small text-uppercase fw-bold">
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Admission No</th>
                            <th>Class / Section</th>
                            <th>Document Type</th>
                            <th>File Name</th>
                            <th>Uploaded At</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $doc)
                            @php
                                $student = $doc->student;
                                $enrollment = $student?->currentEnrollment() ?? $student?->latestEnrollment();
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $student->student_name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-primary">{{ $student->admission_no ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    @if($enrollment)
                                        {{ $enrollment->classRoom->class_name }} / {{ $enrollment->section->section_name ?? 'N/A' }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-1">
                                        {{ $documentTypes[$doc->document_type] ?? $doc->document_type }}
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-muted" title="{{ $doc->file_name }}">{{ Str::limit($doc->file_name, 25) }}</span>
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $doc->uploaded_at ? $doc->uploaded_at->format('d M Y, g:i A') : 'N/A' }}</span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group">
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-light btn-sm rounded-circle border shadow-sm me-1" title="View Document">
                                            <i class="bi bi-eye text-primary"></i>
                                        </a>
                                        <a href="{{ asset('storage/' . $doc->file_path) }}" download class="btn btn-light btn-sm rounded-circle border shadow-sm me-1" title="Download Document">
                                            <i class="bi bi-download text-success"></i>
                                        </a>
                                        <button class="btn btn-light btn-sm rounded-circle border shadow-sm me-1" 
                                                onclick="verifyDocument('{{ $doc->document_id }}')" title="Verify Document">
                                            <i class="bi bi-check-lg text-success"></i>
                                        </button>
                                        <button class="btn btn-light btn-sm rounded-circle border shadow-sm" 
                                                onclick="openRejectModal('{{ $doc->document_id }}')" title="Reject Document">
                                            <i class="bi bi-x-lg text-danger"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-check fs-1 d-block mb-2 text-success"></i>
                                    <span class="fw-semibold">All caught up!</span>
                                    <p class="mb-0 mt-1 small text-muted">No documents are currently awaiting verification.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($documents->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Reject Document Modal -->
<div class="modal fade" id="rejectDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white py-3">
                <h6 class="modal-title fw-bold"><i class="bi bi-x-circle me-2"></i> Reject Document</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectDocForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="4" required placeholder="Describe why this document is rejected (e.g. blurry scan, incorrect information)..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-link text-muted text-decoration-none px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold rounded-pill shadow-sm">
                        <i class="bi bi-x-lg me-2"></i> Reject Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function openRejectModal(documentId) {
        const form = document.getElementById('rejectDocForm');
        form.action = `/admissions/documents/${documentId}/reject`;
        new bootstrap.Modal(document.getElementById('rejectDocModal')).show();
    }

    function verifyDocument(documentId) {
        Swal.fire({
            title: 'Verify Document',
            text: 'Are you sure you want to mark this document as verified?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, verify it'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/admissions/documents/${documentId}/verify`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Verified!',
                            text: 'Document has been verified successfully',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.error || 'Failed to verify document',
                            icon: 'error'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to verify document',
                        icon: 'error'
                    });
                });
            }
        });
    }
</script>
@endpush
@endsection
