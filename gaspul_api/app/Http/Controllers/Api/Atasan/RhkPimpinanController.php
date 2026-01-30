<?php

namespace App\Http\Controllers\Api\Atasan;

use App\Http\Controllers\Controller;
use App\Models\RhkPimpinan;
use App\Models\IndikatorKinerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

/**
 * RHK Pimpinan Controller
 *
 * Managed by: Atasan Langsung / Kabid
 * Purpose: Mengelola Rencana Hasil Kerja Pimpinan yang di Intervensi
 */
class RhkPimpinanController extends Controller
{
    /**
     * Get all RHK Pimpinan with filters
     * Filter by atasan's unit kerja
     */
    public function index(Request $request)
    {
        try {
            $atasan = Auth::user();

            $query = RhkPimpinan::with([
                'indikatorKinerja.sasaranKegiatan',
                'unitKerja',
                'skpTahunanDetails'
            ])->withCount('skpTahunanDetails');

            // Filter by atasan's unit kerja
            $query->where('unit_kerja_id', $atasan->unit_kerja_id);

            // Filter by indikator_kinerja_id
            if ($request->has('indikator_kinerja_id')) {
                $query->where('indikator_kinerja_id', $request->indikator_kinerja_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('rhk_pimpinan', 'like', "%{$search}%");
            }

            // Get all (no pagination for frontend compatibility)
            $rhkList = $query->orderBy('created_at', 'desc')->get();

            return response()->json(['data' => $rhkList]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch RHK Pimpinan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active RHK Pimpinan (for dropdown)
     * Filter by atasan's unit kerja
     */
    public function getActive()
    {
        try {
            $atasan = Auth::user();

            $rhkList = RhkPimpinan::active()
                ->where('unit_kerja_id', $atasan->unit_kerja_id)
                ->with(['indikatorKinerja.sasaranKegiatan', 'unitKerja'])
                ->orderBy('indikator_kinerja_id')
                ->orderBy('rhk_pimpinan')
                ->get();

            return response()->json($rhkList);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch active RHK Pimpinan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get RHK Pimpinan by Indikator Kinerja
     * Filter by atasan's unit kerja
     */
    public function getByIndikatorKinerja($indikatorKinerjaId)
    {
        try {
            $atasan = Auth::user();

            $rhkList = RhkPimpinan::active()
                ->where('indikator_kinerja_id', $indikatorKinerjaId)
                ->where('unit_kerja_id', $atasan->unit_kerja_id)
                ->with(['indikatorKinerja', 'unitKerja'])
                ->orderBy('rhk_pimpinan')
                ->get();

            return response()->json($rhkList);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch RHK Pimpinan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail RHK Pimpinan
     */
    public function show($id)
    {
        try {
            $rhk = RhkPimpinan::with(['indikatorKinerja.sasaranKegiatan', 'skpTahunanDetails'])
                ->findOrFail($id);

            return response()->json($rhk);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'RHK Pimpinan not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create new RHK Pimpinan
     * Auto-assign to atasan's unit kerja
     */
    public function store(Request $request)
    {
        try {
            $atasan = Auth::user();

            $validator = Validator::make($request->all(), [
                'indikator_kinerja_id' => 'required|exists:indikator_kinerja,id',
                'rhk_pimpinan' => 'required|string|max:1000',
                'status' => 'sometimes|in:AKTIF,NONAKTIF',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validasi: Indikator Kinerja harus aktif
            $indikator = IndikatorKinerja::find($request->indikator_kinerja_id);
            if (!$indikator || $indikator->status !== 'AKTIF') {
                return response()->json([
                    'message' => 'Indikator Kinerja tidak aktif atau tidak ditemukan'
                ], 422);
            }

            // Validasi: RHK Pimpinan tidak boleh duplikat untuk indikator yang sama di unit kerja yang sama
            $exists = RhkPimpinan::where('indikator_kinerja_id', $request->indikator_kinerja_id)
                ->where('unit_kerja_id', $atasan->unit_kerja_id)
                ->where('rhk_pimpinan', $request->rhk_pimpinan)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'RHK Pimpinan dengan nama yang sama sudah ada untuk indikator kinerja ini di unit kerja Anda'
                ], 422);
            }

            $rhk = RhkPimpinan::create([
                'indikator_kinerja_id' => $request->indikator_kinerja_id,
                'unit_kerja_id' => $atasan->unit_kerja_id,
                'rhk_pimpinan' => $request->rhk_pimpinan,
                'status' => $request->status ?? 'AKTIF',
            ]);

            $rhk->load(['indikatorKinerja', 'unitKerja']);

            return response()->json([
                'message' => 'RHK Pimpinan created successfully',
                'data' => $rhk
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create RHK Pimpinan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update RHK Pimpinan
     * Security: Only allow updating RHK from atasan's unit kerja
     */
    public function update(Request $request, $id)
    {
        try {
            $atasan = Auth::user();
            $rhk = RhkPimpinan::where('unit_kerja_id', $atasan->unit_kerja_id)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'indikator_kinerja_id' => 'sometimes|exists:indikator_kinerja,id',
                'rhk_pimpinan' => 'sometimes|string|max:1000',
                'status' => 'sometimes|in:AKTIF,NONAKTIF',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validasi: RHK Pimpinan tidak boleh duplikat (exclude current)
            if ($request->has('rhk_pimpinan') || $request->has('indikator_kinerja_id')) {
                $checkIndikatorId = $request->indikator_kinerja_id ?? $rhk->indikator_kinerja_id;
                $checkRhk = $request->rhk_pimpinan ?? $rhk->rhk_pimpinan;

                $exists = RhkPimpinan::where('indikator_kinerja_id', $checkIndikatorId)
                    ->where('unit_kerja_id', $atasan->unit_kerja_id)
                    ->where('rhk_pimpinan', $checkRhk)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'message' => 'RHK Pimpinan dengan nama yang sama sudah ada untuk indikator kinerja ini di unit kerja Anda'
                    ], 422);
                }
            }

            $rhk->update($request->only([
                'indikator_kinerja_id',
                'rhk_pimpinan',
                'status',
            ]));

            $rhk->load(['indikatorKinerja', 'unitKerja']);

            return response()->json([
                'message' => 'RHK Pimpinan updated successfully',
                'data' => $rhk
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update RHK Pimpinan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete RHK Pimpinan
     * Security: Only allow deleting RHK from atasan's unit kerja
     */
    public function destroy($id)
    {
        try {
            $atasan = Auth::user();
            $rhk = RhkPimpinan::where('unit_kerja_id', $atasan->unit_kerja_id)
                ->findOrFail($id);

            // Validasi: Tidak bisa dihapus jika sudah digunakan di SKP
            $usageCount = $rhk->skpTahunanDetails()->count();
            if ($usageCount > 0) {
                return response()->json([
                    'message' => "RHK Pimpinan tidak dapat dihapus karena sudah digunakan di {$usageCount} SKP Tahunan"
                ], 422);
            }

            $rhk->delete();

            return response()->json([
                'message' => 'RHK Pimpinan deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete RHK Pimpinan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
