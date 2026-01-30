<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dashboard Atasan - Monitoring Harian Bawahan
 *
 * Performance Target: < 200ms untuk 250 ASN
 *
 * Features:
 * - Monitoring progres harian bawahan (KH + TLA)
 * - Filter: Harian, Mingguan, Bulanan
 * - Status 3 warna: Merah/Kuning/Hijau
 * - Detail drill-down per ASN
 * - Cetak LKH dan TLA
 */
class HarianBawahanController extends Controller
{
    /**
     * Dashboard monitoring - Tabel per ASN
     */
    public function index(Request $request)
    {
        $atasan = Auth::user();

        // Validasi role
        if ($atasan->role !== 'ATASAN') {
            abort(403, 'Unauthorized');
        }

        // Filter parameters
        $mode = $request->input('mode', 'harian'); // harian|mingguan|bulanan
        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));

        // Get bawahan by unit_kerja_id (SECURITY: unit-based filtering)
        $bawahan_list = $this->getBawahanWithStatus($atasan->unit_kerja_id, $tanggal, $mode);

        return view('atasan.harian-bawahan.index', compact('atasan', 'bawahan_list', 'mode', 'tanggal'));
    }

    /**
     * Detail ASN - Drill down
     */
    public function detail(Request $request, $user_id)
    {
        $atasan = Auth::user();

        // Security check
        $asn = DB::table('users')
            ->where('id', $user_id)
            ->where('unit_kerja_id', $atasan->unit_kerja_id) // Same unit only
            ->first();

        if (!$asn) {
            abort(404, 'ASN tidak ditemukan atau bukan bawahan Anda');
        }

        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));
        $mode = $request->input('mode', 'harian');

        // Get progres detail
        $progres_list = $this->getProgresDetail($user_id, $tanggal, $mode);

        return view('atasan.harian-bawahan.detail', compact('asn', 'progres_list', 'tanggal', 'mode'));
    }

    /**
     * Cetak LKH (Laporan Kinerja Harian)
     */
    public function cetakLKH($user_id, $tanggal)
    {
        $atasan = Auth::user();

        // Security check
        $asn = DB::table('users')
            ->where('id', $user_id)
            ->where('unit_kerja_id', $atasan->unit_kerja_id)
            ->first();

        if (!$asn) {
            abort(404);
        }

        // Get LKH data
        $data_lkh = DB::table('progres_harian')
            ->where('user_id', $user_id)
            ->where('tipe_progres', 'KINERJA_HARIAN')
            ->whereDate('tanggal', $tanggal)
            ->get();

        return view('atasan.harian-bawahan.cetak-lkh', compact('asn', 'data_lkh', 'tanggal', 'atasan'));
    }

    /**
     * Cetak TLA (Tugas Langsung Atasan)
     */
    public function cetakTLA($user_id, $tanggal)
    {
        $atasan = Auth::user();

        // Security check
        $asn = DB::table('users')
            ->where('id', $user_id)
            ->where('unit_kerja_id', $atasan->unit_kerja_id)
            ->first();

        if (!$asn) {
            abort(404);
        }

        // Get TLA data
        $data_tla = DB::table('progres_harian')
            ->where('user_id', $user_id)
            ->where('tipe_progres', 'TUGAS_ATASAN')
            ->whereDate('tanggal', $tanggal)
            ->get();

        return view('atasan.harian-bawahan.cetak-tla', compact('asn', 'data_tla', 'tanggal', 'atasan'));
    }

    // ========================================================================
    // PRIVATE METHODS - OPTIMIZED QUERIES
    // ========================================================================

    /**
     * Get bawahan with status (OPTIMIZED - Single Query)
     *
     * Uses: JOIN + GROUP BY + SUM + COUNT(CASE)
     * NO whereHas nesting!
     */
    private function getBawahanWithStatus($unit_kerja_id, $tanggal, $mode)
    {
        // Build date range
        list($start_date, $end_date) = $this->getDateRange($tanggal, $mode);

        // ⚡ OPTIMIZED QUERY - Single query untuk semua ASN
        $result = DB::table('users as u')
            ->leftJoin('progres_harian as ph', function($join) use ($start_date, $end_date) {
                $join->on('u.id', '=', 'ph.user_id')
                     ->whereBetween('ph.tanggal', [$start_date, $end_date]);
            })
            ->where('u.unit_kerja_id', $unit_kerja_id)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->select(
                'u.id',
                'u.name',
                'u.nip',
                DB::raw('SUM(ph.durasi_menit) as total_menit'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "KINERJA_HARIAN" THEN 1 END) as total_kh'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "TUGAS_ATASAN" THEN 1 END) as total_tla'),
                DB::raw('COUNT(ph.id) as total_progres'),
                DB::raw('SUM(CASE WHEN ph.status_bukti = "BELUM_ADA" OR ph.status_bukti IS NULL THEN 1 ELSE 0 END) as total_tanpa_bukti')
            )
            ->groupBy('u.id', 'u.name', 'u.nip')
            ->orderBy('u.name')
            ->get();

        // Calculate status for each ASN
        return $result->map(function($asn) {
            $asn->total_jam = $this->formatDurasi($asn->total_menit);
            $asn->status = $this->calculateStatus($asn->total_menit, $asn->total_progres, $asn->total_tanpa_bukti);
            $asn->status_badge = $this->getStatusBadge($asn->status);
            return $asn;
        });
    }

    /**
     * Get progres detail for ASN
     */
    private function getProgresDetail($user_id, $tanggal, $mode)
    {
        list($start_date, $end_date) = $this->getDateRange($tanggal, $mode);

        return DB::table('progres_harian')
            ->where('user_id', $user_id)
            ->whereBetween('tanggal', [$start_date, $end_date])
            ->select(
                'id',
                'tanggal',
                'jam_mulai',
                'jam_selesai',
                'durasi_menit',
                'tipe_progres',
                'rencana_kegiatan_harian',
                'tugas_atasan',
                'progres',
                'satuan',
                'bukti_dukung',
                'status_bukti'
            )
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();
    }

    /**
     * Calculate date range based on mode
     */
    private function getDateRange($tanggal, $mode)
    {
        $date = Carbon::parse($tanggal);

        switch ($mode) {
            case 'mingguan':
                return [$date->copy()->startOfWeek()->toDateString(), $date->copy()->endOfWeek()->toDateString()];
            case 'bulanan':
                return [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()];
            default: // harian
                return [$tanggal, $tanggal];
        }
    }

    /**
     * Calculate status based on rules:
     * 🔴 MERAH = Tidak ada progres sama sekali
     * 🟡 KUNING = Ada progres tapi < 450 menit ATAU ada yang tanpa bukti
     * 🟢 HIJAU = >= 450 menit DAN semua ada bukti
     */
    private function calculateStatus($total_menit, $total_progres, $total_tanpa_bukti)
    {
        // MERAH: Tidak ada progres
        if ($total_progres == 0) {
            return 'MERAH';
        }

        // HIJAU: >= 450 menit DAN semua ada bukti
        if ($total_menit >= 450 && $total_tanpa_bukti == 0) {
            return 'HIJAU';
        }

        // KUNING: Sisanya
        return 'KUNING';
    }

    /**
     * Get status badge HTML
     */
    private function getStatusBadge($status)
    {
        switch ($status) {
            case 'HIJAU':
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">🟢 HIJAU</span>';
            case 'KUNING':
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">🟡 KUNING</span>';
            default: // MERAH
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">🔴 MERAH</span>';
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
