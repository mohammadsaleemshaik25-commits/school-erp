@extends('fees.layout')

@section('title', 'Fee Collection - Student Workspace')

@section('content')
<div class="container-fluid py-4">
    <!-- Header with Student Info -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-4">
                        @if($student->photo_path)
                            <img src="{{ asset('storage/' . $student->photo_path) }}" alt="{{ $student->student_name }}" class="rounded-3" style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                                {{ strtoupper(substr($student->student_name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col">
                    <h4 class="fw-bold mb-1">{{ $student->student_name }}</h4>
                    <div class="row g-3 mt-2">
                        <div class="col-auto">
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-card-text me-1"></i>{{ $student->admission_no }}
                            </span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-info text-white">
                                <i class="bi bi-building me-1"></i>{{ $enrollment->classRoom->class_name }} - {{ $enrollment->section->section_name }}
                            </span>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-success text-white">
                                <i class="bi bi-calendar me-1"></i>{{ $enrollment->academicYear->year_name }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <a href="{{ route('fees-collection.payment', $feeAccount->account_id) }}" class="btn btn-primary rounded-pill px-4">
                            <i class="bi bi-cash-stack me-2"></i>Collect Payment
                        </a>
                        <a href="{{ route('fees-collection.ledger', $student->student_id) }}" class="btn btn-outline-dark rounded-pill px-4">
                            <i class="bi bi-journal-text me-2"></i>Student Ledger
                        </a>
                        <a href="{{ route('fees-collection.concession-request', $student->student_id) }}" class="btn btn-outline-info rounded-pill px-4">
                            <i class="bi bi-gift me-2"></i>Concession Request
                        </a>
                    </div>
                </div>
               <div class="col-auto">
    <div class="text-end">

        <div class="small text-muted">
            Tuition Due
        </div>

        <div class="fw-bold text-primary">
            ₹{{ number_format($tuitionSummary['balance'],2) }}
        </div>

        <div class="small text-muted mt-2">
            Books Due
        </div>

        <div class="fw-bold text-info">
            ₹{{ number_format($bookSummary['balance'],2) }}
        </div>

        <div class="small text-muted mt-2">
            Total Due
        </div>

        <div class="h3 fw-bold text-danger">
            ₹{{ number_format($feeAccount->total_due,2) }}
        </div>

    </div>
</div>
        </div>
    </div>

    <form id="paymentForm">
    @csrf
    <input type="hidden" name="account_id" value="{{ $feeAccount->account_id }}">
    <div class="row g-4" id="fee-workspace">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">

    <div class="card-header bg-white border-bottom py-3">
        <div class="d-flex align-items-center">
            <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                <i class="bi bi-mortarboard text-primary"></i>
            </div>

            <h6 class="m-0 fw-bold text-dark">
                A. Tuition Fee Summary
            </h6>
        </div>
    </div>

    <div class="card-body">

        <div class="card bg-light border-0 mb-3">
            <div class="card-body py-3">

                <div class="row text-center">

                    <div class="col">
                        <div class="small text-muted">Total</div>
                        <div class="fw-bold">
                            ₹{{ number_format($tuitionSummary['amount'],2) }}
                        </div>
                    </div>

                    <div class="col">
                        <div class="small text-muted">Concession</div>
                        <div class="fw-bold text-success">
                            ₹{{ number_format($tuitionSummary['concession'],2) }}
                        </div>
                    </div>

                    <div class="col">
                        <div class="small text-muted">Paid</div>
                        <div class="fw-bold text-primary">
                            ₹{{ number_format($tuitionSummary['paid'],2) }}
                        </div>
                    </div>

                    <div class="col">
                        <div class="small text-muted">Outstanding</div>
                        <div class="fw-bold text-danger">
                            ₹{{ number_format($tuitionSummary['balance'],2) }}
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                   <th>Term</th>
                                   <th class="text-end">Amount</th>
                                   <th class="text-end">Concession</th>
                                   <th class="text-end">Paid</th>
                                   <th class="text-end">Balance</th>
                                   <th class="text-center">Status</th>
                                </tr>
                            </thead>
                           <tbody>
    @foreach($tuitionComponents as $component)

        @php
            $account = $componentAccounts->get($component->component_id);

            $amount = $account ? $account->amount : 0;
            $concession = $account ? $account->concession_amount : 0;
            $paid = $account ? $account->paid_amount : 0;
            $balance = $account ? $account->balance_amount : 0;
            $status = $account ? $account->status : 'PENDING';
        @endphp

        <tr>
            <td>
                <div class="fw-bold">
                    {{ $component->component_name }}
                </div>

                <div class="small text-muted">
                    {{ $component->component_code }}
                </div>
            </td>

            <td class="text-end">
                ₹{{ number_format($amount, 2) }}
            </td>

            <td class="text-end text-info">
                ₹{{ number_format($concession, 2) }}
            </td>

            <td class="text-end text-success">
                ₹{{ number_format($paid, 2) }}
            </td>

            <td class="text-end text-danger fw-bold">
                ₹{{ number_format($balance, 2) }}
            </td>

            <td class="text-center">
                @if($status === 'PAID')
                    <span class="badge bg-success">
                        PAID
                    </span>

                @elseif($status === 'PARTIALLY_PAID')
                    <span class="badge bg-warning text-dark">
                        PARTIAL
                    </span>

                @else
                    <span class="badge bg-danger">
                        PENDING
                    </span>
                @endif
            </td>
        </tr>

    @endforeach
</tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="bg-info bg-opacity-10 p-2 rounded-3 me-3">
                                <i class="bi bi-book text-info"></i>
                            </div>
                            <h6 class="m-0 fw-bold text-dark">B. Book Fee</h6>
                            <small class="text-muted ms-3">(Checkbox based selection)</small>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm rounded-pill" onclick="saveBookSelections()">
                            <i class="bi bi-check-lg me-1"></i> Save Selections
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAllBooks" onchange="toggleAllBooks(this)">
                                    </th>
                                    <th>Component</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end text-danger">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bookComponents as $component)
                                    @php
                                        $account = $componentAccounts->get($component->component_id);
                                        $selection = $componentSelections->get($component->component_id);
                                        $isSelected = $selection !== null;
                                        $amount = $account ? $account->amount : 0;
                                        $paid = $account ? $account->paid_amount : 0;
                                        $balance = $account ? $account->balance_amount : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="book-checkbox" 
                                                   data-component-id="{{ $component->component_id }}"
                                                   data-amount="{{ $amount }}"
                                                   {{ $isSelected ? 'checked' : '' }}
                                                   onchange="updateBookSelection(this)">
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $component->component_name }}</div>
                                            <div class="small text-muted">{{ $component->component_code }}</div>
                                        </td>
                                        <td class="text-end">₹{{ number_format($amount, 2) }}</td>
                                        <td class="text-end text-danger fw-bold">₹{{ number_format($balance, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-tag text-warning"></i>
                        </div>
                        <h6 class="m-0 fw-bold text-dark">C. Other Fees</h6>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th>Component</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end text-danger">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($storeComponents as $component)
                                    @php
                                        $account = $componentAccounts->get($component->component_id);
                                        $amount = $account ? $account->amount : 0;
                                        $paid = $account ? $account->paid_amount : 0;
                                        $balance = $account ? $account->balance_amount : 0;
                                    @endphp
                                    @if($balance > 0)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $component->component_name }}</div>
                                            <div class="small text-muted">{{ $component->component_code }}</div>
                                        </td>
                                        <td class="text-end">₹{{ number_format($amount, 2) }}</td>
                                        <td class="text-end text-danger fw-bold">₹{{ number_format($balance, 2) }}</td>
                                    </tr>
                                    @endif
                                @endforeach
                                @foreach($tuitionComponents->whereIn('component_code', ['ADMISSION']) as $component)
                                    @php
                                        $account = $componentAccounts->get($component->component_id);
                                        $amount = $account ? $account->amount : 0;
                                        $paid = $account ? $account->paid_amount : 0;
                                        $balance = $account ? $account->balance_amount : 0;
                                    @endphp
                                    @if($balance > 0)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $component->component_name }}</div>
                                            <div class="small text-muted">{{ $component->component_code }}</div>
                                        </td>
                                        <td class="text-end">₹{{ number_format($amount, 2) }}</td>
                                        <td class="text-end text-danger fw-bold">₹{{ number_format($balance, 2) }}</td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-clock-history text-danger"></i>
                        </div>
                        <h6 class="m-0 fw-bold text-dark">D. Previous Balances</h6>
                        @if(!$canManagePreviousBalances)
                            <small class="text-muted ms-3">(Only Principal/Correspondent can manage)</small>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th>Component</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end text-danger">Balance</th>
                                    @if($canManagePreviousBalances)
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($carryForwardComponents as $component)
                                    @php
                                        $account = $componentAccounts->get($component->component_id);
                                        $amount = $account ? $account->amount : 0;
                                        $paid = $account ? $account->paid_amount : 0;
                                        $balance = $account ? $account->balance_amount : 0;
                                    @endphp
                                    @if($amount > 0)
<tr>
    <td>
        <div class="fw-bold">{{ $component->component_name }}</div>
        <div class="small text-muted">{{ $component->component_code }}</div>
    </td>

    <td class="text-end">
        ₹{{ number_format($amount, 2) }}
    </td>

    <td class="text-end text-danger fw-bold">
        ₹{{ number_format($balance, 2) }}
    </td>
</tr>
@endif
                                @endforeach
                                @if($carryForwardComponents->where(function($c) use ($componentAccounts) { 
                                    return $componentAccounts->get($c->component_id)?->amount > 0; 
                                })->count() === 0)
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="bi bi-check-circle text-success fs-4"></i>
                                                <div class="small mt-2">No previous balances</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 80px; z-index: 1;">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-cash text-success"></i>
                        </div>
                        <h6 class="m-0 fw-bold text-dark">Payment Details</h6>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
    <label class="form-label fw-bold">
        Tuition Payment
    </label>

    <div class="input-group">
        <span class="input-group-text">₹</span>

        <input type="number"
               class="form-control"
               id="tuition_payment"
               name="tuition_payment"
               min="0"
               step="0.01"
               value="0">
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-bold">
        Books Payment
    </label>

    <div class="input-group">
        <span class="input-group-text">₹</span>

        <input type="number"
               class="form-control"
               id="books_payment"
               name="books_payment"
               min="0"
               step="0.01"
               value="0">
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-bold">
        Other Fees Payment
    </label>

    <div class="input-group">
        <span class="input-group-text">₹</span>

        <input type="number"
               class="form-control"
               id="other_payment"
               name="other_payment"
               min="0"
               step="0.01"
               value="0">
    </div>
</div>

<div class="mb-4">
    <label class="form-label fw-bold">
        Previous Balance Payment
                </label>

                 <div class="input-group">
                      <span class="input-group-text">₹</span>
                      <input type="number"
                            class="form-control"
                            id="previous_payment"
                            name="previous_payment"
                            min="0"
                            step="0.01"
                            value="0">
                       </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Payment Mode</label>
                        <select class="form-select" name="payment_mode" required>
                            <option value="">Select Mode</option>
                            <option value="CASH">Cash</option>
                            <option value="UPI">UPI</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Transaction Reference</label>
                        <input type="text" class="form-control" name="transaction_reference" 
                               placeholder="Enter transaction ID (if applicable)">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Remarks (Optional)</label>
                        <textarea class="form-control" name="remarks" rows="2" 
                                  placeholder="Any additional notes..."></textarea>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Due</span>
                            <span class="fw-bold text-danger">₹{{ number_format($feeAccount->total_due, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Paying Now</span>
                            <span class="fw-bold text-primary" id="payingNow">₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Remaining After Payment</span>
                            <span class="fw-bold text-danger" id="remainingAfter">₹{{ number_format($feeAccount->total_due, 2) }}</span>
                        </div>
                    </div>

                    <button type="button" onclick="submitPayment()" 
                            class="btn btn-primary w-100 rounded-pill py-3" id="submitBtn" {{ $feeAccount->total_due <= 0 ? 'disabled' : '' }}>
                        <i class="bi bi-cash-stack me-2"></i> Collect Payment
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="m-0 fw-bold text-dark">Recent Payments</h6>
                </div>
                <div class="card-body">
                    @if($payments->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($payments->take(5) as $payment)
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold">₹{{ number_format($payment->amount, 2) }}</div>
                                            <div class="small text-muted">{{ $payment->payment_date->format('d M Y') }}</div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-success">{{ $payment->payment_mode }}</span>
                                            @if($payment->receipt)
                                                <a href="{{ route('fees.receipts.show', $payment->receipt->receipt_id) }}" 
                                                   class="btn btn-sm btn-link p-0 text-primary" target="_blank">
                                                    <i class="bi bi-receipt"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($payments->count() > 5)
                            <a href="{{ route('fees-collection.ledger', $student->student_id) }}" 
                               class="btn btn-link text-primary w-100 mt-2">
                                View all {{ $payments->count() }} payments
                            </a>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-cash-stack fs-4"></i>
                                <div class="small mt-2">No payments yet</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </form>

    <div class="mt-4">
        <a href="{{ route('fees-collection.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i> Back to Search
        </a>
        <a href="{{ route('fees.ledger', $feeAccount->account_id) }}" class="btn btn-outline-secondary rounded-pill px-4"> <i class="bi bi-journal-text me-2"></i> View Full Ledger </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleAllBooks(checkbox) {
    $('.book-checkbox').prop('checked', checkbox.checked).change();
}

function updateBookSelection(checkbox) {
    // Update visual feedback
    const row = $(checkbox).closest('tr');
    if (checkbox.checked) {
        row.addClass('table-primary');
    } else {
        row.removeClass('table-primary');
    }
}


window.submitPayment = function() {

    const formData = new FormData(document.getElementById('paymentForm'));

    const tuition = parseFloat(formData.get('tuition_payment')) || 0;
    const books = parseFloat(formData.get('books_payment')) || 0;
    const other = parseFloat(formData.get('other_payment')) || 0;
    const previous = parseFloat(formData.get('previous_payment')) || 0;

    const total =
        tuition +
        books +
        other +
        previous;

    if (total <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Amount',
            text: 'Enter at least one payment amount.'
        });
        return;
    }

    const paymentMode = formData.get('payment_mode');

    if (!paymentMode) {
        Swal.fire({
            icon: 'warning',
            title: 'Payment Mode Required',
            text: 'Please select payment mode.'
        });
        return;
    }

    if (
        paymentMode === 'UPI' &&
        !formData.get('transaction_reference')
    ) {
        Swal.fire({
            icon: 'warning',
            title: 'UPI Reference Required',
            text: 'Enter UPI transaction reference.'
        });
        return;
    }

    $('#submitBtn')
        .prop('disabled', true)
        .html(
            '<i class="bi bi-hourglass-split me-2"></i> Processing...'
        );

    $.ajax({
        url: '{{ route("fees-collection.collect-payment") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,

        success: function(response) {

            Swal.fire({
                icon: 'success',
                title: 'Payment Successful!',
                text: response.message,
                showDenyButton: true,
                confirmButtonText: 'View Receipt',
                denyButtonText: 'Back'
            }).then((result) => {

                if (
                    result.isConfirmed &&
                    response.receipt_id
                ) {
                   window.open(
                       '/receipts/' + response.receipt_id,
                      '_blank'
                    );
                }

                location.reload();
            });
        },

        error: function(xhr) {

            Swal.fire({
                icon: 'error',
                title: 'Payment Failed',
                text:
                    xhr.responseJSON?.message ||
                    'Error processing payment'
            });

            $('#submitBtn')
                .prop('disabled', false)
                .html(
                    '<i class="bi bi-cash-stack me-2"></i> Collect Payment'
                );
        }
    });
}

function saveBookSelections() {
    const selections = [];
    $('.book-checkbox').each(function() {
        selections.push({
            component_id: $(this).data('component-id'),
            selected: $(this).prop('checked'),
            amount: $(this).data('amount')
        });
    });

    const saveBtn = $('.btn-primary:contains("Save Selections")');
    const originalHtml = saveBtn.html();
    saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

    $.ajax({
        url: '{{ route("fees-collection.update-selections") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            enrollment_id: {{ $enrollment->enrollment_id }},
            selections: selections
        },
        success: function(response) {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: response.message,
                timer: 1500,
                showConfirmButton: false
            });
            setTimeout(() => location.reload(), 1500);
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: xhr.responseJSON?.message || 'Error saving selections'
            });
            saveBtn.prop('disabled', false).html(originalHtml);
        }
    });
}

function closePreviousBalance(accountId, componentId, action) {
    const actionText = action === 'close' ? 'close' : 'waive';
    
    Swal.fire({
        title: `Are you sure?`,
        text: `This will ${actionText} the previous balance. This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#dc3545',
        confirmButtonText: `Yes, ${actionText} it!`
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Reason',
                input: 'text',
                inputLabel: 'Please provide a reason',
                inputPlaceholder: 'Enter reason here...',
                showCancelButton: true,
                confirmButtonText: 'Submit',
                inputValidator: (value) => {
                    if (!value || value.length < 5) {
                        return 'Reason must be at least 5 characters';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("fees-collection.close-previous-balance") }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            account_id: accountId,
                            component_id: componentId,
                            action: action,
                            reason: result.value
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            setTimeout(() => location.reload(), 1500);
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'Error processing request'
                            });
                        }
                    });
                }
            });
        }
    });
}

// Initialize selected book rows
function updatePaymentSummary() {

    const tuition =
        parseFloat($('#tuition_payment').val()) || 0;

    const books =
        parseFloat($('#books_payment').val()) || 0;

    const other =
        parseFloat($('#other_payment').val()) || 0;

    const previous =
        parseFloat($('#previous_payment').val()) || 0;

    const total =
        tuition + books + other + previous;

    $('#payingNow').text('₹' + total.toFixed(2));

    $('#remainingAfter').text(
        '₹' +
        (
            {{ $feeAccount->total_due }} - total
        ).toFixed(2)
    );
}

$('#tuition_payment').on('input', updatePaymentSummary);
$('#books_payment').on('input', updatePaymentSummary);
$('#other_payment').on('input', updatePaymentSummary);
$('#previous_payment').on('input', updatePaymentSummary);

$(document).ready(function () {

    $('.book-checkbox:checked')
        .closest('tr')
        .addClass('table-primary');

    updatePaymentSummary();
});
</script>
@endpush
