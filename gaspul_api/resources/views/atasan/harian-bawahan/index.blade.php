@extends('layouts.app')

@section('title', 'Monitoring Harian Bawahan')
@section('page-title', 'Monitoring Harian Bawahan')

@section('content')
<div class="space-y-6" x-data="{ tab: '{{ $activeTab }}' }">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Monitoring Harian Bawahan</h2>
                <div class="space-y-1 text-green-100">
                    <p><span class="font-semibold">Atasan:</span> {{ $atasan->name }}</p>
                    <p><span class="font-semibold">NIP:</span> {{ $atasan->nip }}</p>
                    <p><span class="font-semibold">Unit Kerja:</span> {{ $atasan->unitKerja->nama_unit ?? '-' }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-green-100 text-sm">Tanggal Aktif</p>
                <p class="text-2xl font-bold">{{ \Carbon\Carbon::parse($tanggal)->locale('id')->isoFormat('D MMM Y') }}</p>
            </div>
        </div>
    </div>

    {{-- TAB NAV --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex border-b border-gray-200">
            <button type="button"
                    @click="tab='aktivitas'"
                    :class="tab==='aktivitas'
                        ? 'border-b-2 border-green-600 text-green-700 font-semibold bg-green-50'
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                    class="flex items-center gap-2 px-6 py-4 text-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Aktivitas Harian
            </button>
            <button type="button"
                    @click="tab='verifikasi'"
                    :class="tab==='verifikasi'
                        ? 'border-b-2 border-indigo-600 text-indigo-700 font-semibold bg-indigo-50'
                        : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                    class="flex items-center gap-2 px-6 py-4 text-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Monitoring Verifikasi
                @if($verifikasiData['totalBelum'] > 0)
                <span class="ml-1 px-1.5 py-0.5 bg-red-500 text-white text-xs rounded-full font-bold">
                    {{ $verifikasiData['totalBelum'] }}
                </span>
                @endif
            </button>
        </div>

        {{-- ===================================================== --}}
        {{-- TAB 1: AKTIVITAS HARIAN                               --}}
        {{-- ===================================================== --}}
        <div x-show="tab==='aktivitas'" x-transition>

            {{-- Filter --}}
            <div class="p-6 border-b border-gray-100">
                <form method="GET" action="{{ route('atasan.harian-bawahan.index') }}"
                      class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="tab" value="aktivitas">
                    <div class="flex-1 min-w-[140px]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mode</label>
                        <select name="mode"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm">
                            <option value="harian"   {{ $mode === 'harian'   ? 'selected' : '' }}>Harian</option>
                            <option value="mingguan" {{ $mode === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                            <option value="bulanan"  {{ $mode === 'bulanan'  ? 'selected' : '' }}>Bulanan</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[140px]">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ $tanggal }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm">
                    </div>
                    <button type="submit"
                            class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition">
                        Filter
                    </button>
                </form>
            </div>

            {{-- Tabel ASN --}}
            <div class="px-6 py-4 border-b border-gray-200">
                <p class="text-sm font-semibold text-gray-700">
                    Mode: {{ ucfirst($mode) }} &nbsp;|&nbsp; Total: {{ $bawahan_list->count() }} ASN
                </p>
            </div>

            @if($bawahan_list->isEmpty())
            <div class="p-12 text-center text-gray-500">Belum ada ASN di unit kerja Anda.</div>
            @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ASN</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Jam</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">KH</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">TLA</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($bawahan_list as $index => $asn)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $index + 1 }}</td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $asn->name }}</div>
                                <div class="text-sm text-gray-500">NIP: {{ $asn->nip }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $asn->total_jam }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $asn->total_kh }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $asn->total_tla }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{!! $asn->status_badge !!}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('atasan.harian-bawahan.detail', ['user_id' => $asn->id, 'tanggal' => $tanggal, 'mode' => $mode]) }}"
                                   class="text-blue-600 hover:text-blue-900 font-medium">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- ===================================================== --}}
        {{-- TAB 2: MONITORING VERIFIKASI EVIDEN                   --}}
        {{-- ===================================================== --}}
        <div x-show="tab==='verifikasi'" x-transition>

            {{-- Filter Bulan & Tahun --}}
            <div class="p-6 border-b border-gray-100">
                <form method="GET" action="{{ route('atasan.harian-bawahan.index') }}"
                      class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="tab" value="verifikasi">
                    {{-- pertahankan filter aktivitas --}}
                    <input type="hidden" name="mode"    value="{{ $mode }}">
                    <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                        <select name="v_bulan"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
                            @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $nm)
                            <option value="{{ $i+1 }}" {{ $vBulan == ($i+1) ? 'selected' : '' }}>{{ $nm }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                        <select name="v_tahun"
                                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none">
                            @foreach([2026,2025] as $y)
                            <option value="{{ $y }}" {{ $vTahun==$y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                        Tampilkan
                    </button>
                    <a href="{{ route('atasan.monitoring-verifikasi.export', ['v_bulan' => $vBulan, 'v_tahun' => $vTahun]) }}"
                       class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-1.5 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Excel
                    </a>
                </form>
            </div>

            {{-- KPI Baris 1: Berbasis Aktivitas --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 pb-3 border-b border-dashed border-gray-200">
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Ada Eviden</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($verifikasiData['totalAdaBukti']) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">aktivitas</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <p class="text-xs font-semibold text-green-600 uppercase mb-1">Sudah Diverifikasi</p>
                    <p class="text-2xl font-bold text-green-700">{{ number_format($verifikasiData['totalSudah']) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">aktivitas</p>
                </div>
                <div class="bg-red-50 rounded-lg p-4 text-center">
                    <p class="text-xs font-semibold text-red-500 uppercase mb-1">Belum Diverifikasi</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($verifikasiData['totalBelum']) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">aktivitas</p>
                </div>
                <div class="bg-indigo-50 rounded-lg p-4 text-center">
                    <p class="text-xs font-semibold text-indigo-500 uppercase mb-1">Progress — {{ $vPeriode }}</p>
                    <p class="text-2xl font-bold text-indigo-700">{{ $verifikasiData['persenTotal'] }}%</p>
                    <div class="mt-1.5 w-full bg-gray-200 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $verifikasiData['persenTotal']>=100 ? 'bg-green-500' : ($verifikasiData['persenTotal']>=50 ? 'bg-yellow-400' : 'bg-red-500') }}"
                             style="width:{{ min($verifikasiData['persenTotal'],100) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- KPI Baris 2: Berbasis ASN --}}
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 px-6 pt-3 pb-6 border-b border-gray-100">
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-center">
                    <p class="text-xs font-semibold text-emerald-600 uppercase mb-1">ASN Tuntas</p>
                    <p class="text-3xl font-bold text-emerald-700">{{ $verifikasiData['asnTuntas'] }}</p>
                    <p class="text-xs text-emerald-500 mt-0.5">Seluruh eviden sudah diverifikasi</p>
                </div>
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 text-center">
                    <p class="text-xs font-semibold text-orange-600 uppercase mb-1">ASN Belum Tuntas</p>
                    <p class="text-3xl font-bold text-orange-700">{{ $verifikasiData['asnBelumTuntas'] }}</p>
                    <p class="text-xs text-orange-500 mt-0.5">Masih ada eviden belum diverifikasi</p>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">ASN Tanpa Eviden</p>
                    <p class="text-3xl font-bold text-gray-600">{{ $verifikasiData['asnTanpaEviden'] }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Tidak masuk antrian verifikasi</p>
                </div>
            </div>

            {{-- Panel Ringkasan Pembinaan --}}
            <div class="mx-6 mb-4 mt-2 rounded-lg px-4 py-3 text-sm
                {{ $verifikasiData['asnBelumTuntas'] > 0
                    ? 'bg-orange-50 border border-orange-200 text-orange-800'
                    : 'bg-emerald-50 border border-emerald-200 text-emerald-800' }}">
                <span class="font-semibold">Ringkasan Pembinaan:</span>
                ASN Tuntas: <strong>{{ $verifikasiData['asnTuntas'] }}</strong> &nbsp;|&nbsp;
                ASN Belum Tuntas: <strong>{{ $verifikasiData['asnBelumTuntas'] }}</strong>
                &nbsp;&mdash;&nbsp;
                @if($verifikasiData['asnBelumTuntas'] > 0)
                    Terdapat <strong>{{ $verifikasiData['asnBelumTuntas'] }} ASN</strong> yang masih memiliki eviden belum diverifikasi.
                @else
                    Seluruh ASN dengan eviden pada periode ini telah diverifikasi.
                @endif
            </div>

            {{-- Tabel per ASN --}}
            @if($verifikasiData['list']->isEmpty())
            <div class="p-12 text-center text-gray-500">Tidak ada data aktivitas untuk periode ini.</div>
            @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ASN</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ada Eviden</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sudah</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Belum</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Link Kosong</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Progress</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($verifikasiData['list'] as $i => $asn)
                        @php
                            $persen = (float) $asn->persen;
                            // Badge progress (berbasis aktivitas)
                            if ($persen >= 100)    { $badgePct = 'bg-green-100 text-green-800';   $labelPct = 'Selesai'; }
                            elseif ($persen >= 50) { $badgePct = 'bg-yellow-100 text-yellow-800'; $labelPct = 'Proses'; }
                            else                   { $badgePct = 'bg-red-100 text-red-800';       $labelPct = 'Perlu Perhatian'; }
                            // Badge ASN (berbasis tuntas/belum)
                            if ($asn->ada_bukti === 0)       { $badgeAsn = 'bg-gray-100 text-gray-500';    $labelAsn = 'Tanpa Eviden'; }
                            elseif ($asn->belum_verif === 0) { $badgeAsn = 'bg-emerald-100 text-emerald-800'; $labelAsn = 'Tuntas'; }
                            else                             { $badgeAsn = 'bg-orange-100 text-orange-800'; $labelAsn = 'Belum Tuntas'; }
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $i+1 }}</td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-gray-800">{{ $asn->name }}</p>
                                <p class="text-xs text-gray-500">{{ $asn->nip ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-medium text-gray-700">
                                {{ $asn->ada_bukti > 0 ? number_format($asn->ada_bukti) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold text-green-700">
                                {{ $asn->sudah_verif > 0 ? number_format($asn->sudah_verif) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold {{ $asn->belum_verif > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                {{ number_format($asn->belum_verif) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-500">
                                {{ $asn->link_kosong > 0 ? number_format($asn->link_kosong) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($asn->ada_bukti > 0)
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full {{ $persen>=100 ? 'bg-green-500' : ($persen>=50 ? 'bg-yellow-400' : 'bg-red-500') }}"
                                             style="width:{{ min($persen,100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-700 whitespace-nowrap">{{ $persen }}%</span>
                                </div>
                                @else
                                <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $badgeAsn }}">{{ $labelAsn }}</span>
                                    @if($asn->ada_bukti > 0)
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $badgePct }} opacity-70">{{ $labelPct }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('atasan.harian-bawahan.detail', ['user_id' => $asn->id, 'tanggal' => now()->format('Y-m-d'), 'mode' => 'bulanan']) }}"
                                   class="text-xs text-indigo-600 hover:text-indigo-900 font-medium whitespace-nowrap">
                                    Verifikasi →
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
                <span class="ml-auto text-gray-400">Link Kosong tidak masuk hitungan progress verifikasi.</span>
            </div>
            @endif
        </div>

    </div>{{-- /card utama --}}

</div>
@endsection
