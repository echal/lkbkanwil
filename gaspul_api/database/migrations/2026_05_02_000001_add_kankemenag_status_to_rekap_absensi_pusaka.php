<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah nilai enum baru untuk level Kankemenag Kab
        DB::statement("ALTER TABLE rekap_absensi_pusaka
            MODIFY COLUMN status ENUM(
                'pending_kabid',
                'pending_kankemenag',
                'pending_kakanwil',
                'approved',
                'rejected_kabid',
                'rejected_kankemenag',
                'rejected_kakanwil'
            ) NOT NULL DEFAULT 'pending_kabid'");

        // Koreksi data lama: rekap dari ASN madrasah yang sudah disetujui Kepala Madrasah
        // (pending_kakanwil tanpa verified_by_kakanwil) harus melewati Kankemenag Kab dulu
        DB::statement("
            UPDATE rekap_absensi_pusaka r
            JOIN users u        ON r.user_id      = u.id
            JOIN users atasan   ON u.atasan_id    = atasan.id
            JOIN users kankemenag ON atasan.atasan_id = kankemenag.id
            SET r.status = 'pending_kankemenag'
            WHERE r.status = 'pending_kakanwil'
              AND r.verified_by_kakanwil IS NULL
              AND kankemenag.atasan_id = 293
        ");
    }

    public function down(): void
    {
        // Kembalikan rekap yang dikoreksi ke pending_kakanwil
        DB::statement("
            UPDATE rekap_absensi_pusaka r
            JOIN users u        ON r.user_id      = u.id
            JOIN users atasan   ON u.atasan_id    = atasan.id
            JOIN users kankemenag ON atasan.atasan_id = kankemenag.id
            SET r.status = 'pending_kakanwil'
            WHERE r.status = 'pending_kankemenag'
              AND r.verified_by_kakanwil IS NULL
              AND kankemenag.atasan_id = 293
        ");

        // Hapus nilai enum kankemenag
        DB::statement("ALTER TABLE rekap_absensi_pusaka
            MODIFY COLUMN status ENUM(
                'pending_kabid',
                'pending_kakanwil',
                'approved',
                'rejected_kabid',
                'rejected_kakanwil'
            ) NOT NULL DEFAULT 'pending_kabid'");
    }
};
