<?php

namespace App\Http\Controllers\Api\Atasan;

use App\Http\Controllers\Controller;
use App\Models\SkpTahunan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApprovalController extends Controller
{
    /**
     * Get statistics for Atasan dashboard (SKP Tahunan V2)
     */
    public function stats(Request $request)
    {
        $totalPending = SkpTahunan::where('status', 'DIAJUKAN')->count();
        $totalApproved = SkpTahunan::where('status', 'DISETUJUI')->count();
        $totalRejected = SkpTahunan::where('status', 'DITOLAK')->count();
        $totalAll = SkpTahunan::count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_pending' => $totalPending,
                'total_approved' => $totalApproved,
                'total_rejected' => $totalRejected,
                'total_all' => $totalAll,
            ]
        ]);
    }

    // ============================================================================
    // SKP TAHUNAN APPROVAL (NEW)
    // ============================================================================

    /**
     * Get list of SKP Tahunan (HEADER) yang perlu di-approve
     * Menampilkan header dengan jumlah butir kinerja (detail count)
     */
    public function indexSkpTahunan(Request $request)
    {
        $query = SkpTahunan::with([
            'user.unit',
            'approver',
            'details.indikatorKinerja.sasaranKegiatan'
        ])->withCount('details');

        // Filter by status (default: DIAJUKAN)
        $status = $request->get('status', 'DIAJUKAN');
        if ($status) {
            $query->where('status', $status);
        }

        // Optional filter by tahun
        if ($request->has('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        // Optional filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $skpList = $query->orderBy('created_at', 'desc')->get();

        // Add calculated fields
        $skpList->each(function ($skp) {
            $skp->total_butir_kinerja = $skp->details_count;
            $skp->total_target = $skp->total_target;
            $skp->total_realisasi = $skp->total_realisasi;
            $skp->capaian_persen = $skp->capaian_persen;
        });

        return response()->json([
            'success' => true,
            'data' => $skpList
        ]);
    }

    /**
     * Get detail of specific SKP Tahunan (HEADER) for approval
     * Menampilkan header dengan semua butir kinerja (details)
     */
    public function showSkpTahunan(Request $request, $id)
    {
        $skpTahunan = SkpTahunan::with([
            'user.unit',
            'approver',
            'details.indikatorKinerja.sasaranKegiatan'
        ])->find($id);

        if (!$skpTahunan) {
            return response()->json([
                'success' => false,
                'message' => 'SKP Tahunan tidak ditemukan'
            ], 404);
        }

        // Add calculated fields to header
        $skpTahunan->total_butir_kinerja = $skpTahunan->total_butir_kinerja;
        $skpTahunan->total_target = $skpTahunan->total_target;
        $skpTahunan->total_realisasi = $skpTahunan->total_realisasi;
        $skpTahunan->capaian_persen = $skpTahunan->capaian_persen;

        // Add calculated fields to each detail
        $skpTahunan->details->each(function ($detail) {
            $detail->capaian_persen = $detail->capaian_persen;
        });

        return response()->json([
            'success' => true,
            'data' => $skpTahunan
        ]);
    }

    /**
     * Approve SKP Tahunan (HEADER)
     * Menyetujui header akan mempengaruhi semua butir kinerja (details)
     */
    public function approveSkpTahunan(Request $request, $id)
    {
        $atasan = $request->user();

        $skpTahunan = SkpTahunan::with('details')->find($id);

        if (!$skpTahunan) {
            return response()->json([
                'success' => false,
                'message' => 'SKP Tahunan tidak ditemukan'
            ], 404);
        }

        // Validate: must have at least 1 butir kinerja
        if ($skpTahunan->details()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'SKP Tahunan harus memiliki minimal 1 butir kinerja untuk dapat disetujui'
            ], 403);
        }

        if ($skpTahunan->status !== 'DIAJUKAN') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya SKP Tahunan dengan status DIAJUKAN yang dapat disetujui'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'catatan_atasan' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $skpTahunan->update([
                'status' => 'DISETUJUI',
                'catatan_atasan' => $request->catatan_atasan,
                'approved_by' => $atasan->id,
                'approved_at' => now(),
            ]);

            $skpTahunan->load([
                'user.unit',
                'approver',
                'details.indikatorKinerja.sasaranKegiatan'
            ]);

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'SKP Tahunan berhasil disetujui dengan %d butir kinerja',
                    $skpTahunan->details->count()
                ),
                'data' => $skpTahunan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui SKP Tahunan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject SKP Tahunan (HEADER)
     * Menolak header akan mempengaruhi semua butir kinerja (details)
     * ASN dapat memperbaiki dan menambahkan butir kinerja baru setelah ditolak
     */
    public function rejectSkpTahunan(Request $request, $id)
    {
        $atasan = $request->user();

        $skpTahunan = SkpTahunan::with('details')->find($id);

        if (!$skpTahunan) {
            return response()->json([
                'success' => false,
                'message' => 'SKP Tahunan tidak ditemukan'
            ], 404);
        }

        if ($skpTahunan->status !== 'DIAJUKAN') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya SKP Tahunan dengan status DIAJUKAN yang dapat ditolak'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'catatan_atasan' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal. Catatan wajib diisi saat menolak.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $skpTahunan->update([
                'status' => 'DITOLAK',
                'catatan_atasan' => $request->catatan_atasan,
                'approved_by' => $atasan->id,
                'approved_at' => now(),
            ]);

            $skpTahunan->load([
                'user.unit',
                'approver',
                'details.indikatorKinerja.sasaranKegiatan'
            ]);

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'SKP Tahunan ditolak. ASN dapat memperbaiki %d butir kinerja yang ada atau menambah butir kinerja baru.',
                    $skpTahunan->details->count()
                ),
                'data' => $skpTahunan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menolak SKP Tahunan: ' . $e->getMessage()
            ], 500);
        }
    }

}
