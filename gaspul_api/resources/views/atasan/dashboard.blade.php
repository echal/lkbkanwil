@extends('layouts.app')

@section('title', 'Dashboard Atasan - Laporan Harian')
@section('page-title', 'Dashboard Atasan')

@section('content')
<div class="space-y-6">

    <!-- Welcome Card -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Selamat Datang, {{ auth()->user()->name }}</h2>
                <p class="text-blue-100">
                    {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                </p>
                @if(auth()->user()->nip)
                <p class="text-blue-100 text-sm mt-1">NIP: {{ auth()->user()->nip }}</p>
                @endif
            </div>
            <div class="hidden md:block">
                <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- Total Bawahan -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">{{ $stats['total_bawahan'] ?? 0 }}</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 mb-1">Total Bawahan</h3>
            <p class="text-xs text-gray-500">Pegawai di bawah supervisi</p>
        </div>

        <!-- Perlu Persetujuan -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">{{ $stats['pending_approval'] ?? 0 }}</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 mb-1">Perlu Persetujuan</h3>
            <p class="text-xs text-gray-500">Menunggu validasi Anda</p>
        </div>

        <!-- Sudah Disetujui -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">{{ $stats['approved'] ?? 0 }}</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 mb-1">Sudah Disetujui</h3>
            <p class="text-xs text-gray-500">Bulan ini</p>
        </div>

        <!-- Rata-rata Kinerja -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-gray-800">{{ $stats['avg_kinerja'] ?? 0 }}%</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 mb-1">Rata-rata Kinerja</h3>
            <p class="text-xs text-gray-500">Pencapaian tim</p>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Aksi Cepat</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <!-- Validasi Persetujuan -->
            <a href="{{ route('atasan.approval.index') }}" class="flex items-center p-4 bg-orange-50 hover:bg-orange-100 rounded-lg transition group">
                <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Validasi Persetujuan</p>
                    <p class="text-xs text-gray-500">{{ $stats['pending_approval'] ?? 0 }} menunggu</p>
                </div>
            </a>

            <!-- Lihat Kinerja Bawahan -->
            <a href="{{ route('atasan.kinerja-bawahan.index') }}" class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition group">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Kinerja Bawahan</p>
                    <p class="text-xs text-gray-500">Monitoring pencapaian</p>
                </div>
            </a>

            <!-- SKP Tahunan -->
            <a href="{{ route('atasan.skp-tahunan.index') }}" class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition group">
                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-4 group-hover:scale-110 transition">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">SKP Tahunan</p>
                    <p class="text-xs text-gray-500">Target kinerja bawahan</p>
                </div>
            </a>

        </div>
    </div>

    <!-- Pending Approvals -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Persetujuan Menunggu</h3>
            <a href="{{ route('atasan.approval.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                Lihat Semua →
            </a>
        </div>

        @if(isset($pending_approvals) && count($pending_approvals) > 0)
        <div class="space-y-3">
            @foreach($pending_approvals as $approval)
            <div class="flex items-start p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-4 flex-shrink-0">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-gray-800">{{ $approval['pegawai'] }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ $approval['title'] }}</p>
                            <p class="text-xs text-gray-500 mt-2">{{ $approval['date'] }}</p>
                        </div>
                        <a href="{{ route('atasan.approval.show', $approval['id']) }}" class="px-3 py-1 bg-orange-600 hover:bg-orange-700 text-white text-sm rounded-lg transition">
                            Review
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-gray-500">Tidak ada persetujuan yang menunggu</p>
        </div>
        @endif
    </div>

    <!-- Team Performance -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Kinerja Tim</h3>
            <a href="{{ route('atasan.kinerja-bawahan.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                Lihat Detail →
            </a>
        </div>

        @if(isset($team_performance) && count($team_performance) > 0)
        <div class="space-y-4">
            @foreach($team_performance as $member)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold mr-4">
                        {{ strtoupper(substr($member['name'], 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">{{ $member['name'] }}</p>
                        <p class="text-xs text-gray-500">{{ $member['unit'] }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-lg font-bold text-gray-800">{{ $member['progress'] }}%</p>
                    <div class="w-32 h-2 bg-gray-200 rounded-full mt-2">
                        <div class="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full" style="width: {{ $member['progress'] }}%"></div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-gray-500">Belum ada data kinerja tim</p>
        </div>
        @endif
    </div>

</div>
@endsection
