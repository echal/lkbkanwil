<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\SkpTahunan;
use App\Models\SkpTahunanDetail;
use App\Models\RencanaAksiBulanan;
use App\Models\ProgresHarian;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display dashboard based on user role
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = strtoupper($user->role);

        try {
            switch ($role) {
                case 'ASN':
                    return $this->showAsnDashboard();

                case 'ATASAN':
                    return $this->showAtasanDashboard();

                case 'ADMIN':
                    return $this->showAdminDashboard();

                default:
                    return view('dashboard.default', [
                        'stats' => $this->getDefaultStats()
                    ]);
            }
        } catch (\Exception $e) {
            // If fails, show dashboard with empty data
            return $this->showDashboardWithError($role, $e->getMessage());
        }
    }

    /**
     * Show ASN Dashboard - Query langsung dari database
     */
    private function showAsnDashboard()
    {
        $user = auth()->user();
        $tahun = now()->year;
        $bulan = now()->month;

        // 1. Total SKP Tahunan (jumlah butir kinerja tahun ini)
        $skpTahunan = SkpTahunan::where('user_id', $user->id)
            ->where('tahun', $tahun)
            ->first();

        $totalSkp = 0;
        $progresKeseluruhan = 0;

        if ($skpTahunan) {
            $totalSkp = $skpTahunan->details()->count();

            // Hitung progres keseluruhan dari semua butir kinerja
            $details = $skpTahunan->details()->get();
            if ($details->count() > 0) {
                $totalCapaian = 0;
                foreach ($details as $detail) {
                    if ($detail->target_tahunan > 0) {
                        $totalCapaian += ($detail->realisasi_tahunan / $detail->target_tahunan) * 100;
                    }
                }
                $progresKeseluruhan = round($totalCapaian / $details->count());
            }
        }

        // 2. Kinerja Bulan Ini (jumlah progres harian bulan ini)
        $kinerjaBlnIni = ProgresHarian::where('user_id', $user->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->count();

        // 3. Rencana Kerja Aktif (rencana aksi bulanan yang sudah diisi bulan ini)
        $rencanaAktif = 0;
        if ($skpTahunan) {
            $detailIds = $skpTahunan->details()->pluck('id');
            $rencanaAktif = RencanaAksiBulanan::whereIn('skp_tahunan_detail_id', $detailIds)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->where('status', '!=', 'BELUM_DIISI')
                ->count();
        }

        // 4. Recent Activities (5 aktivitas terakhir)
        $recentActivities = ProgresHarian::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $tipeLabel = $item->tipe_progres === 'KINERJA_HARIAN' ? 'Kinerja Harian' : 'Tugas Langsung Atasan';
                return [
                    'title' => $tipeLabel,
                    'description' => $item->kegiatan_harian ?? $item->tugas_atasan ?? '-',
                    'date' => $item->tanggal->format('d M Y'),
                ];
            });

        return view('asn.dashboard', [
            'stats' => [
                'total_skp' => $totalSkp,
                'kinerja_bulan_ini' => $kinerjaBlnIni,
                'rencana_aktif' => $rencanaAktif,
                'progres' => $progresKeseluruhan,
            ],
            'recent_activities' => $recentActivities,
        ]);
    }

    /**
     * Show Atasan Dashboard - Query langsung dari database
     */
    private function showAtasanDashboard()
    {
        $user = auth()->user();
        $tahun = now()->year;

        // Total bawahan (users dengan atasan_id = current user)
        $totalBawahan = \App\Models\User::where('atasan_id', $user->id)->count();

        // Pending approval (SKP dengan status DIAJUKAN)
        $pendingApproval = SkpTahunan::whereHas('user', function ($q) use ($user) {
                $q->where('atasan_id', $user->id);
            })
            ->where('tahun', $tahun)
            ->where('status', 'DIAJUKAN')
            ->count();

        // Approved (SKP dengan status DISETUJUI)
        $approved = SkpTahunan::whereHas('user', function ($q) use ($user) {
                $q->where('atasan_id', $user->id);
            })
            ->where('tahun', $tahun)
            ->where('status', 'DISETUJUI')
            ->count();

        // Rata-rata kinerja bawahan
        $avgKinerja = 0;
        $bawahanSkps = SkpTahunan::whereHas('user', function ($q) use ($user) {
                $q->where('atasan_id', $user->id);
            })
            ->where('tahun', $tahun)
            ->where('status', 'DISETUJUI')
            ->get();

        if ($bawahanSkps->count() > 0) {
            $totalCapaian = 0;
            foreach ($bawahanSkps as $skp) {
                $totalCapaian += $skp->capaian_persen;
            }
            $avgKinerja = round($totalCapaian / $bawahanSkps->count());
        }

        // Pending approvals list
        $pendingApprovals = SkpTahunan::with('user')
            ->whereHas('user', function ($q) use ($user) {
                $q->where('atasan_id', $user->id);
            })
            ->where('tahun', $tahun)
            ->where('status', 'DIAJUKAN')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return view('atasan.dashboard', [
            'stats' => [
                'total_bawahan' => $totalBawahan,
                'pending_approval' => $pendingApproval,
                'approved' => $approved,
                'avg_kinerja' => $avgKinerja,
            ],
            'pending_approvals' => $pendingApprovals,
            'team_performance' => [],
        ]);
    }

    /**
     * Show Admin Dashboard
     */
    private function showAdminDashboard()
    {
        return redirect()->route('admin.dashboard.index');
    }

    /**
     * Get default stats when no data available
     */
    private function getDefaultStats()
    {
        return [
            'total_skp' => 0,
            'kinerja_bulan_ini' => 0,
            'rencana_aktif' => 0,
            'progres' => 0,
        ];
    }

    /**
     * Show dashboard with error message
     */
    private function showDashboardWithError($role, $errorMessage)
    {
        $viewMap = [
            'ASN' => 'asn.dashboard',
            'ATASAN' => 'atasan.dashboard',
            'ADMIN' => 'admin.dashboard',
        ];

        $view = $viewMap[$role] ?? 'dashboard.default';

        return view($view, [
            'stats' => $this->getDefaultStats(),
            'recent_activities' => [],
            'pending_approvals' => [],
            'team_performance' => [],
            'error' => 'Gagal memuat data dashboard. Silakan refresh halaman.',
        ]);
    }
}
