@extends('layouts.app')

@section('title', 'Laporan Bulanan Kinerja')
@section('page-title', 'Laporan Bulanan')

@section('content')
<div class="space-y-6" id="laporan-bulanan">

    {{-- TAB NAVIGATION --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="flex border-b border-gray-200">
            <button onclick="switchTab('laporan')" id="tab-btn-laporan"
                class="tab-btn px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-150 focus:outline-none
                       border-green-600 text-green-600">
                📊 Laporan Bulanan
            </button>
            <button onclick="switchTab('absensi')" id="tab-btn-absensi"
                class="tab-btn px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-150 focus:outline-none
                       border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                📋 Rekap Absensi PUSAKA
            </button>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- TAB 1: LAPORAN BULANAN                                           --}}
    {{-- ================================================================ --}}
    <div id="tab-laporan">

        {{-- Filter Tahun & Bulan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <form method="GET" action="{{ route('asn.bulanan.index') }}" class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 mr-2">Tahun:</label>
                        <select name="tahun" class="px-3 py-2 border border-gray-300 rounded-lg">
                            @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 mr-2">Bulan:</label>
                        <select name="bulan" class="px-3 py-2 border border-gray-300 rounded-lg">
                            @for($m = 1; $m <= 12; $m++)
                                @php
                                    $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                                @endphp
                                <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
                            @endfor
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Tampilkan
                    </button>
                </div>

                @if($hasData ?? false)
                <div class="flex space-x-2">
                    <a href="{{ route('asn.bulanan.export-pdf', ['tahun' => $tahun, 'bulan' => $bulan]) }}"
                       class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Cetak PDF
                    </a>
                    @if($statusLaporan === 'DRAFT')
                    <form method="POST" action="{{ route('asn.bulanan.kirim-atasan') }}" class="inline">
                        @csrf
                        <input type="hidden" name="tahun" value="{{ $tahun }}">
                        <input type="hidden" name="bulan" value="{{ $bulan }}">
                        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition"
                                onclick="return confirm('Kirim laporan ke atasan?')">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Kirim ke Atasan
                        </button>
                    </form>
                    @endif
                </div>
                @endif
            </form>
        </div>

        @if(!($hasData ?? false))
            {{-- No Data State --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Belum Ada Data Laporan</h3>
                <p class="text-gray-600">Silakan pilih tahun dan bulan, atau mulai input kinerja harian.</p>
            </div>
        @else
            {{-- Laporan Content --}}
            @include('asn.bulanan.partials.header')
            @include('asn.bulanan.partials.ringkasan')
            @include('asn.bulanan.partials.rekap-rhk')
            @include('asn.bulanan.partials.rekap-detail-harian')
            @include('asn.bulanan.partials.rekap-harian')
            @include('asn.bulanan.partials.kesimpulan')
        @endif

    </div>{{-- end tab-laporan --}}

    {{-- ================================================================ --}}
    {{-- TAB 2: REKAP ABSENSI PUSAKA                                      --}}
    {{-- ================================================================ --}}
    <div id="tab-absensi" class="hidden space-y-6">

        {{-- Flash success --}}
        @if(session('success_absensi'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-4 flex items-center space-x-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('success_absensi') }}</span>
        </div>
        @endif

        {{-- Form Upload --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">Upload Rekap Absensi PUSAKA</h3>

            <form method="POST" action="{{ route('asn.laporan.rekap-absensi.store') }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Pilih Bulan --}}
                    <div>
                        <label for="bulan" class="block text-sm font-medium text-gray-700 mb-1">
                            Bulan <span class="text-red-500">*</span>
                        </label>
                        <select name="bulan" id="bulan"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent
                                   @error('bulan') border-red-500 @enderror">
                            <option value="">-- Pilih Bulan --</option>
                            @foreach($bulanOptions as $value => $label)
                                <option value="{{ $value }}" {{ old('bulan') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('bulan')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Link Google Drive --}}
                    <div>
                        <label for="link_drive" class="block text-sm font-medium text-gray-700 mb-1">
                            Link Google Drive <span class="text-red-500">*</span>
                        </label>
                        <input type="url" name="link_drive" id="link_drive"
                            value="{{ old('link_drive') }}"
                            placeholder="https://drive.google.com/..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent
                                   @error('link_drive') border-red-500 @enderror">
                        @error('link_drive')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <button type="submit"
                        class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                        Simpan
                    </button>
                    <p class="text-xs text-gray-500">
                        * Link harus berupa Google Drive. Setiap bulan hanya bisa diupload satu kali.
                    </p>
                </div>
            </form>
        </div>

        {{-- Tabel Riwayat --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-800">Riwayat Upload</h3>
            </div>

            @if($rekapAbsensiList->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm">Belum ada rekap absensi yang diupload.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bulan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Link</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catatan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diverifikasi oleh</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Upload</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($rekapAbsensiList as $rekap)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    {{ $rekap->nama_bulan }}
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ $rekap->link_drive }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium rounded-lg transition">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                        Lihat File
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rekap->status_badge_class }}">
                                        {{ $rekap->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600 max-w-xs truncate">
                                    {{ $rekap->catatan ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $rekap->verifier?->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ $rekap->created_at->format('d M Y') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>{{-- end tab-absensi --}}

</div>

@push('scripts')
<script>
function switchTab(tab) {
    // Hide all tab content
    document.getElementById('tab-laporan').classList.add('hidden');
    document.getElementById('tab-absensi').classList.add('hidden');

    // Reset all tab buttons
    document.getElementById('tab-btn-laporan').className =
        'tab-btn px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-150 focus:outline-none border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
    document.getElementById('tab-btn-absensi').className =
        'tab-btn px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-150 focus:outline-none border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';

    // Show selected tab
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.getElementById('tab-btn-' + tab).className =
        'tab-btn px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-150 focus:outline-none border-green-600 text-green-600';
}

// Auto-switch ke tab absensi jika ada error absensi atau success absensi
document.addEventListener('DOMContentLoaded', function () {
    @if($errors->has('bulan') || $errors->has('link_drive') || session('success_absensi'))
        switchTab('absensi');
    @endif
});
</script>
@endpush
@endsection
