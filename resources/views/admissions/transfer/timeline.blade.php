@extends('fees.layout')

@section('title', 'Transfer Timeline - ' . $student->student_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">Admissions</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Transfer Timeline</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 fw-bold text-dark">Transfer Timeline</h1>
        </div>
        <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
            <i class="bi bi-arrow-left me-2"></i> Back to List
        </a>
    </div>

    <!-- Student Summary Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-auto">
                    @if($student->photo_path)
                        <img src="{{ asset('storage/' . $student->photo_path) }}" class="rounded-circle" width="80" height="80" style="object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-person text-muted fs-3"></i>
                        </div>
                    @endif
                </div>
                <div class="col">
                    <h4 class="fw-bold mb-1">{{ $student->student_name }}</h4>
                    <p class="text-primary fw-semibold mb-1">{{ $student->admission_no }}</p>
                    <div class="small text-muted">
                        <i class="bi bi-telephone me-1"></i> {{ $student->phone_primary }}
                        @if($student->email)
                            <span class="mx-2">|</span>
                            <i class="bi bi-envelope me-1"></i> {{ $student->email }}
                        @endif
                    </div>
                </div>
                <div class="col-auto">
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3">
                        <i class="bi bi-arrow-left-right me-1"></i> {{ count($admissions) }} Transfer(s)
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Timeline -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> Academic History</h6>
        </div>
        <div class="card-body p-4">
            <div class="timeline position-relative">
                @foreach($admissions as $index => $admission)
                <div class="timeline-item position-relative pb-5 {{ $index < count($admissions) - 1 ? 'border-start border-2 border-primary ms-3' : '' }}">
                    <div class="timeline-marker position-absolute bg-primary rounded-circle border-4 border-white" style="width: 16px; height: 16px; left: -11px; top: 0;"></div>
                    <div class="ms-4">
                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-1">{{ $admission->academicYear->year_name }}</h6>
                                        <p class="text-muted small mb-0">
                                            Class: {{ $admission->classRoom->class_name }}
                                            @if($admission->section)
                                                / Section: {{ $admission->section->section_name }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge {{ $admission->admission_status === 'ADMITTED' ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill small">
                                            {{ $admission->admission_status }}
                                        </span>
                                        <div class="small text-muted mt-1">
                                            {{ $admission->created_at->format('d M Y') }}
                                        </div>
                                    </div>
                                </div>

                                @if($admission->transferred_from_admission_id)
                                <div class="alert alert-info bg-opacity-10 border-0 rounded-3 mb-0">
                                    <small class="fw-bold"><i class="bi bi-info-circle me-1"></i> Transferred from:</small>
                                    <small class="d-block mt-1">Admission #{{ $admission->transferred_from_admission_id }}</small>
                                </div>
                                @endif

                                @if($admission->remarks)
                                <div class="mt-2">
                                    <small class="text-muted"><strong>Remarks:</strong> {{ $admission->remarks }}</small>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Audit Logs -->
    @if($auditLogs->count() > 0)
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-journal-text me-2 text-primary"></i> Activity Log</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Date/Time</th>
                            <th>Action</th>
                            <th>User</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auditLogs as $log)
                        <tr>
                            <td class="ps-4">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill small">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td>{{ $log->user->username ?? 'System' }}</td>
                            <td class="small text-muted">
                                @if($log->old_value)
                                    <div class="mb-1"><strong>Before:</strong> {{ $log->old_value }}</div>
                                @endif
                                @if($log->new_value)
                                    <div><strong>After:</strong> {{ $log->new_value }}</div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
