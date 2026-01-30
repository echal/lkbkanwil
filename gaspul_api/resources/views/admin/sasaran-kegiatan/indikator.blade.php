@extends('layouts.app')

@section('title', 'Indikator Sasaran - Laporan Harian')
@section('page-title', 'Indikator Sasaran')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Indikator Sasaran</h2>
        <p class="text-sm text-gray-600 mt-1">Halaman Indikator Sasaran</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <svg class="w-24 h-24 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Halaman Dalam Pengembangan</h3>
        <p class="text-sm text-gray-500">UI Layer sudah selesai. Implementasi akan dilakukan di Tahap 2.</p>
    </div>
</div>
@endsection
