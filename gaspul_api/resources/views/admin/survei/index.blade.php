@extends('layouts.app')

@section('title', 'Monitoring Survei ESARAKU')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Monitoring Survei ESARAKU</h1>
            <p class="text-sm text-gray-500 mt-0.5">Monitoring tingkat partisipasi survei penggunaan ESARAKU.</p>
        </div>
        @if(isset($survei) && $survei)
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('admin.survei.export') }}"
                    class="inline-flex items-center gap-2 bg-green-600 text-white text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-green-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export Partisipasi
                </a>
                <a href="{{ route('admin.survei.export-saran') }}"
                    class="inline-flex items-center gap-2 bg-blue-600 text-white text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export Saran
                </a>
                <a href="{{ route('admin.survei.export-detail') }}"
                    class="inline-flex items-center gap-2 bg-purple-600 text-white text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-purple-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export Detail Jawaban
                </a>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════
         PANEL TOGGLE STATUS SURVEI
         ═══════════════════════════════════════════════════════ --}}
    @if(isset($semuaSurvei) && $semuaSurvei->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-700">Status Survei</h2>
            <span class="text-xs text-gray-400">Hanya 1 survei yang dapat aktif dalam satu waktu.</span>
        </div>

        <div class="divide-y divide-gray-50">
            @foreach($semuaSurvei as $s)
            @php
                $badgeClass = match($s->status) {
                    'AKTIF' => 'bg-green-100 text-green-700',
                    'TUTUP' => 'bg-gray-100 text-gray-500',
                    default  => 'bg-yellow-100 text-yellow-700', // DRAFT
                };
            @endphp
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 py-3 first:pt-0 last:pb-0">
                {{-- Info survei --}}
                <div class="flex items-center gap-3 min-w-0">
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold flex-shrink-0 {{ $badgeClass }}">
                        {{ $s->status }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $s->judul }}</p>
                        <p class="text-xs text-gray-400">Periode: {{ $s->periode }}
                            @if($s->ditutup_at)
                                · Tutup: {{ $s->ditutup_at->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Tombol toggle --}}
                <div class="flex-shrink-0">
                    @if($s->status === 'AKTIF')
                        <form method="POST" action="{{ route('admin.survei.tutup', $s->id) }}"
                            onsubmit="return confirm('Tutup survei ini? Popup tidak akan tampil ke ASN & ATASAN.')">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 bg-red-50 text-red-600 border border-red-200 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-red-100 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Tutup Survei
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.survei.aktifkan', $s->id) }}"
                            onsubmit="return confirm('Aktifkan survei ini? Survei lain yang sedang aktif akan otomatis ditutup.')">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 border border-green-200 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-green-100 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Aktifkan Survei
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    {{-- ═══════════════════════════════════════════════════════
         END PANEL TOGGLE
         ═══════════════════════════════════════════════════════ --}}

    @if(!isset($survei) || !$survei)
        {{-- Tidak ada survei aktif --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="w-14 h-14 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-500 font-medium">Belum ada survei aktif.</p>
            <p class="text-sm text-gray-400 mt-1">Aktifkan survei dari panel di atas untuk mulai memantau partisipasi.</p>
        </div>
    @else

        {{-- Info survei aktif --}}
        <div class="bg-blue-50 border border-blue-100 rounded-xl px-5 py-3 mb-5 flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-blue-700">
            <span class="font-semibold">{{ $survei->judul }}</span>
            <span class="text-blue-400">|</span>
            <span>Periode: <strong>{{ $survei->periode }}</strong></span>
            @if($survei->ditutup_at)
                <span class="text-blue-400">|</span>
                <span>Tutup: <strong>{{ $survei->ditutup_at->format('d M Y') }}</strong></span>
            @endif
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Total Pegawai</p>
                <p class="text-3xl font-bold text-gray-800">{{ number_format($totalPegawai) }}</p>
                <p class="text-xs text-gray-400 mt-1">ASN + ATASAN aktif</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Sudah Isi</p>
                <p class="text-3xl font-bold text-green-600">{{ number_format($totalSudah) }}</p>
                <p class="text-xs text-gray-400 mt-1">Telah submit survei</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Belum Isi</p>
                <p class="text-3xl font-bold text-red-500">{{ number_format($totalBelum) }}</p>
                <p class="text-xs text-gray-400 mt-1">Belum submit survei</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Partisipasi</p>
                <p class="text-3xl font-bold {{ $persen >= 80 ? 'text-green-600' : ($persen >= 50 ? 'text-yellow-500' : 'text-red-500') }}">
                    {{ $persen }}%
                </p>
                <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full {{ $persen >= 80 ? 'bg-green-500' : ($persen >= 50 ? 'bg-yellow-400' : 'bg-red-400') }}"
                        style="width: {{ $persen }}%"></div>
                </div>
            </div>
        </div>

        {{-- Rekap Nilai Rata-rata Q1–Q9 --}}
        @if($totalSudah > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Rata-rata Nilai Per Pertanyaan</h2>
            @php
                $skalaLabel = [
                    1 => 'Pendapat Umum',
                    2 => 'Kemudahan Fitur',
                    3 => 'Pemahaman Fitur',
                    4 => 'Kemandirian Akses',
                    5 => 'Manfaat Aplikasi',
                    6 => 'Pemahaman Sasaran',
                    7 => 'Pemahaman Strategi',
                    8 => 'Pemantauan Atasan',
                    9 => 'Distribusi Beban Kerja',
                ];
            @endphp
            <div class="grid grid-cols-3 sm:grid-cols-5 lg:grid-cols-9 gap-3">
                @for($q = 1; $q <= 9; $q++)
                    @php
                        $avg      = $avgRow->{'avg'.$q} ?? 0;
                        $pct      = $avg / 5 * 100;
                        $color    = $avg >= 4 ? 'text-green-600' : ($avg >= 3 ? 'text-yellow-500' : 'text-red-500');
                        $barColor = $avg >= 4 ? 'bg-green-500' : ($avg >= 3 ? 'bg-yellow-400' : 'bg-red-400');
                    @endphp
                    <div class="text-center">
                        <p class="text-xs text-gray-400 mb-1 font-medium">Q{{ $q }}</p>
                        <p class="text-xl font-bold {{ $color }}">{{ number_format($avg, 1) }}</p>
                        <div class="mt-1.5 h-1 bg-gray-100 rounded-full overflow-hidden mx-1">
                            <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $pct }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1 leading-tight hidden lg:block">{{ $skalaLabel[$q] }}</p>
                    </div>
                @endfor
            </div>
            <p class="text-xs text-gray-400 mt-4">Skala: 1 = Paling Rendah · 5 = Paling Tinggi</p>
        </div>
        @endif

        {{-- Tabel per Unit Kerja --}}
        <div
            x-data="{
                search: '',
                modal: false,
                modalUnit: '',
                modalList: [],
                openModal(nama, list) {
                    this.modalUnit = nama;
                    this.modalList = list;
                    this.modal = true;
                }
            }"
            class="bg-white rounded-xl shadow-sm border border-gray-100"
        >
            {{-- Tabel header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-5 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Partisipasi Per Unit Kerja</h2>
                <input
                    type="text"
                    x-model="search"
                    placeholder="Cari unit kerja..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 w-full sm:w-60"
                >
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left w-8">#</th>
                            <th class="px-4 py-3 text-left">Unit Kerja</th>
                            <th class="px-4 py-3 text-center">Total</th>
                            <th class="px-4 py-3 text-center">Sudah</th>
                            <th class="px-4 py-3 text-center">Belum</th>
                            <th class="px-4 py-3 text-center">%</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($tabelUnit as $i => $unit)
                        <tr
                            x-show="search === '' || '{{ strtolower($unit['nama_unit']) }}'.includes(search.toLowerCase())"
                            class="hover:bg-gray-50 transition"
                        >
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $unit['nama_unit'] }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $unit['total'] }}</td>
                            <td class="px-4 py-3 text-center font-semibold text-green-600">{{ $unit['sudah'] }}</td>
                            <td class="px-4 py-3 text-center font-semibold {{ $unit['belum'] > 0 ? 'text-red-500' : 'text-gray-400' }}">
                                {{ $unit['belum'] }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-xs font-semibold {{ $unit['persen'] >= 100 ? 'text-green-600' : ($unit['persen'] >= 50 ? 'text-yellow-500' : 'text-red-500') }}">
                                    {{ $unit['persen'] }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($unit['belum'] === 0)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 px-2 py-1 rounded-full">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Semua sudah
                                    </span>
                                @else
                                    <button
                                        @click="openModal('{{ addslashes($unit['nama_unit']) }}', {{ json_encode($unit['list_belum']) }})"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-red-600 bg-red-50 px-2 py-1 rounded-full hover:bg-red-100 transition"
                                    >
                                        {{ $unit['belum'] }} Belum
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                @if(count($tabelUnit) === 0)
                    <div class="py-10 text-center text-sm text-gray-400">Tidak ada data unit kerja.</div>
                @endif
            </div>

            {{-- Modal ASN belum isi --}}
            <div
                x-show="modal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
                style="display: none;"
                @click.self="modal = false"
                @keydown.escape.window="modal = false"
            >
                <div
                    x-show="modal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col"
                >
                    {{-- Modal header --}}
                    <div class="flex items-center justify-between p-5 border-b border-gray-100">
                        <div>
                            <h3 class="font-semibold text-gray-800 text-sm" x-text="'ASN Belum Isi — ' + modalUnit"></h3>
                            <p class="text-xs text-gray-400 mt-0.5" x-text="modalList.length + ' pegawai belum mengisi survei'"></p>
                        </div>
                        <button @click="modal = false" class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Modal body --}}
                    <div class="overflow-y-auto flex-1 divide-y divide-gray-50">
                        <template x-for="(p, idx) in modalList" :key="idx">
                            <div class="px-5 py-3 flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-red-50 text-red-400 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5"
                                    x-text="idx + 1"></span>
                                <div>
                                    <p class="text-sm font-medium text-gray-800" x-text="p.name"></p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        <span x-text="'NIP: ' + p.nip"></span>
                                        <span class="mx-1">·</span>
                                        <span x-text="p.jabatan"></span>
                                    </p>
                                </div>
                            </div>
                        </template>
                        <template x-if="modalList.length === 0">
                            <div class="py-8 text-center text-sm text-gray-400">Semua sudah mengisi.</div>
                        </template>
                    </div>

                    <div class="p-4 border-t border-gray-100">
                        <button @click="modal = false"
                            class="w-full bg-gray-100 text-gray-700 text-sm font-semibold py-2 rounded-lg hover:bg-gray-200 transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

        </div>
        {{-- end x-data wrapper --}}

        {{-- ═══════════════════════════════════════════════════════
             SECTION: SARAN & MASUKAN ASN
             ═══════════════════════════════════════════════════════ --}}
        @php
            // Siapkan haystack untuk client-side search — di PHP, bukan inline JS
            $saranHaystack = isset($saranTerbaru)
                ? $saranTerbaru->map(fn($s) => strtolower(
                    ($s->nama_asn ?? '') . ' ' .
                    ($s->nama_unit ?? '') . ' ' .
                    ($s->saran ?? '')
                ))->values()->toArray()
                : [];
        @endphp
        <div class="mt-6" x-data="{
            saranSearch: '',
            haystack: {{ json_encode($saranHaystack) }},
            get hasMatch() {
                if (this.saranSearch === '') return true;
                return this.haystack.some(h => h.includes(this.saranSearch.toLowerCase()));
            }
        }">

            {{-- Section header --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Saran & Masukan ASN</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Menampilkan 30 saran terbaru.</p>
                </div>
                <input
                    type="text"
                    x-model="saranSearch"
                    placeholder="Cari nama, unit kerja, atau isi saran..."
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 w-full sm:w-72"
                >
            </div>

            @if(isset($saranTerbaru) && $saranTerbaru->isNotEmpty())

                {{-- Card list --}}
                <div class="space-y-3">
                    @foreach($saranTerbaru as $idx => $s)
                    @php
                        $namaAsn  = $s->nama_asn   ?? '-';
                        $namaUnit = $s->nama_unit   ?? 'Tanpa Unit Kerja';
                        $tgl      = $s->submitted_at
                            ? \Carbon\Carbon::parse($s->submitted_at)->locale('id')->isoFormat('D MMM YYYY, HH:mm')
                            : '-';
                        $isiSaran = $s->saran ?? '';
                    @endphp
                    <div
                        x-show="saranSearch === '' || haystack[{{ $idx }}].includes(saranSearch.toLowerCase())"
                        class="bg-white rounded-xl shadow-sm border border-gray-100 p-5"
                        x-data="{ expanded: false }"
                    >
                        {{-- Meta: nama, unit, tanggal --}}
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-3">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="w-6 h-6 rounded-full bg-blue-50 text-blue-500 text-xs font-bold flex items-center justify-center flex-shrink-0">
                                    {{ $idx + 1 }}
                                </span>
                                <span class="text-sm font-semibold text-gray-800">{{ $namaAsn }}</span>
                                @if($s->nip)
                                    <span class="text-xs text-gray-400">· {{ $s->nip }}</span>
                                @endif
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
                                    {{ $namaUnit }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-400 flex-shrink-0">{{ $tgl }}</span>
                        </div>

                        {{-- Isi saran — line-clamp default 3 baris --}}
                        <div
                            class="text-sm text-gray-600 leading-relaxed whitespace-pre-line"
                            :class="expanded ? '' : 'line-clamp-3'"
                        >{{ $isiSaran }}</div>

                        {{-- Toggle expand --}}
                        @if(mb_strlen($isiSaran) > 200)
                        <button
                            @click="expanded = !expanded"
                            class="mt-2 text-xs text-blue-500 hover:text-blue-700 font-medium transition"
                            x-text="expanded ? 'Sembunyikan ▲' : 'Lihat selengkapnya ▼'"
                        ></button>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Pesan jika search tidak menemukan hasil --}}
                <div
                    x-show="saranSearch !== '' && !hasMatch"
                    class="text-center py-8 text-sm text-gray-400"
                    style="display: none;"
                >
                    Tidak ada saran yang cocok dengan pencarian.
                </div>

            @else
                {{-- Empty state --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    <p class="text-gray-400 font-medium text-sm">Belum ada saran & masukan.</p>
                    <p class="text-xs text-gray-300 mt-1">Saran akan tampil di sini setelah ASN mengisi survei.</p>
                </div>
            @endif

        </div>
        {{-- end section saran --}}

    @endif

</div>
@endsection
