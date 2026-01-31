<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Tugas Langsung Atasan - {{ $tanggal }}</title>
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
            padding: 25px 30px;
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

        .header .tanggal {
            font-size: 11pt;
            font-weight: normal;
            font-style: italic;
        }

        .info-section {
            margin-bottom: 20px;
            border: 2px solid #000;
            padding: 12px 15px;
            background-color: #f9f9f9;
        }

        .info-section h4 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px 8px;
            font-size: 10pt;
        }

        .info-table td:first-child {
            width: 150px;
            font-weight: bold;
        }

        .info-table td:nth-child(2) {
            width: 15px;
        }

        .content-section {
            margin-bottom: 20px;
            border: 1px solid #666;
            padding: 15px;
        }

        .content-section h4 {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 12px;
            text-transform: uppercase;
            background-color: #6f42c1;
            color: #fff;
            padding: 6px 10px;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .detail-table td {
            padding: 6px 10px;
            font-size: 10pt;
            border-bottom: 1px dashed #ccc;
        }

        .detail-table td:first-child {
            width: 180px;
            font-weight: bold;
            vertical-align: top;
        }

        .detail-table td:nth-child(2) {
            width: 15px;
            vertical-align: top;
        }

        .detail-table td:last-child {
            vertical-align: top;
        }

        .tugas-box {
            margin-top: 10px;
            padding: 12px;
            background-color: #f3e5f5;
            border: 2px solid #6f42c1;
            border-radius: 5px;
        }

        .tugas-box h5 {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 8px;
            color: #6f42c1;
        }

        .tugas-box p {
            font-size: 11pt;
            line-height: 1.6;
        }

        .bukti-box {
            margin-top: 15px;
            padding: 10px;
            background-color: #fffbf0;
            border: 1px solid #ddd;
            border-left: 4px solid #6f42c1;
        }

        .bukti-box strong {
            display: block;
            margin-bottom: 5px;
            font-size: 10pt;
        }

        .bukti-box a {
            color: #6f42c1;
            text-decoration: underline;
            word-wrap: break-word;
            font-size: 9pt;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-ada {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-belum {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 45%;
            text-align: center;
            vertical-align: top;
        }

        .signature-box.right {
            float: right;
        }

        .signature-space {
            height: 70px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 3px;
        }

        .signature-nip {
            font-size: 10pt;
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

        .note-box {
            margin-top: 15px;
            padding: 10px;
            background-color: #e7d4f7;
            border-left: 4px solid #6f42c1;
            font-size: 9pt;
            font-style: italic;
        }

        .tla-badge {
            display: inline-block;
            background-color: #6f42c1;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
            margin-left: 5px;
        }
    </style>
</head>
<body>

    <!-- Header Dokumen -->
    <div class="header">
        <h1>KEMENTERIAN AGAMA REPUBLIK INDONESIA</h1>
        <h2>KANWIL KEMENTERIAN AGAMA PROVINSI SULAWESI BARAT</h2>
        <h3>LAPORAN TUGAS LANGSUNG ATASAN (TLA) <span class="tla-badge">TLA</span></h3>
        <p class="tanggal">{{ $tanggal }}</p>
    </div>

    <!-- Informasi ASN -->
    <div class="info-section">
        <h4>Informasi Pegawai</h4>
        <table class="info-table">
            <tr>
                <td>Nama</td>
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
        </table>
    </div>

    <!-- Detail Tugas Atasan -->
    <div class="content-section">
        <h4>Detail Tugas Langsung Atasan</h4>

        <table class="detail-table">
            <tr>
                <td>Tanggal</td>
                <td>:</td>
                <td>{{ $tanggal }}</td>
            </tr>
            <tr>
                <td>Waktu Pelaksanaan</td>
                <td>:</td>
                <td>
                    {{ substr($progres->jam_mulai, 0, 5) }} - {{ substr($progres->jam_selesai, 0, 5) }} WIB
                    <strong>({{ floor($progres->durasi_menit / 60) }} jam {{ $progres->durasi_menit % 60 }} menit)</strong>
                </td>
            </tr>
            <tr>
                <td>Jenis Kegiatan</td>
                <td>:</td>
                <td><span class="tla-badge">TUGAS LANGSUNG ATASAN</span></td>
            </tr>
        </table>

        <!-- Box Uraian Tugas -->
        <div class="tugas-box">
            <h5>URAIAN TUGAS DARI ATASAN:</h5>
            <p>{{ $progres->tugas_atasan ?? $progres->rencana_kegiatan_harian }}</p>
        </div>

        <table class="detail-table" style="margin-top: 15px;">
            <tr>
                <td>Status Bukti Dukung</td>
                <td>:</td>
                <td>
                    @if($progres->status_bukti === 'ADA')
                        <span class="status-badge status-ada">✓ Bukti Tersedia</span>
                    @else
                        <span class="status-badge status-belum">✗ Belum Ada Bukti</span>
                    @endif
                </td>
            </tr>
            @if($progres->keterangan)
            <tr>
                <td>Keterangan</td>
                <td>:</td>
                <td>{{ $progres->keterangan }}</td>
            </tr>
            @endif
        </table>

        <!-- Bukti Dukung -->
        @if($progres->bukti_dukung)
        <div class="bukti-box">
            <strong>Link Bukti Dukung:</strong>
            <a href="{{ $progres->bukti_dukung }}" target="_blank">{{ $progres->bukti_dukung }}</a>
        </div>
        @endif
    </div>

    <!-- Catatan -->
    <div class="note-box">
        <strong>Catatan:</strong> Tugas Langsung Atasan (TLA) adalah tugas yang diberikan langsung oleh atasan di luar rencana kinerja yang telah ditetapkan.
        Dokumen ini merupakan bukti pelaksanaan tugas yang telah diinput ke dalam Sistem Informasi Kinerja ASN.
        Kebenaran data yang tercantum menjadi tanggung jawab pegawai yang bersangkutan.
    </div>

    <!-- Tanda Tangan -->
    <div class="signature-section">
        <div class="signature-box right">
            <p style="margin-bottom: 5px;">{{ now()->locale('id')->isoFormat('D MMMM Y') }}</p>
            <p style="font-weight: bold; margin-bottom: 5px;">Yang Melaporkan,</p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ $asn->name }}</p>
            <p class="signature-nip">NIP. {{ $asn->nip }}</p>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Dicetak melalui Sistem Informasi Kinerja ASN</p>
        <p>Kanwil Kementerian Agama Provinsi Sulawesi Barat</p>
        <p>Tanggal Cetak: {{ now()->locale('id')->isoFormat('D MMMM Y, HH:mm') }} WIB</p>
    </div>

</body>
</html>
