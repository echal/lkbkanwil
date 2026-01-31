<?php

namespace App\Http\Controllers\Asn;

use App\Http\Controllers\Controller;
use App\Models\ProgresHarian;
use App\Models\RencanaAksiBulanan;
use App\Models\SkpTahunan;
use App\Models\SkpTahunanDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Controller Laporan Bulanan Kinerja ASN
 *
 * Menampilkan laporan kinerja bulanan lengkap untuk evaluasi dan cetak PDF
 */
class BulananController extends Controller
{
    /**
     * Display Laporan Bulanan ASN
     *
     * Struktur Data:
     * 1. Header: Identitas ASN
     * 2. Ringkasan Bulanan: Total hari, jam, capaian
     * 3. Rekap RHK Bulanan: Target vs Realisasi per RHK
     * 4. Rekap Kinerja Harian: Detail per tanggal
     * 5. Kesimpulan & Status
     */
    public function index(Request $request)
    {
        $asn = Auth::user();
        $tahun = $request->input('tahun', now()->year);
        $bulan = $request->input('bulan', now()->month);

        // 1. GET SKP TAHUNAN
        $skpTahunan = SkpTahunan::where('user_id', $asn->id)
            ->where('tahun', $tahun)
            ->first();

        if (!$skpTahunan) {
            return view('asn.bulanan.index', [
                'hasData' => false,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'namaBulan' => $this->getNamaBulan($bulan),
                'asn' => $asn,
            ]);
        }

        // 2. GET ALL RENCANA AKSI BULANAN (Target bulan ini)
        $rencanaAksiList = RencanaAksiBulanan::whereHas('skpTahunanDetail', function($query) use ($skpTahunan) {
                $query->where('skp_tahunan_id', $skpTahunan->id);
            })
            ->with([
                'skpTahunanDetail.indikatorKinerja',
                'progresHarian' => function($query) use ($tahun, $bulan) {
                    $query->whereYear('tanggal', $tahun)
                          ->whereMonth('tanggal', $bulan);
                }
            ])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->where('status', '!=', 'BELUM_DIISI')
            ->get();

        // 3. GET ALL PROGRES HARIAN BULAN INI
        $progresHarianList = ProgresHarian::where('user_id', $asn->id)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->with(['rencanaAksiBulanan.skpTahunanDetail.indikatorKinerja'])
            ->orderBy('tanggal', 'asc')
            ->orderBy('jam_mulai', 'asc')
            ->get();

        // Group progres harian by date
        $progresGroupedByDate = $progresHarianList->groupBy(function($item) {
            return Carbon::parse($item->tanggal)->format('Y-m-d');
        });

        // 4. CALCULATE RINGKASAN BULANAN
        $totalHariKerja = $progresGroupedByDate->count();
        $totalDurasiMenit = $progresHarianList->sum('durasi_menit');
        $totalJamKerja = floor($totalDurasiMenit / 60);
        $sisaMenit = $totalDurasiMenit % 60;

        // Target jam kerja per bulan (asumsi: 22 hari x 7.5 jam = 165 jam)
        $targetJamKerjaBulanan = 165;
        $persentaseJamKerja = $targetJamKerjaBulanan > 0
            ? round(($totalDurasiMenit / 60) / $targetJamKerjaBulanan * 100, 1)
            : 0;

        // Status capaian
        $statusCapaian = 'Kurang';
        if ($persentaseJamKerja >= 90) {
            $statusCapaian = 'Sangat Baik';
        } elseif ($persentaseJamKerja >= 75) {
            $statusCapaian = 'Baik';
        } elseif ($persentaseJamKerja >= 60) {
            $statusCapaian = 'Cukup';
        }

        // 5. CALCULATE REKAP RHK BULANAN
        $rekapRhkBulanan = [];
        foreach ($rencanaAksiList as $rencanaAksi) {
            $indikatorKinerja = $rencanaAksi->skpTahunanDetail->indikatorKinerja ?? null;

            if (!$indikatorKinerja) continue;

            // Hitung realisasi dari progres harian
            $realisasiBulanan = $rencanaAksi->progresHarian->sum('progres');
            $targetBulanan = $rencanaAksi->target_bulanan;
            $persentaseCapaian = $targetBulanan > 0
                ? round(($realisasiBulanan / $targetBulanan) * 100, 1)
                : 0;

            $rekapRhkBulanan[] = [
                'indikator_kinerja' => $indikatorKinerja->nama_indikator,
                'kode_indikator' => $indikatorKinerja->kode_indikator ?? '-',
                'rencana_aksi_tahunan' => $rencanaAksi->skpTahunanDetail->rencana_aksi,
                'rencana_aksi_bulanan' => $rencanaAksi->rencana_aksi_bulanan,
                'target_bulanan' => $targetBulanan,
                'satuan' => $rencanaAksi->satuan_target ?? $rencanaAksi->skpTahunanDetail->satuan,
                'realisasi_bulanan' => $realisasiBulanan,
                'persentase_capaian' => $persentaseCapaian,
                'status' => $this->getStatusCapaian($persentaseCapaian),
                'jumlah_hari_dikerjakan' => $rencanaAksi->progresHarian->unique('tanggal')->count(),
            ];
        }

        // 6. CALCULATE REKAP KINERJA HARIAN
        $rekapKinerjaHarian = [];
        foreach ($progresGroupedByDate as $tanggal => $progresItems) {
            $dateObj = Carbon::parse($tanggal);
            $totalProgresHari = $progresItems->sum('progres');
            $totalDurasiHari = $progresItems->sum('durasi_menit');

            $kegiatanList = [];
            foreach ($progresItems as $progres) {
                $indikatorNama = '-';
                if ($progres->rencanaAksiBulanan && $progres->rencanaAksiBulanan->skpTahunanDetail) {
                    $indikatorNama = $progres->rencanaAksiBulanan->skpTahunanDetail->indikatorKinerja->nama_indikator ?? '-';
                }

                $kegiatanList[] = [
                    'jam_mulai' => substr($progres->jam_mulai, 0, 5),
                    'jam_selesai' => substr($progres->jam_selesai, 0, 5),
                    'durasi_menit' => $progres->durasi_menit,
                    'indikator_kinerja' => $indikatorNama,
                    'kegiatan' => $progres->rencana_kegiatan_harian,
                    'progres' => $progres->progres,
                    'satuan' => $progres->satuan,
                    'tipe' => $progres->tipe_progres,
                    'status_bukti' => $progres->status_bukti,
                    'keterangan' => $progres->keterangan,
                ];
            }

            $rekapKinerjaHarian[] = [
                'tanggal' => $tanggal,
                'tanggal_formatted' => $dateObj->translatedFormat('l, d F Y'),
                'hari_nama' => $dateObj->translatedFormat('l'),
                'total_progres' => $totalProgresHari,
                'total_durasi_menit' => $totalDurasiHari,
                'total_durasi_formatted' => floor($totalDurasiHari / 60) . ' jam ' . ($totalDurasiHari % 60) . ' menit',
                'jumlah_kegiatan' => $progresItems->count(),
                'kegiatan_list' => $kegiatanList,
            ];
        }

        // 7. KESIMPULAN OTOMATIS
        $kesimpulanOtomatis = $this->generateKesimpulan([
            'total_hari_kerja' => $totalHariKerja,
            'total_jam_kerja' => $totalJamKerja,
            'persentase_jam_kerja' => $persentaseJamKerja,
            'total_rhk' => count($rekapRhkBulanan),
            'status_capaian' => $statusCapaian,
        ]);

        // 8. REKAP KERJA HARIAN DETAIL (untuk tabel baru)
        $rekapKerjaHarianDetail = $this->buildRekapKerjaHarianDetail($progresHarianList, $asn);

        // 9. STATUS LAPORAN (dari database atau default)
        // TODO: Buat tabel laporan_bulanan untuk tracking status
        $statusLaporan = 'DRAFT'; // DRAFT, DIKIRIM, DISETUJUI, DITOLAK

        return view('asn.bulanan.index', [
            'hasData' => true,
            'asn' => $asn,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'namaBulan' => $this->getNamaBulan($bulan),

            // Ringkasan
            'totalHariKerja' => $totalHariKerja,
            'totalJamKerja' => $totalJamKerja,
            'sisaMenit' => $sisaMenit,
            'targetJamKerjaBulanan' => $targetJamKerjaBulanan,
            'persentaseJamKerja' => $persentaseJamKerja,
            'statusCapaian' => $statusCapaian,

            // Detail Rekap
            'rekapRhkBulanan' => $rekapRhkBulanan,
            'rekapKinerjaHarian' => $rekapKinerjaHarian,
            'rekapKerjaHarianDetail' => $rekapKerjaHarianDetail,

            // Kesimpulan & Status
            'kesimpulanOtomatis' => $kesimpulanOtomatis,
            'statusLaporan' => $statusLaporan,
        ]);
    }

    /**
     * Export laporan to PDF
     */
    public function exportPdf(Request $request)
    {
        // TODO: Implement PDF generation using DomPDF or similar
        $tahun = $request->input('tahun', now()->year);
        $bulan = $request->input('bulan', now()->month);

        return response()->json([
            'message' => 'PDF export will be implemented',
            'tahun' => $tahun,
            'bulan' => $bulan,
        ]);
    }

    /**
     * Kirim laporan ke atasan
     */
    public function kirimKeAtasan(Request $request)
    {
        $asn = Auth::user();
        $tahun = $request->input('tahun', now()->year);
        $bulan = $request->input('bulan', now()->month);

        // TODO: Implement notification to atasan
        // Create laporan_bulanan record with status DIKIRIM

        return redirect()
            ->route('asn.bulanan.index', ['tahun' => $tahun, 'bulan' => $bulan])
            ->with('success', 'Laporan berhasil dikirim ke atasan');
    }

    /**
     * Helper: Get status capaian based on percentage
     */
    private function getStatusCapaian($persentase)
    {
        if ($persentase >= 100) return 'Tercapai';
        if ($persentase >= 80) return 'Hampir Tercapai';
        if ($persentase >= 50) return 'Setengah Jalan';
        return 'Belum Tercapai';
    }

    /**
     * Helper: Generate kesimpulan otomatis
     */
    private function generateKesimpulan($data)
    {
        $kesimpulan = [];

        // Kesimpulan jam kerja
        if ($data['persentase_jam_kerja'] >= 90) {
            $kesimpulan[] = "Jam kerja bulan ini mencapai {$data['persentase_jam_kerja']}%, sangat baik dan melebihi target.";
        } elseif ($data['persentase_jam_kerja'] >= 75) {
            $kesimpulan[] = "Jam kerja bulan ini mencapai {$data['persentase_jam_kerja']}%, sudah memenuhi target minimal.";
        } else {
            $kesimpulan[] = "Jam kerja bulan ini hanya mencapai {$data['persentase_jam_kerja']}%, perlu peningkatan di bulan berikutnya.";
        }

        // Kesimpulan hari kerja
        $kesimpulan[] = "Total hari kerja efektif adalah {$data['total_hari_kerja']} hari dengan akumulasi {$data['total_jam_kerja']} jam.";

        // Kesimpulan RHK
        $kesimpulan[] = "Terdapat {$data['total_rhk']} Rencana Hasil Kerja (RHK) yang dikerjakan pada bulan ini.";

        // Status keseluruhan
        $kesimpulan[] = "Status capaian kinerja: {$data['status_capaian']}.";

        return $kesimpulan;
    }

    /**
     * Helper: Get nama bulan in Bahasa Indonesia
     */
    private function getNamaBulan($bulan)
    {
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $namaBulan[$bulan] ?? '-';
    }

    /**
     * Helper: Build Rekap Kerja Harian Detail untuk tabel detail
     */
    private function buildRekapKerjaHarianDetail($progresHarianList, $asn)
    {
        $rekapDetail = [];

        foreach ($progresHarianList as $progres) {
            $jamKerja = substr($progres->jam_mulai, 0, 5) . ' - ' . substr($progres->jam_selesai, 0, 5);
            $jenisKegiatan = $progres->tipe_progres === 'TUGAS_ATASAN' ? 'TLA' : 'LKH';

            // Uraian kegiatan: gunakan tugas_atasan jika TLA, jika tidak gunakan rencana_kegiatan_harian
            $uraianKegiatan = $progres->rencana_kegiatan_harian;
            if ($progres->tipe_progres === 'TUGAS_ATASAN' && !empty($progres->tugas_atasan)) {
                $uraianKegiatan = $progres->tugas_atasan;
            }

            // Volume dengan satuan
            $volume = $progres->progres > 0 ? $progres->progres . ' ' . $progres->satuan : '-';

            $rekapDetail[] = [
                'id' => $progres->id,
                'tanggal' => $progres->tanggal,
                'nama_pegawai' => $asn->name,
                'nip' => $asn->nip ?? '-',
                'jam_kerja' => $jamKerja,
                'durasi_menit' => $progres->durasi_menit,
                'durasi_formatted' => floor($progres->durasi_menit / 60) . ' jam ' . ($progres->durasi_menit % 60) . ' menit',
                'uraian_kegiatan' => $uraianKegiatan,
                'volume' => $volume,
                'progres' => $progres->progres,
                'satuan' => $progres->satuan,
                'jenis_kegiatan' => $jenisKegiatan,
                'tipe_progres' => $progres->tipe_progres,
                'status_bukti' => $progres->status_bukti,
                'bukti_dukung' => $progres->bukti_dukung,
            ];
        }

        return $rekapDetail;
    }
}
