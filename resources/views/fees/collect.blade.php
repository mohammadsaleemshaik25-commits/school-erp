@extends('fees.layout')

@section('title', 'Fee Collection')

@section('content')
@php
    $todayCollection = \App\Models\Payment::whereDate('payment_date', today())->where('status', '!=', 'CANCELLED')->sum('amount');
    $clerkReceipts = \App\Models\Payment::whereDate('payment_date', today())->where('collected_by', auth()->id())->count();
    $cancelledReceipts = \App\Models\Payment::whereDate('payment_date', today())->where('status', 'CANCELLED')->count();
    $totalPending = \App\Models\StudentFeeAccount::sum('total_due') - \App\Models\StudentFeeAccount::sum('total_paid');
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
            <span class="text-muted fw-normal">Ledger For:</span> {{ $account->student->student_name }}
        @else
            Fee Collection Desk
        @endif
    </h1>
    @if($account)
        <a href="{{ route('fees.collect') }}" class="btn btn-outline-secondary btn-sm shadow-sm px-3 rounded-pill">
            <i class="bi bi-arrow-left me-2"></i> Back to Search
        </a>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

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
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Admission No.</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-hash"></i></span>
                            <input type="text" name="admission_no" value="{{ request('admission_no') }}" placeholder="e.g. ADM-2026-01" class="form-control border-start-0 ps-0">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Student Name</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                            <input type="text" name="student_name" value="{{ request('student_name') }}" placeholder="e.g. Rahul" class="form-control border-start-0 ps-0">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Class</label>
                        <select name="class_id" class="form-select form-select-sm shadow-none">
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
                        <select name="section_id" class="form-select form-select-sm shadow-none">
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
                        <select name="academic_year_id" class="form-select form-select-sm shadow-none">
                            @php $academicYears = \App\Models\AcademicYear::orderBy('year_name', 'desc')->get(); @endphp
                            @foreach($academicYears as $year)
                                <option value="{{ $year->academic_year_id }}" {{ request('academic_year_id', \App\Models\AcademicYear::where('is_active', true)->first()?->academic_year_id) == $year->academic_year_id ? 'selected' : '' }}>
                                    {{ $year->year_name }} {{ $year->is_active ? '(Active)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                    <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i> Fill any field to start searching for student accounts.</p>
                    <button type="submit" class="btn btn-primary px-5 shadow-sm rounded-pill">
                        <i class="bi bi-search me-2"></i> Search Accounts
                    </button>
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
                            <th class="px-4 py-3">Student Details</th>
                            <th class="py-3">Parents</th>
                            <th class="py-3">Class/Section</th>
                            <th class="py-3 text-end">Outstanding Due</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($searchResults as $res)
                            <tr>
                                <td class="px-4">
                                    <div class="fw-bold text-dark">{{ $res->student->student_name }}</div>
                                    <div class="small text-muted font-monospace">{{ $res->student->admission_no }}</div>
                                </td>
                                <td>
                                    <div class="small"><span class="text-muted">F:</span> {{ $res->student->father_name ?? 'N/A' }}</div>
                                    <div class="small"><span class="text-muted">M:</span> {{ $res->student->mother_name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-normal">{{ optional($res->classRoom)->class_name ?? '-' }}</span>
                                    <span class="badge bg-light text-dark border fw-normal">{{ optional($res->section)->section_name ?? '-' }}</span>
                                </td>
                                <td class="text-end fw-bold text-primary font-monospace">₹{{ number_format($res->remaining_balance, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge rounded-pill py-1 px-3 
                                        {{ $res->status === 'PAID' ? 'bg-success-subtle text-success border border-success' : ($res->status === 'PARTIALLY_PAID' ? 'bg-warning-subtle text-warning border border-warning' : 'bg-danger-subtle text-danger border border-danger') }}">
                                        {{ $res->status }}
                                    </span>
                                </td>
                                <td class="px-4 text-end">
                                    <a href="{{ route('fees.collect', ['student_fee_account_id' => $res->id]) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm px-3">
                                        Collect Payment
                                    </a>
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
                            <i class="bi bi-journal-text text-primary"></i>
                        </div>
                        <h6 class="m-0 fw-bold text-dark">Ledger Statement</h6>
                    </div>
                    <span class="badge rounded-pill py-1 px-3 
                        {{ $account->status === 'PAID' ? 'bg-success-subtle text-success border border-success' : ($account->status === 'PARTIALLY_PAID' ? 'bg-warning-subtle text-warning border border-warning' : 'bg-danger-subtle text-danger border border-danger') }}">
                        {{ $account->status }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4 p-3 bg-light rounded-4">
                        <div class="col-sm-4 border-end">
                            <label class="small fw-bold text-muted text-uppercase d-block mb-1" style="font-size: 0.65rem;">Student Name</label>
                            <span class="h6 fw-bold text-dark mb-0">{{ $account->student->student_name }}</span>
                        </div>
                        <div class="col-sm-4 border-end">
                            <label class="small fw-bold text-muted text-uppercase d-block mb-1" style="font-size: 0.65rem;">Admission No.</label>
                            <span class="h6 fw-bold font-monospace text-dark mb-0">{{ $account->student->admission_no }}</span>
                        </div>
                        <div class="col-sm-4">
                            <label class="small fw-bold text-muted text-uppercase d-block mb-1" style="font-size: 0.65rem;">Class / Section</label>
                            <span class="h6 fw-bold text-dark mb-0">{{ optional($account->classRoom)->class_name }} / {{ optional($account->section)->section_name }}</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="bg-light text-center">
                                <tr class="small text-uppercase fw-bold text-muted">
                                    <th class="py-2">Fee Description</th>
                                    <th class="py-2 text-end px-3">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="px-3 py-3">Tuition Fee (Standard)</td>
                                    <td class="px-3 py-3 text-end font-monospace">₹{{ number_format($account->tuition_fee, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Books Fee Applied</span>
                                            <form action="{{ route('fees.books.update', $account->id) }}" method="POST" class="d-flex gap-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="number" name="books_fee_applied" value="{{ (int)$account->books_fee_applied }}" class="form-control form-control-sm py-0 px-2 text-end rounded-pill" style="width: 80px;">
                                                <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none fw-bold" style="font-size: 0.75rem;">Update</button>
                                            </form>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-end font-monospace">₹{{ number_format($account->books_fee_applied, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-3 text-muted italic">Previous Years Balance Carried</td>
                                    <td class="px-3 py-3 text-end font-monospace text-danger">₹{{ number_format($account->previous_balance_carried, 2) }}</td>
                                </tr>
                                <tr class="table-success border-success bg-opacity-10 text-success fw-bold">
                                    <td class="px-3 py-3">Approved Concessions (-)</td>
                                    <td class="px-3 py-3 text-end font-monospace">₹{{ number_format($account->concession_amount, 2) }}</td>
                                </tr>
                                <tr class="fw-bold bg-light">
                                    <td class="px-3 py-3 h6 mb-0">Total Due Outstanding</td>
                                    <td class="px-3 py-3 text-end font-monospace h6 mb-0">₹{{ number_format($account->total_due, 2) }}</td>
                                </tr>
                                <tr class="text-muted small">
                                    <td class="px-3 py-2">Total Fees Paid to Date</td>
                                    <td class="px-3 py-2 text-end font-monospace text-success">₹{{ number_format($account->total_paid, 2) }}</td>
                                </tr>
                                <tr class="border-top border-dark border-3">
                                    <td class="px-3 py-4 h5 fw-bold mb-0 text-uppercase" style="letter-spacing: 1px;">Net Balance Remaining</td>
                                    <td class="px-3 py-4 h4 fw-bold text-primary font-monospace mb-0 text-end">₹{{ number_format($account->remaining_balance, 2) }}</td>
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
                        <form action="{{ route('fees.payments.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="student_fee_account_id" value="{{ $account->id }}">

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Payment Amount (₹)</label>
                                <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden border">
                                    <span class="input-group-text bg-light border-0 fw-bold">₹</span>
                                    <input type="number" step="0.01" name="amount" max="{{ $account->remaining_balance }}" required
                                           value="{{ old('amount', $account->remaining_balance) }}"
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

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Books Purchased From School?</label>
                                <div class="d-flex gap-3">
                                    <div class="flex-fill">
                                        <input type="radio" class="btn-check" name="books_purchased" id="books_yes" value="yes" checked>
                                        <label class="btn btn-outline-secondary w-100 py-2 fw-semibold rounded-3" for="books_yes">Yes</label>
                                    </div>
                                    <div class="flex-fill">
                                        <input type="radio" class="btn-check" name="books_purchased" id="books_no" value="no">
                                        <label class="btn btn-outline-secondary w-100 py-2 fw-semibold rounded-3" for="books_no">No</label>
                                    </div>
                                </div>
                                <div class="form-text small opacity-50">This information is for record tracking only.</div>
                            </div>

                            <div class="mb-4 d-none" id="ref_container">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">UPI / Reference Number</label>
                                <input type="text" name="transaction_reference" id="transaction_reference"
                                       class="form-control rounded-3" placeholder="Enter Transaction ID">
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow rounded-4 mt-2">
                                <i class="bi bi-printer-fill me-2"></i> Process & Print Receipt
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
</script>
@endsection