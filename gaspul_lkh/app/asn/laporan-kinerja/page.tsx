'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { laporanKinerjaAsnApi, KegiatanASN, LaporanKinerjaFilter } from '@/app/lib/api-v2';

/**
 * ✅ LAPORAN KINERJA ASN (PERSONAL) - Self Monitoring Dashboard
 *
 * Fitur:
 * 1. ASN melihat laporan kinerjanya sendiri (LKH + TLA)
 * 2. Filter harian/mingguan/bulanan
 * 3. Cetak laporan KH & TLA milik sendiri
 * 4. STRICT ACCESS CONTROL - hanya data ASN yang login
 */
export default function LaporanKinerjaAsnPage() {
  const router = useRouter();

  // Biodata State
  const [biodata, setBiodata] = useState<any>(null);

  // Data State
  const [kegiatanList, setKegiatanList] = useState<KegiatanASN[]>([]);
  const [summary, setSummary] = useState<any>(null);
  const [loading, setLoading] = useState(false);

  // Filter State
  const [mode, setMode] = useState<'harian' | 'mingguan' | 'bulanan'>('bulanan');
  const [tanggal, setTanggal] = useState(new Date().toISOString().split('T')[0]);
  const [bulan, setBulan] = useState(new Date().getMonth() + 1);
  const [tahun, setTahun] = useState(new Date().getFullYear());

  // Expanded rows state (for accordion)
  const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set());

  // ========== LOAD DATA ==========
  useEffect(() => {
    loadBiodata();
    loadLaporanKinerja();
  }, []);

  useEffect(() => {
    loadLaporanKinerja();
  }, [mode, tanggal, bulan, tahun]);

  const loadBiodata = async () => {
    try {
      const response = await laporanKinerjaAsnApi.getBiodata();
      setBiodata(response.biodata);
    } catch (error: any) {
      console.error('Failed to load biodata:', error);
    }
  };

  const loadLaporanKinerja = async () => {
    try {
      setLoading(true);

      const filter: LaporanKinerjaFilter = {
        mode,
      };

      if (mode === 'harian') {
        filter.tanggal = tanggal;
      } else if (mode === 'bulanan') {
        filter.bulan = bulan;
        filter.tahun = tahun;
      }

      const response = await laporanKinerjaAsnApi.getLaporanKinerja(filter);
      setKegiatanList(response.data.kegiatan_list);
      setSummary(response.summary);
    } catch (error: any) {
      console.error('Failed to load laporan kinerja:', error);
      alert(error.message || 'Gagal memuat data laporan kinerja');
    } finally {
      setLoading(false);
    }
  };

  // ========== CETAK LAPORAN ==========
  const handleCetakKH = async () => {
    try {
      let tanggalMulai = tanggal;
      let tanggalAkhir = tanggal;

      if (mode === 'bulanan') {
        const firstDay = new Date(tahun, bulan - 1, 1);
        const lastDay = new Date(tahun, bulan, 0);
        tanggalMulai = firstDay.toISOString().split('T')[0];
        tanggalAkhir = lastDay.toISOString().split('T')[0];
      }

      const response = await laporanKinerjaAsnApi.cetakLaporanKH(tanggalMulai, tanggalAkhir);

      // ✅ CETAK DENGAN TEMPLATE HTML
      cetakLaporanHTML(response.data, 'KH');
    } catch (error: any) {
      alert(error.message || 'Gagal mencetak laporan KH');
    }
  };

  const handleCetakTLA = async () => {
    try {
      let tanggalMulai = tanggal;
      let tanggalAkhir = tanggal;

      if (mode === 'bulanan') {
        const firstDay = new Date(tahun, bulan - 1, 1);
        const lastDay = new Date(tahun, bulan, 0);
        tanggalMulai = firstDay.toISOString().split('T')[0];
        tanggalAkhir = lastDay.toISOString().split('T')[0];
      }

      const response = await laporanKinerjaAsnApi.cetakLaporanTLA(tanggalMulai, tanggalAkhir);

      // ✅ CETAK DENGAN TEMPLATE HTML
      cetakLaporanHTML(response.data, 'TLA');
    } catch (error: any) {
      alert(error.message || 'Gagal mencetak laporan TLA');
    }
  };

  /**
   * ✅ FUNGSI CETAK LAPORAN - Generate HTML dan Print
   * CATATAN: Tidak ada tanda tangan atasan untuk laporan ASN personal
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
        <title>Laporan ${tipe === 'KH' ? 'Kinerja Harian (LKH)' : 'Tugas Langsung Atasan (TLA)'} - Personal</title>
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
            text-align: right;
          }

          .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 300px;
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

        <!-- TANDA TANGAN ASN (TANPA ATASAN) -->
        <div class="signature-section">
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
            📊 Laporan Kinerja Saya
          </h1>
          <button
            onClick={() => router.push('/asn/dashboard')}
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
              <p className="text-sm text-gray-600">Nama</p>
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
          </div>
        )}
      </div>

      {/* ========== 2️⃣ FILTER ========== */}
      <div className="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 className="text-lg font-semibold text-gray-800 mb-4">Filter Laporan</h2>

        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {/* Mode */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Mode Rekap
            </label>
            <select
              value={mode}
              onChange={(e) => setMode(e.target.value as any)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                Tanggal
              </label>
              <input
                type="date"
                value={tanggal}
                onChange={(e) => setTanggal(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          )}

          {/* Bulan & Tahun (untuk mode bulanan) */}
          {mode === 'bulanan' && (
            <>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Bulan
                </label>
                <select
                  value={bulan}
                  onChange={(e) => setBulan(Number(e.target.value))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
                    <option key={m} value={m}>
                      {new Date(0, m - 1).toLocaleDateString('id-ID', { month: 'long' })}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Tahun
                </label>
                <select
                  value={tahun}
                  onChange={(e) => setTahun(Number(e.target.value))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  {Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i).map((y) => (
                    <option key={y} value={y}>
                      {y}
                    </option>
                  ))}
                </select>
              </div>
            </>
          )}

          {/* Tombol Cetak */}
          <div className="flex items-end gap-2">
            <button
              onClick={handleCetakKH}
              className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium"
            >
              📄 Cetak LKH
            </button>
            <button
              onClick={handleCetakTLA}
              className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium"
            >
              📄 Cetak TLA
            </button>
          </div>
        </div>
      </div>

      {/* ========== 3️⃣ SUMMARY CARDS ========== */}
      {summary && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-white rounded-lg shadow-md p-4">
            <p className="text-sm text-gray-600">Total Kegiatan</p>
            <p className="text-2xl font-bold text-gray-800">{summary.total_kegiatan}</p>
          </div>
          <div className="bg-blue-50 rounded-lg shadow-md p-4 border border-blue-200">
            <p className="text-sm text-blue-700">Kinerja Harian (LKH)</p>
            <p className="text-2xl font-bold text-blue-600">{summary.total_kh}</p>
          </div>
          <div className="bg-purple-50 rounded-lg shadow-md p-4 border border-purple-200">
            <p className="text-sm text-purple-700">Tugas Atasan (TLA)</p>
            <p className="text-2xl font-bold text-purple-600">{summary.total_tla}</p>
          </div>
          <div className="bg-green-50 rounded-lg shadow-md p-4 border border-green-200">
            <p className="text-sm text-green-700">Total Durasi</p>
            <p className="text-2xl font-bold text-green-600">{summary.total_durasi_jam} jam</p>
          </div>
        </div>
      )}

      {/* ========== 4️⃣ TABEL KEGIATAN ========== */}
      <div className="bg-white rounded-lg shadow-md overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  No
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tanggal
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Waktu
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Kegiatan/Tugas
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Realisasi
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Durasi
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Keterangan
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan={7} className="px-4 py-8 text-center text-gray-500">
                    Memuat data...
                  </td>
                </tr>
              ) : kegiatanList.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-4 py-8 text-center text-gray-500">
                    Belum ada kegiatan tercatat untuk periode ini
                  </td>
                </tr>
              ) : (
                kegiatanList.map((kegiatan, index) => (
                  <tr key={kegiatan.id} className="hover:bg-gray-50">
                    <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      {index + 1}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      {new Date(kegiatan.tanggal).toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric'
                      })}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      <div>
                        <p className="font-medium">{kegiatan.jam_mulai} - {kegiatan.jam_selesai}</p>
                      </div>
                    </td>
                    <td className="px-4 py-4 text-sm text-gray-900">
                      <p>{kegiatan.kegiatan}</p>
                      {kegiatan.status_bukti === 'BELUM_ADA' && (
                        <span className="text-xs text-red-600">
                          ⚠️ Belum ada bukti
                        </span>
                      )}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      {kegiatan.realisasi} {kegiatan.satuan}
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      {kegiatan.durasi_jam} jam
                    </td>
                    <td className="px-4 py-4 whitespace-nowrap text-sm">
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
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
