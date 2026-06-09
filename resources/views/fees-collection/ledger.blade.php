@extends('fees.layout')

@section('title', 'Student Fee Ledger')

@section('content')
<div class="container-fluid py-4">
    <!-- Header with Student Info -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-4">
                        @if($student->photo_path)
                            <img src="{{ asset('storage/' . $student->photo_path) }}" alt="{{ $student->student_name }}" class="rounded-3" style="width: 100px; height: 100px; object-fit: cover;">
                        @else
                            <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 2.5rem;">
                                {{ strtoupper(substr($student->student_name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 class="fw-bold mb-1 text-dark">{{ $student->student_name }}</h3>
                            <p class="text-muted mb-3">Admission No: <span class="fw-bold text-primary">{{ $student->admission_no }}</span></p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill mb-2">
                                <i class="bi bi-calendar-check me-1"></i> Academic Year: {{ $enrollment->academicYear->year_name }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="row g-4 mt-1">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 p-2 rounded-3 me-3 text-info">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <div class="small text-muted">Class & Section</div>
                                    <div class="fw-bold">{{ $enrollment->classRoom->class_name }} - {{ $enrollment->section->section_name }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 p-2 rounded-3 me-3 text-warning">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div>
                                    <div class="small text-muted">Father Name</div>
                                    <div class="fw-bold">{{ $student->father_name }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3 text-primary">
                                    <i class="bi bi-telephone-fill"></i>
                                </div>
                                <div>
                                    <div class="small text-muted">Mobile Number</div>
                                    <div class="fw-bold">{{ $student->phone_primary }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-secondary bg-opacity-10 p-2 rounded-3 text-secondary">
                            <i class="bi bi-currency-rupee fs-4"></i>
                        </div>
                    </div>
                    <div class="small text-muted text-uppercase fw-bold mb-1">Total Charges</div>
                    <div class="h3 fw-bold mb-0 text-dark">₹{{ number_format($summary['total_charges'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 p-2 rounded-3 text-info">
                            <i class="bi bi-gift fs-4"></i>
                        </div>
                    </div>
                    <div class="small text-muted text-uppercase fw-bold mb-1">Total Concession</div>
                    <div class="h3 fw-bold mb-0 text-info">₹{{ number_format($summary['total_concession'] + $summary['total_waiver'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 p-2 rounded-3 text-success">
                            <i class="bi bi-check-circle fs-4"></i>
                        </div>
                    </div>
                    <div class="small text-muted text-uppercase fw-bold mb-1">Total Paid</div>
                    <div class="h3 fw-bold mb-0 text-success">₹{{ number_format($summary['total_paid'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-danger bg-opacity-10 p-2 rounded-3 text-danger">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                    </div>
                    <div class="small text-muted text-uppercase fw-bold mb-1">Outstanding</div>
                    <div class="h3 fw-bold mb-0 text-danger">₹{{ number_format($summary['outstanding'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2"></i>Transaction History</h5>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print Ledger
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Running Balance</th>
                            <th class="pe-4">Performed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledgerEntries as $entry)
                            <tr class="border-bottom">
                                <td class="ps-4">
                                    <div class="fw-bold">{{ \Carbon\Carbon::parse($entry['date'])->format('d M Y') }}</div>
                                    <div class="small text-muted">{{ \Carbon\Carbon::parse($entry['date'])->format('h:i A') }}</div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match($entry['type']) {
                                            'PAYMENT' => 'bg-success',
                                            'CONCESSION' => 'bg-info',
                                            'WAIVER' => 'bg-primary',
                                            'RECEIPT CANCELLATION' => 'bg-danger',
                                            'CHARGES' => 'bg-secondary',
                                            default => 'bg-light text-dark border'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} rounded-pill px-3">
                                        {{ $entry['type'] }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-dark">{{ $entry['description'] }}</div>
                                    @if($entry['reference'] !== 'N/A')
                                        <div class="small text-muted">Ref: {{ $entry['reference'] }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($entry['type'] === 'RECEIPT CANCELLATION')
                                        <span class="text-danger fw-bold">+₹{{ number_format(abs($entry['amount']), 2) }}</span>
                                    @elseif($entry['type'] === 'CHARGES')
                                        <span class="text-dark fw-bold">₹{{ number_format($entry['amount'], 2) }}</span>
                                    @else
                                        <span class="text-success fw-bold">-₹{{ number_format(abs($entry['amount']), 2) }}</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    ₹{{ number_format($entry['running_balance'], 2) }}
                                </td>
                                <td class="pe-4">
                                    <div class="small text-muted">{{ $entry['performed_by'] }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">No transactions found for this student.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn-group, .navbar, .sidebar, .footer {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    body {
        background-color: white !important;
    }
}
</style>
@endsection
