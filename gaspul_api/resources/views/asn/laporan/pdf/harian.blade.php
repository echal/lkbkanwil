<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Kinerja Harian - {{ $tanggal_short }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
            padding: 25px;
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

        .header .subtitle {
            font-size: 11pt;
            font-weight: normal;
            margin-bottom: 5px;
        }

        .info-section {
            margin-bottom: 20px;
            border: 1px solid #000;
            padding: 12px;
            background-color: #f9f9f9;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 8px;
            font-size: 10pt;
        }

        .info-table td:first-child {
            width: 140px;
            font-weight: bold;
        }

        .info-table td:nth-child(2) {
            width: 15px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9pt;
        }

        .status-green {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-yellow {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .status-red {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-gray {
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            padding: 6px 10px;
            background-color: #333;
            color: #fff;
            text-transform: uppercase;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .content-table th,
        .content-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
            font-size: 10pt;
        }

        .content-table th {
            background-color: #e8e8e8;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9pt;
            text-align: center;
        }

        .content-table td.center {
            text-align: center;
        }

        .content-table td.kegiatan {
            font-size: 10pt;
            line-height: 1.4;
        }

        .tipe-kh {
            display: inline-block;
            padding: 2px 8px;
            background-color: #cce5ff;
            color: #004085;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .tipe-tla {
            display: inline-block;
            padding: 2px 8px;
            background-color: #fff3cd;
            color: #856404;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .indikator-label {
            font-size: 8pt;
            color: #666;
            font-style: italic;
            margin-top: 2px;
        }

        .summary-box {
            margin-top: 20px;
            padding: 15px;
            border: 2px solid #000;
            background-color: #f5f5f5;
        }

        .summary-box h4 {
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
            width: 200px;
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
        }

        .signature-box {
            float: right;
            width: 250px;
            text-align: center;
        }

        .signature-space {
            height: 70px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .footer {
            clear: both;
            margin-top: 80px;
            text-align: center;
            font-size: 9pt;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .link-bukti {
            font-size: 8pt;
            color: #0066cc;
            word-break: break-all;
        }
    </style>
</head>
<body>

    <!-- Header Dokumen -->
    <div class="header">
        <h1>KEMENTERIAN AGAMA REPUBLIK INDONESIA</h1>
        <h2>KANWIL KEMENTERIAN AGAMA PROVINSI SULAWESI BARAT</h2>
        <h3>LAPORAN KINERJA HARIAN (LKH)</h3>
        <p class="subtitle">{{ $tanggal }}</p>
    </div>

    <!-- Informasi ASN -->
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td>Nama ASN</td>
                <td>:</td>
                <td>{{ $asn->name }}</td>
            </tr>
            <tr>
                <td>NIP</td>
                <td>:</td>
                <td>{{ $asn->nip ?? '-' }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>:</td>
                <td>{{ $asn->jabatan ?? '-' }}</td>
            </tr>
            <tr>
                <td>Unit Kerja</td>
                <td>:</td>
                <td>{{ $asn->unitKerja->nama_unit ?? '-' }}</td>
            </tr>
            <tr>
                <td>Tanggal Laporan</td>
                <td>:</td>
                <td>{{ $tanggal }}</td>
            </tr>
            <tr>
                <td>Status Harian</td>
                <td>:</td>
                <td><span class="status-badge status-{{ $statusColor }}">{{ $status }}</span></td>
            </tr>
        </table>
    </div>

    <!-- Daftar Kegiatan -->
    <div class="section-title">Rincian Kegiatan Harian</div>

    @if($progresHarian->isEmpty())
        <div class="empty-state">
            <p>Tidak ada kegiatan yang tercatat pada tanggal ini.</p>
        </div>
    @else
        <table class="content-table">
            <thead>
                <tr>
                    <th style="width: 25px;">NO</th>
                    <th style="width: 60px;">JAM</th>
                    <th style="width: 50px;">DURASI</th>
                    <th>URAIAN KEGIATAN</th>
                    <th style="width: 60px;">PROGRES</th>
                    <th style="width: 50px;">BUKTI</th>
                </tr>
            </thead>
            <tbody>
                @foreach($progresHarian as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">
                        {{ \Carbon\Carbon::parse($item->jam_mulai)->format('H:i') }} -
                        {{ \Carbon\Carbon::parse($item->jam_selesai)->format('H:i') }}
                    </td>
                    <td class="center">{{ $item->durasi_jam }}</td>
                    <td class="kegiatan">
                        @if($item->tipe_progres === 'KINERJA_HARIAN')
                            <span class="tipe-kh">KH</span>
                            <strong>{{ $item->rencana_kegiatan_harian }}</strong>
                            @if($item->rencanaAksiBulanan)
                                <div class="indikator-label">
                                    Indikator: {{ $item->rencanaAksiBulanan->skpTahunanDetail->indikatorKinerja->nama_indikator ?? '-' }}
                                </div>
                            @endif
                        @else
                            <span class="tipe-tla">TLA</span>
                            <strong>{{ $item->tugas_atasan }}</strong>
                        @endif

                        @if($item->keterangan)
                            <div style="margin-top: 5px; font-size: 9pt; color: #555;">
                                <em>Keterangan: {{ $item->keterangan }}</em>
                            </div>
                        @endif
                    </td>
                    <td class="center">
                        {{ $item->progres }} {{ $item->satuan }}
                    </td>
                    <td class="center">
                        @if($item->status_bukti === 'SUDAH_ADA')
                            ✓
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Ringkasan -->
    <div class="summary-box">
        <h4>RINGKASAN HARIAN</h4>
        <table class="summary-table">
            <tr>
                <td>Total Durasi Kerja</td>
                <td>:</td>
                <td>{{ $totalJam }} ({{ $totalMenit }} menit)</td>
            </tr>
            <tr>
                <td>Total Kinerja Harian (KH)</td>
                <td>:</td>
                <td>{{ $totalKH }} kegiatan</td>
            </tr>
            <tr>
                <td>Total Tugas Langsung Atasan (TLA)</td>
                <td>:</td>
                <td>{{ $totalTLA }} kegiatan</td>
            </tr>
            <tr>
                <td>Total Kegiatan</td>
                <td>:</td>
                <td>{{ $progresHarian->count() }} kegiatan</td>
            </tr>
            <tr>
                <td>Status Kelengkapan</td>
                <td>:</td>
                <td><span class="status-badge status-{{ $statusColor }}">{{ $status }}</span></td>
            </tr>
        </table>
    </div>

    <!-- Tanda Tangan -->
    <div class="signature-section">
        <div class="signature-box">
            <p style="margin-bottom: 5px;">{{ now()->locale('id')->isoFormat('D MMMM Y') }}</p>
            <p style="font-weight: bold; margin-bottom: 5px;">Yang Melaporkan,</p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ $asn->name }}</p>
            <p>NIP. {{ $asn->nip }}</p>
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
