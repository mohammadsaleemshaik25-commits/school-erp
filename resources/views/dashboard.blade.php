@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold">Dashboard Overview</h2>
            <p class="text-sm text-slate-600">Live KPIs for school operations and fee performance.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-4 border">
                <p class="text-sm text-slate-500">Total Students</p>
                <p class="text-2xl font-semibold text-indigo-900 mt-2">{{ number_format($totalStudents) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 border">
                <p class="text-sm text-slate-500">Today's Collection</p>
                <p class="text-2xl font-semibold text-indigo-900 mt-2">Rs {{ number_format((float) $todayCollection, 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 border">
                <p class="text-sm text-slate-500">Pending Fees</p>
                <p class="text-2xl font-semibold text-indigo-900 mt-2">Rs {{ number_format((float) $pendingFees, 2) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 border">
                <p class="text-sm text-slate-500">Academic Years</p>
                <p class="text-2xl font-semibold text-indigo-900 mt-2">{{ number_format($academicYears) }}</p>
            </div>
        </div>
    </section>
@endsection
