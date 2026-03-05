<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Langkah 1: Perluas enum agar mencakup nilai lama DAN baru secara bersamaan
        // (sehingga UPDATE tidak akan truncate data)
        DB::statement("ALTER TABLE rekap_absensi_pusaka
            MODIFY COLUMN status ENUM(
                'pending',
                'valid',
                'ditolak',
                'pending_kabid',
                'pending_kakanwil',
                'approved',
                'rejected_kabid',
                'rejected_kakanwil'
            ) NOT NULL DEFAULT 'pending'");

        // Langkah 2: Migrasi data lama ke nilai enum baru
        DB::statement("UPDATE rekap_absensi_pusaka SET status = 'approved'       WHERE status = 'valid'");
        DB::statement("UPDATE rekap_absensi_pusaka SET status = 'rejected_kabid' WHERE status = 'ditolak'");
        DB::statement("UPDATE rekap_absensi_pusaka SET status = 'pending_kabid'  WHERE status = 'pending'");

        // Langkah 3: Hapus nilai lama, sisakan hanya nilai baru
        DB::statement("ALTER TABLE rekap_absensi_pusaka
            MODIFY COLUMN status ENUM(
                'pending_kabid',
                'pending_kakanwil',
                'approved',
                'rejected_kabid',
                'rejected_kakanwil'
            ) NOT NULL DEFAULT 'pending_kabid'");
    }

    public function down(): void
    {
        // Langkah 1: Perluas enum agar mencakup nilai baru DAN lama
        DB::statement("ALTER TABLE rekap_absensi_pusaka
            MODIFY COLUMN status ENUM(
                'pending',
                'valid',
                'ditolak',
                'pending_kabid',
                'pending_kakanwil',
                'approved',
                'rejected_kabid',
                'rejected_kakanwil'
            ) NOT NULL DEFAULT 'pending_kabid'");

        // Langkah 2: Migrasi data baru ke nilai lama
        DB::statement("UPDATE rekap_absensi_pusaka SET status = 'valid'   WHERE status = 'approved'");
        DB::statement("UPDATE rekap_absensi_pusaka SET status = 'ditolak' WHERE status IN ('rejected_kabid', 'rejected_kakanwil')");
        DB::statement("UPDATE rekap_absensi_pusaka SET status = 'pending' WHERE status IN ('pending_kabid', 'pending_kakanwil')");

        // Langkah 3: Kembalikan ke enum lama saja
        DB::statement("ALTER TABLE rekap_absensi_pusaka
            MODIFY COLUMN status ENUM('pending', 'valid', 'ditolak')
            NOT NULL DEFAULT 'pending'");
    }
};
