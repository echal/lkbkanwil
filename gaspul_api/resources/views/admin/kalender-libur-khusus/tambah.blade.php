@extends('layouts.app')

@section('title', 'Tambah Kalender Libur Khusus - Admin')
@section('page-title', 'Tambah Kalender Libur Khusus')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah Kalender Libur Khusus</h2>
            <p class="text-sm text-gray-600 mt-1">Tambah periode libur untuk jabatan tertentu</p>
        </div>
        <a href="{{ route('admin.kalender-libur-khusus.index') }}"
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form action="{{ route('admin.kalender-libur-khusus.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Unit Kerja --}}
                <div class="md:col-span-2">
                    <label for="unit_kerja_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Unit Kerja <span class="text-red-500">*</span>
                        <span class="text-gray-400 text-xs font-normal ml-1">— pilih unit yang libur, centang "Berlaku ke Anak" untuk seluruh sub-unit</span>
                    </label>
                    <select name="unit_kerja_id" id="unit_kerja_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('unit_kerja_id') border-red-500 @enderror">
                        <option value="">-- Pilih Unit Kerja --</option>
                        @foreach($unitKerjaOptions as $opt)
                        <option value="{{ $opt['id'] }}" {{ old('unit_kerja_id') == $opt['id'] ? 'selected' : '' }}>
                            {{ $opt['label'] }}
                        </option>
                        @endforeach
                    </select>
                    @error('unit_kerja_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Target Khusus --}}
                <div>
                    <label for="target_khusus" class="block text-sm font-medium text-gray-700 mb-2">
                        Target Jabatan <span class="text-red-500">*</span>
                    </label>
                    <select name="target_khusus" id="target_khusus" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('target_khusus') border-red-500 @enderror">
                        <option value="">-- Pilih Target --</option>
                        @foreach($targetOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('target_khusus') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('target_khusus')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="status" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="DRAFT" {{ old('status', 'DRAFT') === 'DRAFT' ? 'selected' : '' }}>DRAFT (tidak aktif)</option>
                        <option value="AKTIF" {{ old('status') === 'AKTIF' ? 'selected' : '' }}>AKTIF (langsung berlaku)</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Simpan sebagai DRAFT dulu, lalu aktifkan jika sudah yakin.</p>
                </div>

                {{-- Tanggal Mulai --}}
                <div>
                    <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Mulai <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                        value="{{ old('tanggal_mulai') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('tanggal_mulai') border-red-500 @enderror">
                    @error('tanggal_mulai')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tanggal Selesai --}}
                <div>
                    <label for="tanggal_selesai" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Selesai <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai"
                        value="{{ old('tanggal_selesai') }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('tanggal_selesai') border-red-500 @enderror">
                    @error('tanggal_selesai')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Keterangan --}}
                <div class="md:col-span-2">
                    <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                        Keterangan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="keterangan" id="keterangan"
                        value="{{ old('keterangan') }}" required maxlength="255"
                        placeholder="Contoh: Libur Semester Genap 2025/2026"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent @error('keterangan') border-red-500 @enderror">
                    @error('keterangan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Berlaku ke Anak --}}
                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="berlaku_ke_anak" value="0">
                        <input type="checkbox" name="berlaku_ke_anak" value="1"
                            {{ old('berlaku_ke_anak', '1') == '1' ? 'checked' : '' }}
                            class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm font-medium text-gray-700">
                            Berlaku untuk seluruh sub-unit (anak) dari unit yang dipilih
                        </span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500 ml-8">Centang ini jika libur berlaku untuk semua madrasah/KUA di bawah unit yang dipilih.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                <button type="submit"
                    class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition">
                    Simpan
                </button>
                <a href="{{ route('admin.kalender-libur-khusus.index') }}"
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
