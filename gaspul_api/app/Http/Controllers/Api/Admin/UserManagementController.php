<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserManagementController extends Controller
{
    /**
     * Display a listing of Users (Master Pegawai).
     *
     * GET /api/admin/users
     */
    public function index(Request $request)
    {
        try {
            $query = User::with('unit');

            // Filter by role
            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            // Filter by unit_id
            if ($request->has('unit_id')) {
                $query->where('unit_id', $request->unit_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $users = $query->orderBy('created_at', 'desc')->get();

            $users = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nip' => $user->nip,
                    'email' => $user->email,
                    'role' => $user->role,
                    'unit_id' => $user->unit_id,
                    'unit_name' => $user->unit ? $user->unit->nama_unit : null,
                    'jabatan' => $user->jabatan,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data Pegawai',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created User.
     *
     * POST /api/admin/users
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nip' => 'required|string|max:18|unique:users,nip',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:ASN,ATASAN,ADMIN',
            'unit_id' => 'required|exists:units,id',
            'jabatan' => 'nullable|string|max:255',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ], [
            'name.required' => 'Nama harus diisi',
            'nip.required' => 'NIP harus diisi',
            'nip.unique' => 'NIP sudah digunakan',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 8 karakter',
            'role.required' => 'Role harus diisi',
            'role.in' => 'Role harus ASN, ATASAN, atau ADMIN',
            'unit_id.required' => 'Unit Kerja harus diisi',
            'unit_id.exists' => 'Unit Kerja tidak valid',
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
            $user = User::create([
                'name' => $request->name,
                'nip' => $request->nip,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'unit_id' => $request->unit_id,
                'jabatan' => $request->jabatan,
                'status' => $request->status,
            ]);

            $user->load('unit');

            return response()->json([
                'success' => true,
                'message' => 'Pegawai berhasil ditambahkan',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nip' => $user->nip,
                    'email' => $user->email,
                    'role' => $user->role,
                    'unit_id' => $user->unit_id,
                    'unit_name' => $user->unit ? $user->unit->nama_unit : null,
                    'jabatan' => $user->jabatan,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan Pegawai',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified User.
     *
     * GET /api/admin/users/{id}
     */
    public function show($id)
    {
        try {
            $user = User::with('unit')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nip' => $user->nip,
                    'email' => $user->email,
                    'role' => $user->role,
                    'unit_id' => $user->unit_id,
                    'unit_name' => $user->unit ? $user->unit->nama_unit : null,
                    'jabatan' => $user->jabatan,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data Pegawai',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified User.
     *
     * PUT /api/admin/users/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'nip' => 'required|string|max:18|unique:users,nip,' . $id,
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:ASN,ATASAN,ADMIN',
            'unit_id' => 'required|exists:units,id',
            'jabatan' => 'nullable|string|max:255',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ], [
            'name.required' => 'Nama harus diisi',
            'nip.required' => 'NIP harus diisi',
            'nip.unique' => 'NIP sudah digunakan',
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
            'role.required' => 'Role harus diisi',
            'role.in' => 'Role harus ASN, ATASAN, atau ADMIN',
            'unit_id.required' => 'Unit Kerja harus diisi',
            'unit_id.exists' => 'Unit Kerja tidak valid',
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
            $user = User::findOrFail($id);

            $user->update([
                'name' => $request->name,
                'nip' => $request->nip,
                'email' => $request->email,
                'role' => $request->role,
                'unit_id' => $request->unit_id,
                'jabatan' => $request->jabatan,
                'status' => $request->status,
            ]);

            $user->load('unit');

            return response()->json([
                'success' => true,
                'message' => 'Data Pegawai berhasil diperbarui',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nip' => $user->nip,
                    'email' => $user->email,
                    'role' => $user->role,
                    'unit_id' => $user->unit_id,
                    'unit_name' => $user->unit ? $user->unit->nama_unit : null,
                    'jabatan' => $user->jabatan,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data Pegawai',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset password for the specified User.
     *
     * PATCH /api/admin/users/{id}/reset-password
     */
    public function resetPassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
        ], [
            'password.required' => 'Password baru harus diisi',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::findOrFail($id);

            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mereset password',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status of the specified User.
     *
     * PATCH /api/admin/users/{id}/toggle-status
     */
    public function toggleStatus($id)
    {
        try {
            $user = User::findOrFail($id);

            $newStatus = $user->status === 'AKTIF' ? 'NONAKTIF' : 'AKTIF';
            $user->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => "Status Pegawai berhasil diubah menjadi {$newStatus}",
                'data' => [
                    'id' => $user->id,
                    'status' => $user->status,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status Pegawai',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified User.
     *
     * DELETE /api/admin/users/{id}
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deleting own account
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak dapat menghapus akun Anda sendiri',
                ], 422);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pegawai berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Pegawai',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
