<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Dashboard Atasan - Cetak PDF Rekap Kinerja
 *
 * Performance Target: < 2 detik untuk 250 ASN
 *
 * Features:
 * - Cetak PDF Rekap Mingguan
 * - Cetak PDF Rekap Bulanan
 * - Format dokumen resmi ASN
 * - Tanda tangan atasan
 */
class RekapKinerjaCetakController extends Controller
{
    /**
     * Cetak PDF Rekap Mingguan
     */
    public function cetakMingguan(Request $request)
    {
        $atasan = Auth::user();

        // Validasi role
        if ($atasan->role !== 'ATASAN') {
            abort(403, 'Unauthorized');
        }

        // Get parameters
        $week = $request->input('week', now()->week);
        $year = $request->input('year', now()->year);

        // Calculate date range for the week
        $startDate = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $endDate = Carbon::now()->setISODate($year, $week)->endOfWeek();

        // Get rekap data (reuse query from TAHAP 5.2)
        $rekap_list = $this->getRekapMingguan($atasan->unit_kerja_id, $week, $year);

        // Calculate summary statistics
        $summary = $this->calculateSummary($rekap_list);

        // Prepare data for view
        $data = [
            'atasan' => $atasan,
            'rekap_list' => $rekap_list,
            'summary' => $summary,
            'periode' => "Minggu ke-{$week} Tahun {$year}",
            'tanggal_range' => $startDate->locale('id')->isoFormat('D MMM') . ' - ' . $endDate->locale('id')->isoFormat('D MMM Y'),
            'tanggal_cetak' => Carbon::now()->locale('id')->isoFormat('D MMMM Y'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('atasan.rekap.pdf.mingguan', $data);
        $pdf->setPaper('A4', 'portrait');

        // Download with filename
        $filename = "Rekap_Mingguan_W{$week}_{$year}_" . now()->format('YmdHis') . ".pdf";
        return $pdf->download($filename);
    }

    /**
     * Cetak PDF Rekap Bulanan
     */
    public function cetakBulanan(Request $request)
    {
        $atasan = Auth::user();

        // Validasi role
        if ($atasan->role !== 'ATASAN') {
            abort(403, 'Unauthorized');
        }

        // Get parameters
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Get month name
        $monthName = Carbon::create($year, $month, 1)->locale('id')->isoFormat('MMMM');

        // Get rekap data (reuse query from TAHAP 5.2)
        $rekap_list = $this->getRekapBulanan($atasan->unit_kerja_id, $month, $year);

        // Calculate summary statistics
        $summary = $this->calculateSummary($rekap_list);

        // Prepare data for view
        $data = [
            'atasan' => $atasan,
            'rekap_list' => $rekap_list,
            'summary' => $summary,
            'periode' => "{$monthName} {$year}",
            'tanggal_cetak' => Carbon::now()->locale('id')->isoFormat('D MMMM Y'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('atasan.rekap.pdf.bulanan', $data);
        $pdf->setPaper('A4', 'portrait');

        // Download with filename
        $filename = "Rekap_Bulanan_{$monthName}_{$year}_" . now()->format('YmdHis') . ".pdf";
        return $pdf->download($filename);
    }

    // ========================================================================
    // PRIVATE METHODS - REUSE FROM TAHAP 5.2
    // ========================================================================

    /**
     * Get rekap mingguan (REUSE from RekapKinerjaController)
     */
    private function getRekapMingguan($unit_kerja_id, $week, $year)
    {
        // Calculate date range for the week (ISO 8601 week)
        $startDate = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $endDate = Carbon::now()->setISODate($year, $week)->endOfWeek();

        // Total days in week (usually 7, but could be less at year boundaries)
        $totalDaysInWeek = $startDate->diffInDays($endDate) + 1;

        // ⚡ OPTIMIZED QUERY - Single query untuk semua ASN
        $result = DB::table('users as u')
            ->leftJoin('progres_harian as ph', function($join) use ($startDate, $endDate) {
                $join->on('u.id', '=', 'ph.user_id')
                     ->whereBetween('ph.tanggal', [$startDate->toDateString(), $endDate->toDateString()]);
            })
            ->where('u.unit_kerja_id', $unit_kerja_id)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->select(
                'u.id',
                'u.name',
                'u.nip',
                DB::raw('COUNT(DISTINCT DATE(ph.tanggal)) as hari_kerja'),
                DB::raw('SUM(ph.durasi_menit) as total_menit'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "KINERJA_HARIAN" THEN 1 END) as total_kh'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "TUGAS_ATASAN" THEN 1 END) as total_tla'),
                DB::raw('COUNT(DISTINCT CASE WHEN ph.tanggal IS NOT NULL AND (SELECT SUM(durasi_menit) FROM progres_harian WHERE user_id = u.id AND tanggal = ph.tanggal) >= 450 THEN DATE(ph.tanggal) END) as hari_lengkap'),
                DB::raw('COUNT(DISTINCT CASE WHEN ph.tanggal IS NOT NULL AND (SELECT SUM(durasi_menit) FROM progres_harian WHERE user_id = u.id AND tanggal = ph.tanggal) < 450 THEN DATE(ph.tanggal) END) as hari_tidak_lengkap')
            )
            ->groupBy('u.id', 'u.name', 'u.nip')
            ->orderBy('u.name')
            ->get();

        // Calculate derived metrics
        return $result->map(function($asn) use ($totalDaysInWeek) {
            $asn->total_jam = $this->formatDurasi($asn->total_menit);
            $asn->avg_jam_per_hari = $asn->hari_kerja > 0 ? round($asn->total_menit / $asn->hari_kerja / 60, 1) : 0;
            $asn->hari_kosong = $totalDaysInWeek - $asn->hari_kerja;

            // Calculate status based on % hari lengkap
            $persen_lengkap = $totalDaysInWeek > 0 ? ($asn->hari_lengkap / $totalDaysInWeek) * 100 : 0;
            $asn->status = $this->calculateStatusRekap($persen_lengkap);

            return $asn;
        });
    }

    /**
     * Get rekap bulanan (REUSE from RekapKinerjaController)
     */
    private function getRekapBulanan($unit_kerja_id, $month, $year)
    {
        // Calculate date range for the month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Total days in month
        $totalDaysInMonth = $startDate->daysInMonth;

        // ⚡ OPTIMIZED QUERY - Single query untuk semua ASN
        $result = DB::table('users as u')
            ->leftJoin('progres_harian as ph', function($join) use ($startDate, $endDate) {
                $join->on('u.id', '=', 'ph.user_id')
                     ->whereBetween('ph.tanggal', [$startDate->toDateString(), $endDate->toDateString()]);
            })
            ->where('u.unit_kerja_id', $unit_kerja_id)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->select(
                'u.id',
                'u.name',
                'u.nip',
                DB::raw('COUNT(DISTINCT DATE(ph.tanggal)) as hari_kerja'),
                DB::raw('SUM(ph.durasi_menit) as total_menit'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "KINERJA_HARIAN" THEN 1 END) as total_kh'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "TUGAS_ATASAN" THEN 1 END) as total_tla'),
                DB::raw('COUNT(DISTINCT CASE WHEN ph.tanggal IS NOT NULL AND (SELECT SUM(durasi_menit) FROM progres_harian WHERE user_id = u.id AND tanggal = ph.tanggal) >= 450 THEN DATE(ph.tanggal) END) as hari_lengkap'),
                DB::raw('COUNT(DISTINCT CASE WHEN ph.tanggal IS NOT NULL AND (SELECT SUM(durasi_menit) FROM progres_harian WHERE user_id = u.id AND tanggal = ph.tanggal) < 450 THEN DATE(ph.tanggal) END) as hari_tidak_lengkap')
            )
            ->groupBy('u.id', 'u.name', 'u.nip')
            ->orderBy('u.name')
            ->get();

        // Calculate derived metrics
        return $result->map(function($asn) use ($totalDaysInMonth) {
            $asn->total_jam = $this->formatDurasi($asn->total_menit);
            $asn->avg_jam_per_hari = $asn->hari_kerja > 0 ? round($asn->total_menit / $asn->hari_kerja / 60, 1) : 0;
            $asn->hari_kosong = $totalDaysInMonth - $asn->hari_kerja;

            // Calculate status based on % hari lengkap
            $persen_lengkap = $totalDaysInMonth > 0 ? ($asn->hari_lengkap / $totalDaysInMonth) * 100 : 0;
            $asn->status = $this->calculateStatusRekap($persen_lengkap);

            return $asn;
        });
    }

    /**
     * Calculate summary statistics
     */
    private function calculateSummary($rekap_list)
    {
        $total_asn = $rekap_list->count();
        $asn_baik = $rekap_list->where('status', 'BAIK')->count();
        $asn_perlu_perhatian = $rekap_list->where('status', 'PERLU_PERHATIAN')->count();
        $asn_buruk = $rekap_list->where('status', 'BURUK')->count();

        // Calculate average jam kerja
        $total_menit = $rekap_list->sum('total_menit');
        $total_hari_kerja = $rekap_list->sum('hari_kerja');
        $avg_jam = $total_hari_kerja > 0 ? round($total_menit / $total_hari_kerja / 60, 1) : 0;

        return [
            'total_asn' => $total_asn,
            'asn_baik' => $asn_baik,
            'asn_perlu_perhatian' => $asn_perlu_perhatian,
            'asn_buruk' => $asn_buruk,
            'avg_jam_unit' => $avg_jam,
        ];
    }

    /**
     * Calculate status based on % hari lengkap:
     * 🟢 BAIK = >= 80% hari lengkap
     * 🟡 PERLU_PERHATIAN = 50-79% hari lengkap
     * 🔴 BURUK = < 50% hari lengkap
     */
    private function calculateStatusRekap($persen_lengkap)
    {
        if ($persen_lengkap >= 80) {
            return 'BAIK';
        } elseif ($persen_lengkap >= 50) {
            return 'PERLU_PERHATIAN';
        } else {
            return 'BURUK';
        }
    }

    /**
     * Format durasi to hours and minutes
     */
    private function formatDurasi($menit)
    {
        if (!$menit) return '0j 0m';

        $jam = floor($menit / 60);
        $sisa_menit = $menit % 60;

        return "{$jam}j {$sisa_menit}m";
    }
}
