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
        $user = Auth::user();
        $user->load(['unitKerja', 'unit']);

        return view('profile.edit', [
            'user' => $user,
        ]);
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
