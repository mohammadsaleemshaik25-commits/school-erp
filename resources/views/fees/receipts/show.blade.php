@extends('fees.layout')

@section('title', 'Receipt '.$receipt->receipt_number)

@section('content')
<div class="container-fluid">
<div class="mx-auto" style="max-width:820px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                        <img src="{{ asset('build/assets/school/logo.png') }}" alt="School Logo" style="height:50px;">
                        <h3 class="mb-0">Vikas High School</h3>
                    </div>
                    <small class="text-muted">Official Fee Receipt</small>
                    @if($receipt->is_duplicate)
                        <div class="mt-2 text-danger fw-bold border border-danger p-1 text-center" style="transform: rotate(-5deg);">
                            *** DUPLICATE RECEIPT ***
                        </div>
                    @endif
                    @if($receipt->status === 'CANCELLED')
                        <div class="mt-2 text-danger fw-bold border border-danger p-1 text-center" style="text-transform: uppercase;">
                            ** CANCELLED **
                        </div>
                    @endif
                </div>
                <div class="text-end">
                    <h5 class="mb-0">Receipt</h5>
                    <div class="text-muted">#{{ $receipt->receipt_number }}</div>
                    @if($receipt->is_duplicate)
                        <div class="small text-muted mt-1">Print Count: {{ $receipt->printed_count }}</div>
                        <div class="small text-muted">Original Date: {{ $receipt->generated_datetime->format('d-m-Y H:i') }}</div>
                    @endif
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-sm-6">
                   <div><strong>Student:</strong> {{ $receipt->payment?->feeAccount?->student?->student_name ?? '-' }}</div>
                      <div><strong>Admission No.:</strong> {{ $receipt->payment?->feeAccount?->student?->admission_no ?? '-' }}</div>
                   <div><strong>Father:</strong> {{ $receipt->payment?->feeAccount?->student?->father_name ?? '-' }}</div>
                </div>

            <div class="col-sm-6 text-sm-end">
              <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($receipt->payment?->payment_date)->format('d-m-Y') }}</div>
                <div><strong>Academic Year:</strong> {{ $receipt->payment?->feeAccount?->academicYear?->year_name ?? '-' }}</div>
              <div><strong>Class:</strong> {{ $receipt->payment?->feeAccount?->classRoom?->class_name ?? '-' }} {{ $receipt->payment?->feeAccount?->section?->section_name ?? '' }}</div>
              <div><strong>Collector:</strong> {{ $receipt->payment?->collector?->full_name ?? $receipt->payment?->collector?->username ?? '-' }}</div>
              </div>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-borderless table-sm">
                    <thead>
                        <tr class="text-muted"><th>Payment Category</th><th class="text-end">Amount (INR)</th></tr>
                    </thead>
                    <tbody>
                        @if($receipt->payment?->previous_fee_paid > 0)
                        <tr><td>Previous Fee</td><td class="text-end">{{ number_format((float) ($receipt->payment?->previous_fee_paid ?? 0), 2) }}</td></tr>
                        @endif
                        @if($receipt->payment?->tuition_fee_paid > 0)
                        <tr><td>Current Tuition Fee</td><td class="text-end">{{ number_format((float) ($receipt->payment?->tuition_fee_paid ?? 0), 2) }}</td></tr>
                        @endif
                        @if($receipt->payment?->books_fee_paid > 0)
                        <tr><td>Books Fee</td><td class="text-end">{{ number_format((float) ($receipt->payment?->books_fee_paid ?? 0), 2) }}</td></tr>
                        @endif
                        <tr class="border-top"><td class="fw-semibold">Total Paid</td><td class="text-end fw-semibold">{{ number_format((float) ($receipt->payment?->amount ?? 0), 2) }}</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Academic Year Completion Status --}}
            @php
                $feeAccount = $receipt->payment?->feeAccount;
                $tuitionRemaining = $feeAccount ? $feeAccount->remaining_tuition_balance : 0;
                $booksRemaining = $feeAccount ? $feeAccount->remaining_books_balance : 0;
                $tuitionCompleted = $tuitionRemaining <= 0 && $feeAccount && $feeAccount->final_tuition_fee > 0;
                $booksCompleted = $booksRemaining <= 0 && $feeAccount && $feeAccount->books_fee_applied > 0;
            @endphp
            @if($tuitionCompleted || $booksCompleted)
            <div class="alert alert-success mb-3">
                @if($tuitionCompleted)
                <div class="fw-bold text-center">ACADEMIC YEAR FEE COMPLETED</div>
                <div class="small text-center">Academic Year: {{ $feeAccount?->academicYear?->year_name ?? '-' }}</div>
                <div class="small text-center">Class: {{ $feeAccount?->classRoom?->class_name ?? '-' }}</div>
                <div class="small text-center">All Tuition Fees Cleared Successfully.</div>
                @endif
                @if($booksCompleted)
                @if($tuitionCompleted)<hr class="my-2">@endif
                <div class="fw-bold text-center">BOOKS FEE COMPLETED</div>
                <div class="small text-center">Academic Year: {{ $feeAccount?->academicYear?->year_name ?? '-' }}</div>
                <div class="small text-center">Class: {{ $feeAccount?->classRoom?->class_name ?? '-' }}</div>
                <div class="small text-center">Books Purchased Through School - Completed.</div>
                @endif
            </div>
            @endif

            <div class="row mb-3 small text-muted">
                <div class="col-sm-6">Remaining Balance: {{ number_format((float) ($receipt->payment?->feeAccount?->remaining_balance ?? 0), 2) }}</div>
                <div class="col-sm-6 text-sm-end">Payment Mode: {{ $receipt->payment?->payment_mode ?? '-' }}</div>
            </div>

            <div class="d-flex justify-content-between align-items-center d-print-none">
                <div class="text-muted small">Generated by: {{ $receipt->payment?->collector?->full_name ?? $receipt->payment?->collector?->username ?? '-' }}</div>
                <div class="btn-group">
                    @if($receipt->status !== 'CANCELLED')
                    <form action="{{ route('fees.receipts.reprint', $receipt->receipt_id) }}" method="POST" class="d-inline mr-2" onsubmit="return confirm('Mark this receipt as DUPLICATE and print again?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Print Again</button>
                    </form>
                    <button type="button" class="btn btn-outline-danger btn-sm mr-2" 
                            onclick="openCancelModal('{{ route('fees.payments.cancel', $receipt->payment_id) }}', '{{ $receipt->receipt_number }}')">
                        Cancel Receipt
                    </button>
                    @endif
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('fees.receipts.print', $receipt->receipt_id) }}?print_format=thermal">Thermal Print</a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

{{-- Cancel Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Cancel Receipt <span id="cancelReceiptNo" class="text-primary"></span></h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cancelForm" method="POST">
                @csrf
                <div class="modal-body py-4">
                    <div class="alert alert-warning small mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This action will revert the student's fee balance and cannot be undone.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Cancellation Reason</label>
                        <textarea name="cancellation_reason" class="form-control shadow-none" rows="3" required placeholder="Enter why this receipt is being cancelled..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openCancelModal(url, receiptNo) {
    document.getElementById('cancelForm').action = url;
    document.getElementById('cancelReceiptNo').innerText = '#' + receiptNo;
    var myModal = new bootstrap.Modal(document.getElementById('cancelModal'));
    myModal.show();
}
</script>
@endpush
@endsection
