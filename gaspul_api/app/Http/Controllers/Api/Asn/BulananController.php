<?php

namespace App\Http\Controllers\Api\Asn;

use App\Http\Controllers\Controller;
use App\Models\Bulanan;
use App\Models\RencanaKerjaAsn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BulananController extends Controller
{
    /**
     * Get list of Bulanan for the authenticated ASN
     * Filter by SKP ID or by year
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Bulanan::with([
            'rencanaKerjaAsn.sasaranKegiatan',
            'rencanaKerjaAsn.indikatorKinerja'
        ])->whereHas('rencanaKerjaAsn', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // Filter by SKP ID
        if ($request->has('skp_id')) {
            $query->where('rencana_kerja_asn_id', $request->skp_id);
        }

        // Filter by year
        if ($request->has('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        // Filter by month
        if ($request->has('bulan')) {
            $query->where('bulan', $request->bulan);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bulananList = $query->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->get();

        // Add calculated fields
        $bulananList->each(function ($bulanan) {
            $bulanan->capaian_persen = $bulanan->capaian_persen;
            $bulanan->bulan_nama = $bulanan->bulan_nama;
            $bulanan->has_target_filled = $bulanan->hasTargetFilled();
            $bulanan->can_create_harian = $bulanan->canCreateHarian();
        });

        return response()->json([
            'success' => true,
            'data' => $bulananList
        ]);
    }

    /**
     * Get detail of specific Bulanan
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $bulanan = Bulanan::with([
            'rencanaKerjaAsn.sasaranKegiatan',
            'rencanaKerjaAsn.indikatorKinerja',
            'harian'
        ])->find($id);

        if (!$bulanan) {
            return response()->json([
                'success' => false,
                'message' => 'Bulanan tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if ($bulanan->rencanaKerjaAsn->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke Bulanan ini'
            ], 403);
        }

        $bulanan->capaian_persen = $bulanan->capaian_persen;
        $bulanan->bulan_nama = $bulanan->bulan_nama;
        $bulanan->has_target_filled = $bulanan->hasTargetFilled();
        $bulanan->can_create_harian = $bulanan->canCreateHarian();

        return response()->json([
            'success' => true,
            'data' => $bulanan
        ]);
    }

    /**
     * Update Bulanan target and rencana kerja
     * Only target_bulanan and rencana_kerja_bulanan can be edited
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $bulanan = Bulanan::with('rencanaKerjaAsn')->find($id);

        if (!$bulanan) {
            return response()->json([
                'success' => false,
                'message' => 'Bulanan tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if ($bulanan->rencanaKerjaAsn->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke Bulanan ini'
            ], 403);
        }

        // Check if parent SKP is DISETUJUI
        if ($bulanan->rencanaKerjaAsn->status !== 'DISETUJUI') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Bulanan dari SKP yang disetujui yang dapat diupdate'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'target_bulanan' => 'required|integer|min:0',
            'rencana_kerja_bulanan' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $bulanan->update([
                'target_bulanan' => $request->target_bulanan,
                'rencana_kerja_bulanan' => $request->rencana_kerja_bulanan,
            ]);

            $bulanan->load(['rencanaKerjaAsn.sasaranKegiatan', 'rencanaKerjaAsn.indikatorKinerja']);

            return response()->json([
                'success' => true,
                'message' => 'Target dan Rencana Kerja Bulanan berhasil diupdate',
                'data' => $bulanan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate Bulanan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Bulanan by SKP ID
     * Helper endpoint to get all Bulanan for a specific SKP
     */
    public function getBulananBySkp(Request $request, $skpId)
    {
        $user = $request->user();

        // Check if SKP exists and belongs to user
        $skp = RencanaKerjaAsn::where('id', $skpId)
            ->where('user_id', $user->id)
            ->first();

        if (!$skp) {
            return response()->json([
                'success' => false,
                'message' => 'SKP tidak ditemukan atau Anda tidak memiliki akses'
            ], 404);
        }

        $bulananList = Bulanan::with([
            'rencanaKerjaAsn.sasaranKegiatan',
            'rencanaKerjaAsn.indikatorKinerja'
        ])
            ->where('rencana_kerja_asn_id', $skpId)
            ->orderBy('bulan', 'asc')
            ->get();

        // Add calculated fields
        $bulananList->each(function ($bulanan) {
            $bulanan->capaian_persen = $bulanan->capaian_persen;
            $bulanan->bulan_nama = $bulanan->bulan_nama;
            $bulanan->has_target_filled = $bulanan->hasTargetFilled();
            $bulanan->can_create_harian = $bulanan->canCreateHarian();
        });

        return response()->json([
            'success' => true,
            'data' => $bulananList
        ]);
    }
}
