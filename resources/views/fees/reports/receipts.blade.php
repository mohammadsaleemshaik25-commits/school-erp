@extends('fees.layout')
@section('title', 'Receipt Report')
@section('content')
<h1 class="h3">Receipt Report</h1>
<form class="row g-2 mb-3"><div class="col-auto"><input class="form-control" type="date" name="from" value="{{ $from }}"></div><div class="col-auto"><input class="form-control" type="date" name="to" value="{{ $to }}"></div><div class="col-auto"><button class="btn btn-primary">View</button></div></form>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Receipt</th><th>Date</th><th>Admission No.</th><th>Student</th><th>Amount</th><th>Status</th><th>Duplicate</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->receipt_number }}</td><td>{{ $row->receipt_date }}</td><td>{{ $row->admission_no }}</td><td>{{ $row->student_name }}</td><td>{{ number_format($row->amount, 2) }}</td><td>{{ $row->status }}</td><td>{{ $row->is_duplicate ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No receipts found.</td></tr>@endforelse</tbody></table></div></div>
@endsection
