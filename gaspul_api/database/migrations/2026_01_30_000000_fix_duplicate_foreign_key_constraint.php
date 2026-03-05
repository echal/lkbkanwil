<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FIX: Duplicate Foreign Key Constraint Error
 *
 * Error: errno 121 "Duplicate key on write or update"
 * Penyebab: Foreign key fk_skp_tahunan_user sudah ada dari migrasi sebelumnya
 *
 * Solusi: Drop dulu jika ada, baru buat ulang — via raw DB::statement
 * agar try-catch benar-benar menangkap error PDO level.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('skp_tahunan')) {
            return;
        }

        // Ambil daftar foreign key yang ada di tabel skp_tahunan
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'skp_tahunan'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        $existingFkNames = array_column($foreignKeys, 'CONSTRAINT_NAME');

        // Drop semua FK yang ada (via raw statement agar tidak ada PDO exception tak tertangkap)
        foreach ($existingFkNames as $fkName) {
            try {
                DB::statement("ALTER TABLE skp_tahunan DROP FOREIGN KEY `{$fkName}`");
            } catch (\Throwable $e) {
                // Lanjutkan jika sudah tidak ada
            }
        }

        // Buat ulang FK hanya jika kolom ada dan FK belum ada
        if (Schema::hasColumn('skp_tahunan', 'user_id')) {
            $this->addForeignKeyIfNotExists(
                'skp_tahunan',
                'fk_skp_tahunan_user',
                "ALTER TABLE skp_tahunan
                 ADD CONSTRAINT `fk_skp_tahunan_user`
                 FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE"
            );
        }

        if (Schema::hasColumn('skp_tahunan', 'approved_by')) {
            $this->addForeignKeyIfNotExists(
                'skp_tahunan',
                'fk_skp_tahunan_approved_by',
                "ALTER TABLE skp_tahunan
                 ADD CONSTRAINT `fk_skp_tahunan_approved_by`
                 FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL"
            );
        }
    }

    public function down(): void
    {
        // Tidak perlu rollback — FK tetap ada
    }

    /**
     * Tambah foreign key hanya jika belum ada.
     */
    private function addForeignKeyIfNotExists(string $table, string $fkName, string $sql): void
    {
        $exists = DB::select("
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA   = DATABASE()
              AND TABLE_NAME     = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            LIMIT 1
        ", [$table, $fkName]);

        if (! empty($exists)) {
            return; // sudah ada, skip
        }

        try {
            DB::statement($sql);
        } catch (\Throwable $e) {
            // Log atau abaikan jika gagal
        }
    }
};
