<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IndikatorTahunanController;
use App\Http\Controllers\RhkPimpinanController;
use App\Http\Controllers\Api\Admin\SasaranKegiatanController;
use App\Http\Controllers\Api\Admin\IndikatorKinerjaController;
use App\Http\Controllers\Api\Admin\UnitController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Asn\RencanaKerjaAsnController;
use App\Http\Controllers\Api\Asn\ProfileController;
use App\Http\Controllers\Api\Atasan\ApprovalController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Semua route di sini menggunakan prefix /api
| Contoh: /api/login
*/

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (TANPA LOGIN)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (WAJIB LOGIN)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH ROUTES
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | ROLE: ADMIN
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN')->group(function () {

        Route::get('/admin/dashboard', function () {
            return response()->json([
                'role' => 'ADMIN',
                'message' => 'Akses Admin berhasil'
            ]);
        });

        // RHK Pimpinan Management (ADMIN only) - DEPRECATED, gunakan Sasaran Kegiatan
        Route::get('/rhk-pimpinan', [RhkPimpinanController::class, 'index']);
        Route::post('/rhk-pimpinan', [RhkPimpinanController::class, 'store']);
        Route::put('/rhk-pimpinan/{id}', [RhkPimpinanController::class, 'update']);
        Route::delete('/rhk-pimpinan/{id}', [RhkPimpinanController::class, 'destroy']);
        Route::patch('/rhk-pimpinan/{id}/toggle-status', [RhkPimpinanController::class, 'toggleStatus']);

        // Master Kinerja Organisasi (SAKIP-Compliant)
        Route::prefix('admin')->group(function () {
            // Master Unit Kerja
            Route::apiResource('units', UnitController::class);
            Route::patch('units/{id}/toggle-status', [UnitController::class, 'toggleStatus']);

            // Master Pegawai (User Management)
            Route::apiResource('users', UserManagementController::class);
            Route::patch('users/{id}/reset-password', [UserManagementController::class, 'resetPassword']);
            Route::patch('users/{id}/toggle-status', [UserManagementController::class, 'toggleStatus']);

            // Sasaran Kegiatan
            Route::apiResource('sasaran-kegiatan', SasaranKegiatanController::class);
            Route::patch('sasaran-kegiatan/{id}/toggle-status', [SasaranKegiatanController::class, 'toggleStatus']);

            // Indikator Kinerja
            Route::apiResource('indikator-kinerja', IndikatorKinerjaController::class);
            Route::patch('indikator-kinerja/{id}/toggle-status', [IndikatorKinerjaController::class, 'toggleStatus']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | ROLE: ASN
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ASN')->group(function () {

        Route::get('/asn/dashboard', function () {
            return response()->json([
                'role' => 'ASN',
                'message' => 'Akses ASN berhasil'
            ]);
        });

        // Indikator Tahunan CRUD (DEPRECATED - use Rencana Kerja ASN)
        Route::apiResource('/asn/indikator-tahunan', IndikatorTahunanController::class);

        // Get active RHK Pimpinan for ASN to select (DEPRECATED)
        Route::get('/asn/rhk-pimpinan/active', [RhkPimpinanController::class, 'getActive']);

        // Rencana Kerja ASN (PERKIN-Compliant)
        Route::prefix('asn')->group(function () {
            // Profile (READ ONLY - dikelola oleh Admin)
            Route::get('profile', [ProfileController::class, 'show']);

            // Get master data untuk form - FILTERED BY UNIT KERJA
            Route::get('sasaran-kegiatan', [App\Http\Controllers\Api\Asn\MasterDataController::class, 'getSasaranKegiatan']);
            Route::get('indikator-kinerja', [App\Http\Controllers\Api\Asn\MasterDataController::class, 'getIndikatorKinerja']);

            // DEPRECATED - Old routes (untuk backward compatibility)
            Route::get('sasaran-kegiatan/active', [RencanaKerjaAsnController::class, 'getActiveSasaran']);
            Route::get('sasaran-kegiatan/{sasaranId}/indikator', [RencanaKerjaAsnController::class, 'getIndikatorBySasaran']);

            // SKP TAHUNAN (WAJIB PERTAMA - Hierarki Baru)
            Route::get('skp-tahunan/approved', [App\Http\Controllers\Api\Asn\SkpTahunanController::class, 'getApprovedList']);
            Route::post('skp-tahunan/{id}/submit', [App\Http\Controllers\Api\Asn\SkpTahunanController::class, 'submit']);
            Route::get('skp-tahunan/detail/{detailId}', [App\Http\Controllers\Api\Asn\SkpTahunanController::class, 'showDetail']);
            Route::put('skp-tahunan/detail/{detailId}', [App\Http\Controllers\Api\Asn\SkpTahunanController::class, 'updateDetail']);
            Route::delete('skp-tahunan/detail/{detailId}', [App\Http\Controllers\Api\Asn\SkpTahunanController::class, 'deleteDetail']);
            Route::apiResource('skp-tahunan', App\Http\Controllers\Api\Asn\SkpTahunanController::class);

            // CRUD Rencana Kerja (SKP Triwulan - Referensi SKP Tahunan)
            Route::apiResource('rencana-kerja', RencanaKerjaAsnController::class);

            // Additional actions
            Route::post('rencana-kerja/{id}/submit', [RencanaKerjaAsnController::class, 'submit']);
            Route::patch('rencana-kerja/{id}/realisasi', [RencanaKerjaAsnController::class, 'updateRealisasi']);

            // Bulanan Management
            Route::get('bulanan/available', [App\Http\Controllers\Api\Asn\HarianController::class, 'getAvailableBulanan']);
            Route::get('bulanan/skp/{skpId}', [App\Http\Controllers\Api\Asn\BulananController::class, 'getBulananBySkp']);
            Route::apiResource('bulanan', App\Http\Controllers\Api\Asn\BulananController::class)->only(['index', 'show', 'update']);

            // Harian Management
            Route::apiResource('harian', App\Http\Controllers\Api\Asn\HarianController::class);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | ROLE: ATASAN
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ATASAN')->group(function () {

        Route::get('/atasan/dashboard', function () {
            return response()->json([
                'role' => 'ATASAN',
                'message' => 'Akses Atasan berhasil'
            ]);
        });

        // Approval Management
        Route::prefix('atasan')->group(function () {
            // Get statistics
            Route::get('stats', [ApprovalController::class, 'stats']);

            // SKP TAHUNAN APPROVAL
            Route::get('skp-tahunan', [ApprovalController::class, 'indexSkpTahunan']);
            Route::get('skp-tahunan/{id}', [ApprovalController::class, 'showSkpTahunan']);
            Route::post('skp-tahunan/{id}/approve', [ApprovalController::class, 'approveSkpTahunan']);
            Route::post('skp-tahunan/{id}/reject', [ApprovalController::class, 'rejectSkpTahunan']);
        });
    });

});

/*
|--------------------------------------------------------------------------
| API ROUTES V2.0 - TOTAL REFACTOR
|--------------------------------------------------------------------------
*/
require __DIR__ . '/api_v2.php';
