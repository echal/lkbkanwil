<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Laporan Harian - Kanwil Kemenag Sulbar')</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        /* Select2 Tailwind integration */
        .select2-container--default .select2-selection--single {
            height: 46px;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0 12px;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #111827;
            font-size: 0.875rem;
            line-height: 1.5;
            padding-left: 0;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px;
            right: 8px;
        }
        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af;
        }
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.25);
        }
        .select2-dropdown {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            font-size: 0.875rem;
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #2563eb;
        }
        .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 6px 10px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-gray-50" x-data="{ sidebarOpen: true }">
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        @include('components.sidebar')

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">

            <!-- Navbar -->
            @include('components.navbar')

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">

                {{-- Flash messages (survei submit, dll) --}}
                @if(session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('info'))
                    <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
                        <svg class="w-5 h-5 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
                        </svg>
                        {{ session('info') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 text-sm flex items-center gap-3">
                        <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>

        </div>
    </div>

    @stack('scripts')

    @if(auth()->check() && auth()->user()->role !== 'ADMIN')
        @include('components.survei-popup')
    @endif

    @include('components.helpdesk-floating-button')
    @include('components.helpdesk-chat-widget')
</body>
</html>
