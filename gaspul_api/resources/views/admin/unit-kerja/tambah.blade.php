@extends('layouts.app')

@section('title', 'Tambah Unit Kerja - Admin')
@section('page-title', 'Tambah Unit Kerja')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Unit Kerja</h2>
            <p class="text-sm text-gray-600 mt-1">Tambah unit kerja baru</p>
        </div>
        <a href="{{ route('admin.unit-kerja.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form action="{{ route('admin.unit-kerja.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label for="kode_unit" class="block text-sm font-medium text-gray-700 mb-2">Kode Unit <span class="text-red-500">*</span></label>
                <input type="text" name="kode_unit" id="kode_unit" value="{{ old('kode_unit') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('kode_unit') border-red-500 @enderror"
                    placeholder="Contoh: UK001">
                @error('kode_unit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nama_unit" class="block text-sm font-medium text-gray-700 mb-2">Nama Unit <span class="text-red-500">*</span></label>
                <input type="text" name="nama_unit" id="nama_unit" value="{{ old('nama_unit') }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('nama_unit') border-red-500 @enderror"
                    placeholder="Nama unit kerja">
                @error('nama_unit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="eselon" class="block text-sm font-medium text-gray-700 mb-2">Eselon</label>
                <input type="text" name="eselon" id="eselon" value="{{ old('eselon') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('eselon') border-red-500 @enderror"
                    placeholder="Contoh: II, III, IV">
                @error('eselon')
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
                <a href="{{ route('admin.unit-kerja.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
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
