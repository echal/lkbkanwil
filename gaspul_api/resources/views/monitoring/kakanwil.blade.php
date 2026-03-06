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

        {{-- Total ASN --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
            <p class="text-xs text-gray-500 mb-1">Total ASN</p>
            <p class="text-3xl font-bold text-gray-800">{{ number_format($data['kpi']['total_asn']) }}</p>
            <p class="text-xs text-gray-400 mt-1">Aktif</p>
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
                        <td class="px-4 py-3 text-center {{ $unit['belum_buat'] > 0 ? 'text-red-500 font-medium' : 'text-gray-400' }}">
                            {{ $unit['belum_buat'] }}
                        </td>
                        <td class="px-4 py-3 text-center text-green-600 font-medium">{{ $unit['total_disetujui'] }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 bg-gray-100 rounded-full h-1.5">
                                    <div class="{{ match($unit['warna']) { 'hijau' => 'bg-green-500', 'kuning' => 'bg-yellow-400', default => 'bg-red-400' } }} h-1.5 rounded-full"
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

{{-- Footer --}}
<footer class="text-center text-xs text-gray-400 pb-6">
    eSARAKu — Kanwil Kemenag Sulawesi Barat &nbsp;|&nbsp;
    Data tahun {{ $tahun }} &nbsp;|&nbsp;
    Halaman ini read-only &nbsp;|&nbsp;
    Auto-refresh setiap 5 menit
</footer>

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
</script>

</body>
</html>
