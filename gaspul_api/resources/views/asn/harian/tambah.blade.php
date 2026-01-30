@extends('layouts.app')

@section('title', 'Tambah Kinerja Harian - Laporan Harian')
@section('page-title', 'Tambah Kinerja Harian')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Kinerja Harian</h2>
            <p class="text-sm text-gray-600 mt-1">Input laporan kinerja harian Anda</p>
        </div>
        <a href="{{ route('asn.harian.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </div>

    <!-- Form Placeholder -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12">
        <div class="text-center">
            <svg class="w-24 h-24 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Form Dalam Pengembangan</h3>
            <p class="text-gray-600 mb-6">Form input kinerja harian akan segera tersedia</p>
            <p class="text-sm text-gray-500">UI Layer sudah selesai. Form implementation akan dilakukan di Tahap 2.</p>
        </div>
    </div>

</div>
@endsection
