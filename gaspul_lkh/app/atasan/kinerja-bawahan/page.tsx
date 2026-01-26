'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { kinerjaBawahanApi, BawahanKinerja, KegiatanBawahan, KinerjaBawahanFilter } from '@/app/lib/api-v2';

/**
 * ✅ KINERJA HARIAN BAWAHAN - Dashboard Pengawasan Atasan
 *
 * Fitur:
 * 1. Monitoring progres harian ASN bawahan
 * 2. Filter harian/mingguan/bulanan
 * 3. Deteksi ASN belum mengisi
 * 4. Cetak laporan KH & TLA
 */
export default function KinerjaBawahanPage() {
  const router = useRouter();

  // Biodata State
  const [biodata, setBiodata] = useState<any>(null);

  // Data State
  const [bawahanList, setBawahanList] = useState<BawahanKinerja[]>([]);
  const [summary, setSummary] = useState<any>(null);
  const [loading, setLoading] = useState(false);

  // Filter State
  const [mode, setMode] = useState<'harian' | 'mingguan' | 'bulanan'>('harian');
  const [tanggal, setTanggal] = useState(new Date().toISOString().split('T')[0]);
  const [bulan, setBulan] = useState(new Date().getMonth() + 1);
  const [tahun, setTahun] = useState(new Date().getFullYear());

  // Expanded rows state (for accordion)
  const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set());

  // ✅ Load biodata on mount
  useEffect(() => {
    loadBiodata();
  }, []);

  // ✅ Load data when filter changes
  useEffect(() => {
    loadKinerjaBawahan();
  }, [mode, tanggal, bulan, tahun]);

  const loadBiodata = async () => {
    try {
      const response = await kinerjaBawahanApi.getBiodata();
      setBiodata(response.biodata);
    } catch (error: any) {
      console.error('Error loading biodata:', error);
      alert(error.message || 'Gagal memuat biodata');
    }
  };

  const loadKinerjaBawahan = async () => {
    try {
      setLoading(true);

      const filter: KinerjaBawahanFilter = {
        mode,
        tahun,
      };

      if (mode === 'harian') {
        filter.tanggal = tanggal;
      } else if (mode === 'bulanan') {
        filter.bulan = bulan;
        filter.tahun = tahun;
      }

      const response = await kinerjaBawahanApi.getKinerjaBawahan(filter);
      setBawahanList(response.data);
      setSummary(response.summary);
    } catch (error: any) {
      console.error('Error loading kinerja bawahan:', error);
      alert(error.message || 'Gagal memuat data kinerja bawahan');
    } finally {
      setLoading(false);
    }
  };

  const toggleRowExpand = (userId: number) => {
    const newExpanded = new Set(expandedRows);
    if (newExpanded.has(userId)) {
      newExpanded.delete(userId);
    } else {
      newExpanded.add(userId);
    }
    setExpandedRows(newExpanded);
  };

  const handleCetakKH = async (userId: number, nama: string) => {
    try {
      // Get date range based on mode
      let tanggalMulai = tanggal;
      let tanggalAkhir = tanggal;

      if (mode === 'bulanan') {
        const firstDay = new Date(tahun, bulan - 1, 1);
        const lastDay = new Date(tahun, bulan, 0);
        tanggalMulai = firstDay.toISOString().split('T')[0];
        tanggalAkhir = lastDay.toISOString().split('T')[0];
      }

      const response = await kinerjaBawahanApi.cetakLaporanKH(userId, tanggalMulai, tanggalAkhir);

      // ✅ CETAK DENGAN TEMPLATE HTML
      cetakLaporanHTML(response.data, 'KH');
    } catch (error: any) {
      alert(error.message || 'Gagal mencetak laporan KH');
    }
  };

  const handleCetakTLA = async (userId: number, nama: string) => {
    try {
      let tanggalMulai = tanggal;
      let tanggalAkhir = tanggal;

      if (mode === 'bulanan') {
        const firstDay = new Date(tahun, bulan - 1, 1);
        const lastDay = new Date(tahun, bulan, 0);
        tanggalMulai = firstDay.toISOString().split('T')[0];
        tanggalAkhir = lastDay.toISOString().split('T')[0];
      }

      const response = await kinerjaBawahanApi.cetakLaporanTLA(userId, tanggalMulai, tanggalAkhir);

      // ✅ CETAK DENGAN TEMPLATE HTML
      cetakLaporanHTML(response.data, 'TLA');
    } catch (error: any) {
      alert(error.message || 'Gagal mencetak laporan TLA');
    }
  };

  /**
   * ✅ FUNGSI CETAK LAPORAN - Generate HTML dan Print
   */
  const cetakLaporanHTML = (data: any, tipe: 'KH' | 'TLA') => {
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
      alert('Pop-up blocker menghalangi jendela cetak. Silakan izinkan pop-up untuk situs ini.');
      return;
    }

    const formatTanggal = (tanggal: string) => {
      const date = new Date(tanggal);
      return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    };

    const formatTanggalIndo = (tanggal: string) => {
      const date = new Date(tanggal);
      const options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
      };
      return date.toLocaleDateString('id-ID', options);
    };

    const hariIni = formatTanggalIndo(new Date().toISOString());

    // Template HTML untuk cetak
    const htmlContent = `
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>Laporan ${tipe === 'KH' ? 'Kinerja Harian (LKH)' : 'Tugas Langsung Atasan (TLA)'}</title>
        <style>
          @media print {
            @page {
              size: A4;
              margin: 2cm;
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
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 20px;
          }

          .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
          }

          .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
          }

          .header h2 {
            font-size: 12pt;
            font-weight: bold;
            margin: 5px 0;
          }

          .header p {
            font-size: 10pt;
            margin: 2px 0;
          }

          .title {
            text-align: center;
            margin: 20px 0;
            text-decoration: underline;
            font-weight: bold;
            font-size: 14pt;
          }

          .info-section {
            margin: 20px 0;
          }

          .info-row {
            display: flex;
            margin-bottom: 5px;
          }

          .info-label {
            width: 150px;
            font-weight: normal;
          }

          .info-separator {
            width: 20px;
            text-align: center;
          }

          .info-value {
            flex: 1;
          }

          table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
          }

          table th,
          table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            vertical-align: top;
          }

          table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
          }

          .total-row {
            font-weight: bold;
            background-color: #f5f5f5;
          }

          .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
          }

          .signature-box {
            width: 45%;
            text-align: center;
          }

          .signature-space {
            height: 80px;
          }

          .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background-color: #3B82F6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
          }

          .print-button:hover {
            background-color: #2563EB;
          }
        </style>
      </head>
      <body>
        <button class="print-button no-print" onclick="window.print()">🖨️ Cetak</button>

        <!-- HEADER RESMI -->
        <div class="header">
          <h1>KEMENTERIAN AGAMA REPUBLIK INDONESIA</h1>
          <h1>KANTOR WILAYAH PROVINSI SULAWESI BARAT</h1>
          <p>Jl. Abdul Malik Pattana Endeng No. 49 Mamuju 91511</p>
          <p>Telepon: (0426) 21065 | Email: kanwil.sulbar@kemenag.go.id</p>
        </div>

        <!-- JUDUL LAPORAN -->
        <div class="title">
          LAPORAN ${tipe === 'KH' ? 'KINERJA HARIAN (LKH)' : 'TUGAS LANGSUNG ATASAN (TLA)'}
        </div>

        <!-- INFORMASI ASN -->
        <div class="info-section">
          <div class="info-row">
            <div class="info-label">Nama</div>
            <div class="info-separator">:</div>
            <div class="info-value">${data.asn.nama}</div>
          </div>
          <div class="info-row">
            <div class="info-label">NIP</div>
            <div class="info-separator">:</div>
            <div class="info-value">${data.asn.nip}</div>
          </div>
          <div class="info-row">
            <div class="info-label">Jabatan</div>
            <div class="info-separator">:</div>
            <div class="info-value">${data.asn.jabatan}</div>
          </div>
          <div class="info-row">
            <div class="info-label">Unit Kerja</div>
            <div class="info-separator">:</div>
            <div class="info-value">${data.asn.unit_kerja}</div>
          </div>
          <div class="info-row">
            <div class="info-label">Periode</div>
            <div class="info-separator">:</div>
            <div class="info-value">${formatTanggal(data.periode.tanggal_mulai)} s.d. ${formatTanggal(data.periode.tanggal_akhir)}</div>
          </div>
        </div>

        <!-- TABEL KEGIATAN -->
        <table>
          <thead>
            <tr>
              <th style="width: 5%">No</th>
              <th style="width: 12%">Tanggal</th>
              <th style="width: 12%">Waktu</th>
              <th style="width: 10%">Durasi</th>
              <th style="width: 40%">${tipe === 'KH' ? 'Kegiatan' : 'Tugas'}</th>
              ${tipe === 'KH'
                ? '<th style="width: 10%">Realisasi</th><th style="width: 11%">Bukti Dukung</th>'
                : '<th style="width: 21%">Bukti Dukung</th>'
              }
            </tr>
          </thead>
          <tbody>
            ${data.progres.length === 0
              ? `<tr><td colspan="${tipe === 'KH' ? '7' : '6'}" style="text-align: center; font-style: italic;">Tidak ada data untuk periode ini</td></tr>`
              : data.progres.map((item: any, index: number) => `
                <tr>
                  <td style="text-align: center">${index + 1}</td>
                  <td>${formatTanggal(item.tanggal)}</td>
                  <td>${item.jam_mulai} - ${item.jam_selesai}</td>
                  <td>${Math.floor(item.durasi_menit / 60)} jam ${item.durasi_menit % 60} menit</td>
                  <td>${tipe === 'KH' ? item.kegiatan : item.tugas}</td>
                  ${tipe === 'KH'
                    ? `<td style="text-align: center">${item.realisasi || '-'} ${item.satuan || ''}</td>`
                    : ''
                  }
                  <td style="font-size: 9pt; word-break: break-all;">${item.bukti_dukung ? 'Ada' : '-'}</td>
                </tr>
              `).join('')
            }
            <tr class="total-row">
              <td colspan="${tipe === 'KH' ? '3' : '3'}" style="text-align: right; padding-right: 10px;">TOTAL DURASI:</td>
              <td colspan="${tipe === 'KH' ? '4' : '3'}" style="font-weight: bold;">${data.total_durasi_jam} Jam</td>
            </tr>
          </tbody>
        </table>

        <!-- TANDA TANGAN -->
        <div class="signature-section">
          <div class="signature-box">
            <p>Mengetahui,</p>
            <p style="font-weight: bold;">Atasan Langsung</p>
            <div class="signature-space"></div>
            <p style="border-top: 1px solid #000; display: inline-block; padding-top: 5px; margin-top: 10px;">
              <strong>${data.atasan.nama}</strong><br>
              NIP. ${data.atasan.nip}
            </p>
          </div>
          <div class="signature-box">
            <p>Mamuju, ${hariIni}</p>
            <p style="font-weight: bold;">Yang Melaporkan</p>
            <div class="signature-space"></div>
            <p style="border-top: 1px solid #000; display: inline-block; padding-top: 5px; margin-top: 10px;">
              <strong>${data.asn.nama}</strong><br>
              NIP. ${data.asn.nip}
            </p>
          </div>
        </div>

        <script>
          // Auto print on load (optional)
          // window.onload = function() { window.print(); };
        </script>
      </body>
      </html>
    `;

    printWindow.document.write(htmlContent);
    printWindow.document.close();
  };

  return (
    <div className="container mx-auto p-6 max-w-7xl">
      {/* ========== 1️⃣ HEADER HALAMAN ========== */}
      <div className="bg-white rounded-lg shadow-md p-6 mb-6">
        <div className="flex items-center justify-between mb-4">
          <h1 className="text-2xl font-bold text-gray-800">
            📊 Kinerja Harian Bawahan
          </h1>
          <button
            onClick={() => router.push('/atasan/dashboard')}
            className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Dashboard
          </button>
        </div>

        {biodata && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 bg-blue-50 p-4 rounded-lg border border-blue-200">
            <div>
              <p className="text-sm text-gray-600">Nama Atasan</p>
              <p className="font-semibold text-gray-800">{biodata.nama}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">NIP</p>
              <p className="font-semibold text-gray-800">{biodata.nip}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Jabatan</p>
              <p className="font-semibold text-gray-800">{biodata.jabatan}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Unit Kerja</p>
              <p className="font-semibold text-gray-800">{biodata.unit_kerja}</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Jumlah Bawahan</p>
              <p className="font-semibold text-blue-600">{biodata.jumlah_bawahan} ASN</p>
            </div>
            <div>
              <p className="text-sm text-gray-600">Tahun Aktif</p>
              <p className="font-semibold text-gray-800">{biodata.tahun_aktif}</p>
            </div>
          </div>
        )}
      </div>

      {/* ========== 2️⃣ FILTER DATA ========== */}
      <div className="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 className="text-lg font-semibold text-gray-800 mb-4">Filter Data</h2>

        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {/* Mode Rekap */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              🔁 Mode Rekap
            </label>
            <select
              value={mode}
              onChange={(e) => setMode(e.target.value as any)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="harian">Harian</option>
              <option value="mingguan">Mingguan</option>
              <option value="bulanan">Bulanan</option>
            </select>
          </div>

          {/* Tanggal (untuk mode harian) */}
          {mode === 'harian' && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                📆 Tanggal
              </label>
              <input
                type="date"
                value={tanggal}
                onChange={(e) => setTanggal(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>
          )}

          {/* Bulan (untuk mode bulanan) */}
          {mode === 'bulanan' && (
            <>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  📅 Bulan
                </label>
                <select
                  value={bulan}
                  onChange={(e) => setBulan(Number(e.target.value))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                  {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
                    <option key={m} value={m}>
                      {new Date(2000, m - 1).toLocaleString('id-ID', { month: 'long' })}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  📅 Tahun
                </label>
                <select
                  value={tahun}
                  onChange={(e) => setTahun(Number(e.target.value))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                  {Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - 2 + i).map((y) => (
                    <option key={y} value={y}>
                      {y}
                    </option>
                  ))}
                </select>
              </div>
            </>
          )}
        </div>
      </div>

      {/* ========== SUMMARY CARDS ========== */}
      {summary && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-white rounded-lg shadow-md p-4">
            <p className="text-sm text-gray-600">Total Bawahan</p>
            <p className="text-2xl font-bold text-gray-800">{summary.total_bawahan}</p>
          </div>
          <div className="bg-green-50 rounded-lg shadow-md p-4 border border-green-200">
            <p className="text-sm text-green-700">Sudah Mengisi</p>
            <p className="text-2xl font-bold text-green-600">{summary.sudah_mengisi}</p>
          </div>
          <div className="bg-red-50 rounded-lg shadow-md p-4 border border-red-200">
            <p className="text-sm text-red-700">Belum Mengisi</p>
            <p className="text-2xl font-bold text-red-600">{summary.belum_mengisi}</p>
          </div>
          <div className="bg-blue-50 rounded-lg shadow-md p-4 border border-blue-200">
            <p className="text-sm text-blue-700">Kepatuhan</p>
            <p className="text-2xl font-bold text-blue-600">{summary.persentase_kepatuhan}%</p>
          </div>
        </div>
      )}

      {/* ========== 3️⃣ TABEL ASN ========== */}
      <div className="bg-white rounded-lg shadow-md overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  No
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Pegawai
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Total Kegiatan
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Total Durasi
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                    Memuat data...
                  </td>
                </tr>
              ) : bawahanList.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                    Tidak ada data bawahan
                  </td>
                </tr>
              ) : (
                bawahanList.map((bawahan, index) => (
                  <React.Fragment key={bawahan.user_id}>
                    {/* Main Row */}
                    <tr
                      className={
                        bawahan.status === 'Belum Mengisi'
                          ? 'bg-red-50 hover:bg-red-100'
                          : 'hover:bg-gray-50'
                      }
                    >
                      <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                        {index + 1}
                      </td>
                      <td className="px-4 py-4">
                        <div>
                          <p className="font-semibold text-gray-900">{bawahan.nama}</p>
                          <p className="text-xs text-gray-500">{bawahan.nip}</p>
                          <p className="text-xs text-gray-500">{bawahan.jabatan}</p>
                        </div>
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap">
                        {bawahan.status === 'Sudah Mengisi' ? (
                          <span className="px-2 py-1 text-xs font-semibold bg-green-100 text-green-800 rounded">
                            🟢 Sudah Mengisi
                          </span>
                        ) : (
                          <span className="px-2 py-1 text-xs font-semibold bg-red-100 text-red-800 rounded">
                            🔴 Belum Mengisi
                          </span>
                        )}
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                        {bawahan.total_kegiatan} kegiatan
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                        {bawahan.total_durasi_jam} jam
                      </td>
                      <td className="px-4 py-4 whitespace-nowrap text-sm">
                        <div className="flex gap-2">
                          <button
                            onClick={() => toggleRowExpand(bawahan.user_id)}
                            className="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                          >
                            {expandedRows.has(bawahan.user_id) ? '▲ Tutup' : '▼ Detail'}
                          </button>
                          <button
                            onClick={() => handleCetakKH(bawahan.user_id, bawahan.nama)}
                            className="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700"
                          >
                            📄 KH
                          </button>
                          <button
                            onClick={() => handleCetakTLA(bawahan.user_id, bawahan.nama)}
                            className="px-2 py-1 text-xs bg-purple-600 text-white rounded hover:bg-purple-700"
                          >
                            📄 TLA
                          </button>
                        </div>
                      </td>
                    </tr>

                    {/* Expanded Detail Row */}
                    {expandedRows.has(bawahan.user_id) && (
                      <tr>
                        <td colSpan={6} className="px-4 py-4 bg-gray-50">
                          {bawahan.kegiatan_list.length === 0 ? (
                            <p className="text-sm text-gray-500 text-center py-4">
                              Belum ada kegiatan tercatat
                            </p>
                          ) : (
                            <table className="min-w-full divide-y divide-gray-200">
                              <thead className="bg-gray-100">
                                <tr>
                                  <th className="px-3 py-2 text-left text-xs font-medium text-gray-600">
                                    Waktu
                                  </th>
                                  <th className="px-3 py-2 text-left text-xs font-medium text-gray-600">
                                    Kegiatan
                                  </th>
                                  <th className="px-3 py-2 text-left text-xs font-medium text-gray-600">
                                    Realisasi
                                  </th>
                                  <th className="px-3 py-2 text-left text-xs font-medium text-gray-600">
                                    Keterangan
                                  </th>
                                </tr>
                              </thead>
                              <tbody className="bg-white divide-y divide-gray-200">
                                {bawahan.kegiatan_list.map((kegiatan) => (
                                  <tr key={kegiatan.id}>
                                    <td className="px-3 py-2 text-sm text-gray-900">
                                      <div>
                                        <p className="font-mono text-xs text-gray-600">
                                          {kegiatan.tanggal}
                                        </p>
                                        <p className="font-medium">
                                          {kegiatan.jam_mulai} - {kegiatan.jam_selesai}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                          ({kegiatan.durasi_jam} jam)
                                        </p>
                                      </div>
                                    </td>
                                    <td className="px-3 py-2 text-sm text-gray-900">
                                      <p>{kegiatan.kegiatan}</p>
                                      {kegiatan.status_bukti === 'BELUM_ADA' && (
                                        <span className="text-xs text-red-600">
                                          ⚠️ Belum ada bukti
                                        </span>
                                      )}
                                    </td>
                                    <td className="px-3 py-2 text-sm text-gray-900">
                                      {kegiatan.realisasi} {kegiatan.satuan}
                                    </td>
                                    <td className="px-3 py-2 text-sm">
                                      {kegiatan.keterangan === 'LKH' ? (
                                        <span className="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                          LKH
                                        </span>
                                      ) : (
                                        <span className="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">
                                          TLA
                                        </span>
                                      )}
                                    </td>
                                  </tr>
                                ))}
                              </tbody>
                            </table>
                          )}
                        </td>
                      </tr>
                    )}
                  </React.Fragment>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
