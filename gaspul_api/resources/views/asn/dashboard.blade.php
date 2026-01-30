@extends('layouts.app')

@section('title', 'Dashboard ASN - Laporan Harian')
@section('page-title', 'Dashboard ASN')

@section('content')
<div class="space-y-6">

    <!-- Welcome Card -->
    <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Selamat Datang, {{ auth()->user()->name }}</h2>
                <p class="text-green-100">
                    {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                </p>
                @if(auth()->user()->nip)
                <p class="text-green-100 text-sm mt-1">NIP: {{ auth()->user()->nip }}</p>
                @endif
            </div>
            <div class="hidden md:block">
                <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- Total SKP Tahunan -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">{{ $stats['total_skp'] ?? 0 }}</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 mb-1">Total SKP Tahunan</h3>
            <p class="text-xs text-gray-500">Target kinerja tahunan</p>
        </div>

        <!-- Kinerja Harian Bulan Ini -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">{{ $stats['kinerja_bulan_ini'] ?? 0 }}</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 mb-1">Kinerja Bulan Ini</h3>
            <p class="text-xs text-gray-500">Laporan harian tercatat</p>
        </div>

        <!-- Rencana Kerja Aktif -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">{{ $stats['rencana_aktif'] ?? 0 }}</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 mb-1">Rencana Kerja Aktif</h3>
            <p class="text-xs text-gray-500">Sedang berjalan</p>
        </div>

        <!-- Progres Keseluruhan -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">{{ $stats['progres'] ?? 0 }}%</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 mb-1">Progres Keseluruhan</h3>
            <p class="text-xs text-gray-500">Pencapaian target</p>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Aksi Cepat</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <!-- Tambah Kinerja Harian -->
            <a href="{{ route('asn.harian.pilih') }}" class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition group">
                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Tambah Progres</p>
                    <p class="text-xs text-gray-500">Input laporan hari ini</p>
                </div>
            </a>

            <!-- Lihat SKP Tahunan -->
            <a href="{{ route('asn.skp-tahunan.index') }}" class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition group">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Lihat SKP Tahunan</p>
                    <p class="text-xs text-gray-500">Target kinerja tahunan</p>
                </div>
            </a>

            <!-- Buat Rencana Kerja -->
            <a href="{{ route('asn.rencana-kerja.tambah') }}" class="flex items-center p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition group">
                <div class="w-10 h-10 bg-yellow-600 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Buat Rencana Kerja</p>
                    <p class="text-xs text-gray-500">Rencanakan kegiatan</p>
                </div>
            </a>

        </div>
    </div>

    <!-- Recent Activities -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Aktivitas Terbaru</h3>
            <a href="{{ route('asn.harian.index') }}" class="text-sm text-green-600 hover:text-green-700 font-medium">
                Lihat Semua →
            </a>
        </div>

        @if(isset($recent_activities) && count($recent_activities) > 0)
        <div class="space-y-3">
            @foreach($recent_activities as $activity)
            <div class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="font-medium text-gray-800">{{ $activity['title'] }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $activity['description'] }}</p>
                    <p class="text-xs text-gray-400 mt-2">{{ $activity['date'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500 mb-4">Belum ada aktivitas tercatat</p>
            <a href="{{ route('asn.harian.pilih') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Progres
            </a>
        </div>
        @endif
    </div>

</div>
@endsection
