<?php
/**
 * DEBUG DASHBOARD - Tampilkan error detail dari admin dashboard
 * Upload ke: public/debug-dashboard.php
 * Akses: https://domain.com/debug-dashboard.php
 * HAPUS setelah selesai!
 */

define('LARAVEL_START', microtime(true));

if (function_exists('opcache_reset')) {
    opcache_reset();
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo '<!DOCTYPE html><html><head><meta charset="utf-8">
<title>Debug Dashboard</title>
<style>
body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4}
h1{color:#4ec9b0} h2{color:#dcdcaa} .ok{color:#6a9955} .err{color:#f44747}
pre{background:#252526;padding:15px;border-radius:6px;border:1px solid #3c3c3c;white-space:pre-wrap;word-break:break-all}
.section{margin:20px 0;padding:15px;background:#252526;border-radius:8px}
</style></head><body>';

echo '<h1>Debug Admin Dashboard</h1>';

// ── 1. Cek semua file yang dibutuhkan ──────────────────────────────
echo '<div class="section"><h2>1. File Check</h2><pre>';
$files = [
    'bootstrap/app.php'                                          => __DIR__.'/../bootstrap/app.php',
    'app/Services/DashboardService.php'                          => __DIR__.'/../app/Services/DashboardService.php',
    'app/Services/SkpAccessService.php'                         => __DIR__.'/../app/Services/SkpAccessService.php',
    'app/Http/Controllers/Admin/AdminDashboardController.php'    => __DIR__.'/../app/Http/Controllers/Admin/AdminDashboardController.php',
    'app/Http/Middleware/NonAdminMiddleware.php'                  => __DIR__.'/../app/Http/Middleware/NonAdminMiddleware.php',
    'app/Http/Middleware/EnsureSkpApproved.php'                  => __DIR__.'/../app/Http/Middleware/EnsureSkpApproved.php',
    'app/Http/Middleware/Authenticate.php'                       => __DIR__.'/../app/Http/Middleware/Authenticate.php',
    'resources/views/admin/dashboard.blade.php'                  => __DIR__.'/../resources/views/admin/dashboard.blade.php',
    'routes/web.php'                                             => __DIR__.'/../routes/web.php',
];

foreach ($files as $label => $path) {
    if (file_exists($path)) {
        $lines = substr_count(file_get_contents($path), "\n");
        echo "<span class=\"ok\">✓</span> $label <span style=\"color:#858585\">($lines baris)</span>\n";
    } else {
        echo "<span class=\"err\">✗ TIDAK ADA: $label</span>\n";
    }
}
echo '</pre></div>';

// ── 2. Cek routes/web.php berisi dashboard routes ──────────────────
echo '<div class="section"><h2>2. Route Check</h2><pre>';
$webPhp = file_get_contents(__DIR__.'/../routes/web.php');
$routeChecks = [
    'admin.dashboard.index'  => 'admin.dashboard.index route name',
    'dashboard.daily-report' => 'dashboard.daily-report route',
    'AdminDashboardController' => 'AdminDashboardController import/usage',
    'DashboardService'       => 'DashboardService (in routes or controller)',
];
foreach ($routeChecks as $needle => $label) {
    if (strpos($webPhp, $needle) !== false) {
        echo "<span class=\"ok\">✓</span> $label\n";
    } else {
        echo "<span class=\"err\">✗ TIDAK ADA: $label</span>\n";
    }
}
echo '</pre></div>';

// ── 3. Cek bootstrap/app.php registrasi middleware ─────────────────
echo '<div class="section"><h2>3. Middleware Registration Check</h2><pre>';
$bootstrapContent = file_get_contents(__DIR__.'/../bootstrap/app.php');
$mwChecks = [
    'non_admin'          => "Alias 'non_admin'",
    'NonAdminMiddleware' => 'NonAdminMiddleware class',
    'skp.approved'       => "Alias 'skp.approved'",
    'EnsureSkpApproved'  => 'EnsureSkpApproved class',
];
foreach ($mwChecks as $needle => $label) {
    if (strpos($bootstrapContent, $needle) !== false) {
        echo "<span class=\"ok\">✓</span> $label\n";
    } else {
        echo "<span class=\"err\">✗ TIDAK ADA: $label</span>\n";
    }
}
echo '</pre></div>';

// ── 4. Cek DashboardService punya method getDailyReportingData ─────
echo '<div class="section"><h2>4. DashboardService Method Check</h2><pre>';
$svcContent = file_get_contents(__DIR__.'/../app/Services/DashboardService.php');
$svcChecks = [
    'getDailyReportingData' => 'Method getDailyReportingData()',
    "use Carbon\\Carbon"    => 'Carbon import',
    "'daily'"               => "Key 'daily' di getAllData()",
    'admin_daily_report_'   => 'Cache key daily report',
    'progres_harian'        => 'Query progres_harian table',
];
foreach ($svcChecks as $needle => $label) {
    if (strpos($svcContent, $needle) !== false) {
        echo "<span class=\"ok\">✓</span> $label\n";
    } else {
        echo "<span class=\"err\">✗ TIDAK ADA: $label</span>\n";
    }
}
$lineCount = substr_count($svcContent, "\n");
echo "Jumlah baris: <span class=\"ok\">$lineCount</span> (seharusnya ~408)\n";
echo '</pre></div>';

// ── 5. Try instantiate DashboardService dan call getAllData ─────────
echo '<div class="section"><h2>5. DashboardService Live Test</h2><pre>';
try {
    $app2 = require_once __DIR__.'/../bootstrap/app.php';
    $app2->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

    $service = new \App\Services\DashboardService();
    echo "<span class=\"ok\">✓</span> DashboardService berhasil dibuat\n";

    // Coba panggil getDailyReportingData saja (yang paling baru)
    $daily = $service->getDailyReportingData();
    echo "<span class=\"ok\">✓</span> getDailyReportingData() berhasil\n";
    echo "  - tanggal: " . ($daily['tanggal'] ?? '?') . "\n";
    echo "  - total_asn: " . ($daily['total_asn'] ?? '?') . "\n";
    echo "  - sudah_isi: " . ($daily['sudah_isi'] ?? '?') . "\n";
    echo "  - keys: " . implode(', ', array_keys($daily)) . "\n";

} catch (\Throwable $e) {
    echo "<span class=\"err\">✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</span>\n\n";
    echo "File: " . htmlspecialchars($e->getFile()) . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . htmlspecialchars($e->getTraceAsString());
}
echo '</pre></div>';

// ── 6. Error log terbaru (20 baris) ───────────────────────────────
echo '<div class="section"><h2>6. Error Log (20 baris terakhir)</h2><pre>';
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $last = array_slice($lines, -20);
    $logContent = htmlspecialchars(implode('', $last));
    $logContent = preg_replace('/(ERROR|CRITICAL|Exception|Fatal|Error:)/', '<span class="err">$1</span>', $logContent);
    echo $logContent ?: '(log kosong)';
} else {
    echo '<span class="err">File log tidak ditemukan</span>';
}
echo '</pre></div>';

echo '<p style="color:#858585;font-size:0.8em">⚠ HAPUS FILE INI SETELAH SELESAI!</p>';
echo '</body></html>';
