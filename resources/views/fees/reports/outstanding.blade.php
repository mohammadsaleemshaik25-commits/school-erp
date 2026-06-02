@extends('fees.layout')
@section('title', 'Outstanding Fee Report')
@section('content')
<h1 class="h3">Outstanding Fee Report</h1>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0">
<thead><tr><th>Admission No.</th><th>Student</th><th>Class</th><th>Section</th><th>Books Due</th><th>Tuition Due</th><th>Total Due</th></tr></thead>
<tbody>@forelse($rows as $row)<tr><td>{{ $row->admission_no }}</td><td>{{ $row->student_name }}</td><td>{{ $row->class_name }}</td><td>{{ $row->section_name }}</td><td>{{ $row->balance->remaining_books_fee }}</td><td>{{ $row->balance->remaining_tuition_fee }}</td><td>{{ $row->balance->due_amount }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No outstanding fees.</td></tr>@endforelse</tbody>
</table></div></div>
@endsection
