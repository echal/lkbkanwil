@extends('layouts.app')

@section('title', 'Monitoring Verifikasi Eviden')
@section('page-title', 'Monitoring Verifikasi Eviden')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Monitoring Verifikasi Eviden</h2>
                <div class="space-y-1 text-indigo-100">
                    <p><span class="font-semibold">Atasan:</span> {{ $atasan->name }}</p>
                    <p><span class="font-semibold">NIP:</span> {{ $atasan->nip ?? '-' }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-indigo-200 text-sm">Periode</p>
                <p class="text-2xl font-bold">{{ $periode }}</p>
            </div>
        </div>
    </div>

    {{-- Filter Bulan & Tahun --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <form method="GET" action="{{ route('atasan.monitoring-verifikasi') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="bulan" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
                    @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $nm)
                    <option value="{{ $i+1 }}" {{ $bulan == ($i+1) ? 'selected' : '' }}>{{ $nm }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="tahun" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
                    @foreach([2026, 2025] as $y)
                    <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                Tampilkan
            </button>
        </form>
    </div>

    {{-- Kartu KPI --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Aktivitas Dengan Eviden</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($kpi['totalAdaBukti']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Sudah Diverifikasi</p>
            <p class="text-3xl font-bold text-green-600">{{ number_format($kpi['totalSudah']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Belum Diverifikasi</p>
            <p class="text-3xl font-bold text-red-600">{{ number_format($kpi['totalBelum']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-indigo-200 shadow-sm p-5 bg-indigo-50">
            <p class="text-xs font-semibold text-indigo-500 uppercase mb-1">Progress Keseluruhan</p>
            <p class="text-3xl font-bold text-indigo-700">{{ $kpi['persenTotal'] }}%</p>
            {{-- Progress bar --}}
            <div class="mt-2 w-full bg-gray-200 rounded-full h-1.5">
                <div class="h-1.5 rounded-full {{ $kpi['persenTotal'] >= 100 ? 'bg-green-500' : ($kpi['persenTotal'] >= 50 ? 'bg-yellow-400' : 'bg-red-500') }}"
                     style="width: {{ min($kpi['persenTotal'], 100) }}%"></div>
            </div>
        </div>
    </div>

    {{-- Tabel per ASN --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-800">Detail Verifikasi per ASN</h3>
            <span class="text-sm text-gray-500">{{ $list->count() }} bawahan | Periode: {{ $periode }}</span>
        </div>

        @if($list->isEmpty())
        <div class="p-12 text-center text-gray-500">Tidak ada data bawahan.</div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ASN</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ada Eviden</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sudah Verifikasi</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Belum Verifikasi</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Link Kosong</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Progress</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($list as $i => $asn)
                    @php
                        $persen = (float) $asn->persen;
                        if ($persen >= 100) {
                            $badge = 'bg-green-100 text-green-800';
                            $label = 'Selesai';
                        } elseif ($persen >= 50) {
                            $badge = 'bg-yellow-100 text-yellow-800';
                            $label = 'Proses';
                        } else {
                            $badge = 'bg-red-100 text-red-800';
                            $label = 'Perlu Perhatian';
                        }
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $i + 1 }}</td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-gray-800">{{ $asn->name }}</p>
                            <p class="text-xs text-gray-500">{{ $asn->nip ?? '-' }}</p>
                            @if($asn->jabatan)
                            <p class="text-xs text-gray-400">{{ $asn->jabatan }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-medium text-gray-700">
                            {{ $asn->ada_bukti > 0 ? number_format($asn->ada_bukti) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-semibold text-green-700">
                            {{ $asn->sudah_verif > 0 ? number_format($asn->sudah_verif) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-semibold {{ $asn->belum_verif > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            {{ $asn->belum_verif > 0 ? number_format($asn->belum_verif) : '0' }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-500">
                            {{ $asn->link_kosong > 0 ? number_format($asn->link_kosong) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($asn->ada_bukti > 0)
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-20 bg-gray-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full {{ $persen >= 100 ? 'bg-green-500' : ($persen >= 50 ? 'bg-yellow-400' : 'bg-red-500') }}"
                                         style="width: {{ min($persen, 100) }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-700">{{ $persen }}%</span>
                            </div>
                            @else
                            <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $badge }}">{{ $label }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('atasan.harian-bawahan.detail', ['user_id' => $asn->id, 'tanggal' => now()->format('Y-m-d'), 'mode' => 'bulanan']) }}"
                               class="text-xs text-indigo-600 hover:text-indigo-900 font-medium whitespace-nowrap">
                                Lihat Detail →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Legenda --}}
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex flex-wrap gap-4 text-xs text-gray-500">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Selesai (100%)</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-yellow-400 inline-block"></span> Proses (50–99%)</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Perlu Perhatian (&lt;50%)</span>
            <span class="ml-auto">
                <strong>Link Kosong</strong> = Aktivitas tanpa eviden, tidak masuk hitungan progress verifikasi.
            </span>
        </div>
        @endif
    </div>

</div>
@endsection
