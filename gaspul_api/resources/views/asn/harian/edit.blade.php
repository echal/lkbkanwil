@extends('layouts.app')

@section('title', 'Edit Kinerja Harian - Laporan Harian')
@section('page-title', 'Edit Kinerja Harian')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit Kinerja Harian</h2>
            <p class="text-sm text-gray-600 mt-1">{{ \Carbon\Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM Y') }}</p>
        </div>
        <a href="{{ route('asn.harian.index', ['date' => $date]) }}"
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
                    <li>Anda dapat menyimpan tanpa link bukti (status: <span class="font-semibold text-red-600">🔴 MERAH</span>)</li>
                    <li>Upload link bukti maksimal sampai jam 23:59 hari ini</li>
                    <li>Jam mulai harus lebih awal dari jam selesai</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('asn.harian.update', $entry->id) }}"
          x-data="{
              jamMulai: '{{ \Carbon\Carbon::parse($entry->jam_mulai)->format('H:i') }}',
              jamSelesai: '{{ \Carbon\Carbon::parse($entry->jam_selesai)->format('H:i') }}',
              get durasiValid() {
                  if (!this.jamMulai || !this.jamSelesai) return true;
                  return this.jamMulai < this.jamSelesai;
              }
          }"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">

        @csrf
        @method('PUT')

        <!-- Hidden field untuk tanggal -->
        <input type="hidden" name="tanggal" value="{{ $date }}">

        <!-- Rencana Aksi Bulanan -->
        <div>
            <label for="rencana_kerja_id" class="block text-sm font-medium text-gray-700 mb-2">
                Rencana Aksi Bulanan <span class="text-gray-400 text-xs">(Opsional)</span>
            </label>
            <select id="rencana_kerja_id"
                    name="rencana_kerja_id"
                    class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition @error('rencana_kerja_id') border-red-500 @enderror">
                <option value="">-- Pilih Rencana Aksi (Opsional) --</option>
                @foreach($rencanaKerja as $rencana)
                    <option value="{{ $rencana['id'] }}" {{ old('rencana_kerja_id', $entry->rencana_aksi_bulanan_id ?? '') == $rencana['id'] ? 'selected' : '' }}>
                        {{ Str::limit($rencana['rencana_aksi_bulanan'], 80) }}
                    </option>
                @endforeach
            </select>
            @error('rencana_kerja_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">
                Pilih rencana kerja bulanan yang terkait dengan kegiatan ini (jika ada)
            </p>
        </div>

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
                       value="{{ old('jam_mulai', \Carbon\Carbon::parse($entry->jam_mulai)->format('H:i')) }}">
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
                       value="{{ old('jam_selesai', \Carbon\Carbon::parse($entry->jam_selesai)->format('H:i')) }}">
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

        <!-- Kegiatan Harian -->
        <div>
            <label for="kegiatan_harian" class="block text-sm font-medium text-gray-700 mb-2">
                Kegiatan Harian <span class="text-red-500">*</span>
            </label>
            <textarea id="kegiatan_harian"
                      name="kegiatan_harian"
                      rows="4"
                      required
                      class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"
                      placeholder="Contoh: Menyusun laporan evaluasi kegiatan Januari 2026">{{ old('kegiatan_harian', $entry->rencana_kegiatan_harian) }}</textarea>
            @error('kegiatan_harian')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Progres & Satuan -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Progres -->
            <div>
                <label for="progres" class="block text-sm font-medium text-gray-700 mb-2">
                    Progres <span class="text-red-500">*</span>
                </label>
                <input type="number"
                       id="progres"
                       name="progres"
                       required
                       min="0"
                       step="0.01"
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                       placeholder="Contoh: 1"
                       value="{{ old('progres', $entry->progres) }}">
                @error('progres')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Jumlah output yang dihasilkan</p>
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
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                       placeholder="Contoh: Dokumen"
                       value="{{ old('satuan', $entry->satuan) }}">
                @error('satuan')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Satuan dari progres (Dokumen, File, Orang, dll)</p>
            </div>
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
                   value="{{ old('link_bukti', $entry->bukti_dukung) }}">
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
                      placeholder="Catatan tambahan...">{{ old('keterangan', $entry->keterangan ?? '') }}</textarea>
            @error('keterangan')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
            <a href="{{ route('asn.harian.index', ['date' => $date]) }}"
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
                    Update Kinerja Harian
                </span>
            </button>
        </div>

    </form>

</div>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
