<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WorkingTimeService;
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

        // Get rekap data berdasarkan atasan_id
        $rekap_list = $this->getRekapMingguan($atasan->id, $week, $year);

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

        // Get rekap data berdasarkan atasan_id
        $rekap_list = $this->getRekapBulanan($atasan->id, $month, $year);

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
     * Get rekap mingguan — dynamic target per pola kerja ASN.
     * SQL subquery >= 450 diganti PHP loop via WorkingTimeService::getTargetMenitByDate().
     */
    private function getRekapMingguan($atasan_id, $week, $year)
    {
        $startDate = Carbon::now()->setISODate($year, $week)->startOfWeek();
        $endDate   = Carbon::now()->setISODate($year, $week)->endOfWeek();

        // Query agregat ringan — tanpa subquery 450
        $rows = DB::table('users as u')
            ->leftJoin('progres_harian as ph', function ($join) use ($startDate, $endDate) {
                $join->on('u.id', '=', 'ph.user_id')
                     ->whereBetween('ph.tanggal', [$startDate->toDateString(), $endDate->toDateString()]);
            })
            ->where('u.atasan_id', $atasan_id)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->select(
                'u.id', 'u.name', 'u.nip', 'u.hari_kerja as pola_kerja', 'u.unit_kerja_id',
                DB::raw('SUM(ph.durasi_menit) as total_menit'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "KINERJA_HARIAN" THEN 1 END) as total_kh'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "TUGAS_ATASAN" THEN 1 END) as total_tla')
            )
            ->groupBy('u.id', 'u.name', 'u.nip', 'u.hari_kerja', 'u.unit_kerja_id')
            ->orderBy('u.name')
            ->get();

        // Progres per tanggal per user — 1 query untuk semua ASN
        $progresPerUser = DB::table('progres_harian')
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('user_id', $rows->pluck('id'))
            ->select('user_id', 'tanggal', DB::raw('SUM(durasi_menit) as total'))
            ->groupBy('user_id', 'tanggal')
            ->get()
            ->groupBy('user_id');

        // Load unit kerja untuk cascade pola kerja
        $unitKerjaMap = DB::table('unit_kerja')
            ->whereIn('id', $rows->pluck('unit_kerja_id')->filter()->unique())
            ->pluck('hari_kerja', 'id');

        return $rows->map(function ($asn) use ($startDate, $endDate, $progresPerUser, $unitKerjaMap) {
            // Resolve pola kerja (cascade: user → unit_kerja → SENIN_JUMAT)
            $valid = ['SENIN_JUMAT', 'SENIN_SABTU'];
            $pola  = in_array($asn->pola_kerja, $valid) ? $asn->pola_kerja
                   : (in_array($unitKerjaMap[$asn->unit_kerja_id] ?? null, $valid) ? $unitKerjaMap[$asn->unit_kerja_id] : 'SENIN_JUMAT');

            // Buat user-like object untuk WorkingTimeService
            $userObj             = new \stdClass();
            $userObj->hari_kerja = $pola;
            $userObj->unitKerja  = null;

            // Hitung hari lengkap/tidak per tanggal dengan target dinamis
            $hariLengkap      = 0;
            $hariTidakLengkap = 0;
            $hariKerja        = 0;

            $userProgres = $progresPerUser->get($asn->id, collect());
            $progresMap  = $userProgres->keyBy('tanggal');

            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                $targetMenit = WorkingTimeService::getTargetMenitByDate($current, $userObj);
                if ($targetMenit === 0) {
                    $current->addDay();
                    continue; // libur / bukan hari kerja
                }

                $hariKerja++;
                $dateKey    = $current->toDateString();
                $totalHari  = (int) ($progresMap->get($dateKey)->total ?? 0);

                if ($totalHari >= $targetMenit) {
                    $hariLengkap++;
                } else {
                    $hariTidakLengkap++;
                }

                $current->addDay();
            }

            $asn->hari_kerja        = $hariKerja;
            $asn->hari_lengkap      = $hariLengkap;
            $asn->hari_tidak_lengkap = $hariTidakLengkap;
            $asn->hari_kosong       = max(0, $hariKerja - ($hariLengkap + $hariTidakLengkap));
            $asn->total_jam         = $this->formatDurasi((int) $asn->total_menit);
            $asn->avg_jam_per_hari  = $hariKerja > 0 ? round($asn->total_menit / $hariKerja / 60, 1) : 0;

            $persen_lengkap = $hariKerja > 0 ? ($hariLengkap / $hariKerja) * 100 : 0;
            $asn->status    = $this->calculateStatusRekap($persen_lengkap);

            return $asn;
        });
    }

    /**
     * Get rekap bulanan — dynamic target per pola kerja ASN.
     * SQL subquery >= 450 diganti PHP loop via WorkingTimeService::getTargetMenitByDate().
     */
    private function getRekapBulanan($atasan_id, $month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate   = Carbon::create($year, $month, 1)->endOfMonth();

        // Query agregat ringan — tanpa subquery 450
        $rows = DB::table('users as u')
            ->leftJoin('progres_harian as ph', function ($join) use ($startDate, $endDate) {
                $join->on('u.id', '=', 'ph.user_id')
                     ->whereBetween('ph.tanggal', [$startDate->toDateString(), $endDate->toDateString()]);
            })
            ->where('u.atasan_id', $atasan_id)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->select(
                'u.id', 'u.name', 'u.nip', 'u.hari_kerja as pola_kerja', 'u.unit_kerja_id',
                DB::raw('SUM(ph.durasi_menit) as total_menit'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "KINERJA_HARIAN" THEN 1 END) as total_kh'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "TUGAS_ATASAN" THEN 1 END) as total_tla')
            )
            ->groupBy('u.id', 'u.name', 'u.nip', 'u.hari_kerja', 'u.unit_kerja_id')
            ->orderBy('u.name')
            ->get();

        // Progres per tanggal per user — 1 query untuk semua ASN
        $progresPerUser = DB::table('progres_harian')
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('user_id', $rows->pluck('id'))
            ->select('user_id', 'tanggal', DB::raw('SUM(durasi_menit) as total'))
            ->groupBy('user_id', 'tanggal')
            ->get()
            ->groupBy('user_id');

        // Load unit kerja untuk cascade pola kerja
        $unitKerjaMap = DB::table('unit_kerja')
            ->whereIn('id', $rows->pluck('unit_kerja_id')->filter()->unique())
            ->pluck('hari_kerja', 'id');

        return $rows->map(function ($asn) use ($startDate, $endDate, $progresPerUser, $unitKerjaMap) {
            // Resolve pola kerja (cascade: user → unit_kerja → SENIN_JUMAT)
            $valid = ['SENIN_JUMAT', 'SENIN_SABTU'];
            $pola  = in_array($asn->pola_kerja, $valid) ? $asn->pola_kerja
                   : (in_array($unitKerjaMap[$asn->unit_kerja_id] ?? null, $valid) ? $unitKerjaMap[$asn->unit_kerja_id] : 'SENIN_JUMAT');

            $userObj             = new \stdClass();
            $userObj->hari_kerja = $pola;
            $userObj->unitKerja  = null;

            // Hitung hari lengkap/tidak per tanggal dengan target dinamis
            $hariLengkap      = 0;
            $hariTidakLengkap = 0;
            $hariKerja        = 0;

            $userProgres = $progresPerUser->get($asn->id, collect());
            $progresMap  = $userProgres->keyBy('tanggal');

            $current = $startDate->copy();
            while ($current->lte($endDate)) {
                $targetMenit = WorkingTimeService::getTargetMenitByDate($current, $userObj);
                if ($targetMenit === 0) {
                    $current->addDay();
                    continue; // libur / bukan hari kerja
                }

                $hariKerja++;
                $dateKey   = $current->toDateString();
                $totalHari = (int) ($progresMap->get($dateKey)->total ?? 0);

                if ($totalHari >= $targetMenit) {
                    $hariLengkap++;
                } else {
                    $hariTidakLengkap++;
                }

                $current->addDay();
            }

            $asn->hari_kerja         = $hariKerja;
            $asn->hari_lengkap       = $hariLengkap;
            $asn->hari_tidak_lengkap = $hariTidakLengkap;
            $asn->hari_kosong        = max(0, $hariKerja - ($hariLengkap + $hariTidakLengkap));
            $asn->total_jam          = $this->formatDurasi((int) $asn->total_menit);
            $asn->avg_jam_per_hari   = $hariKerja > 0 ? round($asn->total_menit / $hariKerja / 60, 1) : 0;

            // TODO PHASE 6: snapshot target_menit_bulanan saat approval
            // agar histori laporan yang sudah DISETUJUI tidak berubah retroaktif
            // jika pola kerja ASN diubah di masa depan.
            $persen_lengkap = $hariKerja > 0 ? ($hariLengkap / $hariKerja) * 100 : 0;
            $asn->status    = $this->calculateStatusRekap($persen_lengkap);

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
