<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SasaranKegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SasaranKegiatanController extends Controller
{
    /**
     * Display a listing of Sasaran Kegiatan with Indikator count.
     *
     * GET /api/admin/sasaran-kegiatan
     */
    public function index()
    {
        try {
            $sasaranList = SasaranKegiatan::with('indikatorKinerja')
                ->orderBy('created_at', 'desc')
                ->get();

            $sasaranList = $sasaranList->map(function ($sasaran) {
                return [
                    'id' => $sasaran->id,
                    'unit_kerja' => $sasaran->unit_kerja,
                    'sasaran_kegiatan' => $sasaran->sasaran_kegiatan,
                    'status' => $sasaran->status,
                    'jumlah_indikator' => $sasaran->indikatorKinerja->count(),
                    'digunakan_asn' => $sasaran->isDigunakanAsn(),
                    'created_at' => $sasaran->created_at,
                    'updated_at' => $sasaran->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
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
     * Store a newly created Sasaran Kegiatan.
     *
     * POST /api/admin/sasaran-kegiatan
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_kerja' => 'required|string|max:255',
            'sasaran_kegiatan' => 'required|string|max:5000',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ], [
            'unit_kerja.required' => 'Unit Kerja harus diisi',
            'unit_kerja.max' => 'Unit Kerja maksimal 255 karakter',
            'sasaran_kegiatan.required' => 'Sasaran Kegiatan harus diisi',
            'sasaran_kegiatan.max' => 'Sasaran Kegiatan maksimal 5000 karakter',
            'status.required' => 'Status harus diisi',
            'status.in' => 'Status harus AKTIF atau NONAKTIF',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sasaran = SasaranKegiatan::create($request->only([
                'unit_kerja',
                'sasaran_kegiatan',
                'status'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Sasaran Kegiatan berhasil ditambahkan',
                'data' => [
                    'id' => $sasaran->id,
                    'unit_kerja' => $sasaran->unit_kerja,
                    'sasaran_kegiatan' => $sasaran->sasaran_kegiatan,
                    'status' => $sasaran->status,
                    'jumlah_indikator' => 0,
                    'digunakan_asn' => false,
                    'created_at' => $sasaran->created_at,
                    'updated_at' => $sasaran->updated_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan Sasaran Kegiatan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified Sasaran Kegiatan with its Indikator.
     *
     * GET /api/admin/sasaran-kegiatan/{id}
     */
    public function show($id)
    {
        try {
            $sasaran = SasaranKegiatan::with('indikatorKinerja')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $sasaran->id,
                    'unit_kerja' => $sasaran->unit_kerja,
                    'sasaran_kegiatan' => $sasaran->sasaran_kegiatan,
                    'status' => $sasaran->status,
                    'jumlah_indikator' => $sasaran->indikatorKinerja->count(),
                    'digunakan_asn' => $sasaran->isDigunakanAsn(),
                    'indikator_kinerja' => $sasaran->indikatorKinerja,
                    'created_at' => $sasaran->created_at,
                    'updated_at' => $sasaran->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sasaran Kegiatan tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data Sasaran Kegiatan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified Sasaran Kegiatan.
     *
     * PUT /api/admin/sasaran-kegiatan/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'unit_kerja' => 'required|string|max:255',
            'sasaran_kegiatan' => 'required|string|max:5000',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ], [
            'unit_kerja.required' => 'Unit Kerja harus diisi',
            'unit_kerja.max' => 'Unit Kerja maksimal 255 karakter',
            'sasaran_kegiatan.required' => 'Sasaran Kegiatan harus diisi',
            'sasaran_kegiatan.max' => 'Sasaran Kegiatan maksimal 5000 karakter',
            'status.required' => 'Status harus diisi',
            'status.in' => 'Status harus AKTIF atau NONAKTIF',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sasaran = SasaranKegiatan::findOrFail($id);

            $sasaran->update($request->only([
                'unit_kerja',
                'sasaran_kegiatan',
                'status'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Sasaran Kegiatan berhasil diperbarui',
                'data' => [
                    'id' => $sasaran->id,
                    'unit_kerja' => $sasaran->unit_kerja,
                    'sasaran_kegiatan' => $sasaran->sasaran_kegiatan,
                    'status' => $sasaran->status,
                    'jumlah_indikator' => $sasaran->indikatorKinerja()->count(),
                    'digunakan_asn' => $sasaran->isDigunakanAsn(),
                    'created_at' => $sasaran->created_at,
                    'updated_at' => $sasaran->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sasaran Kegiatan tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui Sasaran Kegiatan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status of the specified Sasaran Kegiatan.
     *
     * PATCH /api/admin/sasaran-kegiatan/{id}/toggle-status
     */
    public function toggleStatus($id)
    {
        try {
            $sasaran = SasaranKegiatan::findOrFail($id);

            $newStatus = $sasaran->status === 'AKTIF' ? 'NONAKTIF' : 'AKTIF';
            $sasaran->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => "Status Sasaran Kegiatan berhasil diubah menjadi {$newStatus}",
                'data' => [
                    'id' => $sasaran->id,
                    'unit_kerja' => $sasaran->unit_kerja,
                    'sasaran_kegiatan' => $sasaran->sasaran_kegiatan,
                    'status' => $sasaran->status,
                    'jumlah_indikator' => $sasaran->indikatorKinerja()->count(),
                    'digunakan_asn' => $sasaran->isDigunakanAsn(),
                    'created_at' => $sasaran->created_at,
                    'updated_at' => $sasaran->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sasaran Kegiatan tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status Sasaran Kegiatan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified Sasaran Kegiatan.
     *
     * DELETE /api/admin/sasaran-kegiatan/{id}
     */
    public function destroy($id)
    {
        try {
            $sasaran = SasaranKegiatan::findOrFail($id);

            if ($sasaran->isDigunakanAsn()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sasaran Kegiatan tidak dapat dihapus karena sedang digunakan oleh ASN',
                ], 422);
            }

            // Delete will cascade to indikator_kinerja
            $sasaran->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sasaran Kegiatan berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sasaran Kegiatan tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Sasaran Kegiatan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
