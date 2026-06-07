@extends('fees.layout')

@section('title', 'Bulk Import Preview')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Import Preview</h1>
        <a href="{{ route('admissions.bulk.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-success border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">Valid Records</div>
                    <div class="h3 fw-bold mb-0 text-success">{{ count($results['valid']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-danger border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">Rejected Records</div>
                    <div class="h3 fw-bold mb-0 text-danger">{{ count($results['rejected']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 border-start border-warning border-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1">Warnings</div>
                    <div class="h3 fw-bold mb-0 text-warning">{{ count($results['warnings']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Valid Records Table -->
    @if(count($results['valid']) > 0)
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-check-circle me-2 text-success"></i> Valid Records ({{ count($results['valid']) }})</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Row</th>
                            <th>Student Name</th>
                            <th>Class/Section</th>
                            <th>Gender</th>
                            <th>Mobile</th>
                            <th>Aadhaar</th>
                            <th>Photo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['valid'] as $record)
                        <tr>
                            <td class="ps-4 fw-bold">{{ $record['row_index'] }}</td>
                            <td>{{ $record['student_name'] }}</td>
                            <td>{{ $record['class_name'] }} / {{ $record['section_name'] ?? 'N/A' }}</td>
                            <td>{{ $record['gender'] }}</td>
                            <td>{{ $record['phone_primary'] }}</td>
                            <td>{{ $record['aadhaar_no'] ?? '-' }}</td>
                            <td>
                                @if(!empty($record['photo_file_name']))
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill small">
                                        <i class="bi bi-image me-1"></i> {{ $record['photo_file_name'] }}
                                    </span>
                                @else
                                    <span class="text-muted small">No photo</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form action="{{ route('admissions.bulk.confirm') }}" method="POST">
        @csrf
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success px-4 fw-bold rounded-pill shadow-sm">
                <i class="bi bi-check-lg me-2"></i> Confirm Import ({{ count($results['valid']) }} records)
            </button>
            <a href="{{ route('admissions.bulk.index') }}" class="btn btn-outline-secondary px-4 fw-bold rounded-pill shadow-sm">
                Cancel
            </a>
        </div>
    </form>
    @endif

    <!-- Rejected Records Table -->
    @if(count($results['rejected']) > 0)
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="m-0 fw-bold text-dark"><i class="bi bi-x-circle me-2 text-danger"></i> Rejected Records ({{ count($results['rejected']) }})</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Row</th>
                            <th>Student Name</th>
                            <th>Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results['rejected'] as $record)
                        <tr>
                            <td class="ps-4 fw-bold text-danger">{{ $record['row_index'] }}</td>
                            <td>{{ $record['student_name'] }}</td>
                            <td>
                                @foreach($record['errors'] as $error)
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill small me-1">
                                        {{ $error }}
                                    </span>
                                @endforeach
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
