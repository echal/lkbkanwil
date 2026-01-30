@extends('layouts.app')

@section('title', 'Form Tugas Langsung Atasan - Laporan Harian')
@section('page-title', 'Form Tugas Langsung Atasan')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Form Tugas Langsung Atasan</h2>
            <p class="text-sm text-gray-600 mt-1">{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
        </div>
        <a href="{{ route('asn.harian.pilih') }}"
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
                <p class="font-semibold mb-1">Informasi Penting:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Input tugas tambahan atau insidental dari atasan</li>
                    <li>Anda dapat menyimpan tanpa link bukti (status: <span class="font-semibold text-red-600">🔴 MERAH</span>)</li>
                    <li>Upload link bukti maksimal sampai jam 23:59 hari ini</li>
                    <li>Jam kerja boleh overlap dengan kinerja harian</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('asn.harian.store-tla') }}"
          x-data="{
              jamMulai: '',
              jamSelesai: '',
              get durasiValid() {
                  if (!this.jamMulai || !this.jamSelesai) return true;
                  return this.jamMulai < this.jamSelesai;
              }
          }"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">

        @csrf

        <!-- Hidden field untuk tanggal -->
        <input type="hidden" name="tanggal" value="{{ request('date', now()->format('Y-m-d')) }}">

        <!-- Jam Mulai & Selesai -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Jam Mulai -->
            <div>
                <label for="jam_mulai" class="block text-sm font-medium text-gray-700 mb-2">
                    Jam Mulai <span class="text-red-500">*</span>
                </label>
                <input type="time"
                       id="jam_mulai"
                       name="jam_mulai"
                       x-model="jamMulai"
                       required
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                       value="{{ old('jam_mulai') }}">
                @error('jam_mulai')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Jam Selesai -->
            <div>
                <label for="jam_selesai" class="block text-sm font-medium text-gray-700 mb-2">
                    Jam Selesai <span class="text-red-500">*</span>
                </label>
                <input type="time"
                       id="jam_selesai"
                       name="jam_selesai"
                       x-model="jamSelesai"
                       required
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                       value="{{ old('jam_selesai') }}">
                @error('jam_selesai')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <!-- Validation Warning -->
                <p x-show="!durasiValid"
                   x-cloak
                   class="mt-1 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Jam selesai harus lebih besar dari jam mulai
                </p>
            </div>
        </div>

        <!-- Tugas Langsung Atasan -->
        <div>
            <label for="tugas_langsung_atasan" class="block text-sm font-medium text-gray-700 mb-2">
                Tugas Langsung Atasan <span class="text-red-500">*</span>
            </label>
            <textarea id="tugas_langsung_atasan"
                      name="tugas_langsung_atasan"
                      rows="4"
                      required
                      class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"
                      placeholder="Contoh: Menyiapkan kelengkapan dokumen untuk rapat evaluasi kinerja bulan Januari 2026">{{ old('tugas_langsung_atasan') }}</textarea>
            @error('tugas_langsung_atasan')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">
                Deskripsikan tugas tambahan yang diberikan langsung oleh atasan Anda
            </p>
        </div>

        <!-- Link Bukti -->
        <div>
            <label for="link_bukti" class="block text-sm font-medium text-gray-700 mb-2">
                Link Bukti <span class="text-gray-400 text-xs">(Opsional)</span>
            </label>
            <input type="url"
                   id="link_bukti"
                   name="link_bukti"
                   class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                   placeholder="https://drive.google.com/... atau https://..."
                   value="{{ old('link_bukti') }}">
            @error('link_bukti')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">
                Link Google Drive, foto, atau dokumen pendukung.
                <span class="font-semibold text-orange-600">Bisa di-upload nanti sampai jam 23:59</span>
            </p>
        </div>

        <!-- Keterangan -->
        <div>
            <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                Keterangan <span class="text-gray-400 text-xs">(Opsional)</span>
            </label>
            <textarea id="keterangan"
                      name="keterangan"
                      rows="3"
                      class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"
                      placeholder="Catatan tambahan atau konteks tugas...">{{ old('keterangan') }}</textarea>
            @error('keterangan')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Status Preview -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-700 mb-2">Status Setelah Simpan:</h4>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    🔴 MERAH - Belum Upload Bukti
                </span>
                <span class="text-sm text-gray-600">
                    (Progres bar tetap merah sampai link bukti di-upload)
                </span>
            </div>
        </div>

        <!-- Note Box -->
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-amber-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-amber-800">
                    <p class="font-semibold mb-1">Catatan:</p>
                    <p>Tugas langsung atasan tidak mempengaruhi perhitungan progres SKP Tahunan, namun tetap dihitung dalam total durasi harian Anda.</p>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
            <a href="{{ route('asn.harian.index') }}"
               class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium">
                Batal
            </a>
            <button type="submit"
                    :disabled="!durasiValid"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg transition font-medium shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Tugas Langsung
                </span>
            </button>
        </div>

    </form>

</div>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
