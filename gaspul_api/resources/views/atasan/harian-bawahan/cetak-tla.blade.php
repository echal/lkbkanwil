<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Tugas Langsung Atasan (TLA) - {{ $asn->name }}</title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 1.5cm;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none;
            }
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header h2 {
            margin: 5px 0 0 0;
            font-size: 14pt;
            font-weight: normal;
        }

        .biodata {
            margin-bottom: 20px;
        }

        .biodata table {
            width: 100%;
            border-collapse: collapse;
        }

        .biodata td {
            padding: 5px 10px;
            vertical-align: top;
        }

        .biodata td:first-child {
            width: 200px;
            font-weight: bold;
        }

        .biodata td:nth-child(2) {
            width: 20px;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .content-table th,
        .content-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }

        .content-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .content-table td {
            text-align: left;
        }

        .content-table td.center {
            text-align: center;
        }

        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature {
            text-align: center;
            width: 45%;
        }

        .signature-space {
            height: 80px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }

        .no-print {
            margin-bottom: 20px;
            text-align: center;
        }

        .btn-print {
            background-color: #9333ea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14pt;
            font-weight: bold;
        }

        .btn-print:hover {
            background-color: #7e22ce;
        }
    </style>
</head>
<body>

    <!-- Print Button -->
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Cetak / Print</button>
    </div>

    <!-- Header -->
    <div class="header">
        <h1>Laporan Tugas Langsung Atasan (TLA)</h1>
        <h2>Kementerian Agama Provinsi Sulawesi Barat</h2>
    </div>

    <!-- Biodata -->
    <div class="biodata">
        <table>
            <tr>
                <td>Nama Pegawai</td>
                <td>:</td>
                <td>{{ $asn->name }}</td>
            </tr>
            <tr>
                <td>NIP</td>
                <td>:</td>
                <td>{{ $asn->nip }}</td>
            </tr>
            <tr>
                <td>Tanggal Laporan</td>
                <td>:</td>
                <td>{{ \Carbon\Carbon::parse($tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') }}</td>
            </tr>
            <tr>
                <td>Atasan Pemberi Tugas</td>
                <td>:</td>
                <td>{{ $atasan->name }} (NIP: {{ $atasan->nip }})</td>
            </tr>
            <tr>
                <td>Tanggal Cetak</td>
                <td>:</td>
                <td>{{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y HH:mm') }} WIB</td>
            </tr>
        </table>
    </div>

    <!-- Content Table -->
    @if($data_tla->isEmpty())
    <p style="text-align: center; padding: 40px 0; color: #666;">
        Tidak ada Tugas Langsung Atasan untuk tanggal ini.
    </p>
    @else
    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 100px;">Waktu</th>
                <th>Tugas Dari Atasan</th>
                <th style="width: 120px;">Progres</th>
                <th style="width: 150px;">Bukti Dukung</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalMenit = 0;
            @endphp
            @foreach($data_tla as $index => $tla)
            @php
                $totalMenit += $tla->durasi_menit;
            @endphp
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td class="center">
                    {{ \Carbon\Carbon::parse($tla->jam_mulai)->format('H:i') }} -
                    {{ \Carbon\Carbon::parse($tla->jam_selesai)->format('H:i') }}<br>
                    <small>({{ floor($tla->durasi_menit / 60) }}j {{ $tla->durasi_menit % 60 }}m)</small>
                </td>
                <td>{{ $tla->tugas_atasan }}</td>
                <td class="center">{{ $tla->progres }} {{ $tla->satuan }}</td>
                <td class="center">
                    @if($tla->status_bukti === 'SUDAH_ADA')
                        <small>{{ $tla->bukti_dukung }}</small>
                    @else
                        <span style="color: #dc2626;">Belum Ada</span>
                    @endif
                </td>
            </tr>
            @endforeach
            <tr>
                <td colspan="2" style="text-align: right; font-weight: bold;">TOTAL JAM KERJA:</td>
                <td colspan="3" style="font-weight: bold;">
                    {{ floor($totalMenit / 60) }} jam {{ $totalMenit % 60 }} menit
                </td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Footer / Signature -->
    <div class="footer">
        <div class="signature">
            <p>Mengetahui,<br>Atasan Pemberi Tugas</p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ $atasan->name }}</p>
            <p>NIP. {{ $atasan->nip }}</p>
        </div>
        <div class="signature">
            <p>Pegawai Yang Bersangkutan</p>
            <div class="signature-space"></div>
            <p class="signature-name">{{ $asn->name }}</p>
            <p>NIP. {{ $asn->nip }}</p>
        </div>
    </div>

</body>
</html>
