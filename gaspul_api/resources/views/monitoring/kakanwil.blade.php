<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="300"> {{-- Auto-refresh 5 menit --}}
    <title>Dashboard Monitoring eSARAKu {{ $tahun }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .table-container {
            max-height: 520px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .table-container::-webkit-scrollbar { width: 5px; }
        .table-container::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        .table-monitoring { width: 100%; border-collapse: collapse; }
        .table-monitoring thead th {
            position: sticky;
            top: 0;
            background: #f9fafb;
            z-index: 10;
            border-bottom: 2px solid #e5e7eb;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

{{-- ===================================================================== --}}
{{-- HEADER --}}
{{-- ===================================================================== --}}
<header class="bg-gradient-to-r from-green-700 to-green-900 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold leading-tight">DASHBOARD MONITORING eSARAKu</h1>
                    <p class="text-green-200 text-xs">Kantor Wilayah Kemenag Sulawesi Barat — Tahun {{ $tahun }}</p>
                </div>
            </div>
            <div class="flex flex-col items-start md:items-end gap-1">
                <div class="flex items-center gap-2 text-xs text-green-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Last Update: {{ $lastUpdate }}
                </div>
                <div class="flex items-center gap-2">
                    {{-- Tombol Refresh Cache --}}
                    <a href="{{ route('monitoring.kakanwil.clear-cache', ['key' => $monitorKey]) }}"
                       title="Paksa refresh data (hapus cache)"
                       class="text-xs bg-white/10 hover:bg-white/20 border border-white/30 text-white rounded px-2 py-1 flex items-center gap-1 transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </a>
                    {{-- Filter tahun --}}
                    <form method="GET" action="{{ route('monitoring.kakanwil') }}" class="flex items-center gap-1">
                        <input type="hidden" name="key" value="{{ $monitorKey }}">
                        <select name="tahun" onchange="this.form.submit()"
                                class="text-xs bg-white/10 border border-white/30 text-white rounded px-2 py-1 focus:outline-none">
                            @for($y = now()->year; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6 space-y-6">

{{-- ===================================================================== --}}
{{-- SECTION A: KPI CARDS --}}
{{-- ===================================================================== --}}
<section>
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Ringkasan Kepatuhan SKP {{ $tahun }}</h2>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">

        {{-- Total Pegawai --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Total Pegawai</p>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($data['kpi']['total_pegawai'] ?? $data['kpi']['total_asn']) }}</p>
            <p class="text-xs text-gray-400 mt-1">
                ASN <span class="font-semibold text-gray-600">{{ number_format($data['kpi']['total_asn']) }}</span>
                &middot;
                Atasan <span class="font-semibold text-gray-600">{{ number_format($data['kpi']['total_atasan'] ?? 0) }}</span>
            </p>
        </div>

        {{-- Sudah Buat SKP --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Sudah Buat SKP</p>
            <p class="text-3xl font-bold text-blue-600">{{ number_format($data['kpi']['sudah_buat_skp']) }}</p>
            <p class="text-xs text-gray-400 mt-1">
                {{ $data['kpi']['total_asn'] > 0 ? round($data['kpi']['sudah_buat_skp'] / $data['kpi']['total_asn'] * 100, 1) : 0 }}%
            </p>
        </div>

        {{-- Sudah Diajukan --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Sudah Diajukan</p>
            <p class="text-3xl font-bold text-yellow-600">{{ number_format($data['kpi']['sudah_diajukan']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Termasuk disetujui</p>
        </div>

        {{-- Sudah Disetujui --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Sudah Disetujui</p>
            <p class="text-3xl font-bold text-green-600">{{ number_format($data['kpi']['sudah_disetujui']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Final</p>
        </div>

        {{-- Belum Buat --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Belum Buat</p>
            <p class="text-3xl font-bold text-red-500">{{ number_format($data['kpi']['belum_buat']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Perlu tindak lanjut</p>
        </div>

        {{-- Persentase Kepatuhan --}}
        @php
            $warna = $data['kpi']['warna_kepatuhan'];
            $warnaClass = match($warna) {
                'hijau'  => 'text-green-600 border-green-200 bg-green-50',
                'kuning' => 'text-yellow-600 border-yellow-200 bg-yellow-50',
                default  => 'text-red-600 border-red-200 bg-red-50',
            };
        @endphp
        <div class="rounded-xl shadow-sm border p-4 text-center {{ $warnaClass }}">
            <p class="text-xs font-medium mb-1">Kepatuhan</p>
            <p class="text-3xl font-bold">{{ $data['kpi']['persen_kepatuhan'] }}%</p>
            <p class="text-xs mt-1 font-semibold">
                @if($warna === 'hijau') ✔ Baik
                @elseif($warna === 'kuning') ⚠ Perlu Perhatian
                @else ✖ Rendah
                @endif
            </p>
        </div>

    </div>
</section>

{{-- SECTION A2: KEPATUHAN KINERJA AKTIF --}}
{{-- ===================================================================== --}}
@php
    $ka      = $data['kpi']['kinerja_aktif'] ?? ['asn_aktif'=>0,'total_asn'=>0,'persen'=>0,'warna'=>'merah','bulan'=>(int)now()->month,'tahun'=>(int)now()->year];
    $kaWarna = match($ka['warna']) {
        'hijau'  => ['card' => 'border-green-200 bg-green-50',  'text' => 'text-green-700',  'badge' => 'bg-green-100 text-green-800'],
        'kuning' => ['card' => 'border-yellow-200 bg-yellow-50','text' => 'text-yellow-700', 'badge' => 'bg-yellow-100 text-yellow-800'],
        default  => ['card' => 'border-red-200 bg-red-50',      'text' => 'text-red-700',    'badge' => 'bg-red-100 text-red-800'],
    };
    $namaBulanKa = ['','Januari','Februari','Maret','April','Mei','Juni',
                    'Juli','Agustus','September','Oktober','November','Desember'][$ka['bulan']] ?? '-';
@endphp
<section class="mt-4">
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
        Kepatuhan Kinerja Aktif — {{ $namaBulanKa }} {{ $ka['tahun'] }}
        <span class="ml-2 text-xs font-normal text-gray-400 normal-case">(aktif = ≥ 3 hari input bulan ini)</span>
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- ASN Aktif --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">ASN Aktif Kinerja Harian</p>
            <p class="text-3xl font-bold text-indigo-600">{{ number_format($ka['asn_aktif']) }}</p>
            <p class="text-xs text-gray-400 mt-1">dari {{ number_format($ka['total_asn']) }} ASN</p>
            <p class="text-[10px] text-gray-400 mt-2">Input ≥ 3 hari — bulan berjalan</p>
        </div>

        {{-- Kurang Aktif Bulan Ini --}}
        @php $mg = $data['kpi']['mingguan'] ?? ['belum_input'=>0,'kurang_aktif'=>0,'aktif'=>0,'total_belum'=>0,'periode'=>'-']; @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">ASN Kurang Aktif (Bulan Ini)</p>
            <p class="text-3xl font-bold text-orange-500">{{ number_format($mg['total_belum']) }}</p>
            <div class="mt-2 text-xs space-y-1 text-left">
                <div class="flex items-center gap-1.5 text-red-500">
                    <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                    Belum input: <span class="font-semibold">{{ number_format($mg['belum_input']) }}</span> ASN
                </div>
                <div class="flex items-center gap-1.5 text-yellow-600">
                    <span class="w-2 h-2 rounded-full bg-yellow-400 flex-shrink-0"></span>
                    Input &lt; 3 hari: <span class="font-semibold">{{ number_format($mg['kurang_aktif']) }}</span> ASN
                </div>
            </div>
            <p class="text-[10px] text-gray-400 mt-2">
                Perhitungan dari tanggal {{ $mg['periode'] }}
            </p>
        </div>

        {{-- Persentase --}}
        @php
            $progressHariKa   = $data['kpi']['progress_hari'] ?? 0;
            $thresholdTercapai = $progressHariKa >= 10;
            // Jika threshold belum bisa dicapai → override warna card ke netral
            $kaCardClass  = $thresholdTercapai ? $kaWarna['card']  : 'border-gray-200 bg-gray-50';
            $kaTextClass  = $thresholdTercapai ? $kaWarna['text']  : 'text-gray-500';
            $kaBadgeClass = $thresholdTercapai ? $kaWarna['badge'] : 'bg-gray-100 text-gray-500';
        @endphp
        <div class="rounded-xl shadow-sm border p-4 text-center {{ $kaCardClass }}">
            <p class="text-xs font-medium mb-1 {{ $kaTextClass }}">Kepatuhan Kinerja Aktif</p>
            <p class="text-3xl font-bold {{ $kaTextClass }}">{{ $ka['persen'] }}%</p>
            @if(!$thresholdTercapai)
            <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $kaBadgeClass }}">
                ⏳ Menunggu hari ke-10
            </span>
            <p class="text-[10px] text-gray-400 mt-1">
                Baru {{ $progressHariKa }} hari kerja berjalan — indikator aktif setelah hari ke-10
            </p>
            @else
            <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $kaBadgeClass }}">
                @if($ka['warna'] === 'hijau') ✔ Baik
                @elseif($ka['warna'] === 'kuning') ⚠ Perlu Perhatian
                @else ✖ Rendah
                @endif
            </span>
            @endif
        </div>

    </div>

    {{-- ② Progress Bulan + ③ ASN Aktif Hari Ini + ④ Gap Kepatuhan --}}
    @php
        $progressHari    = $data['kpi']['progress_hari']    ?? 0;
        $totalHariKerja  = $data['kpi']['total_hari_kerja'] ?? 0;
        $aktifHariIni    = $data['kpi']['asn_aktif_hari_ini'] ?? ['asn_aktif'=>0,'total_asn'=>0,'persen'=>0];
        $gap             = $data['kpi']['gap_kepatuhan'] ?? 0;
        $gapClass        = abs($gap) > 20 ? 'text-red-500 font-semibold' : 'text-gray-700 font-medium';
        $isAwalBulan     = $progressHari <= 5;
    @endphp
    <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">

        {{-- ② Progress Bulan --}}
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 flex items-start gap-3">
            <div class="mt-0.5 flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500 leading-tight">Progress Bulan Ini</p>
                <p class="text-sm font-semibold text-gray-800 mt-0.5">
                    {{ $progressHari }} / {{ $totalHariKerja }} hari kerja
                </p>
                @if($isAwalBulan)
                <p class="text-xs text-indigo-500 mt-0.5">Masih awal bulan → wajar belum tinggi</p>
                @else
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $totalHariKerja > 0 ? round($progressHari / $totalHariKerja * 100) : 0 }}% bulan telah berjalan
                </p>
                @endif
            </div>
        </div>

        {{-- ③ ASN Aktif Hari Ini --}}
        @php
            $cardBorder = match($statusWaktu ?? 'jam_kerja') {
                'libur'           => 'border-red-200 bg-red-50',
                'belum_jam_kerja' => 'border-yellow-200 bg-yellow-50',
                default           => 'border-gray-200 bg-white',
            };
            $iconBg = match($statusWaktu ?? 'jam_kerja') {
                'libur'           => 'bg-red-100',
                'belum_jam_kerja' => 'bg-yellow-100',
                default           => 'bg-green-50',
            };
            $iconColor = match($statusWaktu ?? 'jam_kerja') {
                'libur'           => 'text-red-400',
                'belum_jam_kerja' => 'text-yellow-500',
                default           => 'text-green-500',
            };
            $msgColor = match($statusWaktu ?? 'jam_kerja') {
                'libur'           => 'text-red-500',
                'belum_jam_kerja' => 'text-yellow-600',
                default           => '',
            };
        @endphp
        <div class="rounded-lg border px-4 py-3 flex items-start gap-3 {{ $cardBorder }} cursor-pointer hover:shadow-md hover:border-green-300 transition-all duration-150 select-none"
             onclick="bukaModalAsnAktif()"
             title="Klik untuk lihat detail per unit kerja">
            <div class="mt-0.5 flex-shrink-0 w-8 h-8 rounded-full {{ $iconBg }} flex items-center justify-center">
                <svg class="w-4 h-4 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div class="min-w-0 w-full">
                <p class="text-xs text-gray-500 leading-tight">
                    ASN Aktif Hari Ini
                    <span class="ml-1 text-[10px] text-green-500 font-medium">▶ Detail</span>
                </p>
                <p class="text-sm font-semibold text-gray-800 mt-0.5">
                    {{ number_format($aktifHariIni['asn_aktif']) }} / {{ number_format($aktifHariIni['total_asn']) }}
                    <span class="text-gray-500 font-normal">({{ $aktifHariIni['persen'] }}%)</span>
                </p>
                <div class="text-xs mt-1 text-gray-600 space-y-0.5">
                    <div>Kinerja Harian: <span class="text-indigo-500 font-medium">{{ $aktifHariIni['kh'] ?? 0 }}</span></div>
                    <div>Tugas Atasan: <span class="text-purple-500 font-medium">{{ $aktifHariIni['tla'] ?? 0 }}</span></div>
                </div>
                <div class="text-[10px] text-gray-400 mt-2">🟢 Berdasarkan aktivitas hari ini (terus diperbarui)</div>
                @if($messageWaktu ?? null)
                <p class="text-xs mt-1.5 {{ $msgColor }}">{{ $messageWaktu }}</p>
                @endif
                <p class="text-[10px] text-gray-400 mt-1">Update: {{ $jamSekarang ?? '-' }}</p>
                @if($jamKerja ?? null)
                <p class="text-[10px] text-gray-400">Jam kerja: {{ $jamKerja }}</p>
                @endif
            </div>
        </div>

        {{-- ④ Gap Kepatuhan --}}
        <div class="bg-white rounded-lg border border-gray-200 px-4 py-3 flex items-start gap-3">
            <div class="mt-0.5 flex-shrink-0 w-8 h-8 rounded-full bg-orange-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500 leading-tight">Gap Kepatuhan</p>
                <p class="text-sm {{ $gapClass }} mt-0.5">
                    SKP {{ $data['kpi']['persen_kepatuhan'] }}% vs Kinerja {{ $ka['persen'] }}%
                </p>
                <p class="text-xs mt-0.5 {{ abs($gap) > 20 ? 'text-red-400' : 'text-gray-400' }}">
                    Selisih {{ abs($gap) }}%
                    @if(abs($gap) > 20) — perlu perhatian @endif
                </p>
            </div>
        </div>

    </div>
</section>

{{-- ===================================================================== --}}
{{-- SECTION B + C: CHARTS (2 kolom) --}}
{{-- ===================================================================== --}}
<section class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Bar Chart per Unit --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Kepatuhan per Unit Kerja</h3>
        <div class="relative" style="height: 300px">
            <canvas id="chartPerUnit"></canvas>
        </div>
    </div>

    {{-- Pie Chart Status Distribution --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Distribusi Status SKP</h3>
        <div class="relative" style="height: 300px">
            <canvas id="chartStatus"></canvas>
        </div>
        {{-- Legend manual --}}
        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-green-500 flex-shrink-0"></span>
                <span class="text-gray-600">Disetujui: <strong>{{ $data['status_distribution']['DISETUJUI'] }}</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-yellow-400 flex-shrink-0"></span>
                <span class="text-gray-600">Diajukan: <strong>{{ $data['status_distribution']['DIAJUKAN'] }}</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-blue-400 flex-shrink-0"></span>
                <span class="text-gray-600">Draft: <strong>{{ $data['status_distribution']['DRAFT'] }}</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-400 flex-shrink-0"></span>
                <span class="text-gray-600">Ditolak: <strong>{{ $data['status_distribution']['DITOLAK'] }}</strong></span>
            </div>
        </div>
    </div>

</section>

{{-- ===================================================================== --}}
{{-- SECTION D: RANKING --}}
{{-- ===================================================================== --}}
<section class="grid grid-cols-1 md:grid-cols-2 gap-6">

    {{-- Top 5 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-lg">🏆</span>
            <h3 class="text-sm font-semibold text-gray-700">Top 5 Unit Tertinggi</h3>
        </div>
        <div class="space-y-2">
            @foreach($data['ranking_top'] as $i => $unit)
            @php
                $barColor = match($unit['warna']) {
                    'hijau'  => 'bg-green-500',
                    'kuning' => 'bg-yellow-400',
                    default  => 'bg-red-400',
                };
                $textColor = match($unit['warna']) {
                    'hijau'  => 'text-green-700',
                    'kuning' => 'text-yellow-700',
                    default  => 'text-red-700',
                };
            @endphp
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-gray-400 w-5 text-right">{{ $i + 1 }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-0.5">
                        <span class="text-xs font-medium text-gray-700 truncate">{{ $unit['nama_unit'] }}</span>
                        <span class="text-xs font-bold {{ $textColor }} ml-2 flex-shrink-0">{{ $unit['persen'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="{{ $barColor }} h-1.5 rounded-full transition-all" style="width: {{ min(100, $unit['persen']) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $unit['total_disetujui'] }}/{{ $unit['total_asn'] }} ASN</p>
                </div>
            </div>
            @endforeach
            @if(empty($data['ranking_top']))
            <p class="text-xs text-gray-400 text-center py-4">Belum ada data</p>
            @endif
        </div>
    </div>

    {{-- Bottom 5 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-lg">⚠️</span>
            <h3 class="text-sm font-semibold text-gray-700">5 Unit Perlu Perhatian</h3>
        </div>
        <div class="space-y-2">
            @foreach($data['ranking_bottom'] as $i => $unit)
            @php
                $barColor = match($unit['warna']) {
                    'hijau'  => 'bg-green-500',
                    'kuning' => 'bg-yellow-400',
                    default  => 'bg-red-400',
                };
                $textColor = match($unit['warna']) {
                    'hijau'  => 'text-green-700',
                    'kuning' => 'text-yellow-700',
                    default  => 'text-red-700',
                };
            @endphp
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-gray-400 w-5 text-right">{{ $i + 1 }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-0.5">
                        <span class="text-xs font-medium text-gray-700 truncate">{{ $unit['nama_unit'] }}</span>
                        <span class="text-xs font-bold {{ $textColor }} ml-2 flex-shrink-0">{{ $unit['persen'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="{{ $barColor }} h-1.5 rounded-full transition-all" style="width: {{ min(100, $unit['persen']) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $unit['total_disetujui'] }}/{{ $unit['total_asn'] }} ASN</p>
                </div>
            </div>
            @endforeach
            @if(empty($data['ranking_bottom']))
            <p class="text-xs text-gray-400 text-center py-4">Belum ada data</p>
            @endif
        </div>
    </div>

</section>

{{-- ===================================================================== --}}
{{-- SECTION E: TABEL DETAIL PER UNIT --}}
{{-- ===================================================================== --}}
<section>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">Detail Kepatuhan per Unit Kerja</h3>
            <span class="text-xs text-gray-400">Diurutkan: Kepatuhan Tertinggi → Terendah</span>
        </div>
        <div class="table-container">
            <table class="table-monitoring text-sm">
                <thead class="text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-center w-10">#</th>
                        <th class="px-4 py-3 text-left">Unit Kerja</th>
                        <th class="px-4 py-3 text-center">Total ASN</th>
                        <th class="px-4 py-3 text-center">Sudah Buat SKP</th>
                        <th class="px-4 py-3 text-center">Belum Buat</th>
                        <th class="px-4 py-3 text-center">Disetujui</th>
                        <th class="px-4 py-3 text-center">Kepatuhan ↓</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($data['per_unit'] as $i => $unit)
                    @php
                        $statusBadge = match($unit['warna']) {
                            'hijau'  => 'bg-green-100 text-green-700',
                            'kuning' => 'bg-yellow-100 text-yellow-700',
                            default  => 'bg-red-100 text-red-700',
                        };
                        $statusText = match($unit['warna']) {
                            'hijau'  => 'Baik',
                            'kuning' => 'Perlu Perhatian',
                            default  => 'Rendah',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-center text-xs text-gray-400 font-mono">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $unit['nama_unit'] }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $unit['total_asn'] }}</td>
                        <td class="px-4 py-3 text-center text-blue-600 font-medium">{{ $unit['total_buat'] }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($unit['belum_buat'] > 0)
                                <button
                                    onclick="bukaModalSkp({{ $unit['unit_id'] }}, 'belum_buat', {{ $tahun }}, '{{ addslashes($unit['nama_unit']) }}')"
                                    class="text-red-500 font-medium hover:underline hover:text-red-700 cursor-pointer focus:outline-none"
                                    title="Klik untuk lihat nama ASN belum buat SKP">
                                    {{ $unit['belum_buat'] }}
                                </button>
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($unit['total_disetujui'] > 0)
                                <button
                                    onclick="bukaModalSkp({{ $unit['unit_id'] }}, 'disetujui', {{ $tahun }}, '{{ addslashes($unit['nama_unit']) }}')"
                                    class="text-green-600 font-medium hover:underline hover:text-green-800 cursor-pointer focus:outline-none"
                                    title="Klik untuk lihat nama ASN SKP disetujui">
                                    {{ $unit['total_disetujui'] }}
                                </button>
                            @else
                                <span class="text-gray-400">0</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 bg-gray-100 rounded-full h-1.5">
                                    @php $barColorUnit = $unit['warna'] === 'hijau' ? 'bg-green-500' : ($unit['warna'] === 'kuning' ? 'bg-yellow-400' : 'bg-red-400'); @endphp
                                <div class="{{ $barColorUnit }} h-1.5 rounded-full"
                                         style="width: {{ min(100, $unit['persen']) }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-700">{{ $unit['persen'] }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusBadge }}">
                                {{ $statusText }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">Belum ada data unit kerja.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- ===================================================================== --}}
{{-- MODAL: Drill-down SKP per Unit (Belum Buat / Disetujui + tab Belum Disetujui) --}}
{{-- ===================================================================== --}}
<div id="modalSkpDetail"
     class="fixed inset-0 bg-black/50 z-50 hidden items-start justify-center pt-16 px-4"
     role="dialog" aria-modal="true">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-xl flex flex-col max-h-[78vh]">

        {{-- Header --}}
        <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
            <div class="min-w-0">
                <p id="skpModalLabel" class="text-xs text-gray-400 font-medium uppercase tracking-wide"></p>
                <h3 id="skpModalTitle" class="text-sm font-semibold text-gray-800 mt-0.5 truncate"></h3>
            </div>
            <button onclick="tutupModalSkp()"
                    class="ml-4 flex-shrink-0 text-gray-400 hover:text-gray-600 focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Tab (hanya muncul saat mode disetujui) --}}
        <div id="skpModalTabs" class="hidden flex-shrink-0 border-b border-gray-100 px-5 pt-2 flex gap-4">
            <button id="skpTabDisetujui"
                    onclick="gantiTabSkp('disetujui')"
                    class="pb-2 text-sm font-medium border-b-2 transition-colors">
                ✅ Disetujui <span id="skpTabCountDisetujui" class="ml-1 text-xs text-gray-400"></span>
            </button>
            <button id="skpTabBelumDisetujui"
                    onclick="gantiTabSkp('belum_disetujui')"
                    class="pb-2 text-sm font-medium border-b-2 transition-colors">
                ⏳ Belum Disetujui <span id="skpTabCountBelum" class="ml-1 text-xs text-gray-400"></span>
            </button>
        </div>

        {{-- Loading --}}
        <div id="skpModalLoading" class="flex-1 flex items-center justify-center py-12">
            <svg class="w-6 h-6 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span class="ml-2 text-sm text-gray-400">Memuat data...</span>
        </div>

        {{-- Error --}}
        <div id="skpModalError" class="hidden flex-1 items-center justify-center py-12 text-sm text-red-500">
            Gagal memuat data. Silakan coba lagi.
        </div>

        {{-- Konten: Tab Disetujui --}}
        <div id="skpPanelDisetujui" class="hidden flex-1 overflow-y-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 sticky top-0">
                    <tr class="text-xs text-gray-500 uppercase">
                        <th class="px-4 py-2 text-center w-8">#</th>
                        <th class="px-4 py-2 text-left">Nama</th>
                        <th class="px-4 py-2 text-left">NIP</th>
                        <th class="px-4 py-2 text-left">Jabatan</th>
                    </tr>
                </thead>
                <tbody id="skpBodyDisetujui" class="divide-y divide-gray-50 text-gray-700"></tbody>
            </table>
            <div id="skpEmptyDisetujui" class="hidden px-4 py-10 text-center text-sm text-gray-400">Tidak ada data.</div>
        </div>

        {{-- Konten: Tab Belum Disetujui --}}
        <div id="skpPanelBelumDisetujui" class="hidden flex-1 overflow-y-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 sticky top-0">
                    <tr class="text-xs text-gray-500 uppercase">
                        <th class="px-4 py-2 text-center w-8">#</th>
                        <th class="px-4 py-2 text-left">Nama</th>
                        <th class="px-4 py-2 text-left">NIP</th>
                        <th class="px-4 py-2 text-left">Jabatan</th>
                        <th class="px-4 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="skpBodyBelumDisetujui" class="divide-y divide-gray-50 text-gray-700"></tbody>
            </table>
            <div id="skpEmptyBelumDisetujui" class="hidden px-4 py-10 text-center text-sm text-gray-400">Semua SKP sudah disetujui.</div>
        </div>

        {{-- Konten: Mode belum_buat (single, tanpa tab) --}}
        <div id="skpPanelBelumBuat" class="hidden flex-1 overflow-y-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 sticky top-0">
                    <tr class="text-xs text-gray-500 uppercase">
                        <th class="px-4 py-2 text-center w-8">#</th>
                        <th class="px-4 py-2 text-left">Nama</th>
                        <th class="px-4 py-2 text-left">NIP</th>
                        <th class="px-4 py-2 text-left">Jabatan</th>
                    </tr>
                </thead>
                <tbody id="skpBodyBelumBuat" class="divide-y divide-gray-50 text-gray-700"></tbody>
            </table>
            <div id="skpEmptyBelumBuat" class="hidden px-4 py-10 text-center text-sm text-gray-400">Semua ASN sudah buat SKP.</div>
        </div>

    </div>
</div>

{{-- ===================================================================== --}}
{{-- SECTION F: KEPATUHAN HARIAN (ASN Belum Mengisi) --}}
{{-- ===================================================================== --}}
<section>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Header + tombol refresh --}}
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-gray-700">Kepatuhan Harian — ASN Belum Mengisi</h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    Berdasarkan hari kerja aktual per ASN (SENIN_SABTU diperhitungkan)
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span id="bhSpinner" class="hidden">
                    <svg class="w-4 h-4 text-green-500 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </span>
                <button onclick="muatBelumHarian()"
                        class="text-xs bg-green-50 hover:bg-green-100 border border-green-200 text-green-700 rounded-lg px-3 py-1.5 flex items-center gap-1.5 transition font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        {{-- Summary strip --}}
        <div id="bhSummary" class="hidden px-5 py-3 bg-gray-50 border-b border-gray-100 flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                <span class="text-xs text-gray-600">Total ASN hari ini: <strong id="bhTotalAsn" class="text-gray-800">-</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                <span class="text-xs text-gray-600">Sudah isi: <strong id="bhTotalSudah" class="text-green-700">-</strong></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                <span class="text-xs text-gray-600">Belum isi: <strong id="bhTotalBelum" class="text-red-600">-</strong></span>
            </div>
            <span id="bhTanggal" class="text-xs text-gray-400 ml-auto"></span>
        </div>

        {{-- Loading state --}}
        <div id="bhLoading" class="flex items-center justify-center py-16">
            <div class="text-center">
                <div class="w-7 h-7 border-2 border-green-500 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-sm text-gray-400">Memuat data kepatuhan harian...</p>
            </div>
        </div>

        {{-- Error state --}}
        <div id="bhError" class="hidden flex items-center justify-center py-16">
            <p class="text-sm text-red-400">Gagal memuat data. <button onclick="muatBelumHarian()" class="underline text-red-500">Coba lagi</button></p>
        </div>

        {{-- Search box --}}
        <div id="bhSearchWrap" class="hidden px-5 py-3 border-b border-gray-100 flex items-center gap-3">
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                </svg>
                <input type="text" id="searchBelumUnit"
                       placeholder="Cari unit kerja..."
                       class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-green-400 focus:ring-1 focus:ring-green-200"
                       oninput="filterBelumHarian(this.value)">
            </div>
            <p id="infoSearchBh" class="text-xs text-gray-400"></p>
        </div>

        {{-- Table --}}
        <div id="bhTable" class="hidden table-container">
            <table class="table-monitoring text-sm">
                <thead class="text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-center w-10">#</th>
                        <th class="px-4 py-3 text-left">Unit Kerja</th>
                        <th class="px-4 py-3 text-center">Total ASN</th>
                        <th class="px-4 py-3 text-center">Sudah Isi</th>
                        <th class="px-4 py-3 text-center">Belum Isi</th>
                        <th class="px-4 py-3 text-center">Kepatuhan</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="bhTableBody" class="divide-y divide-gray-100">
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- Footer --}}
<footer class="text-center text-xs text-gray-400 pb-6">
    eSARAKu — Kanwil Kemenag Sulawesi Barat &nbsp;|&nbsp;
    Data tahun {{ $tahun }} &nbsp;|&nbsp;
    Halaman ini read-only &nbsp;|&nbsp;
    Auto-refresh setiap 5 menit
</footer>

{{-- ===================================================================== --}}
{{-- MODAL: ASN AKTIF HARI INI — DETAIL PER UNIT --}}
{{-- ===================================================================== --}}
<div id="modalAsnAktif"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background:rgba(0,0,0,0.5)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col">

        {{-- Modal Header --}}
        <div class="px-6 py-4 border-b border-gray-100 flex-shrink-0">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-gray-800 leading-tight">ASN Aktif — Detail per Unit Kerja</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Tanggal terpilih: <span id="modalTanggal" class="font-medium text-gray-600"></span>
                    </p>
                </div>
                <button onclick="tutupModalAsnAktif()"
                        class="w-8 h-8 rounded-full hover:bg-gray-100 flex-shrink-0 flex items-center justify-center text-gray-400 hover:text-gray-600 transition mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Filter Tanggal + Tombol Export --}}
            <div class="flex flex-wrap items-center gap-2 mt-3">
                {{-- Tombol cepat --}}
                <button id="btnHariIni" onclick="gantiTanggalCepat('today')"
                        class="text-xs px-3 py-1.5 rounded-full border font-medium transition">
                    Hari Ini
                </button>
                <button id="btnKemarin" onclick="gantiTanggalCepat('yesterday')"
                        class="text-xs px-3 py-1.5 rounded-full border font-medium transition">
                    Kemarin
                </button>

                <div class="flex items-center gap-2 ml-auto">
                    {{-- Spinner --}}
                    <span id="filterSpinner" class="hidden">
                        <svg class="w-3.5 h-3.5 text-green-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </span>

                    {{-- Date picker --}}
                    <div class="relative flex items-center">
                        <div class="flex items-center gap-1.5 bg-white border border-gray-300 rounded-lg px-3 py-1.5 cursor-pointer hover:border-green-400 transition"
                             onclick="document.getElementById('inputTanggal').showPicker()">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span id="labelTanggalPicker" class="text-xs font-medium text-gray-700"></span>
                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        <input type="date" id="inputTanggal"
                               class="absolute opacity-0 w-0 h-0 pointer-events-none"
                               onchange="gantiTanggalPicker(this.value)">
                    </div>

                    {{-- Tombol Download Excel --}}
                    <button id="btnDownloadExcel" onclick="downloadExcel()"
                            title="Download Excel"
                            class="flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span class="hidden sm:inline">Excel</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Loading --}}
        <div id="modalLoading" class="flex-1 flex items-center justify-center py-12">
            <div class="text-center">
                <div class="w-8 h-8 border-2 border-green-500 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-sm text-gray-400">Memuat data unit...</p>
            </div>
        </div>

        {{-- Content --}}
        <div id="modalContent" class="hidden flex-1 flex flex-col overflow-hidden">

            {{-- Summary strip --}}
            <div id="modalSummary" class="px-6 py-3 bg-gray-50 border-b border-gray-100 flex-shrink-0">
            </div>

            {{-- Table --}}
            <div class="overflow-y-auto flex-1">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Unit Kerja</th>
                            <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Aktif</th>
                            <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">%</th>
                            <th class="text-center px-3 py-3 text-xs font-semibold text-indigo-500 uppercase tracking-wide">KH</th>
                            <th class="text-center px-3 py-3 text-xs font-semibold text-purple-500 uppercase tracking-wide">TLA</th>
                        </tr>
                    </thead>
                    <tbody id="modalTableBody" class="divide-y divide-gray-50">
                    </tbody>
                </table>
            </div>

            {{-- Footer note --}}
            <div class="px-6 py-3 border-t border-gray-100 flex-shrink-0">
                <p class="text-[10px] text-gray-400">
                    KH = Kinerja Harian &nbsp;|&nbsp; TLA = Tugas Langsung Atasan &nbsp;|&nbsp;
                    Aktif = ASN unik yang input KH atau TLA hari ini (tidak double-count)
                </p>
            </div>
        </div>

        {{-- Error state --}}
        <div id="modalError" class="hidden flex-1 flex items-center justify-center py-12">
            <p class="text-sm text-red-400">Gagal memuat data. Coba lagi.</p>
        </div>

    </div>
</div>

{{-- ===================================================================== --}}
{{-- MODAL: ASN BELUM ISI — DAFTAR PER UNIT --}}
{{-- ===================================================================== --}}
<div id="modalBelumIsi"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background:rgba(0,0,0,0.5)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-100 flex-shrink-0 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="text-base font-bold text-gray-800 leading-tight" id="bhModalUnitNama">-</h3>
                <p class="text-xs text-gray-400 mt-0.5">Daftar ASN yang belum mengisi hari ini</p>
            </div>
            <button onclick="tutupModalBelumIsi()"
                    class="w-8 h-8 rounded-full hover:bg-gray-100 flex-shrink-0 flex items-center justify-center text-gray-400 hover:text-gray-600 transition mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Summary strip --}}
        <div class="px-6 py-2.5 bg-red-50 border-b border-red-100 flex-shrink-0">
            <p class="text-xs text-red-700" id="bhModalSummary"></p>
        </div>

        {{-- List --}}
        <div class="overflow-y-auto flex-1 px-6 py-4">
            <div id="bhModalList"></div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-3 border-t border-gray-100 flex-shrink-0">
            <p class="text-[10px] text-gray-400">
                Hanya ASN yang hari ini merupakan hari kerja mereka (SENIN_SABTU diperhitungkan)
            </p>
        </div>

    </div>
</div>

</main>

{{-- ===================================================================== --}}
{{-- CHART.JS SCRIPTS --}}
{{-- ===================================================================== --}}
<script>
// -----------------------------------------------------------------------
// Data dari blade → JS (hanya agregat, tanpa nama/NIP individu)
// -----------------------------------------------------------------------
const unitLabels   = @json(array_column($data['per_unit'], 'nama_unit'));
const unitPersen   = @json(array_column($data['per_unit'], 'persen'));
const unitWarna    = @json(array_column($data['per_unit'], 'warna'));

const statusData   = @json(array_values($data['status_distribution']));
const statusLabels = ['DRAFT', 'DIAJUKAN', 'DISETUJUI', 'DITOLAK'];

// -----------------------------------------------------------------------
// Helper warna bar
// -----------------------------------------------------------------------
function getBarColor(warna) {
    if (warna === 'hijau')  return 'rgba(34, 197, 94, 0.8)';
    if (warna === 'kuning') return 'rgba(234, 179, 8, 0.8)';
    return 'rgba(239, 68, 68, 0.8)';
}

// -----------------------------------------------------------------------
// Bar Chart per Unit
// -----------------------------------------------------------------------
const ctxUnit = document.getElementById('chartPerUnit').getContext('2d');
new Chart(ctxUnit, {
    type: 'bar',
    data: {
        labels: unitLabels,
        datasets: [{
            label: 'Kepatuhan (%)',
            data: unitPersen,
            backgroundColor: unitWarna.map(getBarColor),
            borderColor: unitWarna.map(w =>
                w === 'hijau' ? 'rgba(22,163,74,1)' :
                w === 'kuning' ? 'rgba(202,138,4,1)' :
                'rgba(220,38,38,1)'
            ),
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.parsed.y}% kepatuhan`
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: v => v + '%',
                    font: { size: 11 }
                },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                ticks: {
                    font: { size: 10 },
                    maxRotation: 45,
                    minRotation: 30
                },
                grid: { display: false }
            }
        }
    }
});

// -----------------------------------------------------------------------
// Pie Chart Status Distribution
// -----------------------------------------------------------------------
const ctxStatus = document.getElementById('chartStatus').getContext('2d');
new Chart(ctxStatus, {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusData,
            backgroundColor: [
                'rgba(96, 165, 250, 0.85)',   // DRAFT - biru
                'rgba(251, 191, 36, 0.85)',   // DIAJUKAN - kuning
                'rgba(34, 197, 94, 0.85)',    // DISETUJUI - hijau
                'rgba(239, 68, 68, 0.85)',    // DITOLAK - merah
            ],
            borderColor: ['#fff','#fff','#fff','#fff'],
            borderWidth: 2,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: { size: 11 },
                    padding: 12,
                    usePointStyle: true,
                }
            },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.parsed} SKP`
                }
            }
        },
        cutout: '60%',
    }
});

// -----------------------------------------------------------------------
// MODAL: ASN Aktif — Detail per Unit Kerja (dengan filter tanggal)
// -----------------------------------------------------------------------
const DETAIL_BASE_URL = '{{ route('monitoring.kakanwil.asn-aktif-detail') }}?key={{ $monitorKey }}';
const EXPORT_BASE_URL = '{{ route('monitoring.kakanwil.asn-aktif-export') }}?key={{ $monitorKey }}';

// Tanggal terpilih saat ini (format Y-m-d), default hari ini
let selectedTanggal = '{{ now()->setTimezone('Asia/Makassar')->format('Y-m-d') }}';
const todayStr      = '{{ now()->setTimezone('Asia/Makassar')->format('Y-m-d') }}';

function bukaModalAsnAktif() {
    const modal = document.getElementById('modalAsnAktif');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    // Set input max = hari ini
    document.getElementById('inputTanggal').max = todayStr;
    // Reset ke hari ini setiap buka modal
    selectedTanggal = todayStr;
    document.getElementById('inputTanggal').value = todayStr;
    updateButtonStyles();
    muatData();
}

function tutupModalAsnAktif() {
    const modal = document.getElementById('modalAsnAktif');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function gantiTanggalCepat(mode) {
    if (mode === 'today') {
        selectedTanggal = todayStr;
    } else {
        // Kemarin
        const d = new Date(todayStr);
        d.setDate(d.getDate() - 1);
        selectedTanggal = d.toISOString().slice(0, 10);
    }
    document.getElementById('inputTanggal').value = selectedTanggal;
    updateButtonStyles();
    muatData();
}

function gantiTanggalPicker(val) {
    if (!val) return;
    // Jangan izinkan tanggal masa depan
    if (val > todayStr) val = todayStr;
    selectedTanggal = val;
    updateButtonStyles();
    muatData();
}

function updateButtonStyles() {
    const kemarin = (() => {
        const d = new Date(todayStr);
        d.setDate(d.getDate() - 1);
        return d.toISOString().slice(0, 10);
    })();

    const btnHariIni = document.getElementById('btnHariIni');
    const btnKemarin = document.getElementById('btnKemarin');

    // Active style
    const activeClass  = ['bg-green-600', 'text-white', 'border-green-600'];
    const passiveClass = ['bg-white', 'text-gray-600', 'border-gray-300', 'hover:border-green-400'];

    [btnHariIni, btnKemarin].forEach(b => {
        activeClass.forEach(c  => b.classList.remove(c));
        passiveClass.forEach(c => b.classList.remove(c));
    });

    if (selectedTanggal === todayStr) {
        activeClass.forEach(c  => btnHariIni.classList.add(c));
        passiveClass.forEach(c => btnKemarin.classList.add(c));
    } else if (selectedTanggal === kemarin) {
        activeClass.forEach(c  => btnKemarin.classList.add(c));
        passiveClass.forEach(c => btnHariIni.classList.add(c));
    } else {
        passiveClass.forEach(c => btnHariIni.classList.add(c));
        passiveClass.forEach(c => btnKemarin.classList.add(c));
    }

    // Update label date picker
    const d = new Date(selectedTanggal + 'T00:00:00');
    document.getElementById('labelTanggalPicker').textContent =
        d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

function muatData() {
    // Tampilkan spinner kecil di filter, sembunyikan content lama
    document.getElementById('filterSpinner').classList.remove('hidden');
    document.getElementById('modalError').classList.add('hidden');

    // Pertama buka: tampilkan loading besar
    const content = document.getElementById('modalContent');
    const loading  = document.getElementById('modalLoading');
    if (content.classList.contains('hidden')) {
        loading.classList.remove('hidden');
    }

    fetch(DETAIL_BASE_URL + '&tanggal=' + selectedTanggal)
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(json => {
            renderModalDetail(json);
            loading.classList.add('hidden');
            content.classList.remove('hidden');
            document.getElementById('filterSpinner').classList.add('hidden');
        })
        .catch(() => {
            loading.classList.add('hidden');
            document.getElementById('filterSpinner').classList.add('hidden');
            document.getElementById('modalError').classList.remove('hidden');
        });
}

// Tutup modal saat klik backdrop
document.getElementById('modalAsnAktif').addEventListener('click', function(e) {
    if (e.target === this) tutupModalAsnAktif();
});

function downloadExcel() {
    const url = EXPORT_BASE_URL + '&tanggal=' + selectedTanggal;
    // Trigger download langsung via anchor — tidak perlu fetch/blob
    const a = document.createElement('a');
    a.href = url;
    a.download = 'ASN_Aktif_Harian_' + selectedTanggal + '.xlsx';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function renderModalDetail(json) {
    // Label tanggal di header
    document.getElementById('modalTanggal').textContent = json.tanggal
        + (json.is_hari_ini ? ' (hari ini)' : '');

    // Summary strip
    const totalAktifUnit = json.data.reduce((s, r) => s + r.aktif, 0);
    const keteranganWaktu = json.is_hari_ini ? 'hari ini' : 'pada hari tersebut';
    document.getElementById('modalSummary').innerHTML =
        `<span class="text-xs text-gray-600">
            <span class="font-semibold text-green-600">${json.total_unit} unit</span>
            melakukan pengisian eSARAku ${keteranganWaktu}
            &nbsp;·&nbsp;
            Total <span class="font-semibold text-green-600">${totalAktifUnit} ASN</span> aktif
         </span>`;

    // Table rows
    const tbody = document.getElementById('modalTableBody');
    tbody.innerHTML = '';

    if (json.data.length === 0) {
        const label = json.is_hari_ini
            ? 'Belum ada ASN yang melakukan pengisian hari ini.'
            : 'Tidak ada aktivitas pada tanggal tersebut.';
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-10 text-sm text-gray-400">${label}</td>
            </tr>`;
        return;
    }

    json.data.forEach((row, idx) => {
        const persen = row.persen;
        const barColor    = persen >= 80 ? '#22c55e' : persen >= 50 ? '#f59e0b' : '#ef4444';
        const persenBadge = persen >= 80
            ? 'bg-green-100 text-green-700'
            : persen >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600';

        tbody.innerHTML += `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 text-xs text-gray-400">${idx + 1}</td>
                <td class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-800 leading-tight">${row.nama_unit}</p>
                    <div class="mt-1.5 h-1.5 bg-gray-100 rounded-full overflow-hidden w-32">
                        <div style="width:${Math.min(persen,100)}%;background:${barColor}"
                             class="h-full rounded-full transition-all duration-500"></div>
                    </div>
                </td>
                <td class="px-3 py-3 text-center">
                    <span class="text-sm font-semibold text-gray-800">${row.aktif}</span>
                    <span class="text-xs text-gray-400"> / ${row.total}</span>
                </td>
                <td class="px-3 py-3 text-center">
                    <span class="inline-block px-1.5 py-0.5 rounded text-xs font-semibold ${persenBadge}">${persen}%</span>
                </td>
                <td class="px-3 py-3 text-center text-sm font-medium text-indigo-600">${row.kh}</td>
                <td class="px-3 py-3 text-center text-sm font-medium text-purple-600">${row.tla}</td>
            </tr>`;
    });
}

// -----------------------------------------------------------------------
// SECTION F: Kepatuhan Harian — ASN Belum Mengisi
// -----------------------------------------------------------------------
const BH_URL = '{{ route('monitoring.kakanwil.asn-belum-isi') }}?key={{ $monitorKey }}';
let bhData         = null; // cache response JSON terakhir untuk modal
let bhDataOriginal = [];   // cache array data untuk filter search

// ── Selalu realtime: tanggal dirender server setiap request/auto-refresh ──
// sessionStorage persistence dinonaktifkan — akan diaktifkan kembali saat
// fitur date picker histori (Step 2) diimplementasikan.
const BH_TODAY = '{{ now()->setTimezone('Asia/Makassar')->toDateString() }}';

function muatBelumHarian() {
    const url = BH_URL;

    document.getElementById('bhSpinner').classList.remove('hidden');
    document.getElementById('bhError').classList.add('hidden');
    document.getElementById('bhTable').classList.add('hidden');
    document.getElementById('bhSearchWrap').classList.add('hidden');
    document.getElementById('bhSummary').classList.add('hidden');
    document.getElementById('bhLoading').classList.remove('hidden');
    // Reset search
    const searchEl = document.getElementById('searchBelumUnit');
    if (searchEl) searchEl.value = '';

    fetch(url)
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(json => {
            bhData         = json;
            bhDataOriginal = json.data;
            renderBelumHarian(json);
            document.getElementById('bhSpinner').classList.add('hidden');
        })
        .catch(() => {
            document.getElementById('bhSpinner').classList.add('hidden');
            document.getElementById('bhLoading').classList.add('hidden');
            document.getElementById('bhError').classList.remove('hidden');
        });
}

function renderBelumHarian(json) {
    // Summary strip
    document.getElementById('bhTotalAsn').textContent   = json.total_asn;
    document.getElementById('bhTotalSudah').textContent = json.total_sudah;
    document.getElementById('bhTotalBelum').textContent = json.total_belum;
    document.getElementById('bhTanggal').textContent    = json.tanggal;

    // Tampilkan search + tabel
    document.getElementById('bhLoading').classList.add('hidden');
    document.getElementById('bhSummary').classList.remove('hidden');
    document.getElementById('bhSearchWrap').classList.remove('hidden');
    document.getElementById('bhTable').classList.remove('hidden');

    renderFilteredRows(bhDataOriginal);
}

function renderFilteredRows(data) {
    const tbody = document.getElementById('bhTableBody');
    const info  = document.getElementById('infoSearchBh');

    info.textContent = data.length < bhDataOriginal.length
        ? `Menampilkan ${data.length} dari ${bhDataOriginal.length} unit`
        : `${bhDataOriginal.length} unit kerja`;

    if (data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-10 text-sm text-gray-400">
                    Unit kerja tidak ditemukan.
                </td>
            </tr>`;
        return;
    }

    tbody.innerHTML = '';
    data.forEach((row, idx) => {
        // idx di sini adalah posisi di array filtered — untuk modal kita butuh index di bhDataOriginal
        const originalIdx = bhDataOriginal.indexOf(row);

        const persen      = row.persen_sudah;
        const barColor    = persen >= 80 ? '#22c55e' : persen >= 50 ? '#f59e0b' : '#ef4444';
        const persenBadge = persen >= 80
            ? 'bg-green-100 text-green-700'
            : persen >= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-600';

        const statusCell = row.semua_isi
            ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                   ✔ Semua sudah mengisi
               </span>`
            : `<button onclick="bukaModalBelumIsi(${originalIdx})"
                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-600 hover:bg-red-200 transition cursor-pointer">
                   ${row.belum} ASN Belum Mengisi ▶
               </button>`;

        tbody.innerHTML += `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 text-center text-xs text-gray-400 font-mono">${idx + 1}</td>
                <td class="px-4 py-3 font-medium text-gray-800">${row.nama_unit}</td>
                <td class="px-4 py-3 text-center text-gray-600">${row.total}</td>
                <td class="px-4 py-3 text-center text-green-600 font-medium">${row.sudah}</td>
                <td class="px-4 py-3 text-center ${row.belum > 0 ? 'text-red-500 font-medium' : 'text-gray-400'}">${row.belum}</td>
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <div class="w-16 bg-gray-100 rounded-full h-1.5">
                            <div style="width:${Math.min(100,persen)}%;background:${barColor}"
                                 class="h-1.5 rounded-full"></div>
                        </div>
                        <span class="inline-block px-1.5 py-0.5 rounded text-xs font-semibold ${persenBadge}">${persen}%</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-center">${statusCell}</td>
            </tr>`;
    });
}

function filterBelumHarian(keyword) {
    const q = keyword.trim().toLowerCase();
    const filtered = q
        ? bhDataOriginal.filter(row => row.nama_unit.toLowerCase().includes(q))
        : bhDataOriginal;
    renderFilteredRows(filtered);
}

function bukaModalBelumIsi(idx) {
    if (!bhData || !bhData.data[idx]) return;
    const row = bhData.data[idx];

    document.getElementById('bhModalUnitNama').textContent = row.nama_unit;
    document.getElementById('bhModalSummary').textContent  =
        `${row.belum} dari ${row.total} ASN belum mengisi — ${bhData.tanggal}`;

    const list = document.getElementById('bhModalList');
    if (row.daftar_belum.length === 0) {
        list.innerHTML = `<p class="text-sm text-gray-400 text-center py-6">Semua ASN sudah mengisi.</p>`;
    } else {
        list.innerHTML = row.daftar_belum.map((asn, i) => `
            <div class="flex items-start gap-3 py-3 ${i > 0 ? 'border-t border-gray-100' : ''}">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-red-100 text-red-500 text-xs font-bold flex items-center justify-center mt-0.5">${i + 1}</span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 leading-tight">${asn.nama}</p>
                    <p class="text-xs text-gray-400 mt-0.5">NIP: ${asn.nip}</p>
                    <p class="text-xs text-gray-400">${asn.jabatan}</p>
                </div>
            </div>
        `).join('');
    }

    const modal = document.getElementById('modalBelumIsi');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function tutupModalBelumIsi() {
    const modal = document.getElementById('modalBelumIsi');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Tutup modal belum isi saat klik backdrop atau Escape
document.getElementById('modalBelumIsi').addEventListener('click', function(e) {
    if (e.target === this) tutupModalBelumIsi();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        tutupModalBelumIsi();
        tutupModalAsnAktif();
    }
});

// Auto-load saat halaman dibuka
document.addEventListener('DOMContentLoaded', function() {
    muatBelumHarian();
});

// -----------------------------------------------------------------------
// MODAL SKP DETAIL — Drill-down Belum Buat / Disetujui (+ tab Belum Disetujui)
// -----------------------------------------------------------------------
const SKP_DETAIL_URL = '{{ route('monitoring.kakanwil.skp-detail') }}?key={{ $monitorKey }}';

// Cache data hasil fetch agar ganti tab tidak perlu fetch ulang
let _skpDataDisetujui    = null;
let _skpDataBelumDisetujui = null;

function bukaModalSkp(unitId, tipe, tahun, namaUnit) {
    const modal   = document.getElementById('modalSkpDetail');
    const loading = document.getElementById('skpModalLoading');
    const error   = document.getElementById('skpModalError');
    const label   = document.getElementById('skpModalLabel');
    const title   = document.getElementById('skpModalTitle');
    const tabs    = document.getElementById('skpModalTabs');

    // Reset semua panel & state cache
    _skpDataDisetujui      = null;
    _skpDataBelumDisetujui = null;
    ['skpPanelDisetujui','skpPanelBelumDisetujui','skpPanelBelumBuat'].forEach(function(id) {
        document.getElementById(id).classList.add('hidden');
    });
    ['skpBodyDisetujui','skpBodyBelumDisetujui','skpBodyBelumBuat'].forEach(function(id) {
        document.getElementById(id).innerHTML = '';
    });
    ['skpEmptyDisetujui','skpEmptyBelumDisetujui','skpEmptyBelumBuat'].forEach(function(id) {
        document.getElementById(id).classList.add('hidden');
    });
    error.classList.add('hidden');
    error.classList.remove('flex');
    tabs.classList.add('hidden');

    title.textContent = namaUnit;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    loading.classList.remove('hidden');

    if (tipe === 'belum_buat') {
        label.textContent = 'Belum Buat SKP — ' + tahun;

        fetch(SKP_DETAIL_URL + '&unit_id=' + unitId + '&tipe=belum_buat&tahun=' + tahun)
            .then(function(r) { if (!r.ok) throw r; return r.json(); })
            .then(function(json) {
                loading.classList.add('hidden');
                _renderListSkp('skpBodyBelumBuat', 'skpEmptyBelumBuat', json.data, false);
                document.getElementById('skpPanelBelumBuat').classList.remove('hidden');
            })
            .catch(function() { _skpShowError(); });

    } else {
        // tipe === 'disetujui' — fetch 2 paralel
        label.textContent = 'SKP ' + tahun;

        Promise.all([
            fetch(SKP_DETAIL_URL + '&unit_id=' + unitId + '&tipe=disetujui&tahun=' + tahun).then(function(r) { if (!r.ok) throw r; return r.json(); }),
            fetch(SKP_DETAIL_URL + '&unit_id=' + unitId + '&tipe=belum_disetujui&tahun=' + tahun).then(function(r) { if (!r.ok) throw r; return r.json(); })
        ])
        .then(function(results) {
            _skpDataDisetujui      = results[0];
            _skpDataBelumDisetujui = results[1];

            loading.classList.add('hidden');
            tabs.classList.remove('hidden');

            // Update badge hitungan di tab
            document.getElementById('skpTabCountDisetujui').textContent = '(' + _skpDataDisetujui.total + ')';
            document.getElementById('skpTabCountBelum').textContent     = _skpDataBelumDisetujui.total > 0
                ? '(' + _skpDataBelumDisetujui.total + ')'
                : '';

            // Aktifkan tab Disetujui dulu
            gantiTabSkp('disetujui');
        })
        .catch(function() { _skpShowError(); });
    }
}

function gantiTabSkp(aktif) {
    // Sembunyikan semua panel tab
    document.getElementById('skpPanelDisetujui').classList.add('hidden');
    document.getElementById('skpPanelBelumDisetujui').classList.add('hidden');

    // Style tab aktif vs non-aktif
    var tabDisetujui = document.getElementById('skpTabDisetujui');
    var tabBelum     = document.getElementById('skpTabBelumDisetujui');

    if (aktif === 'disetujui') {
        tabDisetujui.className = 'pb-2 text-sm font-semibold border-b-2 border-green-600 text-green-700 transition-colors';
        tabBelum.className     = 'pb-2 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-colors';
        _renderListSkp('skpBodyDisetujui', 'skpEmptyDisetujui', _skpDataDisetujui ? _skpDataDisetujui.data : [], false);
        document.getElementById('skpPanelDisetujui').classList.remove('hidden');
    } else {
        tabBelum.className     = 'pb-2 text-sm font-semibold border-b-2 border-orange-500 text-orange-600 transition-colors';
        tabDisetujui.className = 'pb-2 text-sm font-medium border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-colors';
        _renderListSkp('skpBodyBelumDisetujui', 'skpEmptyBelumDisetujui', _skpDataBelumDisetujui ? _skpDataBelumDisetujui.data : [], true);
        document.getElementById('skpPanelBelumDisetujui').classList.remove('hidden');
    }
}

function _renderListSkp(tbodyId, emptyId, data, withStatus) {
    var tbody = document.getElementById(tbodyId);
    var empty = document.getElementById(emptyId);
    tbody.innerHTML = '';
    empty.classList.add('hidden');

    if (!data || data.length === 0) {
        empty.classList.remove('hidden');
        return;
    }

    data.forEach(function(asn, idx) {
        var statusCell = '';
        if (withStatus) {
            var badge = _statusBadge(asn.status);
            statusCell = '<td class="px-4 py-2 text-center"><span class="px-2 py-0.5 rounded text-xs font-medium ' + badge.cls + '">' + badge.label + '</span></td>';
        }
        var tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50';
        tr.innerHTML =
            '<td class="px-4 py-2 text-center text-xs text-gray-400">' + (idx + 1) + '</td>' +
            '<td class="px-4 py-2 font-medium text-gray-800">' + escHtml(asn.nama) + '</td>' +
            '<td class="px-4 py-2 text-gray-500 text-xs font-mono">' + escHtml(asn.nip) + '</td>' +
            '<td class="px-4 py-2 text-gray-500 text-xs">' + escHtml(asn.jabatan) + '</td>' +
            statusCell;
        tbody.appendChild(tr);
    });
}

function _statusBadge(status) {
    switch (status) {
        case 'DRAFT':            return { cls: 'bg-gray-100 text-gray-600',    label: 'Draft' };
        case 'DIAJUKAN':         return { cls: 'bg-yellow-100 text-yellow-700', label: 'Diajukan' };
        case 'DITOLAK':          return { cls: 'bg-red-100 text-red-600',       label: 'Ditolak' };
        case 'REVISI_DIAJUKAN':  return { cls: 'bg-orange-100 text-orange-700', label: 'Revisi' };
        default:                 return { cls: 'bg-gray-100 text-gray-500',    label: status };
    }
}

function _skpShowError() {
    document.getElementById('skpModalLoading').classList.add('hidden');
    var err = document.getElementById('skpModalError');
    err.classList.remove('hidden');
    err.classList.add('flex');
}

function tutupModalSkp() {
    const modal = document.getElementById('modalSkpDetail');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Tutup saat klik backdrop
document.getElementById('modalSkpDetail').addEventListener('click', function(e) {
    if (e.target === this) tutupModalSkp();
});

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
</script>

</body>
</html>
