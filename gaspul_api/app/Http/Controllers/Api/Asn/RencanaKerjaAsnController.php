<?php

namespace App\Http\Controllers\Api\Asn;

use App\Http\Controllers\Controller;
use App\Models\RencanaKerjaAsn;
use App\Models\SasaranKegiatan;
use App\Models\IndikatorKinerja;
use App\Models\SkpTahunan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RencanaKerjaAsnController extends Controller
{
    /**
     * Display a listing of rencana kerja for authenticated ASN
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = RencanaKerjaAsn::with([
            'skpTahunan.details',
            'skpTahunanDetail.sasaranKegiatan',
            'skpTahunanDetail.indikatorKinerja',
            'sasaranKegiatan',
            'indikatorKinerja',
            'approvedBy'
        ])
        ->where('user_id', $user->id);

        // Optional filters
        if ($request->has('tahun')) {
            $query->byYear($request->tahun);
        }

        if ($request->has('triwulan')) {
            $query->byTriwulan($request->triwulan);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        $rencanaList = $query->orderBy('tahun', 'desc')
            ->orderBy('triwulan', 'desc')
            ->get();

        // Add calculated fields
        $rencanaList->each(function ($rencana) {
            $rencana->capaian_persen = $rencana->capaian_persen;
        });

        return response()->json([
            'success' => true,
            'data' => $rencanaList
        ]);
    }

    /**
     * Store a newly created rencana kerja
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // NEW VALIDATION: SKP Tahunan Detail (Butir Kinerja) WAJIB
        $validator = Validator::make($request->all(), [
            'skp_tahunan_id' => 'required|exists:skp_tahunan,id',
            'skp_tahunan_detail_id' => 'required|exists:skp_tahunan_detail,id',
            'triwulan' => ['required', Rule::in(['I', 'II', 'III', 'IV'])],
            'target' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:100',
            'catatan_asn' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get SKP Tahunan dan validasi
        $skpTahunan = SkpTahunan::with('details')->find($request->skp_tahunan_id);

        if (!$skpTahunan) {
            return response()->json([
                'success' => false,
                'message' => 'SKP Tahunan tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if ($skpTahunan->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'SKP Tahunan ini bukan milik Anda'
            ], 403);
        }

        // VALIDASI PENTING: SKP Tahunan harus DISETUJUI
        if ($skpTahunan->status !== 'DISETUJUI') {
            return response()->json([
                'success' => false,
                'message' => 'SKP Tahunan harus DISETUJUI terlebih dahulu sebelum membuat SKP Triwulan. Status saat ini: ' . $skpTahunan->status,
                'helper_text' => 'Silakan ajukan SKP Tahunan Anda dan tunggu persetujuan dari Atasan.'
            ], 403);
        }

        // Get the specific detail (butir kinerja)
        $detail = $skpTahunan->details()->find($request->skp_tahunan_detail_id);

        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Butir kinerja tidak ditemukan atau tidak termasuk dalam SKP Tahunan ini'
            ], 404);
        }

        // Check for duplicate (same detail + triwulan)
        $exists = RencanaKerjaAsn::where('user_id', $user->id)
            ->where('skp_tahunan_detail_id', $request->skp_tahunan_detail_id)
            ->where('triwulan', $request->triwulan)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memiliki SKP Triwulan ' . $request->triwulan . ' untuk butir kinerja ini'
            ], 422);
        }

        try {
            // SKP Triwulan mengambil data dari SKP Tahunan Detail
            $rencanaKerja = RencanaKerjaAsn::create([
                'user_id' => $user->id,
                'skp_tahunan_id' => $request->skp_tahunan_id,
                'skp_tahunan_detail_id' => $request->skp_tahunan_detail_id,
                'sasaran_kegiatan_id' => $detail->sasaran_kegiatan_id,
                'indikator_kinerja_id' => $detail->indikator_kinerja_id,
                'tahun' => $skpTahunan->tahun,
                'triwulan' => $request->triwulan,
                'target' => $request->target,
                'satuan' => $request->satuan,
                'realisasi' => 0,
                'catatan_asn' => $request->catatan_asn,
                'status' => 'DRAFT',
            ]);

            $rencanaKerja->load([
                'skpTahunan.details',
                'skpTahunanDetail.sasaranKegiatan',
                'skpTahunanDetail.indikatorKinerja',
                'sasaranKegiatan',
                'indikatorKinerja'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SKP Triwulan berhasil ditambahkan',
                'data' => $rencanaKerja
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan Rencana Kerja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified rencana kerja
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $rencanaKerja = RencanaKerjaAsn::with([
            'sasaranKegiatan',
            'indikatorKinerja',
            'approvedBy'
        ])
        ->where('id', $id)
        ->where('user_id', $user->id)
        ->first();

        if (!$rencanaKerja) {
            return response()->json([
                'success' => false,
                'message' => 'Rencana Kerja tidak ditemukan'
            ], 404);
        }

        $rencanaKerja->capaian_persen = $rencanaKerja->capaian_persen;

        return response()->json([
            'success' => true,
            'data' => $rencanaKerja
        ]);
    }

    /**
     * Update the specified rencana kerja
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $rencanaKerja = RencanaKerjaAsn::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$rencanaKerja) {
            return response()->json([
                'success' => false,
                'message' => 'Rencana Kerja tidak ditemukan'
            ], 404);
        }

        // Check if can be edited
        if (!$rencanaKerja->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'Rencana Kerja dengan status ' . $rencanaKerja->status . ' tidak dapat diedit'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'sasaran_kegiatan_id' => 'required|exists:sasaran_kegiatan,id',
            'indikator_kinerja_id' => 'required|exists:indikator_kinerja,id',
            'tahun' => 'required|integer|min:2020|max:2100',
            'triwulan' => ['required', Rule::in(['I', 'II', 'III', 'IV'])],
            'target' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:100',
            'realisasi' => 'nullable|numeric|min:0',
            'catatan_asn' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if indikator belongs to sasaran
        $indikator = IndikatorKinerja::find($request->indikator_kinerja_id);
        if ($indikator->sasaran_kegiatan_id != $request->sasaran_kegiatan_id) {
            return response()->json([
                'success' => false,
                'message' => 'Indikator Kinerja tidak sesuai dengan Sasaran Kegiatan yang dipilih'
            ], 422);
        }

        try {
            $rencanaKerja->update([
                'sasaran_kegiatan_id' => $request->sasaran_kegiatan_id,
                'indikator_kinerja_id' => $request->indikator_kinerja_id,
                'tahun' => $request->tahun,
                'triwulan' => $request->triwulan,
                'target' => $request->target,
                'satuan' => $request->satuan,
                'realisasi' => $request->realisasi ?? $rencanaKerja->realisasi,
                'catatan_asn' => $request->catatan_asn,
            ]);

            $rencanaKerja->load(['sasaranKegiatan', 'indikatorKinerja']);

            return response()->json([
                'success' => true,
                'message' => 'Rencana Kerja berhasil diperbarui',
                'data' => $rencanaKerja
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui Rencana Kerja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified rencana kerja
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $rencanaKerja = RencanaKerjaAsn::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$rencanaKerja) {
            return response()->json([
                'success' => false,
                'message' => 'Rencana Kerja tidak ditemukan'
            ], 404);
        }

        // Only DRAFT can be deleted
        if ($rencanaKerja->status !== 'DRAFT') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Rencana Kerja dengan status DRAFT yang dapat dihapus'
            ], 403);
        }

        try {
            $rencanaKerja->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rencana Kerja berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Rencana Kerja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit rencana kerja for approval (change status from DRAFT to DIAJUKAN)
     */
    public function submit(Request $request, $id)
    {
        $user = $request->user();

        $rencanaKerja = RencanaKerjaAsn::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$rencanaKerja) {
            return response()->json([
                'success' => false,
                'message' => 'Rencana Kerja tidak ditemukan'
            ], 404);
        }

        if (!$rencanaKerja->canBeSubmitted()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Rencana Kerja dengan status DRAFT yang dapat diajukan'
            ], 403);
        }

        try {
            $rencanaKerja->update([
                'status' => 'DIAJUKAN'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rencana Kerja berhasil diajukan untuk persetujuan',
                'data' => $rencanaKerja
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengajukan Rencana Kerja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update realisasi only (for tracking progress)
     */
    public function updateRealisasi(Request $request, $id)
    {
        $user = $request->user();

        $rencanaKerja = RencanaKerjaAsn::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$rencanaKerja) {
            return response()->json([
                'success' => false,
                'message' => 'Rencana Kerja tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'realisasi' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $rencanaKerja->update([
                'realisasi' => $request->realisasi
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Realisasi berhasil diperbarui',
                'data' => $rencanaKerja
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui realisasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active Sasaran Kegiatan for ASN to select
     */
    public function getActiveSasaran()
    {
        $sasaranList = SasaranKegiatan::active()
            ->with('indikatorKinerjaAktif')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sasaranList
        ]);
    }

    /**
     * Get active Indikator Kinerja by Sasaran Kegiatan ID
     */
    public function getIndikatorBySasaran($sasaranId)
    {
        $sasaran = SasaranKegiatan::with('indikatorKinerjaAktif')->find($sasaranId);

        if (!$sasaran) {
            return response()->json([
                'success' => false,
                'message' => 'Sasaran Kegiatan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sasaran->indikatorKinerjaAktif
        ]);
    }
}
