@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Fee Adjustment & Concession Desk</h1>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6 flex justify-between items-center">
            <h2 class="text-lg leading-6 font-medium text-gray-900">Pending & Logged Requests</h2>
            <div class="flex gap-2">
                <a href="{{ route('fees.adjustments.index') }}" class="text-xs text-indigo-600 hover:underline">All</a>
                <span class="text-gray-300">|</span>
                <a href="{{ route('fees.adjustments.index', ['status' => 'PENDING']) }}" class="text-xs text-indigo-600 hover:underline">Pending</a>
            </div>
        </div>
        
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                @forelse($adjustments as $adj)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="font-bold">{{ $adj->feeAccount->student->student_name }}</div>
                            <div class="text-xs text-gray-500">Adm No: {{ $adj->feeAccount->student->admission_no }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold">{{ $adj->adjustment_type }}</span>
                            <div class="text-xs text-gray-500">{{ $adj->sub_type }}</div>
                        </td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-red-600">
                            -₹{{ number_format($adj->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 max-w-xs truncate">{{ $adj->reason }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                {{ $adj->status === 'APPROVED' ? 'bg-green-100 text-green-800' : ($adj->status === 'REJECTED' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ $adj->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($adj->status === 'PENDING' && in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN', 'PRINCIPAL', 'CORRESPONDENT'], true))
                                <!-- Administrative Decision Form inline -->
                                <form action="{{ route('fees.adjustments.decide', $adj->id) }}" method="POST" class="inline-flex gap-2 justify-end">
                                    @csrf
                                    <input type="text" name="decision_remarks" placeholder="Remarks..." class="px-1 border rounded text-xs w-28">
                                    <button type="submit" name="status" value="APPROVED" class="bg-green-600 text-white text-xs px-2 py-1 rounded hover:bg-green-700">Approve</button>
                                    <button type="submit" name="status" value="REJECTED" class="bg-red-600 text-white text-xs px-2 py-1 rounded hover:bg-red-700">Reject</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">No Action Pending</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No concession adjustment logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">
            {{ $adjustments->links() }}
        </div>
    </div>
</div>
@endsection