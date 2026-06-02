@extends('layouts.app')

@section('title', 'Pending Fee Report')

@section('content')
    <div class="bg-white rounded-lg shadow-sm border p-4">
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-slate-900">Pending Fee Report</h2>
            <p class="text-sm text-slate-600">Outstanding balances for follow-up collections.</p>
        </div>
        <div class="overflow-x-auto rounded border">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-2 border text-left">Student</th>
                        <th class="p-2 border text-left">Total Fee</th>
                        <th class="p-2 border text-left">Paid</th>
                        <th class="p-2 border text-left">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td class="p-2 border">{{ optional($account->student)->student_name ?? '-' }}</td>
                            <td class="p-2 border">Rs {{ number_format((float) $account->total_due, 2) }}</td>
                            <td class="p-2 border">Rs {{ number_format((float) $account->total_paid, 2) }}</td>
                            <td class="p-2 border">Rs {{ number_format((float) $account->remaining_balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-3 text-center text-gray-500">No pending fee accounts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
