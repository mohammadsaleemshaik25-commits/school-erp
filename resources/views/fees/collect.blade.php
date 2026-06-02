@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h1 class="text-2xl font-semibold text-gray-900">Fee Payment Desk</h1>
        @if($account)
            <a href="{{ route('fees.collect') }}" class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-1.5 rounded font-medium transition">
                &larr; Back to Search
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Standard Multi-Field Student Search Panel -->
    @if(!$account)
        <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6 mb-6 border">
            <h2 class="text-md font-bold text-gray-800 mb-4 uppercase tracking-wider">Search Student Dues</h2>
            <form action="{{ route('fees.collect') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Admission Number</label>
                    <input type="text" name="admission_no" value="{{ request('admission_no') }}" placeholder="e.g. ADM-2026-01"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Student Name</label>
                    <input type="text" name="student_name" value="{{ request('student_name') }}" placeholder="e.g. Rahul"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Class</label>
                    <select name="class_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">-- Select Class --</option>
                        @foreach($classes as $cls)
                            <option value="{{ $cls->class_id }}" {{ request('class_id') == $cls->class_id ? 'selected' : '' }}>
                                {{ $cls->class_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Section</label>
                    <select name="section_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">-- Select Section --</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->section_id }}" {{ request('section_id') == $sec->section_id ? 'selected' : '' }}>
                                {{ $sec->section_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-4 flex justify-end">
                    <button type="submit" class="bg-indigo-900 hover:bg-indigo-800 text-white font-bold py-2 px-6 rounded text-sm transition shadow">
                        Find Student Ledger
                    </button>
                </div>
            </form>
        </div>

        <!-- Render Search Results Table -->
        @if($searchResults)
            <div class="bg-white shadow overflow-hidden sm:rounded-lg border">
                <div class="px-4 py-3 bg-gray-50 border-b">
                    <h3 class="text-sm font-bold text-gray-700">Matching Student Records</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Admission No</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Name</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Outstanding Due</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        @forelse($searchResults as $res)
                            <tr>
                                <td class="px-6 py-4 font-mono font-bold text-gray-800">{{ $res->student->admission_no }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ $res->student->student_name }}</td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-indigo-900">₹{{ number_format($res->remaining_balance, 2) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full 
                                        {{ $res->status === 'PAID' ? 'bg-green-100 text-green-800' : ($res->status === 'PARTIALLY_PAID' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $res->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('fees.collect', ['student_fee_account_id' => $res->id]) }}"
                                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1 px-3 rounded text-xs transition">
                                        Collect Payment
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">No student accounts matched your criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-6 py-4 border-t">
                    {{ $searchResults->links() }}
                </div>
            </div>
        @endif
    @else
        <!-- Specific Checkout Ledger Screen (Shown when student is loaded) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white shadow overflow-hidden sm:rounded-lg p-6 border">
                <div class="flex justify-between items-center border-b border-gray-200 pb-4 mb-4">
                    <h2 class="text-lg font-bold text-gray-800">Ledger Statement</h2>
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                        {{ $account->status === 'PAID' ? 'bg-green-100 text-green-800' : ($account->status === 'PARTIALLY_PAID' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                        {{ $account->status }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold">Student Name</p>
                        <p class="font-bold text-gray-800">{{ $account->student->student_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-semibold">Admission No.</p>
                        <p class="font-mono font-bold text-gray-800">{{ $account->student->admission_no }}</p>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200 mt-4">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Fee Type</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <tr>
                            <td class="px-4 py-3 text-gray-700">Tuition Fee</td>
                            <td class="px-4 py-3 text-right font-mono">₹{{ number_format($account->tuition_fee, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 flex items-center justify-between text-gray-700">
                                <span>Books Fee Applied</span>
                                <form action="{{ route('fees.books.update', $account->id) }}" method="POST" class="inline-flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="books_fee_applied" value="{{ (int)$account->books_fee_applied }}" class="w-20 px-1 border rounded text-right text-xs">
                                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-950 underline">Update</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right font-mono">₹{{ number_format($account->books_fee_applied, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-gray-700">Carried Balance (Previous Years)</td>
                            <td class="px-4 py-3 text-right font-mono text-red-600">₹{{ number_format($account->previous_balance_carried, 2) }}</td>
                        </tr>
                        <tr class="bg-green-50 text-green-700">
                            <td class="px-4 py-3 font-semibold">Approved Concessions (-)</td>
                            <td class="px-4 py-3 text-right font-mono font-semibold">₹{{ number_format($account->concession_amount, 2) }}</td>
                        </tr>
                        <tr class="font-bold border-t-2">
                            <td class="px-4 py-3">Total Due Outstanding</td>
                            <td class="px-4 py-3 text-right font-mono">₹{{ number_format($account->total_due, 2) }}</td>
                        </tr>
                        <tr class="text-gray-500">
                            <td class="px-4 py-3">Total Fees Paid to Date</td>
                            <td class="px-4 py-3 text-right font-mono text-green-600">₹{{ number_format($account->total_paid, 2) }}</td>
                        </tr>
                        <tr class="bg-gray-100 font-extrabold text-lg">
                            <td class="px-4 py-3">Net Balance Remaining</td>
                            <td class="px-4 py-3 text-right font-mono text-indigo-900">₹{{ number_format($account->remaining_balance, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Collect Payment Form Card -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6 border">
                <h2 class="text-md font-bold text-gray-800 border-b pb-4 mb-4 uppercase">Record Transaction</h2>
                @if($account->remaining_balance <= 0)
                    <div class="bg-green-100 text-green-800 p-4 rounded text-center text-sm font-semibold">
                        This account has been paid in full. No balance is currently due.
                    </div>
                @else
                    <form action="{{ route('fees.payments.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="student_fee_account_id" value="{{ $account->id }}">

                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Payment Amount (₹)</label>
                            <input type="number" step="0.01" name="amount" max="{{ $account->remaining_balance }}" required
                                   value="{{ old('amount', $account->remaining_balance) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        <div class="mb-4">
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Payment Mode</label>
                            <select name="payment_mode" required onchange="toggleRefField(this.value)"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="CASH">Cash</option>
                                <option value="UPI">UPI (Digital)</option>
                            </select>
                        </div>

                        <div class="mb-4 hidden" id="ref_container">
                            <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">UPI / Reference Number</label>
                            <input type="text" name="transaction_reference" id="transaction_reference"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        <button type="submit" class="w-full inline-flex justify-center py-2.5 px-4 border border-transparent shadow-sm text-sm font-bold rounded-md text-white bg-indigo-900 hover:bg-indigo-800 focus:outline-none transition">
                            Process Payment & Print Receipt
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif
</div>

<script>
    function toggleRefField(val) {
        const refContainer = document.getElementById('ref_container');
        const refField = document.getElementById('transaction_reference');
        if (val === 'UPI') {
            refContainer.classList.remove('hidden');
            refField.setAttribute('required', 'required');
        } else {
            refContainer.classList.add('hidden');
            refField.removeAttribute('required');
        }
    }
</script>
@endsection