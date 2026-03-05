<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MonitoringKakanwilController extends Controller
{
    private const CACHE_KEY = 'monitoring_kakanwil';
    private const CACHE_TTL = 300; // 5 menit

    public function clearCache(Request $request)
    {
        $key = $request->query('key');
        if ($key !== config('app.kakanwil_monitor_key') || empty($key)) {
            abort(403, 'Akses ditolak.');
        }

        // Hapus semua cache monitoring (semua tahun)
        foreach (range(now()->year - 2, now()->year + 1) as $y) {
            Cache::forget(self::CACHE_KEY . '_' . $y);
        }

        return redirect()->route('monitoring.kakanwil', ['key' => $key])
            ->with('info', 'Cache monitoring berhasil di-refresh.');
    }

    public function index(Request $request)
    {
        // ====================================================================
        // TOKEN GUARD — tidak perlu login, tapi butuh key yang benar
        // ====================================================================
        $key = $request->query('key');
        if ($key !== config('app.kakanwil_monitor_key') || empty($key)) {
            abort(403, 'Akses ditolak.');
        }

        $tahun = (int) $request->query('tahun', now()->year);

        // Cache key per tahun
        $cacheKey = self::CACHE_KEY . '_' . $tahun;

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tahun) {
            return $this->buildMonitoringData($tahun);
        });

        return view('monitoring.kakanwil', [
            'data'       => $data,
            'tahun'      => $tahun,
            'monitorKey' => config('app.kakanwil_monitor_key'),
            'lastUpdate' => now()->format('d M Y, H:i:s') . ' WIB',
        ]);
    }

    // =========================================================================
    // BUILD DATA
    // =========================================================================

    private function buildMonitoringData(int $tahun): array
    {
        $totalAsn        = $this->getTotalAsn();
        $statusDist      = $this->getStatusDistribution($tahun);
        $sudahBuatSkp    = array_sum(array_values($statusDist));
        $sudahDiajukan   = ($statusDist['DIAJUKAN'] ?? 0) + ($statusDist['DISETUJUI'] ?? 0);
        $sudahDisetujui  = $statusDist['DISETUJUI'] ?? 0;
        $belumBuat       = max(0, $totalAsn - $sudahBuatSkp);

        $persenKepatuhan = $totalAsn > 0
            ? round(($sudahDisetujui / $totalAsn) * 100, 1)
            : 0;

        return [
            'kpi' => [
                'total_asn'        => $totalAsn,
                'sudah_buat_skp'   => $sudahBuatSkp,
                'sudah_diajukan'   => $sudahDiajukan,
                'sudah_disetujui'  => $sudahDisetujui,
                'belum_buat'       => $belumBuat,
                'persen_kepatuhan' => $persenKepatuhan,
                'warna_kepatuhan'  => $this->warnaKepatuhan($persenKepatuhan),
            ],
            'status_distribution' => $statusDist,
            'per_unit'            => $this->getPerUnit($tahun),
            'ranking_top'         => $this->getRanking($tahun, 'top'),
            'ranking_bottom'      => $this->getRanking($tahun, 'bottom'),
            'tahun'               => $tahun,
        ];
    }

    // =========================================================================
    // QUERIES — hanya agregat, TIDAK ada nama/NIP individu
    // =========================================================================

    private function getTotalAsn(): int
    {
        return DB::table('users')
            ->where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->count();
    }

    /**
     * Status distribution SKP tahun ini.
     * Return: ['DRAFT' => n, 'DIAJUKAN' => n, 'DISETUJUI' => n, 'DITOLAK' => n]
     */
    private function getStatusDistribution(int $tahun): array
    {
        $rows = DB::table('skp_tahunan')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->where('tahun', $tahun)
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return array_merge(
            ['DRAFT' => 0, 'DIAJUKAN' => 0, 'DISETUJUI' => 0, 'DITOLAK' => 0],
            $rows
        );
    }

    /**
     * Data per unit kerja — agregat saja, tanpa nama individu.
     */
    private function getPerUnit(int $tahun): array
    {
        // Total ASN aktif per unit
        $asnPerUnit = DB::table('users as u')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id', 'uk.nama_unit', DB::raw('COUNT(u.id) as total_asn'))
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id', 'uk.nama_unit')
            ->get()
            ->keyBy('id');

        // SKP disetujui per unit
        $disetujuiPerUnit = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id', DB::raw('COUNT(s.id) as total_disetujui'))
            ->where('s.tahun', $tahun)
            ->where('s.status', 'DISETUJUI')
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id')
            ->get()
            ->keyBy('id');

        // SKP sudah buat (semua status) per unit
        $buatPerUnit = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id', DB::raw('COUNT(s.id) as total_buat'))
            ->where('s.tahun', $tahun)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id')
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($asnPerUnit as $unitId => $unit) {
            $totalAsn       = (int) $unit->total_asn;
            $totalDisetujui = (int) ($disetujuiPerUnit[$unitId]->total_disetujui ?? 0);
            $totalBuat      = (int) ($buatPerUnit[$unitId]->total_buat ?? 0);
            $persen         = $totalAsn > 0 ? round(($totalDisetujui / $totalAsn) * 100, 1) : 0;

            $result[] = [
                'nama_unit'       => $unit->nama_unit,
                'total_asn'       => $totalAsn,
                'total_buat'      => $totalBuat,
                'total_disetujui' => $totalDisetujui,
                'belum_buat'      => max(0, $totalAsn - $totalBuat),
                'persen'          => $persen,
                'warna'           => $this->warnaKepatuhan($persen),
            ];
        }

        // Urutkan: kepatuhan DESC → total_asn DESC → nama_unit ASC
        usort($result, function ($a, $b) {
            if ($b['persen'] !== $a['persen']) {
                return $b['persen'] <=> $a['persen'];
            }
            if ($b['total_asn'] !== $a['total_asn']) {
                return $b['total_asn'] <=> $a['total_asn'];
            }
            return strcmp($a['nama_unit'], $b['nama_unit']);
        });

        return $result;
    }

    /**
     * Ranking top/bottom 5 unit berdasarkan persentase kepatuhan.
     */
    private function getRanking(int $tahun, string $type): array
    {
        $perUnit = $this->getPerUnit($tahun);

        // Hanya unit yang punya minimal 1 ASN
        $filtered = array_filter($perUnit, fn($u) => $u['total_asn'] > 0);

        // Sort: top = DESC, bottom = ASC
        usort($filtered, fn($a, $b) =>
            $type === 'top'
                ? $b['persen'] <=> $a['persen']
                : $a['persen'] <=> $b['persen']
        );

        return array_slice(array_values($filtered), 0, 5);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function warnaKepatuhan(float $persen): string
    {
        if ($persen >= 85) return 'hijau';
        if ($persen >= 70) return 'kuning';
        return 'merah';
    }
}
