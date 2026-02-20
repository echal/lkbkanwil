<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * NonAdmin Middleware
 *
 * Middleware untuk membatasi akses hanya untuk user NON-ADMIN.
 * ADMIN tidak boleh mengakses fitur SKP, Kinerja Harian, dan LKB.
 *
 * Allowed roles:
 * - ASN
 * - ATASAN
 *
 * Blocked role:
 * - ADMIN
 *
 * @author Claude Sonnet 4.5
 * @date 2026-02-14
 */
class NonAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Cek apakah user adalah ADMIN.
     * Jika ADMIN, reject dengan 403.
     * Jika bukan ADMIN (ASN/ATASAN), allow.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan user sudah login
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Block ADMIN dari mengakses fitur SKP & Kinerja Harian
        if (strtoupper(auth()->user()->role) === 'ADMIN') {
            abort(403, 'Admin tidak memiliki akses ke fitur SKP dan Kinerja Harian. Fitur ini hanya untuk ASN dan ATASAN.');
        }

        // Allow ASN dan ATASAN
        return $next($request);
    }
}
