<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MonitoringPasangkayuController extends Controller
{
    private const CACHE_KEY = 'monitoring_pasangkayu';
    private const CACHE_TTL = 300; // 5 menit

    // ID unit kerja lingkup Kabupaten Pasangkayu
    // id=18 (Kankemenag Pasangkayu) + semua child (parent_id=18) + MAN/MTsN Pasangkayu
    private const UNIT_IDS = [18, 24, 25, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44];

    public function index(Request $request)
    {
        $token = $request->query('token');
        if (empty($token) || $token !== config('app.pasangkayu_tv_token')) {
            abort(403, 'Akses ditolak.');
        }

        $tahun    = (int) $request->query('tahun', now()->year);
        $cacheKey = self::CACHE_KEY . '_' . $tahun;

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tahun) {
            return $this->buildData($tahun);
        });

        return view('monitoring.pasangkayu-tv', [
            'data'       => $data,
            'tahun'      => $tahun,
            'token'      => $token,
            'lastUpdate' => now()->format('d M Y, H:i') . ' WIB',
        ]);
    }

    public function clearCache(Request $request)
    {
        $token = $request->query('token');
        if (empty($token) || $token !== config('app.pasangkayu_tv_token')) {
            abort(403, 'Akses ditolak.');
        }

        foreach (range(now()->year - 1, now()->year + 1) as $y) {
            Cache::forget(self::CACHE_KEY . '_' . $y);
        }

        return redirect()->route('monitoring.pasangkayu', ['token' => $token])
            ->with('info', 'Cache berhasil di-refresh.');
    }

    // =========================================================================
    // BUILD DATA — hanya agregat, tanpa nama/NIP individu
    // =========================================================================

    private function buildData(int $tahun): array
    {
        $unitIds = self::UNIT_IDS;

        // Total ASN aktif di Pasangkayu
        $totalAsn = DB::table('users')
            ->whereIn('unit_kerja_id', $unitIds)
            ->where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->count();

        // SKP distribution
        $dist = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->select('s.status', DB::raw('COUNT(*) as total'))
            ->where('s.tahun', $tahun)
            ->whereIn('u.unit_kerja_id', $unitIds)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('s.status')
            ->pluck('total', 'status')
            ->toArray();

        $sudahBuat   = array_sum(array_values($dist));
        $disetujui   = $dist['DISETUJUI'] ?? 0;
        $belumBuat   = max(0, $totalAsn - $sudahBuat);
        $kepatuhan   = $totalAsn > 0 ? round(($disetujui / $totalAsn) * 100, 1) : 0.0;

        return [
            'kpi' => [
                'total_asn'  => $totalAsn,
                'sudah_buat' => $sudahBuat,
                'disetujui'  => $disetujui,
                'belum_buat' => $belumBuat,
                'kepatuhan'  => $kepatuhan,
                'warna'      => $this->warnaKepatuhan($kepatuhan),
            ],
            'per_unit'       => $this->getPerUnit($tahun),
            'ranking_top'    => $this->getRanking($tahun, 'top'),
            'ranking_bottom' => $this->getRanking($tahun, 'bottom'),
        ];
    }

    private function getPerUnit(int $tahun): array
    {
        $unitIds = self::UNIT_IDS;

        // Total ASN per unit
        $asnPerUnit = DB::table('users as u')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id', 'uk.nama_unit', DB::raw('COUNT(u.id) as total_asn'))
            ->whereIn('uk.id', $unitIds)
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
            ->whereIn('uk.id', $unitIds)
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
            ->whereIn('uk.id', $unitIds)
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
            $persen         = $totalAsn > 0 ? round(($totalDisetujui / $totalAsn) * 100, 1) : 0.0;

            $result[] = [
                'nama_unit'  => $unit->nama_unit,
                'total_asn'  => $totalAsn,
                'total_buat' => $totalBuat,
                'disetujui'  => $totalDisetujui,
                'belum_buat' => max(0, $totalAsn - $totalBuat),
                'kepatuhan'  => $persen,
                'warna'      => $this->warnaKepatuhan($persen),
            ];
        }

        // Urutkan: kepatuhan DESC → total_asn DESC → nama_unit ASC
        usort($result, function ($a, $b) {
            if ($b['kepatuhan'] !== $a['kepatuhan']) {
                return $b['kepatuhan'] <=> $a['kepatuhan'];
            }
            if ($b['total_asn'] !== $a['total_asn']) {
                return $b['total_asn'] <=> $a['total_asn'];
            }
            return strcmp($a['nama_unit'], $b['nama_unit']);
        });

        return $result;
    }

    private function getRanking(int $tahun, string $type): array
    {
        $perUnit  = $this->getPerUnit($tahun);
        $filtered = array_filter($perUnit, fn($u) => $u['total_asn'] > 0);

        if ($type === 'bottom') {
            // Hanya unit dengan kepatuhan < 50%
            $filtered = array_filter($filtered, fn($u) => $u['kepatuhan'] < 50);
            usort($filtered, fn($a, $b) => $a['kepatuhan'] <=> $b['kepatuhan']);
        }
        // top sudah terurut DESC dari getPerUnit

        return array_slice(array_values($filtered), 0, 5);
    }

    private function warnaKepatuhan(float $persen): string
    {
        if ($persen >= 80) return 'hijau';
        if ($persen >= 50) return 'kuning';
        return 'merah';
    }
}
