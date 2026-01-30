<?php

namespace App\Http\Controllers\Api\Asn;

use App\Http\Controllers\Controller;
use App\Models\RencanaAksiBulanan;
use App\Models\SkpTahunanDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Rencana Aksi Bulanan Controller
 *
 * Managed by: ASN / PPPK
 * Purpose: Mengelola rencana aksi bulanan (breakdown dari SKP Tahunan Detail)
 * Note: Periode bulanan AUTO-GENERATED saat SKP Detail dibuat
 */
class RencanaAksiBulananController extends Controller
{
    /**
     * Get all rencana aksi bulanan for authenticated ASN
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = RencanaAksiBulanan::with([
                'skpTahunanDetail.indikatorKinerja.sasaranKegiatan',
                'skpTahunanDetail.skpTahunan',
                'progresHarian'
            ])->whereHas('skpTahunanDetail.skpTahunan', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

            // Filter by bulan
            if ($request->has('bulan')) {
                $query->where('bulan', $request->bulan);
            }

            // Filter by tahun
            if ($request->has('tahun')) {
                $query->where('tahun', $request->tahun);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by SKP Tahunan Detail
            if ($request->has('skp_tahunan_detail_id')) {
                $query->where('skp_tahunan_detail_id', $request->skp_tahunan_detail_id);
            }

            $rencanaList = $query->orderBy('tahun', 'desc')
                ->orderBy('bulan', 'asc')
                ->get();

            return response()->json($rencanaList);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch rencana aksi bulanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rencana aksi bulanan for specific SKP Tahunan Detail
     */
    public function getByDetail($skpTahunanDetailId)
    {
        try {
            $detail = SkpTahunanDetail::with(['skpTahunan'])->find($skpTahunanDetailId);

            if (!$detail) {
                return response()->json([
                    'message' => 'SKP Tahunan Detail tidak ditemukan'
                ], 404);
            }

            $rencanaList = RencanaAksiBulanan::where('skp_tahunan_detail_id', $skpTahunanDetailId)
                ->with(['progresHarian'])
                ->orderBy('bulan', 'asc')
                ->get();

            return response()->json($rencanaList);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch rencana aksi bulanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail rencana aksi bulanan
     */
    public function show($id)
    {
        try {
            $rencana = RencanaAksiBulanan::with([
                'skpTahunanDetail.indikatorKinerja.sasaranKegiatan',
                'skpTahunanDetail.skpTahunan',
                'progresHarian'
            ])->find($id);

            if (!$rencana) {
                return response()->json([
                    'message' => 'Rencana aksi bulanan tidak ditemukan'
                ], 404);
            }

            return response()->json($rencana);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch rencana aksi bulanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update rencana aksi bulanan (ISI RENCANA AKSI)
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            $rencana = RencanaAksiBulanan::with([
                'skpTahunanDetail.skpTahunan'
            ])->find($id);

            if (!$rencana) {
                return response()->json([
                    'message' => 'Rencana aksi bulanan tidak ditemukan'
                ], 404);
            }

            // Check ownership
            if ($rencana->skpTahunanDetail->skpTahunan->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke rencana aksi ini'
                ], 403);
            }

            // Validasi: SKP Tahunan harus sudah disetujui
            if ($rencana->skpTahunanDetail->skpTahunan->status !== 'DISETUJUI') {
                return response()->json([
                    'message' => 'Rencana aksi hanya dapat diisi setelah SKP Tahunan disetujui'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'rencana_aksi_bulanan' => 'required|string|max:1000',
                'target_bulanan' => 'required|integer|min:1',
                'satuan_target' => 'required|string|in:Dokumen,Data,Laporan,Kegiatan,Persentase,Berkas,Dokumentasi',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rencana->update([
                'rencana_aksi_bulanan' => $request->rencana_aksi_bulanan,
                'target_bulanan' => $request->target_bulanan,
                'satuan_target' => $request->satuan_target,
                'status' => 'AKTIF', // Ubah status dari BELUM_DIISI ke AKTIF
            ]);

            $rencana->load([
                'skpTahunanDetail.indikatorKinerja.sasaranKegiatan',
                'progresHarian'
            ]);

            return response()->json([
                'message' => 'Rencana aksi bulanan berhasil diperbarui',
                'data' => $rencana
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update rencana aksi bulanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary rencana aksi bulanan per tahun
     */
    public function getSummary(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'tahun' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tahun = $request->tahun;

            $summary = RencanaAksiBulanan::where('tahun', $tahun)
                ->whereHas('skpTahunanDetail.skpTahunan', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->with(['skpTahunanDetail.indikatorKinerja'])
                ->get()
                ->groupBy('bulan')
                ->map(function ($items, $bulan) {
                    return [
                        'bulan' => $bulan,
                        'bulan_nama' => $items->first()->bulan_nama,
                        'total_rencana' => $items->count(),
                        'total_aktif' => $items->where('status', 'AKTIF')->count(),
                        'total_selesai' => $items->where('status', 'SELESAI')->count(),
                        'total_belum_diisi' => $items->where('status', 'BELUM_DIISI')->count(),
                        'total_target' => $items->sum('target_bulanan'),
                        'total_realisasi' => $items->sum('realisasi_bulanan'),
                        'capaian_persen' => $items->sum('target_bulanan') > 0
                            ? round(($items->sum('realisasi_bulanan') / $items->sum('target_bulanan')) * 100, 2)
                            : 0,
                    ];
                })
                ->values();

            return response()->json($summary);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
