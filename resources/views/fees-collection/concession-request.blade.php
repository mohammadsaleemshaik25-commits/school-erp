@extends('fees.layout')

@section('title', 'Concession Request')

@section('content')
<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- New Request Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-gift me-2 text-primary"></i>New Concession Request</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        @if($student->photo_path)
                            <img src="{{ asset('storage/' . $student->photo_path) }}" alt="{{ $student->student_name }}" class="rounded-circle shadow-sm mb-3" style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width: 80px; height: 80px; font-size: 2rem;">
                                {{ strtoupper(substr($student->student_name, 0, 1)) }}
                            </div>
                        @endif
                        <h5 class="fw-bold mb-1">{{ $student->student_name }}</h5>
                        <p class="text-muted small mb-0">Adm: {{ $student->admission_no }} | {{ $enrollment->classRoom->class_name }}-{{ $enrollment->section->section_name }}</p>
                    </div>

                    <form action="{{ route('fees-collection.concession-request.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="student_id" value="{{ $student->student_id }}">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Component</label>
                            <select name="component_id" class="form-select rounded-3" required>
                                <option value="">-- Select Component --</option>
                                @foreach($componentAccounts as $comp)
                                    <option value="{{ $comp->component_id }}">
                                        {{ $comp->component->component_name }} (Bal: ₹{{ number_format($comp->balance_amount, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Concession Amount (₹)</label>
                            <input type="number" name="amount" class="form-control rounded-3" placeholder="Enter amount" required min="1">
                            <div class="form-text small">Maximum 50% limit recommended.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Reason / Remarks</label>
                            <textarea name="reason" class="form-control rounded-3" rows="3" placeholder="Explain why concession is needed..." required minlength="5"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-3 py-2">
                            <i class="bi bi-send-fill me-2"></i>Submit Request
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Request History -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-info"></i>Request History</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Component</th>
                                    <th class="text-end">Amount</th>
                                    <th>Reason</th>
                                    <th class="text-center">Status</th>
                                    <th class="pe-4">Decision</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $req)
                                    <tr class="border-bottom">
                                        <td class="ps-4">
                                            <div class="small fw-bold">{{ $req->created_at->format('d M Y') }}</div>
                                            <div class="small text-muted">{{ $req->created_at->format('h:i A') }}</div>
                                        </td>
                                        <td>
                                            <div class="small fw-bold text-dark">{{ $req->component->component_name ?? 'General' }}</div>
                                            <div class="small text-muted">By: {{ $req->requester->full_name }}</div>
                                        </td>
                                        <td class="text-end fw-bold text-primary">
                                            ₹{{ number_format($req->discount_amount, 2) }}
                                        </td>
                                        <td>
                                            <div class="small text-muted text-wrap" style="max-width: 200px;">{{ $req->reason }}</div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusClass = match($req->approval_status) {
                                                    'PENDING' => 'bg-warning text-dark',
                                                    'APPROVED' => 'bg-success text-white',
                                                    'REJECTED' => 'bg-danger text-white',
                                                    default => 'bg-light text-dark'
                                                };
                                            @endphp
                                            <span class="badge {{ $statusClass }} rounded-pill px-3">
                                                {{ $req->approval_status }}
                                            </span>
                                        </td>
                                        <td class="pe-4">
                                            @if($req->approval_status === 'APPROVED')
                                                <div class="small text-success">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Approved by {{ $req->approver->full_name ?? 'Admin' }}
                                                </div>
                                            @elseif($req->approval_status === 'REJECTED')
                                                <div class="small text-danger">
                                                    <i class="bi bi-x-circle-fill me-1"></i>Rejected: {{ $req->rejection_reason }}
                                                </div>
                                            @else
                                                <div class="small text-muted italic">Waiting for principal...</div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted small">No previous concession requests found.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
