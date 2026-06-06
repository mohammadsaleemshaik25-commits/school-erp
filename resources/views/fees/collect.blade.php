@extends('fees.layout')

@section('title', 'Fee Collection')

@section('content')
<div class="container-fluid">
@php
    $todayCollection = \App\Models\Payment::whereDate('payment_date', today())->where('status', '!=', 'CANCELLED')->sum('amount');
    $clerkReceipts = \App\Models\Payment::whereDate('payment_date', today())->where('collected_by', auth()->id())->count();
    $cancelledReceipts = \App\Models\Payment::whereDate('payment_date', today())->where('status', 'CANCELLED')->count();
    
    $totalDue = \App\Models\StudentFeeAccount::sum('total_due');
    $totalPaid = \App\Models\Payment::where('status', 'SUCCESS')->sum('amount');
    $totalPending = max(0.00, $totalDue - $totalPaid);
@endphp

<div class="mb-4">
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white h-100 overflow-hidden position-relative">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold opacity-75 mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Today's Collection</div>
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

<!-- Improved Student Search Panel -->
@if(!$account)
    <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                    <i class="bi bi-search text-primary"></i>
                </div>
                <h6 class="m-0 fw-bold text-dark">Search Student Ledger</h6>
            </div>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('fees.collect') }}" method="GET">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Universal Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, Adm No, Parent, Phone..." class="form-control border-start-0 ps-0 shadow-none">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Class</label>
                        <select name="class_id" class="form-select shadow-none">
                            <option value="">All Classes</option>
                            @foreach($classes as $cls)
                                <option value="{{ $cls->class_id }}" {{ request('class_id') == $cls->class_id ? 'selected' : '' }}>
                                    {{ $cls->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Section</label>
                        <select name="section_id" class="form-select shadow-none">
                            <option value="">All Sections</option>
                            @foreach($sections as $sec)
                                <option value="{{ $sec->section_id }}" {{ request('section_id') == $sec->section_id ? 'selected' : '' }}>
                                    {{ $sec->section_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Academic Year</label>
                        <select name="academic_year_id" class="form-select shadow-none">
                            @php $academicYears = \App\Models\AcademicYear::orderBy('year_name', 'desc')->get(); @endphp
                            @foreach($academicYears as $year)
                                <option value="{{ $year->academic_year_id }}" {{ request('academic_year_id', \App\Models\AcademicYear::where('is_active', true)->first()?->academic_year_id) == $year->academic_year_id ? 'selected' : '' }}>
                                    {{ $year->year_name }} {{ $year->is_active ? '(Active)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 shadow-sm rounded-pill py-2">
                            <i class="bi bi-filter me-2"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Render Search Results Card -->
    @if($searchResults)
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="m-0 fw-bold text-dark">Matching Student Records ({{ $searchResults->total() }})</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small text-uppercase fw-bold text-muted" style="letter-spacing: 0.5px;">
                            <th class="px-4 py-3">Student / Admission</th>
                            <th class="py-3">Parent / Contact</th>
                            <th class="py-3 text-center">Class / Section</th>
                            <th class="py-3 text-end">Outstanding Due</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($searchResults as $res)
                            <tr>
                                <td class="px-4">
                                    <div class="fw-bold text-dark">{{ $res->student?->student_name ?? 'N/A' }}</div>
                                    <div class="small text-muted font-monospace">{{ $res->student?->admission_no ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="small"><span class="text-muted">F:</span> {{ $res->student?->father_name ?? 'N/A' }}</div>
                                    <div class="small text-muted" style="font-size: 0.75rem;">
                                        <i class="bi bi-telephone me-1"></i>{{ $res->student?->phone_primary ?? '-' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-normal px-2">
                                        {{ optional($res->classRoom)->class_name ?? '-' }} - {{ optional($res->section)->section_name ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-danger font-monospace">₹{{ number_format($res->remaining_balance, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge rounded-pill py-1 px-3 
                                        {{ $res->status === 'PAID' ? 'bg-success-subtle text-success border border-success' : ($res->status === 'PARTIALLY_PAID' ? 'bg-warning-subtle text-warning border border-warning' : 'bg-danger-subtle text-danger border border-danger') }}">
                                        {{ $res->status }}
                                    </span>
                                </td>
                                <td class="px-4 text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('fees.collect', ['account_id' => $res->account_id]) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm px-3">
                                            Collect Fee
                                        </a>
                                        <button type="button" class="btn btn-warning btn-sm rounded-pill shadow-sm px-3 ms-1" 
                                                onclick="openConcessionModal({
                                                    account_id: '{{ $res->account_id }}',
                                                    student_name: '{{ addslashes($res->student->student_name) }}',
                                                    admission_no: '{{ $res->student->admission_no }}',
                                                    class_name: '{{ $res->classRoom->class_name }}',
                                                    tuition_fee: '{{ $res->final_tuition_fee }}',
                                                    current_due: '{{ $res->remaining_balance }}'
                                                })">
                                            Concession
                                        </button>
                                        <a href="{{ route('fees.ledger', $res->account_id) }}" class="btn btn-outline-secondary btn-sm rounded-pill shadow-sm px-3 ms-1">
                                            Ledger
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-search fs-1 d-block mb-3 opacity-25"></i>
                                    No student accounts matched your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($searchResults->hasPages())
                <div class="card-footer bg-white border-top py-3">
                    {{ $searchResults->links() }}
                </div>
            @endif
        </div>
    @endif
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

                    <h6 class="fw-bold text-muted text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 1px;">Fee Summary</h6>
                    <div class="table-responsive">
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
                                @if($account->previous_balance > 0)
                                <tr>
                                    <td class="px-3 py-3">Previous Session Balance</td>
                                    <td class="px-3 py-3 text-end font-monospace">₹{{ number_format($account->previous_balance, 2) }}</td>
                                </tr>
                                @endif
                                @if($account->discount_amount > 0 || $account->waived_amount > 0)
                                <tr class="text-success">
                                    <td class="px-3 py-3 italic">Total Discounts/Waivers</td>
                                    <td class="px-3 py-3 text-end font-monospace">-₹{{ number_format($account->discount_amount + $account->waived_amount, 2) }}</td>
                                </tr>
                                @endif
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
        const amount = form.querySelector('input[name="amount"]').value;
        
        if (!amount || amount <= 0) {
            Swal.fire('Error', 'Please enter a valid amount.', 'error');
            return;
        }

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
                // Disable button and show spinner
                btn.disabled = true;
                btn.querySelector('.spinner-border').classList.remove('d-none');
                btn.querySelector('.btn-icon').classList.add('d-none');
                btn.querySelector('.btn-text').innerText = 'Processing...';
                
                form.submit();
            }
        });
    }
</script>
</div>
@endsection