<?php

namespace App\Http\Controllers\Api\Asn;

use App\Http\Controllers\Controller;
use App\Models\SkpTahunan;
use App\Models\SkpTahunanDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * SKP Tahunan Controller (HEADER-DETAIL PATTERN)
 *
 * ENTERPRISE ARCHITECTURE:
 * - skp_tahunan = HEADER (user_id + tahun + status)
 * - skp_tahunan_detail = DETAIL (butir kinerja, multiple rows)
 * - ASN BOLEH input berkali-kali dengan sasaran/indikator sama
 * - Validasi UNIQUE hanya: user_id + tahun
 */
class SkpTahunanController extends Controller
{
    /**
     * Get list of SKP Tahunan HEADERS for authenticated ASN
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = SkpTahunan::with(['details.sasaranKegiatan', 'details.indikatorKinerja', 'approver'])
            ->where('user_id', $user->id);

        // Filter by tahun
        if ($request->has('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $skpList = $query->orderBy('tahun', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Add calculated fields
        $skpList->each(function ($skp) {
            $skp->total_butir_kinerja = $skp->total_butir_kinerja;
            $skp->capaian_persen = $skp->capaian_persen;
            $skp->can_add_details = $skp->canAddDetails();
            $skp->can_be_submitted = $skp->canBeSubmitted();
            $skp->can_edit_details = $skp->canEditDetails();
        });

        return response()->json([
            'success' => true,
            'data' => $skpList
        ]);
    }

    /**
     * Get detail of specific SKP Tahunan HEADER with all DETAILS
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $skp = SkpTahunan::with([
            'details.sasaranKegiatan',
            'details.indikatorKinerja',
            'approver'
        ])->find($id);

        if (!$skp) {
            return response()->json([
                'success' => false,
                'message' => 'SKP Tahunan tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if ($skp->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke SKP Tahunan ini'
            ], 403);
        }

        $skp->total_butir_kinerja = $skp->total_butir_kinerja;
        $skp->capaian_persen = $skp->capaian_persen;
        $skp->can_add_details = $skp->canAddDetails();
        $skp->can_be_submitted = $skp->canBeSubmitted();
        $skp->can_edit_details = $skp->canEditDetails();

        return response()->json([
            'success' => true,
            'data' => $skp
        ]);
    }

    /**
     * ADD BUTIR KINERJA (Detail) to existing or new SKP Tahunan Header
     *
     * FLOW:
     * 1. Cek apakah header untuk tahun ini sudah ada
     * 2. Jika belum ada → buat header baru (status DRAFT)
     * 3. Jika sudah ada → validasi status (harus DRAFT atau DITOLAK)
     * 4. Tambahkan detail baru (BOLEH duplikat sasaran/indikator)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'tahun' => 'required|integer|min:2020|max:2100',
            'sasaran_kegiatan_id' => 'required|integer|exists:sasaran_kegiatan,id',
            'indikator_kinerja_id' => 'required|integer|exists:indikator_kinerja,id',
            'target_tahunan' => 'required|integer|min:0',
            'satuan' => 'required|string|max:50',
            'rencana_aksi' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // 1. Get or Create HEADER
            $header = SkpTahunan::forUserAndYear($user->id, $request->tahun)->first();

            if (!$header) {
                // Buat header baru
                $header = SkpTahunan::create([
                    'user_id' => $user->id,
                    'tahun' => $request->tahun,
                    'status' => 'DRAFT',
                ]);
            } else {
                // Validasi: header harus DRAFT atau DITOLAK
                if (!$header->canAddDetails()) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'SKP Tahunan dengan status ' . $header->status . ' tidak dapat ditambahkan butir kinerja baru. Hanya SKP dengan status DRAFT atau DITOLAK yang dapat diedit.'
                    ], 403);
                }
            }

            // 2. Tambahkan DETAIL (Butir Kinerja)
            // PENTING: TIDAK ADA validasi unique pada sasaran/indikator
            $detail = SkpTahunanDetail::create([
                'skp_tahunan_id' => $header->id,
                'sasaran_kegiatan_id' => $request->sasaran_kegiatan_id,
                'indikator_kinerja_id' => $request->indikator_kinerja_id,
                'target_tahunan' => $request->target_tahunan,
                'satuan' => $request->satuan,
                'rencana_aksi' => $request->rencana_aksi,
                'realisasi_tahunan' => 0,
            ]);

            DB::commit();

            // Load relationships untuk response
            $header->load(['details.sasaranKegiatan', 'details.indikatorKinerja']);
            $header->total_butir_kinerja = $header->total_butir_kinerja;
            $header->can_add_details = $header->canAddDetails();
            $header->can_be_submitted = $header->canBeSubmitted();

            $message = $header->details()->count() == 1
                ? 'SKP Tahunan berhasil dibuat dengan butir kinerja pertama'
                : 'Butir kinerja berhasil ditambahkan ke SKP Tahunan';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'header' => $header,
                    'detail' => $detail->load(['sasaranKegiatan', 'indikatorKinerja'])
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan butir kinerja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET SINGLE DETAIL (Butir Kinerja) by ID
     * URL: GET /api/asn/skp-tahunan/detail/{detailId}
     *
     * Used by edit page to load existing detail data
     */
    public function showDetail(Request $request, $detailId)
    {
        $user = $request->user();

        $detail = SkpTahunanDetail::with([
            'skpTahunan',
            'sasaranKegiatan',
            'indikatorKinerja'
        ])->find($detailId);

        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Butir kinerja tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if ($detail->skpTahunan->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke butir kinerja ini'
            ], 403);
        }

        // Add additional context
        $detail->can_edit = $detail->skpTahunan->canEditDetails();

        return response()->json([
            'success' => true,
            'data' => $detail
        ]);
    }

    /**
     * UPDATE DETAIL (Butir Kinerja)
     * URL: PUT /api/asn/skp-tahunan/detail/{detailId}
     */
    public function updateDetail(Request $request, $detailId)
    {
        $user = $request->user();

        $detail = SkpTahunanDetail::with('skpTahunan')->find($detailId);

        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Butir kinerja tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if ($detail->skpTahunan->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke butir kinerja ini'
            ], 403);
        }

        // Check if can edit
        if (!$detail->skpTahunan->canEditDetails()) {
            return response()->json([
                'success' => false,
                'message' => 'Butir kinerja dengan status ' . $detail->skpTahunan->status . ' tidak dapat diedit'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'sasaran_kegiatan_id' => 'required|integer|exists:sasaran_kegiatan,id',
            'indikator_kinerja_id' => 'required|integer|exists:indikator_kinerja,id',
            'target_tahunan' => 'required|integer|min:0',
            'satuan' => 'required|string|max:50',
            'rencana_aksi' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $detail->update($request->only([
                'sasaran_kegiatan_id',
                'indikator_kinerja_id',
                'target_tahunan',
                'satuan',
                'rencana_aksi'
            ]));

            $detail->load(['sasaranKegiatan', 'indikatorKinerja', 'skpTahunan']);

            return response()->json([
                'success' => true,
                'message' => 'Butir kinerja berhasil diupdate',
                'data' => $detail
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate butir kinerja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE DETAIL (Butir Kinerja)
     * URL: DELETE /api/asn/skp-tahunan/detail/{detailId}
     */
    public function deleteDetail(Request $request, $detailId)
    {
        $user = $request->user();

        $detail = SkpTahunanDetail::with('skpTahunan')->find($detailId);

        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Butir kinerja tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if ($detail->skpTahunan->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke butir kinerja ini'
            ], 403);
        }

        // Check if can delete
        if (!$detail->skpTahunan->canEditDetails()) {
            return response()->json([
                'success' => false,
                'message' => 'Butir kinerja dengan status ' . $detail->skpTahunan->status . ' tidak dapat dihapus'
            ], 403);
        }

        try {
            $headerId = $detail->skp_tahunan_id;
            $detail->delete();

            // Jika tidak ada detail lagi, hapus header juga
            $header = SkpTahunan::find($headerId);
            if ($header && $header->details()->count() == 0) {
                $header->delete();
                $message = 'Butir kinerja dan SKP Tahunan berhasil dihapus (tidak ada butir kinerja tersisa)';
            } else {
                $message = 'Butir kinerja berhasil dihapus';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus butir kinerja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * SUBMIT SKP Tahunan for approval (DRAFT -> DIAJUKAN)
     * URL: POST /api/asn/skp-tahunan/{id}/submit
     */
    public function submit(Request $request, $id)
    {
        $user = $request->user();

        $skp = SkpTahunan::with('details')->find($id);

        if (!$skp) {
            return response()->json([
                'success' => false,
                'message' => 'SKP Tahunan tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if ($skp->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke SKP Tahunan ini'
            ], 403);
        }

        // Validasi: harus ada minimal 1 butir kinerja
        if ($skp->details()->count() == 0) {
            return response()->json([
                'success' => false,
                'message' => 'SKP Tahunan harus memiliki minimal 1 butir kinerja sebelum dapat diajukan'
            ], 422);
        }

        // Validasi: status harus DRAFT atau DITOLAK
        if (!$skp->canBeSubmitted()) {
            return response()->json([
                'success' => false,
                'message' => 'SKP Tahunan dengan status ' . $skp->status . ' tidak dapat diajukan'
            ], 403);
        }

        try {
            $skp->update([
                'status' => 'DIAJUKAN',
                'catatan_atasan' => null,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            $skp->load(['details.sasaranKegiatan', 'details.indikatorKinerja']);

            return response()->json([
                'success' => true,
                'message' => 'SKP Tahunan berhasil diajukan untuk persetujuan',
                'data' => $skp
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengajukan SKP Tahunan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of APPROVED SKP Tahunan (untuk dipilih di SKP Triwulan)
     * URL: GET /api/asn/skp-tahunan/approved
     */
    public function getApprovedList(Request $request)
    {
        $user = $request->user();

        $skpList = SkpTahunan::with(['details.sasaranKegiatan', 'details.indikatorKinerja'])
            ->where('user_id', $user->id)
            ->where('status', 'DISETUJUI')
            ->orderBy('tahun', 'desc')
            ->get();

        // Add helper attributes
        $skpList->each(function ($skp) {
            $skp->total_butir_kinerja = $skp->total_butir_kinerja;
            $skp->display_name = $skp->display_name;
        });

        return response()->json([
            'success' => true,
            'data' => $skpList
        ]);
    }
}
