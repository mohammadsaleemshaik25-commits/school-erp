@extends('layouts.app')

@section('title', 'Daily Collection Report')

@section('content')
    <div class="bg-white rounded-lg shadow-sm border p-4">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">Daily Collection Report</h2>
            <p class="text-sm text-slate-600">Today-wise collection snapshot and receipt details.</p>
        </div>
        <form method="GET" class="mb-4">
            <label class="text-sm text-gray-700">Date</label>
            <input type="date" name="date" value="{{ $dateStr }}" class="border px-2 py-1 rounded text-sm">
            <button class="bg-blue-600 text-white text-sm px-3 py-1 rounded">Filter</button>
        </form>
        <p class="mb-3 text-sm font-medium text-gray-700">Total Collection: <span class="text-indigo-900">Rs {{ number_format((float) $totalCollected, 2) }}</span></p>
        <div class="overflow-x-auto rounded border">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-2 border text-left">Date</th>
                        <th class="p-2 border text-left">Student</th>
                        <th class="p-2 border text-left">Amount</th>
                        <th class="p-2 border text-left">Mode</th>
                        <th class="p-2 border text-left">Receipt No</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td class="p-2 border">{{ optional($payment->payment_date)->format('d-m-Y') }}</td>
                            <td class="p-2 border">{{ optional(optional($payment->feeAccount)->student)->student_name ?? '-' }}</td>
                            <td class="p-2 border">Rs {{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="p-2 border">{{ $payment->payment_mode }}</td>
                            <td class="p-2 border">{{ optional($payment->receipt)->receipt_number ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-3 text-center text-gray-500">No payments found for this date.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
