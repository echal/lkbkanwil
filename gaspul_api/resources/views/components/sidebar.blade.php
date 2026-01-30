<!-- Sidebar -->
<aside
    class="bg-gradient-to-b from-green-700 to-green-800 text-white w-64 flex-shrink-0 transition-all duration-300 overflow-y-auto"
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
>
    <div class="p-6">
        <!-- Logo & Title -->
        <div class="flex items-center mb-8">
            <div class="flex-shrink-0 mr-3">
                <img src="{{ asset('images/logo/logo-kemenag.png') }}"
                     alt="Logo Kemenag"
                     class="h-10 w-10"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div class="h-10 w-10 bg-white rounded-full flex items-center justify-center text-green-700 font-bold text-xl hidden">
                    K
                </div>
            </div>
            <div>
                <h2 class="text-lg font-bold leading-tight">Laporan Harian</h2>
                <p class="text-xs text-green-200">Kemenag Sulbar</p>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="space-y-2">

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('dashboard') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="font-medium">Dashboard</span>
            </a>

            @if(auth()->user()->role === 'ASN')
            <!-- ASN Menu -->
            <div class="pt-4">
                <p class="px-4 text-xs font-semibold text-green-200 uppercase tracking-wider mb-2">Menu ASN</p>

                <!-- SKP Tahunan -->
                <a href="{{ route('asn.skp-tahunan.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('asn.skp-tahunan.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="font-medium">SKP Tahunan</span>
                </a>

                <!-- Kinerja Harian -->
                <a href="{{ route('asn.harian.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('asn.harian.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span class="font-medium">Kinerja Harian</span>
                </a>

                <!-- Rencana Hasil Kerja -->
                <a href="{{ route('asn.rencana-kerja.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('asn.rencana-kerja.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="font-medium">Rencana Hasil Kerja</span>
                </a>

                <!-- Bulanan -->
                <a href="{{ route('asn.bulanan.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('asn.bulanan.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="font-medium">Laporan Bulanan</span>
                </a>
            </div>
            @endif

            @if(auth()->user()->role === 'ATASAN')
            <!-- Atasan Menu -->
            <div class="pt-4">
                <p class="px-4 text-xs font-semibold text-green-200 uppercase tracking-wider mb-2">Menu Atasan</p>

                <!-- SKP Tahunan -->
                <a href="{{ route('atasan.skp-tahunan.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('atasan.skp-tahunan.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="font-medium">SKP Tahunan</span>
                </a>

                <!-- Persetujuan -->
                <a href="{{ route('atasan.approval.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('atasan.approval.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium">Persetujuan</span>
                </a>

                <!-- Harian Bawahan (TAHAP 3) -->
                <a href="{{ route('atasan.harian-bawahan.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('atasan.harian-bawahan.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span class="font-medium">Harian Bawahan</span>
                </a>

                <!-- Kinerja Bawahan -->
                <a href="{{ route('atasan.kinerja-bawahan.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('atasan.kinerja-bawahan.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="font-medium">Kinerja Bawahan</span>
                </a>
            </div>
            @endif

            @if(auth()->user()->role === 'ADMIN')
            <!-- Admin Menu -->
            <div class="pt-4">
                <p class="px-4 text-xs font-semibold text-green-200 uppercase tracking-wider mb-2">Menu Admin</p>

                <!-- Sasaran Kegiatan -->
                <a href="{{ route('admin.sasaran-kegiatan.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('admin.sasaran-kegiatan.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="font-medium">Sasaran Kegiatan</span>
                </a>

                <!-- Indikator Kinerja -->
                <a href="{{ route('admin.indikator-kinerja.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('admin.indikator-kinerja.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="font-medium">Indikator Kinerja</span>
                </a>

                <!-- RHK Pimpinan -->
                <a href="{{ route('admin.rhk-pimpinan.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('admin.rhk-pimpinan.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span class="font-medium">RHK Pimpinan</span>
                </a>

                <!-- Unit Kerja -->
                <a href="{{ route('admin.unit-kerja.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('admin.unit-kerja.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="font-medium">Unit Kerja</span>
                </a>

                <!-- Data Pegawai -->
                <a href="{{ route('admin.pegawai.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg transition duration-200 {{ request()->routeIs('admin.pegawai.*') ? 'bg-white text-green-700 shadow-lg' : 'text-white hover:bg-green-600' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="font-medium">Data Pegawai</span>
                </a>
            </div>
            @endif

        </nav>
    </div>
</aside>
