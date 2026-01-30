@extends('layouts.app')

@section('title', 'Detail Kinerja Harian - ' . $asn->name)
@section('page-title', 'Detail Kinerja Harian')

@section('content')
<div class="space-y-6">

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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kegiatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progres</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bukti</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($progres_list as $index => $progres)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ \Carbon\Carbon::parse($progres->tanggal)->locale('id')->isoFormat('D MMM Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ \Carbon\Carbon::parse($progres->jam_mulai)->format('H:i') }} -
                            {{ \Carbon\Carbon::parse($progres->jam_selesai)->format('H:i') }}
                            <span class="text-gray-500">({{ floor($progres->durasi_menit / 60) }}j {{ $progres->durasi_menit % 60 }}m)</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($progres->tipe_progres === 'KINERJA_HARIAN')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">KH</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">TLA</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            @if($progres->tipe_progres === 'KINERJA_HARIAN')
                                <div class="max-w-md">{{ $progres->rencana_kegiatan_harian }}</div>
                            @else
                                <div class="max-w-md">{{ $progres->tugas_atasan }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $progres->progres }} {{ $progres->satuan }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($progres->status_bukti === 'SUDAH_ADA')
                                <a href="{{ $progres->bukti_dukung }}" target="_blank"
                                   class="text-green-600 hover:text-green-900 font-medium">
                                    Lihat Bukti
                                </a>
                            @else
                                <span class="text-red-600">Belum Ada</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
