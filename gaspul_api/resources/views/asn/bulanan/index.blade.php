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
            <button onclick="switchTab('riwayat')" id="tab-btn-riwayat"
                class="tab-btn px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-150 focus:outline-none
                       border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                📁 Riwayat Laporan
                @if(isset($riwayatLaporan) && $riwayatLaporan->where('status','DITOLAK')->isNotEmpty())
                    <span class="ml-1 px-1.5 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-700">!</span>
                @endif
            </button>
            <button onclick="switchTab('absensi')" id="tab-btn-absensi"
                class="tab-btn px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-150 focus:outline-none
                       border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                📋 Rekap Absensi PUSAKA
            </button>
        </div>
    </div>

    {{-- Flash: kirim laporan berhasil --}}
    @if(session('success_laporan'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-4 flex items-center space-x-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('success_laporan') }}</span>
    </div>
    @endif

    {{-- Flash: error laporan --}}
    @if($errors->has('laporan'))
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-4 flex items-center space-x-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <span>{{ $errors->first('laporan') }}</span>
    </div>
    @endif

    {{-- ================================================================ --}}
    {{-- TAB 1: LAPORAN BULANAN                                           --}}
    {{-- ================================================================ --}}
    <div id="tab-laporan">

        {{-- Form kirim laporan (diletakkan di luar form GET agar tidak bersarang) --}}
        @if($hasData ?? false)
        <form id="form-kirim-laporan" method="POST" action="{{ route('asn.bulanan.kirim-atasan') }}" class="hidden">
            @csrf
            <input type="hidden" name="tahun" value="{{ $tahun }}">
            <input type="hidden" name="bulan" value="{{ $bulan }}">
        </form>
        @endif

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
                    @if(in_array($statusLaporan ?? 'DRAFT', ['DRAFT', 'DITOLAK']))
                    <button type="button"
                            form="form-kirim-laporan"
                            onclick="if(confirm('Kirim laporan {{ $namaBulan }} ke atasan?')) document.getElementById('form-kirim-laporan').submit();"
                            class="px-4 py-2 {{ ($statusLaporan ?? 'DRAFT') === 'DITOLAK' ? 'bg-orange-600 hover:bg-orange-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        {{ ($statusLaporan ?? 'DRAFT') === 'DITOLAK' ? 'Kirim Ulang' : 'Kirim ke Atasan' }}
                    </button>
                    @elseif(($statusLaporan ?? 'DRAFT') === 'DIKIRIM')
                    <span class="px-4 py-2 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-lg">
                        Menunggu Persetujuan
                    </span>
                    @elseif(($statusLaporan ?? 'DRAFT') === 'DISETUJUI')
                    <span class="px-4 py-2 bg-green-100 text-green-800 text-sm font-medium rounded-lg">
                        ✓ Disetujui Atasan
                    </span>
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
    {{-- TAB 2: RIWAYAT LAPORAN BULANAN KINERJA                          --}}
    {{-- ================================================================ --}}
    <div id="tab-riwayat" class="hidden space-y-4">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Riwayat Laporan Bulanan Kinerja</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Kirim laporan dari tab Laporan Bulanan menggunakan tombol "Kirim ke Atasan".</p>
                </div>
            </div>

            @if(!isset($riwayatLaporan) || $riwayatLaporan->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-600">Belum ada laporan yang pernah dikirim.</p>
                    <p class="text-xs text-gray-400 mt-1">Pilih bulan di tab Laporan Bulanan, lalu klik "Kirim ke Atasan".</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bulan</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hari Kerja</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Jam</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Capaian</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disetujui/Ditolak Oleh</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catatan Atasan</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($riwayatLaporan as $lap)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 font-medium text-gray-800 whitespace-nowrap">
                                    {{ $lap->nama_bulan }}
                                </td>
                                <td class="px-4 py-4 text-center text-gray-700">
                                    {{ $lap->total_hari }} hari
                                </td>
                                <td class="px-4 py-4 text-center text-gray-700">
                                    {{ $lap->total_jam }} jam
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @php
                                        $pct = $lap->capaian_persen;
                                        $pctColor = $pct >= 90 ? 'text-green-700' : ($pct >= 60 ? 'text-yellow-700' : 'text-red-700');
                                    @endphp
                                    <span class="font-semibold {{ $pctColor }}">{{ number_format($pct, 1) }}%</span>
                                    <span class="block text-xs text-gray-400">dari 165 jam</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $lap->status_badge_class }}">
                                        {{ $lap->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-gray-600 text-xs whitespace-nowrap">
                                    @if($lap->approver)
                                        {{ $lap->approver->name }}
                                        @if($lap->approved_at)
                                            <span class="block text-gray-400">{{ $lap->approved_at->format('d M Y') }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-xs text-gray-600 max-w-xs">
                                    @if($lap->catatan)
                                        <span class="italic text-{{ $lap->status === 'DITOLAK' ? 'red' : 'gray' }}-600 line-clamp-2" title="{{ $lap->catatan }}">
                                            "{{ $lap->catatan }}"
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center whitespace-nowrap">
                                    @if($lap->is_kirimable)
                                        <form method="POST" action="{{ route('asn.bulanan.kirim-atasan') }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="tahun" value="{{ $lap->tahun }}">
                                            <input type="hidden" name="bulan" value="{{ $lap->bulan }}">
                                            <button type="submit"
                                                    onclick="return confirm('Kirim ulang laporan {{ $lap->nama_bulan }} ke atasan?')"
                                                    class="inline-flex items-center px-2.5 py-1 {{ $lap->status === 'DITOLAK' ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-600 hover:bg-green-700' }} text-white text-xs font-medium rounded-lg transition">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                                </svg>
                                                {{ $lap->status === 'DITOLAK' ? 'Kirim Ulang' : 'Kirim' }}
                                            </button>
                                        </form>
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

    </div>{{-- end tab-riwayat --}}

    {{-- ================================================================ --}}
    {{-- TAB 3: REKAP ABSENSI PUSAKA                                       --}}
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

        {{-- Tabel Riwayat Upload --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-800">Riwayat Upload</h3>
                <p class="text-xs text-gray-500 mt-0.5">Deadline upload: setiap tanggal 5 bulan berikutnya</p>
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bulan</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Link</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revisi ke-</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verifikasi</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Upload</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        @foreach($rekapAbsensiList as $rekap)
                        {{-- Setiap rekap dibungkus <tbody> sendiri agar Alpine x-data bisa berbagi state antar <tr> --}}
                        <tbody x-data="{ showRevisi: false, showHistori: false }" class="divide-y divide-gray-100">
                            <tr class="hover:bg-gray-50">
                                    {{-- Bulan --}}
                                    <td class="px-4 py-4 font-medium text-gray-800 whitespace-nowrap">
                                        {{ $rekap->nama_bulan }}
                                    </td>

                                    {{-- Deadline --}}
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="text-xs {{ $rekap->is_deadline_past ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                            {{ $rekap->deadline_upload->format('d M Y') }}
                                            @if($rekap->is_deadline_past)
                                                <span class="block text-red-500">Terlewat</span>
                                            @endif
                                        </span>
                                    </td>

                                    {{-- Link Drive --}}
                                    <td class="px-4 py-4">
                                        <a href="{{ $rekap->link_drive }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium rounded-lg transition">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                            Lihat File
                                        </a>
                                    </td>

                                    {{-- Status badge --}}
                                    <td class="px-4 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rekap->status_badge_class }}">
                                            {{ $rekap->status_label }}
                                        </span>
                                    </td>

                                    {{-- Nomor revisi --}}
                                    <td class="px-4 py-4 text-center text-gray-700">
                                        {{ $rekap->revision_count > 0 ? $rekap->revision_count : '-' }}
                                    </td>

                                    {{-- Verifikasi Kabid + Kakanwil --}}
                                    <td class="px-4 py-4 text-gray-600 max-w-xs space-y-1">
                                        @if($rekap->verifier || $rekap->catatan)
                                            <div class="text-xs">
                                                <span class="font-medium text-indigo-700">Kabid:</span>
                                                {{ $rekap->verifier->name ?? '-' }}
                                                @if($rekap->catatan)
                                                    <span class="block text-gray-500 italic line-clamp-2" title="{{ $rekap->catatan }}">
                                                        "{{ $rekap->catatan }}"
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                        @if($rekap->verifierKakanwil || $rekap->catatan_kakanwil)
                                            <div class="text-xs">
                                                <span class="font-medium text-purple-700">Kakanwil:</span>
                                                {{ $rekap->verifierKakanwil->name ?? '-' }}
                                                @if($rekap->catatan_kakanwil)
                                                    <span class="block text-gray-500 italic line-clamp-2" title="{{ $rekap->catatan_kakanwil }}">
                                                        "{{ $rekap->catatan_kakanwil }}"
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                        @if(!$rekap->verifier && !$rekap->catatan && !$rekap->verifierKakanwil && !$rekap->catatan_kakanwil)
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>

                                    {{-- Tanggal upload --}}
                                    <td class="px-4 py-4 text-gray-500 whitespace-nowrap text-xs">
                                        {{ $rekap->created_at->format('d M Y') }}
                                    </td>

                                    {{-- Kolom Aksi --}}
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            {{-- Tombol Revisi (hanya jika is_revisable) --}}
                                            @if($rekap->is_revisable)
                                            <button @click="showRevisi = !showRevisi; showHistori = false"
                                                    :class="showRevisi ? 'bg-orange-700' : 'bg-orange-500 hover:bg-orange-600'"
                                                    class="inline-flex items-center px-2.5 py-1 text-white text-xs font-medium rounded-lg transition">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Revisi
                                            </button>
                                            @endif

                                            {{-- Tombol Histori (hanya jika ada riwayat revisi) --}}
                                            @if($rekap->histori->isNotEmpty())
                                            <button @click="showHistori = !showHistori; showRevisi = false"
                                                    :class="showHistori ? 'bg-gray-600' : 'bg-gray-400 hover:bg-gray-500'"
                                                    class="inline-flex items-center px-2.5 py-1 text-white text-xs font-medium rounded-lg transition">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Histori ({{ $rekap->histori->count() }})
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                            </tr>

                            {{-- Panel Form Revisi (collapsible) --}}
                            @if($rekap->is_revisable)
                            <tr x-show="showRevisi" x-transition style="display:none">
                                <td colspan="8" class="px-6 py-4 bg-orange-50 border-t border-orange-200">
                                    <div class="max-w-2xl">
                                        <p class="text-xs font-semibold text-orange-700 mb-2">
                                            Revisi Rekap {{ $rekap->nama_bulan }}
                                            &mdash; Deadline: {{ $rekap->deadline_upload->format('d M Y') }}
                                        </p>
                                        <form method="POST"
                                              action="{{ route('asn.laporan.rekap-absensi.revisi', $rekap->id) }}"
                                              class="flex items-center space-x-3">
                                            @csrf
                                            <input type="url" name="link_drive"
                                                   placeholder="Masukkan link Google Drive yang baru..."
                                                   required
                                                   class="flex-1 px-3 py-2 text-sm border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                            <button type="submit"
                                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition whitespace-nowrap">
                                                Kirim Revisi
                                            </button>
                                            <button type="button" @click="showRevisi = false"
                                                    class="px-3 py-2 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition">
                                                Batal
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endif

                            {{-- Panel Histori Revisi (collapsible) --}}
                            @if($rekap->histori->isNotEmpty())
                            <tr x-show="showHistori" x-transition style="display:none">
                                <td colspan="8" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                                    <p class="text-xs font-semibold text-gray-600 mb-2">
                                        Riwayat Revisi — {{ $rekap->nama_bulan }}
                                    </p>
                                    <table class="w-full text-xs border border-gray-200 rounded-lg overflow-hidden">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-gray-600 font-medium">Revisi ke-</th>
                                                <th class="px-4 py-2 text-left text-gray-600 font-medium">Link Lama</th>
                                                <th class="px-4 py-2 text-left text-gray-600 font-medium">Tanggal Revisi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            @foreach($rekap->histori as $h)
                                            <tr>
                                                <td class="px-4 py-2 text-gray-700 font-medium text-center">
                                                    {{ $h->revision_number + 1 }}
                                                </td>
                                                <td class="px-4 py-2">
                                                    <a href="{{ $h->link_drive_lama }}" target="_blank" rel="noopener noreferrer"
                                                       class="text-blue-600 hover:underline truncate block max-w-xs">
                                                        {{ $h->link_drive_lama }}
                                                    </a>
                                                </td>
                                                <td class="px-4 py-2 text-gray-500 whitespace-nowrap">
                                                    {{ $h->tanggal_revisi->format('d M Y, H:i') }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            @endif
                        </tbody>{{-- end tbody per rekap --}}
                        @endforeach
                    </table>
                </div>
            @endif
        </div>

    </div>{{-- end tab-absensi --}}

</div>

@push('scripts')
<script>
const TAB_ACTIVE   = 'tab-btn px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-150 focus:outline-none border-green-600 text-green-600';
const TAB_INACTIVE = 'tab-btn px-6 py-4 text-sm font-medium border-b-2 transition-colors duration-150 focus:outline-none border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';

function switchTab(tab) {
    ['laporan', 'riwayat', 'absensi'].forEach(function(t) {
        document.getElementById('tab-' + t).classList.add('hidden');
        document.getElementById('tab-btn-' + t).className = TAB_INACTIVE;
    });
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.getElementById('tab-btn-' + tab).className = TAB_ACTIVE;
}

document.addEventListener('DOMContentLoaded', function () {
    @if($errors->has('bulan') || $errors->has('link_drive') || session('success_absensi'))
        switchTab('absensi');
    @elseif($errors->has('laporan') || session('success_laporan'))
        switchTab('riwayat');
    @endif
});
</script>
@endpush
@endsection
