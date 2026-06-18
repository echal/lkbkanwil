<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\HelpdeskTokenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Asn\HarianController;
use App\Http\Controllers\Asn\RencanaKerjaController;
use App\Http\Controllers\Asn\SkpTahunanController;
use App\Http\Controllers\Asn\BulananController;
use App\Http\Controllers\Asn\LaporanCetakController;
use App\Http\Controllers\Asn\TutorialController;
use App\Http\Controllers\Asn\CutiController;
use App\Http\Controllers\Atasan\ApprovalController;
use App\Http\Controllers\Atasan\KinerjaBawahanController;
use App\Http\Controllers\Atasan\SkpTahunanAtasanController;
use App\Http\Controllers\Atasan\HarianBawahanController;
use App\Http\Controllers\Atasan\RekapKinerjaCetakController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\SasaranKegiatanController;
use App\Http\Controllers\Admin\IndikatorKinerjaController;
use App\Http\Controllers\Admin\IndikatorUnitKerjaController;
use App\Http\Controllers\Admin\UnitKerjaController;
use App\Http\Controllers\Admin\PegawaiController;
use App\Http\Controllers\Admin\RhkPimpinanController;
use App\Http\Controllers\Admin\ImportAsnController;
use App\Http\Controllers\Admin\RotasiJabatanController;
use App\Http\Controllers\Admin\KalenderLiburKhususController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MonitoringKakanwilController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// ============================================================================
// PUBLIC MONITORING — Tidak perlu login, dilindungi token key
// GET /monitoring-kakanwil?key=<KAKANWIL_MONITOR_KEY>
// ============================================================================
Route::get('/monitoring-kakanwil', [MonitoringKakanwilController::class, 'index'])
    ->name('monitoring.kakanwil');
Route::get('/monitoring-kakanwil/clear-cache', [MonitoringKakanwilController::class, 'clearCache'])
    ->name('monitoring.kakanwil.clear-cache');
Route::get('/monitoring-kakanwil/asn-aktif-detail', [MonitoringKakanwilController::class, 'asnAktifDetail'])
    ->name('monitoring.kakanwil.asn-aktif-detail');
Route::get('/monitoring-kakanwil/asn-aktif-export', [MonitoringKakanwilController::class, 'exportExcel'])
    ->name('monitoring.kakanwil.asn-aktif-export');
Route::get('/monitoring-kakanwil/asn-belum-isi', [MonitoringKakanwilController::class, 'asnBelumIsiPerUnit'])
    ->name('monitoring.kakanwil.asn-belum-isi');
Route::get('/monitoring-kakanwil/skp-detail', [MonitoringKakanwilController::class, 'skpDetail'])
    ->name('monitoring.kakanwil.skp-detail');

// ============================================================================
// Dashboard TV Publik — Kankemenag Kabupaten Pasangkayu
// GET /monitoring-tv/pasangkayu?token=PASANGKAYU2026TV
// ============================================================================
Route::get('/monitoring-tv/pasangkayu', [\App\Http\Controllers\MonitoringPasangkayuController::class, 'index'])
    ->name('monitoring.pasangkayu');
Route::get('/monitoring-tv/pasangkayu/clear-cache', [\App\Http\Controllers\MonitoringPasangkayuController::class, 'clearCache'])
    ->name('monitoring.pasangkayu.clear-cache');

// ============================================================================
// Dashboard TV Publik — Kankemenag Kabupaten Mamuju Tengah
// GET /monitoring-tv/mamuju-tengah?token=MATENGTV2026
// ============================================================================
Route::get('/monitoring-tv/mamuju-tengah', [\App\Http\Controllers\MonitoringMamujuTengahController::class, 'index'])
    ->name('monitoring.mamuju-tengah');
Route::get('/monitoring-tv/mamuju-tengah/clear-cache', [\App\Http\Controllers\MonitoringMamujuTengahController::class, 'clearCache'])
    ->name('monitoring.mamuju-tengah.clear-cache');

// ============================================================================
// Dashboard Monitoring — Bidang Bimbingan Masyarakat Islam
// GET /monitoring-tv/bimas-islam?token=BIMASISLAM2026
// ============================================================================
Route::get('/monitoring-tv/bimas-islam', [\App\Http\Controllers\MonitoringBimasIslamController::class, 'index'])
    ->name('monitoring.bimas-islam');
Route::get('/monitoring-tv/bimas-islam/clear-cache', [\App\Http\Controllers\MonitoringBimasIslamController::class, 'clearCache'])
    ->name('monitoring.bimas-islam.clear-cache');

// ============================================================================
// Dashboard Kepatuhan ASN — Kankemenag Kabupaten Mamasa
// GET /monitoring-tv/mamasa?token=MAMASA2026TV
// ============================================================================
Route::get('/monitoring-tv/mamasa', [\App\Http\Controllers\MonitoringMamasaController::class, 'index'])
    ->name('monitoring.mamasa');
Route::get('/monitoring-tv/mamasa/clear-cache', [\App\Http\Controllers\MonitoringMamasaController::class, 'clearCache'])
    ->name('monitoring.mamasa.clear-cache');
Route::get('/monitoring-tv/mamasa/asn-detail/{id}', [\App\Http\Controllers\MonitoringMamasaController::class, 'asnDetail'])
    ->name('monitoring.mamasa.asn-detail');

// ============================================================================
// HELPDESK SSO — Dipanggil via fetch() dari floating button (same-origin, web session)
// POST /api/helpdesk-token  → JSON { token, helpdesk_url }
// Route ini di web.php agar punya session middleware (auth:web dapat membaca session)
// ============================================================================
Route::middleware(['auth', 'role:ASN', 'throttle:10,1'])
    ->post('/api/helpdesk-token', [HelpdeskTokenController::class, 'issue'])
    ->name('helpdesk.token');

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    // Phase J-02: throttle:5,1 — mencegah brute-force credential (lihat
    // PHASE_PRODUCTION_SECURITY_AUDIT.md G4). Key default Laravel adalah IP+path,
    // jadi 5 percobaan gagal/menit per IP sebelum 429 Too Many Requests.
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout.get'); // Fallback untuk expired session

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Survei Penggunaan ESARAKU
    Route::get('/survei', [\App\Http\Controllers\SurveiController::class, 'show'])->name('survei.show');
    Route::post('/survei', [\App\Http\Controllers\SurveiController::class, 'store'])->name('survei.store');

    // Profile & Settings
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/settings', function() { return view('settings.index'); })->name('settings.index');

    // ========================================================================
    // SKP & KINERJA HARIAN Routes (untuk ASN & ATASAN)
    // Middleware 'non_admin': Semua role KECUALI ADMIN bisa akses
    // ========================================================================
    Route::prefix('asn')->name('asn.')->middleware('non_admin')->group(function () {
        // Tutorial ESARAKU — tidak perlu SKP approved, cukup auth + non_admin
        Route::get('/tutorial', [TutorialController::class, 'index'])->name('tutorial.index');

        // ================================================================
        // SKP Tahunan - TIDAK perlu SKP approved (ASN buat & ajukan disini)
        // ================================================================
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

        // ================================================================
        // KINERJA HARIAN - Halaman view BOLEH diakses semua ASN
        // Hanya form input Kinerja Harian yang butuh SKP approved
        // TLA selalu boleh diakses tanpa SKP approved
        // ================================================================
        Route::get('/harian', [HarianController::class, 'index'])->name('harian.index');
        Route::get('/harian/pilih', [HarianController::class, 'pilih'])->name('harian.pilih');

        // Cuti / Dinas Luar — tidak perlu SKP approved
        Route::get('/cuti/create', [CutiController::class, 'create'])->name('cuti.create');
        Route::post('/cuti/store', [CutiController::class, 'store'])->name('cuti.store');
        Route::get('/cuti', [CutiController::class, 'index'])->name('cuti.index');
        Route::delete('/cuti/{id}', [CutiController::class, 'destroy'])->name('cuti.destroy');

        // TLA - TIDAK perlu SKP approved (Create, Edit, Update, Delete, Cetak)
        Route::get('/harian/form-tla', [HarianController::class, 'formTla'])->name('harian.form-tla');
        Route::post('/harian/store-tla', [HarianController::class, 'storeTla'])->name('harian.store-tla');
        Route::get('/harian/edit-tla/{id}', [HarianController::class, 'editTla'])->name('harian.edit-tla');
        Route::put('/harian/update-tla/{id}', [HarianController::class, 'updateTla'])->name('harian.update-tla');
        Route::delete('/harian/destroy-tla/{id}', [HarianController::class, 'destroyTla'])->name('harian.destroy-tla');
        Route::get('/harian/cetak-tla/{id}', [HarianController::class, 'cetakTugasAtasan'])->name('harian.cetak-tla');

        // ================================================================
        // FITUR YANG BUTUH SKP APPROVED
        // Form Kinerja Harian, RHK Bulanan, Laporan Bulanan
        // ================================================================
        Route::middleware('skp.approved')->group(function () {
            // Kinerja Harian (form input & CRUD - butuh SKP approved)
            Route::get('/harian/form-kinerja', [HarianController::class, 'formKinerja'])->name('harian.form-kinerja');
            Route::post('/harian/store-kinerja', [HarianController::class, 'storeKinerja'])->name('harian.store-kinerja');
            Route::get('/harian/edit/{id}', [HarianController::class, 'edit'])->name('harian.edit');
            Route::put('/harian/update/{id}', [HarianController::class, 'update'])->name('harian.update');
            Route::delete('/harian/destroy/{id}', [HarianController::class, 'destroy'])->name('harian.destroy');
            Route::get('/harian/cetak/{id}', [HarianController::class, 'cetakKinerjaHarian'])->name('harian.cetak');

            // Rencana Kerja (RHK Bulanan)
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

            // Rekap Absensi PUSAKA
            Route::post('/laporan/rekap-absensi', [BulananController::class, 'storeRekapAbsensi'])->name('laporan.rekap-absensi.store');
            Route::post('/laporan/rekap-absensi/{id}/revisi', [BulananController::class, 'revisiRekapAbsensi'])->name('laporan.rekap-absensi.revisi');
        });
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

        // Rekap Absensi PUSAKA
        Route::post('/approval/rekap-absensi/{id}/approve', [ApprovalController::class, 'approveRekap'])->name('approval.rekap-absensi.approve');
        Route::post('/approval/rekap-absensi/{id}/reject',  [ApprovalController::class, 'rejectRekap'])->name('approval.rekap-absensi.reject');

        // Laporan Bulanan Kinerja
        Route::post('/approval/laporan-bulanan/{id}/approve', [ApprovalController::class, 'approveLaporan'])->name('approval.laporan-bulanan.approve');
        Route::post('/approval/laporan-bulanan/{id}/tolak',   [ApprovalController::class, 'tolakLaporan'])->name('approval.laporan-bulanan.tolak');
        Route::get('/approval/laporan-bulanan/{id}/pdf',      [ApprovalController::class, 'downloadPdfBawahan'])->name('approval.laporan-bulanan.pdf');

        // Harian Bawahan (TAHAP 5.1 - Monitoring Dashboard)
        Route::get('/harian-bawahan', [HarianBawahanController::class, 'index'])->name('harian-bawahan.index');
        Route::get('/harian-bawahan/detail/{user_id}', [HarianBawahanController::class, 'detail'])->name('harian-bawahan.detail');
        Route::get('/harian-bawahan/cetak-lkh/{user_id}/{tanggal}', [HarianBawahanController::class, 'cetakLKH'])->name('harian-bawahan.cetak-lkh');
        Route::get('/harian-bawahan/cetak-tla/{user_id}/{tanggal}', [HarianBawahanController::class, 'cetakTLA'])->name('harian-bawahan.cetak-tla');
        Route::post('/harian-bawahan/verifikasi/{progresId}', [HarianBawahanController::class, 'verifikasi'])->name('harian-bawahan.verifikasi');
        Route::get('/monitoring-verifikasi', [HarianBawahanController::class, 'monitoringVerifikasi'])->name('monitoring-verifikasi');
        Route::get('/monitoring-verifikasi/export', [HarianBawahanController::class, 'exportVerifikasi'])->name('monitoring-verifikasi.export');

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

        // Pemetaan Indikator per Unit Kerja
        Route::get('indikator-unit-kerja', [IndikatorUnitKerjaController::class, 'index'])->name('indikator-unit-kerja.index');
        Route::post('indikator-unit-kerja/{unitKerja}/update', [IndikatorUnitKerjaController::class, 'update'])->name('indikator-unit-kerja.update');

        // Unit Kerja (Resource)
        Route::resource('unit-kerja', UnitKerjaController::class);

        // Data Pegawai (Resource)
        Route::resource('pegawai', PegawaiController::class);

        // RHK Pimpinan (Resource)
        Route::resource('rhk-pimpinan', RhkPimpinanController::class);

        // Import ASN dari Excel
        Route::get('/import-asn',                   [ImportAsnController::class, 'index'])           ->name('import-asn.index');
        Route::get('/import-asn/template',          [ImportAsnController::class, 'downloadTemplate'])->name('import-asn.template');
        Route::post('/import-asn/preview',          [ImportAsnController::class, 'preview'])         ->name('import-asn.preview');
        Route::post('/import-asn/confirm',          [ImportAsnController::class, 'confirm'])         ->name('import-asn.confirm');

        // Rotasi Jabatan
        Route::get('/rotasi-jabatan', [RotasiJabatanController::class, 'index'])->name('rotasi-jabatan.index');
        Route::get('/rotasi-jabatan/info-unit', [RotasiJabatanController::class, 'infoUnit'])->name('rotasi-jabatan.info-unit');
        Route::get('/rotasi-jabatan/bawahan-atasan', [RotasiJabatanController::class, 'bawahanAtasan'])->name('rotasi-jabatan.bawahan-atasan');
        Route::post('/rotasi-jabatan/ganti-kepala', [RotasiJabatanController::class, 'gantiKepala'])->name('rotasi-jabatan.ganti-kepala');
        Route::post('/rotasi-jabatan/pindah-staf', [RotasiJabatanController::class, 'pindahStaf'])->name('rotasi-jabatan.pindah-staf');

        // Koreksi Laporan Bulanan
        Route::get('/koreksi-laporan',               [\App\Http\Controllers\Admin\KoreksiLaporanController::class, 'index'])  ->name('koreksi-laporan.index');
        Route::post('/laporan-bulanan/{id}/koreksi', [\App\Http\Controllers\Admin\KoreksiLaporanController::class, 'koreksi'])->name('laporan-bulanan.koreksi');

        // Kalender Libur Khusus (GURU, PENYULUH, PENGHULU)
        Route::get('/kalender-libur-khusus',                      [KalenderLiburKhususController::class, 'index'])  ->name('kalender-libur-khusus.index');
        Route::get('/kalender-libur-khusus/create',               [KalenderLiburKhususController::class, 'create']) ->name('kalender-libur-khusus.create');
        Route::post('/kalender-libur-khusus',                     [KalenderLiburKhususController::class, 'store'])  ->name('kalender-libur-khusus.store');
        Route::get('/kalender-libur-khusus/{kalender}/edit',      [KalenderLiburKhususController::class, 'edit'])   ->name('kalender-libur-khusus.edit');
        Route::put('/kalender-libur-khusus/{kalender}',           [KalenderLiburKhususController::class, 'update']) ->name('kalender-libur-khusus.update');
        Route::delete('/kalender-libur-khusus/{kalender}',        [KalenderLiburKhususController::class, 'destroy'])->name('kalender-libur-khusus.destroy');
        Route::patch('/kalender-libur-khusus/{kalender}/toggle-status',
            [KalenderLiburKhususController::class, 'toggleStatus'])->name('kalender-libur-khusus.toggle-status');

        // Monitoring Survei ESARAKU
        Route::get('/survei',                    [\App\Http\Controllers\Admin\SurveiMonitoringController::class, 'index'])      ->name('survei.index');
        Route::get('/survei/export',             [\App\Http\Controllers\Admin\SurveiMonitoringController::class, 'export'])     ->name('survei.export');
        Route::get('/survei/export-saran',       [\App\Http\Controllers\Admin\SurveiMonitoringController::class, 'exportSaran']) ->name('survei.export-saran');
        Route::get('/survei/export-detail',      [\App\Http\Controllers\Admin\SurveiMonitoringController::class, 'exportDetail'])->name('survei.export-detail');
        Route::post('/survei/{id}/aktifkan',     [\App\Http\Controllers\Admin\SurveiMonitoringController::class, 'aktifkan'])   ->name('survei.aktifkan');
        Route::post('/survei/{id}/tutup',        [\App\Http\Controllers\Admin\SurveiMonitoringController::class, 'tutup'])      ->name('survei.tutup');

        // Dashboard Admin
        Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/dashboard/refresh', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'refresh'])->name('dashboard.refresh');
        Route::get('/dashboard/daily-report', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'dailyReport'])->name('dashboard.daily-report');
    });
});
