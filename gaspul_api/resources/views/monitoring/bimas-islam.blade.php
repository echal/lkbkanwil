<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Bimas Islam eSARAku — Tahun {{ $tahun }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { font-size: 16px; }
        .tv-card { padding: 1.5rem; }
        .kpi-value { font-size: 2.75rem; font-weight: 800; line-height: 1; }
        .kpi-value-sm { font-size: 2rem; font-weight: 800; line-height: 1; }
        .kpi-label { font-size: 0.78rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }
        .progress-bar { height: 12px; border-radius: 9999px; }
        .table-container {
            max-height: 52vh;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .table-container::-webkit-scrollbar { width: 6px; }
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
<header class="bg-gradient-to-r from-teal-700 to-teal-950 text-white shadow-xl">
    <div class="max-w-screen-2xl mx-auto px-6 py-5">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                @if(file_exists(public_path('images/logo/esaraku-logo.png')))
                <img src="{{ asset('images/logo/esaraku-logo.png') }}" alt="Logo" class="h-14 w-14 object-contain">
                @else
                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                @endif
                <div>
                    <h1 class="text-2xl font-black tracking-wide leading-tight">MONITORING BIMBINGAN MASYARAKAT ISLAM</h1>
                    <p class="text-teal-200 font-semibold text-base mt-0.5">KANWIL KEMENTERIAN AGAMA SULAWESI BARAT &nbsp;·&nbsp; TAHUN {{ $tahun }}</p>
                </div>
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-teal-200 text-sm font-medium">Last Update</p>
                <p class="text-white font-bold text-lg">{{ $lastUpdate }}</p>
                <p class="text-teal-300 text-xs mt-1">Auto refresh setiap 5 menit</p>
            </div>
        </div>
    </div>
</header>

@if(session('info'))
<div class="bg-blue-50 border-b border-blue-200 px-6 py-2 text-blue-700 text-sm text-center">
    {{ session('info') }}
</div>
@endif

@php
    $kpi = $data['kpi'];
    $kanwil = $data['kanwil'];
@endphp

<main class="max-w-screen-2xl mx-auto px-6 py-5 space-y-5">

{{-- ===================================================================== --}}
{{-- BARIS 1: KPI KEPATUHAN HARIAN --}}
{{-- ===================================================================== --}}
<div>
    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Kepatuhan Harian — {{ now()->translatedFormat('l, d F Y') }}</p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <div class="bg-white rounded-2xl shadow border border-gray-200 tv-card text-center">
            <p class="kpi-label text-gray-500 mb-2">Total ASN Bimas Islam</p>
            <p class="kpi-value text-gray-800">{{ number_format($kpi['total_asn']) }}</p>
            <p class="text-sm text-gray-400 mt-2">ASN Aktif (63 KUA + 6 Seksi + Kanwil)</p>
        </div>

        <div class="bg-white rounded-2xl shadow border border-blue-200 tv-card text-center">
            <p class="kpi-label text-blue-500 mb-2">Mengisi Hari Ini</p>
            <p class="kpi-value text-blue-600">{{ number_format($kpi['sudah_isi_hari_ini']) }}</p>
            <p class="text-sm text-blue-400 mt-2">ASN sudah input KH</p>
        </div>

        <div class="bg-white rounded-2xl shadow border border-red-200 tv-card text-center">
            <p class="kpi-label text-red-500 mb-2">Belum Mengisi</p>
            <p class="kpi-value text-red-500">{{ number_format($kpi['belum_isi_hari_ini']) }}</p>
            <p class="text-sm text-red-400 mt-2">Perlu segera diisi</p>
        </div>

        @php
            $wH = match($kpi['warna_harian']) {
                'hijau'  => ['bg' => 'bg-green-50 border-green-300',  'text' => 'text-green-700',  'badge' => 'bg-green-100 text-green-700'],
                'kuning' => ['bg' => 'bg-yellow-50 border-yellow-300','text' => 'text-yellow-700', 'badge' => 'bg-yellow-100 text-yellow-700'],
                default  => ['bg' => 'bg-red-50 border-red-300',      'text' => 'text-red-700',    'badge' => 'bg-red-100 text-red-700'],
            };
        @endphp
        <div class="rounded-2xl shadow border tv-card text-center {{ $wH['bg'] }}">
            <p class="kpi-label {{ $wH['text'] }} mb-2">Kepatuhan Harian</p>
            <p class="kpi-value {{ $wH['text'] }}">{{ $kpi['kepatuhan_harian'] }}%</p>
            <span class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-bold {{ $wH['badge'] }}">
                {{ $kpi['warna_harian'] === 'hijau' ? 'Baik' : ($kpi['warna_harian'] === 'kuning' ? 'Perlu Perhatian' : 'Rendah') }}
            </span>
        </div>
    </div>
</div>

{{-- ===================================================================== --}}
{{-- BARIS 2: KPI SKP --}}
{{-- ===================================================================== --}}
<div>
    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">SKP Tahunan {{ $tahun }}</p>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

        <div class="bg-white rounded-2xl shadow border border-blue-200 tv-card text-center">
            <p class="kpi-label text-blue-500 mb-2">Sudah Buat SKP</p>
            <p class="kpi-value text-blue-600">{{ number_format($kpi['sudah_buat_skp']) }}</p>
            <p class="text-sm text-blue-400 mt-2">
                {{ $kpi['total_asn'] > 0 ? round($kpi['sudah_buat_skp'] / $kpi['total_asn'] * 100, 1) : 0 }}% dari total ASN
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow border border-red-200 tv-card text-center">
            <p class="kpi-label text-red-500 mb-2">Belum Buat SKP</p>
            <p class="kpi-value text-red-500">{{ number_format($kpi['belum_buat_skp']) }}</p>
            <p class="text-sm text-red-400 mt-2">Perlu segera dibuat</p>
        </div>

        <div class="bg-white rounded-2xl shadow border border-green-200 tv-card text-center">
            <p class="kpi-label text-green-500 mb-2">Sudah Disetujui</p>
            <p class="kpi-value text-green-600">{{ number_format($kpi['disetujui_skp']) }}</p>
            <p class="text-sm text-green-400 mt-2">SKP disetujui atasan</p>
        </div>

        @php
            $wS = match($kpi['warna_skp']) {
                'hijau'  => ['bg' => 'bg-green-50 border-green-300',  'text' => 'text-green-700',  'badge' => 'bg-green-100 text-green-700'],
                'kuning' => ['bg' => 'bg-yellow-50 border-yellow-300','text' => 'text-yellow-700', 'badge' => 'bg-yellow-100 text-yellow-700'],
                default  => ['bg' => 'bg-red-50 border-red-300',      'text' => 'text-red-700',    'badge' => 'bg-red-100 text-red-700'],
            };
        @endphp
        <div class="rounded-2xl shadow border tv-card text-center {{ $wS['bg'] }}">
            <p class="kpi-label {{ $wS['text'] }} mb-2">Kepatuhan SKP</p>
            <p class="kpi-value {{ $wS['text'] }}">{{ $kpi['kepatuhan_skp'] }}%</p>
            <span class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-bold {{ $wS['badge'] }}">
                {{ $kpi['warna_skp'] === 'hijau' ? 'Baik' : ($kpi['warna_skp'] === 'kuning' ? 'Perlu Perhatian' : 'Rendah') }}
            </span>
        </div>
    </div>
</div>

{{-- ===================================================================== --}}
{{-- BIDANG BIMAS ISLAM KANWIL — Tampil Terpisah --}}
{{-- ===================================================================== --}}
<div class="bg-teal-50 border border-teal-200 rounded-2xl shadow p-5">
    <h3 class="font-bold text-teal-800 text-base mb-3 flex items-center gap-2">
        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        BIDANG BIMBINGAN MASYARAKAT ISLAM — KANTOR WILAYAH
    </h3>
    <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl p-3 text-center border border-teal-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Total ASN</p>
            <p class="text-2xl font-black text-gray-800">{{ $kanwil['total_asn'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-3 text-center border border-blue-100">
            <p class="text-xs text-blue-500 font-semibold uppercase mb-1">Isi KH Hari Ini</p>
            <p class="text-2xl font-black text-blue-600">{{ $kanwil['sudah_isi'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-3 text-center border border-red-100">
            <p class="text-xs text-red-500 font-semibold uppercase mb-1">Belum Isi</p>
            <p class="text-2xl font-black text-red-500">{{ $kanwil['belum_isi'] }}</p>
        </div>
        @php
            $wKanwilH = match($kanwil['warna_harian']) { 'hijau' => 'text-green-700', 'kuning' => 'text-yellow-600', default => 'text-red-600' };
        @endphp
        <div class="bg-white rounded-xl p-3 text-center border border-gray-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Kepatuhan KH</p>
            <p class="text-2xl font-black {{ $wKanwilH }}">{{ $kanwil['kepatuhan_harian'] }}%</p>
        </div>
        <div class="bg-white rounded-xl p-3 text-center border border-green-100">
            <p class="text-xs text-green-600 font-semibold uppercase mb-1">SKP Disetujui</p>
            <p class="text-2xl font-black text-green-600">{{ $kanwil['disetujui_skp'] }}</p>
        </div>
        @php
            $wKanwilS = match($kanwil['warna_skp']) { 'hijau' => 'text-green-700', 'kuning' => 'text-yellow-600', default => 'text-red-600' };
        @endphp
        <div class="bg-white rounded-xl p-3 text-center border border-gray-100">
            <p class="text-xs text-gray-500 font-semibold uppercase mb-1">Kepatuhan SKP</p>
            <p class="text-2xl font-black {{ $wKanwilS }}">{{ $kanwil['kepatuhan_skp'] }}%</p>
        </div>
    </div>
</div>

{{-- ===================================================================== --}}
{{-- RANKING KABUPATEN + RANKING KUA --}}
{{-- ===================================================================== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Ranking Kabupaten --}}
    <div class="bg-white rounded-2xl shadow border border-gray-200 p-5">
        <h3 class="font-bold text-gray-700 text-base mb-4 flex items-center gap-2">
            <span class="text-teal-500">▲</span> Ranking Kabupaten
            <span class="text-xs font-normal text-gray-400">(kepatuhan harian)</span>
        </h3>
        <div class="space-y-3">
            @foreach($data['ranking_kab'] as $i => $kab)
            @php
                $barH = match($kab['warna_harian']) { 'hijau' => 'bg-green-500', 'kuning' => 'bg-yellow-400', default => 'bg-red-400' };
                $textH = match($kab['warna_harian']) { 'hijau' => 'text-green-600', 'kuning' => 'text-yellow-600', default => 'text-red-500' };
                $medals = ['🥇','🥈','🥉','4️⃣','5️⃣','6️⃣'];
            @endphp
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <span class="text-base">{{ $medals[$i] ?? ($i+1) }}</span>
                        {{ $kab['kabupaten'] }}
                        <span class="text-xs text-gray-400">({{ $kab['total_asn'] }} ASN)</span>
                    </span>
                    <span class="text-sm font-bold {{ $textH }}">{{ $kab['kepatuhan_harian'] }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full progress-bar">
                    <div class="{{ $barH }} progress-bar transition-all" style="width: {{ min(100, $kab['kepatuhan_harian']) }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-400 mt-0.5">
                    <span>SKP: {{ $kab['kepatuhan_skp'] }}%</span>
                    <span>{{ $kab['total_kua'] }} KUA</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Top 10 KUA --}}
    <div class="bg-white rounded-2xl shadow border border-gray-200 p-5">
        <h3 class="font-bold text-gray-700 text-base mb-4 flex items-center gap-2">
            <span class="text-green-500">▲</span> Top 10 KUA Terbaik
            <span class="text-xs font-normal text-gray-400">(kepatuhan SKP)</span>
        </h3>
        <div class="space-y-2">
            @foreach($data['ranking_kua_top'] as $i => $kua)
            @php
                $bar = match($kua['warna_skp']) { 'hijau' => 'bg-green-500', 'kuning' => 'bg-yellow-400', default => 'bg-red-400' };
                $txt = match($kua['warna_skp']) { 'hijau' => 'text-green-600', 'kuning' => 'text-yellow-600', default => 'text-red-500' };
            @endphp
            <div>
                <div class="flex justify-between items-center mb-0.5">
                    <span class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-green-100 text-green-700 text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i+1 }}</span>
                        <span>{{ Str::limit($kua['nama_unit'], 22) }}</span>
                        <span class="text-xs text-gray-400">{{ $kua['kabupaten'] }}</span>
                    </span>
                    <span class="text-sm font-bold {{ $txt }}">{{ $kua['kepatuhan_skp'] }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full" style="height:8px;">
                    <div class="{{ $bar }} rounded-full" style="height:8px; width: {{ min(100, $kua['kepatuhan_skp']) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Bottom 10 KUA --}}
    <div class="bg-white rounded-2xl shadow border border-red-100 p-5">
        <h3 class="font-bold text-gray-700 text-base mb-4 flex items-center gap-2">
            <span class="text-red-500">▼</span> Bottom 10 KUA
            <span class="text-xs font-normal text-red-400">(perlu pembinaan)</span>
        </h3>
        <div class="space-y-2">
            @forelse($data['ranking_kua_bot'] as $i => $kua)
            @php
                $bar = match($kua['warna_skp']) { 'hijau' => 'bg-green-500', 'kuning' => 'bg-yellow-400', default => 'bg-red-400' };
            @endphp
            <div>
                <div class="flex justify-between items-center mb-0.5">
                    <span class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-red-100 text-red-600 text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i+1 }}</span>
                        <span>{{ Str::limit($kua['nama_unit'], 22) }}</span>
                        <span class="text-xs text-gray-400">{{ $kua['kabupaten'] }}</span>
                    </span>
                    <span class="text-sm font-bold text-red-500">{{ $kua['kepatuhan_skp'] }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full" style="height:8px;">
                    <div class="{{ $bar }} rounded-full" style="height:8px; width: {{ max(2, min(100, $kua['kepatuhan_skp'])) }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-sm text-green-600 font-medium">Semua KUA memiliki kepatuhan baik.</p>
            @endforelse
        </div>
    </div>

</div>

{{-- ===================================================================== --}}
{{-- PANEL: KEPATUHAN HARIAN PER UNIT KERJA (SUDAH vs BELUM) --}}
{{-- ===================================================================== --}}
@php
    $unitData      = $data['kepatuhan_per_unit'];
    $totalBelum    = collect($unitData)->sum('jml_belum');
    $totalSudah    = collect($unitData)->sum('jml_sudah');
    $totalAsnPanel = $totalSudah + $totalBelum;
@endphp
<div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
    {{-- Header panel --}}
    <div class="px-6 py-4 border-b border-gray-100">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    KEPATUHAN KINERJA HARIAN PER UNIT KERJA
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ now()->translatedFormat('l, d F Y') }} — Klik baris untuk lihat daftar nama ASN</p>
            </div>
            {{-- Ringkasan sudah vs belum --}}
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 bg-green-50 border border-green-200 rounded-xl px-4 py-2">
                    <span class="w-3 h-3 rounded-full bg-green-500 flex-shrink-0"></span>
                    <span class="text-sm font-bold text-green-700">{{ $totalSudah }} Sudah Mengisi</span>
                </div>
                <div class="flex items-center gap-1.5 bg-red-50 border border-red-200 rounded-xl px-4 py-2">
                    <span class="w-3 h-3 rounded-full bg-red-400 flex-shrink-0"></span>
                    <span class="text-sm font-bold text-red-600">{{ $totalBelum }} Belum Mengisi</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel per unit --}}
    <div class="table-container" id="tableKepatuhan">
        <table class="table-monitoring text-sm">
            <thead class="text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3 text-center w-10">#</th>
                    <th class="px-4 py-3 text-left">Unit Kerja</th>
                    <th class="px-4 py-3 text-left">Kabupaten</th>
                    <th class="px-4 py-3 text-center w-20">Total</th>
                    <th class="px-4 py-3 text-center w-32">Sudah Isi</th>
                    <th class="px-4 py-3 text-center w-32">Belum Isi</th>
                    <th class="px-4 py-3 text-left" style="min-width:200px">Progress</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="tableKepatuhanBody">
                @foreach($unitData as $i => $unit)
                @php
                    $barW   = min(100, $unit['persen']);
                    $bgRow  = $unit['jml_belum'] === 0 ? 'bg-green-50' : ($unit['persen'] < 50 ? 'bg-red-50' : '');
                    $barClr = match($unit['warna']) { 'hijau' => 'bg-green-500', 'kuning' => 'bg-yellow-400', default => 'bg-red-400' };
                    $txtClr = match($unit['warna']) { 'hijau' => 'text-green-700', 'kuning' => 'text-yellow-600', default => 'text-red-600' };
                    $rowId  = 'row-' . $unit['uk_id'];
                @endphp
                {{-- Baris ringkasan unit --}}
                <tr class="{{ $bgRow }} hover:bg-teal-50 cursor-pointer transition"
                    onclick="toggleDetail('{{ $rowId }}')"
                    title="Klik untuk lihat daftar ASN">
                    <td class="px-4 py-3 text-center text-gray-400 text-xs font-mono">{{ $i + 1 }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-800">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transform transition" id="icon-{{ $rowId }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            {{ $unit['nama_unit'] }}
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-700">{{ $unit['kabupaten'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $unit['total'] }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-bold text-green-600">{{ $unit['jml_sudah'] }}</span>
                        @if($unit['jml_sudah'] > 0)
                        <span class="text-xs text-gray-400 ml-1">ASN</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($unit['jml_belum'] === 0)
                        <span class="inline-flex items-center gap-1 text-green-600 font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Semua Isi
                        </span>
                        @else
                        <span class="font-bold text-red-500">{{ $unit['jml_belum'] }}</span>
                        <span class="text-xs text-gray-400 ml-1">ASN</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            {{-- Stacked bar: hijau (sudah) + merah (belum) --}}
                            <div class="flex-1 bg-red-100 rounded-full overflow-hidden" style="height:10px;">
                                <div class="{{ $barClr }} rounded-full h-full transition-all" style="width: {{ $barW }}%"></div>
                            </div>
                            <span class="text-xs font-bold {{ $txtClr }} w-10 text-right flex-shrink-0">{{ $unit['persen'] }}%</span>
                        </div>
                    </td>
                </tr>
                {{-- Baris detail expandable --}}
                <tr id="{{ $rowId }}" class="hidden">
                    <td colspan="7" class="px-0 py-0 bg-gray-50 border-b border-gray-200">
                        <div class="grid grid-cols-2 divide-x divide-gray-200">
                            {{-- Kolom SUDAH --}}
                            <div class="p-4">
                                <p class="text-xs font-bold text-green-700 uppercase mb-2 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Sudah Mengisi ({{ $unit['jml_sudah'] }})
                                </p>
                                @if(count($unit['sudah']) > 0)
                                <div class="space-y-1 max-h-40 overflow-y-auto">
                                    @foreach($unit['sudah'] as $j => $asn)
                                    <div class="flex items-center gap-2 text-sm py-0.5">
                                        <span class="text-xs text-gray-400 w-5 text-right flex-shrink-0">{{ $j+1 }}.</span>
                                        <span class="font-medium text-gray-800">{{ $asn['nama'] }}</span>
                                        <span class="text-xs text-gray-400 font-mono">{{ $asn['nip'] }}</span>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <p class="text-xs text-gray-400 italic">Belum ada yang mengisi.</p>
                                @endif
                            </div>
                            {{-- Kolom BELUM --}}
                            <div class="p-4">
                                <p class="text-xs font-bold text-red-600 uppercase mb-2 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Belum Mengisi ({{ $unit['jml_belum'] }})
                                </p>
                                @if(count($unit['belum']) > 0)
                                <div class="space-y-1 max-h-40 overflow-y-auto">
                                    @foreach($unit['belum'] as $j => $asn)
                                    <div class="flex items-center gap-2 text-sm py-0.5">
                                        <span class="text-xs text-gray-400 w-5 text-right flex-shrink-0">{{ $j+1 }}.</span>
                                        <span class="font-medium text-red-700">{{ $asn['nama'] }}</span>
                                        <span class="text-xs text-gray-400 font-mono">{{ $asn['nip'] }}</span>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <p class="text-xs text-green-600 font-semibold">Semua ASN sudah mengisi!</p>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ===================================================================== --}}
{{-- TABEL DETAIL PER KUA --}}
{{-- ===================================================================== --}}
<div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-gray-700 text-base">Detail Kepatuhan per KUA</h3>
            <p class="text-xs text-gray-400 mt-0.5">Diurutkan: Kepatuhan SKP Tertinggi → Terendah</p>
        </div>
        <span class="text-sm text-gray-400">{{ count($data['per_kua']) }} KUA</span>
    </div>

    @php $tableRowsKua = count($data['per_kua']); @endphp

    <div class="table-container" id="tableKua">
        <table class="table-monitoring text-sm">
            <thead class="text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3 text-center w-10">#</th>
                    <th class="px-4 py-3 text-left">KUA</th>
                    <th class="px-4 py-3 text-left">Kabupaten</th>
                    <th class="px-4 py-3 text-center">ASN</th>
                    <th class="px-4 py-3 text-center">Isi KH Hari Ini</th>
                    <th class="px-4 py-3 text-center">SKP Disetujui</th>
                    <th class="px-4 py-3 text-center">Kepatuhan SKP ↓</th>
                    <th class="px-4 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($data['per_kua'] as $i => $kua)
                @php
                    $statusBadge = match($kua['warna_skp']) {
                        'hijau'  => 'bg-green-100 text-green-700',
                        'kuning' => 'bg-yellow-100 text-yellow-700',
                        default  => 'bg-red-100 text-red-700',
                    };
                    $statusText = match($kua['warna_skp']) {
                        'hijau'  => 'Baik',
                        'kuning' => 'Perlu Perhatian',
                        default  => 'Rendah',
                    };
                    $barColor = match($kua['warna_skp']) {
                        'hijau'  => 'bg-green-500',
                        'kuning' => 'bg-yellow-400',
                        default  => 'bg-red-400',
                    };
                    $rowBg = $kua['kepatuhan_skp'] == 0 ? 'bg-red-50' : 'hover:bg-gray-50';
                @endphp
                <tr class="{{ $rowBg }} transition">
                    <td class="px-4 py-3 text-center text-gray-400 font-mono text-xs">{{ $i + 1 }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $kua['nama_unit'] }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-700">{{ $kua['kabupaten'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600 font-medium">{{ $kua['total_asn'] }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-semibold {{ $kua['sudah_isi'] > 0 ? 'text-blue-600' : 'text-gray-400' }}">{{ $kua['sudah_isi'] }}</span>
                        <span class="text-gray-400 text-xs">/{{ $kua['total_asn'] }}</span>
                    </td>
                    <td class="px-4 py-3 text-center text-green-600 font-semibold">{{ $kua['disetujui_skp'] }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-20 bg-gray-100 rounded-full" style="height:8px;">
                                <div class="{{ $barColor }} rounded-full" style="height:8px; width: {{ min(100, $kua['kepatuhan_skp']) }}%"></div>
                            </div>
                            <span class="text-sm font-bold text-gray-700 w-12 text-right">{{ $kua['kepatuhan_skp'] }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $statusBadge }}">
                            {{ $statusText }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-gray-400">Belum ada data KUA.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===================================================================== --}}
{{-- TABEL DETAIL PER KABUPATEN --}}
{{-- ===================================================================== --}}
<div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-bold text-gray-700 text-base">Rekapitulasi per Kabupaten/Kota</h3>
        <p class="text-xs text-gray-400 mt-0.5">Seksi Bimas Islam + KUA</p>
    </div>
    <div class="overflow-x-auto">
        <table class="table-monitoring text-sm">
            <thead class="text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-5 py-3 text-center w-10">#</th>
                    <th class="px-5 py-3 text-left">Kabupaten</th>
                    <th class="px-5 py-3 text-center">Jml KUA</th>
                    <th class="px-5 py-3 text-center">Total ASN</th>
                    <th class="px-5 py-3 text-center">Isi KH Hari Ini</th>
                    <th class="px-5 py-3 text-center">Belum Isi</th>
                    <th class="px-5 py-3 text-center">Kepatuhan KH</th>
                    <th class="px-5 py-3 text-center">SKP Disetujui</th>
                    <th class="px-5 py-3 text-center">Belum SKP</th>
                    <th class="px-5 py-3 text-center">Kepatuhan SKP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($data['per_kabupaten'] as $i => $kab)
                @php
                    $barH = match($kab['warna_harian']) { 'hijau' => 'bg-green-500', 'kuning' => 'bg-yellow-400', default => 'bg-red-400' };
                    $barS = match($kab['warna_skp'])   { 'hijau' => 'bg-green-500', 'kuning' => 'bg-yellow-400', default => 'bg-red-400' };
                    $txtH = match($kab['warna_harian']) { 'hijau' => 'text-green-700', 'kuning' => 'text-yellow-600', default => 'text-red-600' };
                    $txtS = match($kab['warna_skp'])   { 'hijau' => 'text-green-700', 'kuning' => 'text-yellow-600', default => 'text-red-600' };
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-4 text-center text-gray-400 text-xs font-mono">{{ $i + 1 }}</td>
                    <td class="px-5 py-4 font-bold text-gray-800">{{ $kab['kabupaten'] }}</td>
                    <td class="px-5 py-4 text-center text-gray-600">{{ $kab['total_kua'] }}</td>
                    <td class="px-5 py-4 text-center font-semibold text-gray-700">{{ $kab['total_asn'] }}</td>
                    <td class="px-5 py-4 text-center text-blue-600 font-semibold">{{ $kab['sudah_isi'] }}</td>
                    <td class="px-5 py-4 text-center font-semibold {{ $kab['belum_isi'] > 0 ? 'text-red-500' : 'text-gray-400' }}">{{ $kab['belum_isi'] }}</td>
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-16 bg-gray-100 rounded-full" style="height:8px;">
                                <div class="{{ $barH }} rounded-full" style="height:8px; width: {{ min(100, $kab['kepatuhan_harian']) }}%"></div>
                            </div>
                            <span class="text-sm font-bold {{ $txtH }}">{{ $kab['kepatuhan_harian'] }}%</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-center text-green-600 font-semibold">{{ $kab['disetujui_skp'] }}</td>
                    <td class="px-5 py-4 text-center font-semibold {{ $kab['belum_buat_skp'] > 0 ? 'text-red-500' : 'text-gray-400' }}">{{ $kab['belum_buat_skp'] }}</td>
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-16 bg-gray-100 rounded-full" style="height:8px;">
                                <div class="{{ $barS }} rounded-full" style="height:8px; width: {{ min(100, $kab['kepatuhan_skp']) }}%"></div>
                            </div>
                            <span class="text-sm font-bold {{ $txtS }}">{{ $kab['kepatuhan_skp'] }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ===================================================================== --}}
{{-- FOOTER --}}
{{-- ===================================================================== --}}
<footer class="text-center text-sm text-gray-400 pb-4">
    eSARAku — Sistem Administrasi Kinerja ASN &nbsp;·&nbsp; Kementerian Agama RI &nbsp;·&nbsp; Sulawesi Barat
    &nbsp;·&nbsp;
    <a href="{{ route('monitoring.bimas-islam.clear-cache', ['token' => $token]) }}" class="text-teal-500 hover:underline">Refresh Cache</a>
</footer>

</main>

{{-- ===================================================================== --}}
{{-- SCRIPTS --}}
{{-- ===================================================================== --}}
<script>
// Auto refresh 5 menit
setTimeout(function () { location.reload(); }, 300000);

// Toggle accordion detail per unit kerja
function toggleDetail(rowId) {
    const row  = document.getElementById(rowId);
    const icon = document.getElementById('icon-' + rowId);
    if (!row) return;
    const isHidden = row.classList.contains('hidden');
    row.classList.toggle('hidden', !isHidden);
    if (icon) icon.style.transform = isHidden ? 'rotate(90deg)' : 'rotate(0deg)';
}

// Auto scroll tabel kepatuhan per unit
(function () {
    const container = document.getElementById('tableKepatuhan');
    if (!container) return;
    const totalRows = {{ count($unitData) }};
    if (totalRows <= 12) return;

    let paused  = false;
    let holding = false;

    container.addEventListener('mouseenter', () => paused = true);
    container.addEventListener('mouseleave', () => paused = false);

    const pauseOnBottom = 7000;
    const pauseOnReset  = 20000;

    function autoScroll() {
        if (!paused && !holding) {
            container.scrollTop += 1;
            if (container.scrollTop + container.clientHeight >= container.scrollHeight) {
                holding = true;
                setTimeout(() => {
                    container.scrollTop = 0;
                    setTimeout(() => { holding = false; }, pauseOnReset);
                }, pauseOnBottom);
            }
        }
        setTimeout(autoScroll, 35);
    }
    setTimeout(autoScroll, pauseOnReset);
})();

// Auto scroll tabel KUA
(function () {
    const container = document.getElementById('tableKua');
    if (!container) return;
    const totalRows = {{ $tableRowsKua }};
    if (totalRows <= 10) return;

    let paused  = false;
    let holding = false;

    container.addEventListener('mouseenter', () => paused = true);
    container.addEventListener('mouseleave', () => paused = false);

    const pauseOnBottom = 7000;
    const pauseOnReset  = 20000;

    function autoScroll() {
        if (!paused && !holding) {
            container.scrollTop += 1;
            if (container.scrollTop + container.clientHeight >= container.scrollHeight) {
                holding = true;
                setTimeout(() => {
                    container.scrollTop = 0;
                    setTimeout(() => { holding = false; }, pauseOnReset);
                }, pauseOnBottom);
            }
        }
        setTimeout(autoScroll, 40);
    }
    setTimeout(autoScroll, pauseOnReset);
})();
</script>

</body>
</html>
