@extends('layouts.app')

@section('title', 'Form Rencana Aksi - Rencana Hasil Kerja')
@section('page-title', 'Form Rencana Aksi')

@section('content')
@php
    // Array nama bulan dalam Bahasa Indonesia
    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $namaBulanText = $namaBulan[$rencana->bulan] ?? 'Unknown';
@endphp

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Form Rencana Aksi</h2>
            <p class="text-sm text-gray-600 mt-1">{{ $namaBulanText }} {{ $rencana->tahun }}</p>
        </div>
        <a href="{{ route('asn.rencana-kerja.index', ['tahun' => $rencana->tahun]) }}"
           class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-semibold mb-1">Informasi:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Rencana aksi bulanan untuk <strong>{{ $namaBulanText }} {{ $rencana->tahun }}</strong></li>
                    <li>Pastikan data yang diubah sudah sesuai</li>
                    <li>Semua field wajib diisi</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('asn.rencana-kerja.update', $rencana->id) }}"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">

        @csrf
        @method('PUT')

        <!-- Uraian Rencana Aksi -->
        <div>
            <label for="rencana_aksi_bulanan" class="block text-sm font-medium text-gray-700 mb-2">
                Uraian Rencana Aksi <span class="text-red-500">*</span>
            </label>
            <textarea id="rencana_aksi_bulanan"
                      name="rencana_aksi_bulanan"
                      rows="6"
                      required
                      class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none @error('rencana_aksi_bulanan') border-red-500 @enderror"
                      placeholder="Contoh: Melakukan monitoring dan evaluasi pelaksanaan program pendidikan di wilayah Kabupaten Majene dan Kabupaten Mamuju Tengah">{{ old('rencana_aksi_bulanan', $rencana->rencana_aksi_bulanan) }}</textarea>
            @error('rencana_aksi_bulanan')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">
                Jelaskan secara detail rencana aksi yang akan dilakukan pada bulan ini
            </p>
        </div>

        <!-- Target & Satuan -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Target -->
            <div>
                <label for="target_bulanan" class="block text-sm font-medium text-gray-700 mb-2">
                    Target Bulanan <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       id="target_bulanan"
                       name="target_bulanan"
                       required
                       min="1"
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('target_bulanan') border-red-500 @enderror"
                       placeholder="Contoh: 2"
                       value="{{ old('target_bulanan', $rencana->target_bulanan) }}">
                @error('target_bulanan')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Target yang ingin dicapai dalam bulan ini</p>
            </div>

            <!-- Satuan -->
            <div>
                <label for="satuan_target" class="block text-sm font-medium text-gray-700 mb-2">
                    Satuan Target <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="satuan_target"
                       name="satuan_target"
                       required
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('satuan_target') border-red-500 @enderror"
                       placeholder="Contoh: Laporan / Dokumen / Kegiatan"
                       value="{{ old('satuan_target', $rencana->satuan_target) }}">
                @error('satuan_target')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Satuan untuk mengukur target bulanan</p>
            </div>
        </div>

        <!-- Info Realisasi -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Realisasi Bulanan Saat Ini</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $rencana->realisasi_bulanan ?? 0 }} {{ $rencana->satuan_target }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Terakhir Diupdate</p>
                    <p class="text-sm font-semibold text-gray-900">
                        {{ $rencana->updated_at ? $rencana->updated_at->locale('id')->diffForHumans() : '-' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
            <a href="{{ route('asn.rencana-kerja.index', ['tahun' => $rencana->tahun]) }}"
               class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg transition font-medium shadow-lg hover:shadow-xl">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Update Rencana Aksi
                </span>
            </button>
        </div>

    </form>

</div>
@endsection
