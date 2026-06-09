<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Vikas High School ERP</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        :root{--brand:#0d6efd;--muted:#6c757d}
        body{background:#f6f8fa}
        .topbar{height:56px}
        .sidebar{position: fixed;top:56px;left:0;width:250px;height:calc(100vh - 56px);background:#ffffff;border-right:1px solid rgba(0,0,0,.05);overflow-y:auto;}
        .sidebar .nav-link{color:rgba(0,0,0,.7)}
        .sidebar .nav-link.active{background:linear-gradient(90deg,rgba(13,110,253,.08),transparent);color:var(--brand)}
        main.content-area{margin-top:56px;padding:24px;width:100%;}
        @media(min-width:992px){main.content-area{margin-left:250px;width:calc(100% - 250px);}}
        @media(max-width:991px){.sidebar{display:none}}
        .card.shadow-sm{box-shadow:0 1px 4px rgba(18,38,63,.04)}
        /* Print styles for receipts */
        @media print{body{background:white} .topbar, .sidebar, .d-print-none{display:none} main.content-area{margin:0;padding:0}}
    </style>

    <!-- Core Scripts (Loaded early to ensure availability) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow fixed-top topbar">
        <div class="container-fluid px-4">
            <button class="btn btn-primary d-lg-none me-2 border-0" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ url('/') }}">
                <img src="{{ asset('build/assets/school/logo.png') }}" alt="School Logo" style="height: 32px; margin-right: 8px;">
                <span class="d-none d-sm-inline">Vikas High School ERP</span>
            </a>
            
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="text-white d-none d-md-block text-end me-3">
                    <div class="small opacity-75 text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">Current Date</div>
                    <div class="small fw-semibold">{{ now()->format('l, d F Y') }}</div>
                </div>
                
                <div class="vr bg-white opacity-25 d-none d-md-block" style="height: 30px;"></div>
                
                <div class="dropdown">
                    <button class="btn btn-link text-white text-decoration-none dropdown-toggle d-flex align-items-center gap-2 p-0" type="button" data-bs-toggle="dropdown">
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 32px; height: 32px; font-size: 0.8rem;">
                            {{ strtoupper(substr(auth()->user()->username ?? 'U', 0, 1)) }}
                        </div>
                        <span class="small fw-semibold d-none d-sm-inline">{{ auth()->user()->username }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><h6 class="dropdown-header">User Account</h6></li>
                        <li><div class="dropdown-item small text-muted"><i class="bi bi-person me-2"></i>{{ auth()->user()->full_name ?? auth()->user()->username }}</div></li>
                        <li><div class="dropdown-item small text-muted"><i class="bi bi-shield-lock me-2"></i>{{ optional(auth()->user()->role)->role_name ?? 'User' }}</div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger small">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="offcanvas-lg offcanvas-start sidebar border-0 shadow-sm" tabindex="-1" id="sidebarOffcanvas">
        <div class="offcanvas-header bg-primary text-white d-lg-none">
            <h5 class="offcanvas-title fw-bold">ERP Navigation</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column h-100">
            <div class="p-4 d-none d-lg-block">
                <div class="small text-muted text-uppercase fw-bold mb-3" style="font-size: 0.7rem; letter-spacing: 1px;">Navigation</div>
            </div>
            <nav class="nav flex-column px-3 pb-4">
                @php $role = strtoupper(optional(auth()->user()->role)->role_name ?? ''); @endphp

                <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->routeIs('dashboard') || request()->routeIs('clerk.dashboard') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2 me-3 fs-5"></i>
                    <span class="fw-semibold">Dashboard</span>
                </a>
                
                <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->routeIs('fees-collection.*') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('fees-collection.index') }}">
                    <i class="bi bi-cash-stack me-3 fs-5"></i>
                    <span class="fw-semibold">Fee Collection</span>
                </a>

                <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->routeIs('fees.receipts.index') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('fees.receipts.index') }}">
                    <i class="bi bi-receipt me-3 fs-5"></i>
                    <span class="fw-semibold">Receipts</span>
                </a>

                @if(in_array($role, ['ADMINISTRATOR', 'ADMIN', 'PRINCIPAL', 'CORRESPONDENT']))
                <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->routeIs('admissions.*') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('admissions.index') }}">
                    <i class="bi bi-person-plus me-3 fs-5"></i>
                    <span class="fw-semibold">Admissions</span>
                </a>

                <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->routeIs('promotions.*') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('promotions.index') }}">
                    <i class="bi bi-arrow-up-right-circle me-3 fs-5"></i>
                    <span class="fw-semibold">Promotions</span>
                </a>
                @endif

                <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->routeIs('fees.reports.closing') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('fees.reports.closing') }}">
                    <i class="bi bi-file-earmark-check me-3 fs-5"></i>
                    <span class="fw-semibold">Closing Report</span>
                </a>

                @if($role === 'CLERK')
                    <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->routeIs('fees.adjustments.index') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('fees.adjustments.index') }}">
                        <i class="bi bi-clock-history me-3 fs-5"></i>
                        <span class="fw-semibold">My Requests</span>
                    </a>
                @endif

                @if(in_array($role, ['ADMINISTRATOR', 'ADMIN', 'CORRESPONDENT', 'PRINCIPAL']))
                    <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->routeIs('fees.adjustments.index') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('fees.adjustments.index') }}">
                        <i class="bi bi-percent me-3 fs-5"></i>
                        <span class="fw-semibold">Concession Management</span>
                    </a>
                    
                    <div class="my-3 mx-2 border-bottom opacity-10"></div>
                    <div class="px-2 mb-2 small text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">Reports</div>

                    <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->is('daily-collection') || request()->is('fees/reports/daily') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('fees.reports.daily') }}">
                        <i class="bi bi-calendar2-check me-3 fs-5"></i>
                        <span class="fw-semibold">Daily Report</span>
                    </a>
                    <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->is('pending-fees') || request()->is('fees/reports/outstanding') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('fees.reports.outstanding') }}">
                        <i class="bi bi-exclamation-circle me-3 fs-5"></i>
                        <span class="fw-semibold">Outstanding</span>
                    </a>
                    <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->is('fees/reports/clerk') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('fees.reports.clerk') }}">
                        <i class="bi bi-person-lines-fill me-3 fs-5"></i>
                        <span class="fw-semibold">Clerk Report</span>
                    </a>
                @endif
            </nav>
            
            <div class="mt-auto p-4 bg-light mx-3 mb-4 rounded-4 border">
                <div class="small fw-bold text-dark mb-1">Need Support?</div>
                <div class="small text-muted mb-0" style="font-size: 0.75rem;">Contact IT Admin for ERP related issues.</div>
            </div>
        </div>
    </div>

    <main class="content-area">
        @yield('content')
    </main>

    <!-- Global Concession Request Modal -->
    <div class="modal fade" id="concessionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-primary text-white py-3">
                    <h6 class="modal-title fw-bold"><i class="bi bi-percent me-2"></i> Request Fee Concession</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('fees.adjustments.store') }}" method="POST" id="concessionForm">
                    @csrf
                    <input type="hidden" name="account_id" id="modal_account_id">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Student Details</label>
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="fw-bold text-dark" id="modal_student_name">Loading...</div>
                                    <div class="small text-muted" id="modal_admission_no">...</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-2 bg-light rounded-3 border text-center">
                                    <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Tuition Fee</div>
                                    <div class="fw-bold text-primary" id="modal_tuition_fee">₹0</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-2 bg-light rounded-3 border text-center">
                                    <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.6rem;">Current Due</div>
                                    <div class="fw-bold text-danger" id="modal_current_due">₹0</div>
                                </div>
                            </div>
                            <div class="col-md-12" id="modal_component_container">
                                <label class="form-label small fw-bold text-muted text-uppercase">Component (Optional)</label>
                                <select name="component_id" id="modal_component_id" class="form-select border-primary shadow-none">
                                    <option value="">-- Overall Tuition / Legacy --</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Adjustment Type <span class="text-danger">*</span></label>
                                <select name="adjustment_type" class="form-select border-primary shadow-none" required>
                                    <option value="CONCESSION">CONCESSION</option>
                                    <option value="WAIVER">WAIVER</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Discount %</label>
                                <input type="number" name="discount_percent" id="modal_discount_percent" class="form-control border-primary shadow-none" step="0.01" min="0" max="100" placeholder="e.g. 10">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase">Amount (₹)</label>
                                <input type="number" name="discount_amount" id="modal_discount_amount" class="form-control border-primary shadow-none" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted text-uppercase">Reason <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control border-primary shadow-none" rows="3" required placeholder="Reason for concession..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 py-3">
                        <button type="button" class="btn btn-link text-muted text-decoration-none px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold rounded-pill shadow-sm">
                            <i class="bi bi-send-fill me-2"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Layout Scripts -->
    <script>
        function openCancelModal(cancelUrl, receiptNumber) {
            Swal.fire({
                title: 'Cancel Receipt?',
                html: `<p>Receipt: <strong>${receiptNumber}</strong></p><textarea id="cancelReason" class="swal2-textarea" placeholder="Enter cancellation reason"></textarea>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Cancel',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'No'
            }).then((result) => {
                if (result.isConfirmed) {
                    const reason = document.getElementById('cancelReason').value;
                    if (!reason.trim()) {
                        Swal.fire('Error', 'Cancellation reason is required', 'error');
                        return;
                    }
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = cancelUrl;
                    form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}"><input type="hidden" name="cancellation_reason" value="${reason}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function openConcessionModal(data) {
            $('#modal_account_id').val(data.account_id);
            $('#modal_student_name').text(data.student_name);
            $('#modal_admission_no').text(data.admission_no + ' | ' + data.class_name);
            $('#modal_tuition_fee').text('₹' + parseFloat(data.tuition_fee).toLocaleString());
            $('#modal_current_due').text('₹' + parseFloat(data.current_due).toLocaleString());
            window.currentTuitionFee = parseFloat(data.tuition_fee);
            window.currentBaseFee = window.currentTuitionFee;

            const compSelect = $('#modal_component_id');
            compSelect.html('<option value="">-- Overall Tuition / Legacy --</option>');
            if (data.components && data.components.length > 0) {
                $('#modal_component_container').removeClass('d-none');
                data.components.forEach(c => {
                    if (parseFloat(c.balance) > 0) {
                        compSelect.append(`<option value="${c.id}" data-balance="${c.balance}" data-amount="${c.amount}">${c.name} (Due: ₹${parseFloat(c.balance).toLocaleString()})</option>`);
                    }
                });
            } else {
                $('#modal_component_container').addClass('d-none');
            }

            $('#modal_discount_percent').val('');
            $('#modal_discount_amount').val('');
            $('[name="reason"]').val('');
            new bootstrap.Modal(document.getElementById('concessionModal')).show();
        }

        $(document).ready(function() {
            $('#modal_component_id').on('change', function() {
                const selected = $(this).find('option:selected');
                const compAmount = parseFloat(selected.data('amount'));
                if (!isNaN(compAmount)) {
                    window.currentBaseFee = compAmount;
                } else {
                    window.currentBaseFee = window.currentTuitionFee;
                }
                const percent = parseFloat($('#modal_discount_percent').val());
                if (!isNaN(percent) && window.currentBaseFee > 0) {
                    $('#modal_discount_amount').val((window.currentBaseFee * percent / 100).toFixed(2));
                }
            });

            $('#modal_discount_percent').on('input', function() {
                const percent = parseFloat($(this).val());
                if (!isNaN(percent) && window.currentBaseFee > 0) {
                    $('#modal_discount_amount').val((window.currentBaseFee * percent / 100).toFixed(2));
                } else {
                    $('#modal_discount_amount').val('');
                }
            });

            $('#modal_discount_amount').on('input', function() {
                const amount = parseFloat($(this).val());
                if (!isNaN(amount) && window.currentBaseFee > 0) {
                    $('#modal_discount_percent').val((amount / window.currentBaseFee * 100).toFixed(2));
                } else {
                    $('#modal_discount_percent').val('');
                }
            });
        });
    </script>

    {{-- Session Notifications --}}
    <script>
        $(document).ready(function() {
            @if(session('success_clerk'))
                Swal.fire({
                    icon: 'success',
                    title: '✓ ' + @json(session('success_clerk')['message']),
                    html: `
                        <div class="text-start">
                            <div class="mb-2"><span class="badge bg-primary">Request ID: #${ @json(session('success_clerk')['id']) }</span></div>
                            <div class="mb-2"><strong>Status:</strong> <span class="text-warning">${ @json(session('success_clerk')['status']) }</span></div>
                            <p class="small text-muted mb-0">${ @json(session('success_clerk')['detail']) }</p>
                        </div>
                    `,
                    confirmButtonColor: '#0d6efd',
                    confirmButtonText: 'Understood'
                });
            @endif

            @if(session('success'))
                Swal.fire({ icon: 'success', title: 'Success', text: @json(session('success')), confirmButtonColor: '#0d6efd', timer: 3000, timerProgressBar: true });
            @endif
            @if(session('error'))
                Swal.fire({ icon: 'error', title: 'Error', text: @json(session('error')), confirmButtonColor: '#dc3545' });
            @endif
            @if(session('warning'))
                Swal.fire({ icon: 'warning', title: 'Warning', text: @json(session('warning')), confirmButtonColor: '#ffc107' });
            @endif
            @if($errors->any())
                Swal.fire({ icon: 'error', title: 'Validation Error', html: `<ul style="text-align:left;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>`, confirmButtonColor: '#dc3545' });
            @endif
        });
    </script>

    @stack('scripts')
</body>
</html>
