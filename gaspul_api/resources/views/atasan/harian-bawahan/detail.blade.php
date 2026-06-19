@extends('layouts.app')

@section('title', 'Detail Kinerja Harian - ' . $asn->name)
@section('page-title', 'Detail Kinerja Harian')

@section('content')
<div class="space-y-6" x-data="verifikasiEviden()">

    <!-- Header & Breadcrumb -->
    <div class="flex items-center justify-between">
        <div>
            <nav class="flex text-sm text-gray-600">
                <a href="{{ route('atasan.harian-bawahan.index', ['tanggal' => $tanggal, 'mode' => $mode]) }}" class="hover:text-green-600">
                    Monitoring Harian Bawahan
                </a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">Detail {{ $asn->name }}</span>
            </nav>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('atasan.harian-bawahan.cetak-lkh', ['user_id' => $asn->id, 'tanggal' => $tanggal]) }}"
               target="_blank"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition text-sm font-medium">
                Cetak LKH
            </a>
            <a href="{{ route('atasan.harian-bawahan.cetak-tla', ['user_id' => $asn->id, 'tanggal' => $tanggal]) }}"
               target="_blank"
               class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition text-sm font-medium">
                Cetak TLA
            </a>
        </div>
    </div>

    <!-- Flash / Error -->
    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif

    <!-- Biodata ASN -->
    <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">{{ $asn->name }}</h2>
                <div class="space-y-1 text-green-100">
                    <p><span class="font-semibold">NIP:</span> {{ $asn->nip }}</p>
                    <p><span class="font-semibold">Mode:</span> {{ ucfirst($mode) }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-green-100 text-sm">Periode</p>
                <p class="text-2xl font-bold">{{ \Carbon\Carbon::parse($tanggal)->locale('id')->isoFormat('D MMM Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-600 mb-1">Total Progres</div>
            <div class="text-2xl font-bold text-gray-900">{{ $progres_list->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-600 mb-1">Total Jam Kerja</div>
            <div class="text-2xl font-bold text-gray-900">
                @php
                    $totalMenit = $progres_list->sum('durasi_menit');
                    $jam = floor($totalMenit / 60);
                    $menit = $totalMenit % 60;
                @endphp
                {{ $jam }}j {{ $menit }}m
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-600 mb-1">Kinerja Harian (KH)</div>
            <div class="text-2xl font-bold text-blue-600">{{ $progres_list->where('tipe_progres', 'KINERJA_HARIAN')->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="text-sm text-gray-600 mb-1">Tugas Atasan (TLA)</div>
            <div class="text-2xl font-bold text-purple-600">{{ $progres_list->where('tipe_progres', 'TUGAS_ATASAN')->count() }}</div>
        </div>
    </div>

    <!-- Ringkasan Verifikasi -->
    @php
        $cntSesuai      = $progres_list->where('verifikasi_eviden', 'SESUAI')->count();
        $cntKurang      = $progres_list->where('verifikasi_eviden', 'KURANG')->count();
        $cntTidakSesuai = $progres_list->where('verifikasi_eviden', 'TIDAK_SESUAI')->count();
        $cntBelum       = $progres_list->whereNull('verifikasi_eviden')->count();
    @endphp
    @if($progres_list->isNotEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Ringkasan Verifikasi Eviden</p>
        <div class="flex flex-wrap gap-3 text-sm">
            <span class="flex items-center gap-1">
                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>
                <span class="font-semibold text-green-700">Sesuai:</span> {{ $cntSesuai }}
            </span>
            <span class="flex items-center gap-1">
                <span class="w-2.5 h-2.5 rounded-full bg-yellow-400 inline-block"></span>
                <span class="font-semibold text-yellow-700">Kurang:</span> {{ $cntKurang }}
            </span>
            <span class="flex items-center gap-1">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span>
                <span class="font-semibold text-red-700">Tidak Sesuai:</span> {{ $cntTidakSesuai }}
            </span>
            <span class="flex items-center gap-1">
                <span class="w-2.5 h-2.5 rounded-full bg-gray-400 inline-block"></span>
                <span class="font-semibold text-gray-600">Belum Diverifikasi:</span> {{ $cntBelum }}
            </span>
        </div>
    </div>
    @endif

    <!-- Detail Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Detail Progres Harian</h3>
        </div>

        @if($progres_list->isEmpty())
        <div class="p-12 text-center">
            <p class="text-gray-500">Belum ada progres untuk periode ini</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kegiatan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progres</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bukti</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Verifikasi Eviden</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($progres_list as $index => $progres)
                    <tr class="hover:bg-gray-50" id="row-{{ $progres->id }}">
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">{{ $index + 1 }}</td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ \Carbon\Carbon::parse($progres->tanggal)->locale('id')->isoFormat('D MMM Y') }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ \Carbon\Carbon::parse($progres->jam_mulai)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($progres->jam_selesai)->format('H:i') }}
                            <span class="text-gray-500">({{ floor($progres->durasi_menit / 60) }}j {{ $progres->durasi_menit % 60 }}m)</span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            @if($progres->tipe_progres === 'KINERJA_HARIAN')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">KH</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">TLA</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-700">
                            @if($progres->tipe_progres === 'KINERJA_HARIAN')
                                <div class="max-w-xs">{{ $progres->rencana_kegiatan_harian }}</div>
                            @else
                                <div class="max-w-xs">{{ $progres->tugas_atasan }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $progres->progres }} {{ $progres->satuan }}
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            @if($progres->status_bukti === 'SUDAH_ADA')
                                <a href="{{ $progres->bukti_dukung }}" target="_blank"
                                   class="text-green-600 hover:text-green-900 font-medium">
                                    Lihat Bukti
                                </a>
                            @else
                                <span class="text-red-600">Belum Ada</span>
                            @endif
                        </td>

                        {{-- Kolom Verifikasi Eviden --}}
                        @php $adaBukti = $progres->status_bukti === 'SUDAH_ADA' && !empty($progres->bukti_dukung); @endphp
                        <td class="px-4 py-4 text-sm" id="badge-{{ $progres->id }}">
                            @if(!$adaBukti)
                                {{-- Tidak ada bukti = tampil Tidak Sesuai (display only, DB tidak diubah) --}}
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">✗ Tidak Sesuai</span>
                                <div class="mt-1 text-xs text-red-600">(Link Kosong)</div>
                            @elseif($progres->verifikasi_eviden === 'SESUAI')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✓ Sesuai</span>
                            @elseif($progres->verifikasi_eviden === 'KURANG')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">⚠ Kurang</span>
                                @if($progres->catatan_verifikasi)
                                <div class="mt-1 text-xs text-yellow-700 max-w-[160px]">{{ $progres->catatan_verifikasi }}</div>
                                @endif
                            @elseif($progres->verifikasi_eviden === 'TIDAK_SESUAI')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">✗ Tidak Sesuai</span>
                                @if($progres->catatan_verifikasi)
                                <div class="mt-1 text-xs text-red-700 max-w-[160px]">{{ $progres->catatan_verifikasi }}</div>
                                @endif
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">— Belum Diverifikasi</span>
                            @endif
                        </td>

                        {{-- Kolom Aksi --}}
                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                            @if($adaBukti)
                            @php
                                $kegiatanTeks = $progres->tipe_progres === 'KINERJA_HARIAN'
                                    ? ($progres->rencana_kegiatan_harian ?? '')
                                    : ($progres->tugas_atasan ?? '');
                            @endphp
                            <button type="button"
                                    @click="openModal($event)"
                                    data-id="{{ $progres->id }}"
                                    data-kegiatan="{{ json_encode($kegiatanTeks) }}"
                                    data-status="{{ json_encode($progres->verifikasi_eviden ?? '') }}"
                                    data-catatan="{{ json_encode($progres->catatan_verifikasi ?? '') }}"
                                    class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-medium transition">
                                Verifikasi
                            </button>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ========================================================= --}}
    {{-- MODAL VERIFIKASI EVIDEN                                    --}}
    {{-- ========================================================= --}}
    <div x-show="modal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center"
         @keydown.escape.window="closeModal()">

        {{-- Overlay --}}
        <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>

        {{-- Panel --}}
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 z-10 overflow-hidden">

            {{-- Header --}}
            <div class="bg-indigo-600 px-6 py-4">
                <h3 class="text-white font-bold text-lg">Verifikasi Eviden</h3>
                <p class="text-indigo-200 text-xs mt-0.5 line-clamp-2" x-text="kegiatanLabel"></p>
            </div>

            {{-- Form --}}
            <form :id="'form-verifikasi-' + progresId" @submit.prevent="submitVerifikasi()">
                <div class="px-6 py-5 space-y-4">

                    {{-- Pilihan status --}}
                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-2">Status Verifikasi <span class="text-red-500">*</span></p>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" x-model="status" value="SESUAI"
                                       class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500">
                                <span class="text-sm font-medium text-green-700">✓ Sesuai</span>
                                <span class="text-xs text-gray-500">— Eviden cukup dan relevan</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" x-model="status" value="KURANG"
                                       class="w-4 h-4 text-yellow-500 border-gray-300 focus:ring-yellow-500">
                                <span class="text-sm font-medium text-yellow-700">⚠ Kurang</span>
                                <span class="text-xs text-gray-500">— Eviden ada tapi kurang memadai</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio" x-model="status" value="TIDAK_SESUAI"
                                       class="w-4 h-4 text-red-600 border-gray-300 focus:ring-red-500">
                                <span class="text-sm font-medium text-red-700">✗ Tidak Sesuai</span>
                                <span class="text-xs text-gray-500">— Eviden tidak valid / tidak relevan</span>
                            </label>
                        </div>
                    </div>

                    {{-- Checklist cepat — hanya muncul jika KURANG --}}
                    <div x-show="status === 'KURANG'" x-transition>
                        <p class="text-xs font-semibold text-yellow-700 mb-2 uppercase">Alasan (pilih yang relevan)</p>
                        <div class="space-y-1.5">
                            <template x-for="item in checklistKurang" :key="item">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" :value="item" x-model="checklist"
                                           class="w-3.5 h-3.5 text-yellow-500 border-gray-300 rounded">
                                    <span class="text-xs text-gray-700" x-text="item"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- Checklist cepat — hanya muncul jika TIDAK_SESUAI --}}
                    <div x-show="status === 'TIDAK_SESUAI'" x-transition>
                        <p class="text-xs font-semibold text-red-700 mb-2 uppercase">Alasan (pilih yang relevan)</p>
                        <div class="space-y-1.5">
                            <template x-for="item in checklistTidakSesuai" :key="item">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" :value="item" x-model="checklist"
                                           class="w-3.5 h-3.5 text-red-500 border-gray-300 rounded">
                                    <span class="text-xs text-gray-700" x-text="item"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- Catatan tambahan (selalu tampil jika KURANG/TIDAK_SESUAI) --}}
                    <div x-show="status === 'KURANG' || status === 'TIDAK_SESUAI'" x-transition>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Catatan Tambahan
                            <span class="text-red-500" x-show="checklist.length === 0">*</span>
                        </label>
                        <textarea x-model="catatan" rows="3"
                                  placeholder="Tuliskan catatan verifikasi..."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none resize-none transition"></textarea>
                    </div>

                    {{-- Error message --}}
                    <p x-show="errorMsg" x-text="errorMsg" class="text-xs text-red-600 font-medium"></p>

                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                    <button type="button" @click="closeModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="!status || loading"
                            class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition flex items-center gap-2">
                        <svg x-show="loading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span x-text="loading ? 'Menyimpan...' : 'Simpan Verifikasi'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function verifikasiEviden() {
    return {
        modal: false,
        progresId: null,
        kegiatanLabel: '',
        status: '',
        catatan: '',
        checklist: [],
        loading: false,
        errorMsg: '',

        checklistKurang: [
            'Bukti kurang jelas',
            'Dokumen pendukung tidak ada',
            'Foto tidak menunjukkan hasil kerja',
            'Link tidak dapat diakses',
            'Bukti tidak relevan',
            'Lainnya',
        ],

        checklistTidakSesuai: [
            'Bukti tidak sesuai kegiatan',
            'Bukti duplikat',
            'Bukti lama digunakan kembali',
            'Link kosong',
            'Tidak ditemukan hasil kerja',
            'Lainnya',
        ],

        openModal(event) {
            const btn = event.currentTarget;
            this.progresId     = btn.dataset.id;
            this.kegiatanLabel = JSON.parse(btn.dataset.kegiatan || '""');
            this.status        = JSON.parse(btn.dataset.status   || '""');
            this.catatan       = JSON.parse(btn.dataset.catatan  || '""');
            this.checklist     = [];
            this.errorMsg      = '';
            this.loading       = false;
            this.modal         = true;
        },

        closeModal() {
            this.modal = false;
        },

        buildCatatan() {
            const parts = [...this.checklist];
            if (this.catatan.trim()) parts.push(this.catatan.trim());
            return parts.join('; ');
        },

        submitVerifikasi() {
            this.errorMsg = '';

            if (!this.status) {
                this.errorMsg = 'Pilih salah satu status verifikasi.';
                return;
            }

            if (['KURANG', 'TIDAK_SESUAI'].includes(this.status)) {
                const combined = this.buildCatatan();
                if (!combined) {
                    this.errorMsg = 'Pilih alasan atau isi catatan verifikasi.';
                    return;
                }
            }

            this.loading = true;

            const payload = new FormData();
            payload.append('_token', '{{ csrf_token() }}');
            payload.append('verifikasi_eviden', this.status);
            payload.append('catatan_verifikasi', this.buildCatatan());

            fetch(`/atasan/harian-bawahan/verifikasi/${this.progresId}`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: payload,
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.updateBadge(data);
                    this.closeModal();
                } else {
                    this.errorMsg = data.message || 'Terjadi kesalahan.';
                }
            })
            .catch(() => {
                this.errorMsg = 'Gagal terhubung ke server. Coba lagi.';
            })
            .finally(() => {
                this.loading = false;
            });
        },

        updateBadge(data) {
            const el = document.getElementById('badge-' + this.progresId);
            if (!el) return;

            const catatan = data.catatan_verifikasi
                ? `<div class="mt-1 text-xs max-w-[160px] ${data.verifikasi_eviden === 'KURANG' ? 'text-yellow-700' : 'text-red-700'}">${data.catatan_verifikasi}</div>`
                : '';

            let badge = '';
            if (data.verifikasi_eviden === 'SESUAI') {
                badge = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✓ Sesuai</span>';
            } else if (data.verifikasi_eviden === 'KURANG') {
                badge = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">⚠ Kurang</span>' + catatan;
            } else {
                badge = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">✗ Tidak Sesuai</span>' + catatan;
            }
            el.innerHTML = badge;
        },
    };
}
</script>
@endsection
