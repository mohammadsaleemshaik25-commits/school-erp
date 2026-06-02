<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vikas School ERP')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen">
    <header class="bg-indigo-900 text-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-wider text-indigo-200">School ERP</p>
                <h1 class="font-semibold">Vikas High School</h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm hidden sm:block">{{ auth()->user()->full_name ?? auth()->user()->username }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-indigo-700 hover:bg-indigo-600 text-xs px-3 py-2 rounded">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-6 grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-6">
        <aside class="bg-white border rounded-lg p-3 h-fit">
            <nav class="space-y-2 text-sm">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded hover:bg-slate-100">Dashboard</a>
                <a href="{{ route('reports.student') }}" class="block px-3 py-2 rounded hover:bg-slate-100">Student Report</a>
                <a href="{{ route('reports.fee') }}" class="block px-3 py-2 rounded hover:bg-slate-100">Fee Report</a>
                <a href="{{ route('reports.pending') }}" class="block px-3 py-2 rounded hover:bg-slate-100">Pending Fees</a>
                <a href="{{ route('reports.daily') }}" class="block px-3 py-2 rounded hover:bg-slate-100">Daily Collection</a>
                @if(in_array(strtoupper(optional(auth()->user()->role)->role_name ?? ''), ['ADMINISTRATOR', 'ADMIN'], true))
                    <a href="{{ route('users.index') }}" class="block px-3 py-2 rounded hover:bg-slate-100">User Management</a>
                @endif
            </nav>
        </aside>
        <main>@yield('content')</main>
    </div>
</body>
</html>
