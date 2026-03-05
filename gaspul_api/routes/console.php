<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Registrasi command custom
// (Laravel 10 dengan bootstrap/app.php — auto-discovery dari app/Console/Commands/ aktif)
// File di bawah ini juga terdaftar eksplisit sebagai referensi:
//  - App\Console\Commands\AuditUnitKerjaHierarchy  (php artisan audit:unit-kerja)
//  - App\Console\Commands\SnapshotDatabase          (php artisan snapshot:db)
