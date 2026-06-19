@extends('layouts.app')

@section('title', 'Rotasi Jabatan')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-8">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Rotasi Jabatan</h1>
        <p class="text-sm text-gray-500 mt-1">Ganti kepala unit atau pindahkan staf tanpa perlu mengedit satu per satu.</p>
    </div>

    {{-- Flash message --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- ================================================================== --}}
    {{-- BAGIAN 1: GANTI KEPALA UNIT --}}
    {{-- ================================================================== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200"
         x-data="gantiKepala()">

        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-800">Ganti Kepala Unit</h2>
            <p class="text-xs text-gray-500 mt-0.5">
                Pilih unit kerja → sistem tampilkan kepala sekarang → pilih kepala baru → semua staf unit otomatis berpindah atasan.
            </p>
        </div>

        <div class="px-6 py-5 space-y-5">

            {{-- Pilih Unit Kerja --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Kerja</label>
                <select x-model="unitId" @change="loadInfoUnit()"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">— Pilih unit kerja —</option>
                    @foreach($unitList as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->nama_unit }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Info Kepala Sekarang --}}
            <div x-show="unitId && !loading" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kepala Unit Sekarang</label>
                <template x-if="kepalaSekarang.length === 0">
                    <p class="text-sm text-gray-400 italic">Belum ada kepala yang terdaftar di unit ini.</p>
                </template>
                <template x-if="kepalaSekarang.length > 0">
                    <div class="space-y-2">
                        <template x-for="k in kepalaSekarang" :key="k.id">
                            <div class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2">
                                <div class="w-8 h-8 rounded-full bg-amber-200 flex items-center justify-center text-amber-700 font-bold text-sm flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800" x-text="k.name"></p>
                                    <p class="text-xs text-gray-500" x-text="k.jabatan"></p>
                                </div>
                                <span class="ml-auto text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Kepala Lama</span>
                            </div>
                        </template>
                        <p class="text-xs text-gray-500">
                            <span x-text="stafCount"></span> staf akan berpindah ke kepala baru.
                        </p>
                    </div>
                </template>
            </div>

            <div x-show="loading" x-cloak>
                <p class="text-sm text-gray-400 animate-pulse">Memuat informasi unit...</p>
            </div>

            {{-- Pilih Kepala Baru --}}
            <div x-show="unitId" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kepala Baru</label>
                <select x-model="kepalaBaru"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">— Pilih pejabat baru —</option>
                    @foreach($atasanList as $atasan)
                    <option value="{{ $atasan->id }}">{{ $atasan->name }} — {{ $atasan->jabatan }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Jabatan Baru --}}
            <div x-show="unitId && kepalaBaru" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jabatan Baru</label>
                <input type="text" x-model="jabatanBaru" placeholder="Contoh: Kepala Bidang Bimbingan Masyarakat Islam"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                <p class="text-xs text-gray-400 mt-1">Nama jabatan ini akan disimpan ke data pejabat baru tersebut.</p>
            </div>

            {{-- Konfirmasi & Tombol --}}
            <div x-show="unitId && kepalaBaru && jabatanBaru" x-cloak>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800 mb-4">
                    <p class="font-medium mb-1">Konfirmasi perubahan:</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li>Kepala lama akan dilepas dari unit ini</li>
                        <li><span x-text="stafCount"></span> staf unit akan berpindah ke kepala baru</li>
                        <li>Jabatan kepala baru akan diupdate ke "<span x-text="jabatanBaru"></span>"</li>
                    </ul>
                </div>
                <button @click="prosesGantiKepala()"
                        :disabled="prosesLoading"
                        class="w-full bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-medium py-2.5 px-4 rounded-lg text-sm transition">
                    <span x-show="!prosesLoading">Terapkan Pergantian Kepala</span>
                    <span x-show="prosesLoading" x-cloak>Memproses...</span>
                </button>
            </div>

            {{-- Result --}}
            <div x-show="resultMsg" x-cloak>
                <div :class="resultOk ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                     class="border rounded-lg px-4 py-3 text-sm" x-text="resultMsg"></div>
            </div>

        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- BAGIAN 2: PINDAH STAF --}}
    {{-- ================================================================== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200"
         x-data="pindahStaf()">

        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-800">Pindah Staf</h2>
            <p class="text-xs text-gray-500 mt-0.5">
                Pindahkan satu atau beberapa staf ke atasan lain. Cocok untuk kasus khusus tanpa ganti kepala unit.
            </p>
        </div>

        <div class="px-6 py-5 space-y-5">

            {{-- Pilih Atasan Lama --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Atasan Saat Ini</label>
                <select x-model="atasanLama" @change="loadBawahan()"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">— Pilih atasan —</option>
                    @foreach($atasanList as $atasan)
                    <option value="{{ $atasan->id }}">{{ $atasan->name }} — {{ $atasan->jabatan }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Daftar Bawahan (checklist) --}}
            <div x-show="atasanLama && !loadingBawahan" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Pilih Staf yang Dipindah
                    <span class="text-xs font-normal text-gray-400 ml-1">(<span x-text="selected.length"></span> dipilih)</span>
                </label>

                <template x-if="bawahan.length === 0">
                    <p class="text-sm text-gray-400 italic">Atasan ini tidak memiliki bawahan.</p>
                </template>

                <template x-if="bawahan.length > 0">
                    <div>
                        {{-- Pilih Semua --}}
                        <div class="flex items-center gap-2 mb-2">
                            <button @click="toggleAll()"
                                    class="text-xs text-green-600 hover:underline">
                                <span x-text="selected.length === bawahan.length ? 'Batal Pilih Semua' : 'Pilih Semua'"></span>
                            </button>
                            <span class="text-gray-300">|</span>
                            <span class="text-xs text-gray-500" x-text="bawahan.length + ' staf total'"></span>
                        </div>

                        {{-- List --}}
                        <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-64 overflow-y-auto">
                            <template x-for="s in bawahan" :key="s.id">
                                <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" :value="s.id" x-model="selected"
                                           class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800" x-text="s.name"></p>
                                        <p class="text-xs text-gray-500" x-text="s.jabatan + ' (' + s.role + ')'"></p>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="loadingBawahan" x-cloak>
                <p class="text-sm text-gray-400 animate-pulse">Memuat daftar bawahan...</p>
            </div>

            {{-- Pilih Atasan Baru --}}
            <div x-show="atasanLama && selected.length > 0" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1">Atasan Baru</label>
                <select x-model="atasanBaru"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">— Pilih atasan tujuan —</option>
                    @foreach($atasanList as $atasan)
                    <option value="{{ $atasan->id }}"
                            :disabled="String({{ $atasan->id }}) === String(atasanLama)">
                        {{ $atasan->name }} — {{ $atasan->jabatan }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Tombol --}}
            <div x-show="atasanLama && selected.length > 0 && atasanBaru" x-cloak>
                <button @click="prosesPindah()"
                        :disabled="prosesLoading"
                        class="w-full bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-medium py-2.5 px-4 rounded-lg text-sm transition">
                    <span x-show="!prosesLoading">Pindahkan <span x-text="selected.length"></span> Staf</span>
                    <span x-show="prosesLoading" x-cloak>Memproses...</span>
                </button>
            </div>

            {{-- Result --}}
            <div x-show="resultMsg" x-cloak>
                <div :class="resultOk ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                     class="border rounded-lg px-4 py-3 text-sm" x-text="resultMsg"></div>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
function gantiKepala() {
    return {
        unitId: '',
        kepalaBaru: '',
        jabatanBaru: '',
        kepalaSekarang: [],
        stafCount: 0,
        loading: false,
        prosesLoading: false,
        resultMsg: '',
        resultOk: true,

        loadInfoUnit() {
            if (!this.unitId) return;
            this.loading = true;
            this.kepalaSekarang = [];
            this.stafCount = 0;
            this.kepalaBaru = '';
            this.jabatanBaru = '';
            this.resultMsg = '';

            fetch(`{{ route('admin.rotasi-jabatan.info-unit') }}?unit_kerja_id=${this.unitId}`)
                .then(r => r.json())
                .then(data => {
                    this.kepalaSekarang = data.kepala_sekarang;
                    this.stafCount = data.staf_count;
                    this.loading = false;
                })
                .catch(() => { this.loading = false; });
        },

        prosesGantiKepala() {
            if (!this.unitId || !this.kepalaBaru || !this.jabatanBaru) return;
            this.prosesLoading = true;
            this.resultMsg = '';

            fetch('{{ route('admin.rotasi-jabatan.ganti-kepala') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    unit_kerja_id:  this.unitId,
                    kepala_baru_id: this.kepalaBaru,
                    jabatan_baru:   this.jabatanBaru,
                }),
            })
            .then(r => r.json())
            .then(data => {
                this.resultOk  = data.success;
                this.resultMsg = data.message;
                if (data.success) {
                    // Reload info unit untuk update tampilan
                    this.loadInfoUnit();
                    this.kepalaBaru  = '';
                    this.jabatanBaru = '';
                }
                this.prosesLoading = false;
            })
            .catch(() => {
                this.resultOk  = false;
                this.resultMsg = 'Terjadi kesalahan. Coba lagi.';
                this.prosesLoading = false;
            });
        },
    };
}

function pindahStaf() {
    return {
        atasanLama: '',
        atasanBaru: '',
        bawahan: [],
        selected: [],
        loadingBawahan: false,
        prosesLoading: false,
        resultMsg: '',
        resultOk: true,

        loadBawahan() {
            if (!this.atasanLama) return;
            this.loadingBawahan = true;
            this.bawahan = [];
            this.selected = [];
            this.atasanBaru = '';
            this.resultMsg = '';

            fetch(`{{ route('admin.rotasi-jabatan.bawahan-atasan') }}?atasan_id=${this.atasanLama}`)
                .then(r => r.json())
                .then(data => {
                    this.bawahan = data.bawahan;
                    this.loadingBawahan = false;
                })
                .catch(() => { this.loadingBawahan = false; });
        },

        toggleAll() {
            if (this.selected.length === this.bawahan.length) {
                this.selected = [];
            } else {
                this.selected = this.bawahan.map(s => s.id);
            }
        },

        prosesPindah() {
            if (!this.selected.length || !this.atasanBaru) return;
            this.prosesLoading = true;
            this.resultMsg = '';

            fetch('{{ route('admin.rotasi-jabatan.pindah-staf') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    staf_ids:       this.selected,
                    atasan_baru_id: this.atasanBaru,
                }),
            })
            .then(r => r.json())
            .then(data => {
                this.resultOk  = data.success;
                this.resultMsg = data.message;
                if (data.success) {
                    this.loadBawahan();
                }
                this.prosesLoading = false;
            })
            .catch(() => {
                this.resultOk  = false;
                this.resultMsg = 'Terjadi kesalahan. Coba lagi.';
                this.prosesLoading = false;
            });
        },
    };
}
</script>
@endpush

@endsection
