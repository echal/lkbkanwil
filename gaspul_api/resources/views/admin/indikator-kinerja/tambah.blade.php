@extends('layouts.app')

@section('title', 'Tambah Indikator Kinerja - Admin')
@section('page-title', 'Tambah Indikator Kinerja')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Indikator Kinerja</h2>
            <p class="text-sm text-gray-600 mt-1">Tambah indikator kinerja baru</p>
        </div>
        <a href="{{ route('admin.indikator-kinerja.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form action="{{ route('admin.indikator-kinerja.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label for="sasaran_kegiatan_id" class="block text-sm font-medium text-gray-700 mb-2">Sasaran Kegiatan <span class="text-red-500">*</span></label>
                <select name="sasaran_kegiatan_id" id="sasaran_kegiatan_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('sasaran_kegiatan_id') border-red-500 @enderror">
                    <option value="">Pilih Sasaran Kegiatan</option>
                    @foreach($sasaran as $item)
                        <option value="{{ $item->id }}" {{ old('sasaran_kegiatan_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->kode_sasaran }} - {{ $item->nama_sasaran }}
                        </option>
                    @endforeach
                </select>
                @error('sasaran_kegiatan_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="unit_kerja_id" class="block text-sm font-medium text-gray-700 mb-2">Unit Kerja (Opsional)</label>
                <select name="unit_kerja_id" id="unit_kerja_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('unit_kerja_id') border-red-500 @enderror">
                    <option value="">-- Semua Unit (Global) --</option>
                    @foreach($unitKerja as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_kerja_id') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->nama_unit }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Jika kosong, Indikator Kinerja ini bisa digunakan oleh semua unit kerja</p>
                @error('unit_kerja_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="kode_indikator" class="block text-sm font-medium text-gray-700 mb-2">Kode Indikator <span class="text-red-500">*</span></label>
                <input type="text" name="kode_indikator" id="kode_indikator" value="{{ old('kode_indikator') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('kode_indikator') border-red-500 @enderror"
                    placeholder="Contoh: IK001">
                @error('kode_indikator')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nama_indikator" class="block text-sm font-medium text-gray-700 mb-2">Nama Indikator Kinerja (RHK Pimpinan) <span class="text-red-500">*</span></label>
                <textarea name="nama_indikator" id="nama_indikator" rows="3" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('nama_indikator') border-red-500 @enderror"
                    placeholder="Nama indikator kinerja / RHK yang akan dipilih ASN">{{ old('nama_indikator') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Ini adalah RHK yang akan dipilih ASN saat membuat SKP Tahunan</p>
                @error('nama_indikator')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="satuan" class="block text-sm font-medium text-gray-700 mb-2">Satuan <span class="text-red-500">*</span></label>
                <input type="text" name="satuan" id="satuan" value="{{ old('satuan') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('satuan') border-red-500 @enderror"
                    placeholder="Contoh: Dokumen, Kegiatan, Laporan">
                @error('satuan')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="tipe_target" class="block text-sm font-medium text-gray-700 mb-2">Tipe Target <span class="text-red-500">*</span></label>
                <select name="tipe_target" id="tipe_target" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('tipe_target') border-red-500 @enderror">
                    <option value="ANGKA" {{ old('tipe_target') === 'ANGKA' ? 'selected' : '' }}>ANGKA</option>
                    <option value="DOKUMEN" {{ old('tipe_target') === 'DOKUMEN' ? 'selected' : '' }}>DOKUMEN</option>
                    <option value="PERSENTASE" {{ old('tipe_target') === 'PERSENTASE' ? 'selected' : '' }}>PERSENTASE</option>
                </select>
                @error('tipe_target')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                <select name="status" id="status" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('status') border-red-500 @enderror">
                    <option value="AKTIF" {{ old('status') === 'AKTIF' ? 'selected' : '' }}>AKTIF</option>
                    <option value="NONAKTIF" {{ old('status') === 'NONAKTIF' ? 'selected' : '' }}>NONAKTIF</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4">
                <a href="{{ route('admin.indikator-kinerja.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
