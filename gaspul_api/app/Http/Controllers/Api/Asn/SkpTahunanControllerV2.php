<?php

namespace App\Http\Controllers\Api\Asn;

use App\Http\Controllers\Controller;
use App\Models\SkpTahunan;
use App\Models\SkpTahunanDetail;
use App\Models\RhkPimpinan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * SKP Tahunan Controller V2.0 (Total Refactor)
 *
 * PERUBAHAN KRUSIAL:
 * - HEADER: user_id + tahun (UNIQUE)
 * - DETAIL: rhk_pimpinan_id + rencana_aksi (NO DB constraint, validasi di aplikasi)
 * - ASN BOLEH pilih RHK yang sama berkali-kali, asal rencana_aksi berbeda
 * - Auto-generate 12 bulan Rencana Aksi Bulanan setelah detail dibuat
 */
class SkpTahunanControllerV2 extends Controller
{
    /**
     * Get list of SKP Tahunan HEADERS for authenticated ASN
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = SkpTahunan::with(['details.rhkPimpinan.indikatorKinerja.sasaranKegiatan', 'approver'])
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

            return response()->json($skpList);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch SKP Tahunan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail of specific SKP Tahunan HEADER with all DETAILS
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            $skp = SkpTahunan::with([
                'details.rhkPimpinan.indikatorKinerja.sasaranKegiatan',
                'details.rencanaAksiBulanan',
                'approver'
            ])->find($id);

            if (!$skp) {
                return response()->json([
                    'message' => 'SKP Tahunan tidak ditemukan'
                ], 404);
            }

            // Check ownership
            if ($skp->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke SKP Tahunan ini'
                ], 403);
            }

            $skp->total_butir_kinerja = $skp->total_butir_kinerja;
            $skp->capaian_persen = $skp->capaian_persen;
            $skp->can_add_details = $skp->canAddDetails();
            $skp->can_be_submitted = $skp->canBeSubmitted();
            $skp->can_edit_details = $skp->canEditDetails();

            return response()->json($skp);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch SKP Tahunan detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create or get existing SKP Tahunan HEADER
     * (One header per user per year)
     */
    public function createOrGetHeader(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'tahun' => 'required|integer|min:2020|max:2100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get or create header
            $skp = SkpTahunan::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'tahun' => $request->tahun,
                ],
                [
                    'status' => 'DRAFT',
                ]
            );

            $skp->load(['details.rhkPimpinan.indikatorKinerja.sasaranKegiatan']);

            return response()->json([
                'message' => $skp->wasRecentlyCreated ? 'SKP Tahunan header created' : 'SKP Tahunan header retrieved',
                'data' => $skp
            ], $skp->wasRecentlyCreated ? 201 : 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create/get SKP Tahunan header',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add detail (butir kinerja) to SKP Tahunan
     */
    public function addDetail(Request $request, $skpTahunanId)
    {
        try {
            $user = $request->user();

            $skp = SkpTahunan::find($skpTahunanId);

            if (!$skp) {
                return response()->json([
                    'message' => 'SKP Tahunan tidak ditemukan'
                ], 404);
            }

            // Check ownership
            if ($skp->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke SKP Tahunan ini'
                ], 403);
            }

            // Check if can add details
            if (!$skp->canAddDetails()) {
                return response()->json([
                    'message' => 'SKP Tahunan dengan status ' . $skp->status . ' tidak dapat ditambahkan butir kinerja'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'rhk_pimpinan_id' => 'required|exists:rhk_pimpinan,id',
                'target_tahunan' => 'required|integer|min:1',
                'satuan' => 'required|string|max:50',
                'rencana_aksi' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validasi: RHK Pimpinan harus aktif
            $rhk = RhkPimpinan::find($request->rhk_pimpinan_id);
            if (!$rhk || !$rhk->isActive()) {
                return response()->json([
                    'message' => 'RHK Pimpinan tidak aktif atau tidak ditemukan'
                ], 422);
            }

            // VALIDASI UNIQUE: skp_tahunan_id + rhk_pimpinan_id + rencana_aksi
            $exists = SkpTahunanDetail::where('skp_tahunan_id', $skpTahunanId)
                ->where('rhk_pimpinan_id', $request->rhk_pimpinan_id)
                ->where('rencana_aksi', $request->rencana_aksi)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'RHK Pimpinan dengan Rencana Aksi yang sama sudah ada di SKP Tahunan ini'
                ], 422);
            }

            DB::beginTransaction();

            // Create detail
            $detail = SkpTahunanDetail::create([
                'skp_tahunan_id' => $skpTahunanId,
                'rhk_pimpinan_id' => $request->rhk_pimpinan_id,
                'target_tahunan' => $request->target_tahunan,
                'satuan' => $request->satuan,
                'rencana_aksi' => $request->rencana_aksi,
                'realisasi_tahunan' => 0,
            ]);

            // Auto-generate 12 bulan Rencana Aksi Bulanan (handled by model event)
            // See SkpTahunanDetail::boot() method

            DB::commit();

            $detail->load(['rhkPimpinan.indikatorKinerja.sasaranKegiatan', 'rencanaAksiBulanan']);

            return response()->json([
                'message' => 'Butir kinerja berhasil ditambahkan. 12 periode bulanan telah dibuat.',
                'data' => $detail
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to add detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update detail (butir kinerja)
     */
    public function updateDetail(Request $request, $skpTahunanId, $detailId)
    {
        try {
            $user = $request->user();

            $skp = SkpTahunan::find($skpTahunanId);

            if (!$skp) {
                return response()->json([
                    'message' => 'SKP Tahunan tidak ditemukan'
                ], 404);
            }

            // Check ownership
            if ($skp->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke SKP Tahunan ini'
                ], 403);
            }

            $detail = SkpTahunanDetail::where('skp_tahunan_id', $skpTahunanId)
                ->find($detailId);

            if (!$detail) {
                return response()->json([
                    'message' => 'Detail tidak ditemukan'
                ], 404);
            }

            // Check if can edit
            if (!$detail->canBeEdited()) {
                return response()->json([
                    'message' => 'Detail dengan status ' . $skp->status . ' tidak dapat diubah'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'rhk_pimpinan_id' => 'sometimes|exists:rhk_pimpinan,id',
                'target_tahunan' => 'sometimes|integer|min:1',
                'satuan' => 'sometimes|string|max:50',
                'rencana_aksi' => 'sometimes|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // VALIDASI UNIQUE (exclude current detail)
            if ($request->has('rhk_pimpinan_id') || $request->has('rencana_aksi')) {
                $checkRhkId = $request->rhk_pimpinan_id ?? $detail->rhk_pimpinan_id;
                $checkRencanaAksi = $request->rencana_aksi ?? $detail->rencana_aksi;

                $exists = SkpTahunanDetail::where('skp_tahunan_id', $skpTahunanId)
                    ->where('rhk_pimpinan_id', $checkRhkId)
                    ->where('rencana_aksi', $checkRencanaAksi)
                    ->where('id', '!=', $detailId)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'message' => 'RHK Pimpinan dengan Rencana Aksi yang sama sudah ada di SKP Tahunan ini'
                    ], 422);
                }
            }

            $detail->update($request->only([
                'rhk_pimpinan_id',
                'target_tahunan',
                'satuan',
                'rencana_aksi',
            ]));

            $detail->load(['rhkPimpinan.indikatorKinerja.sasaranKegiatan', 'rencanaAksiBulanan']);

            return response()->json([
                'message' => 'Detail berhasil diperbarui',
                'data' => $detail
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete detail (butir kinerja)
     */
    public function deleteDetail(Request $request, $skpTahunanId, $detailId)
    {
        try {
            $user = $request->user();

            $skp = SkpTahunan::find($skpTahunanId);

            if (!$skp) {
                return response()->json([
                    'message' => 'SKP Tahunan tidak ditemukan'
                ], 404);
            }

            // Check ownership
            if ($skp->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke SKP Tahunan ini'
                ], 403);
            }

            $detail = SkpTahunanDetail::where('skp_tahunan_id', $skpTahunanId)
                ->find($detailId);

            if (!$detail) {
                return response()->json([
                    'message' => 'Detail tidak ditemukan'
                ], 404);
            }

            // Check if can delete
            if (!$detail->canBeEdited()) {
                return response()->json([
                    'message' => 'Detail dengan status ' . $skp->status . ' tidak dapat dihapus'
                ], 422);
            }

            DB::beginTransaction();

            // Delete detail (cascade akan menghapus rencana_aksi_bulanan dan progres_harian)
            $detail->delete();

            DB::commit();

            return response()->json([
                'message' => 'Detail berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit SKP Tahunan for approval
     */
    public function submit(Request $request, $id)
    {
        try {
            $user = $request->user();

            $skp = SkpTahunan::find($id);

            if (!$skp) {
                return response()->json([
                    'message' => 'SKP Tahunan tidak ditemukan'
                ], 404);
            }

            // Check ownership
            if ($skp->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke SKP Tahunan ini'
                ], 403);
            }

            // Check if can be submitted
            if (!$skp->canBeSubmitted()) {
                return response()->json([
                    'message' => 'SKP Tahunan tidak dapat diajukan. Pastikan status DRAFT/DITOLAK dan ada minimal 1 butir kinerja'
                ], 422);
            }

            $skp->update([
                'status' => 'DIAJUKAN',
            ]);

            return response()->json([
                'message' => 'SKP Tahunan berhasil diajukan untuk persetujuan',
                'data' => $skp
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit SKP Tahunan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
