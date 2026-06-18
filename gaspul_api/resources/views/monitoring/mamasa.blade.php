<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="300">
    <title>Monitoring Kepatuhan ASN — Kankemenag Kabupaten Mamasa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .warna-hijau  { background-color: #16a34a; color: #fff; }
        .warna-kuning { background-color: #ca8a04; color: #fff; }
        .warna-merah  { background-color: #dc2626; color: #fff; }
        .badge-hijau  { @apply bg-green-100 text-green-800; }
        .badge-kuning { @apply bg-yellow-100 text-yellow-800; }
        .badge-merah  { @apply bg-red-100 text-red-800; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased">

{{-- HEADER --}}
<header class="bg-green-800 text-white shadow-lg sticky top-0 z-30">
    <div class="max-w-screen-2xl mx-auto px-4 py-3 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-bold leading-tight">Dashboard Kepatuhan ASN</h1>
            <p class="text-green-200 text-sm">Kankemenag Kabupaten Mamasa — Tahun {{ $tahun }}</p>
        </div>
        <div class="text-right text-sm text-green-200">
            <div class="font-semibold text-white">{{ $jamSekarang }}</div>
            <div>{{ $lastUpdate }}</div>
            <div class="mt-1">
                <a href="{{ route('monitoring.mamasa.clear-cache', ['token' => $token, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
                   class="text-xs bg-green-600 hover:bg-green-500 px-2 py-0.5 rounded">
                    Refresh Cache
                </a>
            </div>
        </div>
    </div>
</header>

@if($statusWaktu !== 'jam_kerja')
<div class="max-w-screen-2xl mx-auto px-4 pt-4">
    <div class="rounded-lg px-4 py-3 text-sm font-medium
        {{ $statusWaktu === 'libur' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">
        {{ $messageWaktu }}
    </div>
</div>
@endif

<div class="max-w-screen-2xl mx-auto px-4 py-4 space-y-6">

{{-- BULAN NAVIGATOR --}}
<div class="flex items-center gap-3 flex-wrap">
    @php
        $prevBulan = $bulan - 1;
        $prevTahun = $tahun;
        if ($prevBulan < 1) { $prevBulan = 12; $prevTahun--; }
        $nextBulan = $bulan + 1;
        $nextTahun = $tahun;
        if ($nextBulan > 12) { $nextBulan = 1; $nextTahun++; }
        $namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    @endphp
    <a href="{{ route('monitoring.mamasa', ['token' => $token, 'tahun' => $prevTahun, 'bulan' => $prevBulan]) }}"
       class="px-3 py-1.5 bg-white rounded shadow text-sm hover:bg-gray-50">
        &larr; {{ $namaBulan[$prevBulan] }}
    </a>
    <span class="text-base font-bold text-gray-800">
        {{ $namaBulan[$bulan] }} {{ $tahun }}
    </span>
    @if(!($bulan === now()->month && $tahun === now()->year))
    <a href="{{ route('monitoring.mamasa', ['token' => $token, 'tahun' => $nextTahun, 'bulan' => $nextBulan]) }}"
       class="px-3 py-1.5 bg-white rounded shadow text-sm hover:bg-gray-50">
        {{ $namaBulan[$nextBulan] }} &rarr;
    </a>
    @endif
    <span class="text-xs text-gray-500 ml-2">{{ $data['hari_kerja_berlalu'] }} hari kerja berlalu</span>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SEKSI A — KPI HARIAN HARI INI --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<section>
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">A. Kepatuhan Pengisian Hari Ini</h2>
    @php $kpi = $data['kpi_harian']; @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {{-- Total ASN --}}
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-3xl font-extrabold text-gray-800">{{ number_format($kpi['total_asn']) }}</div>
            <div class="text-xs text-gray-500 mt-1">Total ASN Aktif</div>
        </div>
        {{-- Wajib Isi --}}
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-3xl font-extrabold text-blue-700">{{ number_format($kpi['wajib_hari_ini']) }}</div>
            <div class="text-xs text-gray-500 mt-1">Wajib Isi Hari Ini</div>
        </div>
        {{-- Sudah Isi --}}
        <div class="rounded-lg shadow p-4 text-center warna-{{ $kpi['warna'] }}">
            <div class="text-3xl font-extrabold">{{ number_format($kpi['sudah_hari_ini']) }}</div>
            <div class="text-xs opacity-80 mt-1">Sudah Isi Hari Ini</div>
            <div class="text-lg font-bold mt-0.5">{{ $kpi['persen'] }}%</div>
        </div>
        {{-- Belum Isi --}}
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-3xl font-extrabold text-red-600">{{ number_format($kpi['belum_hari_ini']) }}</div>
            <div class="text-xs text-gray-500 mt-1">Belum Isi Hari Ini</div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SEKSI B — DISTRIBUSI SKP TAHUN BERJALAN --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<section>
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">B. Status SKP Tahun {{ $tahun }}</h2>
    @php $skp = $data['skp']; @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-extrabold text-gray-800">{{ $skp['total_asn'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Total ASN</div>
        </div>
        <div class="rounded-lg shadow p-4 text-center warna-{{ $skp['warna_disetujui'] }}">
            <div class="text-2xl font-extrabold">{{ $skp['disetujui'] }}</div>
            <div class="text-xs opacity-80 mt-1">SKP Disetujui</div>
            <div class="text-sm font-bold">{{ $skp['persen_disetujui'] }}%</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-yellow-400">
            <div class="text-2xl font-extrabold text-yellow-700">{{ $skp['menunggu'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Menunggu Approval</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-orange-400">
            <div class="text-2xl font-extrabold text-orange-700">{{ $skp['perlu_tindak'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Perlu Tindak Lanjut</div>
            <div class="text-xs text-gray-400">(Draft / Ditolak)</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-red-500">
            <div class="text-2xl font-extrabold text-red-700">{{ $skp['belum_buat'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Belum Buat SKP</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
            <div class="text-2xl font-extrabold text-gray-600">{{ $skp['sudah_buat'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Sudah Buat SKP</div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SEKSI C — RINGKASAN PEMBINAAN --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<section>
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">C. Ringkasan Prioritas Pembinaan</h2>
    @php $dist = $data['distribusi']; @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-red-600 text-white rounded-lg shadow p-4 text-center">
            <div class="text-3xl font-extrabold">{{ $dist['TINGGI'] }}</div>
            <div class="text-sm font-semibold mt-1">Prioritas TINGGI</div>
            <div class="text-xs opacity-75">Skor &ge; 40</div>
        </div>
        <div class="bg-orange-500 text-white rounded-lg shadow p-4 text-center">
            <div class="text-3xl font-extrabold">{{ $dist['SEDANG'] }}</div>
            <div class="text-sm font-semibold mt-1">Prioritas SEDANG</div>
            <div class="text-xs opacity-75">Skor 10–39</div>
        </div>
        <div class="bg-yellow-400 text-yellow-900 rounded-lg shadow p-4 text-center">
            <div class="text-3xl font-extrabold">{{ $dist['RENDAH'] }}</div>
            <div class="text-sm font-semibold mt-1">Prioritas RENDAH</div>
            <div class="text-xs opacity-75">Skor 1–9</div>
        </div>
        <div class="bg-green-600 text-white rounded-lg shadow p-4 text-center">
            <div class="text-3xl font-extrabold">{{ $dist['BAIK'] }}</div>
            <div class="text-sm font-semibold mt-1">Kepatuhan BAIK</div>
            <div class="text-xs opacity-75">Skor 0</div>
        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SEKSI D — RANKING UNIT KERJA --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<section x-data="{ expandRanking: false }">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">D. Ranking Kepatuhan Per Unit Kerja</h2>
        <button @click="expandRanking = !expandRanking"
                class="text-xs text-green-700 hover:underline">
            <span x-text="expandRanking ? 'Sembunyikan' : 'Tampilkan Semua'"></span>
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-green-800 text-white text-xs uppercase">
                <tr>
                    <th class="px-3 py-2 text-left w-8">#</th>
                    <th class="px-3 py-2 text-left">Unit Kerja</th>
                    <th class="px-3 py-2 text-center">Hari Isi / Wajib (Bulan)</th>
                    <th class="px-3 py-2 text-center">%</th>
                    <th class="px-3 py-2 text-center">Isi Hari Ini</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($data['ranking_unit'] as $i => $unit)
                <tr class="hover:bg-gray-50" @if($i >= 5) x-show="expandRanking" @endif>
                    <td class="px-3 py-2 text-gray-400 font-bold">{{ $i + 1 }}</td>
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $unit['nama_unit'] }}</td>
                    <td class="px-3 py-2 text-center text-gray-700">
                        {{ $unit['sudah'] }} / {{ $unit['wajib'] }}
                    </td>
                    <td class="px-3 py-2 text-center">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-bold warna-{{ $unit['warna'] }}">
                            {{ $unit['persen'] }}%
                        </span>
                    </td>
                    <td class="px-3 py-2 text-center text-gray-600">
                        {{ $unit['sudah_hari_ini'] }} / {{ $unit['wajib_hari_ini'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if(count($data['ranking_unit']) > 5)
        <div class="px-4 py-2 text-xs text-gray-500 border-t bg-gray-50"
             x-show="!expandRanking">
            Menampilkan 5 dari {{ count($data['ranking_unit']) }} unit kerja.
            <button @click="expandRanking = true" class="text-green-700 hover:underline ml-1">Tampilkan semua &darr;</button>
        </div>
        @endif
    </div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SEKSI E — PANEL ASN PRIORITAS PEMBINAAN --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<section
    x-data="pembinaan({
        data: {{ json_encode($data['pembinaan']) }},
        token: '{{ $token }}',
        tahun: {{ $tahun }},
        bulan: {{ $bulan }}
    })"
    x-cloak
>
    <div class="flex items-center justify-between mb-2 flex-wrap gap-2">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">E. Panel Prioritas Pembinaan</h2>
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Filter prioritas --}}
            <select x-model="filterPrioritas"
                    class="text-sm border border-gray-200 rounded px-2 py-1 focus:ring-1 focus:ring-green-500">
                <option value="">Semua Prioritas</option>
                <option value="TINGGI">TINGGI</option>
                <option value="SEDANG">SEDANG</option>
                <option value="RENDAH">RENDAH</option>
                <option value="BAIK">BAIK</option>
            </select>
            {{-- Filter status SKP --}}
            <select x-model="filterSkp"
                    class="text-sm border border-gray-200 rounded px-2 py-1 focus:ring-1 focus:ring-green-500">
                <option value="">Semua Status SKP</option>
                <option value="NULL">Belum Buat</option>
                <option value="DRAFT">Draft</option>
                <option value="DIAJUKAN">Diajukan</option>
                <option value="DISETUJUI">Disetujui</option>
                <option value="DITOLAK">Ditolak</option>
                <option value="REVISI_DIAJUKAN">Revisi Diajukan</option>
                <option value="REVISI_DITOLAK">Revisi Ditolak</option>
            </select>
            {{-- Search nama/NIP/unit --}}
            <input type="search"
                   x-model="search"
                   placeholder="Cari nama / NIP / unit..."
                   class="text-sm border border-gray-200 rounded px-3 py-1 w-48 focus:ring-1 focus:ring-green-500">
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        {{-- Stats bar --}}
        <div class="px-4 py-2 bg-gray-50 border-b text-xs text-gray-500 flex gap-4 flex-wrap">
            <span>Menampilkan <strong x-text="filtered.length"></strong> dari <strong>{{ count($data['pembinaan']) }}</strong> ASN</span>
            <span class="text-red-600">TINGGI: <strong x-text="countPrioritas('TINGGI')"></strong></span>
            <span class="text-orange-600">SEDANG: <strong x-text="countPrioritas('SEDANG')"></strong></span>
            <span class="text-yellow-700">RENDAH: <strong x-text="countPrioritas('RENDAH')"></strong></span>
            <span class="text-green-700">BAIK: <strong x-text="countPrioritas('BAIK')"></strong></span>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-green-800 text-white text-xs uppercase sticky top-16">
                <tr>
                    <th class="px-3 py-2 text-left">#</th>
                    <th class="px-3 py-2 text-left cursor-pointer" @click="sortBy('name')">
                        Nama <span class="opacity-50">⇅</span>
                    </th>
                    <th class="px-3 py-2 text-left">NIP / Jabatan</th>
                    <th class="px-3 py-2 text-left">Unit Kerja</th>
                    <th class="px-3 py-2 text-center cursor-pointer" @click="sortBy('persen')">
                        Kepatuhan <span class="opacity-50">⇅</span>
                    </th>
                    <th class="px-3 py-2 text-center">Status SKP</th>
                    <th class="px-3 py-2 text-center cursor-pointer" @click="sortBy('skor')">
                        Prioritas <span class="opacity-50">⇅</span>
                    </th>
                    <th class="px-3 py-2 text-center">Terakhir Isi</th>
                    <th class="px-3 py-2 text-center">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="(row, idx) in paginated" :key="row.id">
                    <tr class="hover:bg-gray-50 transition-colors"
                        :class="{'bg-red-50': row.prioritas === 'TINGGI'}">
                        <td class="px-3 py-2 text-gray-400 text-xs" x-text="offset + idx + 1"></td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-gray-800" x-text="row.name"></div>
                            <template x-for="a in row.alasan" :key="a">
                                <span class="inline-block text-xs bg-red-100 text-red-700 rounded px-1 mr-0.5 mt-0.5" x-text="a"></span>
                            </template>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500">
                            <div x-text="row.nip"></div>
                            <div class="text-gray-400" x-text="row.jabatan"></div>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600" x-text="row.nama_unit"></td>
                        <td class="px-3 py-2 text-center">
                            <div class="text-sm font-bold"
                                 :class="{
                                    'text-green-700': row.persen >= 80,
                                    'text-yellow-700': row.persen >= 50 && row.persen < 80,
                                    'text-red-700': row.persen < 50
                                 }"
                                 x-text="row.persen + '%'"></div>
                            <div class="text-xs text-gray-400"
                                 x-text="row.hari_isi + '/' + row.hari_wajib + ' hari'"></div>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-block text-xs px-2 py-0.5 rounded font-medium"
                                  :class="badgeSkp(row.skp_status)"
                                  x-text="labelSkp(row.skp_status)"></span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-block text-xs px-2 py-0.5 rounded font-bold"
                                  :class="{
                                    'bg-red-600 text-white': row.prioritas === 'TINGGI',
                                    'bg-orange-500 text-white': row.prioritas === 'SEDANG',
                                    'bg-yellow-300 text-yellow-900': row.prioritas === 'RENDAH',
                                    'bg-green-100 text-green-800': row.prioritas === 'BAIK'
                                  }"
                                  x-text="row.prioritas"></span>
                            <div class="text-xs text-gray-400 mt-0.5">Skor: <span x-text="row.skor"></span></div>
                        </td>
                        <td class="px-3 py-2 text-center text-xs text-gray-500"
                            x-text="row.last_isi ? row.last_isi : '—'"></td>
                        <td class="px-3 py-2 text-center">
                            <button @click="openDetail(row.id)"
                                    class="text-xs bg-green-700 text-white px-2 py-1 rounded hover:bg-green-600">
                                Detail
                            </button>
                        </td>
                    </tr>
                </template>
                <tr x-show="filtered.length === 0">
                    <td colspan="9" class="px-4 py-6 text-center text-gray-400 text-sm">
                        Tidak ada ASN yang sesuai filter.
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="flex items-center justify-between px-4 py-3 border-t bg-gray-50 text-sm">
            <span class="text-gray-500 text-xs">
                Hal. <strong x-text="page"></strong> / <strong x-text="totalPages"></strong>
                (<span x-text="filtered.length"></span> ASN)
            </span>
            <div class="flex gap-1">
                <button @click="page = 1" :disabled="page === 1"
                        class="px-2 py-1 rounded border text-xs disabled:opacity-40 hover:bg-gray-100">«</button>
                <button @click="page--" :disabled="page === 1"
                        class="px-2 py-1 rounded border text-xs disabled:opacity-40 hover:bg-gray-100">‹</button>
                <button @click="page++" :disabled="page >= totalPages"
                        class="px-2 py-1 rounded border text-xs disabled:opacity-40 hover:bg-gray-100">›</button>
                <button @click="page = totalPages" :disabled="page >= totalPages"
                        class="px-2 py-1 rounded border text-xs disabled:opacity-40 hover:bg-gray-100">»</button>
            </div>
            <select x-model.number="perPage" class="text-xs border rounded px-1 py-1">
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
                <option value="100">100 / hal</option>
            </select>
        </div>
    </div>

    {{-- ═══════════════════════════════════════ --}}
    {{-- MODAL DRILL-DOWN --}}
    {{-- ═══════════════════════════════════════ --}}
    <div x-show="modal.open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
         @keydown.escape.window="modal.open = false"
         @click.self="modal.open = false">

        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-screen overflow-y-auto"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:enter-end="scale-100 opacity-100">

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b bg-green-800 text-white rounded-t-xl">
                <h3 class="font-bold text-lg">Detail Kepatuhan ASN</h3>
                <button @click="modal.open = false" class="text-green-200 hover:text-white text-2xl leading-none">&times;</button>
            </div>

            {{-- Loading --}}
            <div x-show="modal.loading" class="py-16 text-center text-gray-500">
                <svg class="animate-spin h-8 w-8 text-green-700 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Memuat data...
            </div>

            {{-- Error --}}
            <div x-show="modal.error && !modal.loading" class="py-8 text-center text-red-600">
                <p x-text="modal.error"></p>
            </div>

            {{-- Content --}}
            <template x-if="!modal.loading && !modal.error && modal.data">
                <div class="p-6 space-y-4">
                    {{-- Info ASN --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-bold text-gray-800 text-base" x-text="modal.data.asn.name"></h4>
                        <div class="text-sm text-gray-600 mt-1 grid grid-cols-2 gap-1">
                            <div>NIP: <span x-text="modal.data.asn.nip"></span></div>
                            <div>Jabatan: <span x-text="modal.data.asn.jabatan"></span></div>
                            <div>Unit: <span x-text="modal.data.asn.nama_unit"></span></div>
                            <div>Atasan: <span x-text="modal.data.asn.nama_atasan"></span></div>
                            <div>Jabatan Atasan: <span x-text="modal.data.asn.jabatan_atasan"></span></div>
                            <div>Status SKP:
                                <span :class="badgeSkp(modal.data.asn.skp_status)"
                                      class="inline-block text-xs px-2 py-0.5 rounded font-medium"
                                      x-text="labelSkp(modal.data.asn.skp_status)"></span>
                            </div>
                        </div>
                    </div>

                    {{-- KPI --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div class="text-center bg-blue-50 rounded-lg p-3">
                            <div class="text-2xl font-extrabold text-blue-700" x-text="modal.data.kepatuhan.hari_isi"></div>
                            <div class="text-xs text-gray-500">Hari Diisi</div>
                        </div>
                        <div class="text-center bg-gray-100 rounded-lg p-3">
                            <div class="text-2xl font-extrabold text-gray-700" x-text="modal.data.kepatuhan.hari_wajib"></div>
                            <div class="text-xs text-gray-500">Hari Wajib</div>
                        </div>
                        <div class="text-center rounded-lg p-3"
                             :class="{
                                'bg-green-100': modal.data.kepatuhan.persen >= 80,
                                'bg-yellow-100': modal.data.kepatuhan.persen >= 50 && modal.data.kepatuhan.persen < 80,
                                'bg-red-100': modal.data.kepatuhan.persen < 50
                             }">
                            <div class="text-2xl font-extrabold"
                                 :class="{
                                    'text-green-700': modal.data.kepatuhan.persen >= 80,
                                    'text-yellow-700': modal.data.kepatuhan.persen >= 50 && modal.data.kepatuhan.persen < 80,
                                    'text-red-700': modal.data.kepatuhan.persen < 50
                                 }"
                                 x-text="modal.data.kepatuhan.persen + '%'"></div>
                            <div class="text-xs text-gray-500">Kepatuhan</div>
                        </div>
                    </div>

                    <div class="text-xs text-gray-500 grid grid-cols-2 gap-2">
                        <div>Terakhir isi KH: <strong x-text="modal.data.kepatuhan.last_kh || '—'"></strong></div>
                        <div>Terakhir isi TLA: <strong x-text="modal.data.kepatuhan.last_tla || '—'"></strong></div>
                    </div>

                    {{-- Riwayat --}}
                    <div>
                        <h5 class="text-sm font-semibold text-gray-600 mb-2">Riwayat Pengisian Bulan Ini</h5>
                        <div x-show="modal.data.riwayat.length === 0" class="text-sm text-gray-400 italic">Belum ada pengisian bulan ini.</div>
                        <div class="space-y-1 max-h-64 overflow-y-auto pr-1">
                            <template x-for="r in modal.data.riwayat" :key="r.tanggal">
                                <div class="flex items-center gap-2 text-xs p-2 rounded bg-gray-50 border">
                                    <span class="font-medium text-gray-700 w-24" x-text="r.tanggal"></span>
                                    <span x-show="r.ada_kh"
                                          class="bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">KH</span>
                                    <span x-show="r.ada_tla"
                                          class="bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded">TLA</span>
                                    <span class="text-gray-400 ml-auto"
                                          x-text="r.total_menit > 0 ? r.total_menit + ' mnt' : ''"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</section>

{{-- FOOTER --}}
<footer class="text-center text-xs text-gray-400 pb-6 mt-8">
    Dashboard Monitoring Kepatuhan ASN — Kankemenag Kabupaten Mamasa &bull;
    Auto-refresh setiap 5 menit &bull;
    {{ $lastUpdate }}
</footer>

</div>{{-- end max-w container --}}

<script>
function pembinaan({ data, token, tahun, bulan }) {
    return {
        data,
        token,
        tahun,
        bulan,
        search: '',
        filterPrioritas: '',
        filterSkp: '',
        sortField: 'skor',
        sortDir: 'desc',
        page: 1,
        perPage: 50,
        modal: { open: false, loading: false, error: null, data: null },

        get filtered() {
            let rows = this.data.slice();

            if (this.filterPrioritas) {
                rows = rows.filter(r => r.prioritas === this.filterPrioritas);
            }
            if (this.filterSkp) {
                if (this.filterSkp === 'NULL') {
                    rows = rows.filter(r => r.skp_status === null);
                } else {
                    rows = rows.filter(r => r.skp_status === this.filterSkp);
                }
            }
            if (this.search.trim()) {
                const q = this.search.trim().toLowerCase();
                rows = rows.filter(r =>
                    r.name.toLowerCase().includes(q) ||
                    (r.nip && r.nip.toLowerCase().includes(q)) ||
                    r.nama_unit.toLowerCase().includes(q)
                );
            }

            const dir = this.sortDir === 'asc' ? 1 : -1;
            const f   = this.sortField;
            rows.sort((a, b) => {
                if (a[f] < b[f]) return -1 * dir;
                if (a[f] > b[f]) return 1 * dir;
                return a.name.localeCompare(b.name);
            });

            return rows;
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },

        get offset() {
            return (this.page - 1) * this.perPage;
        },

        get paginated() {
            return this.filtered.slice(this.offset, this.offset + this.perPage);
        },

        sortBy(field) {
            if (this.sortField === field) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortDir   = 'desc';
            }
            this.page = 1;
        },

        countPrioritas(p) {
            return this.filtered.filter(r => r.prioritas === p).length;
        },

        badgeSkp(status) {
            const map = {
                null: 'bg-red-100 text-red-700',
                DRAFT: 'bg-gray-100 text-gray-700',
                DIAJUKAN: 'bg-yellow-100 text-yellow-700',
                DISETUJUI: 'bg-green-100 text-green-700',
                DITOLAK: 'bg-red-200 text-red-800',
                REVISI_DIAJUKAN: 'bg-orange-100 text-orange-700',
                REVISI_DITOLAK: 'bg-blue-100 text-blue-700',
            };
            return map[status] ?? 'bg-gray-100 text-gray-600';
        },

        labelSkp(status) {
            const map = {
                null: 'Belum Buat',
                DRAFT: 'Draft',
                DIAJUKAN: 'Diajukan',
                DISETUJUI: 'Disetujui',
                DITOLAK: 'Ditolak',
                REVISI_DIAJUKAN: 'Revisi Diajukan',
                REVISI_DITOLAK: 'Revisi Ditolak',
            };
            return map[status] ?? (status || 'Belum Buat');
        },

        async openDetail(id) {
            this.modal.open    = true;
            this.modal.loading = true;
            this.modal.error   = null;
            this.modal.data    = null;

            const url = `/monitoring-tv/mamasa/asn-detail/${id}?token=${this.token}&tahun=${this.tahun}&bulan=${this.bulan}`;
            try {
                const res = await fetch(url);
                if (!res.ok) {
                    this.modal.error = 'Gagal memuat data. Kode: ' + res.status;
                } else {
                    this.modal.data = await res.json();
                }
            } catch (e) {
                this.modal.error = 'Gagal menghubungi server: ' + e.message;
            } finally {
                this.modal.loading = false;
            }
        },
    };
}
</script>

</body>
</html>
