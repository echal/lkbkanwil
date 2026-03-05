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

        /* ── Rencana Aksi ────────────────────────────────── */
        .rencana-box {
            margin-bottom: 12px;
            padding: 8px 10px;
            border: 1px solid #ccc;
            background: #fffbf0;
        }
        .rencana-box h4 { font-size: 8.5pt; font-weight: bold; margin-bottom: 4px; }
        .rencana-item {
            font-size: 7.5pt;
            margin-bottom: 3px;
            padding: 3px 4px;
            border-left: 3px solid #007bff;
            background: #fff;
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
        .signature-section { margin-top: 24px; }
        .signature-box { float: right; width: 200px; text-align: center; font-size: 9pt; }
        .signature-space { height: 55px; }
        .signature-name { font-weight: bold; text-decoration: underline; }

        /* ── Footer ──────────────────────────────────────── */
        .footer {
            clear: both;
            margin-top: 50px;
            text-align: center;
            font-size: 7.5pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <h1>KEMENTERIAN AGAMA REPUBLIK INDONESIA</h1>
        <h2>KANWIL KEMENTERIAN AGAMA PROVINSI SULAWESI BARAT</h2>
        <h3>REKAP KINERJA ASN BULANAN</h3>
        <p class="periode">Periode: {{ $periode }}</p>
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
            <div class="rencana-item">
                <strong>{{ $rencana->skpTahunanDetail->indikatorKinerja->nama_indikator ?? '-' }}</strong><br>
                {{ $rencana->rencana_aksi_bulanan }}
                <br><em style="color:#666;">Target: {{ $rencana->target_bulanan }} {{ $rencana->satuan_target }}</em>
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
                        $pct = $summary['total_hari'] > 0
                            ? number_format(($summary['hari_green'] / $summary['total_hari']) * 100, 1)
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
        </table>
    </div>

    {{-- Tanda Tangan --}}
    <div class="signature-section">
        <div class="signature-box">
            <p style="margin-bottom:4px;">{{ now()->locale('id')->isoFormat('D MMMM Y') }}</p>
            <p style="font-weight:bold;margin-bottom:4px;">Yang Melaporkan,</p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ $asn->name }}</p>
            <p style="font-size:8.5pt;">NIP. {{ $asn->nip ?? '-' }}</p>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>Dicetak melalui Sistem Informasi Kinerja ASN — esaraku</p>
        <p>Kanwil Kementerian Agama Provinsi Sulawesi Barat</p>
        <p>Tanggal Cetak: {{ $tanggal_cetak }}</p>
    </div>

</body>
</html>
