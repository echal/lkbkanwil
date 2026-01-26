<?php

namespace App\Http\Controllers\Api\Atasan;

use App\Http\Controllers\Controller;
use App\Models\RhkPimpinan;
use App\Models\IndikatorKinerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
     */
    public function index(Request $request)
    {
        try {
            $query = RhkPimpinan::with([
                'indikatorKinerja.sasaranKegiatan',
                'skpTahunanDetails'
            ])->withCount('skpTahunanDetails');

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
     */
    public function getActive()
    {
        try {
            $rhkList = RhkPimpinan::active()
                ->with(['indikatorKinerja.sasaranKegiatan'])
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
     */
    public function getByIndikatorKinerja($indikatorKinerjaId)
    {
        try {
            $rhkList = RhkPimpinan::active()
                ->where('indikator_kinerja_id', $indikatorKinerjaId)
                ->with(['indikatorKinerja'])
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
     */
    public function store(Request $request)
    {
        try {
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

            // Validasi: RHK Pimpinan tidak boleh duplikat untuk indikator yang sama
            $exists = RhkPimpinan::where('indikator_kinerja_id', $request->indikator_kinerja_id)
                ->where('rhk_pimpinan', $request->rhk_pimpinan)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'RHK Pimpinan dengan nama yang sama sudah ada untuk indikator kinerja ini'
                ], 422);
            }

            $rhk = RhkPimpinan::create([
                'indikator_kinerja_id' => $request->indikator_kinerja_id,
                'rhk_pimpinan' => $request->rhk_pimpinan,
                'status' => $request->status ?? 'AKTIF',
            ]);

            $rhk->load('indikatorKinerja');

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
     */
    public function update(Request $request, $id)
    {
        try {
            $rhk = RhkPimpinan::findOrFail($id);

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
                    ->where('rhk_pimpinan', $checkRhk)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'message' => 'RHK Pimpinan dengan nama yang sama sudah ada untuk indikator kinerja ini'
                    ], 422);
                }
            }

            $rhk->update($request->only([
                'indikator_kinerja_id',
                'rhk_pimpinan',
                'status',
            ]));

            $rhk->load('indikatorKinerja');

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
     */
    public function destroy($id)
    {
        try {
            $rhk = RhkPimpinan::findOrFail($id);

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
