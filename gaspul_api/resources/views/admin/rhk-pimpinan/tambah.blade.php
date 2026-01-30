@extends('layouts.app')

@section('title', 'Tambah RHK Pimpinan - Admin')
@section('page-title', 'Tambah RHK Pimpinan')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah RHK Pimpinan</h2>
            <p class="text-sm text-gray-600 mt-1">Buat Rencana Hasil Kerja Pimpinan baru</p>
        </div>
        <a href="{{ route('admin.rhk-pimpinan.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>
    </div>

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg" x-data="{show: true}" x-show="show" x-transition>
        <div class="flex items-center justify-between">
            <span>{{ session('error') }}</span>
            <button @click="show = false" class="text-red-700 hover:text-red-900">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form action="{{ route('admin.rhk-pimpinan.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Unit Kerja -->
            <div>
                <label for="unit_kerja_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Unit Kerja <span class="text-red-500">*</span>
                </label>
                <select name="unit_kerja_id" id="unit_kerja_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('unit_kerja_id') border-red-500 @enderror" required>
                    <option value="">Pilih Unit Kerja</option>
                    @foreach($unitKerjaList as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_kerja_id') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->nama_unit }}
                        </option>
                    @endforeach
                </select>
                @error('unit_kerja_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Indikator Kinerja -->
            <div>
                <label for="indikator_kinerja_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Indikator Kinerja <span class="text-red-500">*</span>
                </label>
                <select name="indikator_kinerja_id" id="indikator_kinerja_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('indikator_kinerja_id') border-red-500 @enderror" required>
                    <option value="">Pilih Indikator Kinerja</option>
                    @foreach($indikatorList as $indikator)
                        <option value="{{ $indikator->id }}" {{ old('indikator_kinerja_id') == $indikator->id ? 'selected' : '' }}>
                            {{ $indikator->nama_indikator }}
                        </option>
                    @endforeach
                </select>
                @error('indikator_kinerja_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- RHK Pimpinan -->
            <div>
                <label for="rhk_pimpinan" class="block text-sm font-medium text-gray-700 mb-2">
                    RHK Pimpinan <span class="text-red-500">*</span>
                </label>
                <textarea name="rhk_pimpinan" id="rhk_pimpinan" rows="4" maxlength="1000" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 @error('rhk_pimpinan') border-red-500 @enderror" placeholder="Masukkan Rencana Hasil Kerja Pimpinan..." required>{{ old('rhk_pimpinan') }}</textarea>
                <div class="flex justify-between mt-1">
                    @error('rhk_pimpinan')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @else
                        <p class="text-sm text-gray-500">Maksimal 1000 karakter</p>
                    @enderror
                    <p class="text-sm text-gray-500">
                        <span id="charCount">0</span>/1000
                    </p>
                </div>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status <span class="text-red-500">*</span>
                </label>
                <div class="flex space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="status" value="AKTIF" class="form-radio text-green-600 focus:ring-green-500" {{ old('status', 'AKTIF') == 'AKTIF' ? 'checked' : '' }} required>
                        <span class="ml-2 text-sm text-gray-700">AKTIF</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="status" value="NONAKTIF" class="form-radio text-red-600 focus:ring-red-500" {{ old('status') == 'NONAKTIF' ? 'checked' : '' }}>
                        <span class="ml-2 text-sm text-gray-700">NONAKTIF</span>
                    </label>
                </div>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('admin.rhk-pimpinan.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Character counter
    const textarea = document.getElementById('rhk_pimpinan');
    const charCount = document.getElementById('charCount');

    function updateCount() {
        charCount.textContent = textarea.value.length;
    }

    textarea.addEventListener('input', updateCount);
    updateCount();
</script>
@endpush
@endsection
