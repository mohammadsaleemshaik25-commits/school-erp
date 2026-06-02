@extends('fees.layout')

@section('title', 'Receipt History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Receipt History</h1>
    <a href="{{ route('fees.receipts.index') }}" class="btn btn-sm btn-outline-secondary d-none d-md-inline">Refresh</a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Receipt</th><th>Date</th><th>Student</th><th>Admission No.</th><th class="text-end">Amount</th><th>Collector</th><th></th></tr></thead>
            <tbody>
            @forelse ($receipts as $receipt)
                <tr>
                    <td class="fw-medium">{{ $receipt->receipt_number }}</td>
                    <td>{{ optional(optional($receipt->payment)->payment_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ optional(optional(optional($receipt->payment)->feeAccount)->student)->student_name ?? '-' }}</td>
                    <td>{{ optional(optional(optional($receipt->payment)->feeAccount)->student)->admission_no ?? '-' }}</td>
                    <td class="text-end">{{ number_format((float) optional($receipt->payment)->amount, 2) }}</td>
                    <td>{{ optional(optional($receipt->payment)->collector)->full_name ?? optional(optional($receipt->payment)->collector)->username ?? '-' }}</td>
                    <td><a class="btn btn-sm btn-outline-primary" href="{{ route('fees.receipts.show', $receipt->id) }}">View</a></td>
                </tr>
            @empty
                <tr><td class="text-center text-muted" colspan="7">No receipts found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $receipts->links() }}</div>

@endsection
