@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Daily Revenue collection</h1>
        <form action="{{ route('reports.daily') }}" method="GET" class="flex gap-2 items-center">
            <input type="date" name="date" value="{{ $dateStr }}" class="rounded border-gray-300 text-sm">
            <button type="submit" class="bg-indigo-600 text-white text-sm px-4 py-2 rounded hover:bg-indigo-700">Filter Date</button>
        </form>
    </div>

    <!-- Summary Box -->
    <div class="bg-indigo-800 text-white rounded-lg p-6 mb-6 shadow-md flex justify-between items-center">
        <div>
            <h3 class="text-lg opacity-80">Total Collections Recorded for {{ \Carbon\Carbon::parse($dateStr)->format('d F Y') }}</h3>
            <p class="text-3xl font-extrabold font-mono mt-1">₹{{ number_format($totalCollected, 2) }}</p>
        </div>
        <button onclick="window.print()" class="bg-white text-indigo-800 font-semibold text-sm px-4 py-2 rounded hover:bg-gray-100">Print Report</button>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Payment Mode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recorded By</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-sm font-mono">
                @forelse($payments as $p)
                    <tr>
                        <td class="px-6 py-4 font-bold text-indigo-600">
                            {{ $p->receipt->receipt_number ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 font-sans font-semibold">
                            {{ $p->feeAccount->student->first_name }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-0.5 rounded text-xs {{ $p->payment_mode === 'UPI' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ $p->payment_mode }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-sans text-xs">
                            {{ $p->collector->name }}
                        </td>
                        <td class="px-6 py-4 text-right font-bold">
                            ₹{{ number_format($p->amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 font-sans">No collections logged for this date.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection