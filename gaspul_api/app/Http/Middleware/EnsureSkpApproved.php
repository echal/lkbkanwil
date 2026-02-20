<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SkpAccessService;

/**
 * Middleware: Ensure SKP Tahunan Approved
 *
 * Block akses ke RHK Bulanan dan Kinerja Harian jika SKP belum disetujui.
 * TLA (Tugas Atasan Langsung) TIDAK diblokir oleh middleware ini.
 *
 * Usage:
 *   Route::middleware('skp.approved')->group(...)
 */
class EnsureSkpApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah user memiliki SKP yang disetujui
        if (!SkpAccessService::hasApprovedSkp()) {
            $message = SkpAccessService::getAccessDeniedMessage();

            // Jika AJAX request, return JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'redirect' => route('asn.skp-tahunan.index'),
                ], 403);
            }

            // Redirect ke halaman SKP Tahunan dengan pesan error
            return redirect()
                ->route('asn.skp-tahunan.index')
                ->with('warning', $message)
                ->with('skp_required', true);
        }

        return $next($request);
    }
}
