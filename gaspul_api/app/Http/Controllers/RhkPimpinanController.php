<?php

namespace App\Http\Controllers;

use App\Models\RhkPimpinan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RhkPimpinanController extends Controller
{
    /**
     * Get all RHK Pimpinan with usage count
     */
    public function index()
    {
        try {
            $rhkPimpinan = RhkPimpinan::withCount('rhkAsn as usage_count')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $rhkPimpinan,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memuat data RHK Pimpinan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get only active RHK Pimpinan for ASN to select
     */
    public function getActive()
    {
        try {
            $rhkPimpinan = RhkPimpinan::aktif()
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $rhkPimpinan,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memuat data RHK Pimpinan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new RHK Pimpinan (ADMIN only)
     */
    public function store(Request $request)
    {
        // RBAC: Only ADMIN can create
        if (Auth::user()->role !== 'ADMIN') {
            return response()->json([
                'message' => 'Unauthorized. Only ADMIN can create RHK Pimpinan.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rencana_hasil_kerja' => 'required|string',
            'unit_kerja' => 'nullable|string|max:255',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $rhkPimpinan = RhkPimpinan::create([
                'rencana_hasil_kerja' => $request->rencana_hasil_kerja,
                'unit_kerja' => $request->unit_kerja,
                'status' => $request->status,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'RHK Pimpinan berhasil ditambahkan',
                'data' => $rhkPimpinan,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menambahkan RHK Pimpinan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update existing RHK Pimpinan (ADMIN only)
     */
    public function update(Request $request, $id)
    {
        // RBAC: Only ADMIN can update
        if (Auth::user()->role !== 'ADMIN') {
            return response()->json([
                'message' => 'Unauthorized. Only ADMIN can update RHK Pimpinan.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rencana_hasil_kerja' => 'required|string',
            'unit_kerja' => 'nullable|string|max:255',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $rhkPimpinan = RhkPimpinan::findOrFail($id);

            $rhkPimpinan->update([
                'rencana_hasil_kerja' => $request->rencana_hasil_kerja,
                'unit_kerja' => $request->unit_kerja,
                'status' => $request->status,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'RHK Pimpinan berhasil diperbarui',
                'data' => $rhkPimpinan,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'RHK Pimpinan tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui RHK Pimpinan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete RHK Pimpinan (ADMIN only)
     * Cannot delete if already used by ASN (ON DELETE RESTRICT)
     */
    public function destroy($id)
    {
        // RBAC: Only ADMIN can delete
        if (Auth::user()->role !== 'ADMIN') {
            return response()->json([
                'message' => 'Unauthorized. Only ADMIN can delete RHK Pimpinan.',
            ], 403);
        }

        try {
            $rhkPimpinan = RhkPimpinan::withCount('rhkAsn')->findOrFail($id);

            // Check if RHK Pimpinan is being used by any ASN
            if ($rhkPimpinan->rhk_asn_count > 0) {
                return response()->json([
                    'message' => "Tidak dapat menghapus RHK Pimpinan ini karena sudah digunakan oleh {$rhkPimpinan->rhk_asn_count} ASN.",
                ], 400);
            }

            $rhkPimpinan->delete();

            return response()->json([
                'message' => 'RHK Pimpinan berhasil dihapus',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'RHK Pimpinan tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus RHK Pimpinan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status AKTIF/NONAKTIF (ADMIN only)
     */
    public function toggleStatus(Request $request, $id)
    {
        // RBAC: Only ADMIN can toggle status
        if (Auth::user()->role !== 'ADMIN') {
            return response()->json([
                'message' => 'Unauthorized. Only ADMIN can toggle status.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:AKTIF,NONAKTIF',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $rhkPimpinan = RhkPimpinan::findOrFail($id);

            $rhkPimpinan->update([
                'status' => $request->status,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'message' => 'Status berhasil diubah',
                'data' => $rhkPimpinan,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'RHK Pimpinan tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengubah status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
