<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use App\Models\LaporanBulananKinerja;
use App\Models\RencanaAksiBulanan;
use App\Models\RekapAbsensiPusaka;
use App\Models\SkpTahunan;
use App\Services\LaporanBulananService;
use App\Services\RekapAbsensiService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Controller Pusat Persetujuan (Approval Center)
 *
 * Centralized approval interface untuk Atasan:
 * - Tab 1: SKP Tahunan (status DIAJUKAN)
 * - Tab 2: Rekap Absensi PUSAKA (multi-level: Kabid → Kakanwil)
 * - Tab 3: Laporan Bulanan Kinerja (single-level: atasan langsung)
 *
 * Level approver dideteksi via atasan_id:
 * - Kabid    = ATASAN yang memiliki atasan_id (bukan puncak hierarki)
 * - Kakanwil = ATASAN yang atasan_id = NULL (puncak hierarki)
 */
class ApprovalController extends Controller
{
    public function __construct(
        protected RekapAbsensiService $rekapService,
        protected LaporanBulananService $laporanService,
    ) {}

    /**
     * Tampilkan Pusat Persetujuan dengan 3 tab.
     */
    public function index(Request $request)
    {
        $atasan    = Auth::user();
        // Prioritas: query string > session > default 'skp'
        $activeTab = $request->input('tab', session('active_tab', 'skp'));
        $isKabid   = $atasan->atasan_id !== null;

        // ── Tab 1: SKP Tahunan ────────────────────────────────────────────────
        $skpList = SkpTahunan::with([
                'user:id,name,nip,jabatan,unit_kerja_id,atasan_id',
                'user.unitKerja:id,nama_unit',
            ])
            ->whereHas('user', fn($q) => $q->where('atasan_id', $atasan->id))
            ->latest()
            ->get();

        $skpPendingCount  = $skpList->where('status', 'DIAJUKAN')->count();
        $skpApprovedCount = $skpList->where('status', 'DISETUJUI')->count();
        $skpRejectedCount = $skpList->where('status', 'DITOLAK')->count();

        // ── Tab 2: Rekap Absensi PUSAKA ───────────────────────────────────────
        $rekapFilter = $request->input('rekap_filter', 'semua');
        $rekapBulan  = $request->input('rekap_bulan', 'semua');

        if ($isKabid) {
            $rekapList         = $this->rekapService->getForKabid($atasan->id, $rekapFilter, $rekapBulan);
            $rekapPendingCount = $this->rekapService->getForKabid($atasan->id)
                                     ->where('status', RekapAbsensiPusaka::STATUS_PENDING_KABID)->count();
        } else {
            $rekapList         = $this->rekapService->getForKakanwil($atasan->id, $rekapFilter, $rekapBulan);
            $rekapPendingCount = $this->rekapService->getForKakanwil($atasan->id)
                                     ->where('status', RekapAbsensiPusaka::STATUS_PENDING_KAKANWIL)->count();
        }

        // ── Tab 3: Laporan Bulanan Kinerja ────────────────────────────────────
        $laporanFilter       = $request->input('laporan_filter', 'semua');
        $laporanBulan        = $request->input('laporan_bulan', 'semua');
        $laporanList         = $this->laporanService->getApprovalList($atasan->id, $laporanFilter, $laporanBulan);
        $laporanPendingCount = $this->laporanService->getPendingCount($atasan->id);

        return view('atasan.approval.index', compact(
            'skpList',
            'rekapList',
            'laporanList',
            'skpPendingCount',
            'skpApprovedCount',
            'skpRejectedCount',
            'rekapPendingCount',
            'laporanPendingCount',
            'rekapFilter',
            'rekapBulan',
            'laporanFilter',
            'laporanBulan',
            'activeTab',
            'isKabid',
        ));
    }

    // ── Rekap Absensi PUSAKA ──────────────────────────────────────────────────

    /**
     * Setujui Rekap Absensi PUSAKA.
     */
    public function approveRekap(Request $request, int $id): RedirectResponse
    {
        $atasan  = Auth::user();
        $catatan = $request->input('catatan');

        try {
            if ($atasan->atasan_id !== null) {
                $rekap = $this->rekapService->approveKabid($atasan->id, $id, $catatan);
                $msg   = 'Rekap absensi ' . $rekap->nama_bulan . ' atas nama ' . $rekap->user->name
                       . ' diteruskan ke Kakanwil.';
            } else {
                $rekap = $this->rekapService->approveKakanwil($atasan->id, $id, $catatan);
                $msg   = 'Rekap absensi ' . $rekap->nama_bulan . ' atas nama ' . $rekap->user->name
                       . ' telah disetujui.';
            }
        } catch (ValidationException $e) {
            return redirect()
                ->route('atasan.approval.index')
                ->with('active_tab', 'rekap')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('atasan.approval.index')
            ->with('active_tab', 'rekap')
            ->with('success', $msg);
    }

    /**
     * Tolak Rekap Absensi PUSAKA.
     */
    public function rejectRekap(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'catatan' => ['required', 'string', 'max:500'],
        ], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $atasan = Auth::user();

        try {
            if ($atasan->atasan_id !== null) {
                $rekap = $this->rekapService->rejectKabid($atasan->id, $id, $request->catatan);
                $msg   = 'Rekap absensi ' . $rekap->nama_bulan . ' atas nama ' . $rekap->user->name
                       . ' ditolak oleh Kabid.';
            } else {
                $rekap = $this->rekapService->rejectKakanwil($atasan->id, $id, $request->catatan);
                $msg   = 'Rekap absensi ' . $rekap->nama_bulan . ' atas nama ' . $rekap->user->name
                       . ' ditolak oleh Kakanwil.';
            }
        } catch (ValidationException $e) {
            return redirect()
                ->route('atasan.approval.index')
                ->with('active_tab', 'rekap')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('atasan.approval.index')
            ->with('active_tab', 'rekap')
            ->with('success', $msg);
    }

    // ── Laporan Bulanan Kinerja ───────────────────────────────────────────────

    /**
     * Setujui Laporan Bulanan Kinerja bawahan.
     */
    public function approveLaporan(Request $request, int $id): RedirectResponse
    {
        $atasan  = Auth::user();
        $catatan = $request->input('catatan');

        try {
            $laporan = $this->laporanService->approve($id, $atasan->id, $catatan);
            $msg     = 'Laporan ' . $laporan->nama_bulan . ' atas nama ' . $laporan->user->name
                     . ' telah disetujui.';
        } catch (ValidationException $e) {
            return redirect()
                ->route('atasan.approval.index')
                ->with('active_tab', 'laporan')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('atasan.approval.index')
            ->with('active_tab', 'laporan')
            ->with('success', $msg);
    }

    /**
     * Tolak Laporan Bulanan Kinerja bawahan.
     */
    public function tolakLaporan(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'catatan' => ['required', 'string', 'max:1000'],
        ], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $atasan = Auth::user();

        try {
            $laporan = $this->laporanService->tolak($id, $atasan->id, $request->catatan);
            $msg     = 'Laporan ' . $laporan->nama_bulan . ' atas nama ' . $laporan->user->name
                     . ' ditolak. ASN dapat mengirim ulang.';
        } catch (ValidationException $e) {
            return redirect()
                ->route('atasan.approval.index')
                ->with('active_tab', 'laporan')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('atasan.approval.index')
            ->with('active_tab', 'laporan')
            ->with('success', $msg);
    }

    // ── Download PDF ──────────────────────────────────────────────────────────

    /**
     * Download PDF rekap bulanan milik bawahan.
     * Atasan hanya boleh download laporan dari bawahannya sendiri.
     */
    public function downloadPdfBawahan(Request $request, int $id)
    {
        $atasan = Auth::user();

        // Pastikan laporan milik bawahan langsung atasan ini
        $laporan = LaporanBulananKinerja::whereHas(
                'user', fn($q) => $q->where('atasan_id', $atasan->id)
            )
            ->with(['user.unitKerja'])
            ->findOrFail($id);

        $asn   = $laporan->user;
        $bulan = $laporan->bulan;
        $tahun = $laporan->tahun;

        // Rencana Aksi Bulanan (konteks SKP)
        $skpTahunan = SkpTahunan::where('user_id', $asn->id)
            ->where('tahun', $tahun)
            ->first();

        $rencanaAksi = $skpTahunan
            ? RencanaAksiBulanan::whereHas('skpTahunanDetail', fn($q) => $q->where('skp_tahunan_id', $skpTahunan->id))
                ->with(['skpTahunanDetail.indikatorKinerja'])
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->whereNotNull('rencana_aksi_bulanan')
                ->get()
            : collect();

        // Rekap harian via service
        $rekapHarian = $this->laporanService->getRekapHarian($asn->id, $bulan, $tahun);
        $summary     = $this->laporanService->getSummary($rekapHarian, $tahun, $bulan);

        $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $periode      = ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun;
        $tanggalCetak = Carbon::now()->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB';

        $pdf = \PDF::loadView('asn.laporan.pdf.bulanan', [
            'asn'          => $asn,
            'periode'      => $periode,
            'bulan'        => $bulan,
            'tahun'        => $tahun,
            'rencanaAksi'  => $rencanaAksi,
            'rekapHarian'  => $rekapHarian,
            'summary'      => $summary,
            'tanggal_cetak'=> $tanggalCetak,
        ]);

        $pdf->setPaper('A4', 'landscape');

        // Nama file: nama_bulan_tahun.pdf — huruf kecil, spasi jadi underscore
        $namaAsn   = strtolower(str_replace(' ', '_', $asn->name));
        $namaBln   = strtolower($namaBulan[$bulan] ?? $bulan);
        $fileName  = $namaAsn . '_' . $namaBln . '_' . $tahun . '.pdf';

        return $pdf->download($fileName);
    }
}
