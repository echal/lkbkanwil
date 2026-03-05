<?php
/**
 * Artisan Clear + Verify Script
 * Upload ke: public_html/.../public/artisan-clear.php
 * Akses via: https://domain.com/artisan-clear.php
 * HAPUS setelah selesai!
 */

// Basic security: hanya bisa diakses dari IP tertentu (opsional)
// $allowed = ['your.ip.address'];
// if (!in_array($_SERVER['REMOTE_ADDR'], $allowed)) die('Forbidden');

define('LARAVEL_START', microtime(true));

// ── OPcache clear (PERTAMA sebelum autoload) ────────────────────────
if (function_exists('opcache_reset')) {
    opcache_reset();
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo '<!DOCTYPE html><html><head><meta charset="utf-8">
<title>Clear Cache</title>
<style>
body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4}
h1{color:#4ec9b0} h2{color:#dcdcaa} .ok{color:#6a9955} .err{color:#f44747}
pre{background:#252526;padding:15px;border-radius:6px;border:1px solid #3c3c3c}
.section{margin:20px 0;padding:15px;background:#252526;border-radius:8px}
</style></head><body>';

echo '<h1>Laravel Artisan Clear</h1>';

$results = [];

// ── 1. Clear semua cache ────────────────────────────────────────────
$commands = [
    'config:clear'  => 'Config cache',
    'route:clear'   => 'Route cache',
    'view:clear'    => 'View cache',
    'cache:clear'   => 'Application cache',
];

echo '<div class="section"><h2>1. Clear Cache</h2><pre>';
foreach ($commands as $cmd => $label) {
    try {
        $exitCode = $kernel->call($cmd);
        $status = $exitCode === 0 ? '<span class="ok">✓ OK</span>' : '<span class="err">✗ Failed (exit:'.$exitCode.')</span>';
        echo "$label ($cmd): $status\n";
        $results[$cmd] = $exitCode === 0;
    } catch (\Exception $e) {
        echo "$label ($cmd): <span class=\"err\">✗ Error: " . htmlspecialchars($e->getMessage()) . "</span>\n";
        $results[$cmd] = false;
    }
}
echo '</pre></div>';

// ── 2. Verify method exists di DashboardService ─────────────────────
echo '<div class="section"><h2>2. Verify DashboardService</h2><pre>';
$serviceFile = __DIR__ . '/../app/Services/DashboardService.php';
if (file_exists($serviceFile)) {
    $content = file_get_contents($serviceFile);
    $checks = [
        'getDailyReportingData' => 'Method getDailyReportingData()',
        'use Carbon\Carbon'     => 'Carbon import',
        "'daily'"               => "Key 'daily' di getAllData()",
        'admin_daily_report_'   => 'Cache key daily report',
    ];
    foreach ($checks as $needle => $label) {
        if (strpos($content, $needle) !== false) {
            echo "$label: <span class=\"ok\">✓ Ditemukan</span>\n";
        } else {
            echo "$label: <span class=\"err\">✗ TIDAK DITEMUKAN - File belum terupdate!</span>\n";
        }
    }
    $lineCount = substr_count($content, "\n");
    echo "Jumlah baris: <span class=\"ok\">$lineCount baris</span> (seharusnya ~407)\n";
} else {
    echo '<span class="err">✗ File DashboardService.php tidak ditemukan!</span>';
}
echo '</pre></div>';

// ── 3. Verify AdminDashboardController ─────────────────────────────
echo '<div class="section"><h2>3. Verify AdminDashboardController</h2><pre>';
$ctrlFile = __DIR__ . '/../app/Http/Controllers/Admin/AdminDashboardController.php';
if (file_exists($ctrlFile)) {
    $content = file_get_contents($ctrlFile);
    $checks = [
        'dailyReport'           => 'Method dailyReport()',
        'use Illuminate\Http\Request' => 'Request import',
        'getDailyReportingData' => 'Call getDailyReportingData()',
    ];
    foreach ($checks as $needle => $label) {
        if (strpos($content, $needle) !== false) {
            echo "$label: <span class=\"ok\">✓ Ditemukan</span>\n";
        } else {
            echo "$label: <span class=\"err\">✗ TIDAK DITEMUKAN - File belum terupdate!</span>\n";
        }
    }
} else {
    echo '<span class="err">✗ File tidak ditemukan!</span>';
}
echo '</pre></div>';

// ── 4. Verify routes/web.php ────────────────────────────────────────
echo '<div class="section"><h2>4. Verify routes/web.php</h2><pre>';
$routeFile = __DIR__ . '/../routes/web.php';
if (file_exists($routeFile)) {
    $content = file_get_contents($routeFile);
    if (strpos($content, 'daily-report') !== false) {
        echo "Route daily-report: <span class=\"ok\">✓ Ditemukan</span>\n";
    } else {
        echo "Route daily-report: <span class=\"err\">✗ TIDAK DITEMUKAN - routes/web.php belum terupdate!</span>\n";
    }
} else {
    echo '<span class="err">✗ File tidak ditemukan!</span>';
}
echo '</pre></div>';

// ── 5. Verify dashboard.blade.php ──────────────────────────────────
echo '<div class="section"><h2>5. Verify dashboard.blade.php</h2><pre>';
$bladeFile = __DIR__ . '/../resources/views/admin/dashboard.blade.php';
if (file_exists($bladeFile)) {
    $content = file_get_contents($bladeFile);
    $checks = [
        'Daily Reporting Control' => 'Section H header',
        'chartDailyUnit'          => 'Canvas chart daily',
        'dailyReporting()'        => 'Alpine component',
        "daily['chart']"          => 'Blade $daily[chart]',
    ];
    foreach ($checks as $needle => $label) {
        if (strpos($content, $needle) !== false) {
            echo "$label: <span class=\"ok\">✓ Ditemukan</span>\n";
        } else {
            echo "$label: <span class=\"err\">✗ TIDAK DITEMUKAN - Blade belum terupdate!</span>\n";
        }
    }
    $lineCount = substr_count($content, "\n");
    echo "Jumlah baris: <span class=\"ok\">$lineCount baris</span> (seharusnya ~720+)\n";
} else {
    echo '<span class="err">✗ File tidak ditemukan!</span>';
}
echo '</pre></div>';

// ── Summary ─────────────────────────────────────────────────────────
echo '<div class="section"><h2>Summary</h2>';
$allOk = !in_array(false, $results, true);
if ($allOk) {
    echo '<p class="ok" style="font-size:1.2em">✓ Semua cache berhasil di-clear. Buka dashboard dan hard refresh (Ctrl+Shift+R).</p>';
} else {
    echo '<p class="err" style="font-size:1.2em">✗ Ada yang gagal. Cek output di atas.</p>';
}
echo '</div>';

// ── 6. Cek Laravel Error Log (50 baris terakhir) ───────────────────
echo '<div class="section"><h2>6. Laravel Error Log (terbaru)</h2><pre>';
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $last50 = array_slice($lines, -50);
    $logContent = implode('', $last50);
    // Highlight error lines
    $logContent = htmlspecialchars($logContent);
    $logContent = preg_replace('/(ERROR|CRITICAL|Exception|Fatal|Error:)/', '<span class="err">$1</span>', $logContent);
    echo $logContent ?: '(log kosong)';
} else {
    echo '<span class="err">File log tidak ditemukan: ' . htmlspecialchars($logFile) . '</span>';
}
echo '</pre></div>';

// ── 7. Cek PHP Version & Extensions ────────────────────────────────
echo '<div class="section"><h2>7. Environment Info</h2><pre>';
echo 'PHP Version: <span class="ok">' . PHP_VERSION . '</span>' . "\n";
echo 'Laravel Path: ' . htmlspecialchars(__DIR__ . '/..') . "\n";
echo 'Storage writable: ' . (is_writable(__DIR__.'/../storage') ? '<span class="ok">✓ Ya</span>' : '<span class="err">✗ Tidak</span>') . "\n";
echo 'Bootstrap/cache writable: ' . (is_writable(__DIR__.'/../bootstrap/cache') ? '<span class="ok">✓ Ya</span>' : '<span class="err">✗ Tidak</span>') . "\n";

// Cek .env (baca manual, bukan parse_ini_file karena bisa crash di beberapa nilai)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $envRaw = file_get_contents($envFile);
    $envVars = [];
    foreach (explode("\n", $envRaw) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $envVars[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
    echo 'APP_ENV: <span class="ok">' . htmlspecialchars($envVars['APP_ENV'] ?? '?') . '</span>' . "\n";
    echo 'APP_DEBUG: ' . (($envVars['APP_DEBUG'] ?? '') === 'true' ? '<span class="ok">true</span>' : '<span class="err">false (error tersembunyi!)</span>') . "\n";
    echo 'DB_DATABASE: <span class="ok">' . htmlspecialchars($envVars['DB_DATABASE'] ?? '?') . '</span>' . "\n";
} else {
    echo '<span class="err">File .env tidak ditemukan!</span>' . "\n";
}
echo '</pre></div>';

echo '<p style="color:#858585;font-size:0.8em">⚠ HAPUS FILE INI SETELAH SELESAI untuk keamanan!</p>';
echo '</body></html>';
