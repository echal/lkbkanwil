<?php

namespace App\Http\Controllers\Asn;

use App\Http\Controllers\Controller;
use App\Models\LaporanBulananKinerja;
use App\Models\ProgresHarian;
use App\Models\RencanaAksiBulanan;
use App\Services\LaporanBulananService;
use App\Services\WorkingTimeService;
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
        $tanggal = $request->input('date', now()->format('Y-m-d'));
        $dateObj = Carbon::parse($tanggal);

        $progresHarian = ProgresHarian::where('user_id', $asn->id)
            ->whereDate('tanggal', $tanggal)
            ->with(['rencanaAksiBulanan.skpTahunanDetail.indikatorKinerja'])
            ->orderBy('jam_mulai')
            ->get();

        $asn         = Auth::user()->load('unitKerja');
        $targetMenit = WorkingTimeService::getTargetMenitByDate($dateObj, $asn);
        $totalMenit  = (int) $progresHarian->sum('durasi_menit');
        $totalJam    = $this->formatDurasi($totalMenit);
        $totalKH     = $progresHarian->where('tipe_progres', 'KINERJA_HARIAN')->count();
        $totalTLA    = $progresHarian->where('tipe_progres', 'TUGAS_ATASAN')->count();
        $hasEvidence = $progresHarian->where('status_bukti', 'SUDAH_ADA')->isNotEmpty();

        if ($targetMenit === 0) {
            $status = 'HARI LIBUR'; $statusColor = 'gray';
        } elseif ($totalMenit === 0) {
            $status = 'KOSONG'; $statusColor = 'gray';
        } elseif (! $hasEvidence) {
            $status = 'BELUM UPLOAD BUKTI'; $statusColor = 'red';
        } elseif ($totalMenit >= $targetMenit) {
            $status = 'LENGKAP'; $statusColor = 'green';
        } else {
            $jamTarget   = floor($targetMenit / 60);
            $menitTarget = $targetMenit % 60;
            $status      = "KURANG DARI {$jamTarget} JAM {$menitTarget} MENIT"; $statusColor = 'yellow';
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
        $asn    = Auth::user()->load(['unitKerja', 'atasan']);
        $bulan  = (int) $request->input('bulan', now()->month);
        $tahun  = (int) $request->input('tahun', now()->year);

        $dateObj   = Carbon::create($tahun, $bulan, 1);
        $namaBulan = $dateObj->locale('id')->isoFormat('MMMM Y');

        // ── Rekap harian via service (1 query, no N+1) ──────────────────────
        $rekapHarian = $this->laporanService->getRekapHarian($asn->id, $bulan, $tahun, $asn);
        $summary     = $this->laporanService->getSummary($rekapHarian, $tahun, $bulan);

        // ── Snapshot audit trail — prioritas: snapshot > fallback dinamis ──
        $laporan = LaporanBulananKinerja::where('user_id', $asn->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        $targetMenitBulanan = $laporan?->target_menit_bulanan_snapshot
            ?? WorkingTimeService::getTargetMenitBulanan($bulan, $tahun, $asn);
        $targetJamBulanan   = $laporan?->target_jam_bulanan_snapshot
            ?? round($targetMenitBulanan / 60, 2);
        $polaKerja          = $laporan?->pola_kerja_snapshot
            ?? \App\Helpers\HolidayHelper::getHariKerjaUser($asn);

        // ── Rencana Aksi Bulanan (konteks SKP) ─────────────────────────────
        $rencanaAksi = RencanaAksiBulanan::whereHas('skpTahunanDetail.skpTahunan', function ($q) use ($asn, $tahun) {
                $q->where('user_id', $asn->id)->where('tahun', $tahun);
            })
            ->with(['skpTahunanDetail.indikatorKinerja'])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotNull('rencana_aksi_bulanan')
            ->get();

        // ── Detail KH & TLA per hari (1 query, grouped di PHP) ─────────────
        $detailHarian = ProgresHarian::where('user_id', $asn->id)
            ->whereBetween('tanggal', [
                $dateObj->copy()->startOfMonth(),
                $dateObj->copy()->endOfMonth(),
            ])
            ->with(['rencanaAksiBulanan.skpTahunanDetail.indikatorKinerja'])
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get()
            ->groupBy(fn($p) => $p->tanggal->format('Y-m-d'));

        $data = [
            'asn'                 => $asn,
            'atasan'              => $asn->atasan,
            'periode'             => $namaBulan,
            'bulan'               => $bulan,
            'tahun'               => $tahun,
            'rekapHarian'         => $rekapHarian,
            'rencanaAksi'         => $rencanaAksi,
            'detailHarian'        => $detailHarian,
            'summary'             => $summary,
            'target_menit_bulanan'=> $targetMenitBulanan,
            'target_jam_bulanan'  => $targetJamBulanan,
            'pola_kerja'          => $polaKerja,
            'tanggal_cetak'       => Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm'),
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
