<?php
/**
 * EMERGENCY CACHE CLEAR SCRIPT
 *
 * Upload file ini ke folder /public_html/gaspul_api/public/
 * Lalu akses: https://your-domain.com/clear-cache.php
 *
 * HAPUS FILE INI SETELAH SELESAI DIGUNAKAN (untuk keamanan)
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "<h1>Laravel Cache Clear</h1>";
echo "<pre>";

// Clear config cache
echo "Clearing config cache...\n";
$kernel->call('config:clear');
echo "✓ Config cache cleared\n\n";

// Clear route cache
echo "Clearing route cache...\n";
$kernel->call('route:clear');
echo "✓ Route cache cleared\n\n";

// Clear view cache
echo "Clearing view cache...\n";
$kernel->call('view:clear');
echo "✓ View cache cleared\n\n";

// Clear application cache
echo "Clearing application cache...\n";
$kernel->call('cache:clear');
echo "✓ Application cache cleared\n\n";

echo "</pre>";
echo "<h2 style='color: green;'>✓ All caches cleared successfully!</h2>";
echo "<p><strong>IMPORTANT:</strong> Delete this file (clear-cache.php) now for security!</p>";
