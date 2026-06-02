<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Vikas High School ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root{--brand:#0d6efd;--muted:#6c757d}
        body{background:#f6f8fa}
        .topbar{height:56px}
        .sidebar{width:250px;background:#ffffff;border-right:1px solid rgba(0,0,0,.05)}
        .sidebar .nav-link{color:rgba(0,0,0,.7)}
        .sidebar .nav-link.active{background:linear-gradient(90deg,rgba(13,110,253,.08),transparent);color:var(--brand)}
        main.content-area{margin-top:56px;padding:24px}
        @media(min-width:992px){main.content-area{margin-left:250px}}
        @media(max-width:991px){.sidebar{display:none}}
        .card.shadow-sm{box-shadow:0 1px 4px rgba(18,38,63,.04)}
        /* Print styles for receipts */
        @media print{body{background:white} .topbar, .sidebar, .d-print-none{display:none} main.content-area{margin:0;padding:0}}
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow fixed-top topbar">
        <div class="container-fluid px-4">
            <button class="btn btn-primary d-lg-none me-2 border-0" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ url('/') }}">
                <i class="bi bi-mortarboard-fill me-2 fs-3"></i>
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
        <div class="offcanvas-body p-0 flex-column h-100">
            <div class="p-4 d-none d-lg-block">
                <div class="small text-muted text-uppercase fw-bold mb-3" style="font-size: 0.7rem; letter-spacing: 1px;">Finance Module</div>
            </div>
            <nav class="nav flex-column px-3 pb-4">
                <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->routeIs('fees.collect') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('fees.collect') }}">
                    <i class="bi bi-cash-stack me-3 fs-5"></i>
                    <span class="fw-semibold">Collect Fee</span>
                </a>
                <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->routeIs('fees.adjustments.index') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('fees.adjustments.index') }}">
                    <i class="bi bi-percent me-3 fs-5"></i>
                    <span class="fw-semibold">Concessions</span>
                </a>
                <a class="nav-link rounded-3 mb-2 d-flex align-items-center {{ request()->routeIs('fees.receipts.index') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}" href="{{ route('fees.receipts.index') }}">
                    <i class="bi bi-receipt me-3 fs-5"></i>
                    <span class="fw-semibold">Receipts</span>
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
            </nav>
            
            <div class="mt-auto p-4 bg-light mx-3 mb-4 rounded-4 border">
                <div class="small fw-bold text-dark mb-1">Need Support?</div>
                <div class="small text-muted mb-0" style="font-size: 0.75rem;">Contact IT Admin for ERP related issues.</div>
            </div>
        </div>
    </div>

    <main class="content-area">
        <div class="container-fluid">
            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
