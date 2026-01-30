@extends('layouts.app')

@section('title', 'Edit Sasaran Kegiatan - Admin')
@section('page-title', 'Edit Sasaran Kegiatan')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Sasaran Kegiatan</h2>
            <p class="text-sm text-gray-600 mt-1">Edit data sasaran kegiatan</p>
        </div>
        <a href="{{ route('admin.sasaran-kegiatan.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form action="{{ route('admin.sasaran-kegiatan.update', $sasaran->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="kode_sasaran" class="block text-sm font-medium text-gray-700 mb-2">Kode Sasaran <span class="text-red-500">*</span></label>
                <input type="text" name="kode_sasaran" id="kode_sasaran" value="{{ old('kode_sasaran', $sasaran->kode_sasaran) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('kode_sasaran') border-red-500 @enderror"
                    placeholder="Contoh: SK001">
                @error('kode_sasaran')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nama_sasaran" class="block text-sm font-medium text-gray-700 mb-2">Nama Sasaran <span class="text-red-500">*</span></label>
                <input type="text" name="nama_sasaran" id="nama_sasaran" value="{{ old('nama_sasaran', $sasaran->nama_sasaran) }}" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('nama_sasaran') border-red-500 @enderror"
                    placeholder="Nama sasaran kegiatan">
                @error('nama_sasaran')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-2">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsi" rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('deskripsi') border-red-500 @enderror"
                    placeholder="Deskripsi sasaran kegiatan">{{ old('deskripsi', $sasaran->deskripsi) }}</textarea>
                @error('deskripsi')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                <select name="status" id="status" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('status') border-red-500 @enderror">
                    <option value="AKTIF" {{ old('status', $sasaran->status) === 'AKTIF' ? 'selected' : '' }}>AKTIF</option>
                    <option value="NONAKTIF" {{ old('status', $sasaran->status) === 'NONAKTIF' ? 'selected' : '' }}>NONAKTIF</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4">
                <a href="{{ route('admin.sasaran-kegiatan.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
