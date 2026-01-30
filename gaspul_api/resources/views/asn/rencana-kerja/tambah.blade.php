@extends('layouts.app')

@section('title', 'Tambah Rencana Kerja - Laporan Harian')
@section('page-title', 'Tambah Rencana Kerja')

@section('content')
@php
    // Get bulan from query string
    $bulan = request('bulan', now()->month);
    $tahun = request('tahun', now()->year);

    // Array nama bulan dalam Bahasa Indonesia
    $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $namaBulanText = $namaBulan[$bulan] ?? 'Unknown';
@endphp

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Rencana Kerja</h2>
            <p class="text-sm text-gray-600 mt-1">{{ $namaBulanText }} {{ $tahun }}</p>
        </div>
        <a href="{{ route('asn.rencana-kerja.index', ['tahun' => $tahun]) }}"
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
                    <li>Isi rencana kerja untuk bulan <strong>{{ $namaBulanText }} {{ $tahun }}</strong></li>
                    <li>Jelaskan kegiatan yang akan dilakukan secara detail</li>
                    <li>Semua field wajib diisi</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('asn.rencana-kerja.store') }}"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">

        @csrf

        <!-- Hidden fields -->
        <input type="hidden" name="bulan" value="{{ $bulan }}">
        <input type="hidden" name="tahun" value="{{ $tahun }}">

        <!-- Uraian Rencana Kerja -->
        <div>
            <label for="uraian" class="block text-sm font-medium text-gray-700 mb-2">
                Uraian Rencana Kerja <span class="text-red-500">*</span>
            </label>
            <textarea id="uraian"
                      name="uraian"
                      rows="6"
                      required
                      class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none @error('uraian') border-red-500 @enderror"
                      placeholder="Contoh: Melakukan monitoring dan evaluasi pelaksanaan program pendidikan di wilayah Kabupaten Majene dan Kabupaten Mamuju Tengah">{{ old('uraian') }}</textarea>
            @error('uraian')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">
                Jelaskan secara detail kegiatan yang akan dilakukan pada bulan ini
            </p>
        </div>

        <!-- Target & Satuan -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Target -->
            <div>
                <label for="target" class="block text-sm font-medium text-gray-700 mb-2">
                    Target <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       id="target"
                       name="target"
                       required
                       min="1"
                       step="0.01"
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('target') border-red-500 @enderror"
                       placeholder="Contoh: 2"
                       value="{{ old('target') }}">
                @error('target')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Target yang ingin dicapai dalam bulan ini</p>
            </div>

            <!-- Satuan -->
            <div>
                <label for="satuan" class="block text-sm font-medium text-gray-700 mb-2">
                    Satuan <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="satuan"
                       name="satuan"
                       required
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('satuan') border-red-500 @enderror"
                       placeholder="Contoh: Laporan / Dokumen / Kegiatan"
                       value="{{ old('satuan') }}">
                @error('satuan')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Satuan untuk mengukur target</p>
            </div>
        </div>

        <!-- Keterangan -->
        <div>
            <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                Keterangan <span class="text-gray-400 text-xs">(Opsional)</span>
            </label>
            <textarea id="keterangan"
                      name="keterangan"
                      rows="3"
                      class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none @error('keterangan') border-red-500 @enderror"
                      placeholder="Catatan tambahan...">{{ old('keterangan') }}</textarea>
            @error('keterangan')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Preview Info -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Informasi:</h4>
            <p class="text-sm text-gray-600">
                Rencana kerja ini akan ditampilkan di kalender bulan <strong>{{ $namaBulanText }} {{ $tahun }}</strong>.
                Anda dapat mengeditnya kapan saja sebelum periode penilaian berakhir.
            </p>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
            <a href="{{ route('asn.rencana-kerja.index', ['tahun' => $tahun]) }}"
               class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg transition font-medium shadow-lg hover:shadow-xl">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Rencana Kerja
                </span>
            </button>
        </div>

    </form>

</div>
@endsection
