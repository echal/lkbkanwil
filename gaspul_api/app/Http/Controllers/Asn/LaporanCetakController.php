<?php

namespace App\Http\Controllers\Asn;

use App\Http\Controllers\Controller;
use App\Models\ProgresHarian;
use App\Models\RencanaAksiBulanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Laporan Cetak Controller - ASN
 *
 * Fitur:
 * - Cetak PDF Laporan Kinerja Harian (LKH)
 * - Cetak PDF Rekap Kinerja Bulanan
 * - Format dokumen resmi untuk arsip/audit
 */
class LaporanCetakController extends Controller
{
    /**
     * Cetak PDF Laporan Kinerja Harian (LKH)
     *
     * Format: Portrait A4
     * Output: PDF download
     */
    public function cetakHarian(Request $request)
    {
        $asn = Auth::user();

        // Get date parameter (default: today)
        $tanggal = $request->input('date', now()->format('Y-m-d'));
        $dateObj = Carbon::parse($tanggal);

        // Query progres harian untuk tanggal tertentu
        $progresHarian = ProgresHarian::where('user_id', $asn->id)
            ->whereDate('tanggal', $tanggal)
            ->with(['rencanaAksiBulanan.skpTahunanDetail.indikatorKinerja'])
            ->orderBy('jam_mulai')
            ->get();

        // Calculate totals
        $totalMenit = $progresHarian->sum('durasi_menit');
        $totalJam = $this->formatDurasi($totalMenit);
        $totalKH = $progresHarian->where('tipe_progres', 'KINERJA_HARIAN')->count();
        $totalTLA = $progresHarian->where('tipe_progres', 'TUGAS_ATASAN')->count();

        // Status calculation
        $hasEvidence = $progresHarian->where('status_bukti', 'SUDAH_ADA')->count() > 0;
        if ($totalMenit == 0) {
            $status = 'KOSONG';
            $statusColor = 'gray';
        } elseif (!$hasEvidence) {
            $status = 'BELUM UPLOAD BUKTI';
            $statusColor = 'red';
        } elseif ($totalMenit < 450) {
            $status = 'KURANG DARI 7.5 JAM';
            $statusColor = 'yellow';
        } else {
            $status = 'LENGKAP';
            $statusColor = 'green';
        }

        // Prepare data for view
        $data = [
            'asn' => $asn,
            'tanggal' => $dateObj->locale('id')->isoFormat('dddd, D MMMM Y'),
            'tanggal_short' => $dateObj->format('d-m-Y'),
            'progresHarian' => $progresHarian,
            'totalMenit' => $totalMenit,
            'totalJam' => $totalJam,
            'totalKH' => $totalKH,
            'totalTLA' => $totalTLA,
            'status' => $status,
            'statusColor' => $statusColor,
            'tanggal_cetak' => Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('asn.laporan.pdf.harian', $data);
        $pdf->setPaper('A4', 'portrait');

        // Download with filename
        $filename = "LKH_{$asn->name}_{$dateObj->format('Y-m-d')}_" . now()->format('YmdHis') . ".pdf";
        return $pdf->download($filename);
    }

    /**
     * Cetak PDF Rekap Kinerja Bulanan
     *
     * Format: Landscape A4 (karena banyak kolom)
     * Output: PDF download
     */
    public function cetakBulanan(Request $request)
    {
        $asn = Auth::user();

        // Get month and year parameters
        $bulan = $request->input('bulan', now()->month);
        $tahun = $request->input('tahun', now()->year);

        // Create date object
        $dateObj = Carbon::create($tahun, $bulan, 1);
        $namaBulan = $dateObj->locale('id')->isoFormat('MMMM Y');

        // Calculate date range for the month
        $startDate = $dateObj->copy()->startOfMonth();
        $endDate = $dateObj->copy()->endOfMonth();
        $totalHariInMonth = $dateObj->daysInMonth;

        // Query all progres harian for the month
        $progresHarian = ProgresHarian::where('user_id', $asn->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->with(['rencanaAksiBulanan.skpTahunanDetail.indikatorKinerja'])
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();

        // Group by date
        $progresPerHari = $progresHarian->groupBy(function($item) {
            return $item->tanggal->format('Y-m-d');
        });

        // Build calendar data (setiap hari dalam bulan)
        $rekapPerHari = [];
        for ($day = 1; $day <= $totalHariInMonth; $day++) {
            $currentDate = Carbon::create($tahun, $bulan, $day);
            $dateKey = $currentDate->format('Y-m-d');

            if (isset($progresPerHari[$dateKey])) {
                $dayProgres = $progresPerHari[$dateKey];
                $totalMenit = $dayProgres->sum('durasi_menit');
                $hasEvidence = $dayProgres->where('status_bukti', 'SUDAH_ADA')->count() > 0;
                $countKH = $dayProgres->where('tipe_progres', 'KINERJA_HARIAN')->count();
                $countTLA = $dayProgres->where('tipe_progres', 'TUGAS_ATASAN')->count();

                // Status logic
                if (!$hasEvidence) {
                    $status = 'RED';
                } elseif ($totalMenit < 450) {
                    $status = 'YELLOW';
                } else {
                    $status = 'GREEN';
                }

                $rekapPerHari[] = [
                    'tanggal' => $currentDate->format('d'),
                    'hari' => $currentDate->locale('id')->isoFormat('ddd'),
                    'total_menit' => $totalMenit,
                    'total_jam' => $this->formatDurasi($totalMenit),
                    'count_kh' => $countKH,
                    'count_tla' => $countTLA,
                    'status' => $status,
                ];
            } else {
                $rekapPerHari[] = [
                    'tanggal' => $currentDate->format('d'),
                    'hari' => $currentDate->locale('id')->isoFormat('ddd'),
                    'total_menit' => 0,
                    'total_jam' => '0j 0m',
                    'count_kh' => 0,
                    'count_tla' => 0,
                    'status' => 'EMPTY',
                ];
            }
        }

        // Calculate monthly summary
        $totalMenitBulan = $progresHarian->sum('durasi_menit');
        $totalKHBulan = $progresHarian->where('tipe_progres', 'KINERJA_HARIAN')->count();
        $totalTLABulan = $progresHarian->where('tipe_progres', 'TUGAS_ATASAN')->count();
        $hariKerja = $progresPerHari->count();
        $hariKosong = $totalHariInMonth - $hariKerja;
        $avgJamPerHari = $hariKerja > 0 ? round($totalMenitBulan / $hariKerja / 60, 1) : 0;

        // Count status distribution
        $hariGreen = collect($rekapPerHari)->where('status', 'GREEN')->count();
        $hariYellow = collect($rekapPerHari)->where('status', 'YELLOW')->count();
        $hariRed = collect($rekapPerHari)->where('status', 'RED')->count();

        // Query Rencana Aksi Bulanan untuk konteks
        $rencanaAksi = RencanaAksiBulanan::whereHas('skpTahunanDetail.skpTahunan', function($q) use ($asn, $tahun) {
                $q->where('user_id', $asn->id)
                  ->where('tahun', $tahun);
            })
            ->with(['skpTahunanDetail.indikatorKinerja'])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotNull('rencana_aksi_bulanan')
            ->get();

        // Prepare data for view
        $data = [
            'asn' => $asn,
            'periode' => $namaBulan,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'rekapPerHari' => $rekapPerHari,
            'rencanaAksi' => $rencanaAksi,
            'summary' => [
                'total_hari' => $totalHariInMonth,
                'hari_kerja' => $hariKerja,
                'hari_kosong' => $hariKosong,
                'total_menit' => $totalMenitBulan,
                'total_jam' => $this->formatDurasi($totalMenitBulan),
                'total_kh' => $totalKHBulan,
                'total_tla' => $totalTLABulan,
                'avg_jam_per_hari' => $avgJamPerHari,
                'hari_green' => $hariGreen,
                'hari_yellow' => $hariYellow,
                'hari_red' => $hariRed,
            ],
            'tanggal_cetak' => Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('asn.laporan.pdf.bulanan', $data);
        $pdf->setPaper('A4', 'landscape'); // Landscape karena banyak kolom

        // Download with filename
        $filename = "Rekap_Bulanan_{$asn->name}_{$namaBulan}_{$tahun}_" . now()->format('YmdHis') . ".pdf";
        return $pdf->download($filename);
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Format durasi menit ke format "Xj Ym"
     */
    private function formatDurasi($menit)
    {
        if (!$menit || $menit == 0) return '0j 0m';

        $jam = floor($menit / 60);
        $sisa_menit = $menit % 60;

        return "{$jam}j {$sisa_menit}m";
    }
}
