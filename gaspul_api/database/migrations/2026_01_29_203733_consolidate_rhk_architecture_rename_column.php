<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * KONSOLIDASI ARSITEKTUR RHK:
     * - Tambah kolom unit_kerja_id di indikator_kinerja untuk filter per unit
     * - Rename index di skp_tahunan_detail untuk konsistensi penamaan
     *
     * NOTE: Kolom skp_tahunan_detail.indikator_kinerja_id sudah ada (sudah di-rename secara manual)
     */
    public function up(): void
    {
        // Step 1: Rename index di skp_tahunan_detail untuk konsistensi
        if (Schema::hasColumn('skp_tahunan_detail', 'indikator_kinerja_id')) {
            Schema::table('skp_tahunan_detail', function (Blueprint $table) {
                // Drop old index name (jika ada)
                try {
                    $table->dropIndex('idx_rhk_pimpinan_id');
                } catch (\Exception $e) {
                    // Ignore if not exists
                }

                // Create new index with correct name
                if (!DB::select("SHOW INDEX FROM skp_tahunan_detail WHERE Key_name = 'idx_indikator_kinerja_id'")) {
                    $table->index('indikator_kinerja_id', 'idx_indikator_kinerja_id');
                }
            });
        }

        // Step 2: Tambah kolom unit_kerja_id di indikator_kinerja (jika belum ada)
        if (!Schema::hasColumn('indikator_kinerja', 'unit_kerja_id')) {
            Schema::table('indikator_kinerja', function (Blueprint $table) {
                $table->foreignId('unit_kerja_id')->nullable()->after('sasaran_kegiatan_id')
                      ->comment('Unit Kerja yang bisa menggunakan indikator ini (NULL = semua unit)');

                $table->foreign('unit_kerja_id', 'fk_indikator_kinerja_unit_kerja')
                      ->references('id')
                      ->on('unit_kerja')
                      ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse Step 2: Drop unit_kerja_id from indikator_kinerja
        if (Schema::hasColumn('indikator_kinerja', 'unit_kerja_id')) {
            Schema::table('indikator_kinerja', function (Blueprint $table) {
                $table->dropForeign('fk_indikator_kinerja_unit_kerja');
                $table->dropColumn('unit_kerja_id');
            });
        }

        // Reverse Step 1: Restore old index name
        Schema::table('skp_tahunan_detail', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_indikator_kinerja_id');
            } catch (\Exception $e) {
                // Ignore if not exists
            }

            $table->index('indikator_kinerja_id', 'idx_rhk_pimpinan_id');
        });
    }
};
