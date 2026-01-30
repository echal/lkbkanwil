<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Kinerja Bulanan - {{ $periode }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
            padding: 20px 25px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
        }

        .header h1 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }

        .header h2 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .header h3 {
            font-size: 11pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 6px;
        }

        .header .periode {
            font-size: 10pt;
            font-weight: normal;
        }

        .info-box {
            margin-bottom: 15px;
            border: 1px solid #000;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 8px;
            font-size: 9pt;
        }

        .info-table td:first-child {
            width: 120px;
            font-weight: bold;
        }

        .info-table td:nth-child(2) {
            width: 12px;
        }

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 8px;
            padding: 5px 10px;
            background-color: #333;
            color: #fff;
            text-transform: uppercase;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8pt;
        }

        .content-table th,
        .content-table td {
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: center;
        }

        .content-table th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7pt;
            line-height: 1.2;
        }

        .content-table td.center {
            text-align: center;
        }

        .content-table td.left {
            text-align: left;
        }

        /* Status colors */
        .status-green {
            background-color: #d4edda;
            font-weight: bold;
        }

        .status-yellow {
            background-color: #fff3cd;
            font-weight: bold;
        }

        .status-red {
            background-color: #f8d7da;
            font-weight: bold;
        }

        .status-empty {
            background-color: #f5f5f5;
            color: #999;
        }

        .rencana-box {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #fffbf0;
        }

        .rencana-box h4 {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .rencana-item {
            font-size: 8pt;
            margin-bottom: 4px;
            padding: 4px;
            border-left: 3px solid #007bff;
            background-color: #fff;
        }

        .summary-box {
            margin-top: 15px;
            padding: 12px;
            border: 2px solid #000;
            background-color: #f5f5f5;
        }

        .summary-box h4 {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 8px;
            text-decoration: underline;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-row {
            display: table-row;
        }

        .summary-cell {
            display: table-cell;
            padding: 4px 8px;
            font-size: 9pt;
            border-bottom: 1px dashed #ccc;
        }

        .summary-cell:first-child {
            width: 40%;
            font-weight: bold;
        }

        .summary-cell:nth-child(2) {
            width: 10px;
        }

        .signature-section {
            margin-top: 30px;
        }

        .signature-box {
            float: right;
            width: 220px;
            text-align: center;
        }

        .signature-space {
            height: 60px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .footer {
            clear: both;
            margin-top: 60px;
            text-align: center;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <!-- Header Dokumen -->
    <div class="header">
        <h1>KEMENTERIAN AGAMA REPUBLIK INDONESIA</h1>
        <h2>KANWIL KEMENTERIAN AGAMA PROVINSI SULAWESI BARAT</h2>
        <h3>REKAP KINERJA ASN BULANAN</h3>
        <p class="periode">Periode: {{ $periode }}</p>
    </div>

    <!-- Informasi ASN -->
    <div class="info-box">
        <table class="info-table">
            <tr>
                <td>Nama ASN</td>
                <td>:</td>
                <td>{{ $asn->name }}</td>
                <td style="width: 120px; font-weight: bold;">NIP</td>
                <td style="width: 12px;">:</td>
                <td>{{ $asn->nip ?? '-' }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>:</td>
                <td>{{ $asn->jabatan ?? '-' }}</td>
                <td style="font-weight: bold;">Unit Kerja</td>
                <td>:</td>
                <td>{{ $asn->unitKerja->nama_unit ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <!-- Rencana Aksi Bulanan (Context) -->
    @if($rencanaAksi->isNotEmpty())
    <div class="rencana-box">
        <h4>RENCANA AKSI BULAN {{ strtoupper($periode) }}</h4>
        @foreach($rencanaAksi as $rencana)
            <div class="rencana-item">
                <strong>{{ $rencana->skpTahunanDetail->indikatorKinerja->nama_indikator ?? '-' }}</strong>
                <br>
                {{ $rencana->rencana_aksi_bulanan }}
                <br>
                <em style="color: #666;">Target: {{ $rencana->target_bulanan }} {{ $rencana->satuan_target }}</em>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Tabel Rekap Harian -->
    <div class="section-title">Rekap Kinerja Per Hari</div>

    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 35px;">TGL</th>
                <th style="width: 40px;">HARI</th>
                <th style="width: 70px;">TOTAL<br>JAM</th>
                <th style="width: 45px;">KH</th>
                <th style="width: 45px;">TLA</th>
                <th style="width: 80px;">STATUS</th>
                <th style="width: 35px;">TGL</th>
                <th style="width: 40px;">HARI</th>
                <th style="width: 70px;">TOTAL<br>JAM</th>
                <th style="width: 45px;">KH</th>
                <th style="width: 45px;">TLA</th>
                <th style="width: 80px;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @php
                $chunks = array_chunk($rekapPerHari, 2);
            @endphp
            @foreach($chunks as $chunk)
            <tr>
                @foreach($chunk as $hari)
                    <td class="center">{{ $hari['tanggal'] }}</td>
                    <td class="center">{{ $hari['hari'] }}</td>
                    <td class="center">{{ $hari['total_jam'] }}</td>
                    <td class="center">{{ $hari['count_kh'] }}</td>
                    <td class="center">{{ $hari['count_tla'] }}</td>
                    <td class="status-{{ strtolower($hari['status']) }}">
                        @if($hari['status'] === 'GREEN')
                            LENGKAP
                        @elseif($hari['status'] === 'YELLOW')
                            < 7.5 JAM
                        @elseif($hari['status'] === 'RED')
                            BELUM BUKTI
                        @else
                            -
                        @endif
                    </td>
                @endforeach
                @if(count($chunk) < 2)
                    <td colspan="6" class="status-empty">-</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Ringkasan Bulanan -->
    <div class="summary-box">
        <h4>RINGKASAN KINERJA BULAN {{ strtoupper($periode) }}</h4>
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-cell">Total Hari dalam Bulan</div>
                <div class="summary-cell">:</div>
                <div class="summary-cell">{{ $summary['total_hari'] }} hari</div>
                <div class="summary-cell">Total Kinerja Harian (KH)</div>
                <div class="summary-cell">:</div>
                <div class="summary-cell">{{ $summary['total_kh'] }} kegiatan</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Hari Kerja (Ada Input)</div>
                <div class="summary-cell">:</div>
                <div class="summary-cell">{{ $summary['hari_kerja'] }} hari</div>
                <div class="summary-cell">Total Tugas Langsung Atasan (TLA)</div>
                <div class="summary-cell">:</div>
                <div class="summary-cell">{{ $summary['total_tla'] }} kegiatan</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Hari Kosong (Tidak Ada Input)</div>
                <div class="summary-cell">:</div>
                <div class="summary-cell">{{ $summary['hari_kosong'] }} hari</div>
                <div class="summary-cell">Total Durasi Kerja Bulan Ini</div>
                <div class="summary-cell">:</div>
                <div class="summary-cell">{{ $summary['total_jam'] }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Hari LENGKAP (≥ 7.5 jam + bukti)</div>
                <div class="summary-cell">:</div>
                <div class="summary-cell" style="color: #155724; font-weight: bold;">{{ $summary['hari_green'] }} hari</div>
                <div class="summary-cell">Rata-rata Jam Kerja per Hari</div>
                <div class="summary-cell">:</div>
                <div class="summary-cell">{{ number_format($summary['avg_jam_per_hari'], 1) }} jam</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Hari KURANG (< 7.5 jam)</div>
                <div class="summary-cell">:</div>
                <div class="summary-cell" style="color: #856404; font-weight: bold;">{{ $summary['hari_yellow'] }} hari</div>
                <div class="summary-cell">Tingkat Kepatuhan</div>
                <div class="summary-cell">:</div>
                <div class="summary-cell">
                    @php
                        $kepatuhan = $summary['total_hari'] > 0 ? ($summary['hari_green'] / $summary['total_hari']) * 100 : 0;
                    @endphp
                    {{ number_format($kepatuhan, 1) }}%
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-cell">Hari BELUM UPLOAD BUKTI</div>
                <div class="summary-cell">:</div>
                <div class="summary-cell" style="color: #721c24; font-weight: bold;">{{ $summary['hari_red'] }} hari</div>
                <div class="summary-cell"></div>
                <div class="summary-cell"></div>
                <div class="summary-cell"></div>
            </div>
        </div>
    </div>

    <!-- Tanda Tangan -->
    <div class="signature-section">
        <div class="signature-box">
            <p style="margin-bottom: 5px;">{{ now()->locale('id')->isoFormat('D MMMM Y') }}</p>
            <p style="font-weight: bold; margin-bottom: 5px;">Yang Melaporkan,</p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ $asn->name }}</p>
            <p style="font-size: 9pt;">NIP. {{ $asn->nip }}</p>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Dicetak melalui Sistem Informasi Kinerja ASN</p>
        <p>Kanwil Kementerian Agama Provinsi Sulawesi Barat</p>
        <p>Tanggal Cetak: {{ $tanggal_cetak }}</p>
    </div>

</body>
</html>
