<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring SKP eSARAku — Kankemenag Pasangkayu {{ $tahun }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { font-size: 18px; }
        .tv-card { padding: 1.75rem; }
        .kpi-value { font-size: 3rem; font-weight: 800; line-height: 1; }
        .kpi-label { font-size: 0.85rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }
        .progress-bar { height: 14px; border-radius: 9999px; }
        /* Tabel sticky header */
        .table-container {
            max-height: 55vh;
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
        .table-monitoring thead th::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: 0;
            border-bottom: 2px solid #e5e7eb;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

{{-- ===================================================================== --}}
{{-- HEADER --}}
{{-- ===================================================================== --}}
<header class="bg-gradient-to-r from-green-800 to-green-950 text-white shadow-xl">
    <div class="max-w-screen-2xl mx-auto px-6 py-5">
        <div class="flex items-center justify-between gap-4">
            {{-- Logo + Judul --}}
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
                    <h1 class="text-2xl font-black tracking-wide leading-tight">MONITORING KEPATUHAN SKP eSARAKu</h1>
                    <p class="text-green-200 font-semibold text-base mt-0.5">KANKEMENAG KABUPATEN PASANGKAYU &nbsp;·&nbsp; TAHUN {{ $tahun }}</p>
                </div>
            </div>
            {{-- Info kanan --}}
            <div class="text-right flex-shrink-0">
                <p class="text-green-200 text-sm font-medium">Last Update</p>
                <p class="text-white font-bold text-lg">{{ $lastUpdate }}</p>
                <p class="text-green-300 text-xs mt-1">Auto refresh setiap 5 menit</p>
            </div>
        </div>
    </div>
</header>

{{-- Flash info --}}
@if(session('info'))
<div class="bg-blue-50 border-b border-blue-200 px-6 py-2 text-blue-700 text-sm text-center">
    {{ session('info') }}
</div>
@endif

<main class="max-w-screen-2xl mx-auto px-6 py-6 space-y-6">

{{-- ===================================================================== --}}
{{-- SECTION A: KPI CARDS --}}
{{-- ===================================================================== --}}
@php
    $kpi = $data['kpi'];
    $warnaKelas = match($kpi['warna']) {
        'hijau'  => ['bg' => 'bg-green-50 border-green-300',  'text' => 'text-green-700',  'badge' => 'bg-green-100 text-green-700'],
        'kuning' => ['bg' => 'bg-yellow-50 border-yellow-300','text' => 'text-yellow-700', 'badge' => 'bg-yellow-100 text-yellow-700'],
        default  => ['bg' => 'bg-red-50 border-red-300',      'text' => 'text-red-700',    'badge' => 'bg-red-100 text-red-700'],
    };
@endphp

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
    {{-- Total ASN --}}
    <div class="bg-white rounded-2xl shadow border border-gray-200 tv-card text-center">
        <p class="kpi-label text-gray-500 mb-2">Total ASN</p>
        <p class="kpi-value text-gray-800">{{ number_format($kpi['total_asn']) }}</p>
        <p class="text-sm text-gray-400 mt-2">ASN Aktif</p>
    </div>

    {{-- Sudah Buat --}}
    <div class="bg-white rounded-2xl shadow border border-blue-200 tv-card text-center">
        <p class="kpi-label text-blue-500 mb-2">Sudah Buat SKP</p>
        <p class="kpi-value text-blue-600">{{ number_format($kpi['sudah_buat']) }}</p>
        <p class="text-sm text-blue-400 mt-2">
            {{ $kpi['total_asn'] > 0 ? round(($kpi['sudah_buat'] / $kpi['total_asn']) * 100, 1) : 0 }}% dari total
        </p>
    </div>

    {{-- Disetujui --}}
    <div class="bg-white rounded-2xl shadow border border-green-200 tv-card text-center">
        <p class="kpi-label text-green-500 mb-2">Disetujui</p>
        <p class="kpi-value text-green-600">{{ number_format($kpi['disetujui']) }}</p>
        <p class="text-sm text-green-400 mt-2">SKP disetujui atasan</p>
    </div>

    {{-- Belum Buat --}}
    <div class="bg-white rounded-2xl shadow border border-red-200 tv-card text-center">
        <p class="kpi-label text-red-500 mb-2">Belum Buat</p>
        <p class="kpi-value text-red-500">{{ number_format($kpi['belum_buat']) }}</p>
        <p class="text-sm text-red-400 mt-2">Perlu segera dibuat</p>
    </div>

    {{-- Kepatuhan % --}}
    <div class="rounded-2xl shadow border tv-card text-center {{ $warnaKelas['bg'] }}">
        <p class="kpi-label {{ $warnaKelas['text'] }} mb-2">Kepatuhan</p>
        <p class="kpi-value {{ $warnaKelas['text'] }}">{{ $kpi['kepatuhan'] }}%</p>
        <span class="inline-block mt-2 px-3 py-1 rounded-full text-sm font-bold {{ $warnaKelas['badge'] }}">
            {{ $kpi['warna'] === 'hijau' ? 'Baik' : ($kpi['warna'] === 'kuning' ? 'Perlu Perhatian' : 'Rendah') }}
        </span>
    </div>
</div>

{{-- ===================================================================== --}}
{{-- SECTION B: Grafik + Ranking --}}
{{-- ===================================================================== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Bar Chart Kepatuhan per Unit --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow border border-gray-200 p-6">
        <h3 class="font-bold text-gray-700 text-lg mb-4">Kepatuhan per Unit Kerja</h3>
        <div style="height: 320px;">
            <canvas id="chartUnit"></canvas>
        </div>
    </div>

    {{-- Ranking Top & Bottom --}}
    <div class="space-y-4">

        {{-- Top 5 --}}
        <div class="bg-white rounded-2xl shadow border border-gray-200 p-5">
            <h3 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                <span class="text-green-500">▲</span> Top 5 Unit Tertinggi
            </h3>
            <div class="space-y-3">
                @forelse($data['ranking_top'] as $i => $unit)
                @php
                    $barColor = match($unit['warna']) { 'hijau' => 'bg-green-500', 'kuning' => 'bg-yellow-400', default => 'bg-red-400' };
                    $textColor = match($unit['warna']) { 'hijau' => 'text-green-600', 'kuning' => 'text-yellow-600', default => 'text-red-500' };
                @endphp
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-green-100 text-green-700 text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                            {{ Str::limit($unit['nama_unit'], 28) }}
                        </span>
                        <span class="text-sm font-bold {{ $textColor }}">{{ $unit['kepatuhan'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full progress-bar">
                        <div class="{{ $barColor }} progress-bar transition-all" style="width: {{ min(100, $unit['kepatuhan']) }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-400">Belum ada data.</p>
                @endforelse
            </div>
        </div>

        {{-- 5 Perlu Perhatian --}}
        <div class="bg-white rounded-2xl shadow border border-red-100 p-5">
            <h3 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                <span class="text-red-500">▼</span> Unit Perlu Perhatian
                <span class="text-xs font-normal text-red-400">(kepatuhan &lt; 50%)</span>
            </h3>
            <div class="space-y-3">
                @forelse($data['ranking_bottom'] as $i => $unit)
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-red-100 text-red-600 text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                            {{ Str::limit($unit['nama_unit'], 28) }}
                        </span>
                        <span class="text-sm font-bold text-red-500">{{ $unit['kepatuhan'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full progress-bar">
                        <div class="bg-red-400 progress-bar transition-all" style="width: {{ max(2, min(100, $unit['kepatuhan'])) }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-sm text-green-600 font-medium">Semua unit ≥ 50% kepatuhan.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>

{{-- ===================================================================== --}}
{{-- SECTION C: TABEL DETAIL --}}
{{-- ===================================================================== --}}
<div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-gray-700 text-lg">Detail Kepatuhan per Unit Kerja</h3>
            <p class="text-xs text-gray-400 mt-0.5">Diurutkan: Kepatuhan Tertinggi → Terendah</p>
        </div>
        <span class="text-sm text-gray-400">{{ count($data['per_unit']) }} unit kerja</span>
    </div>

    @php $tableRows = count($data['per_unit']); @endphp

    <div class="table-container" id="tableContainer">
        <table class="table-monitoring text-base">
            <thead class="text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-5 py-3 text-center w-12">#</th>
                    <th class="px-5 py-3 text-left">Unit Kerja</th>
                    <th class="px-5 py-3 text-center">Total ASN</th>
                    <th class="px-5 py-3 text-center">Sudah Buat</th>
                    <th class="px-5 py-3 text-center">Belum Buat</th>
                    <th class="px-5 py-3 text-center">Disetujui</th>
                    <th class="px-5 py-3 text-center">Kepatuhan ↓</th>
                    <th class="px-5 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="tableBody">
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
                    $barColor = match($unit['warna']) {
                        'hijau'  => 'bg-green-500',
                        'kuning' => 'bg-yellow-400',
                        default  => 'bg-red-400',
                    };
                    $rowHighlight = $unit['kepatuhan'] == 0 ? 'bg-red-50' : 'hover:bg-gray-50';
                @endphp
                <tr class="{{ $rowHighlight }} transition">
                    <td class="px-5 py-4 text-center text-sm text-gray-400 font-mono">{{ $i + 1 }}</td>
                    <td class="px-5 py-4 font-semibold text-gray-800">{{ $unit['nama_unit'] }}</td>
                    <td class="px-5 py-4 text-center text-gray-600 font-medium">{{ $unit['total_asn'] }}</td>
                    <td class="px-5 py-4 text-center text-blue-600 font-semibold">{{ $unit['total_buat'] }}</td>
                    <td class="px-5 py-4 text-center font-semibold {{ $unit['belum_buat'] > 0 ? 'text-red-500' : 'text-gray-400' }}">
                        {{ $unit['belum_buat'] }}
                    </td>
                    <td class="px-5 py-4 text-center text-green-600 font-semibold">{{ $unit['disetujui'] }}</td>
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <div class="w-24 bg-gray-100 rounded-full" style="height:10px;">
                                <div class="{{ $barColor }} rounded-full" style="height:10px; width: {{ min(100, $unit['kepatuhan']) }}%"></div>
                            </div>
                            <span class="text-sm font-bold text-gray-700 w-14 text-right">{{ $unit['kepatuhan'] }}%</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $statusBadge }}">
                            {{ $statusText }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-gray-400">Belum ada data unit kerja.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===================================================================== --}}
{{-- FOOTER --}}
{{-- ===================================================================== --}}
<footer class="text-center text-sm text-gray-400 pb-4">
    eSARAku — Sistem Administrasi Kinerja ASN &nbsp;·&nbsp; Kementerian Agama RI &nbsp;·&nbsp; Sulawesi Barat
</footer>

</main>

{{-- ===================================================================== --}}
{{-- SCRIPTS --}}
{{-- ===================================================================== --}}
<script>
// Bar chart horizontal — kepatuhan per unit
const unitData = @json($data['per_unit']);
const labels   = unitData.map(u => u.nama_unit.length > 30 ? u.nama_unit.substring(0, 30) + '…' : u.nama_unit);
const values   = unitData.map(u => u.kepatuhan);
const colors   = unitData.map(u => {
    if (u.warna === 'hijau')  return 'rgba(34,197,94,0.8)';
    if (u.warna === 'kuning') return 'rgba(234,179,8,0.8)';
    return 'rgba(239,68,68,0.8)';
});

const ctx = document.getElementById('chartUnit').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Kepatuhan (%)',
            data: values,
            backgroundColor: colors,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.parsed.x}% kepatuhan`
                }
            }
        },
        scales: {
            x: {
                min: 0, max: 100,
                ticks: { callback: v => v + '%', font: { size: 13 } },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            y: {
                ticks: { font: { size: 13 } }
            }
        }
    }
});

// Auto refresh 5 menit
setTimeout(function () { location.reload(); }, 300000);

// Auto scroll tabel jika lebih dari 10 baris — pakai JS scrollTop (kompatibel sticky header)
(function () {
    const container  = document.getElementById('tableContainer');
    if (!container) return;
    const totalRows  = {{ $tableRows }};
    if (totalRows <= 10) return;

    let paused  = false;
    let holding = false; // sedang dalam jeda setelah reset

    container.addEventListener('mouseenter', () => paused = true);
    container.addEventListener('mouseleave', () => paused = false);

    const scrollStep   = 1;     // px per tick
    const tickDelay    = 30;    // ms per tick
    const pauseOnBottom = 7000; // 7 detik tahan di baris terakhir sebelum reset
    const pauseOnReset  = 15000; // 15 detik tahan di baris pertama setelah reset

    function autoScroll() {
        if (!paused && !holding) {
            container.scrollTop += scrollStep;

            // Sudah di bawah — tahan 7 detik di bawah, lalu reset ke atas, lalu tahan 15 detik
            if (container.scrollTop + container.clientHeight >= container.scrollHeight) {
                holding = true;
                setTimeout(() => {
                    container.scrollTop = 0;          // reset ke atas
                    setTimeout(() => { holding = false; }, pauseOnReset); // tahan 15 detik di atas
                }, pauseOnBottom);                    // tahan 7 detik di bawah dulu
            }
        }
        setTimeout(autoScroll, tickDelay);
    }

    // Mulai setelah 15 detik pertama (baca dulu sebelum scroll)
    setTimeout(autoScroll, pauseOnReset);
})();
</script>

</body>
</html>
