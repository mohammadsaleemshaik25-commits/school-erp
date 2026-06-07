<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERP Portal - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full border border-gray-200">
       <div class="text-center mb-6">

    <img src="{{ asset('build/assets/school/logo.png') }}"
     alt="School Logo"
     class="mx-auto mb-3"
     style="height:90px; width:auto;">

    <span class="text-3xl font-extrabold text-indigo-900 block">
        VIKAS HIGH SCHOOL
    </span>

    <p class="text-gray-500 text-sm mt-1">
        ERP Portal - Authorized Administration Sign In
    </p>

</div>

        @if($errors->any())
            <div class="bg-red-50 text-red-700 p-3 rounded mb-4 text-xs">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus
                       class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>

            <div class="mb-6">
                <label class="block text-xs font-semibold uppercase text-gray-600 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full px-3 py-2 border rounded-md focus:ring-2 focus:ring-indigo-500 outline-none text-sm">
            </div>

            <button type="submit" class="w-full bg-indigo-900 hover:bg-indigo-800 text-white py-2 rounded-md font-bold text-sm tracking-wide transition shadow">
                Sign In to Dashboard
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="{{ route('dashboard') }}" class="text-xs text-indigo-600 hover:underline">Go to Dashboard</a>
        </div>
    </div>

</body>
</html>