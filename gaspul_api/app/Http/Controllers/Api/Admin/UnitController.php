<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    /**
     * Display a listing of Units with user count.
     *
     * GET /api/admin/units
     */
    public function index()
    {
        try {
            $units = Unit::withCount('users')
                ->orderBy('created_at', 'desc')
                ->get();

            $units = $units->map(function ($unit) {
                return [
                    'id' => $unit->id,
                    'nama_unit' => $unit->nama_unit,
                    'kode_unit' => $unit->kode_unit,
                    'status' => $unit->status,
                    'jumlah_pegawai' => $unit->users_count,
                    'digunakan_pegawai' => $unit->users_count > 0,
                    'created_at' => $unit->created_at,
                    'updated_at' => $unit->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $units,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data Unit Kerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created Unit.
     *
     * POST /api/admin/units
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_unit' => 'required|string|max:255',
            'kode_unit' => 'required|string|max:20|unique:units,kode_unit',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ], [
            'nama_unit.required' => 'Nama Unit Kerja harus diisi',
            'nama_unit.max' => 'Nama Unit Kerja maksimal 255 karakter',
            'kode_unit.required' => 'Kode Unit harus diisi',
            'kode_unit.max' => 'Kode Unit maksimal 20 karakter',
            'kode_unit.unique' => 'Kode Unit sudah digunakan',
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
            $unit = Unit::create($request->only([
                'nama_unit',
                'kode_unit',
                'status'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Unit Kerja berhasil ditambahkan',
                'data' => [
                    'id' => $unit->id,
                    'nama_unit' => $unit->nama_unit,
                    'kode_unit' => $unit->kode_unit,
                    'status' => $unit->status,
                    'jumlah_pegawai' => 0,
                    'digunakan_pegawai' => false,
                    'created_at' => $unit->created_at,
                    'updated_at' => $unit->updated_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan Unit Kerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified Unit.
     *
     * GET /api/admin/units/{id}
     */
    public function show($id)
    {
        try {
            $unit = Unit::withCount('users')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $unit->id,
                    'nama_unit' => $unit->nama_unit,
                    'kode_unit' => $unit->kode_unit,
                    'status' => $unit->status,
                    'jumlah_pegawai' => $unit->users_count,
                    'digunakan_pegawai' => $unit->users_count > 0,
                    'created_at' => $unit->created_at,
                    'updated_at' => $unit->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unit Kerja tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data Unit Kerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified Unit.
     *
     * PUT /api/admin/units/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nama_unit' => 'required|string|max:255',
            'kode_unit' => 'required|string|max:20|unique:units,kode_unit,' . $id,
            'status' => 'required|in:AKTIF,NONAKTIF',
        ], [
            'nama_unit.required' => 'Nama Unit Kerja harus diisi',
            'nama_unit.max' => 'Nama Unit Kerja maksimal 255 karakter',
            'kode_unit.required' => 'Kode Unit harus diisi',
            'kode_unit.max' => 'Kode Unit maksimal 20 karakter',
            'kode_unit.unique' => 'Kode Unit sudah digunakan',
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
            $unit = Unit::findOrFail($id);

            $unit->update($request->only([
                'nama_unit',
                'kode_unit',
                'status'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Unit Kerja berhasil diperbarui',
                'data' => [
                    'id' => $unit->id,
                    'nama_unit' => $unit->nama_unit,
                    'kode_unit' => $unit->kode_unit,
                    'status' => $unit->status,
                    'jumlah_pegawai' => $unit->users()->count(),
                    'digunakan_pegawai' => $unit->users()->exists(),
                    'created_at' => $unit->created_at,
                    'updated_at' => $unit->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unit Kerja tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui Unit Kerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status of the specified Unit.
     *
     * PATCH /api/admin/units/{id}/toggle-status
     */
    public function toggleStatus($id)
    {
        try {
            $unit = Unit::findOrFail($id);

            $newStatus = $unit->status === 'AKTIF' ? 'NONAKTIF' : 'AKTIF';
            $unit->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => "Status Unit Kerja berhasil diubah menjadi {$newStatus}",
                'data' => [
                    'id' => $unit->id,
                    'nama_unit' => $unit->nama_unit,
                    'kode_unit' => $unit->kode_unit,
                    'status' => $unit->status,
                    'jumlah_pegawai' => $unit->users()->count(),
                    'digunakan_pegawai' => $unit->users()->exists(),
                    'created_at' => $unit->created_at,
                    'updated_at' => $unit->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unit Kerja tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status Unit Kerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified Unit.
     *
     * DELETE /api/admin/units/{id}
     */
    public function destroy($id)
    {
        try {
            $unit = Unit::findOrFail($id);

            if ($unit->isDigunakanPegawai()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit Kerja tidak dapat dihapus karena sedang digunakan oleh pegawai',
                ], 422);
            }

            $unit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Unit Kerja berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unit Kerja tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Unit Kerja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
