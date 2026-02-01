<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Asn\HarianController;
use App\Http\Controllers\Asn\RencanaKerjaController;
use App\Http\Controllers\Asn\SkpTahunanController;
use App\Http\Controllers\Asn\BulananController;
use App\Http\Controllers\Asn\LaporanCetakController;
use App\Http\Controllers\Atasan\ApprovalController;
use App\Http\Controllers\Atasan\KinerjaBawahanController;
use App\Http\Controllers\Atasan\SkpTahunanAtasanController;
use App\Http\Controllers\Atasan\HarianBawahanController;
use App\Http\Controllers\Atasan\RekapKinerjaCetakController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\SasaranKegiatanController;
use App\Http\Controllers\Admin\IndikatorKinerjaController;
use App\Http\Controllers\Admin\UnitKerjaController;
use App\Http\Controllers\Admin\PegawaiController;
use App\Http\Controllers\Admin\RhkPimpinanController;
use App\Http\Controllers\ProfileController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout.get'); // Fallback untuk expired session

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile & Settings
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/settings', function() { return view('settings.index'); })->name('settings.index');

    // ASN Routes
    Route::prefix('asn')->name('asn.')->middleware('role:ASN')->group(function () {
        // SKP Tahunan
        Route::get('/skp-tahunan', [SkpTahunanController::class, 'index'])->name('skp-tahunan.index');
        Route::get('/skp-tahunan/create', [SkpTahunanController::class, 'create'])->name('skp-tahunan.create');
        Route::post('/skp-tahunan/store', [SkpTahunanController::class, 'store'])->name('skp-tahunan.store');

        // Route Model Binding: parameter {detail} akan auto-inject SkpTahunanDetail model
        Route::get('/skp-tahunan/edit/{detail}', [SkpTahunanController::class, 'edit'])->name('skp-tahunan.edit');
        Route::put('/skp-tahunan/update/{detail}', [SkpTahunanController::class, 'update'])->name('skp-tahunan.update');
        Route::delete('/skp-tahunan/destroy/{detail}', [SkpTahunanController::class, 'destroy'])->name('skp-tahunan.destroy');
        Route::post('/skp-tahunan/submit/{id}', [SkpTahunanController::class, 'submit'])->name('skp-tahunan.submit');

        // Revision Management
        Route::post('/skp-tahunan/{skpTahunan}/ajukan-revisi', [SkpTahunanController::class, 'ajukanRevisi'])->name('skp-tahunan.ajukan-revisi');

        // Kinerja Harian
        Route::get('/harian', [HarianController::class, 'index'])->name('harian.index');
        Route::get('/harian/pilih', [HarianController::class, 'pilih'])->name('harian.pilih');
        Route::get('/harian/form-kinerja', [HarianController::class, 'formKinerja'])->name('harian.form-kinerja');
        Route::post('/harian/store-kinerja', [HarianController::class, 'storeKinerja'])->name('harian.store-kinerja');
        Route::get('/harian/form-tla', [HarianController::class, 'formTla'])->name('harian.form-tla');
        Route::post('/harian/store-tla', [HarianController::class, 'storeTla'])->name('harian.store-tla');
        Route::get('/harian/edit/{id}', [HarianController::class, 'edit'])->name('harian.edit');
        Route::put('/harian/update/{id}', [HarianController::class, 'update'])->name('harian.update');
        Route::delete('/harian/destroy/{id}', [HarianController::class, 'destroy'])->name('harian.destroy');
        Route::get('/harian/cetak/{id}', [HarianController::class, 'cetakKinerjaHarian'])->name('harian.cetak');
        Route::get('/harian/cetak-tla/{id}', [HarianController::class, 'cetakTugasAtasan'])->name('harian.cetak-tla');

        // Rencana Kerja
        Route::get('/rencana-kerja', [RencanaKerjaController::class, 'index'])->name('rencana-kerja.index');
        Route::get('/rencana-kerja/tambah', [RencanaKerjaController::class, 'create'])->name('rencana-kerja.tambah');
        Route::post('/rencana-kerja/store', [RencanaKerjaController::class, 'store'])->name('rencana-kerja.store');
        Route::get('/rencana-kerja/detail/{id}', [RencanaKerjaController::class, 'show'])->name('rencana-kerja.detail');
        Route::get('/rencana-kerja/edit/{id}', [RencanaKerjaController::class, 'edit'])->name('rencana-kerja.edit');
        Route::put('/rencana-kerja/update/{id}', [RencanaKerjaController::class, 'update'])->name('rencana-kerja.update');
        Route::delete('/rencana-kerja/destroy/{id}', [RencanaKerjaController::class, 'destroy'])->name('rencana-kerja.destroy');

        // Bulanan (Laporan Kinerja Bulanan)
        Route::get('/bulanan', [BulananController::class, 'index'])->name('bulanan.index');
        Route::get('/bulanan/export-pdf', [BulananController::class, 'exportPdf'])->name('bulanan.export-pdf');
        Route::post('/bulanan/kirim-atasan', [BulananController::class, 'kirimKeAtasan'])->name('bulanan.kirim-atasan');

        // Cetak PDF Laporan (NEW - for ASN)
        Route::get('/laporan/cetak-harian', [LaporanCetakController::class, 'cetakHarian'])->name('laporan.cetak-harian');
        Route::get('/laporan/cetak-bulanan', [LaporanCetakController::class, 'cetakBulanan'])->name('laporan.cetak-bulanan');
    });

    // Atasan Routes
    Route::prefix('atasan')->name('atasan.')->middleware('role:ATASAN')->group(function () {
        // SKP Tahunan
        Route::get('/skp-tahunan', [SkpTahunanAtasanController::class, 'index'])->name('skp-tahunan.index');
        Route::get('/skp-tahunan/{id}', [SkpTahunanAtasanController::class, 'show'])->name('skp-tahunan.show');
        Route::post('/skp-tahunan/{id}/approve', [SkpTahunanAtasanController::class, 'approve'])->name('skp-tahunan.approve');
        Route::post('/skp-tahunan/{id}/reject', [SkpTahunanAtasanController::class, 'reject'])->name('skp-tahunan.reject');

        // Revision Management (Route Model Binding)
        Route::post('/skp-tahunan/{skpTahunan}/setujui-revisi', [SkpTahunanAtasanController::class, 'setujuiRevisi'])->name('skp-tahunan.setujui-revisi');
        Route::post('/skp-tahunan/{skpTahunan}/tolak-revisi', [SkpTahunanAtasanController::class, 'tolakRevisi'])->name('skp-tahunan.tolak-revisi');

        // Approval/Persetujuan
        Route::get('/approval', [ApprovalController::class, 'index'])->name('approval.index');
        Route::get('/approval/{id}', [ApprovalController::class, 'show'])->name('approval.show');

        // Harian Bawahan (TAHAP 5.1 - Monitoring Dashboard)
        Route::get('/harian-bawahan', [HarianBawahanController::class, 'index'])->name('harian-bawahan.index');
        Route::get('/harian-bawahan/detail/{user_id}', [HarianBawahanController::class, 'detail'])->name('harian-bawahan.detail');
        Route::get('/harian-bawahan/cetak-lkh/{user_id}/{tanggal}', [HarianBawahanController::class, 'cetakLKH'])->name('harian-bawahan.cetak-lkh');
        Route::get('/harian-bawahan/cetak-tla/{user_id}/{tanggal}', [HarianBawahanController::class, 'cetakTLA'])->name('harian-bawahan.cetak-tla');

        // Rekap Kinerja - Cetak PDF (TAHAP 5.2 - PDF Export)
        Route::get('/rekap/mingguan/cetak', [RekapKinerjaCetakController::class, 'cetakMingguan'])->name('rekap.cetak-mingguan');
        Route::get('/rekap/bulanan/cetak', [RekapKinerjaCetakController::class, 'cetakBulanan'])->name('rekap.cetak-bulanan');

        // Kinerja Bawahan
        Route::get('/kinerja-bawahan', [KinerjaBawahanController::class, 'index'])->name('kinerja-bawahan.index');
    });

    // Admin Routes
    Route::prefix('admin')->name('admin.')->middleware('role:ADMIN')->group(function () {
        // Users (old routes - keep as is)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/tambah', [UserController::class, 'create'])->name('users.tambah');
        Route::get('/users/edit/{id}', [UserController::class, 'edit'])->name('users.edit');

        // Units (old routes - keep as is)
        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::get('/units/tambah', [UnitController::class, 'create'])->name('units.tambah');
        Route::get('/units/edit/{id}', [UnitController::class, 'edit'])->name('units.edit');

        // Sasaran Kegiatan (Resource)
        Route::resource('sasaran-kegiatan', SasaranKegiatanController::class);

        // Indikator Kinerja (Resource)
        Route::resource('indikator-kinerja', IndikatorKinerjaController::class);

        // Unit Kerja (Resource)
        Route::resource('unit-kerja', UnitKerjaController::class);

        // Data Pegawai (Resource)
        Route::resource('pegawai', PegawaiController::class);

        // RHK Pimpinan (Resource)
        Route::resource('rhk-pimpinan', RhkPimpinanController::class);
    });
});
