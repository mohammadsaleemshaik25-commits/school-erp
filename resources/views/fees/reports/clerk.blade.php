@extends('fees.layout')
@section('title', 'Clerk Collection Report')
@section('content')
<h1 class="h3">Clerk Collection Report</h1>
<form class="row g-2 mb-3"><div class="col-auto"><input class="form-control" type="date" name="from" value="{{ $from }}"></div><div class="col-auto"><input class="form-control" type="date" name="to" value="{{ $to }}"></div><div class="col-auto"><button class="btn btn-primary">View</button></div></form>
@forelse($rows as $collector => $collections)<h2 class="h5 mt-4">{{ $collector }} <span class="badge text-bg-primary">{{ number_format($collections->sum('amount'), 2) }}</span></h2>@include('fees.reports.payment-table', ['rows' => $collections])@empty<p class="text-muted">No collections found.</p>@endforelse
@endsection
