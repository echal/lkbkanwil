<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use App\Models\SkpTahunan;
use App\Models\User;
use App\Models\UnitKerja;
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

        // Query SKP Tahunan dari bawahan
        $query = SkpTahunan::with([
            'user.unitKerja',
            'details.indikatorKinerja.sasaranKegiatan',
            'approver'
        ]);

        // Filter by unit kerja - ATASAN melihat semua SKP di unit kerja yang sama
        // Jika atasan punya unit_kerja_id, filter by that
        // Jika NULL, bisa lihat semua atau filter by unit_kerja_id dari request
        if ($unitKerjaId) {
            $query->whereHas('user', function($q) use ($unitKerjaId) {
                $q->where('unit_kerja_id', $unitKerjaId);
            });
        } elseif ($atasan->unit_kerja_id) {
            $query->whereHas('user', function($q) use ($atasan) {
                $q->where('unit_kerja_id', $atasan->unit_kerja_id);
            });
        }

        // Filter by tahun
        $query->where('tahun', $tahun);

        // Filter by status (optional)
        if ($status) {
            $query->where('status', $status);
        }

        // Filter by ASN name (optional)
        if ($searchAsn) {
            $query->whereHas('user', function($q) use ($searchAsn) {
                $q->where('name', 'like', '%' . $searchAsn . '%')
                  ->orWhere('nip', 'like', '%' . $searchAsn . '%');
            });
        }

        // Only show ASN users (not ADMIN or ATASAN)
        $query->whereHas('user', function($q) {
            $q->where('role', 'ASN');
        });

        // Order by latest
        $skpList = $query->latest()->paginate(15);

        // Get unit kerja list for filter
        $unitKerjaList = UnitKerja::where('status', 'AKTIF')->get();

        // Prepare data for view
        $skpData = $skpList->map(function($skp) {
            return [
                'id' => $skp->id,
                'asn_nama' => $skp->user->name,
                'asn_nip' => $skp->user->nip ?? '-',
                'asn_jabatan' => $skp->user->jabatan ?? '-',
                'unit_kerja' => $skp->user->unitKerja->nama_unit ?? '-',
                'tahun' => $skp->tahun,
                'status' => $skp->status,
                'total_rhk' => $skp->details->count(),
                'catatan_atasan' => $skp->catatan_atasan,
                'approved_by_name' => $skp->approver->name ?? null,
                'approved_at' => $skp->approved_at,
                'can_approve' => in_array($skp->status, ['DIAJUKAN']),
            ];
        });

        return view('atasan.skp-tahunan.index', [
            'skpList' => $skpList,
            'skpData' => $skpData,
            'tahun' => $tahun,
            'status' => $status,
            'unitKerjaId' => $unitKerjaId,
            'searchAsn' => $searchAsn,
            'unitKerjaList' => $unitKerjaList,
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
     */
    public function show($id)
    {
        $skp = SkpTahunan::with([
            'user.unitKerja',
            'details.indikatorKinerja.sasaranKegiatan',
            'details.rencanaAksiBulanan',
            'approver'
        ])->findOrFail($id);

        // Verify atasan can view this SKP (same unit_kerja)
        $atasan = Auth::user();
        if ($atasan->unit_kerja_id && $skp->user->unit_kerja_id !== $atasan->unit_kerja_id) {
            abort(403, 'Anda tidak memiliki akses ke SKP ini');
        }

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
            'skp' => $skp,
            'asn' => $skp->user,
            'rhkList' => $rhkList,
            'canApprove' => in_array($skp->status, ['DIAJUKAN']),
        ]);
    }

    /**
     * Approve SKP Tahunan
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

        // Verify atasan can approve this SKP (same unit_kerja)
        $atasan = Auth::user();
        if ($atasan->unit_kerja_id && $skp->user->unit_kerja_id !== $atasan->unit_kerja_id) {
            return back()->with('error', 'Anda tidak memiliki akses ke SKP ini');
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

        // Verify atasan can reject this SKP (same unit_kerja)
        $atasan = Auth::user();
        if ($atasan->unit_kerja_id && $skp->user->unit_kerja_id !== $atasan->unit_kerja_id) {
            return back()->with('error', 'Anda tidak memiliki akses ke SKP ini');
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
}
