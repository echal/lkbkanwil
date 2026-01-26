<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IndikatorTahunan;
use Illuminate\Http\Request;

class IndikatorTahunanController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/asn/indikator-tahunan
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $indikators = IndikatorTahunan::where('user_id', $user->id)
            ->orderBy('tahun', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Data indikator tahunan berhasil diambil',
            'data' => $indikators
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/asn/indikator-tahunan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_indikator' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tahun' => 'required|integer|min:2020|max:2100',
            'target' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:50',
            'status' => 'nullable|in:aktif,nonaktif',
        ]);

        $indikator = IndikatorTahunan::create([
            'user_id' => $request->user()->id,
            'nama_indikator' => $validated['nama_indikator'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'tahun' => $validated['tahun'],
            'target' => $validated['target'],
            'satuan' => $validated['satuan'],
            'status' => $validated['status'] ?? 'aktif',
        ]);

        return response()->json([
            'message' => 'Indikator tahunan berhasil dibuat',
            'data' => $indikator
        ], 201);
    }

    /**
     * Display the specified resource.
     * GET /api/asn/indikator-tahunan/{id}
     */
    public function show(Request $request, string $id)
    {
        $indikator = IndikatorTahunan::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$indikator) {
            return response()->json([
                'message' => 'Indikator tahunan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Data indikator tahunan berhasil diambil',
            'data' => $indikator
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/asn/indikator-tahunan/{id}
     */
    public function update(Request $request, string $id)
    {
        $indikator = IndikatorTahunan::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$indikator) {
            return response()->json([
                'message' => 'Indikator tahunan tidak ditemukan'
            ], 404);
        }

        $validated = $request->validate([
            'nama_indikator' => 'sometimes|required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tahun' => 'sometimes|required|integer|min:2020|max:2100',
            'target' => 'sometimes|required|numeric|min:0',
            'satuan' => 'sometimes|required|string|max:50',
            'status' => 'nullable|in:aktif,nonaktif',
        ]);

        $indikator->update($validated);

        return response()->json([
            'message' => 'Indikator tahunan berhasil diupdate',
            'data' => $indikator
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/asn/indikator-tahunan/{id}
     */
    public function destroy(Request $request, string $id)
    {
        $indikator = IndikatorTahunan::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$indikator) {
            return response()->json([
                'message' => 'Indikator tahunan tidak ditemukan'
            ], 404);
        }

        $indikator->delete();

        return response()->json([
            'message' => 'Indikator tahunan berhasil dihapus'
        ]);
    }
}
