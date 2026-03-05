<?php
/**
 * Check DB columns & migrations
 * HAPUS setelah selesai!
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo '<!DOCTYPE html><html><head><meta charset="utf-8">
<title>DB Check</title>
<style>
body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4}
h2{color:#dcdcaa} .ok{color:#6a9955} .err{color:#f44747}
pre{background:#252526;padding:15px;border-radius:6px}
</style></head><body>';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 1. Cek kolom atasan_id di tabel users
echo '<h2>1. Kolom atasan_id di tabel users</h2><pre>';
if (Schema::hasColumn('users', 'atasan_id')) {
    echo '<span class="ok">✓ Kolom atasan_id SUDAH ADA — migrasi tidak perlu dijalankan lagi</span>';
} else {
    echo '<span class="err">✗ Kolom atasan_id BELUM ADA — perlu jalankan: php artisan migrate</span>';
}
echo '</pre>';

// 2. Cek tabel migrations apakah migration sudah tercatat
echo '<h2>2. Status Migration</h2><pre>';
$ran = DB::table('migrations')
    ->where('migration', 'like', '%add_atasan_id_to_users_table%')
    ->first();
if ($ran) {
    echo '<span class="ok">✓ Migration sudah tercatat di tabel migrations (batch: '.$ran->batch.')</span>';
} else {
    echo '<span class="err">✗ Migration BELUM tercatat — perlu jalankan: php artisan migrate</span>';
}
echo '</pre>';

// 3. Tampilkan semua kolom tabel users
echo '<h2>3. Kolom tabel users saat ini</h2><pre>';
$columns = Schema::getColumnListing('users');
echo implode(', ', $columns);
echo '</pre>';

echo '<p style="color:#858585;font-size:0.8em">⚠ HAPUS FILE INI SETELAH SELESAI!</p>';
echo '</body></html>';
