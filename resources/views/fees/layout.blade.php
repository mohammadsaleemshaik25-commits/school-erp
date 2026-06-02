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
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top topbar">
        <div class="container-fluid">
            <button class="btn btn-outline-secondary d-lg-none me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand fw-semibold" href="{{ url('/') }}">Vikas High School ERP</a>
            <div class="ms-auto d-flex align-items-center">
                <form class="d-none d-md-flex" role="search" action="" method="get">
                    <div class="input-group input-group-sm">
                        <input name="q" class="form-control form-control-sm" placeholder="Search..." aria-label="Search">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <div class="offcanvas-lg offcanvas-start sidebar" tabindex="-1" id="sidebarOffcanvas">
        <div class="offcanvas-header d-lg-none">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column p-3">
                <a class="nav-link mb-1" href="{{ route('fees.collect') }}"><i class="bi bi-cash-stack me-2"></i>Collect Fee</a>
                <a class="nav-link mb-1" href="{{ route('fees.adjustments.index') }}"><i class="bi bi-percent me-2"></i>Concessions</a>
                <a class="nav-link mb-1" href="{{ route('fees.receipts.index') }}"><i class="bi bi-receipt me-2"></i>Receipts</a>
                <a class="nav-link mb-1" href="{{ route('fees.reports.daily') }}"><i class="bi bi-calendar2-check me-2"></i>Daily Report</a>
                <a class="nav-link mb-1" href="{{ route('fees.reports.outstanding') }}"><i class="bi bi-exclamation-circle me-2"></i>Outstanding</a>
                <a class="nav-link mb-1" href="{{ route('fees.reports.clerk') }}"><i class="bi bi-person-lines-fill me-2"></i>Clerk Report</a>
            </nav>
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
