@extends('layouts.app')

@section('title', 'Form Kinerja Harian - Laporan Harian')
@section('page-title', 'Form Kinerja Harian')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Form Kinerja Harian</h2>
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
    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-green-800">
                <p class="font-semibold mb-1">Informasi Penting:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Anda dapat menyimpan tanpa link bukti (status: <span class="font-semibold text-red-600">🔴 MERAH</span>)</li>
                    <li>Upload link bukti maksimal sampai jam 23:59 hari ini</li>
                    <li>Jam mulai harus lebih awal dari jam selesai</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- WARNING B: ASN belum punya RAB aktif bulan ini (server-rendered, permanent) --}}
    @if(!$hasRabAktif)
    <div class="p-4 bg-red-50 border border-red-300 rounded-lg flex items-start gap-3">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
            <p class="text-sm font-semibold text-red-700">Belum Ada Rencana Aksi Bulanan</p>
            <p class="text-sm text-red-600 mt-1">
                Anda belum memiliki Rencana Aksi Bulanan untuk bulan ini.
                Progres yang diinput tidak akan terhitung ke capaian SKP.
            </p>
            <a href="{{ route('asn.skp-tahunan.index') }}"
               class="inline-block mt-2 text-sm font-semibold text-red-700 underline underline-offset-2 hover:text-red-900">
                &rarr; Buat Rencana Aksi Bulanan di menu SKP Tahunan
            </a>
        </div>
    </div>
    @endif

    <!-- Form -->
    <form method="POST" action="{{ route('asn.harian.store-kinerja') }}"
          x-data="formKinerja()"
          x-ref="kinerjaForm"
          @submit="handleSubmit($event)"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">

        @csrf

        <!-- Hidden field untuk tanggal -->
        <input type="hidden" name="tanggal" value="{{ request('date', now()->format('Y-m-d')) }}">

        <!-- Rencana Aksi Bulanan -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Rencana Aksi Bulanan <span class="text-gray-400 text-xs">(Opsional)</span>
            </label>

            {{-- Hidden input — nilai yang dikirim ke server (tidak berubah) --}}
            <input type="hidden" name="rencana_kerja_id" :value="selectedRabId">

            {{-- Custom dropdown Alpine.js — mendukung teks multi-baris --}}
            <div class="relative" x-data="{ openRab: false }" @click.outside="openRab = false">

                {{-- Trigger button --}}
                <button type="button"
                        @click="openRab = !openRab"
                        class="w-full px-4 py-3 border rounded-lg text-left flex items-start justify-between gap-2 transition focus:outline-none focus:ring-2 focus:ring-green-500
                               {{ $errors->has('rencana_kerja_id') ? 'border-red-500 bg-red-50' : 'border-gray-300 bg-white hover:border-gray-400' }}">
                    <span class="text-sm leading-snug"
                          :class="selectedRabId ? 'text-gray-900' : 'text-gray-400'"
                          x-text="selectedRabId && rabData[selectedRabId]
                                  ? rabData[selectedRabId].bulan + ' — ' + rabData[selectedRabId].indikator_kinerja + (rabData[selectedRabId].rencana_aksi_bulanan ? ' — ' + rabData[selectedRabId].rencana_aksi_bulanan : '')
                                  : '-- Pilih Rencana Aksi (Opsional) --'">
                    </span>
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-gray-400 transition-transform duration-150"
                         :class="openRab ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Dropdown list --}}
                <div x-show="openRab"
                     x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute z-40 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-72 overflow-y-auto">

                    {{-- Opsi kosong --}}
                    <button type="button"
                            @click="selectedRabId = ''; updateGuidance(''); openRab = false"
                            class="w-full text-left px-4 py-3 text-sm text-gray-400 hover:bg-gray-50 border-b border-gray-100 transition">
                        -- Pilih Rencana Aksi (Opsional) --
                    </button>

                    @foreach($rencanaKerja as $rencana)
                    <button type="button"
                            @click="selectedRabId = '{{ $rencana['id'] }}'; updateGuidance('{{ $rencana['id'] }}'); openRab = false"
                            class="w-full text-left px-4 py-3 text-sm hover:bg-green-50 transition border-b border-gray-50 last:border-0"
                            :class="selectedRabId == '{{ $rencana['id'] }}' ? 'bg-green-50 text-green-800 font-medium' : 'text-gray-800'">
                        <span class="block font-medium text-green-700 text-xs mb-0.5">{{ $rencana['bulan'] }}</span>
                        <span class="block text-gray-800 leading-snug">{{ $rencana['indikator_kinerja'] }}</span>
                        @if(!empty($rencana['rencana_aksi_bulanan']))
                        <span class="block text-gray-500 text-xs leading-snug mt-0.5">{{ $rencana['rencana_aksi_bulanan'] }}</span>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>

            @error('rencana_kerja_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @if($rencanaKerja->isEmpty())
                <p class="mt-1 text-xs text-red-600 font-semibold">
                    ⚠ Tidak ada Rencana Aksi Bulanan untuk bulan ini. Pastikan Anda sudah mengisi Rencana Aksi Bulanan di menu SKP Tahunan.
                </p>
            @else
                <p class="mt-1 text-xs text-gray-500">
                    Pilih rencana kerja bulanan yang terkait dengan kegiatan ini (jika ada)
                </p>
            @endif

            {{-- WARNING A: punya RAB tapi belum dipilih (Alpine, dismissable) --}}
            @if($hasRabAktif)
            <div x-show="selectedRab === null && !warnADismissed"
                 x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="mt-2 flex items-start gap-2 bg-amber-50 border border-amber-300 rounded-lg px-3 py-2.5">
                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <p class="text-xs text-amber-800 flex-1">
                    <span class="font-semibold">Pilih Rencana Aksi Bulanan</span> agar progres ini tercatat ke capaian SKP Anda.
                </p>
                <button type="button"
                        @click="warnADismissed = true"
                        class="text-amber-400 hover:text-amber-700 transition ml-1 flex-shrink-0"
                        title="Tutup pesan ini">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
            @endif

            {{-- =============================================
                 GUIDANCE PANEL — muncul saat RAB dipilih
                 Pure Alpine.js, no AJAX, no new query
                 ============================================= --}}
            <div x-show="selectedRab !== null"
                 x-cloak
                 class="mt-3 rounded-lg border p-4 transition-all duration-200"
                 :class="isOverTarget ? 'bg-amber-50 border-amber-300' : 'bg-blue-50 border-blue-200'">

                {{-- Judul panel --}}
                <p class="text-xs font-semibold mb-3 flex items-center gap-1"
                   :class="isOverTarget ? 'text-amber-800' : 'text-blue-800'">
                    <span x-show="!isOverTarget">📊 Progress Target Bulan Ini</span>
                    <span x-show="isOverTarget">✅ Target Sudah Tercapai</span>
                </p>

                {{-- Grid: Target | Realisasi | Sisa --}}
                <div class="grid grid-cols-3 gap-2 mb-3 text-center">
                    <div class="bg-white rounded border border-gray-200 p-2">
                        <div class="text-[10px] text-gray-500 mb-0.5">Target</div>
                        <div class="text-sm font-bold text-gray-800"
                             x-text="selectedRab ? formatNum(selectedRab.target_bulanan) : '-'"></div>
                        <div class="text-[10px] text-gray-500"
                             x-text="selectedRab ? selectedRab.satuan_target : ''"></div>
                    </div>
                    <div class="bg-white rounded border border-gray-200 p-2">
                        <div class="text-[10px] text-gray-500 mb-0.5">Realisasi</div>
                        <div class="text-sm font-bold"
                             :class="isOverTarget ? 'text-green-700' : 'text-blue-700'"
                             x-text="selectedRab ? formatNum(selectedRab.realisasi_bulanan) : '-'"></div>
                        <div class="text-[10px] text-gray-500"
                             x-text="selectedRab ? selectedRab.satuan_target : ''"></div>
                    </div>
                    <div class="bg-white rounded border border-gray-200 p-2">
                        <div class="text-[10px] text-gray-500 mb-0.5">Sisa</div>
                        <div class="text-sm font-bold"
                             :class="isOverTarget ? 'text-green-600' : 'text-orange-600'"
                             x-text="isOverTarget ? 'Tercapai ✓' : formatNum(sisaTarget)"></div>
                        <div class="text-[10px] text-gray-500"
                             x-show="!isOverTarget"
                             x-text="selectedRab ? selectedRab.satuan_target : ''"></div>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div class="mb-3">
                    <div class="flex justify-between text-[10px] mb-1"
                         :class="isOverTarget ? 'text-amber-700' : 'text-blue-700'">
                        <span>Progress</span>
                        <span x-text="progressPersen + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full transition-all duration-300"
                             :class="isOverTarget ? 'bg-green-500' : 'bg-blue-500'"
                             :style="'width: ' + progressPersen + '%'"></div>
                    </div>
                </div>

                {{-- Saran per hari (hanya jika belum tercapai dan ada sisa hari) --}}
                <div x-show="!isOverTarget && sisaHariKerja > 0"
                     class="flex items-center justify-between text-[11px] text-blue-700 border-t border-blue-200 pt-2">
                    <span>
                        <span class="font-medium">Hari kerja tersisa:</span>
                        <span x-text="sisaHariKerja + ' hari'"></span>
                    </span>
                    <span>
                        <span class="font-medium">Saran:</span>
                        <span x-text="formatNum(saranPerHari) + ' ' + (selectedRab ? selectedRab.satuan_target : '') + '/hari'"></span>
                    </span>
                </div>

                {{-- Pesan jika target sudah tercapai --}}
                <div x-show="isOverTarget"
                     class="text-[11px] text-amber-700 border-t border-amber-200 pt-2">
                    Target bulan ini sudah terpenuhi. Progres tetap bisa disimpan sesuai kegiatan nyata Anda.
                </div>
            </div>
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
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
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
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
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

        <!-- Kegiatan Harian -->
        <div>
            <label for="kegiatan_harian" class="block text-sm font-medium text-gray-700 mb-2">
                Kegiatan Harian <span class="text-red-500">*</span>
            </label>
            <textarea id="kegiatan_harian"
                      name="kegiatan_harian"
                      rows="4"
                      required
                      class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition resize-none"
                      placeholder="Contoh: Menyusun laporan evaluasi kegiatan Januari 2026">{{ old('kegiatan_harian') }}</textarea>
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
                       x-model="progresInput"
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                       :class="isProgresOverSisa ? 'border-amber-400' : ''"
                       placeholder="Contoh: 0.05"
                       value="{{ old('progres') }}">
                @error('progres')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Kontribusi progres hari ini terhadap target</p>

                {{-- WARNING over sisa target — NON BLOCKING, submit tetap aktif --}}
                <div x-show="isProgresOverSisa"
                     x-cloak
                     class="mt-2 flex items-start gap-2 bg-amber-50 border border-amber-300 rounded-lg px-3 py-2">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-xs text-amber-800">
                        Nilai progres melebihi sisa target bulan ini.
                        Pastikan input sesuai progres pekerjaan sebenarnya.
                    </p>
                </div>
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
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                       placeholder="Contoh: Dokumen"
                       value="{{ old('satuan') }}">
                @error('satuan')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Satuan dari progres (Dokumen, File, Orang, dll)</p>
            </div>
        </div>

        <!-- Link Bukti -->
        <div x-data="cekLinkEviden()">
            <label for="link_bukti" class="block text-sm font-medium text-gray-700 mb-2">
                Link Eviden Google Drive <span class="text-gray-400 text-xs">(Opsional)</span>
            </label>
            <div class="flex gap-2">
                <input type="url"
                       id="link_bukti"
                       name="link_bukti"
                       x-model="url"
                       @input="reset()"
                       class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                       placeholder="https://drive.google.com/..."
                       value="{{ old('link_bukti') }}">
                <button type="button"
                        @click="cek()"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition whitespace-nowrap border border-gray-300">
                    Cek Link
                </button>
            </div>
            {{-- Hasil cek domain (client-side, non-blocking) --}}
            <p x-show="status === 'valid'"   class="mt-1 text-xs text-green-600 font-medium">✓ Domain Google valid</p>
            <p x-show="status === 'invalid'" class="mt-1 text-xs text-red-600 font-medium">✗ Domain tidak didukung. Gunakan Google Drive / Docs / Sheets / Forms / Slides.</p>
            <p x-show="status === 'empty'"   class="mt-1 text-xs text-gray-400">Link kosong — eviden dapat diisi nanti.</p>
            @error('link_bukti')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            {{-- Panduan sharing --}}
            <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                <p class="font-semibold mb-1">⚠ Pastikan pengaturan berbagi Google Drive:</p>
                <p>"Siapa saja yang memiliki link dapat <strong>melihat</strong> (Viewer)"</p>
                <p class="mt-1 text-amber-600">Domain yang diterima: drive.google.com, docs.google.com, sheets.google.com, forms.google.com, slides.google.com</p>
            </div>
        </div>

        <script>
        function cekLinkEviden() {
            const allowed = {!! \App\Helpers\EvidenHelper::allowedDomainsJson() !!};
            return {
                url: '{{ old('link_bukti') }}',
                status: '',
                reset() { this.status = ''; },
                cek() {
                    const v = this.url ? this.url.trim() : '';
                    if (!v) { this.status = 'empty'; return; }
                    try {
                        const host = new URL(v).hostname.replace(/^www\./i,'').toLowerCase();
                        this.status = allowed.includes(host) ? 'valid' : 'invalid';
                    } catch(e) {
                        this.status = 'invalid';
                    }
                },
            };
        }
        </script>

        <!-- Keterangan -->
        <div>
            <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                Keterangan <span class="text-gray-400 text-xs">(Opsional)</span>
            </label>
            <textarea id="keterangan"
                      name="keterangan"
                      rows="3"
                      class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition resize-none"
                      placeholder="Catatan tambahan...">{{ old('keterangan') }}</textarea>
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

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
            <a href="{{ route('asn.harian.index') }}"
               class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium">
                Batal
            </a>
            <button type="submit"
                    :disabled="!durasiValid"
                    class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg transition font-medium shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Kinerja Harian
                </span>
            </button>
        </div>

        {{-- WARNING C: Confirm modal — submit tanpa RAB (Alpine, non-blocking) --}}
        @if($hasRabAktif)
        <div x-show="showConfirmNoRab"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background: rgba(0,0,0,0.45);">
            <div x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-sm mx-auto">
                <div class="flex items-start gap-3 mb-4">
                    <div class="flex-shrink-0 w-9 h-9 bg-amber-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Simpan tanpa Rencana Aksi Bulanan?</p>
                        <p class="text-sm text-gray-600 mt-1">
                            Progres ini <span class="font-semibold text-amber-700">tidak akan dihitung</span> ke capaian SKP
                            karena tidak terkait Rencana Aksi Bulanan.
                        </p>
                    </div>
                </div>
                <div class="flex gap-3 justify-end">
                    <button type="button"
                            @click="showConfirmNoRab = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                        Pilih RAB dulu
                    </button>
                    <button type="button"
                            @click="confirmSubmitNoRab()"
                            class="px-4 py-2 text-sm font-medium text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition">
                        Tetap Simpan
                    </button>
                </div>
            </div>
        </div>
        @endif

    </form>

</div>

<style>
[x-cloak] { display: none !important; }
</style>

@push('scripts')
<script>
function formKinerja() {
    return {
        jamMulai: '',
        jamSelesai: '',
        progresInput: '',
        selectedRabId: '{{ old('rencana_kerja_id', '') }}',
        selectedRab: null,
        sisaHariKerja: {{ $sisaHariKerja }},
        rabData: @json($rencanaKerja->keyBy('id')),
        hasAvailableRab: {{ $hasRabAktif ? 'true' : 'false' }},
        warnADismissed: false,
        showConfirmNoRab: false,

        init() {
            if (this.selectedRabId) {
                this.selectedRab = this.rabData[this.selectedRabId] || null;
            }
        },

        get durasiValid() {
            if (!this.jamMulai || !this.jamSelesai) return true;
            return this.jamMulai < this.jamSelesai;
        },

        updateGuidance(rabId) {
            this.warnADismissed = false;
            this.selectedRabId = rabId || '';
            this.selectedRab = rabId ? (this.rabData[rabId] || null) : null;
        },

        handleSubmit(event) {
            if (this.hasAvailableRab && this.selectedRab === null) {
                event.preventDefault();
                this.showConfirmNoRab = true;
            }
        },

        confirmSubmitNoRab() {
            this.showConfirmNoRab = false;
            this.$root.submit();
        },

        get sisaTarget() {
            if (!this.selectedRab) return 0;
            return Math.max(0, this.selectedRab.target_bulanan - this.selectedRab.realisasi_bulanan);
        },

        get progressPersen() {
            if (!this.selectedRab || this.selectedRab.target_bulanan <= 0) return 0;
            return Math.min(100, Math.round((this.selectedRab.realisasi_bulanan / this.selectedRab.target_bulanan) * 100));
        },

        get saranPerHari() {
            if (this.sisaTarget <= 0 || this.sisaHariKerja <= 0) return 0;
            return this.sisaTarget / this.sisaHariKerja;
        },

        get isOverTarget() {
            if (!this.selectedRab || this.selectedRab.target_bulanan <= 0) return false;
            return this.selectedRab.realisasi_bulanan >= this.selectedRab.target_bulanan;
        },

        get isProgresOverSisa() {
            if (!this.selectedRab || this.selectedRab.target_bulanan <= 0) return false;
            const p = parseFloat(this.progresInput);
            if (isNaN(p) || p <= 0) return false;
            return p > this.sisaTarget;
        },

        formatNum(val) {
            if (val <= 0) return '0';
            if (val < 0.01) return '< 0.01';
            if (val >= 100) return Math.round(val).toString();
            if (val >= 10) return val.toFixed(1);
            return val.toFixed(2);
        }
    };
}
</script>
@endpush

@endsection
