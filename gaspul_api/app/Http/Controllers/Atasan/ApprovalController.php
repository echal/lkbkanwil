<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use App\Models\SkpTahunan;
use App\Models\ProgresHarian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Controller Persetujuan (Approval Center)
 *
 * Centralized approval interface untuk Atasan
 * Menampilkan semua pengajuan yang memerlukan persetujuan:
 * - SKP Tahunan (status DIAJUKAN)
 * - Laporan Bulanan (jika ada tabel tracking)
 * - Kinerja Harian (validasi bukti)
 */
class ApprovalController extends Controller
{
    /**
     * Approval Center Dashboard
     *
     * Menampilkan semua pending approvals dalam satu halaman
     */
    public function index(Request $request)
    {
        $atasan = Auth::user();
        $filterType = $request->input('type', 'all'); // all|skp|bukti
        $filterStatus = $request->input('status', 'pending'); // pending|approved|rejected

        // 1. GET SKP TAHUNAN YANG MENUNGGU PERSETUJUAN
        $skpQuery = SkpTahunan::with(['user.unitKerja', 'details.indikatorKinerja'])
            ->whereHas('user', function($q) use ($atasan) {
                $q->where('role', 'ASN');
                if ($atasan->unit_kerja_id) {
                    $q->where('unit_kerja_id', $atasan->unit_kerja_id);
                }
            });

        if ($filterStatus === 'pending') {
            $skpQuery->where('status', 'DIAJUKAN');
        } elseif ($filterStatus === 'approved') {
            $skpQuery->where('status', 'DISETUJUI');
        } elseif ($filterStatus === 'rejected') {
            $skpQuery->where('status', 'DITOLAK');
        }

        $skpPendingList = $skpQuery->latest()->get();

        // 2. GET PROGRES HARIAN YANG PERLU VALIDASI BUKTI
        $progresBuktiQuery = ProgresHarian::with(['user', 'rencanaAksiBulanan.skpTahunanDetail.indikatorKinerja'])
            ->whereHas('user', function($q) use ($atasan) {
                $q->where('role', 'ASN');
                if ($atasan->unit_kerja_id) {
                    $q->where('unit_kerja_id', $atasan->unit_kerja_id);
                }
            })
            ->where('status_bukti', '!=', 'BELUM_ADA')
            ->whereNotNull('bukti_dukung');

        $progresBuktiList = $progresBuktiQuery->latest()->limit(50)->get();

        // 3. PREPARE DATA FOR VIEW
        $approvalData = [
            'skp_pending_count' => $skpPendingList->where('status', 'DIAJUKAN')->count(),
            'skp_approved_count' => $skpPendingList->where('status', 'DISETUJUI')->count(),
            'skp_rejected_count' => $skpPendingList->where('status', 'DITOLAK')->count(),
            'bukti_count' => $progresBuktiList->count(),
        ];

        // Format SKP data
        $skpData = $skpPendingList->map(function($skp) {
            return [
                'id' => $skp->id,
                'type' => 'SKP Tahunan',
                'asn_nama' => $skp->user->name,
                'asn_nip' => $skp->user->nip ?? '-',
                'asn_jabatan' => $skp->user->jabatan ?? '-',
                'unit_kerja' => $skp->user->unitKerja->nama_unit ?? '-',
                'tahun' => $skp->tahun,
                'total_rhk' => $skp->details->count(),
                'status' => $skp->status,
                'tanggal_pengajuan' => $skp->updated_at,
                'url_detail' => route('atasan.skp-tahunan.show', $skp->id),
            ];
        });

        // Format Progres Bukti data
        $buktiData = $progresBuktiList->map(function($progres) {
            return [
                'id' => $progres->id,
                'type' => 'Bukti Dukung',
                'asn_nama' => $progres->user->name,
                'asn_nip' => $progres->user->nip ?? '-',
                'tanggal' => $progres->tanggal,
                'kegiatan' => $progres->tipe_progres === 'KINERJA_HARIAN'
                    ? $progres->rencana_kegiatan_harian
                    : $progres->tugas_atasan,
                'indikator_kinerja' => $progres->rencanaAksiBulanan->skpTahunanDetail->indikatorKinerja->nama_indikator ?? '-',
                'bukti_dukung' => $progres->bukti_dukung,
                'status_bukti' => $progres->status_bukti,
                'tanggal_upload' => $progres->updated_at,
            ];
        });

        return view('atasan.approval.index', [
            'approvalData' => $approvalData,
            'skpData' => $skpData,
            'buktiData' => $buktiData,
            'filterType' => $filterType,
            'filterStatus' => $filterStatus,
            'atasan' => $atasan,
        ]);
    }

    /**
     * Show detail of approval item
     * Currently redirects to respective detail pages
     */
    public function show($id)
    {
        // This can be expanded to show unified detail view
        // For now, we redirect to SKP detail
        return redirect()->route('atasan.skp-tahunan.show', $id);
    }
}

