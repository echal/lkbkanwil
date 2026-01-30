<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class LoginController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Attempt login directly with Laravel Auth (no API call)
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Store user data in session (for compatibility)
            session(['user' => $user]);

            // Redirect based on role
            return $this->redirectBasedOnRole($user->role);
        }

        // Login failed
        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->withInput($request->only('email'));
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        // Clear session
        session()->forget('user');

        // Logout from Laravel
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Redirect user based on their role
     */
    private function redirectBasedOnRole($role)
    {
        switch (strtoupper($role)) {
            case 'ADMIN':
                return redirect()->route('admin.users.index');
            case 'ATASAN':
                return redirect()->route('dashboard');
            case 'ASN':
            default:
                return redirect()->route('dashboard');
        }
    }
}
