<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use App\Models\SkpTahunan;
use App\Models\User;
use App\Models\UnitKerja;
use App\Services\SubordinateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller SKP Tahunan Atasan
 *
 * Menampilkan dan mengelola SKP Tahunan bawahan
 * Atasan dapat melihat, menyetujui, atau menolak SKP bawahan
 */
class SkpTahunanAtasanController extends Controller
{
    /**
     * Display list of SKP Tahunan from bawahan
     *
     * Query Logic:
     * - Atasan melihat SKP dari ASN yang memiliki unit_kerja_id yang sama
     * - Filter by tahun, status, ASN name
     * - Display: Nama ASN, NIP, Jabatan, Unit Kerja, Tahun, Status, Total RHK
     */
    public function index(Request $request)
    {
        $atasan = Auth::user();
        $tahun = $request->input('tahun', now()->year);
        $status = $request->input('status');
        $unitKerjaId = $request->input('unit_kerja_id');
        $searchAsn = $request->input('search_asn');

        $service = new SubordinateService();
        $isKepalaKab = $service->isKepalaKankemenagKab($atasan);

        // ========================================================================
        // TENTUKAN USER ID YANG BISA DILIHAT SKP-nya
        // Kepala Kankemenag Kab: L1 + L2 strategis (read-only untuk L2)
        // Atasan biasa: hanya L1 langsung (atasan_id = atasan->id)
        // ========================================================================
        if ($isKepalaKab) {
            $visibleUserIds = $service->getMonitorableIds($atasan);
        } else {
            $visibleUserIds = User::where('atasan_id', $atasan->id)->pluck('id');
        }

        // Query SKP Tahunan dari bawahan yang visible
        $query = SkpTahunan::with([
            'user.unitKerja',
            'user.atasan',
            'details.indikatorKinerja.sasaranKegiatan',
            'approver'
        ])->whereHas('user', function($q) use ($visibleUserIds) {
            $q->whereIn('id', $visibleUserIds);
        });

        // Filter by tahun
        $query->where('tahun', $tahun);

        // Filter by status (optional)
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by unit kerja (optional)
        if ($unitKerjaId) {
            $query->whereHas('user', function($q) use ($unitKerjaId) {
                $q->where('unit_kerja_id', $unitKerjaId);
            });
        }

        // Filter by nama pegawai (optional)
        if ($searchAsn) {
            $query->whereHas('user', function($q) use ($searchAsn) {
                $q->where('name', 'like', '%' . $searchAsn . '%')
                  ->orWhere('nip', 'like', '%' . $searchAsn . '%');
            });
        }

        // Order by latest
        $skpList = $query->latest()->paginate(15);

        // Get unit kerja list for filter
        $unitKerjaList = UnitKerja::where('status', 'AKTIF')->get();

        // Pending counts — berbasis approved_by (hanya L1 langsung yang bisa diapprove)
        $pendingRevisionCount = SkpTahunan::where('status', 'REVISI_DIAJUKAN')
            ->where('tahun', $tahun)
            ->where('approved_by', $atasan->id)
            ->count();

        $pendingApprovalCount = SkpTahunan::where('status', 'DIAJUKAN')
            ->where('tahun', $tahun)
            ->where('approved_by', $atasan->id)
            ->count();

        // Prepare data for view
        // can_approve: true hanya jika approved_by = atasan ini (bukan read-only L2)
        $skpData = $skpList->map(function($skp) use ($atasan) {
            $canApprove = in_array($skp->status, ['DIAJUKAN'])
                && (int)$skp->approved_by === (int)$atasan->id;
            $canActRevisi = in_array($skp->status, ['REVISI_DIAJUKAN'])
                && (int)$skp->approved_by === (int)$atasan->id;
            return [
                'id'               => $skp->id,
                'asn_nama'         => $skp->user->name,
                'asn_nip'          => $skp->user->nip ?? '-',
                'asn_jabatan'      => $skp->user->jabatan ?? '-',
                'unit_kerja'       => $skp->user->unitKerja->nama_unit ?? '-',
                'tahun'            => $skp->tahun,
                'status'           => $skp->status,
                'total_rhk'        => $skp->details->count(),
                'catatan_atasan'   => $skp->catatan_atasan,
                'approved_by_name' => $skp->approver->name ?? null,
                'approved_at'      => $skp->approved_at,
                'can_approve'      => $canApprove,
                'can_act_revisi'   => $canActRevisi,
                'is_readonly'      => (int)$skp->approved_by !== (int)$atasan->id,
            ];
        });

        return view('atasan.skp-tahunan.index', [
            'skpList'              => $skpList,
            'skpData'              => $skpData,
            'tahun'                => $tahun,
            'status'               => $status,
            'unitKerjaId'          => $unitKerjaId,
            'searchAsn'            => $searchAsn,
            'unitKerjaList'        => $unitKerjaList,
            'pendingRevisionCount' => $pendingRevisionCount,
            'pendingApprovalCount' => $pendingApprovalCount,
            'isKepalaKab'          => $isKepalaKab,
        ]);
    }

    /**
     * Show detail SKP Tahunan bawahan
     *
     * Display:
     * - ASN identity
     * - List of RHK with rencana aksi
     * - Status dan catatan
     * - Form approve/reject
     *
     * ACCESS VALIDATION (SIMPLIFIED):
     * - Hanya validasi approved_by = auth()->id()
     * - Backward compatible: approved_by null = allow (data lama)
     */
    public function show($id)
    {
        $skp = SkpTahunan::with([
            'user.unitKerja',
            'details.indikatorKinerja.sasaranKegiatan',
            'details.rencanaAksiBulanan',
            'approver'
        ])->findOrFail($id);

        // ========================================================================
        // ACCESS VALIDATION BERBASIS approved_by (ONLY)
        // Tidak ada dependency ke role atau unit_kerja_id
        // ========================================================================

        $atasan = Auth::user();

        $service = new SubordinateService();
        $isKepalaKab = $service->isKepalaKankemenagKab($atasan);

        // Cek apakah ASN ini boleh dilihat oleh atasan yang login
        $allowedIds = $service->getMonitorableIds($atasan);
        $isMonitorable = $allowedIds->contains($skp->user->id);

        // Jika tidak masuk daftar monitorable, tolak akses
        if (!$isMonitorable) {
            // Fallback rotasi jabatan: approved_by = atasan lama tapi ASN sudah pindah ke atasan ini
            $isCurrentAtasan = (int)$skp->user->atasan_id === (int)$atasan->id;
            if (!$isCurrentAtasan) {
                abort(403, 'Anda tidak memiliki akses ke SKP ini.');
            }
        }

        // can_approve: hanya jika approved_by = atasan ini (bukan read-only L2)
        $isReadonly = (int)$skp->approved_by !== (int)$atasan->id;

        // Prepare detail data
        $rhkList = $skp->details->map(function($detail) {
            return [
                'id' => $detail->id,
                'sasaran_kegiatan' => $detail->indikatorKinerja->sasaranKegiatan->nama_sasaran ?? '-',
                'indikator_kinerja' => $detail->indikatorKinerja->nama_indikator ?? '-',
                'kode_indikator' => $detail->indikatorKinerja->kode_indikator ?? '-',
                'rencana_aksi' => $detail->rencana_aksi,
                'target_tahunan' => $detail->target_tahunan,
                'satuan' => $detail->satuan,
                'realisasi_tahunan' => $detail->realisasi_tahunan,
                'capaian_persen' => $detail->capaian_persen,
                'total_rencana_bulanan' => $detail->rencanaAksiBulanan->count(),
            ];
        });

        return view('atasan.skp-tahunan.show', [
            'skp'        => $skp,
            'asn'        => $skp->user,
            'rhkList'    => $rhkList,
            'canApprove' => !$isReadonly && in_array($skp->status, ['DIAJUKAN']),
            'canRevisi'  => !$isReadonly && in_array($skp->status, ['REVISI_DIAJUKAN']),
            'isReadonly' => $isReadonly,
        ]);
    }

    /**
     * Approve SKP Tahunan
     *
     * APPROVAL LOGIC (SIMPLIFIED):
     * - Hanya validasi approved_by = auth()->id()
     * - Tidak ada dependency ke role atau unit_kerja_id
     * - Backward compatible: approved_by bisa null untuk data lama (auto-allow)
     */
    public function approve(Request $request, $id)
    {
        $validated = $request->validate([
            'catatan_atasan' => 'nullable|string|max:1000',
        ]);

        $skp = SkpTahunan::findOrFail($id);

        // Verify status
        if ($skp->status !== 'DIAJUKAN') {
            return back()->with('error', 'SKP tidak dalam status diajukan');
        }

        // ========================================================================
        // APPROVAL VALIDATION BERBASIS approved_by (ONLY)
        // Tidak ada dependency ke role atau unit_kerja_id
        // ========================================================================

        $atasan = Auth::user();

        // VALIDATION: Hanya check approved_by
        // Backward compatible: jika approved_by null, allow (data lama)
        if ($skp->approved_by != null && (int)$skp->approved_by !== (int)$atasan->id) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menyetujui SKP ini. SKP ini bukan tanggung jawab Anda.');
        }

        // Update status
        $skp->update([
            'status' => 'DISETUJUI',
            'catatan_atasan' => $validated['catatan_atasan'] ?? null,
            'approved_by' => $atasan->id,
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('atasan.skp-tahunan.show', $id)
            ->with('success', 'SKP Tahunan berhasil disetujui');
    }

    /**
     * Reject SKP Tahunan
     *
     * REJECTION LOGIC (SIMPLIFIED):
     * - Hanya validasi approved_by = auth()->id()
     * - Tidak ada dependency ke role atau unit_kerja_id
     * - Backward compatible: approved_by bisa null untuk data lama (auto-allow)
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'catatan_atasan' => 'required|string|max:1000',
        ]);

        $skp = SkpTahunan::findOrFail($id);

        // Verify status
        if ($skp->status !== 'DIAJUKAN') {
            return back()->with('error', 'SKP tidak dalam status diajukan');
        }

        // ========================================================================
        // REJECTION VALIDATION BERBASIS approved_by (ONLY)
        // Tidak ada dependency ke role atau unit_kerja_id
        // ========================================================================

        $atasan = Auth::user();

        // VALIDATION: Hanya check approved_by
        // Backward compatible: jika approved_by null, allow (data lama)
        if ($skp->approved_by != null && (int)$skp->approved_by !== (int)$atasan->id) {
            return back()->with('error', 'Anda tidak memiliki akses untuk menolak SKP ini. SKP ini bukan tanggung jawab Anda.');
        }

        // Update status
        $skp->update([
            'status' => 'DITOLAK',
            'catatan_atasan' => $validated['catatan_atasan'],
            'approved_by' => $atasan->id,
            'approved_at' => now(),
        ]);

        return redirect()
            ->route('atasan.skp-tahunan.show', $id)
            ->with('success', 'SKP Tahunan ditolak. ASN akan memperbaiki.');
    }

    /**
     * Setujui permintaan revisi SKP Tahunan
     *
     * BUSINESS RULES:
     * - Hanya PIMPINAN yang bisa menyetujui
     * - SKP harus berstatus REVISI_DIAJUKAN
     * - Menggunakan Policy authorization
     * - Status kembali ke DRAFT agar ASN bisa edit
     * - RHK dan Kinerja Harian TIDAK terpengaruh
     */
    public function setujuiRevisi(Request $request, SkpTahunan $skpTahunan)
    {
        // AUTHORIZATION menggunakan Policy
        $this->authorize('approveRevision', $skpTahunan);

        $validated = $request->validate([
            'catatan_revisi' => 'nullable|string|max:1000',
        ], [
            'catatan_revisi.max' => 'Catatan revisi maksimal 1000 karakter',
        ]);

        // Update status kembali ke DRAFT
        $skpTahunan->update([
            'status' => 'DRAFT',
            'catatan_revisi' => $validated['catatan_revisi'] ?? null,
            'revisi_disetujui_at' => now(),
        ]);

        return redirect()
            ->route('atasan.skp-tahunan.show', $skpTahunan->id)
            ->with('success', 'Permintaan revisi DISETUJUI. ASN sekarang dapat mengedit SKP Tahunan.');
    }

    /**
     * Tolak permintaan revisi SKP Tahunan
     *
     * BUSINESS RULES:
     * - Hanya PIMPINAN yang bisa menolak
     * - SKP harus berstatus REVISI_DIAJUKAN
     * - Menggunakan Policy authorization
     * - Status menjadi REVISI_DITOLAK
     * - ASN tidak bisa edit SKP, harus ajukan revisi lagi jika perlu
     */
    public function tolakRevisi(Request $request, SkpTahunan $skpTahunan)
    {
        // AUTHORIZATION menggunakan Policy
        $this->authorize('rejectRevision', $skpTahunan);

        $validated = $request->validate([
            'catatan_revisi' => 'required|string|min:10|max:1000',
        ], [
            'catatan_revisi.required' => 'Alasan penolakan revisi wajib diisi',
            'catatan_revisi.min' => 'Alasan penolakan minimal 10 karakter',
            'catatan_revisi.max' => 'Alasan penolakan maksimal 1000 karakter',
        ]);

        // Update status menjadi REVISI_DITOLAK
        $skpTahunan->update([
            'status' => 'REVISI_DITOLAK',
            'catatan_revisi' => $validated['catatan_revisi'],
        ]);

        return redirect()
            ->route('atasan.skp-tahunan.show', $skpTahunan->id)
            ->with('warning', 'Permintaan revisi DITOLAK. Alasan telah dikirim ke ASN.');
    }
}
