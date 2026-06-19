<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Kinerja Bulanan - {{ $periode }}</title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            padding: 18px 22px;
        }

        /* ── Header ─────────────────────────────────────── */
        .header {
            text-align: center;
            margin-bottom: 16px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
        }
        .header h1 { font-size: 12pt; font-weight: bold; text-transform: uppercase; margin-bottom: 1px; }
        .header h2 { font-size: 11pt; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; }
        .header h3 { font-size: 10.5pt; font-weight: bold; text-decoration: underline; margin-bottom: 4px; }
        .header .periode { font-size: 10pt; font-weight: normal; }

        /* ── Info ASN ───────────────────────────────────── */
        .info-box {
            margin-bottom: 12px;
            border: 1px solid #000;
            padding: 8px 10px;
            background: #f9f9f9;
        }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 2px 6px; font-size: 9pt; }
        .info-table td:first-child { width: 110px; font-weight: bold; }
        .info-table td:nth-child(2) { width: 10px; }

        /* ── Section title ──────────────────────────────── */
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 6px;
            padding: 4px 10px;
            background: #333;
            color: #fff;
            text-transform: uppercase;
        }

        /* ── Tabel utama ─────────────────────────────────── */
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 8pt;
        }
        .content-table th,
        .content-table td {
            border: 1px solid #555;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
        }
        .content-table th {
            background: #e0e0e0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7pt;
            line-height: 1.2;
        }

        /* ── Status cell colors ──────────────────────────── */
        .s-green  { background: #d4edda; font-weight: bold; font-size: 7pt; }
        .s-yellow { background: #fff3cd; font-weight: bold; font-size: 7pt; }
        .s-red    { background: #f8d7da; font-weight: bold; font-size: 7pt; }
        .s-libur  { background: #e2e8f0; color: #64748b; font-size: 7pt; }
        .s-empty  { background: #f5f5f5; color: #aaa; font-size: 7pt; }
        .s-cuti   { background: #ffedd5; color: #c2410c; font-weight: bold; font-size: 7pt; }

        /* ── Rencana Aksi ────────────────────────────────── */
        .rencana-box {
            margin-bottom: 12px;
            padding: 8px 10px;
            border: 1px solid #ccc;
            background: #fffbf0;
        }
        .rencana-box h4 { font-size: 8.5pt; font-weight: bold; margin-bottom: 6px; }
        .rencana-item {
            font-size: 7.5pt;
            margin-bottom: 8px;
            padding: 6px 8px;
            border-left: 3px solid #007bff;
            background: #fff;
            page-break-inside: avoid;
        }
        .rencana-level-value { font-size: 8pt; font-weight: bold; margin-bottom: 1px; }
        .rencana-level-label { font-size: 7pt; color: #555; font-style: italic; margin-bottom: 5px; }
        .rencana-angka {
            font-size: 7.5pt;
            margin-top: 5px;
            padding-top: 4px;
            border-top: 1px dashed #ccc;
            line-height: 1.7;
        }

        /* ── Summary ─────────────────────────────────────── */
        .summary-box {
            margin-top: 12px;
            padding: 10px;
            border: 2px solid #000;
            background: #f5f5f5;
        }
        .summary-box h4 {
            font-size: 9.5pt;
            font-weight: bold;
            margin-bottom: 6px;
            text-decoration: underline;
        }
        .summary-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        .summary-table td { padding: 3px 6px; border-bottom: 1px dashed #ccc; }
        .summary-table td:first-child { width: 38%; font-weight: bold; }
        .summary-table td:nth-child(2) { width: 10px; }

        /* ── Tanda tangan ────────────────────────────────── */
        .signature-section { margin-top: 24px; overflow: hidden; }
        .signature-left  { float: left;  width: 220px; text-align: center; font-size: 9pt; }
        .signature-right { float: right; width: 220px; text-align: center; font-size: 9pt; }
        .signature-space { height: 55px; }
        .signature-name  { font-weight: bold; text-decoration: underline; }
        .signature-clear { clear: both; }

        /* ── Footer ──────────────────────────────────────── */
        .footer {
            clear: both;
            margin-top: 12px;
            text-align: center;
            font-size: 7pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 5px;
            line-height: 1.3;
            page-break-inside: avoid;
        }

        /* ── Halaman detail KH/TLA ───────────────────────── */
        .page-break { page-break-before: always; }

        .detail-header {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 12px;
            padding: 6px;
            background: #333;
            color: #fff;
        }

        .hari-detail {
            page-break-inside: avoid;
            border: 1px solid #dcdcdc;
            padding: 8px 10px;
            margin-bottom: 10px;
            background: #fff;
        }

        .hari-tanggal {
            font-size: 9.5pt;
            font-weight: bold;
            border-bottom: 1px solid #999;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .item-kegiatan {
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 1px dashed #ddd;
            font-size: 8pt;
        }

        .item-kegiatan:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

        .jam-kegiatan {
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
        }

        .badge-kh {
            display: inline-block;
            padding: 1px 6px;
            background: #cce5ff;
            color: #004085;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 2px;
            margin-right: 4px;
        }

        .badge-tla {
            display: inline-block;
            padding: 1px 6px;
            background: #fff3cd;
            color: #856404;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 2px;
            margin-right: 4px;
        }

        .uraian-kegiatan {
            margin: 3px 0 3px 0;
            font-size: 8.5pt;
            line-height: 1.4;
        }

        .indikator-detail {
            font-size: 7.5pt;
            color: #555;
            font-style: italic;
            margin-bottom: 2px;
        }

        .meta-kegiatan {
            font-size: 7.5pt;
            color: #444;
            margin-top: 3px;
        }

        .meta-kegiatan span { margin-right: 10px; }

        .keterangan-kegiatan {
            font-size: 7.5pt;
            color: #666;
            font-style: italic;
            margin-top: 2px;
        }

        .kosong-hari {
            font-size: 8pt;
            color: #aaa;
            font-style: italic;
            padding: 4px 0;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <table style="border-collapse:collapse;margin:0 auto 6px auto;">
            <tr>
                <td valign="middle" style="padding-right:10px;">
                    <img src="{{ public_path('images/logo/logo-kemenag.png') }}"
                         width="90" alt="Logo Kemenag"
                         style="display:block;">
                </td>
                <td valign="middle" align="center">
                    <h1>KEMENTERIAN AGAMA REPUBLIK INDONESIA</h1>
                    <h2>KANWIL KEMENTERIAN AGAMA PROVINSI SULAWESI BARAT</h2>
                    <h3>REKAP KINERJA ASN BULANAN</h3>
                    <p class="periode">Periode: {{ $periode }}</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- Informasi ASN --}}
    <div class="info-box">
        <table class="info-table">
            <tr>
                <td>Nama ASN</td><td>:</td><td>{{ $asn->name }}</td>
                <td style="width:110px;font-weight:bold;">NIP</td><td style="width:10px;">:</td>
                <td>{{ $asn->nip ?? '-' }}</td>
            </tr>
            <tr>
                <td>Jabatan</td><td>:</td><td>{{ $asn->jabatan ?? '-' }}</td>
                <td style="font-weight:bold;">Unit Kerja</td><td>:</td>
                <td>{{ $asn->unitKerja->nama_unit ?? '-' }}</td>
            </tr>
        </table>
    </div>

    {{-- Rencana Aksi Bulanan --}}
    @if($rencanaAksi->isNotEmpty())
    <div class="rencana-box">
        <h4>RENCANA AKSI BULAN {{ strtoupper($periode) }}</h4>
        @foreach($rencanaAksi as $rencana)
        @php
            $rhkPimpinan = $rencana->skpTahunanDetail->indikatorKinerja?->nama_indikator_bersih ?? '-';
            $skpTahunan  = $rencana->skpTahunanDetail->rencana_aksi ?? null;
            $rhkBulanan  = $rencana->rencana_aksi_bulanan ?? '-';
            $target      = $rencana->target_bulanan;
            $realisasi   = $rencana->realisasi_bulanan;
            $satuan      = $rencana->satuan_target ?? '';
            $capaian     = $target > 0 ? number_format(($realisasi / $target) * 100, 2) : '0.00';
        @endphp
        <div class="rencana-item">

            {{-- Level 1: RHK Pimpinan --}}
            <div class="rencana-level-value">{{ $rhkPimpinan }}</div>
            <div class="rencana-level-label">(RHK Pimpinan Yang Diintervensi)</div>

            {{-- Level 2: SKP Tahunan ASN --}}
            @if($skpTahunan)
            <div class="rencana-level-value">{{ $skpTahunan }}</div>
            <div class="rencana-level-label">(Rencana Hasil Kerja Tahunan)</div>
            @endif

            {{-- Level 3: RHK Bulanan ASN --}}
            <div class="rencana-level-value">{{ $rhkBulanan }}</div>
            <div class="rencana-level-label">(Rencana Aksi Bulanan ASN)</div>

            {{-- Target / Realisasi / Capaian --}}
            <div class="rencana-angka">
                Target &nbsp;&nbsp;&nbsp;: {{ number_format($target, 0) }} {{ $satuan }}<br>
                Realisasi : {{ number_format($realisasi, 0) }} {{ $satuan }}<br>
                Capaian &nbsp;&nbsp;: {{ $capaian }} %
            </div>

        </div>
        @endforeach
    </div>
    @endif

    {{-- Tabel Rekap Per Hari --}}
    <div class="section-title">Rekap Kinerja Per Hari</div>

    <table class="content-table">
        <thead>
            <tr>
                <th style="width:30px;">TGL</th>
                <th style="width:34px;">HARI</th>
                <th style="width:62px;">TOTAL JAM</th>
                <th style="width:38px;">KH</th>
                <th style="width:38px;">TLA</th>
                <th style="width:72px;">STATUS</th>
                <th style="width:30px;">TGL</th>
                <th style="width:34px;">HARI</th>
                <th style="width:62px;">TOTAL JAM</th>
                <th style="width:38px;">KH</th>
                <th style="width:38px;">TLA</th>
                <th style="width:72px;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @php $chunks = array_chunk($rekapHarian, 2); @endphp
            @foreach($chunks as $chunk)
            <tr>
                @foreach($chunk as $hari)
                    @php
                        $sc = $hari['status_code'];
                        $tdClass = match($sc) {
                            'GREEN'  => 's-green',
                            'YELLOW' => 's-yellow',
                            'RED'    => 's-red',
                            'LIBUR'  => 's-libur',
                            'CUTI'   => 's-cuti',
                            default  => 's-empty',
                        };
                    @endphp
                    <td>{{ $hari['tanggal'] }}</td>
                    <td>{{ $hari['hari'] }}</td>
                    <td>{{ $hari['total_jam'] }}</td>
                    <td>{{ $hari['kh'] > 0 ? $hari['kh'] : '-' }}</td>
                    <td>{{ $hari['tla'] > 0 ? $hari['tla'] : '-' }}</td>
                    <td class="{{ $tdClass }}">{{ $hari['status'] }}</td>
                @endforeach
                @if(count($chunk) < 2)
                    <td colspan="6" class="s-empty">-</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Ringkasan Bulanan --}}
    <div class="summary-box">
        <h4>RINGKASAN KINERJA BULAN {{ strtoupper($periode) }}</h4>
        <table class="summary-table">
            <tr>
                <td>Total Hari dalam Bulan</td><td>:</td>
                <td>{{ $summary['total_hari'] }} hari</td>
                <td style="width:36%;font-weight:bold;">Total Kinerja Harian (KH)</td><td style="width:10px;">:</td>
                <td>{{ $summary['total_kh'] }} kegiatan</td>
            </tr>
            <tr>
                <td>Hari Kerja (Ada Input)</td><td>:</td>
                <td>{{ $summary['hari_kerja'] }} hari</td>
                <td style="font-weight:bold;">Total Tugas Langsung Atasan (TLA)</td><td>:</td>
                <td>{{ $summary['total_tla'] }} kegiatan</td>
            </tr>
            <tr>
                <td>Hari Cuti</td><td>:</td>
                <td style="color:#c2410c;font-weight:bold;">{{ $summary['hari_cuti'] ?? 0 }} hari</td>
                <td style="font-weight:bold;"></td><td></td><td></td>
            </tr>
            <tr>
                <td>Hari Kosong (Tidak Ada Input)</td><td>:</td>
                <td>{{ $summary['hari_kosong'] }} hari</td>
                <td style="font-weight:bold;">Total Durasi Kerja Bulan Ini</td><td>:</td>
                <td>{{ $summary['total_jam'] }}</td>
            </tr>
            <tr>
                <td>Hari LENGKAP (≥ 7.5 jam + bukti)</td><td>:</td>
                <td style="color:#155724;font-weight:bold;">{{ $summary['hari_green'] }} hari</td>
                <td style="font-weight:bold;">Rata-rata Jam Kerja per Hari</td><td>:</td>
                <td>{{ $summary['avg_jam'] }} jam</td>
            </tr>
            <tr>
                <td>Hari KURANG (&lt; 7.5 jam)</td><td>:</td>
                <td style="color:#856404;font-weight:bold;">{{ $summary['hari_yellow'] }} hari</td>
                <td style="font-weight:bold;">Tingkat Kepatuhan</td><td>:</td>
                <td>
                    @php
                        $hariMasuk       = $summary['hari_green'] + $summary['hari_yellow'] + $summary['hari_red'];
                        $hariKerjaAktual = $summary['hari_kerja'];
                        $pct             = $hariKerjaAktual > 0
                            ? number_format(($hariMasuk / $hariKerjaAktual) * 100, 1)
                            : '0.0';
                    @endphp
                    {{ $pct }}%
                </td>
            </tr>
            <tr>
                <td>Hari BELUM UPLOAD BUKTI</td><td>:</td>
                <td style="color:#721c24;font-weight:bold;">{{ $summary['hari_red'] }} hari</td>
                <td></td><td></td><td></td>
            </tr>
            @php
                $allItems     = collect($detailHarian ?? collect())->flatten(1);
                $vSesuai      = $allItems->where('verifikasi_eviden', 'SESUAI')->count();
                $vKurang      = $allItems->where('verifikasi_eviden', 'KURANG')->count();
                $vTidakSesuai = $allItems->where('verifikasi_eviden', 'TIDAK_SESUAI')->count();
                // Link Kosong: bukti tidak ada (display only, bukan dari DB)
                $vLinkKosong  = $allItems->filter(fn($i) =>
                    empty($i->bukti_dukung) || ($i->status_bukti ?? '') !== 'SUDAH_ADA'
                )->count();
                // Belum Diverifikasi: ada bukti tapi verifikasi_eviden masih NULL
                $vBelum       = $allItems->filter(fn($i) =>
                    !empty($i->bukti_dukung) &&
                    ($i->status_bukti ?? '') === 'SUDAH_ADA' &&
                    empty($i->verifikasi_eviden)
                )->count();
                $adaData = $allItems->count() > 0;
            @endphp
            @if($adaData)
            <tr>
                <td style="font-weight:bold;color:#374151;">Verifikasi Eviden Atasan</td><td>:</td>
                <td colspan="4" style="font-size:8pt;">
                    <span style="color:#15803d;font-weight:bold;">Sesuai: {{ $vSesuai }}</span>
                    &nbsp;|&nbsp;
                    <span style="color:#92400e;font-weight:bold;">Kurang: {{ $vKurang }}</span>
                    &nbsp;|&nbsp;
                    <span style="color:#991b1b;font-weight:bold;">Tidak Sesuai: {{ $vTidakSesuai }}</span>
                    &nbsp;|&nbsp;
                    <span style="color:#6b21a8;font-weight:bold;">Link Kosong: {{ $vLinkKosong }}</span>
                    &nbsp;|&nbsp;
                    <span style="color:#6b7280;">Belum Diverifikasi: {{ $vBelum }}</span>
                </td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Tanda Tangan 2 Kolom --}}
    @php $atasan = $atasan ?? $asn->atasan ?? null; @endphp
    <div class="signature-section">
        {{-- Kiri: Atasan Langsung --}}
        <div class="signature-left">
            <p style="margin-bottom:4px;">Mengetahui,</p>
            <p style="font-weight:bold;margin-bottom:4px;">{{ $atasan?->jabatan ?? 'Atasan Langsung' }}</p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ $atasan?->name ?? '______________________' }}</p>
            <p style="font-size:8.5pt;">NIP. {{ $atasan?->nip ?? '-' }}</p>
        </div>
        {{-- Kanan: ASN Pelapor --}}
        <div class="signature-right">
            <p style="margin-bottom:4px;">{{ now()->locale('id')->isoFormat('D MMMM Y') }}</p>
            <p style="font-weight:bold;margin-bottom:4px;">Yang Melaporkan,</p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ $asn->name }}</p>
            <p style="font-size:8.5pt;">NIP. {{ $asn->nip ?? '-' }}</p>
        </div>
        <div class="signature-clear"></div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>Dicetak melalui Sistem Informasi Kinerja ASN — esaraku</p>
        <p>Kanwil Kementerian Agama Provinsi Sulawesi Barat</p>
        <p>Tanggal Cetak: {{ $tanggal_cetak }}</p>
    </div>

    {{-- ================================================================ --}}
    {{-- HALAMAN 2+: RINCIAN DETAIL KH & TLA PER HARI                    --}}
    {{-- ================================================================ --}}
    @php $detailHarian = $detailHarian ?? collect(); @endphp
    @if($detailHarian->isNotEmpty())
    <div class="page-break"></div>

    {{-- Sub-header identitas (ringkas, tanpa kop ulang) --}}
    <div class="detail-header">
        Rincian Kegiatan Harian — {{ $asn->name }} — {{ $periode }}
    </div>

    @foreach($detailHarian as $tglKey => $itemList)
        @php
            $tglObj   = \Carbon\Carbon::parse($tglKey);
            $tglLabel = $tglObj->locale('id')->isoFormat('dddd, D MMMM Y');
            $totalMenitHari = $itemList->sum('durasi_menit');
            $jamHari  = intdiv($totalMenitHari, 60);
            $menitHari= $totalMenitHari % 60;
            $durasiHari = $menitHari > 0 ? "{$jamHari}j {$menitHari}m" : ($jamHari > 0 ? "{$jamHari}j" : '-');
        @endphp

        <div class="hari-detail">
            <div class="hari-tanggal">
                {{ $tglLabel }}
                <span style="float:right;font-size:8pt;font-weight:normal;color:#555;">
                    Total: {{ $durasiHari }} &nbsp;|&nbsp;
                    KH: {{ $itemList->where('tipe_progres','KINERJA_HARIAN')->count() }} &nbsp;|&nbsp;
                    TLA: {{ $itemList->where('tipe_progres','TUGAS_ATASAN')->count() }}
                </span>
                <div style="clear:both;"></div>
            </div>

            @foreach($itemList as $item)
            @php
                $jamMulai  = $item->jam_mulai  ? \Carbon\Carbon::parse($item->jam_mulai)->format('H:i')  : '-';
                $jamSelesai= $item->jam_selesai ? \Carbon\Carbon::parse($item->jam_selesai)->format('H:i'): '-';
                $dur       = $item->durasi_menit ?? 0;
                $durJam    = intdiv($dur, 60);
                $durMenit  = $dur % 60;
                $durLabel  = $durMenit > 0 ? "{$durJam}j {$durMenit}m" : ($durJam > 0 ? "{$durJam}j" : '-');
                $isKH      = $item->tipe_progres === 'KINERJA_HARIAN';
                $uraian    = $isKH ? ($item->rencana_kegiatan_harian ?? '-') : ($item->tugas_atasan ?? '-');
                $indikator = $isKH ? ($item->rencanaAksiBulanan?->skpTahunanDetail?->indikatorKinerja?->nama_indikator_bersih ?? null) : null;
            @endphp
            <div class="item-kegiatan">
                <div class="jam-kegiatan">
                    {{ $jamMulai }} – {{ $jamSelesai }}
                    @if($isKH)
                        <span class="badge-kh">KINERJA HARIAN</span>
                    @else
                        <span class="badge-tla">TUGAS ATASAN</span>
                    @endif
                </div>

                @if($indikator)
                <div class="indikator-detail">Indikator: {{ $indikator }}</div>
                @endif

                <div class="uraian-kegiatan">{{ $uraian }}</div>

                <div class="meta-kegiatan">
                    <span>Durasi: {{ $durLabel }}</span>
                    @if($item->progres && $item->satuan)
                    <span>Progres: {{ $item->progres }} {{ $item->satuan }}</span>
                    @endif
                    <span>Bukti: {{ $item->status_bukti === 'SUDAH_ADA' ? '✓ Ada' : '– Belum' }}</span>
                </div>

                @if($item->keterangan)
                <div class="keterangan-kegiatan">Ket: {{ $item->keterangan }}</div>
                @endif

                {{-- Verifikasi Eviden oleh Atasan --}}
                @php $itemAdaBukti = ($item->status_bukti ?? '') === 'SUDAH_ADA' && !empty($item->bukti_dukung); @endphp
                @if(!$itemAdaBukti)
                    {{-- Link Kosong: display only, tidak menyentuh DB --}}
                    <div style="margin-top:4px;font-size:7pt;">
                        <span style="padding:1px 6px;border-radius:3px;font-weight:bold;background:#f3e8ff;color:#6b21a8;">LINK KOSONG</span>
                    </div>
                @elseif(!empty($item->verifikasi_eviden))
                @php
                    $vStyle = match($item->verifikasi_eviden) {
                        'SESUAI'       => 'background:#d1fae5;color:#065f46;',
                        'KURANG'       => 'background:#fef3c7;color:#92400e;',
                        'TIDAK_SESUAI' => 'background:#fee2e2;color:#991b1b;',
                        default        => 'background:#f3f4f6;color:#6b7280;',
                    };
                    $vLabel = match($item->verifikasi_eviden) {
                        'SESUAI'       => 'SESUAI',
                        'KURANG'       => 'KURANG',
                        'TIDAK_SESUAI' => 'TIDAK SESUAI',
                        default        => $item->verifikasi_eviden,
                    };
                @endphp
                <div style="margin-top:4px;font-size:7pt;">
                    <span style="padding:1px 6px;border-radius:3px;font-weight:bold;{{ $vStyle }}">{{ $vLabel }}</span>
                    @if(!empty($item->catatan_verifikasi))
                    <span style="color:#555;margin-left:6px;font-style:italic;">{{ $item->catatan_verifikasi }}</span>
                    @endif
                </div>
                @else
                    {{-- Ada bukti, belum diverifikasi --}}
                    <div style="margin-top:4px;font-size:7pt;">
                        <span style="padding:1px 6px;border-radius:3px;background:#f3f4f6;color:#6b7280;">BELUM DIVERIFIKASI</span>
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    @endforeach

    {{-- Footer halaman detail --}}
    <div class="footer">
        <p>Dicetak melalui Sistem Informasi Kinerja ASN — esaraku &nbsp;|&nbsp; {{ $tanggal_cetak }}</p>
    </div>
    @endif

</body>
</html>
