@extends('layouts.app')

@section('title', 'Pusat Persetujuan')
@section('page-title', 'Pusat Persetujuan')

@section('content')
<div class="space-y-6" x-data="{ tab: '{{ $activeTab }}', laporanModal: null }">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Pusat Persetujuan</h2>
            <p class="text-sm text-gray-600 mt-1">
                @if($isKabid)
                    Level: <span class="font-semibold text-indigo-700">Kepala Bidang (Kabid)</span> — menyetujui pengajuan bawahan langsung
                @else
                    Level: <span class="font-semibold text-purple-700">Kepala Kanwil (Kakanwil)</span> — menyetujui final setelah Kabid
                @endif
            </p>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-4 flex items-center space-x-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-4 flex items-center space-x-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <span>{{ $errors->first() }}</span>
    </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- SKP Menunggu --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 cursor-pointer hover:shadow-md transition"
             @click="tab='skp'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 mb-1 uppercase tracking-wide">SKP Menunggu</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $skpPendingCount }}</p>
                </div>
                <div class="w-11 h-11 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- SKP Disetujui --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 cursor-pointer hover:shadow-md transition"
             @click="tab='skp'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 mb-1 uppercase tracking-wide">SKP Disetujui</p>
                    <p class="text-3xl font-bold text-green-600">{{ $skpApprovedCount }}</p>
                </div>
                <div class="w-11 h-11 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- SKP Ditolak --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 cursor-pointer hover:shadow-md transition"
             @click="tab='skp'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 mb-1 uppercase tracking-wide">SKP Ditolak</p>
                    <p class="text-3xl font-bold text-red-600">{{ $skpRejectedCount }}</p>
                </div>
                <div class="w-11 h-11 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Rekap Absensi Pending --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 cursor-pointer hover:shadow-md transition"
             @click="tab='rekap'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 mb-1 uppercase tracking-wide">Rekap Pending</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $rekapPendingCount }}</p>
                </div>
                <div class="w-11 h-11 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Laporan Bulanan Pending --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 cursor-pointer hover:shadow-md transition"
             @click="tab='laporan'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500 mb-1 uppercase tracking-wide">Laporan Pending</p>
                    <p class="text-3xl font-bold text-emerald-600">{{ $laporanPendingCount }}</p>
                </div>
                <div class="w-11 h-11 bg-emerald-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Navigation + Content --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">

        {{-- Tab Nav --}}
        <div class="flex border-b border-gray-200 px-2 pt-2">
            {{-- Tab SKP --}}
            <button @click="tab='skp'"
                :class="tab === 'skp'
                    ? 'border-blue-600 text-blue-600 bg-blue-50'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="relative flex items-center px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition-colors duration-150 focus:outline-none mr-1">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                SKP Tahunan
                @if($skpPendingCount > 0)
                <span class="ml-2 px-1.5 py-0.5 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800">
                    {{ $skpPendingCount }}
                </span>
                @endif
            </button>

            {{-- Tab Rekap Absensi --}}
            <button @click="tab='rekap'"
                :class="tab === 'rekap'
                    ? 'border-blue-600 text-blue-600 bg-blue-50'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="relative flex items-center px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition-colors duration-150 focus:outline-none">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Rekap Absensi PUSAKA
                @if($rekapPendingCount > 0)
                <span class="ml-2 px-1.5 py-0.5 text-xs font-bold rounded-full bg-blue-100 text-blue-800">
                    {{ $rekapPendingCount }}
                </span>
                @endif
            </button>

            {{-- Tab Laporan Bulanan Kinerja --}}
            <button @click="tab='laporan'"
                :class="tab === 'laporan'
                    ? 'border-emerald-600 text-emerald-700 bg-emerald-50'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="relative flex items-center px-5 py-3 text-sm font-medium border-b-2 rounded-t-lg transition-colors duration-150 focus:outline-none">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Laporan Bulanan
                @if($laporanPendingCount > 0)
                <span class="ml-2 px-1.5 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">
                    {{ $laporanPendingCount }}
                </span>
                @endif
            </button>
        </div>

        {{-- ================================================================ --}}
        {{-- TAB PANEL: SKP TAHUNAN                                          --}}
        {{-- ================================================================ --}}
        <div x-show="tab === 'skp'" x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

            @if($skpList->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-600">Tidak ada SKP Tahunan dari bawahan Anda.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama ASN</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Unit Kerja</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Tahun</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tgl Diajukan</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($skpList as $skp)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                            {{ strtoupper(substr($skp->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $skp->user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $skp->user->nip ?? '-' }} &bull; {{ $skp->user->jabatan ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ $skp->user->unitKerja->nama_unit ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-center font-semibold text-gray-800">
                                    {{ $skp->tahun }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $skp->updated_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($skp->status === 'DIAJUKAN')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                            Menunggu
                                        </span>
                                    @elseif($skp->status === 'DISETUJUI')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                            Disetujui
                                        </span>
                                    @elseif($skp->status === 'DITOLAK')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                            Ditolak
                                        </span>
                                    @elseif(str_starts_with($skp->status, 'REVISI'))
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                            Revisi
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                            {{ $skp->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('atasan.skp-tahunan.show', $skp->id) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Review
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ================================================================ --}}
        {{-- TAB PANEL: REKAP ABSENSI PUSAKA (MULTI-LEVEL)                  --}}
        {{-- ================================================================ --}}
        <div x-show="tab === 'rekap'" x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

            {{-- Filter Bar Rekap Absensi --}}
            <div class="px-6 pt-4 pb-3 flex flex-col sm:flex-row sm:items-center gap-3 border-b border-gray-100">
                {{-- Filter Status Pill --}}
                @php
                    $rekapFilters = [
                        'semua'              => ['label' => 'Semua',        'color' => 'bg-gray-100 text-gray-700 hover:bg-gray-200'],
                        'pending_kabid'      => ['label' => 'Menunggu Kabid',    'color' => 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'],
                        'pending_kakanwil'   => ['label' => 'Menunggu Kakanwil', 'color' => 'bg-blue-100 text-blue-800 hover:bg-blue-200'],
                        'approved'           => ['label' => 'Terverifikasi', 'color' => 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'],
                        'rejected_kabid'     => ['label' => 'Ditolak Kabid',    'color' => 'bg-red-100 text-red-700 hover:bg-red-200'],
                        'rejected_kakanwil'  => ['label' => 'Ditolak Kakanwil', 'color' => 'bg-red-100 text-red-700 hover:bg-red-200'],
                    ];
                    // Kabid hanya tampilkan status relevan untuk levelnya
                    if ($isKabid) {
                        unset($rekapFilters['pending_kakanwil'], $rekapFilters['rejected_kakanwil']);
                    }
                @endphp
                <div class="flex items-center gap-2 flex-wrap flex-1">
                    @foreach($rekapFilters as $val => $opt)
                        <a href="{{ route('atasan.approval.index', array_merge(request()->query(), ['rekap_filter' => $val, 'rekap_bulan' => $rekapBulan, 'tab' => 'rekap'])) }}"
                           class="px-3 py-1 text-xs font-semibold rounded-full transition {{ $opt['color'] }}
                                  {{ ($rekapFilter ?? 'semua') === $val ? 'ring-2 ring-offset-1 ring-current' : '' }}">
                            {{ $opt['label'] }}
                            @if($val === 'pending_kabid' && $isKabid && $rekapPendingCount > 0)
                                <span class="ml-1 bg-yellow-600 text-white text-xs px-1.5 rounded-full">{{ $rekapPendingCount }}</span>
                            @elseif($val === 'pending_kakanwil' && !$isKabid && $rekapPendingCount > 0)
                                <span class="ml-1 bg-blue-700 text-white text-xs px-1.5 rounded-full">{{ $rekapPendingCount }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>

                {{-- Dropdown Filter Bulan --}}
                @php
                    $namaBulanRekap = [
                        1  => 'Januari', 2  => 'Februari', 3  => 'Maret',
                        4  => 'April',   5  => 'Mei',      6  => 'Juni',
                        7  => 'Juli',    8  => 'Agustus',  9  => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ];
                    $opsiBulanRekap = [];
                    for ($i = 11; $i >= 0; $i--) {
                        $tgl = \Carbon\Carbon::now()->subMonths($i);
                        $key = $tgl->format('Y-m');
                        $opsiBulanRekap[$key] = $namaBulanRekap[(int)$tgl->format('n')] . ' ' . $tgl->format('Y');
                    }
                @endphp
                <form method="GET" action="{{ route('atasan.approval.index') }}" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="rekap">
                    <input type="hidden" name="rekap_filter" value="{{ $rekapFilter }}">
                    <label class="text-xs text-gray-500 whitespace-nowrap">Bulan:</label>
                    <select name="rekap_bulan" onchange="this.form.submit()"
                            class="text-xs border border-gray-300 rounded-lg px-2 py-1.5 bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <option value="semua" {{ ($rekapBulan ?? 'semua') === 'semua' ? 'selected' : '' }}>Semua Bulan</option>
                        @foreach($opsiBulanRekap as $key => $label)
                            <option value="{{ $key }}" {{ ($rekapBulan ?? 'semua') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if($rekapList->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-600">
                        @if(($rekapFilter ?? 'semua') === 'semua' && ($rekapBulan ?? 'semua') === 'semua')
                            @if($isKabid) Belum ada rekap absensi dari bawahan Anda.
                            @else Belum ada rekap absensi yang perlu diproses.
                            @endif
                        @elseif(($rekapFilter ?? 'semua') !== 'semua' && ($rekapBulan ?? 'semua') !== 'semua')
                            Tidak ada rekap dengan status "{{ $rekapFilters[$rekapFilter]['label'] ?? $rekapFilter }}"
                            pada {{ $opsiBulanRekap[$rekapBulan] ?? $rekapBulan }}.
                        @elseif(($rekapFilter ?? 'semua') !== 'semua')
                            Tidak ada rekap dengan status "{{ $rekapFilters[$rekapFilter]['label'] ?? $rekapFilter }}".
                        @else
                            Tidak ada rekap pada bulan {{ $opsiBulanRekap[$rekapBulan] ?? $rekapBulan }}.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama ASN</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Unit Kerja</th>
                                @if(!$isKabid)
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kabid</th>
                                @endif
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bulan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tgl Upload</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($rekapList as $rekap)
                            <tr class="hover:bg-gray-50 transition" x-data="{ open: false }">
                                <td class="px-6 py-4 text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                            {{ strtoupper(substr($rekap->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $rekap->user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $rekap->user->nip ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ $rekap->user->unitKerja->nama_unit ?? '-' }}
                                </td>
                                @if(!$isKabid)
                                <td class="px-6 py-4 text-gray-700 text-xs">
                                    {{ $rekap->verifier->name ?? ($rekap->user->atasan->name ?? '-') }}
                                </td>
                                @endif
                                <td class="px-6 py-4 font-medium text-gray-800">
                                    {{ $rekap->nama_bulan }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $rekap->created_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $rekap->status_badge_class }}">
                                        {{ $rekap->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="open = true"
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium rounded-lg transition">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Review
                                    </button>

                                    {{-- ── MODAL REVIEW ─────────────────────────────────── --}}
                                    <div x-show="open" x-transition
                                         class="fixed inset-0 z-50 flex items-center justify-center px-4"
                                         style="display: none;">

                                        {{-- Backdrop --}}
                                        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

                                        {{-- Modal Panel --}}
                                        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg z-10 max-h-screen overflow-y-auto">

                                            {{-- Modal Header --}}
                                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                                                <div>
                                                    <h3 class="text-lg font-bold text-gray-900">Review Rekap Absensi</h3>
                                                    <p class="text-xs text-gray-500 mt-0.5">
                                                        @if($isKabid)
                                                            Persetujuan Level Kabid
                                                        @else
                                                            Persetujuan Final Kakanwil
                                                        @endif
                                                    </p>
                                                </div>
                                                <button @click="open = false"
                                                        class="text-gray-400 hover:text-gray-600 transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            {{-- Modal Body: Detail Info --}}
                                            <div class="px-6 py-4 space-y-3">
                                                <dl class="grid grid-cols-3 gap-x-4 gap-y-3 text-sm">
                                                    <dt class="font-medium text-gray-500">ASN</dt>
                                                    <dd class="col-span-2 text-gray-900 font-semibold">{{ $rekap->user->name }}</dd>

                                                    <dt class="font-medium text-gray-500">NIP</dt>
                                                    <dd class="col-span-2 text-gray-700">{{ $rekap->user->nip ?? '-' }}</dd>

                                                    <dt class="font-medium text-gray-500">Unit Kerja</dt>
                                                    <dd class="col-span-2 text-gray-700">{{ $rekap->user->unitKerja->nama_unit ?? '-' }}</dd>

                                                    <dt class="font-medium text-gray-500">Bulan</dt>
                                                    <dd class="col-span-2 font-semibold text-gray-900">{{ $rekap->nama_bulan }}</dd>

                                                    <dt class="font-medium text-gray-500">Tgl Upload</dt>
                                                    <dd class="col-span-2 text-gray-700">{{ $rekap->created_at->format('d M Y, H:i') }}</dd>

                                                    <dt class="font-medium text-gray-500">Status</dt>
                                                    <dd class="col-span-2">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $rekap->status_badge_class }}">
                                                            {{ $rekap->status_label }}
                                                        </span>
                                                    </dd>

                                                    <dt class="font-medium text-gray-500">Link Drive</dt>
                                                    <dd class="col-span-2">
                                                        <a href="{{ $rekap->link_drive }}" target="_blank" rel="noopener noreferrer"
                                                           class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium transition">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                            </svg>
                                                            Buka File
                                                        </a>
                                                    </dd>

                                                    @if($rekap->revision_count > 0)
                                                    <dt class="font-medium text-gray-500">Revisi ke-</dt>
                                                    <dd class="col-span-2 text-gray-700">{{ $rekap->revision_count }}</dd>
                                                    @endif

                                                    {{-- Catatan Kabid --}}
                                                    @if($rekap->catatan)
                                                    <dt class="font-medium text-gray-500">Catatan Kabid</dt>
                                                    <dd class="col-span-2 text-gray-700 italic">{{ $rekap->catatan }}</dd>
                                                    @endif

                                                    {{-- Catatan Kakanwil --}}
                                                    @if($rekap->catatan_kakanwil)
                                                    <dt class="font-medium text-gray-500">Catatan Kakanwil</dt>
                                                    <dd class="col-span-2 text-gray-700 italic">{{ $rekap->catatan_kakanwil }}</dd>
                                                    @endif
                                                </dl>
                                            </div>

                                            {{-- Modal Footer: Action --}}
                                            <div class="px-6 py-4 border-t border-gray-200" x-data="{ confirmAction: '' }">

                                                @php
                                                    $isPendingForMe = $isKabid
                                                        ? $rekap->status === \App\Models\RekapAbsensiPusaka::STATUS_PENDING_KABID
                                                        : $rekap->status === \App\Models\RekapAbsensiPusaka::STATUS_PENDING_KAKANWIL;
                                                @endphp

                                                @if($isPendingForMe)
                                                    {{-- Tombol Setujui --}}
                                                    <form method="POST"
                                                          action="{{ route('atasan.approval.rekap-absensi.approve', $rekap->id) }}"
                                                          x-show="confirmAction === '' || confirmAction === 'setujui'"
                                                          x-transition>
                                                        @csrf
                                                        <textarea name="catatan" rows="2" maxlength="500"
                                                                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent mb-3"
                                                                  placeholder="Catatan (opsional)..."></textarea>
                                                        <button type="submit"
                                                                @click="confirmAction = 'setujui'"
                                                                class="w-full flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition">
                                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            @if($isKabid) Setujui & Teruskan ke Kakanwil @else Setujui Final @endif
                                                        </button>
                                                    </form>

                                                    <div class="relative my-3">
                                                        <div class="absolute inset-0 flex items-center">
                                                            <div class="w-full border-t border-gray-200"></div>
                                                        </div>
                                                        <div class="relative flex justify-center text-xs">
                                                            <span class="bg-white px-2 text-gray-400">atau</span>
                                                        </div>
                                                    </div>

                                                    {{-- Tombol Tolak --}}
                                                    <div x-data="{ showTolak: false }">
                                                        <button type="button"
                                                                @click="showTolak = !showTolak"
                                                                class="w-full flex items-center justify-center px-4 py-2.5 border border-red-300 text-red-700 hover:bg-red-50 text-sm font-semibold rounded-lg transition">
                                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                            Tolak
                                                        </button>

                                                        <form x-show="showTolak" x-transition
                                                              method="POST"
                                                              action="{{ route('atasan.approval.rekap-absensi.reject', $rekap->id) }}"
                                                              class="mt-3">
                                                            @csrf
                                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                Alasan Penolakan <span class="text-red-500">*</span>
                                                            </label>
                                                            <textarea name="catatan" rows="3" maxlength="500" required
                                                                      class="w-full px-3 py-2 border border-red-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent mb-3"
                                                                      placeholder="Tuliskan alasan penolakan..."></textarea>
                                                            <button type="submit"
                                                                    class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition">
                                                                Konfirmasi Penolakan
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    {{-- Sudah diproses / bukan giliran kita --}}
                                                    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-3">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        <span>Status: <strong>{{ $rekap->status_label }}</strong></span>
                                                    </div>
                                                    <button @click="open = false"
                                                            class="w-full py-2 border border-gray-300 text-sm text-gray-600 rounded-lg hover:bg-gray-50 transition">
                                                        Tutup
                                                    </button>
                                                @endif

                                            </div>

                                        </div>{{-- end modal panel --}}
                                    </div>{{-- end modal --}}

                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ================================================================ --}}
        {{-- TAB PANEL: LAPORAN BULANAN KINERJA                             --}}
        {{-- ================================================================ --}}
        <div x-show="tab === 'laporan'" x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

            {{-- Filter Bar --}}
            <div class="px-6 pt-4 pb-3 flex flex-col sm:flex-row sm:items-center gap-3 border-b border-gray-100">
                {{-- Filter Status Pill --}}
                @php
                    $filters = [
                        'semua'    => ['label' => 'Semua', 'color' => 'bg-gray-100 text-gray-700 hover:bg-gray-200'],
                        'DIKIRIM'  => ['label' => 'Menunggu',     'color' => 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'],
                        'DISETUJUI'=> ['label' => 'Terverifikasi','color' => 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'],
                        'DITOLAK'  => ['label' => 'Ditolak',      'color' => 'bg-red-100 text-red-700 hover:bg-red-200'],
                    ];
                @endphp
                <div class="flex items-center gap-2 flex-wrap flex-1">
                    @foreach($filters as $val => $opt)
                        <a href="{{ route('atasan.approval.index', array_merge(request()->query(), ['laporan_filter' => $val, 'laporan_bulan' => $laporanBulan, 'tab' => 'laporan'])) }}"
                           class="px-3 py-1 text-xs font-semibold rounded-full transition {{ $opt['color'] }}
                                  {{ ($laporanFilter ?? 'semua') === $val ? 'ring-2 ring-offset-1 ring-current' : '' }}">
                            {{ $opt['label'] }}
                            @if($val === 'DIKIRIM' && $laporanPendingCount > 0)
                                <span class="ml-1 bg-yellow-600 text-white text-xs px-1.5 rounded-full">{{ $laporanPendingCount }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>

                {{-- Dropdown Filter Bulan --}}
                @php
                    $namaBulanList = [
                        1  => 'Januari', 2  => 'Februari', 3  => 'Maret',
                        4  => 'April',   5  => 'Mei',      6  => 'Juni',
                        7  => 'Juli',    8  => 'Agustus',  9  => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ];
                    $tahunSekarang = \Carbon\Carbon::now()->year;
                    // Buat daftar opsi bulan: 12 bulan terakhir + bulan berjalan
                    $opsibulan = [];
                    for ($i = 11; $i >= 0; $i--) {
                        $tgl   = \Carbon\Carbon::now()->subMonths($i);
                        $key   = $tgl->format('Y-m');
                        $label = $namaBulanList[(int)$tgl->format('n')] . ' ' . $tgl->format('Y');
                        $opsibulan[$key] = $label;
                    }
                @endphp
                <form method="GET" action="{{ route('atasan.approval.index') }}" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="laporan">
                    <input type="hidden" name="laporan_filter" value="{{ $laporanFilter }}">
                    <label class="text-xs text-gray-500 whitespace-nowrap">Bulan:</label>
                    <select name="laporan_bulan" onchange="this.form.submit()"
                            class="text-xs border border-gray-300 rounded-lg px-2 py-1.5 bg-white focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="semua" {{ ($laporanBulan ?? 'semua') === 'semua' ? 'selected' : '' }}>Semua Bulan</option>
                        @foreach($opsibulan as $key => $label)
                            <option value="{{ $key }}" {{ ($laporanBulan ?? 'semua') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if($laporanList->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-600">
                        @if(($laporanFilter ?? 'semua') === 'semua' && ($laporanBulan ?? 'semua') === 'semua')
                            Belum ada laporan bulanan dari bawahan.
                        @elseif(($laporanFilter ?? 'semua') !== 'semua' && ($laporanBulan ?? 'semua') !== 'semua')
                            Tidak ada laporan dengan status "{{ $filters[$laporanFilter]['label'] ?? $laporanFilter }}"
                            pada {{ $opsibulan[$laporanBulan] ?? $laporanBulan }}.
                        @elseif(($laporanFilter ?? 'semua') !== 'semua')
                            Tidak ada laporan dengan status "{{ $filters[$laporanFilter]['label'] ?? $laporanFilter }}".
                        @else
                            Tidak ada laporan pada bulan {{ $opsibulan[$laporanBulan] ?? $laporanBulan }}.
                        @endif
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Laporan muncul setelah ASN menekan tombol "Kirim ke Atasan".</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama ASN</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Unit Kerja</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bulan</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Hari Kerja</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Jam</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Capaian</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($laporanList as $lap)
                            <tr class="hover:bg-gray-50 transition" x-data="{ openLaporan: false }">
                                <td class="px-6 py-4 text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-emerald-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                            {{ strtoupper(substr($lap->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $lap->user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $lap->user->nip ?? '-' }} &bull; {{ $lap->user->jabatan ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ $lap->user->unitKerja->nama_unit ?? '-' }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-800 whitespace-nowrap">{{ $lap->nama_bulan }}</td>
                                <td class="px-6 py-4 text-center text-gray-700">{{ $lap->total_hari }} hari</td>
                                <td class="px-6 py-4 text-center text-gray-700">{{ $lap->total_jam }} jam</td>
                                <td class="px-6 py-4 text-center">
                                    @php $pct = $lap->capaian_persen; @endphp
                                    <span class="font-semibold {{ $pct >= 90 ? 'text-green-700' : ($pct >= 60 ? 'text-yellow-700' : 'text-red-600') }}">
                                        {{ number_format($pct, 1) }}%
                                    </span>
                                    <span class="block text-xs text-gray-400">dari 165 jam</span>
                                </td>

                                {{-- Kolom Status --}}
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $lap->status_badge_class }}">
                                        {{ $lap->status_label }}
                                    </span>
                                    @if($lap->status === 'DISETUJUI' && $lap->approved_at)
                                        <span class="block text-xs text-gray-400 mt-0.5">{{ $lap->approved_at->format('d M Y') }}</span>
                                    @elseif($lap->status === 'DITOLAK' && $lap->approved_at)
                                        <span class="block text-xs text-gray-400 mt-0.5">{{ $lap->approved_at->format('d M Y') }}</span>
                                    @endif
                                </td>

                                {{-- Kolom Aksi --}}
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @if($lap->status === 'DIKIRIM')
                                        {{-- Tombol Review (pending) --}}
                                        <button @click="openLaporan = true"
                                                class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Review
                                        </button>
                                    @elseif($lap->status === 'DISETUJUI')
                                        {{-- Tombol Lihat Detail + Download PDF --}}
                                        <div class="flex flex-col gap-1 items-center">
                                            <button @click="openLaporan = true"
                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition">
                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Lihat Detail
                                            </button>
                                            <a href="{{ route('atasan.approval.laporan-bulanan.pdf', $lap->id) }}" target="_blank"
                                               class="inline-flex items-center px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-lg transition">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                                </svg>
                                                PDF
                                            </a>
                                        </div>
                                    @elseif($lap->status === 'DITOLAK')
                                        {{-- Tombol Lihat Catatan --}}
                                        <button @click="openLaporan = true"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded-lg transition">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Lihat Catatan
                                        </button>
                                    @endif

                                    {{-- ── MODAL ADAPTIF (Review / Lihat Detail / Lihat Catatan) ─── --}}
                                    <div x-show="openLaporan" x-transition
                                         class="fixed inset-0 z-50 flex items-center justify-center px-4"
                                         style="display: none;">
                                        <div class="absolute inset-0 bg-black/50" @click="openLaporan = false"></div>

                                        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg z-10 max-h-screen overflow-y-auto">

                                            {{-- Modal Header --}}
                                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                                                <div>
                                                    <h3 class="text-lg font-bold text-gray-900">
                                                        @if($lap->status === 'DIKIRIM') Review Laporan Bulanan
                                                        @elseif($lap->status === 'DISETUJUI') Detail Laporan Bulanan
                                                        @else Catatan Penolakan
                                                        @endif
                                                    </h3>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $lap->nama_bulan }} — {{ $lap->user->name }}</p>
                                                </div>
                                                <button @click="openLaporan = false" class="text-gray-400 hover:text-gray-600 transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            {{-- Modal Body --}}
                                            <div class="px-6 py-4 space-y-3">
                                                <dl class="grid grid-cols-3 gap-x-4 gap-y-3 text-sm">
                                                    <dt class="font-medium text-gray-500">ASN</dt>
                                                    <dd class="col-span-2 font-semibold text-gray-900">{{ $lap->user->name }}</dd>

                                                    <dt class="font-medium text-gray-500">NIP</dt>
                                                    <dd class="col-span-2 text-gray-700">{{ $lap->user->nip ?? '-' }}</dd>

                                                    <dt class="font-medium text-gray-500">Unit Kerja</dt>
                                                    <dd class="col-span-2 text-gray-700">{{ $lap->user->unitKerja->nama_unit ?? '-' }}</dd>

                                                    <dt class="font-medium text-gray-500">Periode</dt>
                                                    <dd class="col-span-2 font-semibold text-gray-900">{{ $lap->nama_bulan }}</dd>

                                                    <dt class="font-medium text-gray-500">Hari Kerja</dt>
                                                    <dd class="col-span-2 text-gray-700">{{ $lap->total_hari }} hari</dd>

                                                    <dt class="font-medium text-gray-500">Total Jam</dt>
                                                    <dd class="col-span-2 text-gray-700">{{ $lap->total_jam }} jam</dd>

                                                    <dt class="font-medium text-gray-500">Capaian</dt>
                                                    <dd class="col-span-2">
                                                        <span class="font-semibold {{ $lap->capaian_persen >= 90 ? 'text-green-700' : ($lap->capaian_persen >= 60 ? 'text-yellow-700' : 'text-red-600') }}">
                                                            {{ number_format($lap->capaian_persen, 1) }}%
                                                        </span>
                                                        <span class="text-gray-400 text-xs ml-1">dari target 165 jam/bulan</span>
                                                    </dd>

                                                    <dt class="font-medium text-gray-500">Status</dt>
                                                    <dd class="col-span-2">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $lap->status_badge_class }}">
                                                            {{ $lap->status_label }}
                                                        </span>
                                                    </dd>

                                                    <dt class="font-medium text-gray-500">Dikirim</dt>
                                                    <dd class="col-span-2 text-gray-700">{{ $lap->updated_at->format('d M Y, H:i') }}</dd>

                                                    @if($lap->approved_at)
                                                    <dt class="font-medium text-gray-500">
                                                        {{ $lap->status === 'DISETUJUI' ? 'Disetujui' : 'Ditolak' }} Tgl
                                                    </dt>
                                                    <dd class="col-span-2 text-gray-700">{{ $lap->approved_at->format('d M Y, H:i') }}</dd>
                                                    @endif

                                                    @if($lap->catatan)
                                                    <dt class="font-medium text-gray-500">
                                                        {{ $lap->status === 'DITOLAK' ? 'Alasan Tolak' : 'Catatan' }}
                                                    </dt>
                                                    <dd class="col-span-2 text-gray-700 italic">
                                                        <span class="{{ $lap->status === 'DITOLAK' ? 'text-red-600' : 'text-gray-600' }}">
                                                            "{{ $lap->catatan }}"
                                                        </span>
                                                    </dd>
                                                    @endif

                                                    <dt class="font-medium text-gray-500">Rekap PDF</dt>
                                                    <dd class="col-span-2">
                                                        @php
                                                            $namaFile = strtolower(str_replace(' ', '_', $lap->user->name))
                                                                      . '_' . strtolower(\Carbon\Carbon::create($lap->tahun, $lap->bulan, 1)->locale('id')->isoFormat('MMMM'))
                                                                      . '_' . $lap->tahun . '.pdf';
                                                        @endphp
                                                        <a href="{{ route('atasan.approval.laporan-bulanan.pdf', $lap->id) }}"
                                                           target="_blank"
                                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                            </svg>
                                                            {{ $namaFile }}
                                                        </a>
                                                    </dd>
                                                </dl>
                                            </div>

                                            {{-- Modal Footer: Aksi kondisional --}}
                                            <div class="px-6 py-4 border-t border-gray-200">

                                                @if($lap->status === 'DIKIRIM')
                                                    {{-- Form Setujui --}}
                                                    <form method="POST"
                                                          action="{{ route('atasan.approval.laporan-bulanan.approve', $lap->id) }}">
                                                        @csrf
                                                        <textarea name="catatan" rows="2" maxlength="500"
                                                                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent mb-3"
                                                                  placeholder="Catatan (opsional)..."></textarea>
                                                        <button type="submit"
                                                                class="w-full flex items-center justify-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition">
                                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            Setujui Laporan
                                                        </button>
                                                    </form>

                                                    <div class="relative my-3">
                                                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                                                        <div class="relative flex justify-center text-xs"><span class="bg-white px-2 text-gray-400">atau</span></div>
                                                    </div>

                                                    {{-- Form Tolak (accordion) --}}
                                                    <div x-data="{ showTolak: false }">
                                                        <button type="button" @click="showTolak = !showTolak"
                                                                class="w-full flex items-center justify-center px-4 py-2.5 border border-red-300 text-red-700 hover:bg-red-50 text-sm font-semibold rounded-lg transition">
                                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                            Tolak Laporan
                                                        </button>
                                                        <form x-show="showTolak" x-transition method="POST"
                                                              action="{{ route('atasan.approval.laporan-bulanan.tolak', $lap->id) }}"
                                                              class="mt-3">
                                                            @csrf
                                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                Alasan Penolakan <span class="text-red-500">*</span>
                                                            </label>
                                                            <textarea name="catatan" rows="3" maxlength="1000" required
                                                                      class="w-full px-3 py-2 border border-red-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent mb-3"
                                                                      placeholder="Tuliskan alasan penolakan agar ASN bisa memperbaiki..."></textarea>
                                                            <button type="submit"
                                                                    class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition">
                                                                Konfirmasi Penolakan
                                                            </button>
                                                        </form>
                                                    </div>

                                                @else
                                                    {{-- Sudah diproses — hanya tombol tutup --}}
                                                    <button @click="openLaporan = false"
                                                            class="w-full py-2.5 border border-gray-300 text-sm text-gray-600 rounded-lg hover:bg-gray-50 transition">
                                                        Tutup
                                                    </button>
                                                @endif

                                            </div>
                                        </div>{{-- end modal panel --}}
                                    </div>{{-- end modal --}}

                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>{{-- end tab laporan --}}

    </div>{{-- end tab card --}}

</div>{{-- end x-data --}}
@endsection
