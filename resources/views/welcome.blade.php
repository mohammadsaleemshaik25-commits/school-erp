<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vikas High School - ERP & Management Portal</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">

    <!-- Navigation Header -->
    <header class="bg-indigo-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="bg-white p-2 rounded-full text-indigo-900 font-extrabold text-xl w-10 h-10 flex items-center justify-center shadow">
                    V
                </div>
                <span class="text-xl font-bold tracking-wider">VIKAS HIGH SCHOOL</span>
            </div>
            <nav class="space-x-4">
                <a href="#about" class="hover:text-indigo-200 transition text-sm font-medium">About</a>
                <a href="#contact" class="hover:text-indigo-200 transition text-sm font-medium">Contact</a>
                <a href="{{ route('login') }}" class="bg-white text-indigo-900 px-4 py-2 rounded-md text-sm font-bold shadow hover:bg-indigo-50 transition">
                    ERP Portal Login
                </a>
            </nav>
        </div>
    </header>

    <!-- Main Hero Banner Section -->
    <main class="flex-grow">
        <div class="bg-gradient-to-r from-indigo-900 to-indigo-700 text-white py-20 px-4 text-center">
            <div class="max-w-3xl mx-auto">
                <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-4">
                    Nurturing Knowledge, Character & Excellence
                </h1>
                <p class="text-lg sm:text-xl opacity-90 mb-8">
                    Welcome to the official digital platform of Vikas High School. Serving our students, parents, and administration with integrity.
                </p>
                <div class="flex justify-center space-x-4">
                    <a href="{{ route('login') }}" class="bg-yellow-500 hover:bg-yellow-600 text-indigo-950 font-bold px-6 py-3 rounded-md shadow-lg transition">
                        Access School ERP
                    </a>
                    <a href="#notices" class="border border-white hover:bg-white hover:text-indigo-900 font-semibold px-6 py-3 rounded-md transition">
                        Latest Notices
                    </a>
                </div>
            </div>
        </div>

        <!-- School Features Section -->
        <div class="max-w-7xl mx-auto px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg shadow border border-gray-100 text-center">
                    <div class="bg-indigo-100 text-indigo-900 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">✓</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Academic Excellence</h3>
                    <p class="text-gray-600 text-sm">Consistent high scores and comprehensive curriculum tailored for individual student growth.</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow border border-gray-100 text-center">
                    <div class="bg-indigo-100 text-indigo-900 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">✓</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Modern Infrastructure</h3>
                    <p class="text-gray-600 text-sm">Well-equipped science labs, computer rooms, sports complexes, and digital learning classrooms.</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow border border-gray-100 text-center">
                    <div class="bg-indigo-100 text-indigo-900 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 text-xl font-bold">✓</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Unified ERP Portal</h3>
                    <p class="text-gray-600 text-sm">Seamless student records management, instant digital receipt generation, and live fee ledger tracking.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm">
            <p>&copy; {{ date('Y') }} Vikas High School ERP Project. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>