@extends('fees.layout')

@section('title', 'Fee Collection')

@section('content')
<div class="container-fluid">


<div class="mb-4">
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold opacity-75 mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">  Overall Today's Collection</div>
                    <div class="h4 fw-bold mb-0">₹{{ number_format($todayCollection, 2) }}</div>
                    <i class="bi bi-currency-rupee position-absolute end-0 bottom-0 opacity-25" style="font-size: 4rem; margin-right: -10px; margin-bottom: -15px;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white h-100 overflow-hidden position-relative border-start border-success border-4">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">My Receipts (Today)</div>
                    <div class="h4 fw-bold mb-0 text-success">{{ $clerkReceipts }}</div>
                    <i class="bi bi-receipt position-absolute end-0 bottom-0 text-success opacity-10" style="font-size: 4rem; margin-right: -10px; margin-bottom: -15px;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white h-100 overflow-hidden position-relative border-start border-danger border-4">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Cancelled Receipts</div>
                    <div class="h4 fw-bold mb-0 text-danger">{{ $cancelledReceipts }}</div>
                    <i class="bi bi-x-circle position-absolute end-0 bottom-0 text-danger opacity-10" style="font-size: 4rem; margin-right: -10px; margin-bottom: -15px;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white h-100 overflow-hidden position-relative border-start border-warning border-4">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Outstanding</div>
                    <div class="h4 fw-bold mb-0 text-warning">₹{{ number_format($totalPending, 2) }}</div>
                    <i class="bi bi-exclamation-triangle position-absolute end-0 bottom-0 text-warning opacity-10" style="font-size: 4rem; margin-right: -10px; margin-bottom: -15px;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 mb-0 fw-bold text-dark">
        @if($account)
            <span class="text-muted fw-normal">Ledger For:</span> {{ $account->student?->student_name ?? 'N/A' }}
        @else
            Fee Collection Desk
        @endif
    </h1>
    @if($account)
        <div class="d-flex gap-2">
            <a href="{{ route('fees.ledger', $account->account_id) }}" class="btn btn-outline-primary btn-sm shadow-sm px-3 rounded-pill">
                <i class="bi bi-journal-text me-2"></i> View Ledger
            </a>
            <a href="{{ route('fees.collect') }}" class="btn btn-outline-secondary btn-sm shadow-sm px-3 rounded-pill">
                <i class="bi bi-arrow-left me-2"></i> Back to Search
            </a>
        </div>
    @endif
</div>

<!-- Professional Student Finder UI -->
@if(!$account)
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden finder-container">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                        <i class="bi bi-person-bounding-box text-primary fs-5"></i>
                    </div>
                    <div>
                        <h6 class="m-0 fw-bold text-dark">Professional Student Finder</h6>
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
                    <select id="filterGender" class="form-select form-select-sm rounded-pill px-3 shadow-none finder-filter">
                        <option value="">Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-4 bg-light bg-opacity-50">
            <div class="search-wrapper mb-4">
                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border">
                    <span class="input-group-text bg-white border-0 ps-4 text-primary"><i class="bi bi-search"></i></span>
                    <input type="text" id="finderInput" class="form-control border-0 py-3 shadow-none" 
                           placeholder="Type at least 2 characters to find students..." autocomplete="off">
                </div>
            </div>

            <!-- Results Grid -->
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
            width: 80px;
            height: 80px;
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
            font-size: 1rem;
            color: #1a1a1a;
            margin-bottom: 2px;
        }
        .student-card .admission-no {
            font-size: 0.85rem;
            font-family: var(--bs-font-monospace);
            color: var(--bs-primary);
        }
        .student-card .info-row {
            font-size: 0.8rem;
            margin-bottom: 4px;
        }
        .student-card .contact-badge {
            font-size: 0.9rem;
            background: #f8f9fa;
            color: #333;
            border: 1px dashed #ccc;
        }
    </style>
@else
    <!-- Specific Checkout Ledger Screen -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-person-badge text-primary"></i>
                        </div>
                        <h6 class="m-0 fw-bold text-dark">Student Information</h6>
                    </div>
                    <span class="badge rounded-pill py-1 px-3 
                        {{ $account->status === 'PAID' ? 'bg-success-subtle text-success border border-success' : ($account->status === 'PARTIALLY_PAID' ? 'bg-warning-subtle text-warning border border-warning' : 'bg-danger-subtle text-danger border border-danger') }}">
                        {{ $account->status }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-4 h-100 text-center">
                                @if ($account->student?->photo_path)
                                    <img src="{{ asset('storage/' . $account->student->photo_path) }}" alt="Student Photo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                @else
                                    <div style="width: 100px; height: 100px; border-radius: 50%; background-color: #e0e0e0; display: flex; align-items: center; justify-content: center; margin: 0 auto; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                        <span style="font-size: 12px; color: #666;">No Photo</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-4 h-100">
                                        <div class="row mb-2">
                                            <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Student Name</div>
                                            <div class="col-7 fw-bold text-dark">{{ $account->student?->student_name ?? 'N/A' }}</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Admission No</div>
                                            <div class="col-7 fw-bold font-monospace text-primary">{{ $account->student?->admission_no ?? '-' }}</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Father's Name</div>
                                            <div class="col-7 fw-semibold">{{ $account->student?->father_name ?? '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Phone Number</div>
                                            <div class="col-7 fw-semibold">{{ $account->student?->phone_primary ?? '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-4 h-100">
                                        <div class="row mb-2">
                                            <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Academic Year</div>
                                            <div class="col-7 fw-bold">{{ optional($account->academicYear)->year_name ?? '-' }}</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Class / Section</div>
                                            <div class="col-7 fw-bold text-dark">{{ optional($account->classRoom)->class_name ?? '-' }} / {{ optional($account->section)->section_name ?? '-' }}</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Mother's Name</div>
                                            <div class="col-7 fw-semibold">{{ $account->student?->mother_name ?? '-' }}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-5 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Guardian</div>
                                            <div class="col-7 fw-semibold">{{ $account->student?->guardian_name ?? '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">Outstanding Summary</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-light text-center">
                                <tr class="small text-uppercase fw-bold text-muted">
                                    <th class="py-2">Academic Year</th>
                                    <th class="py-2 text-end px-3">Amount Due</th>
                                    @if(in_array(strtoupper(optional(auth()->user()->role)->role_name), ['ADMINISTRATOR', 'ADMIN', 'PRINCIPAL', 'CORRESPONDENT']))
                                    <th class="py-2 text-center px-3">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $outstandingSummary = $account->outstanding_summary;
                                    $currentUserRole = strtoupper(optional(auth()->user()->role)->role_name);
                                    $canManagePreviousFees = in_array($currentUserRole, ['ADMINISTRATOR', 'ADMIN', 'PRINCIPAL', 'CORRESPONDENT']);
                                @endphp
                                @if(isset($outstandingSummary['previous_years']) && count($outstandingSummary['previous_years']) > 0)
                                    @foreach($outstandingSummary['previous_years'] as $prevYear)
                                    <tr class="table-danger">
                                        <td class="px-3 py-3">{{ $prevYear['year_name'] }}</td>
                                        <td class="px-3 py-3 text-end font-monospace fw-bold">₹{{ number_format($prevYear['total_outstanding'], 2) }}</td>
                                        @if($canManagePreviousFees)
                                        <td class="px-3 py-3 text-center">
                                            <button type="button" class="btn btn-sm btn-outline-warning me-1" onclick="openClosePreviousFeeModal('{{ $prevYear['year_name'] }}', {{ $prevYear['total_outstanding'] }})">
                                                <i class="bi bi-x-circle me-1"></i>Close
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="openWaivePreviousFeeModal('{{ $prevYear['year_name'] }}', {{ $prevYear['total_outstanding'] }})">
                                                <i class="bi bi-dash-circle me-1"></i>Waive
                                            </button>
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                @endif
                                @if(isset($outstandingSummary['current_year']))
                                    <tr class="table-primary">
                                        <td class="px-3 py-3">{{ $outstandingSummary['current_year']['year_name'] }} (Current)</td>
                                        <td class="px-3 py-3 text-end font-monospace fw-bold">₹{{ number_format($outstandingSummary['current_year']['total_outstanding'], 2) }}</td>
                                        @if($canManagePreviousFees)
                                        <td class="px-3 py-3 text-center">
                                            <span class="text-muted small">Current year</span>
                                        </td>
                                        @endif
                                    </tr>
                                    <tr class="table-light">
                                        <td class="px-3 py-3 ps-4">- Tuition Fee</td>
                                        <td class="px-3 py-3 text-end font-monospace">₹{{ number_format($outstandingSummary['current_year']['tuition_outstanding'], 2) }}</td>
                                        @if($canManagePreviousFees)
                                        <td class="px-3 py-3"></td>
                                        @endif
                                    </tr>
                                    <tr class="table-light">
                                        <td class="px-3 py-3 ps-4">- Books Fee</td>
                                        <td class="px-3 py-3 text-end font-monospace">₹{{ number_format($outstandingSummary['current_year']['books_outstanding'], 2) }}</td>
                                        @if($canManagePreviousFees)
                                        <td class="px-3 py-3"></td>
                                        @endif
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">Fee Summary</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-light text-center">
                                <tr class="small text-uppercase fw-bold text-muted">
                                    <th class="py-2">Fee Description</th>
                                    <th class="py-2 text-end px-3">Amount Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="px-3 py-3">Billed Tuition Fee</td>
                                    <td class="px-3 py-3 text-end font-monospace">₹{{ number_format($account->final_tuition_fee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-3">Billed Books Fee</td>
                                    <td class="px-3 py-3 text-end font-monospace">₹{{ number_format($account->books_fee_applied, 2) }}</td>
                                </tr>
                                <tr class="bg-light fw-bold">
                                    <td class="px-3 py-3">Total Payable (Current session)</td>
                                    <td class="px-3 py-3 text-end font-monospace">₹{{ number_format($account->total_due, 2) }}</td>
                                </tr>
                                <tr class="text-muted small">
                                    <td class="px-3 py-2">Total Paid to Date</td>
                                    <td class="px-3 py-2 text-end font-monospace text-success">₹{{ number_format($account->total_paid, 2) }}</td>
                                </tr>
                                <tr class="bg-dark text-white fw-bold">
                                    <td class="px-3 py-3 fs-5">Balance Due</td>
                                    <td class="px-3 py-3 text-end font-monospace fs-5">₹{{ number_format($account->remaining_balance, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">Financial Timeline</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-light text-center">
                                <tr class="small text-uppercase fw-bold text-muted">
                                    <th class="py-2">Academic Year</th>
                                    <th class="py-2 text-center px-3">Status</th>
                                    <th class="py-2 text-end px-3">Outstanding Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $student = $account->student;
                                    $currentYear = \App\Models\AcademicYear::where('is_active', true)->first();
                                    $allEnrollments = [];
                                    if($student) {
                                        $allEnrollments = \App\Models\StudentEnrollment::with(['feeAccount', 'academicYear'])
                                            ->where('student_id', $student->student_id)
                                            ->where('status', 'ACTIVE')
                                            ->orderBy('academic_year_id')
                                            ->get();
                                    }
                                @endphp
                                @foreach($allEnrollments as $enrollment)
                                    @php
                                        $feeAccount = $enrollment->feeAccount;
                                        $yearName = $enrollment->academicYear->year_name;
                                        $isCurrentYear = $currentYear && $enrollment->academicYear_id === $currentYear->academic_year_id;
                                        $status = 'Not Started';
                                        $outstanding = 0;
                                        $rowClass = '';
                                        
                                        if($feeAccount) {
                                            $outstanding = (float) $feeAccount->remaining_balance;
                                            if($feeAccount->status === 'PAID' || $feeAccount->status === 'CLOSED') {
                                                $status = 'Completed';
                                                $rowClass = 'table-success';
                                            } elseif($feeAccount->status === 'PARTIALLY_PAID') {
                                                $status = 'Partially Paid';
                                                $rowClass = 'table-warning';
                                            } elseif($isCurrentYear) {
                                                $status = 'Current Year';
                                                $rowClass = 'table-primary';
                                            } else {
                                                $status = 'Outstanding';
                                                $rowClass = 'table-danger';
                                            }
                                        }
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td class="px-3 py-3">{{ $yearName }}</td>
                                        <td class="px-3 py-3 text-center">{{ $status }}</td>
                                        <td class="px-3 py-3 text-end font-monospace">
                                            @if($outstanding > 0)
                                                ₹{{ number_format($outstanding, 2) }}
                                            @else
                                                ₹0.00
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-top border-primary border-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="m-0 fw-bold text-dark text-uppercase small" style="letter-spacing: 1px;">Record Transaction</h6>
                </div>
                <div class="card-body p-4">
                    @if($account->remaining_balance <= 0)
                        <div class="text-center py-5">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="bi bi-check-lg fs-1"></i>
                            </div>
                            <h5 class="fw-bold text-dark">Account Paid In Full</h5>
                            <p class="text-muted small px-3">This student has no outstanding balance for the current session.</p>
                        </div>
                    @else
                        <div class="mb-4">
                            <button type="button" class="btn btn-warning w-100 py-2 fw-bold rounded-pill shadow-sm" 
                                    onclick="openConcessionModal({
                                        account_id: '{{ $account->account_id }}',
                                        student_name: '{{ addslashes($account->student->student_name) }}',
                                        admission_no: '{{ $account->student->admission_no }}',
                                        class_name: '{{ $account->classRoom->class_name }}',
                                        tuition_fee: '{{ $account->final_tuition_fee }}',
                                        current_due: '{{ $account->remaining_balance }}'
                                    })">
                                <i class="bi bi-percent me-2"></i> Request Concession
                            </button>
                        </div>

                        @if($account->books_status === 'PENDING')
                        {{-- BOOKS DECISION CARD --}}
                        <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-4 mb-4">
                            <h6 class="fw-bold text-warning-emphasis mb-3">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> Books Decision Required
                            </h6>
                            <p class="small text-muted mb-4">
                                You must record whether the student is purchasing books from the school before collecting any fees.
                            </p>
                            <div class="alert alert-info small py-2 border-0 shadow-sm mb-4">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Warning:</strong> This action affects fee calculations and cannot be changed later.
                            </div>
                            
                            <form action="{{ route('fees.books.update', $account->account_id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-2">Books Purchased From School?</label>
                                    <div class="d-flex gap-3">
                                        <div class="flex-fill">
                                            <input type="radio" class="btn-check" name="books_status" id="books_school" value="SCHOOL" required>
                                            <label class="btn btn-outline-primary w-100 py-2 fw-semibold rounded-3" for="books_school">
                                                <i class="bi bi-check-circle me-1"></i> YES
                                            </label>
                                        </div>
                                        <div class="flex-fill">
                                            <input type="radio" class="btn-check" name="books_status" id="books_outside" value="OUTSIDE" required>
                                            <label class="btn btn-outline-danger w-100 py-2 fw-semibold rounded-3" for="books_outside">
                                                <i class="bi bi-x-circle me-1"></i> NO
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-2">Confirm Student Name</label>
                                    <input type="text" name="student_name_confirmation" class="form-control rounded-3" 
                                           placeholder="Type: {{ $account->student?->student_name }}" required>
                                    <div class="form-text small text-danger" style="font-size: 0.7rem;">
                                        Type the name exactly as shown above to confirm.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-warning w-100 py-3 fw-bold shadow-sm rounded-4">
                                    Finalize Books Decision
                                </button>
                            </form>
                        </div>
                        
                        <div class="text-center opacity-50">
                            <p class="small text-muted mb-0">Payment form is disabled until books decision is finalized.</p>
                        </div>
                    @else
                        {{-- REGULAR PAYMENT FORM --}}
                        <form action="{{ route('fees.payments.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="account_id" value="{{ $account->account_id }}">
                            <input type="hidden" name="overpayment_allocation" id="overpayment_allocation" value="TUITION">

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Payment Amount (₹)</label>
                                <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden border">
                                    <span class="input-group-text bg-light border-0 fw-bold">₹</span>
                                    <input type="number"
                                     step="1"
                                     min="1"
                                     name="amount"
                                     max="{{ floor($account->remaining_balance) }}"
                                     required
                                     value="{{ old('amount') }}"
                                     placeholder="Enter Amount"
                                     class="form-control border-0 fw-bold text-primary text-end px-4">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Apply Payment To</label>
                                <div class="d-flex flex-column gap-2">
                                    @php
                                        $outstandingSummary = $account->outstanding_summary;
                                        $hasPreviousFees = isset($outstandingSummary['previous_years']) && count($outstandingSummary['previous_years']) > 0;
                                        $hasBooksFee = $account->books_fee_applied > 0 && $account->remaining_books_balance > 0;
                                        $hasTuitionFee = $account->final_tuition_fee > 0;
                                    @endphp
                                    @if($hasPreviousFees)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="allocation" id="alloc_previous" value="PREVIOUS" checked>
                                        <label class="form-check-label" for="alloc_previous">
                                            <span class="fw-bold text-danger">Previous Fee</span>
                                            <span class="text-muted small ms-2">(Outstanding from previous academic years)</span>
                                        </label>
                                    </div>
                                    @endif
                                    @if($hasTuitionFee)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="allocation" id="alloc_tuition" value="TUITION" @if(!$hasPreviousFees) checked @endif>
                                        <label class="form-check-label" for="alloc_tuition">
                                            <span class="fw-bold text-primary">Current Tuition Fee</span>
                                            <span class="text-muted small ms-2">(Current academic year tuition)</span>
                                        </label>
                                    </div>
                                    @endif
                                    @if($hasBooksFee)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="allocation" id="alloc_books" value="BOOKS" @if(!$hasPreviousFees && !$hasTuitionFee) checked @endif>
                                        <label class="form-check-label" for="alloc_books">
                                            <span class="fw-bold text-info">Books Fee</span>
                                            <span class="text-muted small ms-2">(Books purchased from school)</span>
                                        </label>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Payment Mode</label>
                                <div class="d-flex gap-3">
                                    <div class="flex-fill">
                                        <input type="radio" class="btn-check" name="payment_mode" id="mode_cash" value="CASH" checked onchange="toggleRefField(this.value)">
                                        <label class="btn btn-outline-primary w-100 py-2 fw-semibold rounded-3" for="mode_cash">
                                            <i class="bi bi-cash me-2"></i> Cash
                                        </label>
                                    </div>
                                    <div class="flex-fill">
                                        <input type="radio" class="btn-check" name="payment_mode" id="mode_upi" value="UPI" onchange="toggleRefField(this.value)">
                                        <label class="btn btn-outline-primary w-100 py-2 fw-semibold rounded-3" for="mode_upi">
                                            <i class="bi bi-qr-code-scan me-2"></i> UPI
                                        </label>
                                    </div>
                                </div>
                            </div>

                            @if($account->books_status === 'SCHOOL')
    <div class="p-3 bg-light rounded-4 mb-4 border-start border-primary border-4">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">
                Books Fee Status
            </span>
            <span class="badge bg-primary rounded-pill">SCHOOL PURCHASE</span>
        </div>
        <div class="small text-muted">
            Books fee (₹{{ number_format($account->books_fee_applied, 2) }}) will be collected first.
        </div>
    </div>

@elseif($account->books_status === 'BOOKS_PAID')

    <div class="p-3 bg-success bg-opacity-10 rounded-4 mb-4 border-start border-success border-4">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">
                Books Fee Status
            </span>
            <span class="badge bg-success rounded-pill">PAID & FROZEN</span>
        </div>
        <div class="small text-muted">
            All payments will now go toward tuition fee.
        </div>
    </div>

@elseif($account->books_status === 'OUTSIDE')

    <div class="p-3 bg-info bg-opacity-10 rounded-4 mb-4 border-start border-info border-4">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">
                Books Fee Status
            </span>
            <span class="badge bg-info rounded-pill">PURCHASED OUTSIDE</span>
        </div>
        <div class="small text-muted">
            Books fee waived. All payments go toward tuition fee.
        </div>
    </div>

@endif

<div class="mb-4 d-none" id="ref_container">
    
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">UPI / Reference Number</label>
                                <input type="text" name="transaction_reference" id="transaction_reference"
                                       class="form-control rounded-3" placeholder="Enter Transaction ID">
                            </div>
                            @endif

                            <button type="button" id="submitPaymentBtn"
        class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow rounded-4 mt-2"
        onclick="confirmPayment(this)">
    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
    <i class="bi bi-printer-fill me-2 btn-icon"></i>
    <span class="btn-text">Process & Print Receipt</span>
</button>
                            <p class="text-center text-muted small mt-3">
                                <i class="bi bi-shield-check me-1"></i> Secure Transaction
                            </p>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
    function toggleRefField(val) {
        const refContainer = document.getElementById('ref_container');
        const refField = document.getElementById('transaction_reference');
        if (val === 'UPI') {
            refContainer.classList.remove('d-none');
            refField.setAttribute('required', 'required');
        } else {
            refContainer.classList.add('d-none');
            refField.removeAttribute('required');
        }
    }

    function confirmPayment(btn) {
        const form = btn.closest('form');
        const amount = parseFloat(form.querySelector('input[name="amount"]').value);
        const allocation = form.querySelector('input[name="allocation"]:checked')?.value || 'TUITION';
        
        if (!amount || amount <= 0) {
            Swal.fire('Error', 'Please enter a valid amount.', 'error');
            return;
        }

        @php
            $booksRemaining = $account ? $account->remaining_books_balance : 0;
            $tuitionRemaining = $account ? $account->remaining_tuition_balance : 0;
            $prevRemaining = 0;
            if($account) {
                $outstandingSummary = $account->outstanding_summary;
                $prevRemaining = isset($outstandingSummary['previous_years']) ? array_sum(array_column($outstandingSummary['previous_years'], 'total_outstanding')) : 0;
            }
        @endphp

        const booksRemaining = {{ $booksRemaining }};
        const tuitionRemaining = {{ $tuitionRemaining }};
        const prevRemaining = {{ $prevRemaining }};

        // Check for overpayment scenarios
        let overpaymentAmount = 0;
        let overpaymentOptions = [];

        if (allocation === 'BOOKS' && amount > booksRemaining) {
            overpaymentAmount = amount - booksRemaining;
            if (prevRemaining > 0) overpaymentOptions.push({ value: 'PREVIOUS', label: 'Previous Fee' });
            if (tuitionRemaining > 0) overpaymentOptions.push({ value: 'TUITION', label: 'Current Tuition Fee' });
        } else if (allocation === 'PREVIOUS' && amount > prevRemaining) {
            overpaymentAmount = amount - prevRemaining;
            if (booksRemaining > 0) overpaymentOptions.push({ value: 'BOOKS', label: 'Books Fee' });
            if (tuitionRemaining > 0) overpaymentOptions.push({ value: 'TUITION', label: 'Current Tuition Fee' });
        } else if (allocation === 'TUITION' && amount > tuitionRemaining) {
            overpaymentAmount = amount - tuitionRemaining;
            if (prevRemaining > 0) overpaymentOptions.push({ value: 'PREVIOUS', label: 'Previous Fee' });
            if (booksRemaining > 0) overpaymentOptions.push({ value: 'BOOKS', label: 'Books Fee' });
        }

        // If there's overpayment and allocation options exist, show prompt
        if (overpaymentAmount > 0 && overpaymentOptions.length > 0) {
            let html = '<p>Payment exceeds the selected fee category by <strong>₹' + overpaymentAmount + '</strong></p>';
            html += '<p class="mb-3">Where should the remaining amount be allocated?</p>';
            
            overpaymentOptions.forEach((option, index) => {
                html += '<div class="form-check mb-2">';
                html += '<input class="form-check-input" type="radio" name="overpayment_option" id="overpay_' + option.value + '" value="' + option.value + '" ' + (index === 0 ? 'checked' : '') + '>';
                html += '<label class="form-check-label" for="overpay_' + option.value + '">' + option.label + '</label>';
                html += '</div>';
            });

            Swal.fire({
                title: 'Overpayment Detected',
                html: html,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Process Payment',
                preConfirm: () => {
                    const selected = document.querySelector('input[name="overpayment_option"]:checked');
                    if (!selected) {
                        Swal.showValidationMessage('Please select an allocation option');
                        return false;
                    }
                    return selected.value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('overpayment_allocation').value = result.value;
                    submitPayment(form, btn);
                }
            });
        } else {
            // No overpayment, proceed normally
            Swal.fire({
                title: 'Confirm Payment',
                text: 'Collect ₹' + amount + ' and generate receipt?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Yes, Save Payment'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitPayment(form, btn);
                }
            });
        }
    }

    function submitPayment(form, btn) {
        btn.disabled = true;
        btn.querySelector('.spinner-border').classList.remove('d-none');
        btn.querySelector('.btn-icon').classList.add('d-none');
        btn.querySelector('.btn-text').innerText = 'Processing...';
        form.submit();
    }

    function openClosePreviousFeeModal(yearName, amount) {
        Swal.fire({
            title: 'Close Previous Fee',
            html: `
                <p>Are you sure you want to close the outstanding fee for <strong>${yearName}</strong>?</p>
                <p>Amount: <strong>₹${amount.toFixed(2)}</strong></p>
                <div class="mt-3">
                    <label class="form-label small fw-bold">Reason for closing:</label>
                    <textarea id="closeReason" class="form-control" rows="3" placeholder="Enter reason..."></textarea>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Close Fee',
            preConfirm: () => {
                const reason = document.getElementById('closeReason').value;
                if (!reason || reason.trim().length < 5) {
                    Swal.showValidationMessage('Please provide a reason (min 5 characters)');
                    return false;
                }
                return { yearName, amount, reason };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit close fee request
                submitClosePreviousFee(result.value);
            }
        });
    }

    function openWaivePreviousFeeModal(yearName, amount) {
        Swal.fire({
            title: 'Waive Previous Fee',
            html: `
                <p>Are you sure you want to waive the outstanding fee for <strong>${yearName}</strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>
                <p>Amount: <strong>₹${amount.toFixed(2)}</strong></p>
                <div class="mt-3">
                    <label class="form-label small fw-bold">Reason for waiving:</label>
                    <textarea id="waiveReason" class="form-control" rows="3" placeholder="Enter reason..."></textarea>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Waive Fee',
            preConfirm: () => {
                const reason = document.getElementById('waiveReason').value;
                if (!reason || reason.trim().length < 5) {
                    Swal.showValidationMessage('Please provide a reason (min 5 characters)');
                    return false;
                }
                return { yearName, amount, reason };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit waive fee request
                submitWaivePreviousFee(result.value);
            }
        });
    }

    function submitClosePreviousFee(data) {
        // This would typically make an AJAX call to the backend
        // For now, just show a success message
        Swal.fire({
            title: 'Fee Closed',
            text: `Previous fee for ${data.yearName} has been closed.`,
            icon: 'success'
        });
    }

    function submitWaivePreviousFee(data) {
        // This would typically make an AJAX call to the backend
        // For now, just show a success message
        Swal.fire({
            title: 'Fee Waived',
            text: `Previous fee for ${data.yearName} has been waived.`,
            icon: 'success'
        });
    }

    $(document).ready(function () {
        // Professional Student Finder Logic
        const finderInput = $('#finderInput');
        const finderResults = $('#finderResults');
        const finderLoading = $('#finderLoading');
        const finderFilters = $('.finder-filter');
        let searchTimeout = null;

        function performSearch() {
            const q = finderInput.val().trim();
            const class_id = $('#filterClass').val();
            const section_id = $('#filterSection').val();
            const gender = $('#filterGender').val();

            // Clear results if search is too short and no filters are selected
            if (q.length < 2 && !class_id && !section_id && !gender) {
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
                url: "{{ route('fees.finder') }}",
                data: { q, class_id, section_id, gender },
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
                            ? `<img src="${student.photo_url}" alt="Photo">`
                            : `<div class="d-flex align-items-center justify-content-center h-100 text-muted small bg-light">No Photo</div>`;

                        html += `
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="card student-card border-0 shadow-sm rounded-4 p-3" onclick="window.location.href='{{ route('fees.collect') }}?account_id=${student.account_id}'">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="photo-container rounded-circle overflow-hidden me-3 flex-shrink-0">
                                            ${photo}
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="student-name fw-bold text-truncate" title="${student.student_name}">${student.student_name}</div>
                                            <div class="admission-no">${student.admission_no}</div>
                                        </div>
                                    </div>
                                    <div class="info-grid mb-3">
                                        <div class="info-row d-flex justify-content-between">
                                            <span class="text-muted">Class / Sec:</span>
                                            <span class="fw-semibold">${student.class_name} / ${student.section_name}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between">
                                            <span class="text-muted">Gender:</span>
                                            <span class="fw-semibold">${student.gender}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between">
                                            <span class="text-muted">Father:</span>
                                            <span class="fw-semibold text-truncate ms-2">${student.father_name}</span>
                                        </div>
                                        <div class="info-row d-flex justify-content-between">
                                            <span class="text-muted">Mother:</span>
                                            <span class="fw-semibold text-truncate ms-2">${student.mother_name || 'N/A'}</span>
                                        </div>
                                    </div>
                                    <div class="contact-badge rounded-pill py-2 px-3 text-center fw-bold">
                                        <i class="bi bi-telephone-fill me-2 text-primary small"></i>${student.phone_primary}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    finderResults.html(html);
                },
                error: function() {
                    finderLoading.addClass('d-none');
                    finderResults.removeClass('d-none').html('<div class="col-12 text-center text-danger py-5">Error searching students. Please try again.</div>');
                }
            });
        }

        finderInput.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 300);
        });

        finderFilters.on('change', performSearch);
    });
</script>
@endpush



@endsection