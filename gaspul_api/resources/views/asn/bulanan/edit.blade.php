@extends('layouts.app')

@section('title', 'Edit Rencana Aksi Bulanan')
@section('page-title', 'Edit Rencana Aksi Bulanan')

@section('content')
<div class="space-y-6">

    <!-- Breadcrumb -->
    <div class="flex items-center text-sm text-gray-600">
        <a href="{{ route('asn.bulanan.index', ['tahun' => $rencanaAksi->tahun]) }}" class="hover:text-blue-600">
            Rencana Aksi Bulanan
        </a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Edit - {{ $rencanaAksi->bulan_nama }} {{ $rencanaAksi->tahun }}</span>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-blue-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-lg font-semibold text-blue-800 mb-1">Periode: {{ $rencanaAksi->bulan_nama }} {{ $rencanaAksi->tahun }}</h3>
                <p class="text-sm text-blue-700 mb-2"><strong>Indikator Kinerja:</strong> {{ $rencanaAksi->skpTahunanDetail->indikatorKinerja->nama_indikator ?? '-' }}</p>
                <p class="text-sm text-blue-700"><strong>Rencana Aksi ASN:</strong> {{ $rencanaAksi->skpTahunanDetail->rencana_aksi }}</p>
            </div>
        </div>
    </div>

    <!-- Form Edit -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Form Rencana Aksi Bulanan</h3>
        </div>

        <form method="POST" action="{{ route('asn.bulanan.update', $rencanaAksi->id) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Rencana Aksi Bulanan -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Rencana Aksi Bulanan <span class="text-red-500">*</span>
                </label>
                <textarea name="rencana_aksi_bulanan" rows="4" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('rencana_aksi_bulanan') border-red-500 @enderror"
                    placeholder="Jelaskan rencana aksi yang akan dilakukan pada bulan ini...">{{ old('rencana_aksi_bulanan', $rencanaAksi->rencana_aksi_bulanan) }}</textarea>
                @error('rencana_aksi_bulanan')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-sm text-gray-500">Contoh: Menyusun laporan evaluasi kinerja triwulan I</p>
            </div>

            <!-- Target Bulanan & Satuan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Target Bulanan <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="target_bulanan" min="0" required
                        value="{{ old('target_bulanan', $rencanaAksi->target_bulanan) }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('target_bulanan') border-red-500 @enderror"
                        placeholder="Contoh: 2">
                    @error('target_bulanan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Satuan
                    </label>
                    <input type="text" name="satuan_target"
                        value="{{ old('satuan_target', $rencanaAksi->satuan_target ?? $rencanaAksi->skpTahunanDetail->satuan) }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Contoh: Laporan / Dokumen / Kegiatan">
                    <p class="mt-2 text-sm text-gray-500">Default: {{ $rencanaAksi->skpTahunanDetail->satuan }}</p>
                </div>
            </div>

            <!-- Info Realisasi -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <p class="text-sm text-gray-700 mb-2"><strong>Realisasi Saat Ini:</strong> {{ $rencanaAksi->realisasi_bulanan }}</p>
                <p class="text-sm text-gray-600">
                    Realisasi akan otomatis dihitung dari <strong>Progres Harian</strong> yang Anda input.
                    Tidak perlu diisi secara manual di sini.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('asn.bulanan.index', ['tahun' => $rencanaAksi->tahun]) }}"
                   class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    Simpan Rencana Aksi
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
