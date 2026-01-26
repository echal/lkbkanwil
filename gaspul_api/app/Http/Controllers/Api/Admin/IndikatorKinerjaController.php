<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndikatorKinerja;
use App\Models\SasaranKegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IndikatorKinerjaController extends Controller
{
    /**
     * Display a listing of Indikator Kinerja (all or by sasaran_kegiatan_id).
     *
     * GET /api/admin/indikator-kinerja?sasaran_kegiatan_id=1
     */
    public function index(Request $request)
    {
        try {
            $query = IndikatorKinerja::with('sasaranKegiatan')
                ->orderBy('created_at', 'desc');

            // Filter by sasaran_kegiatan_id if provided
            if ($request->has('sasaran_kegiatan_id')) {
                $query->where('sasaran_kegiatan_id', $request->sasaran_kegiatan_id);
            }

            $indikatorList = $query->get();

            $indikatorList = $indikatorList->map(function ($indikator) {
                return [
                    'id' => $indikator->id,
                    'sasaran_kegiatan_id' => $indikator->sasaran_kegiatan_id,
                    'sasaran_kegiatan' => $indikator->sasaranKegiatan ? [
                        'id' => $indikator->sasaranKegiatan->id,
                        'unit_kerja' => $indikator->sasaranKegiatan->unit_kerja,
                        'sasaran_kegiatan' => $indikator->sasaranKegiatan->sasaran_kegiatan,
                    ] : null,
                    'indikator_kinerja' => $indikator->indikator_kinerja,
                    'status' => $indikator->status,
                    'digunakan_asn' => $indikator->isDigunakanAsn(),
                    'created_at' => $indikator->created_at,
                    'updated_at' => $indikator->updated_at,
                ];
            });

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

    /**
     * Store a newly created Indikator Kinerja.
     *
     * POST /api/admin/indikator-kinerja
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sasaran_kegiatan_id' => 'required|exists:sasaran_kegiatan,id',
            'indikator_kinerja' => 'required|string|max:5000',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ], [
            'sasaran_kegiatan_id.required' => 'Sasaran Kegiatan harus dipilih',
            'sasaran_kegiatan_id.exists' => 'Sasaran Kegiatan tidak valid',
            'indikator_kinerja.required' => 'Indikator Kinerja harus diisi',
            'indikator_kinerja.max' => 'Indikator Kinerja maksimal 5000 karakter',
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
            $indikator = IndikatorKinerja::create($request->only([
                'sasaran_kegiatan_id',
                'indikator_kinerja',
                'status'
            ]));

            // Load sasaran kegiatan relation
            $indikator->load('sasaranKegiatan');

            return response()->json([
                'success' => true,
                'message' => 'Indikator Kinerja berhasil ditambahkan',
                'data' => [
                    'id' => $indikator->id,
                    'sasaran_kegiatan_id' => $indikator->sasaran_kegiatan_id,
                    'sasaran_kegiatan' => $indikator->sasaranKegiatan ? [
                        'id' => $indikator->sasaranKegiatan->id,
                        'unit_kerja' => $indikator->sasaranKegiatan->unit_kerja,
                        'sasaran_kegiatan' => $indikator->sasaranKegiatan->sasaran_kegiatan,
                    ] : null,
                    'indikator_kinerja' => $indikator->indikator_kinerja,
                    'status' => $indikator->status,
                    'digunakan_asn' => false,
                    'created_at' => $indikator->created_at,
                    'updated_at' => $indikator->updated_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan Indikator Kinerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified Indikator Kinerja.
     *
     * GET /api/admin/indikator-kinerja/{id}
     */
    public function show($id)
    {
        try {
            $indikator = IndikatorKinerja::with('sasaranKegiatan')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $indikator->id,
                    'sasaran_kegiatan_id' => $indikator->sasaran_kegiatan_id,
                    'sasaran_kegiatan' => $indikator->sasaranKegiatan ? [
                        'id' => $indikator->sasaranKegiatan->id,
                        'unit_kerja' => $indikator->sasaranKegiatan->unit_kerja,
                        'sasaran_kegiatan' => $indikator->sasaranKegiatan->sasaran_kegiatan,
                    ] : null,
                    'indikator_kinerja' => $indikator->indikator_kinerja,
                    'status' => $indikator->status,
                    'digunakan_asn' => $indikator->isDigunakanAsn(),
                    'created_at' => $indikator->created_at,
                    'updated_at' => $indikator->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Indikator Kinerja tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data Indikator Kinerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified Indikator Kinerja.
     *
     * PUT /api/admin/indikator-kinerja/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'sasaran_kegiatan_id' => 'required|exists:sasaran_kegiatan,id',
            'indikator_kinerja' => 'required|string|max:5000',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ], [
            'sasaran_kegiatan_id.required' => 'Sasaran Kegiatan harus dipilih',
            'sasaran_kegiatan_id.exists' => 'Sasaran Kegiatan tidak valid',
            'indikator_kinerja.required' => 'Indikator Kinerja harus diisi',
            'indikator_kinerja.max' => 'Indikator Kinerja maksimal 5000 karakter',
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
            $indikator = IndikatorKinerja::findOrFail($id);

            $indikator->update($request->only([
                'sasaran_kegiatan_id',
                'indikator_kinerja',
                'status'
            ]));

            // Load sasaran kegiatan relation
            $indikator->load('sasaranKegiatan');

            return response()->json([
                'success' => true,
                'message' => 'Indikator Kinerja berhasil diperbarui',
                'data' => [
                    'id' => $indikator->id,
                    'sasaran_kegiatan_id' => $indikator->sasaran_kegiatan_id,
                    'sasaran_kegiatan' => $indikator->sasaranKegiatan ? [
                        'id' => $indikator->sasaranKegiatan->id,
                        'unit_kerja' => $indikator->sasaranKegiatan->unit_kerja,
                        'sasaran_kegiatan' => $indikator->sasaranKegiatan->sasaran_kegiatan,
                    ] : null,
                    'indikator_kinerja' => $indikator->indikator_kinerja,
                    'status' => $indikator->status,
                    'digunakan_asn' => $indikator->isDigunakanAsn(),
                    'created_at' => $indikator->created_at,
                    'updated_at' => $indikator->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Indikator Kinerja tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui Indikator Kinerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status of the specified Indikator Kinerja.
     *
     * PATCH /api/admin/indikator-kinerja/{id}/toggle-status
     */
    public function toggleStatus($id)
    {
        try {
            $indikator = IndikatorKinerja::with('sasaranKegiatan')->findOrFail($id);

            $newStatus = $indikator->status === 'AKTIF' ? 'NONAKTIF' : 'AKTIF';
            $indikator->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => "Status Indikator Kinerja berhasil diubah menjadi {$newStatus}",
                'data' => [
                    'id' => $indikator->id,
                    'sasaran_kegiatan_id' => $indikator->sasaran_kegiatan_id,
                    'sasaran_kegiatan' => $indikator->sasaranKegiatan ? [
                        'id' => $indikator->sasaranKegiatan->id,
                        'unit_kerja' => $indikator->sasaranKegiatan->unit_kerja,
                        'sasaran_kegiatan' => $indikator->sasaranKegiatan->sasaran_kegiatan,
                    ] : null,
                    'indikator_kinerja' => $indikator->indikator_kinerja,
                    'status' => $indikator->status,
                    'digunakan_asn' => $indikator->isDigunakanAsn(),
                    'created_at' => $indikator->created_at,
                    'updated_at' => $indikator->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Indikator Kinerja tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status Indikator Kinerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified Indikator Kinerja.
     *
     * DELETE /api/admin/indikator-kinerja/{id}
     */
    public function destroy($id)
    {
        try {
            $indikator = IndikatorKinerja::findOrFail($id);

            if ($indikator->isDigunakanAsn()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Indikator Kinerja tidak dapat dihapus karena sedang digunakan oleh ASN',
                ], 422);
            }

            $indikator->delete();

            return response()->json([
                'success' => true,
                'message' => 'Indikator Kinerja berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Indikator Kinerja tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Indikator Kinerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
