<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Kinerja Mingguan - {{ $periode }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            padding: 20px 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }

        .header h2 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .header h3 {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 8px;
        }

        .header .periode {
            font-size: 11pt;
            font-weight: normal;
            margin-bottom: 5px;
        }

        .header .unit-kerja {
            font-size: 10pt;
            font-weight: normal;
            font-style: italic;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 8px;
            font-size: 10pt;
        }

        .info-table td:first-child {
            width: 150px;
            font-weight: bold;
        }

        .info-table td:nth-child(2) {
            width: 15px;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .content-table th,
        .content-table td {
            border: 1px solid #000;
            padding: 6px 5px;
            text-align: center;
            font-size: 10pt;
        }

        .content-table th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9pt;
        }

        .content-table td.left {
            text-align: left;
        }

        .content-table td.nama {
            text-align: left;
            font-weight: bold;
        }

        .content-table td.nip {
            text-align: left;
            font-size: 9pt;
            color: #333;
        }

        .status-baik {
            font-weight: bold;
        }

        .status-perlu-perhatian {
            font-weight: bold;
        }

        .status-buruk {
            font-weight: bold;
        }

        .summary {
            margin-top: 20px;
            margin-bottom: 30px;
            padding: 15px;
            border: 2px solid #000;
            background-color: #f5f5f5;
        }

        .summary h4 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 10px;
            text-decoration: underline;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 5px 10px;
            font-size: 10pt;
        }

        .summary-table td:first-child {
            width: 250px;
            font-weight: bold;
        }

        .summary-table td:nth-child(2) {
            width: 20px;
        }

        .summary-table td:nth-child(3) {
            font-weight: bold;
        }

        .signature-section {
            margin-top: 40px;
            text-align: center;
        }

        .signature-box {
            display: inline-block;
            text-align: center;
            width: 45%;
            vertical-align: top;
        }

        .signature-space {
            height: 70px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 9pt;
            color: #555;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <!-- Header Dokumen -->
    <div class="header">
        <h1>KEMENTERIAN AGAMA REPUBLIK INDONESIA</h1>
        <h2>KANWIL KEMENTERIAN AGAMA PROVINSI SULAWESI BARAT</h2>
        <h3>REKAP KINERJA ASN</h3>
        <p class="periode">Periode: {{ $periode }}</p>
        <p class="periode">{{ $tanggal_range }}</p>
        <p class="unit-kerja">Unit Kerja: {{ $atasan->unitKerja->nama_unit ?? '-' }}</p>
    </div>

    <!-- Informasi Dokumen -->
    <table class="info-table">
        <tr>
            <td>Atasan / Pencetak</td>
            <td>:</td>
            <td>{{ $atasan->name }}</td>
        </tr>
        <tr>
            <td>NIP Atasan</td>
            <td>:</td>
            <td>{{ $atasan->nip }}</td>
        </tr>
        <tr>
            <td>Tanggal Cetak</td>
            <td>:</td>
            <td>{{ $tanggal_cetak }}</td>
        </tr>
    </table>

    <!-- Tabel Rekap Kinerja -->
    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 30px;">NO</th>
                <th style="width: 180px;">NAMA ASN</th>
                <th style="width: 60px;">HARI<br>KERJA</th>
                <th style="width: 70px;">TOTAL<br>JAM</th>
                <th style="width: 60px;">AVG/<br>HARI</th>
                <th style="width: 40px;">KH</th>
                <th style="width: 40px;">TLA</th>
                <th style="width: 100px;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @if($rekap_list->isEmpty())
            <tr>
                <td colspan="8" style="padding: 30px; text-align: center; color: #666;">
                    Tidak ada data ASN untuk periode ini
                </td>
            </tr>
            @else
            @foreach($rekap_list as $index => $asn)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="left">
                    <div class="nama">{{ $asn->name }}</div>
                    <div class="nip">NIP: {{ $asn->nip }}</div>
                </td>
                <td>{{ $asn->hari_kerja }}</td>
                <td>{{ $asn->total_jam }}</td>
                <td>{{ number_format($asn->avg_jam_per_hari, 1) }}j</td>
                <td>{{ $asn->total_kh }}</td>
                <td>{{ $asn->total_tla }}</td>
                <td class="status-{{ strtolower(str_replace('_', '-', $asn->status)) }}">
                    {{ str_replace('_', ' ', $asn->status) }}
                </td>
            </tr>
            @endforeach
            @endif
        </tbody>
    </table>

    <!-- Ringkasan -->
    <div class="summary">
        <h4>RINGKASAN KINERJA UNIT</h4>
        <table class="summary-table">
            <tr>
                <td>Total ASN</td>
                <td>:</td>
                <td>{{ $summary['total_asn'] }} orang</td>
            </tr>
            <tr>
                <td>ASN dengan Status BAIK</td>
                <td>:</td>
                <td>{{ $summary['asn_baik'] }} orang ({{ $summary['total_asn'] > 0 ? number_format(($summary['asn_baik'] / $summary['total_asn']) * 100, 1) : 0 }}%)</td>
            </tr>
            <tr>
                <td>ASN yang Perlu Perhatian</td>
                <td>:</td>
                <td>{{ $summary['asn_perlu_perhatian'] }} orang ({{ $summary['total_asn'] > 0 ? number_format(($summary['asn_perlu_perhatian'] / $summary['total_asn']) * 100, 1) : 0 }}%)</td>
            </tr>
            <tr>
                <td>ASN dengan Status BURUK</td>
                <td>:</td>
                <td>{{ $summary['asn_buruk'] }} orang ({{ $summary['total_asn'] > 0 ? number_format(($summary['asn_buruk'] / $summary['total_asn']) * 100, 1) : 0 }}%)</td>
            </tr>
            <tr>
                <td>Rata-rata Jam Kerja Per Hari (Unit)</td>
                <td>:</td>
                <td>{{ number_format($summary['avg_jam_unit'], 1) }} jam</td>
            </tr>
        </table>
    </div>

    <!-- Tanda Tangan -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Mengetahui,<br>Kepala Unit Kerja</p>
            <div class="signature-space"></div>
            <p class="signature-name">( ______________________ )</p>
            <p>NIP. __________________</p>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Dicetak melalui Sistem Informasi dan Data</p>
        <p>Kanwil Kementerian Agama Provinsi Sulawesi Barat</p>
        <p>Tanggal Cetak: {{ $tanggal_cetak }}</p>
    </div>

</body>
</html>
