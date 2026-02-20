<?php

namespace App\Services;

use App\Models\User;
use App\Models\SkpTahunan;
use App\Models\UnitKerja;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    protected int $tahun;
    protected int $cacheTtl = 300; // 5 menit

    public function __construct()
    {
        $this->tahun = now()->year;
    }

    // =========================================================================
    // A. SUMMARY CARDS
    // =========================================================================

    public function getSummaryCards(): array
    {
        return Cache::remember("admin_summary_{$this->tahun}", $this->cacheTtl, function () {
            $totalPegawai  = User::whereIn('role', ['ASN', 'ATASAN'])->where('status_pegawai', 'AKTIF')->count();
            $totalUnitKerja = UnitKerja::where('status', 'AKTIF')->count();

            $skpStats = SkpTahunan::where('tahun', $this->tahun)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'total_pegawai'   => $totalPegawai,
                'total_unit_kerja' => $totalUnitKerja,
                'skp_aktif'       => $skpStats->sum(),
                'skp_pending'     => $skpStats->get('DIAJUKAN', 0) + $skpStats->get('REVISI_DIAJUKAN', 0),
                'skp_disetujui'   => $skpStats->get('DISETUJUI', 0),
                'skp_ditolak'     => $skpStats->get('DITOLAK', 0) + $skpStats->get('REVISI_DITOLAK', 0),
            ];
        });
    }

    // =========================================================================
    // B. APPROVAL MONITORING
    // =========================================================================

    public function getApprovalMonitoring(): array
    {
        return Cache::remember("admin_approval_{$this->tahun}", $this->cacheTtl, function () {
            // Pending per jabatan level
            $pendingPerLevel = DB::table('skp_tahunan as s')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->where('s.tahun', $this->tahun)
                ->where('s.status', 'DIAJUKAN')
                ->select('u.role', DB::raw('count(*) as total'))
                ->groupBy('u.role')
                ->pluck('total', 'role');

            // Top 5 SKP pending paling lama
            $topLama = SkpTahunan::with('user:id,name,jabatan,role')
                ->where('tahun', $this->tahun)
                ->where('status', 'DIAJUKAN')
                ->whereNotNull('approved_by')
                ->orderBy('updated_at', 'asc')
                ->limit(5)
                ->get()
                ->map(fn($s) => [
                    'id'       => $s->id,
                    'name'     => $s->user->name ?? '-',
                    'jabatan'  => $s->user->jabatan ?? '-',
                    'role'     => $s->user->role ?? '-',
                    'since'    => $s->updated_at->diffForHumans(),
                    'days'     => $s->updated_at->diffInDays(now()),
                ]);

            return [
                'pending_per_level' => $pendingPerLevel,
                'top_lama'          => $topLama,
            ];
        });
    }

    // =========================================================================
    // C. GRAFIK AKTIVITAS
    // =========================================================================

    public function getChartData(): array
    {
        return Cache::remember("admin_chart_{$this->tahun}", $this->cacheTtl, function () {
            // SKP per bulan (12 bulan terakhir)
            $skpPerBulan = DB::table('skp_tahunan')
                ->where('tahun', $this->tahun)
                ->select(
                    DB::raw('MONTH(created_at) as bulan'),
                    DB::raw('count(*) as total')
                )
                ->groupBy('bulan')
                ->orderBy('bulan')
                ->pluck('total', 'bulan');

            $months      = collect(range(1, 12));
            $skpLabels   = $months->map(fn($m) => now()->month($m)->format('M'))->values();
            $skpData     = $months->map(fn($m) => $skpPerBulan->get($m, 0))->values();

            // Distribusi pegawai per unit kerja
            $distribusi = DB::table('users as u')
                ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
                ->whereIn('u.role', ['ASN', 'ATASAN'])
                ->where('u.status_pegawai', 'AKTIF')
                ->select('uk.nama_unit', DB::raw('count(*) as total'))
                ->groupBy('uk.nama_unit')
                ->orderByDesc('total')
                ->limit(8)
                ->get();

            return [
                'skp_per_bulan' => [
                    'labels' => $skpLabels,
                    'data'   => $skpData,
                ],
                'distribusi_unit' => [
                    'labels' => $distribusi->pluck('nama_unit'),
                    'data'   => $distribusi->pluck('total'),
                ],
            ];
        });
    }

    // =========================================================================
    // E. SYSTEM HEALTH / DATA INTEGRITY
    // =========================================================================

    public function getSystemHealth(): array
    {
        return Cache::remember('admin_health', $this->cacheTtl, function () {
            $pegawaiTanpaAtasan = User::whereIn('role', ['ASN', 'ATASAN'])
                ->where('status_pegawai', 'AKTIF')
                ->whereNull('atasan_id')
                ->whereNotIn('id', User::where('role', 'ATASAN')->whereNull('atasan_id')->pluck('id')) // kecuali Kakanwil
                ->count();

            // Lebih tepat: ASN tanpa atasan
            $asnTanpaAtasan = User::where('role', 'ASN')
                ->where('status_pegawai', 'AKTIF')
                ->whereNull('atasan_id')
                ->count();

            $skpTanpaApprovedBy = SkpTahunan::where('tahun', $this->tahun)
                ->where('status', 'DIAJUKAN')
                ->whereNull('approved_by')
                ->count();

            $userTanpaUnitKerja = User::whereIn('role', ['ASN'])
                ->where('status_pegawai', 'AKTIF')
                ->whereNull('unit_kerja_id')
                ->count();

            $atasanTanpaAtasan = User::where('role', 'ATASAN')
                ->where('status_pegawai', 'AKTIF')
                ->whereNull('atasan_id')
                ->where('jabatan', '!=', 'Kakanwil')
                ->whereNotNull('jabatan')
                ->count();

            return [
                'asn_tanpa_atasan'         => $asnTanpaAtasan,
                'skp_tanpa_approved_by'    => $skpTanpaApprovedBy,
                'user_tanpa_unit_kerja'    => $userTanpaUnitKerja,
                'atasan_tanpa_atasan'      => $atasanTanpaAtasan,
                'total_issues'             => $asnTanpaAtasan + $skpTanpaApprovedBy + $userTanpaUnitKerja + $atasanTanpaAtasan,
            ];
        });
    }

    // =========================================================================
    // F. AKTIVITAS TERBARU
    // =========================================================================

    public function getRecentActivities(): array
    {
        return Cache::remember("admin_recent_{$this->tahun}", $this->cacheTtl, function () {
            $skpTerbaru = SkpTahunan::with('user:id,name,jabatan')
                ->where('tahun', $this->tahun)
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn($s) => [
                    'name'    => $s->user->name ?? '-',
                    'jabatan' => $s->user->jabatan ?? '-',
                    'status'  => $s->status,
                    'date'    => $s->created_at->format('d M Y'),
                ]);

            $pegawaiTerbaru = User::whereIn('role', ['ASN', 'ATASAN'])
                ->with('unitKerja:id,nama_unit')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn($u) => [
                    'name'      => $u->name,
                    'jabatan'   => $u->jabatan ?? '-',
                    'unit'      => $u->unitKerja->nama_unit ?? '-',
                    'role'      => $u->role,
                    'date'      => $u->created_at->format('d M Y'),
                ]);

            $approvalTerakhir = SkpTahunan::with(['user:id,name', 'approver:id,name'])
                ->where('tahun', $this->tahun)
                ->whereIn('status', ['DISETUJUI', 'DITOLAK'])
                ->whereNotNull('approved_at')
                ->orderByDesc('approved_at')
                ->limit(5)
                ->get()
                ->map(fn($s) => [
                    'asn'      => $s->user->name ?? '-',
                    'approver' => $s->approver->name ?? '-',
                    'status'   => $s->status,
                    'date'     => $s->approved_at?->format('d M Y') ?? '-',
                ]);

            return [
                'skp_terbaru'       => $skpTerbaru,
                'pegawai_terbaru'   => $pegawaiTerbaru,
                'approval_terakhir' => $approvalTerakhir,
            ];
        });
    }

    // =========================================================================
    // G. ROLE DISTRIBUTION
    // =========================================================================

    public function getRoleDistribution(): array
    {
        return Cache::remember('admin_role_dist', $this->cacheTtl, function () {
            $byRole = User::where('status_pegawai', 'AKTIF')
                ->select('role', DB::raw('count(*) as total'))
                ->groupBy('role')
                ->pluck('total', 'role');

            // Distribusi per unit kerja untuk ATASAN
            $atasanPerUnit = DB::table('users as u')
                ->leftJoin('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
                ->where('u.role', 'ATASAN')
                ->where('u.status_pegawai', 'AKTIF')
                ->select('uk.nama_unit', DB::raw('count(*) as total'))
                ->groupBy('uk.nama_unit')
                ->get();

            return [
                'by_role'         => $byRole,
                'atasan_per_unit' => $atasanPerUnit,
                'total_aktif'     => $byRole->sum(),
            ];
        });
    }

    // =========================================================================
    // H. DAILY REPORTING CONTROL
    // =========================================================================

    public function getDailyReportingData(?string $tanggal = null): array
    {
        $hari = $tanggal ? Carbon::parse($tanggal) : Carbon::today();
        $cacheKey = "admin_daily_report_{$hari->format('Y-m-d')}";

        // Cache per-day: 5 menit untuk hari ini, 1 jam untuk hari lalu
        $ttl = $hari->isToday() ? 300 : 3600;

        return Cache::remember($cacheKey, $ttl, function () use ($hari) {
            // ── Total ASN aktif (bukan Admin/Kakanwil) ──────────────────────
            $totalAsn = User::where('role', 'ASN')
                ->where('status_pegawai', 'AKTIF')
                ->count();

            // ── ASN yang sudah isi laporan hari ini ─────────────────────────
            // Ambil distinct user_id dari progres_harian pada tanggal tersebut
            $sudahIsiIds = DB::table('progres_harian')
                ->whereDate('tanggal', $hari->toDateString())
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id');

            $sudahIsi = $sudahIsiIds->count();
            $belumIsi = max(0, $totalAsn - $sudahIsi);
            $persen   = $totalAsn > 0 ? round(($sudahIsi / $totalAsn) * 100) : 0;

            // ── Stacked bar per unit kerja ───────────────────────────────────
            // Semua unit kerja dengan ASN aktif
            $units = DB::table('unit_kerja as uk')
                ->join('users as u', 'u.unit_kerja_id', '=', 'uk.id')
                ->where('u.role', 'ASN')
                ->where('u.status_pegawai', 'AKTIF')
                ->select(
                    'uk.id',
                    'uk.nama_unit',
                    DB::raw('count(distinct u.id) as total_asn')
                )
                ->groupBy('uk.id', 'uk.nama_unit')
                ->orderByDesc('total_asn')
                ->limit(10)
                ->get();

            // ASN yang sudah isi, digroup per unit_kerja_id
            $sudahPerUnit = DB::table('progres_harian as ph')
                ->join('users as u', 'ph.user_id', '=', 'u.id')
                ->whereDate('ph.tanggal', $hari->toDateString())
                ->where('u.role', 'ASN')
                ->where('u.status_pegawai', 'AKTIF')
                ->whereNotNull('u.unit_kerja_id')
                ->select('u.unit_kerja_id', DB::raw('count(distinct ph.user_id) as sudah'))
                ->groupBy('u.unit_kerja_id')
                ->pluck('sudah', 'unit_kerja_id');

            $chartData = $units->map(function ($unit) use ($sudahPerUnit) {
                $sudah = (int) $sudahPerUnit->get($unit->id, 0);
                $total = (int) $unit->total_asn;
                $belum = max(0, $total - $sudah);
                $pct   = $total > 0 ? round(($sudah / $total) * 100) : 0;
                return [
                    'unit'       => $unit->nama_unit,
                    'unit_id'    => $unit->id,
                    'total'      => $total,
                    'sudah'      => $sudah,
                    'belum'      => $belum,
                    'persen'     => $pct,
                ];
            })->values();

            // ── Top 5 unit compliance TERENDAH ──────────────────────────────
            $top5Rendah = $chartData
                ->filter(fn($u) => $u['total'] > 0)
                ->sortBy('persen')
                ->take(5)
                ->values();

            // ── ASN belum isi per unit (untuk drill-down modal) ─────────────
            // Ambil semua ASN aktif yang BELUM isi hari ini, group by unit
            $asnBelumIsi = User::where('role', 'ASN')
                ->where('status_pegawai', 'AKTIF')
                ->whereNotIn('id', $sudahIsiIds)
                ->with('unitKerja:id,nama_unit')
                ->select('id', 'name', 'jabatan', 'unit_kerja_id')
                ->orderBy('unit_kerja_id')
                ->orderBy('name')
                ->get()
                ->map(fn($u) => [
                    'id'      => $u->id,
                    'name'    => $u->name,
                    'jabatan' => $u->jabatan ?? '-',
                    'unit'    => $u->unitKerja->nama_unit ?? 'Tidak Ada Unit',
                    'unit_id' => $u->unit_kerja_id,
                ]);

            return [
                'tanggal'     => $hari->format('d F Y'),
                'tanggal_raw' => $hari->toDateString(),
                'is_today'    => $hari->isToday(),
                'total_asn'   => $totalAsn,
                'sudah_isi'   => $sudahIsi,
                'belum_isi'   => $belumIsi,
                'persen'      => $persen,
                'chart'       => $chartData,
                'top5_rendah' => $top5Rendah,
                'asn_belum'   => $asnBelumIsi,
            ];
        });
    }

    // =========================================================================
    // AGGREGATE: semua data untuk view
    // =========================================================================

    public function getAllData(): array
    {
        return [
            'summary'    => $this->getSummaryCards(),
            'approval'   => $this->getApprovalMonitoring(),
            'chart'      => $this->getChartData(),
            'health'     => $this->getSystemHealth(),
            'recent'     => $this->getRecentActivities(),
            'roles'      => $this->getRoleDistribution(),
            'daily'      => $this->getDailyReportingData(),
            'tahun'      => $this->tahun,
        ];
    }

    public function clearCache(): void
    {
        $keys = [
            "admin_summary_{$this->tahun}",
            "admin_approval_{$this->tahun}",
            "admin_chart_{$this->tahun}",
            'admin_health',
            "admin_recent_{$this->tahun}",
            'admin_role_dist',
            'admin_daily_report_' . now()->format('Y-m-d'),
        ];
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
