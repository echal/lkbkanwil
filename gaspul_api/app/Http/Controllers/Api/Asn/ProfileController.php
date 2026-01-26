<?php

namespace App\Http\Controllers\Api\Asn;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile (READ ONLY).
     *
     * GET /api/asn/profile
     */
    public function show(Request $request)
    {
        try {
            $user = $request->user()->load('unit');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nip' => $user->nip,
                    'email' => $user->email,
                    'role' => $user->role,
                    'unit_id' => $user->unit_id,
                    'unit_name' => $user->unit ? $user->unit->nama_unit : $user->unit_kerja, // Fallback to old field
                    'unit_kode' => $user->unit ? $user->unit->kode_unit : null,
                    'jabatan' => $user->jabatan,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'message' => 'Profil ini dikelola oleh Administrator dan tidak dapat diubah sendiri.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat profil',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
