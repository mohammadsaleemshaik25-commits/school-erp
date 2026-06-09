@extends('fees.layout')

@section('title', 'Fee Collection - Collect Payment')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0 fw-bold text-dark">
            <i class="bi bi-cash-stack me-2 text-primary"></i>Collect Payment
        </h1>
        <a href="{{ route('fees-collection.workspace', $feeAccount->enrollment->student_id) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-2"></i> Back to Workspace
        </a>
    </div>

    <!-- Student Info Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    @if($feeAccount->enrollment->student->photo_path)
                        <img src="{{ asset('storage/' . $feeAccount->enrollment->student->photo_path) }}" 
                             alt="{{ $feeAccount->enrollment->student->student_name }}" 
                             class="rounded-3" style="width: 60px; height: 60px; object-fit: cover;">
                    @else
                        <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" 
                             style="width: 60px; height: 60px; font-size: 1.5rem;">
                            {{ strtoupper(substr($feeAccount->enrollment->student->student_name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="col">
                    <h5 class="fw-bold mb-1">{{ $feeAccount->enrollment->student->student_name }}</h5>
                    <div class="small text-muted">
                        {{ $feeAccount->enrollment->student->admission_no }} | 
                        {{ $feeAccount->enrollment->classRoom->class_name }} - {{ $feeAccount->enrollment->section->section_name }}
                    </div>
                </div>
                <div class="col-auto text-end">
                    <div class="small text-muted">Total Due</div>
                    <div class="h4 fw-bold text-danger">₹{{ number_format($totalDue, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column - Component Selection -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-list-check text-primary"></i>
                        </div>
                        <h6 class="m-0 fw-bold text-dark">Fee Components (Partial Payment)</h6>
                    </div>
                </div>
                <div class="card-body">
                    <form id="paymentForm">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $feeAccount->account_id }}">
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAllComponents" onchange="toggleAllComponents(this)">
                                        </th>
                                        <th>Component</th>
                                        <th class="text-end">Balance</th>
                                        <th class="text-end">Pay Now</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($componentAccounts as $component)
                                        @if($component->balance_amount > 0)
                                        <tr class="component-row" data-balance="{{ $component->balance_amount }}">
                                            <td>
                                                <input type="checkbox" class="component-checkbox" 
                                                       data-id="{{ $component->id }}"
                                                       onchange="toggleComponent(this)">
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $component->component->component_name }}</div>
                                                <div class="small text-muted">{{ $component->component->category }}</div>
                                            </td>
                                            <td class="text-end text-danger fw-bold">₹{{ number_format($component->balance_amount, 2) }}</td>
                                            <td class="text-end">
                                                <input type="number" 
                                                       class="form-control form-control-sm text-end payment-amount" 
                                                       name="component_allocations[{{ $component->id }}][amount]"
                                                       data-component-account-id="{{ $component->id }}"
                                                       data-max-amount="{{ $component->balance_amount }}"
                                                       min="0" 
                                                       step="0.01"
                                                       value="{{ $component->balance_amount }}"
                                                       readonly>
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column - Payment Details -->
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
                    <div class="mb-4">
                        <label class="form-label fw-bold">Amount to Pay</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">₹</span>
                            <input type="number" id="totalAmount" class="form-control" 
                                   name="amount" min="0" step="0.01" required>
                        </div>
                        <div class="small text-muted mt-1">
                            Selected components: <span id="selectedCount">0</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Payment Mode</label>
                        <select class="form-select" name="payment_mode" required>
                            <option value="">Select Mode</option>
                            <option value="CASH">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="NEFT">NEFT/RTGS</option>
                            <option value="CHEQUE">Cheque</option>
                            <option value="CARD">Card</option>
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
                            <span class="fw-bold">₹{{ number_format($totalDue, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Paying Now</span>
                            <span class="fw-bold text-primary" id="payingNow">₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Remaining After Payment</span>
                            <span class="fw-bold text-danger" id="remainingAfter">₹{{ number_format($totalDue, 2) }}</span>
                        </div>
                    </div>

                    <button type="button" onclick="submitPayment()" 
                            class="btn btn-primary w-100 rounded-pill py-3" id="submitBtn">
                        <i class="bi bi-cash-stack me-2"></i> Collect Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleAllComponents(checkbox) {
    $('.component-checkbox').prop('checked', checkbox.checked).change();
}

function toggleComponent(checkbox) {
    const row = $(checkbox).closest('tr');
    const amountInput = row.find('.payment-amount');
    
    if (checkbox.checked) {
        row.addClass('table-primary');
        amountInput.prop('readonly', false);
    } else {
        row.removeClass('table-primary');
        amountInput.prop('readonly', true);
        amountInput.val(0);
    }
    
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    let count = 0;
    
    $('.component-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const amount = parseFloat(row.find('.payment-amount').val()) || 0;
        total += amount;
        count++;
    });
    
    $('#totalAmount').val(total.toFixed(2));
    $('#selectedCount').text(count);
    $('#payingNow').text('₹' + total.toFixed(2));
    $('#remainingAfter').text('₹' + ({{ $totalDue }} - total).toFixed(2));
}

$('.payment-amount').on('input', function() {
    const maxAmount = parseFloat($(this).data('max-amount'));
    let value = parseFloat($(this).val()) || 0;
    
    if (value > maxAmount) {
        value = maxAmount;
        $(this).val(maxAmount);
    }
    
    if (value < 0) {
        value = 0;
        $(this).val(0);
    }
    
    calculateTotal();
});

$('#totalAmount').on('input', function() {
    const totalAmount = parseFloat($(this).val()) || 0;
    let distributed = 0;
    
    const checkedInputs = $('.component-checkbox:checked');
    
    if (checkedInputs.length === 0) {
        return;
    }
    
    checkedInputs.each(function(index) {
        const row = $(this).closest('tr');
        const amountInput = row.find('.payment-amount');
        const maxAmount = parseFloat(amountInput.data('max-amount'));
        
        if (index === checkedInputs.length - 1) {
            // Last component gets remaining
            amountInput.val(Math.max(0, totalAmount - distributed).toFixed(2));
        } else {
            // Distribute evenly up to max
            const share = Math.min(maxAmount, totalAmount / checkedInputs.length);
            amountInput.val(share.toFixed(2));
            distributed += share;
        }
    });
    
    $('#payingNow').text('₹' + totalAmount.toFixed(2));
    $('#remainingAfter').text('₹' + ({{ $totalDue }} - totalAmount).toFixed(2));
});

function submitPayment() {
    const form = $('#paymentForm');
    const formData = new FormData(form[0]);
    
    // Add component allocations
    const allocations = [];
    $('.component-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const amount = parseFloat(row.find('.payment-amount').val()) || 0;
        
        if (amount > 0) {
            allocations.push({
                component_account_id: $(this).data('id'),
                amount: amount
            });
        }
    });
    
    if (allocations.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Components Selected',
            text: 'Please select at least one fee component to pay.'
        });
        return;
    }
    
    formData.append('component_allocations', JSON.stringify(allocations));
    
    const paymentMode = $('select[name="payment_mode"]').val();
    if (!paymentMode) {
        Swal.fire({
            icon: 'warning',
            title: 'Payment Mode Required',
            text: 'Please select a payment mode.'
        });
        return;
    }
    
    const amount = parseFloat($('#totalAmount').val()) || 0;
    if (amount <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Amount',
            text: 'Please enter a valid payment amount.'
        });
        return;
    }
    
    // Disable button
    $('#submitBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split me-2"></i> Processing...');
    
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
                showConfirmButton: true,
                confirmButtonText: 'View Receipt'
            }).then((result) => {
                if (result.isConfirmed && response.receipt_id) {
                    window.open('/fees/receipts/' + response.receipt_id, '_blank');
                }
                window.location.href = '{{ route("fees-collection.workspace", $feeAccount->enrollment->student_id) }}';
            });
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Payment Failed',
                text: xhr.responseJSON?.message || 'Error processing payment'
            });
            $('#submitBtn').prop('disabled', false).html('<i class="bi bi-cash-stack me-2"></i> Collect Payment');
        }
    });
}

// Initialize
$(document).ready(function() {
    calculateTotal();
});
</script>
@endsection
