<?php

use Illuminate\Support\Facades\Route;

/**
 * API ROUTES V2.0 - TOTAL REFACTOR
 *
 * PERUBAHAN SISTEM:
 * - SKP Tahunan: Header-Detail pattern dengan RHK Pimpinan
 * - Rencana Aksi Bulanan: Menggantikan modul Bulanan lama
 * - Progres Harian: Dengan jam kerja & bukti dukung
 * - Master Atasan: Relasi ASN - Atasan per tahun
 *
 * NOTE: Routes ini harus ditambahkan ke api.php atau di-include
 */

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (WAJIB LOGIN)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | ROLE: ADMIN
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN')->prefix('admin/v2')->group(function () {

        // Master Atasan Management
        // IMPORTANT: Specific routes MUST come BEFORE apiResource
        Route::get('master-atasan/asn/list', [App\Http\Controllers\Api\Admin\MasterAtasanController::class, 'getAsnList']);
        Route::get('master-atasan/atasan/list', [App\Http\Controllers\Api\Admin\MasterAtasanController::class, 'getAtasanList']);
        Route::post('master-atasan/get-atasan-for-asn', [App\Http\Controllers\Api\Admin\MasterAtasanController::class, 'getAtasanForAsn']);
        Route::post('master-atasan/get-asn-under-atasan', [App\Http\Controllers\Api\Admin\MasterAtasanController::class, 'getAsnUnderAtasan']);
        Route::apiResource('master-atasan', App\Http\Controllers\Api\Admin\MasterAtasanController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | ROLE: ATASAN
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ATASAN')->prefix('atasan/v2')->group(function () {

        // Indikator Kinerja (Read Only - untuk dropdown RHK Pimpinan)
        Route::get('indikator-kinerja', [\App\Http\Controllers\Api\Admin\IndikatorKinerjaController::class, 'index']);
        Route::get('indikator-kinerja/{id}', [\App\Http\Controllers\Api\Admin\IndikatorKinerjaController::class, 'show']);

        // RHK Pimpinan Management (formerly Indikator Kinerja table)
        // IMPORTANT: Specific routes MUST come BEFORE apiResource
        Route::get('rhk-pimpinan/active/list', [App\Http\Controllers\Api\Atasan\RhkPimpinanController::class, 'getActive']);
        Route::get('rhk-pimpinan/by-indikator/{indikatorKinerjaId}', [App\Http\Controllers\Api\Atasan\RhkPimpinanController::class, 'getByIndikatorKinerja']);
        Route::apiResource('rhk-pimpinan', App\Http\Controllers\Api\Atasan\RhkPimpinanController::class);

        // ✅ KINERJA HARIAN BAWAHAN - Dashboard Pengawasan
        Route::prefix('kinerja-bawahan')->group(function () {
            Route::get('/biodata', [App\Http\Controllers\Api\Atasan\KinerjaBawahanController::class, 'getBiodata']);
            Route::get('/', [App\Http\Controllers\Api\Atasan\KinerjaBawahanController::class, 'getKinerjaBawahan']);
            Route::get('/cetak-kh/{userId}', [App\Http\Controllers\Api\Atasan\KinerjaBawahanController::class, 'cetakLaporanKH']);
            Route::get('/cetak-tla/{userId}', [App\Http\Controllers\Api\Atasan\KinerjaBawahanController::class, 'cetakLaporanTLA']);
        });

        // SKP Tahunan Approval (V2)
        // TODO: Create ApprovalControllerV2
        // Route::get('skp-tahunan', [App\Http\Controllers\Api\Atasan\ApprovalControllerV2::class, 'indexSkpTahunan']);
        // Route::get('skp-tahunan/{id}', [App\Http\Controllers\Api\Atasan\ApprovalControllerV2::class, 'showSkpTahunan']);
        // Route::post('skp-tahunan/{id}/approve', [App\Http\Controllers\Api\Atasan\ApprovalControllerV2::class, 'approveSkpTahunan']);
        // Route::post('skp-tahunan/{id}/reject', [App\Http\Controllers\Api\Atasan\ApprovalControllerV2::class, 'rejectSkpTahunan']);
    });

    /*
    |--------------------------------------------------------------------------
    | ROLE: ASN
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:ASN')->prefix('asn/v2')->group(function () {

        // RHK Pimpinan (Read Only - untuk dropdown)
        Route::get('rhk-pimpinan/active', [App\Http\Controllers\Api\Atasan\RhkPimpinanController::class, 'getActive']);
        Route::get('rhk-pimpinan/by-sasaran/{sasaranKegiatanId}', [App\Http\Controllers\Api\Atasan\RhkPimpinanController::class, 'getBySasaranKegiatan']);

        // SKP TAHUNAN V2 (Header-Detail Pattern)
        Route::prefix('skp-tahunan')->group(function () {
            // Header operations
            Route::get('/', [App\Http\Controllers\Api\Asn\SkpTahunanControllerV2::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\Api\Asn\SkpTahunanControllerV2::class, 'show']);
            Route::post('/create-or-get', [App\Http\Controllers\Api\Asn\SkpTahunanControllerV2::class, 'createOrGetHeader']);
            Route::post('/{id}/submit', [App\Http\Controllers\Api\Asn\SkpTahunanControllerV2::class, 'submit']);

            // Detail operations
            Route::post('/{skpTahunanId}/detail', [App\Http\Controllers\Api\Asn\SkpTahunanControllerV2::class, 'addDetail']);
            Route::put('/{skpTahunanId}/detail/{detailId}', [App\Http\Controllers\Api\Asn\SkpTahunanControllerV2::class, 'updateDetail']);
            Route::delete('/{skpTahunanId}/detail/{detailId}', [App\Http\Controllers\Api\Asn\SkpTahunanControllerV2::class, 'deleteDetail']);
        });

        // RENCANA AKSI BULANAN (Menggantikan Bulanan lama)
        Route::prefix('rencana-aksi-bulanan')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Asn\RencanaAksiBulananController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\Api\Asn\RencanaAksiBulananController::class, 'show']);
            Route::get('/by-detail/{skpTahunanDetailId}', [App\Http\Controllers\Api\Asn\RencanaAksiBulananController::class, 'getByDetail']);
            Route::put('/{id}', [App\Http\Controllers\Api\Asn\RencanaAksiBulananController::class, 'update']);
            Route::get('/summary/year', [App\Http\Controllers\Api\Asn\RencanaAksiBulananController::class, 'getSummary']);
        });

        // PROGRES HARIAN (Menggantikan Harian lama)
        Route::prefix('progres-harian')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Asn\ProgresHarianController::class, 'index']);
            Route::get('/{id}', [App\Http\Controllers\Api\Asn\ProgresHarianController::class, 'show']);
            Route::post('/', [App\Http\Controllers\Api\Asn\ProgresHarianController::class, 'store']);
            Route::put('/{id}', [App\Http\Controllers\Api\Asn\ProgresHarianController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\Api\Asn\ProgresHarianController::class, 'destroy']);

            // Specialized endpoints
            Route::post('/by-date', [App\Http\Controllers\Api\Asn\ProgresHarianController::class, 'getByDate']);
            Route::post('/calendar', [App\Http\Controllers\Api\Asn\ProgresHarianController::class, 'getCalendar']);
            Route::put('/{id}/bukti-dukung', [App\Http\Controllers\Api\Asn\ProgresHarianController::class, 'updateBuktiDukung']);
        });

        // ✅ LAPORAN KINERJA ASN (PERSONAL) - Self Monitoring
        Route::prefix('laporan-kinerja')->group(function () {
            Route::get('/biodata', [App\Http\Controllers\Api\Asn\LaporanKinerjaController::class, 'getBiodata']);
            Route::get('/', [App\Http\Controllers\Api\Asn\LaporanKinerjaController::class, 'getLaporanKinerja']);
            Route::get('/cetak-kh', [App\Http\Controllers\Api\Asn\LaporanKinerjaController::class, 'cetakLaporanKH']);
            Route::get('/cetak-tla', [App\Http\Controllers\Api\Asn\LaporanKinerjaController::class, 'cetakLaporanTLA']);
        });
    });

});
