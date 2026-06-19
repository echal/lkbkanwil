@extends('layouts.app')

@section('title', 'Input Cuti ASN')
@section('page-title', 'Cuti ASN')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center space-x-3">
        <a href="{{ route('asn.harian.pilih') }}"
           class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Input Cuti ASN</h2>
            <p class="text-sm text-gray-500">Catat periode cuti resmi agar tidak tercatat sebagai hari kosong</p>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
        <ul class="text-sm text-red-700 space-y-1">
            @foreach($errors->all() as $error)
                <li>• {{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Form -->
    <form action="{{ route('asn.cuti.store') }}" method="POST"
          x-data="cutiForm()" @submit.prevent="submitForm">

        @csrf
        <input type="hidden" name="kategori" value="CUTI">

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">

            <!-- Jenis Cuti -->
            <div>
                <label for="jenis" class="block text-sm font-semibold text-gray-700 mb-2">Jenis Cuti <span class="text-red-500">*</span></label>
                <select id="jenis" name="jenis"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition"
                        required>
                    <option value="">-- Pilih Jenis Cuti --</option>
                    @foreach($jenisCuti as $j)
                    <option value="{{ $j }}" {{ old('jenis') === $j ? 'selected' : '' }}>{{ $j }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tanggal Mulai & Selesai -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="tanggal_mulai" class="block text-sm font-semibold text-gray-700 mb-2">
                        Tanggal Mulai <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai"
                           value="{{ old('tanggal_mulai', $tanggal) }}"
                           x-model="tanggalMulai"
                           @change="updateJumlahHari()"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition"
                           required>
                </div>
                <div>
                    <label for="tanggal_selesai" class="block text-sm font-semibold text-gray-700 mb-2">
                        Tanggal Selesai <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai"
                           value="{{ old('tanggal_selesai', $tanggal) }}"
                           x-model="tanggalSelesai"
                           @change="updateJumlahHari()"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition"
                           required>
                </div>
            </div>

            <!-- Info jumlah hari -->
            <div x-show="jumlahHari > 0"
                 class="flex items-center space-x-2 text-sm px-4 py-2.5 rounded-xl"
                 :class="jumlahHari > 30 ? 'bg-red-50 text-red-700' : 'bg-orange-50 text-orange-700'">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <span x-text="jumlahHari > 30 ? 'Maksimal 30 hari sekaligus (' + jumlahHari + ' hari dipilih)' : 'Durasi: ' + jumlahHari + ' hari'"></span>
            </div>

            <!-- Keterangan -->
            <div>
                <label for="keterangan" class="block text-sm font-semibold text-gray-700 mb-2">Keterangan</label>
                <textarea id="keterangan" name="keterangan" rows="3"
                          placeholder="Contoh: Cuti tahunan sisa tahun 2025, dst."
                          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition resize-none"
                >{{ old('keterangan') }}</textarea>
            </div>

            <!-- Link Surat Resmi -->
            <div>
                <label for="bukti_dukung" class="block text-sm font-semibold text-gray-700 mb-2">
                    Link Surat Resmi (Google Drive / URL) <span class="text-red-500">*</span>
                </label>
                <input type="url" id="bukti_dukung" name="bukti_dukung"
                       value="{{ old('bukti_dukung') }}"
                       placeholder="https://drive.google.com/..."
                       class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-orange-400 focus:border-orange-400 outline-none transition"
                       required>
                <p class="mt-1 text-xs text-gray-500">Upload surat cuti ke Google Drive lalu paste link-nya di sini.</p>
            </div>

        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('asn.harian.pilih') }}"
               class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-medium transition">
                Batal
            </a>
            <div class="flex items-center space-x-3">
                <a href="{{ route('asn.cuti.index') }}"
                   class="px-6 py-2.5 border border-orange-300 text-orange-600 hover:bg-orange-50 rounded-xl text-sm font-medium transition">
                    Riwayat Cuti
                </a>
                <button type="submit"
                        :disabled="jumlahHari > 30"
                        class="px-8 py-2.5 bg-orange-500 hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-sm font-semibold transition">
                    Simpan
                </button>
            </div>
        </div>

    </form>

</div>

<script>
function cutiForm() {
    return {
        tanggalMulai: '{{ old('tanggal_mulai', $tanggal) }}',
        tanggalSelesai: '{{ old('tanggal_selesai', $tanggal) }}',
        jumlahHari: 1,

        init() {
            this.updateJumlahHari();
        },

        updateJumlahHari() {
            if (!this.tanggalMulai || !this.tanggalSelesai) {
                this.jumlahHari = 0;
                return;
            }
            const mulai   = new Date(this.tanggalMulai);
            const selesai = new Date(this.tanggalSelesai);
            const diff    = (selesai - mulai) / (1000 * 60 * 60 * 24);
            this.jumlahHari = diff >= 0 ? diff + 1 : 0;
        },

        submitForm() {
            if (this.jumlahHari > 30) return;
            this.$el.submit();
        },
    };
}
</script>
@endsection
