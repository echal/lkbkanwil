<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProgresHarian;
use App\Models\RencanaAksiBulanan;
use App\Models\SkpTahunan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Controller Kinerja Bawahan
 *
 * Dashboard performance monitoring untuk Atasan
 * Menampilkan kinerja bulanan dan tahunan bawahan
 */
class KinerjaBawahanController extends Controller
{
    /**
     * Dashboard Kinerja Bawahan
     *
     * Menampilkan:
     * - Summary cards per ASN
     * - Total jam kerja bulanan/tahunan
     * - Persentase capaian RHK
     * - Status kinerja
     * - Filter: Bulan & Tahun
     */
    public function index(Request $request)
    {
        $atasan = Auth::user();
        $tahun = $request->input('tahun', now()->year);
        $bulan = $request->input('bulan', now()->month);
        $viewMode = $request->input('view_mode', 'bulanan'); // bulanan | tahunan

        // Get all ASN bawahan in the same unit_kerja
        $query = User::where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF');

        // Filter by unit_kerja_id if atasan has one
        if ($atasan->unit_kerja_id) {
            $query->where('unit_kerja_id', $atasan->unit_kerja_id);
        }

        $asnList = $query->orderBy('name')->get();

        // Calculate performance metrics for each ASN
        $kinerjaData = $asnList->map(function($asn) use ($tahun, $bulan, $viewMode) {
            return $this->calculateKinerjaMetrics($asn, $tahun, $bulan, $viewMode);
        });

        // Get nama bulan
        $namaBulan = $this->getNamaBulan($bulan);

        return view('atasan.kinerja-bawahan.index', [
            'kinerjaData' => $kinerjaData,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'namaBulan' => $namaBulan,
            'viewMode' => $viewMode,
            'atasan' => $atasan,
        ]);
    }

    /**
     * Calculate kinerja metrics for an ASN
     */
    private function calculateKinerjaMetrics($asn, $tahun, $bulan, $viewMode)
    {
        if ($viewMode === 'bulanan') {
            // Bulanan metrics
            $progresHarian = ProgresHarian::where('user_id', $asn->id)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->get();

            $totalHariKerja = $progresHarian->unique('tanggal')->count();
            $totalDurasiMenit = $progresHarian->sum('durasi_menit');
            $totalJamKerja = floor($totalDurasiMenit / 60);
            $sisaMenit = $totalDurasiMenit % 60;

            // Target jam kerja per bulan (22 hari x 7.5 jam = 165 jam)
            $targetJamKerjaBulanan = 165;
            $persentaseJamKerja = $targetJamKerjaBulanan > 0
                ? round(($totalDurasiMenit / 60) / $targetJamKerjaBulanan * 100, 1)
                : 0;

            // Count RHK aktif di bulan ini
            $skpTahunan = SkpTahunan::where('user_id', $asn->id)
                ->where('tahun', $tahun)
                ->first();

            $totalRhkAktif = 0;
            $totalRencanaAksi = 0;

            if ($skpTahunan) {
                $totalRhkAktif = $skpTahunan->details()->count();
                $totalRencanaAksi = RencanaAksiBulanan::whereHas('skpTahunanDetail', function($query) use ($skpTahunan) {
                        $query->where('skp_tahunan_id', $skpTahunan->id);
                    })
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->where('status', '!=', 'BELUM_DIISI')
                    ->count();
            }

            $statusKinerja = $this->getStatusKinerja($persentaseJamKerja);

            return [
                'asn_id' => $asn->id,
                'asn_nama' => $asn->name,
                'asn_nip' => $asn->nip ?? '-',
                'asn_jabatan' => $asn->jabatan ?? '-',
                'total_hari_kerja' => $totalHariKerja,
                'total_jam_kerja' => $totalJamKerja,
                'sisa_menit' => $sisaMenit,
                'total_jam_formatted' => "{$totalJamKerja}j {$sisaMenit}m",
                'target_jam' => $targetJamKerjaBulanan,
                'persentase_jam_kerja' => $persentaseJamKerja,
                'total_rhk_aktif' => $totalRhkAktif,
                'total_rencana_aksi' => $totalRencanaAksi,
                'total_progres' => $progresHarian->count(),
                'status_kinerja' => $statusKinerja,
                'status_badge' => $this->getStatusBadge($statusKinerja),
            ];
        } else {
            // Tahunan metrics
            $progresHarian = ProgresHarian::where('user_id', $asn->id)
                ->whereYear('tanggal', $tahun)
                ->get();

            $totalHariKerja = $progresHarian->unique('tanggal')->count();
            $totalDurasiMenit = $progresHarian->sum('durasi_menit');
            $totalJamKerja = floor($totalDurasiMenit / 60);
            $sisaMenit = $totalDurasiMenit % 60;

            // Target jam kerja per tahun (250 hari x 7.5 jam = 1875 jam)
            $targetJamKerjaTahunan = 1875;
            $persentaseJamKerja = $targetJamKerjaTahunan > 0
                ? round(($totalDurasiMenit / 60) / $targetJamKerjaTahunan * 100, 1)
                : 0;

            $skpTahunan = SkpTahunan::where('user_id', $asn->id)
                ->where('tahun', $tahun)
                ->first();

            $totalRhkAktif = 0;
            $totalRencanaAksi = 0;

            if ($skpTahunan) {
                $totalRhkAktif = $skpTahunan->details()->count();
                $totalRencanaAksi = RencanaAksiBulanan::whereHas('skpTahunanDetail', function($query) use ($skpTahunan) {
                        $query->where('skp_tahunan_id', $skpTahunan->id);
                    })
                    ->where('tahun', $tahun)
                    ->where('status', '!=', 'BELUM_DIISI')
                    ->count();
            }

            $statusKinerja = $this->getStatusKinerja($persentaseJamKerja);

            return [
                'asn_id' => $asn->id,
                'asn_nama' => $asn->name,
                'asn_nip' => $asn->nip ?? '-',
                'asn_jabatan' => $asn->jabatan ?? '-',
                'total_hari_kerja' => $totalHariKerja,
                'total_jam_kerja' => $totalJamKerja,
                'sisa_menit' => $sisaMenit,
                'total_jam_formatted' => "{$totalJamKerja}j {$sisaMenit}m",
                'target_jam' => $targetJamKerjaTahunan,
                'persentase_jam_kerja' => $persentaseJamKerja,
                'total_rhk_aktif' => $totalRhkAktif,
                'total_rencana_aksi' => $totalRencanaAksi,
                'total_progres' => $progresHarian->count(),
                'status_kinerja' => $statusKinerja,
                'status_badge' => $this->getStatusBadge($statusKinerja),
            ];
        }
    }

    /**
     * Get status kinerja based on persentase
     */
    private function getStatusKinerja($persentase)
    {
        if ($persentase >= 90) return 'Sangat Baik';
        if ($persentase >= 75) return 'Baik';
        if ($persentase >= 60) return 'Cukup';
        return 'Kurang';
    }

    /**
     * Get status badge HTML
     */
    private function getStatusBadge($status)
    {
        switch ($status) {
            case 'Sangat Baik':
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Sangat Baik</span>';
            case 'Baik':
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">Baik</span>';
            case 'Cukup':
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">Cukup</span>';
            default:
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Kurang</span>';
        }
    }

    /**
     * Get nama bulan in Bahasa Indonesia
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
}
