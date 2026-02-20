@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard')


@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Command Center</h2>
            <p class="text-sm text-gray-500 mt-1">Laporan Harian & SKP — Tahun {{ $tahun }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-400">{{ now()->format('l, d F Y') }}</span>
            <a href="{{ route('admin.dashboard.refresh') }}"
               class="inline-flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-200 text-gray-600 text-xs rounded-lg hover:bg-gray-50 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh Data
            </a>
        </div>
    </div>

    {{-- System Health Alert --}}
    @if($health['total_issues'] > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-semibold text-amber-800">{{ $health['total_issues'] }} Masalah Integritas Data Terdeteksi</p>
                <div class="flex flex-wrap gap-4 mt-2">
                    @if($health['asn_tanpa_atasan'] > 0)
                    <span class="text-xs text-amber-700">⚠ {{ $health['asn_tanpa_atasan'] }} ASN tanpa atasan</span>
                    @endif
                    @if($health['skp_tanpa_approved_by'] > 0)
                    <span class="text-xs text-amber-700">⚠ {{ $health['skp_tanpa_approved_by'] }} SKP tanpa approver</span>
                    @endif
                    @if($health['user_tanpa_unit_kerja'] > 0)
                    <span class="text-xs text-amber-700">⚠ {{ $health['user_tanpa_unit_kerja'] }} pegawai tanpa unit kerja</span>
                    @endif
                    @if($health['atasan_tanpa_atasan'] > 0)
                    <span class="text-xs text-amber-700">⚠ {{ $health['atasan_tanpa_atasan'] }} atasan belum terhubung ke Kakanwil</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- A. SUMMARY CARDS --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">

        {{-- Total Pegawai --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($summary['total_pegawai']) }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Pegawai Aktif</p>
        </div>

        {{-- Total Unit Kerja --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ $summary['total_unit_kerja'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Unit Kerja</p>
        </div>

        {{-- SKP Aktif --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ $summary['skp_aktif'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Total SKP {{ $tahun }}</p>
        </div>

        {{-- SKP Pending --}}
        <div class="bg-white rounded-xl border border-amber-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-amber-600">{{ $summary['skp_pending'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Pending Approval</p>
        </div>

        {{-- SKP Disetujui --}}
        <div class="bg-white rounded-xl border border-green-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-green-600">{{ $summary['skp_disetujui'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Disetujui</p>
        </div>

        {{-- SKP Ditolak --}}
        <div class="bg-white rounded-xl border border-red-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-red-600">{{ $summary['skp_ditolak'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Ditolak</p>
        </div>
    </div>

    {{-- Row 2: Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- C. Grafik SKP per Bulan --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Grafik SKP per Bulan</h3>
                    <p class="text-xs text-gray-500">Jumlah SKP dibuat sepanjang tahun {{ $tahun }}</p>
                </div>
            </div>
            <div style="position:relative; height:200px;">
                <canvas id="chartSkpBulan"></canvas>
            </div>
        </div>

        {{-- G. Role Distribution --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-800 mb-1">Distribusi Role</h3>
            <p class="text-xs text-gray-500 mb-4">Komposisi pegawai aktif</p>
            <div style="position:relative; height:150px;">
                <canvas id="chartRole"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($roles['by_role'] as $role => $total)
                @php
                    $colors = [
                        'ASN'   => 'bg-blue-500',
                        'ATASAN'=> 'bg-green-500',
                        'ADMIN' => 'bg-gray-400',
                    ];
                    $color = $colors[$role] ?? 'bg-gray-400';
                    $pct = $roles['total_aktif'] > 0 ? round(($total / $roles['total_aktif']) * 100) : 0;
                @endphp
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $color }}"></span>
                        <span class="text-gray-600">{{ $role }}</span>
                    </div>
                    <span class="font-semibold text-gray-800">{{ $total }} <span class="font-normal text-gray-400">({{ $pct }}%)</span></span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Row 3: Distribusi Unit + Quick Access --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- C. Grafik distribusi unit kerja --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-800 mb-1">Distribusi Pegawai per Unit Kerja</h3>
            <p class="text-xs text-gray-500 mb-4">Jumlah ASN & Atasan aktif per unit</p>
            <div style="position:relative; height:220px;">
                <canvas id="chartUnit"></canvas>
            </div>
        </div>

        {{-- D. QUICK ACCESS PANEL --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-800 mb-4">Akses Cepat</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('admin.pegawai.create') }}"
                   class="flex flex-col items-center gap-2 p-3 rounded-lg bg-blue-50 hover:bg-blue-100 transition text-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <span class="text-xs font-medium text-blue-700">Tambah Pegawai</span>
                </a>

                <a href="{{ route('admin.unit-kerja.create') }}"
                   class="flex flex-col items-center gap-2 p-3 rounded-lg bg-purple-50 hover:bg-purple-100 transition text-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="text-xs font-medium text-purple-700">Tambah Unit Kerja</span>
                </a>

                <a href="{{ route('admin.rhk-pimpinan.create') }}"
                   class="flex flex-col items-center gap-2 p-3 rounded-lg bg-green-50 hover:bg-green-100 transition text-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-xs font-medium text-green-700">Tambah RHK</span>
                </a>

                <a href="{{ route('admin.pegawai.index') }}"
                   class="flex flex-col items-center gap-2 p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition text-center">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-xs font-medium text-gray-700">Kelola Pegawai</span>
                </a>

                <a href="{{ route('admin.sasaran-kegiatan.index') }}"
                   class="flex flex-col items-center gap-2 p-3 rounded-lg bg-indigo-50 hover:bg-indigo-100 transition text-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="text-xs font-medium text-indigo-700">Sasaran Kegiatan</span>
                </a>

                <a href="{{ route('admin.indikator-kinerja.index') }}"
                   class="flex flex-col items-center gap-2 p-3 rounded-lg bg-rose-50 hover:bg-rose-100 transition text-center">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="text-xs font-medium text-rose-700">Indikator Kinerja</span>
                </a>
            </div>
        </div>
    </div>

    {{-- H. DAILY REPORTING CONTROL --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm" x-data="dailyReporting()">

        {{-- Header --}}
        <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Daily Reporting Control</h3>
                <p class="text-xs text-gray-500">Kepatuhan pengisian laporan harian ASN</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="date"
                       x-model="selectedDate"
                       @change="loadData()"
                       max="{{ now()->format('Y-m-d') }}"
                       class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                <button @click="loadData()"
                        :disabled="loading"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 disabled:opacity-50 transition">
                    <svg class="w-3.5 h-3.5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span x-text="loading ? 'Memuat...' : 'Refresh'"></span>
                </button>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="px-5 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4 border-b border-gray-100">

            {{-- Total ASN --}}
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-800" x-text="data.total_asn ?? '{{ $daily['total_asn'] }}'"></p>
                <p class="text-xs text-gray-500 mt-1">Total ASN Aktif</p>
            </div>

            {{-- Sudah Isi --}}
            <div class="text-center">
                <p class="text-2xl font-bold text-green-600" x-text="data.sudah_isi ?? '{{ $daily['sudah_isi'] }}'"></p>
                <p class="text-xs text-gray-500 mt-1">Sudah Mengisi</p>
            </div>

            {{-- Belum Isi --}}
            <div class="text-center">
                <p class="text-2xl font-bold text-red-500" x-text="data.belum_isi ?? '{{ $daily['belum_isi'] }}'"></p>
                <p class="text-xs text-gray-500 mt-1">Belum Mengisi</p>
            </div>

            {{-- Persentase --}}
            <div class="text-center">
                <div class="relative inline-flex items-center justify-center">
                    <p class="text-2xl font-bold"
                       :class="(data.persen ?? {{ $daily['persen'] }}) >= 80
                                ? 'text-green-600'
                                : ((data.persen ?? {{ $daily['persen'] }}) >= 50 ? 'text-amber-600' : 'text-red-500')"
                       x-text="(data.persen ?? '{{ $daily['persen'] }}') + '%'"></p>
                </div>
                <p class="text-xs text-gray-500 mt-1">Kepatuhan</p>
                {{-- Progress bar --}}
                <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full transition-all duration-500"
                         :class="(data.persen ?? {{ $daily['persen'] }}) >= 80
                                  ? 'bg-green-500'
                                  : ((data.persen ?? {{ $daily['persen'] }}) >= 50 ? 'bg-amber-500' : 'bg-red-500')"
                         :style="'width:' + (data.persen ?? {{ $daily['persen'] }}) + '%'"></div>
                </div>
            </div>
        </div>

        {{-- Grafik + Top 5 --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-0 divide-y lg:divide-y-0 lg:divide-x divide-gray-100">

            {{-- Stacked Bar Chart --}}
            <div class="lg:col-span-2 p-5">
                <h4 class="text-xs font-semibold text-gray-700 mb-3">Kepatuhan per Unit Kerja</h4>
                <div style="position:relative; height:240px;">
                    <canvas id="chartDailyUnit"></canvas>
                </div>
            </div>

            {{-- Top 5 Terendah --}}
            <div class="p-5">
                <h4 class="text-xs font-semibold text-gray-700 mb-3">Top 5 Kepatuhan Terendah</h4>
                <div class="space-y-3" id="top5List">
                    @forelse($daily['top5_rendah'] as $unit)
                    @php
                        $pct = $unit['persen'];
                        $bar  = $pct >= 80 ? 'bg-green-500' : ($pct >= 50 ? 'bg-amber-500' : 'bg-red-500');
                        $text = $pct >= 80 ? 'text-green-600' : ($pct >= 50 ? 'text-amber-600' : 'text-red-500');
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-700 truncate max-w-[160px]" title="{{ $unit['unit'] }}">{{ $unit['unit'] }}</span>
                            <span class="text-xs font-bold {{ $text }}">{{ $pct }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5">
                            <div class="{{ $bar }} h-1.5 rounded-full" style="width:{{ $pct }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $unit['sudah'] }}/{{ $unit['total'] }} ASN</p>
                    </div>
                    @empty
                    <p class="text-xs text-gray-400 text-center py-4">Tidak ada data</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Tombol Drill-Down --}}
        @if($daily['belum_isi'] > 0)
        <div class="px-5 pb-4 border-t border-gray-100 pt-3 flex items-center justify-between">
            <p class="text-xs text-gray-500">
                <span class="font-semibold text-red-500">{{ $daily['belum_isi'] }} ASN</span> belum mengisi laporan hari ini
            </p>
            <button @click="openModal()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg hover:bg-red-100 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Lihat Detail
            </button>
        </div>
        @else
        <div class="px-5 pb-4 border-t border-gray-100 pt-3 text-center">
            <p class="text-xs text-green-600 font-semibold">✓ Semua ASN sudah mengisi laporan hari ini!</p>
        </div>
        @endif

        {{-- Modal Drill-Down --}}
        <div x-show="modalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="modalOpen = false">
            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="modalOpen = false"></div>

            {{-- Modal Content --}}
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[80vh] flex flex-col z-10">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800">ASN Belum Mengisi Laporan</h3>
                        <p class="text-xs text-gray-500" x-text="'Tanggal: ' + (data.tanggal ?? '{{ $daily['tanggal'] }}')"></p>
                    </div>
                    <button @click="modalOpen = false"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Search filter --}}
                <div class="px-6 py-3 border-b border-gray-100">
                    <input type="text" x-model="modalSearch"
                           placeholder="Cari nama atau unit kerja..."
                           class="w-full text-xs border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>

                {{-- Modal Body --}}
                <div class="flex-1 overflow-y-auto divide-y divide-gray-50">
                    <template x-for="(asn, idx) in filteredAsnBelum" :key="asn.id">
                        <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-red-100 text-red-600 text-xs font-bold flex items-center justify-center flex-shrink-0"
                                      x-text="idx + 1"></span>
                                <div>
                                    <p class="text-xs font-semibold text-gray-800" x-text="asn.name"></p>
                                    <p class="text-xs text-gray-500" x-text="asn.jabatan"></p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-500 text-right max-w-[140px] truncate" x-text="asn.unit"></span>
                        </div>
                    </template>
                    <div x-show="filteredAsnBelum.length === 0" class="px-6 py-8 text-center text-xs text-gray-500">
                        Tidak ada ASN yang cocok dengan pencarian
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-3 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">
                        Menampilkan <span class="font-semibold" x-text="filteredAsnBelum.length"></span> ASN
                    </p>
                    <button @click="modalOpen = false"
                            class="px-4 py-1.5 bg-gray-100 text-gray-700 text-xs rounded-lg hover:bg-gray-200 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 4: Approval Monitoring + Recent Activities --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- B. APPROVAL MONITORING --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Monitoring Approval</h3>
                    <p class="text-xs text-gray-500">SKP pending paling lama belum diproses</p>
                </div>
                @if($approval['pending_per_level']->sum() > 0)
                <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">
                    {{ $approval['pending_per_level']->sum() }} pending
                </span>
                @endif
            </div>

            {{-- Pending per level --}}
            <div class="px-5 py-3 border-b border-gray-100">
                <div class="flex gap-4">
                    @foreach($approval['pending_per_level'] as $role => $total)
                    @php
                        $badge = ['ASN' => 'bg-blue-100 text-blue-700', 'ATASAN' => 'bg-green-100 text-green-700'];
                        $bc = $badge[$role] ?? 'bg-gray-100 text-gray-700';
                    @endphp
                    <div class="flex items-center gap-1.5">
                        <span class="px-2 py-0.5 {{ $bc }} text-xs font-medium rounded-full">{{ $role }}</span>
                        <span class="text-sm font-bold text-gray-800">{{ $total }}</span>
                    </div>
                    @endforeach
                    @if($approval['pending_per_level']->isEmpty())
                    <span class="text-xs text-green-600">✓ Tidak ada pending</span>
                    @endif
                </div>
            </div>

            {{-- Top 5 paling lama --}}
            <div class="divide-y divide-gray-50">
                @forelse($approval['top_lama'] as $item)
                <div class="px-5 py-3 flex items-center justify-between hover:bg-gray-50">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 truncate">{{ $item['name'] }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $item['jabatan'] ?: $item['role'] }}</p>
                    </div>
                    <div class="text-right ml-4">
                        <span class="text-xs font-semibold {{ $item['days'] > 7 ? 'text-red-600' : 'text-amber-600' }}">
                            {{ $item['days'] }}h lalu
                        </span>
                        <p class="text-xs text-gray-400">{{ $item['since'] }}</p>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center">
                    <svg class="w-8 h-8 text-green-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-gray-500">Semua SKP sudah diproses</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- F. AKTIVITAS TERBARU --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Aktivitas Terbaru</h3>
            </div>

            {{-- Tabs --}}
            <div x-data="{ tab: 'skp' }">
                <div class="flex border-b border-gray-100 px-5">
                    <button @click="tab = 'skp'"
                            :class="tab === 'skp' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500'"
                            class="py-2.5 text-xs font-medium mr-4 transition">SKP Terbaru</button>
                    <button @click="tab = 'pegawai'"
                            :class="tab === 'pegawai' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500'"
                            class="py-2.5 text-xs font-medium mr-4 transition">Pegawai Baru</button>
                    <button @click="tab = 'approval'"
                            :class="tab === 'approval' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500'"
                            class="py-2.5 text-xs font-medium transition">Approval</button>
                </div>

                {{-- SKP Terbaru --}}
                <div x-show="tab === 'skp'" class="divide-y divide-gray-50">
                    @foreach($recent['skp_terbaru'] as $item)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ $item['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $item['jabatan'] ?: '-' }}</p>
                        </div>
                        <div class="ml-3 text-right">
                            @php
                                $sc = [
                                    'DRAFT'     => 'bg-gray-100 text-gray-600',
                                    'DIAJUKAN'  => 'bg-amber-100 text-amber-700',
                                    'DISETUJUI' => 'bg-green-100 text-green-700',
                                    'DITOLAK'   => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $sc[$item['status']] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $item['status'] }}
                            </span>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $item['date'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Pegawai Terbaru --}}
                <div x-show="tab === 'pegawai'" x-cloak class="divide-y divide-gray-50">
                    @foreach($recent['pegawai_terbaru'] as $item)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ $item['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $item['unit'] }}</p>
                        </div>
                        <div class="ml-3 text-right">
                            <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">{{ $item['role'] }}</span>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $item['date'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Approval Terakhir --}}
                <div x-show="tab === 'approval'" x-cloak class="divide-y divide-gray-50">
                    @forelse($recent['approval_terakhir'] as $item)
                    <div class="px-5 py-3 flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-800 truncate">{{ $item['asn'] }}</p>
                            <p class="text-xs text-gray-500">oleh {{ $item['approver'] }}</p>
                        </div>
                        <div class="ml-3 text-right">
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $item['status'] === 'DISETUJUI' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $item['status'] }}
                            </span>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $item['date'] }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-8 text-center">
                        <p class="text-xs text-gray-500">Belum ada approval</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    // Pastikan Chart.js sudah siap
    if (typeof Chart === 'undefined') {
        console.error('Chart.js tidak ter-load');
        return;
    }

    // ── Data dari server ────────────────────────────────────────────
    const skpLabels  = {!! json_encode(array_values($chart['skp_per_bulan']['labels']->toArray())) !!};
    const skpData    = {!! json_encode(array_values($chart['skp_per_bulan']['data']->toArray())) !!};
    const unitLabels = {!! json_encode(array_values($chart['distribusi_unit']['labels']->toArray())) !!};
    const unitData   = {!! json_encode(array_values($chart['distribusi_unit']['data']->toArray())) !!};
    const roleLabels = {!! json_encode(array_values($roles['by_role']->keys()->toArray())) !!};
    const roleData   = {!! json_encode(array_values($roles['by_role']->values()->toArray())) !!};

    // ── Chart 1: SKP per Bulan ───────────────────────────────────────
    const ctxSkp = document.getElementById('chartSkpBulan');
    if (ctxSkp) {
        new Chart(ctxSkp.getContext('2d'), {
            type: 'bar',
            data: {
                labels: skpLabels,
                datasets: [{
                    label: 'Jumlah SKP',
                    data: skpData,
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + ctx.parsed.y + ' SKP'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 11 } },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        ticks: { font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // ── Chart 2: Distribusi Unit Kerja ───────────────────────────────
    const ctxUnit = document.getElementById('chartUnit');
    if (ctxUnit) {
        const colors = [
            '#6366f1','#10b981','#f59e0b','#ef4444',
            '#0ea5e9','#a855f7','#ec4899','#14b8a6'
        ];
        new Chart(ctxUnit.getContext('2d'), {
            type: 'bar',
            data: {
                labels: unitLabels,
                datasets: [{
                    label: 'Pegawai',
                    data: unitData,
                    backgroundColor: colors.map(c => c + '33'),
                    borderColor: colors,
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + ctx.parsed.x + ' pegawai'
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 10 } },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    y: {
                        ticks: { font: { size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // ── Chart 3: Role Distribution (Doughnut) ───────────────────────
    const ctxRole = document.getElementById('chartRole');
    if (ctxRole) {
        new Chart(ctxRole.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: roleLabels,
                datasets: [{
                    data: roleData,
                    backgroundColor: [
                        'rgba(59,130,246,0.85)',
                        'rgba(16,185,129,0.85)',
                        'rgba(156,163,175,0.85)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' orang'
                        }
                    }
                }
            }
        });
    }

    // ── Chart H: Daily Stacked Bar (per Unit Kerja) ──────────────────
    const dailyInitialData = {!! json_encode($daily['chart']->toArray()) !!};

    window.dailyChartInstance = null;

    window.renderDailyChart = function(chartData) {
        const ctxDaily = document.getElementById('chartDailyUnit');
        if (!ctxDaily) return;

        if (window.dailyChartInstance) {
            window.dailyChartInstance.destroy();
        }

        const labels   = chartData.map(u => u.unit);
        const sudah    = chartData.map(u => u.sudah);
        const belum    = chartData.map(u => u.belum);

        window.dailyChartInstance = new Chart(ctxDaily.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sudah Isi',
                        data: sudah,
                        backgroundColor: 'rgba(16,185,129,0.75)',
                        borderColor: 'rgba(16,185,129,1)',
                        borderWidth: 1,
                        borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 4, bottomRight: 4 },
                        borderSkipped: false,
                        stack: 'kepatuhan',
                    },
                    {
                        label: 'Belum Isi',
                        data: belum,
                        backgroundColor: 'rgba(239,68,68,0.65)',
                        borderColor: 'rgba(239,68,68,1)',
                        borderWidth: 1,
                        borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                        borderSkipped: false,
                        stack: 'kepatuhan',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { font: { size: 11 }, boxWidth: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            afterBody: (items) => {
                                const idx   = items[0].dataIndex;
                                const total = chartData[idx]?.total ?? 0;
                                const pct   = chartData[idx]?.persen ?? 0;
                                return ['Total: ' + total + ' ASN', 'Kepatuhan: ' + pct + '%'];
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        ticks: { font: { size: 9 }, maxRotation: 45, minRotation: 30 },
                        grid: { display: false }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 10 } },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    }
                }
            }
        });
    };

    // Render saat halaman load
    renderDailyChart(dailyInitialData);

})();

// ── Alpine.js component: dailyReporting ─────────────────────────────
function dailyReporting() {
    return {
        loading: false,
        modalOpen: false,
        modalSearch: '',
        selectedDate: '{{ now()->format("Y-m-d") }}',
        data: {
            total_asn: {{ $daily['total_asn'] }},
            sudah_isi: {{ $daily['sudah_isi'] }},
            belum_isi: {{ $daily['belum_isi'] }},
            persen:    {{ $daily['persen'] }},
            tanggal:   '{{ $daily['tanggal'] }}',
        },
        asnBelum: {!! json_encode($daily['asn_belum']->toArray()) !!},

        get filteredAsnBelum() {
            if (!this.modalSearch) return this.asnBelum;
            const q = this.modalSearch.toLowerCase();
            return this.asnBelum.filter(a =>
                a.name.toLowerCase().includes(q) ||
                a.unit.toLowerCase().includes(q) ||
                (a.jabatan && a.jabatan.toLowerCase().includes(q))
            );
        },

        openModal() {
            this.modalSearch = '';
            this.modalOpen = true;
        },

        async loadData() {
            this.loading = true;
            try {
                const url = '{{ route("admin.dashboard.daily-report") }}?tanggal=' + this.selectedDate;
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('Gagal memuat data');
                const json = await res.json();

                // Update summary cards
                this.data.total_asn = json.total_asn;
                this.data.sudah_isi = json.sudah_isi;
                this.data.belum_isi = json.belum_isi;
                this.data.persen    = json.persen;
                this.data.tanggal   = json.tanggal;

                // Update ASN belum list
                this.asnBelum = json.asn_belum;

                // Re-render chart
                if (window.renderDailyChart) {
                    window.renderDailyChart(json.chart);
                }

                // Update top5 list (simple DOM update)
                this.updateTop5(json.top5_rendah);

            } catch(e) {
                console.error('Error loading daily report:', e);
            } finally {
                this.loading = false;
            }
        },

        updateTop5(top5) {
            const container = document.getElementById('top5List');
            if (!container) return;
            if (!top5 || top5.length === 0) {
                container.innerHTML = '<p class="text-xs text-gray-400 text-center py-4">Tidak ada data</p>';
                return;
            }
            container.innerHTML = top5.map(u => {
                const barColor  = u.persen >= 80 ? 'bg-green-500' : (u.persen >= 50 ? 'bg-amber-500' : 'bg-red-500');
                const textColor = u.persen >= 80 ? 'text-green-600' : (u.persen >= 50 ? 'text-amber-600' : 'text-red-500');
                return `
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-gray-700 truncate max-w-[160px]" title="${u.unit}">${u.unit}</span>
                        <span class="text-xs font-bold ${textColor}">${u.persen}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="${barColor} h-1.5 rounded-full" style="width:${u.persen}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">${u.sudah}/${u.total} ASN</p>
                </div>`;
            }).join('');
        }
    };
}
</script>
@endpush
