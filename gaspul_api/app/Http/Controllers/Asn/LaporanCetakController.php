<?php

namespace App\Http\Controllers\Asn;

use App\Http\Controllers\Controller;
use App\Models\ProgresHarian;
use App\Models\RencanaAksiBulanan;
use App\Services\LaporanBulananService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanCetakController extends Controller
{
    public function __construct(protected LaporanBulananService $laporanService) {}

    /**
     * Cetak PDF Laporan Kinerja Harian (LKH) — Portrait A4
     */
    public function cetakHarian(Request $request)
    {
        $asn     = Auth::user();
        $tanggal = $request->input('date', now()->format('Y-m-d'));
        $dateObj = Carbon::parse($tanggal);

        $progresHarian = ProgresHarian::where('user_id', $asn->id)
            ->whereDate('tanggal', $tanggal)
            ->with(['rencanaAksiBulanan.skpTahunanDetail.indikatorKinerja'])
            ->orderBy('jam_mulai')
            ->get();

        $totalMenit  = (int) $progresHarian->sum('durasi_menit');
        $totalJam    = $this->formatDurasi($totalMenit);
        $totalKH     = $progresHarian->where('tipe_progres', 'KINERJA_HARIAN')->count();
        $totalTLA    = $progresHarian->where('tipe_progres', 'TUGAS_ATASAN')->count();
        $hasEvidence = $progresHarian->where('status_bukti', 'SUDAH_ADA')->isNotEmpty();

        if ($totalMenit === 0) {
            $status = 'KOSONG'; $statusColor = 'gray';
        } elseif (! $hasEvidence) {
            $status = 'BELUM UPLOAD BUKTI'; $statusColor = 'red';
        } elseif ($totalMenit < 450) {
            $status = 'KURANG DARI 7.5 JAM'; $statusColor = 'yellow';
        } else {
            $status = 'LENGKAP'; $statusColor = 'green';
        }

        $data = [
            'asn'           => $asn,
            'tanggal'       => $dateObj->locale('id')->isoFormat('dddd, D MMMM Y'),
            'tanggal_short' => $dateObj->format('d-m-Y'),
            'progresHarian' => $progresHarian,
            'totalMenit'    => $totalMenit,
            'totalJam'      => $totalJam,
            'totalKH'       => $totalKH,
            'totalTLA'      => $totalTLA,
            'status'        => $status,
            'statusColor'   => $statusColor,
            'tanggal_cetak' => Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm'),
        ];

        $pdf      = Pdf::loadView('asn.laporan.pdf.harian', $data);
        $pdf->setPaper('A4', 'portrait');
        $filename = "LKH_{$asn->name}_{$dateObj->format('Y-m-d')}_" . now()->format('YmdHis') . ".pdf";
        return $pdf->download($filename);
    }

    /**
     * Cetak PDF Rekap Kinerja Bulanan — Landscape A4
     */
    public function cetakBulanan(Request $request)
    {
        $asn    = Auth::user();
        $bulan  = (int) $request->input('bulan', now()->month);
        $tahun  = (int) $request->input('tahun', now()->year);

        $dateObj   = Carbon::create($tahun, $bulan, 1);
        $namaBulan = $dateObj->locale('id')->isoFormat('MMMM Y');

        // ── Rekap harian via service (1 query, no N+1) ──────────────────────
        $rekapHarian = $this->laporanService->getRekapHarian($asn->id, $bulan, $tahun);
        $summary     = $this->laporanService->getSummary($rekapHarian, $tahun, $bulan);

        // ── Rencana Aksi Bulanan (konteks SKP) ─────────────────────────────
        $rencanaAksi = RencanaAksiBulanan::whereHas('skpTahunanDetail.skpTahunan', function ($q) use ($asn, $tahun) {
                $q->where('user_id', $asn->id)->where('tahun', $tahun);
            })
            ->with(['skpTahunanDetail.indikatorKinerja'])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotNull('rencana_aksi_bulanan')
            ->get();

        $data = [
            'asn'          => $asn,
            'periode'      => $namaBulan,
            'bulan'        => $bulan,
            'tahun'        => $tahun,
            'rekapHarian'  => $rekapHarian,   // key baru — konsisten dengan service
            'rencanaAksi'  => $rencanaAksi,
            'summary'      => $summary,
            'tanggal_cetak'=> Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm'),
        ];

        $pdf = Pdf::loadView('asn.laporan.pdf.bulanan', $data);
        $pdf->setPaper('A4', 'landscape');

        $filename = "Rekap_Bulanan_{$asn->name}_{$namaBulan}_{$tahun}_" . now()->format('YmdHis') . ".pdf";
        return $pdf->download($filename);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function formatDurasi(int $menit): string
    {
        if ($menit <= 0) return '-';
        $jam       = intdiv($menit, 60);
        $sisaMenit = $menit % 60;
        return $sisaMenit > 0 ? "{$jam}j {$sisaMenit}m" : "{$jam}j";
    }
}
