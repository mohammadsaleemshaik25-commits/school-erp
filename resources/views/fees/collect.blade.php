@extends('fees.layout')

@section('title', 'Fee Collection')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Stats Section -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white overflow-hidden">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold opacity-75 mb-1" style="font-size: 0.7rem;">Today's Collection</div>
                    <div class="h4 fw-bold mb-0">₹{{ number_format($todayCollection, 2) }}</div>
                    <i class="bi bi-currency-rupee position-absolute end-0 bottom-0 opacity-25" style="font-size: 3rem; margin-right: -5px; margin-bottom: -10px;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-success border-4">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1" style="font-size: 0.7rem;">Receipts Issued</div>
                    <div class="h4 fw-bold mb-0 text-success">{{ $clerkReceipts }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-danger border-4">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1" style="font-size: 0.7rem;">Cancelled</div>
                    <div class="h4 fw-bold mb-0 text-danger">{{ $cancelledReceipts }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-white border-start border-warning border-4">
                <div class="card-body p-3">
                    <div class="small text-uppercase fw-bold text-muted mb-1" style="font-size: 0.7rem;">Outstanding Balance</div>
                    <div class="h4 fw-bold mb-0 text-warning">₹{{ number_format($totalPending, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0 fw-bold text-dark">
            @if($account)
                <span class="text-muted fw-normal">Fee Collection for:</span> {{ $account->student?->student_name ?? 'N/A' }}
            @else
                Fee Collection Desk
            @endif
        </h1>
        @if($account)
            <div class="d-flex gap-2">
                <a href="{{ route('fees.ledger', $account->account_id) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-journal-text me-2"></i> View Ledger
                </a>
                <a href="{{ route('fees.collect') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-2"></i> Back to Search
                </a>
            </div>
        @endif
    </div>

    @if(!$account)
        <!-- Student Finder Interface -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h6 class="m-0 fw-bold">Professional Student Finder</h6>
                        <small class="text-muted">Search by Name, Admission No, or Parent Name</small>
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <select id="filterClass" class="form-select form-select-sm rounded-pill">
                            <option value="">All Classes</option>
                            @foreach($classes as $cls)
                                <option value="{{ $cls->class_id }}">{{ $cls->class_name }}</option>
                            @endforeach
                        </select>
                        <select id="filterSection" class="form-select form-select-sm rounded-pill">
                            <option value="">All Sections</option>
                            @foreach($sections as $sec)
                                <option value="{{ $sec->section_id }}">{{ $sec->section_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body p-4 bg-light bg-opacity-50">
                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border mb-4">
                    <span class="input-group-text bg-white border-0 ps-4 text-primary"><i class="bi bi-search"></i></span>
                    <input type="text" id="finderInput" class="form-control border-0 py-3 shadow-none" 
                           placeholder="Type student name or admission number..." autocomplete="off">
                </div>

                <div id="finderResults" class="row g-4">
                    <div class="col-12 text-center py-5">
                        <div class="opacity-50 mb-3"><i class="bi bi-search" style="font-size: 3rem;"></i></div>
                        <h5 class="text-muted fw-normal">Start typing to find students.</h5>
                    </div>
                </div>

                <div id="finderLoading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Searching students...</p>
                </div>
            </div>
        </div>
    @else
        <!-- Checkout Ledger Interface -->
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Fee Components Table -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                                <i class="bi bi-list-check text-primary"></i>
                            </div>
                            <h6 class="m-0 fw-bold text-dark">Fee Component Details</h6>
                        </div>
                        <span class="badge rounded-pill bg-info-subtle text-info border border-info px-3">
                            {{ $account->academicYear->year_name }}
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="componentsTable">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4">Component</th>
                                        <th class="text-end">Actual Fee</th>
                                        <th class="text-end text-success">Concession</th>
                                        <th class="text-end text-info">Paid</th>
                                        <th class="text-end text-danger">Balance</th>
                                        <th class="text-center pe-4" style="width: 150px;">Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalBalance = 0; @endphp
                                    @foreach($componentAccounts as $comp)
                                        @php $totalBalance += $comp->balance_amount; @endphp
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <div class="fw-bold text-dark">{{ $comp->component->component_name }}</div>
                                                        <div class="small text-muted">{{ $comp->component->category }}</div>
                                                    </div>
                                                    @if($comp->balance_amount > 0)
                                                         <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary" 
                                                                 onclick="openConcessionModal({{ $comp->component_id }}, '{{ $comp->component->component_name }}', {{ $comp->balance_amount }})">
                                                             <i class="bi bi-gift me-1"></i>Concession
                                                         </button>
                                                     @endif
                                                </div>
                                            </td>
                                            <td class="text-end">₹{{ number_format($comp->amount, 0) }}</td>
                                            <td class="text-end text-success">
                                                @if($comp->concession_amount > 0 || $comp->waiver_amount > 0)
                                                    ₹{{ number_format($comp->concession_amount + $comp->waiver_amount, 0) }}
                                                    <i class="bi bi-info-circle ms-1" data-bs-toggle="tooltip" 
                                                       title="Concession: ₹{{ $comp->concession_amount }}, Waiver: ₹{{ $comp->waiver_amount }}"></i>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end text-info">₹{{ number_format($comp->paid_amount, 0) }}</td>
                                            <td class="text-end text-danger fw-bold">₹{{ number_format($comp->balance_amount, 0) }}</td>
                                            <td class="pe-4">
                                                @if($comp->balance_amount > 0)
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-white border-end-0">₹</span>
                                                        <input type="number" 
                                                               class="form-control border-start-0 text-end component-payment-input" 
                                                               data-id="{{ $comp->id }}" 
                                                               data-balance="{{ $comp->balance_amount }}"
                                                               max="{{ $comp->balance_amount }}"
                                                               placeholder="0">
                                                    </div>
                                                @else
                                                    <div class="text-center text-success"><i class="bi bi-check-circle-fill"></i> PAID</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light fw-bold">
                                    <tr>
                                        <td class="ps-4">TOTAL OUTSTANDING</td>
                                        <td colspan="3"></td>
                                        <td class="text-end text-danger">₹{{ number_format($totalBalance, 0) }}</td>
                                        <td class="pe-4">
                                            <div id="totalPaymentDisplay" class="text-end text-primary">₹0</div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Previous Year Dues (If any) -->
                @if($previousAccounts->count() > 0)
                    <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-danger border-4">
                        <div class="card-body">
                            <h6 class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>Previous Year Dues Detected</h6>
                            @foreach($previousAccounts as $prevAcc)
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-danger bg-opacity-10 rounded-3">
                                    <div>
                                        <span class="fw-bold">{{ $prevAcc->academicYear->year_name }}</span>
                                        <span class="text-muted ms-2">({{ $prevAcc->classRoom->class_name }})</span>
                                    </div>
                                    <div class="text-danger fw-bold">₹{{ number_format($prevAcc->remaining_balance, 0) }}</div>
                                </div>
                            @endforeach
                            <div class="small text-muted mt-2">Note: These dues should be cleared or moved to 'CARRY FORWARD' component for current year tracking.</div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <!-- Payment Action Card -->
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="m-0 fw-bold">Collect Payment</h6>
                    </div>
                    <div class="card-body">
                        <form id="paymentForm" action="{{ route('fees.payments.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="account_id" value="{{ $account->account_id }}">
                            <input type="hidden" name="amount" id="totalAmountInput" value="0">
                            <div id="allocationsContainer"></div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Total Payment Amount</label>
                                <div class="display-4 fw-bold text-primary mb-0" id="summaryTotalDisplay">₹0</div>
                                <div class="text-muted small" id="amountInWords">Zero Rupees Only</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Payment Mode</label>
                                <div class="d-flex gap-2">
                                    <input type="radio" class="btn-check" name="payment_mode" id="modeCash" value="CASH" checked>
                                    <label class="btn btn-outline-primary flex-grow-1 rounded-3" for="modeCash">
                                        <i class="bi bi-cash-stack d-block mb-1"></i> Cash
                                    </label>
                                    <input type="radio" class="btn-check" name="payment_mode" id="modeOnline" value="ONLINE">
                                    <label class="btn btn-outline-primary flex-grow-1 rounded-3" for="modeOnline">
                                        <i class="bi bi-upc-scan d-block mb-1"></i> Online
                                    </label>
                                    <input type="radio" class="btn-check" name="payment_mode" id="modeCheque" value="CHEQUE">
                                    <label class="btn btn-outline-primary flex-grow-1 rounded-3" for="modeCheque">
                                        <i class="bi bi-bank d-block mb-1"></i> Cheque
                                    </label>
                                </div>
                            </div>

                            <div id="transactionRefGroup" class="mb-3 d-none">
                                <label class="form-label small fw-bold text-muted text-uppercase">Reference / Transaction ID</label>
                                <input type="text" name="transaction_reference" class="form-control rounded-3" placeholder="UTR / Reference Number">
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase">Remarks (Optional)</label>
                                <textarea name="remarks" class="form-control rounded-3" rows="2" placeholder="Any notes about this payment..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-sm" id="submitBtn" disabled>
                                <i class="bi bi-check2-circle me-2"></i> COMPLETE PAYMENT
                            </button>
                        </form>
                    </div>
                    <div class="card-footer bg-light border-0 py-3 rounded-bottom-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img src="{{ $account->student?->photo_path ? asset('storage/'.$account->student->photo_path) : 'https://ui-avatars.com/api/?name='.urlencode($account->student->student_name).'&background=random' }}" 
                                     class="rounded-circle" width="45" height="45" style="object-fit: cover;">
                            </div>
                            <div class="ms-3 overflow-hidden">
                                <div class="fw-bold text-truncate">{{ $account->student->student_name }}</div>
                                <div class="small text-muted">ADM: {{ $account->student->admission_no }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Concession Request Modal -->
<div class="modal fade" id="concessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-0 py-3">
                <h6 class="modal-title fw-bold text-dark"><i class="bi bi-gift me-2 text-primary"></i> Request Concession</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('fees.adjustments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="account_id" value="{{ $account->account_id ?? '' }}">
                <input type="hidden" name="component_id" id="concession_component_id">
                <input type="hidden" name="adjustment_type" value="CONCESSION">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Component</label>
                        <div class="h6 fw-bold text-dark mb-0" id="concession_component_name"></div>
                        <div class="small text-muted">Outstanding Balance: ₹<span id="concession_balance"></span></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Discount Amount (₹)</label>
                            <input type="number" name="discount_amount" class="form-control rounded-3" placeholder="Enter Amount" id="concession_amount_input">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Or Discount (%)</label>
                            <input type="number" name="discount_percent" class="form-control rounded-3" placeholder="Enter %" id="concession_percent_input">
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-muted text-uppercase">Reason for Concession</label>
                        <textarea name="reason" class="form-control rounded-3" rows="3" required placeholder="e.g. Sibling discount, Staff child, Merit-based..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-link text-muted text-decoration-none px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    .component-payment-input:focus { border-color: var(--bs-primary); box-shadow: none; }
    .student-card { transition: all 0.2s; cursor: pointer; }
    .student-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.component-payment-input');
    const totalDisplay = document.getElementById('totalPaymentDisplay');
    const summaryTotalDisplay = document.getElementById('summaryTotalDisplay');
    const totalAmountInput = document.getElementById('totalAmountInput');
    const allocationsContainer = document.getElementById('allocationsContainer');
    const submitBtn = document.getElementById('submitBtn');
    const amountInWords = document.getElementById('amountInWords');

    // Payment Mode Toggle Logic
    const modeRadios = document.querySelectorAll('input[name="payment_mode"]');
    const refGroup = document.getElementById('transactionRefGroup');
    modeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'ONLINE' || this.value === 'CHEQUE') {
                refGroup.classList.remove('d-none');
            } else {
                refGroup.classList.add('d-none');
            }
        });
    });

    function updateTotals() {
        let total = 0;
        allocationsContainer.innerHTML = '';
        
        inputs.forEach(input => {
            const val = parseFloat(input.value) || 0;
            if (val > 0) {
                total += val;
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = `allocations[${input.dataset.id}]`;
                hiddenInput.value = val;
                allocationsContainer.appendChild(hiddenInput);
            }
        });

        const formatted = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(total);
        totalDisplay.textContent = formatted;
        summaryTotalDisplay.textContent = formatted;
        totalAmountInput.value = total;
        
        submitBtn.disabled = total <= 0;
        amountInWords.textContent = total > 0 ? numberToWords(total) + " Rupees Only" : "Zero Rupees Only";
    }

    // Modal Helper
    window.openConcessionModal = function(id, name, balance) {
        document.getElementById('concession_component_id').value = id;
        document.getElementById('concession_component_name').textContent = name;
        document.getElementById('concession_balance').textContent = balance;
        new bootstrap.Modal(document.getElementById('concessionModal')).show();
    };

    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const balance = parseFloat(this.dataset.balance);
            if (parseFloat(this.value) > balance) {
                this.value = balance;
            }
            updateTotals();
        });
    });

    // Student Finder Logic
    const finderInput = document.getElementById('finderInput');
    const finderResults = document.getElementById('finderResults');
    const finderLoading = document.getElementById('finderLoading');
    let finderTimeout = null;

    if (finderInput) {
        finderInput.addEventListener('input', function() {
            clearTimeout(finderTimeout);
            const query = this.value.trim();
            if (query.length < 2) {
                finderResults.innerHTML = '<div class="col-12 text-center py-5"><div class="opacity-50 mb-3"><i class="bi bi-search" style="font-size: 3rem;"></i></div><h5 class="text-muted fw-normal">Start typing to find students.</h5></div>';
                return;
            }

            finderTimeout = setTimeout(() => {
                finderLoading.classList.remove('d-none');
                finderResults.classList.add('opacity-50');

                const classId = document.getElementById('filterClass').value;
                const sectionId = document.getElementById('filterSection').value;

                fetch(`{{ route('fees.search.students') }}?query=${encodeURIComponent(query)}&class_id=${classId}&section_id=${sectionId}`)
                    .then(res => res.json())
                    .then(data => {
                        finderLoading.classList.add('d-none');
                        finderResults.classList.remove('opacity-50');
                        
                        if (data.length === 0) {
                            finderResults.innerHTML = '<div class="col-12 text-center py-5"><h5 class="text-muted fw-normal">No students found matching your search.</h5></div>';
                            return;
                        }

                        let html = '';
                        data.forEach(student => {
                            const photo = student.photo_path ? `/storage/${student.photo_path}` : `https://ui-avatars.com/api/?name=${encodeURIComponent(student.student_name)}&background=random`;
                            html += `
                                <div class="col-md-6 col-xl-4">
                                    <div class="card border-0 shadow-sm rounded-4 student-card h-100" onclick="window.location.href='{{ route('fees.collect') }}?account_id=${student.account_id}'">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center">
                                                <img src="${photo}" class="rounded-3 shadow-sm me-3" width="60" height="60" style="object-fit: cover;">
                                                <div class="overflow-hidden">
                                                    <h6 class="mb-0 fw-bold text-dark text-truncate">${student.student_name}</h6>
                                                    <div class="small text-primary fw-bold font-monospace">${student.admission_no}</div>
                                                    <div class="small text-muted">${student.class_name} - ${student.section_name}</div>
                                                </div>
                                            </div>
                                            <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                                                <div class="small text-muted">Parent: ${student.father_name || student.mother_name || 'N/A'}</div>
                                                <div class="badge bg-warning-subtle text-warning border border-warning rounded-pill">₹${student.balance}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        finderResults.innerHTML = html;
                    });
            }, 300);
        });
    }

    function numberToWords(num) {
        const a = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        
        if ((num = num.toString()).length > 9) return 'Too large';
        let n = ('000000000' + num).substr(-9).match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);
        if (!n) return '';
        let str = '';
        str += (n[1] != 0) ? (a[Number(n[1])] || b[n[1][0]] + ' ' + a[n[1][1]]) + ' Crore ' : '';
        str += (n[2] != 0) ? (a[Number(n[2])] || b[n[2][0]] + ' ' + a[n[2][1]]) + ' Lakh ' : '';
        str += (n[3] != 0) ? (a[Number(n[3])] || b[n[3][0]] + ' ' + a[n[3][1]]) + ' Thousand ' : '';
        str += (n[4] != 0) ? (a[Number(n[4])] || b[n[4][0]] + ' ' + a[n[4][1]]) + ' Hundred ' : '';
        str += (n[5] != 0) ? ((str != '') ? 'and ' : '') + (a[Number(n[5])] || b[n[5][0]] + ' ' + a[n[5][1]]) : '';
        return str;
    }
});
</script>
@endpush
@endsection
