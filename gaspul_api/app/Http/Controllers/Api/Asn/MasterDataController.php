<?php

namespace App\Http\Controllers\Api\Asn;

use App\Http\Controllers\Controller;
use App\Models\SasaranKegiatan;
use App\Models\IndikatorKinerja;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    /**
     * Get Sasaran Kegiatan berdasarkan unit kerja user yang login
     * Hanya menampilkan sasaran yang status AKTIF dan sesuai unit kerja ASN
     *
     * GET /api/asn/sasaran-kegiatan
     */
    public function getSasaranKegiatan(Request $request)
    {
        try {
            $user = $request->user()->load('unit');

            // Ambil nama unit kerja dari relasi unit (prioritas) atau fallback ke unit_kerja lama
            $unitKerjaName = null;
            if ($user->unit_id && $user->unit) {
                $unitKerjaName = $user->unit->nama_unit;
            } elseif (!empty($user->unit_kerja)) {
                $unitKerjaName = $user->unit_kerja;
            }

            // Validasi user harus punya unit kerja
            if (empty($unitKerjaName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit kerja Anda belum terdaftar. Silakan hubungi Administrator untuk mengatur unit kerja Anda.',
                ], 403);
            }

            // Ambil Sasaran Kegiatan berdasarkan unit kerja user
            $sasaranList = SasaranKegiatan::where('unit_kerja', $unitKerjaName)
                ->where('status', 'AKTIF')
                ->orderBy('sasaran_kegiatan', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'unit_kerja' => $unitKerjaName,
                'data' => $sasaranList,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data Sasaran Kegiatan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Indikator Kinerja berdasarkan sasaran_kegiatan_id
     *
     * GET /api/asn/indikator-kinerja?sasaran_kegiatan_id={id}
     */
    public function getIndikatorKinerja(Request $request)
    {
        try {
            $sasaranId = $request->query('sasaran_kegiatan_id');

            if (!$sasaranId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter sasaran_kegiatan_id harus diisi',
                ], 400);
            }

            // Validasi sasaran kegiatan exists dan sesuai unit kerja user
            $user = $request->user()->load('unit');
            $sasaran = SasaranKegiatan::find($sasaranId);

            if (!$sasaran) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sasaran Kegiatan tidak ditemukan',
                ], 404);
            }

            // Ambil nama unit kerja dari relasi unit (prioritas) atau fallback ke unit_kerja lama
            $unitKerjaName = null;
            if ($user->unit_id && $user->unit) {
                $unitKerjaName = $user->unit->nama_unit;
            } elseif (!empty($user->unit_kerja)) {
                $unitKerjaName = $user->unit_kerja;
            }

            // Validasi unit kerja
            if ($sasaran->unit_kerja !== $unitKerjaName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke Sasaran Kegiatan ini',
                ], 403);
            }

            // Ambil indikator kinerja yang aktif
            $indikatorList = IndikatorKinerja::where('sasaran_kegiatan_id', $sasaranId)
                ->where('status', 'AKTIF')
                ->orderBy('indikator_kinerja', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $indikatorList,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data Indikator Kinerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
