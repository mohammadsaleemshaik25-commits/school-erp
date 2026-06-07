@extends('fees.layout')

@section('title', 'Bulk Admission Import')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold text-dark">Bulk Admission Import</h1>
        <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
            <i class="bi bi-arrow-left me-2"></i> Back to Admissions
        </a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="m-0 fw-bold text-dark"><i class="bi bi-file-earmark-excel me-2 text-primary"></i> Upload Import File</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admissions.bulk.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Import File <span class="text-danger">*</span></label>
                            <input type="file" name="import_file" class="form-control" required accept=".csv,.txt,.xlsx,.xls">
                            <div class="form-text small">Supported formats: CSV, TXT, XLSX, XLS</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Photo Folder (Optional)</label>
                            <input type="file" name="photo_folder" webkitdirectory directory multiple class="form-control">
                            <div class="form-text small">Upload a folder containing student photos. Photos should be named to match the "Photo File Name" column in your import file.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm">
                                <i class="bi bi-upload me-2"></i> Upload & Validate
                            </button>
                            <a href="{{ route('admissions.bulk.template') }}" class="btn btn-outline-primary px-4 fw-bold rounded-pill shadow-sm">
                                <i class="bi bi-download me-2"></i> Download Template
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Import Instructions</h6>
                    <ol class="small opacity-75 mb-4 ps-3">
                        <li class="mb-2">Download the template CSV file</li>
                        <li class="mb-2">Fill in student data following the column format</li>
                        <li class="mb-2">Optional: Upload photo folder with student images</li>
                        <li class="mb-2">Upload the filled CSV file for validation</li>
                        <li class="mb-2">Review validation results in preview</li>
                        <li>Confirm import to create admissions</li>
                    </ol>
                    <div class="alert alert-light bg-opacity-25 border-0 rounded-3">
                        <small class="fw-bold"><i class="bi bi-info-circle me-1"></i> Note:</small>
                        <small class="d-block mt-1">All admissions will be created with "SUBMITTED" status and will need to go through the verification workflow.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
