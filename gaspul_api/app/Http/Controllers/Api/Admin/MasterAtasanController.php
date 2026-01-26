<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterAtasan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Master Atasan Controller
 *
 * Managed by: Admin
 * Purpose: Mengelola relasi ASN dengan Atasan Langsung per tahun
 */
class MasterAtasanController extends Controller
{
    /**
     * Get all master atasan with filters
     */
    public function index(Request $request)
    {
        try {
            $query = MasterAtasan::with(['asn', 'atasan']);

            // Filter by tahun
            if ($request->has('tahun')) {
                $query->where('tahun', $request->tahun);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by ASN
            if ($request->has('asn_id')) {
                $query->where('asn_id', $request->asn_id);
            }

            // Filter by Atasan
            if ($request->has('atasan_id')) {
                $query->where('atasan_id', $request->atasan_id);
            }

            // Search by name
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('asn', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('atasan', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            // Get all (without pagination for simplicity)
            $masterAtasan = $query->orderBy('tahun', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $masterAtasan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch master atasan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail master atasan
     */
    public function show($id)
    {
        try {
            $masterAtasan = MasterAtasan::with(['asn', 'atasan'])->findOrFail($id);

            return response()->json($masterAtasan);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Master atasan not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create new master atasan
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'asn_id' => 'required|exists:users,id',
                'atasan_id' => 'required|exists:users,id|different:asn_id',
                'tahun' => 'required|integer|min:2020|max:2100',
                'status' => 'sometimes|in:AKTIF,NONAKTIF',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validasi: ASN tidak bisa jadi atasan dirinya sendiri
            if ($request->asn_id == $request->atasan_id) {
                return response()->json([
                    'message' => 'ASN tidak bisa menjadi atasan dirinya sendiri'
                ], 422);
            }

            // Validasi: ASN sudah punya atasan untuk tahun ini
            $exists = MasterAtasan::where('asn_id', $request->asn_id)
                ->where('tahun', $request->tahun)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'ASN sudah memiliki atasan untuk tahun ' . $request->tahun
                ], 422);
            }

            // Validasi: Pastikan ASN memiliki role ASN
            $asn = User::find($request->asn_id);
            if (!$asn || $asn->role !== 'ASN') {
                return response()->json([
                    'message' => 'User yang dipilih bukan ASN'
                ], 422);
            }

            // Validasi: Pastikan Atasan memiliki role ATASAN
            $atasan = User::find($request->atasan_id);
            if (!$atasan || $atasan->role !== 'ATASAN') {
                return response()->json([
                    'message' => 'User yang dipilih bukan Atasan'
                ], 422);
            }

            $masterAtasan = MasterAtasan::create([
                'asn_id' => $request->asn_id,
                'atasan_id' => $request->atasan_id,
                'tahun' => $request->tahun,
                'status' => $request->status ?? 'AKTIF',
            ]);

            $masterAtasan->load(['asn', 'atasan']);

            return response()->json([
                'message' => 'Master atasan created successfully',
                'data' => $masterAtasan
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create master atasan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update master atasan
     */
    public function update(Request $request, $id)
    {
        try {
            $masterAtasan = MasterAtasan::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'asn_id' => 'sometimes|exists:users,id',
                'atasan_id' => 'sometimes|exists:users,id',
                'tahun' => 'sometimes|integer|min:2020|max:2100',
                'status' => 'sometimes|in:AKTIF,NONAKTIF',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validasi: ASN tidak bisa jadi atasan dirinya sendiri
            $asnId = $request->asn_id ?? $masterAtasan->asn_id;
            $atasanId = $request->atasan_id ?? $masterAtasan->atasan_id;

            if ($asnId == $atasanId) {
                return response()->json([
                    'message' => 'ASN tidak bisa menjadi atasan dirinya sendiri'
                ], 422);
            }

            // Validasi: Unique constraint (exclude current record)
            if ($request->has('asn_id') || $request->has('tahun')) {
                $checkAsnId = $request->asn_id ?? $masterAtasan->asn_id;
                $checkTahun = $request->tahun ?? $masterAtasan->tahun;

                $exists = MasterAtasan::where('asn_id', $checkAsnId)
                    ->where('tahun', $checkTahun)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'message' => 'ASN sudah memiliki atasan untuk tahun ' . $checkTahun
                    ], 422);
                }
            }

            $masterAtasan->update($request->only([
                'asn_id',
                'atasan_id',
                'tahun',
                'status',
            ]));

            $masterAtasan->load(['asn', 'atasan']);

            return response()->json([
                'message' => 'Master atasan updated successfully',
                'data' => $masterAtasan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update master atasan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete master atasan
     */
    public function destroy($id)
    {
        try {
            $masterAtasan = MasterAtasan::findOrFail($id);
            $masterAtasan->delete();

            return response()->json([
                'message' => 'Master atasan deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete master atasan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of ASN (for dropdown)
     */
    public function getAsnList()
    {
        try {
            $asnList = User::where('role', 'ASN')
                ->where('status', 'AKTIF')
                ->select('id', 'name', 'nip', 'unit_id')
                ->with('unit:id,nama_unit')
                ->orderBy('name')
                ->get();

            return response()->json($asnList);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch ASN list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of Atasan (for dropdown)
     */
    public function getAtasanList()
    {
        try {
            $atasanList = User::where('role', 'ATASAN')
                ->where('status', 'AKTIF')
                ->select('id', 'name', 'nip', 'unit_id')
                ->with('unit:id,nama_unit')
                ->orderBy('name')
                ->get();

            return response()->json($atasanList);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch Atasan list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get atasan for specific ASN and year
     */
    public function getAtasanForAsn(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'asn_id' => 'required|exists:users,id',
                'tahun' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $atasan = MasterAtasan::getAtasanForAsn($request->asn_id, $request->tahun);

            if (!$atasan) {
                return response()->json([
                    'message' => 'Atasan not found for this ASN and year'
                ], 404);
            }

            return response()->json($atasan);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch atasan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all ASN under specific atasan for year
     */
    public function getAsnUnderAtasan(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'atasan_id' => 'required|exists:users,id',
                'tahun' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $asnList = MasterAtasan::getAsnUnderAtasan($request->atasan_id, $request->tahun);

            return response()->json($asnList);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch ASN list',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
