<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Profile Controller (Web)
 *
 * Handle user profile viewing and password change
 */
class ProfileController extends Controller
{
    /**
     * Show profile edit page
     */
    public function edit()
    {
        try {
            $user = Auth::user();

            // Try to load unitKerja relation if exists (safe loading)
            try {
                if (method_exists($user, 'unitKerja')) {
                    $user->load('unitKerja');
                }
            } catch (\Exception $relationError) {
                // Silently fail if relation loading fails
                \Log::warning('UnitKerja relation loading failed', [
                    'user_id' => $user->id,
                    'error' => $relationError->getMessage(),
                ]);
            }

            return view('profile.edit', [
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            // Log error for debugging
            \Log::error('Profile Edit Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('dashboard')
                ->with('error', 'Terjadi kesalahan saat memuat halaman profil. Silakan hubungi administrator.');
        }
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Password lama wajib diisi',
            'password.required' => 'Password baru wajib diisi',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        // Check current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()
                ->route('profile.edit')
                ->with('error', 'Password lama tidak sesuai')
                ->withInput();
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Password berhasil diubah');
    }

    /**
     * Update profile (only email - other fields managed by Admin)
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
        ], [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah digunakan',
        ]);

        $user->update([
            'email' => $validated['email'],
        ]);

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Email berhasil diubah');
    }
}
