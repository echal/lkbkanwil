<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        $user = Auth::user();

        if ($user->status !== 'AKTIF') {
            Auth::logout();
            return response()->json([
                'message' => 'Akun Anda tidak aktif. Hubungi administrator.'
            ], 403);
        }

        // Hapus token lama (opsional, tergantung kebutuhan multi-device)
        // $user->tokens()->delete();

        // Buat token baru untuk user
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'nip'  => $user->nip,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    public function me(Request $request)
    {
        $user  = $request->user();
        $token = $request->user()->currentAccessToken();

        // Phase J-05/J-06 — Production Hardening: 'helpdesk-sso' tokens are
        // single-use. The delete happens FIRST, inside a row lock, so that
        // two concurrent requests replaying the same token (e.g. an attacker
        // racing the legitimate SSO request) cannot both observe the token as
        // still-valid before either one deletes it. Whichever request wins
        // the lock deletes the row and proceeds; the loser finds no row left
        // to lock and is rejected with 401 — never both succeeding.
        // Only this specific token name is targeted — regular 'auth_token'
        // tokens used by other API clients are unaffected.
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken && $token->name === 'helpdesk-sso') {
            $consumed = DB::transaction(function () use ($token) {
                $locked = PersonalAccessToken::where('id', $token->id)->lockForUpdate()->first();

                if ($locked === null) {
                    return false; // already consumed by a concurrent request
                }

                $locked->delete();

                return true;
            });

            if (! $consumed) {
                return response()->json([
                    'message' => 'Token tidak valid atau sudah kedaluwarsa.',
                ], 401);
            }
        }

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'nip'        => $user->nip,
                'email'      => $user->email,
                'role'       => $user->role,
                'jabatan'    => $user->jabatan,
                'unit_id'    => $user->unit_id,
                'unit_name'  => $user->unit ? $user->unit->nama_unit : ($user->unit_kerja ?? null),
                'status'     => $user->status,
            ],
        ]);
    }
}
