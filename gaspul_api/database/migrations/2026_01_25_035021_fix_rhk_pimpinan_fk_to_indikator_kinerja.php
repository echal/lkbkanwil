<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix RHK Pimpinan FK to Indikator Kinerja
 *
 * Change FK from sasaran_kegiatan to indikator_kinerja
 * Rename column to indikator_kinerja_id for clarity
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rhk_pimpinan', function (Blueprint $table) {
            // Drop existing FK constraint
            $table->dropForeign(['sasaran_kegiatan_id']);

            // Rename column
            $table->renameColumn('sasaran_kegiatan_id', 'indikator_kinerja_id');
        });

        // Add new FK constraint (separate statement after rename)
        Schema::table('rhk_pimpinan', function (Blueprint $table) {
            $table->foreign('indikator_kinerja_id')
                ->references('id')
                ->on('indikator_kinerja')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('rhk_pimpinan', function (Blueprint $table) {
            // Drop new FK constraint
            $table->dropForeign(['indikator_kinerja_id']);

            // Rename back
            $table->renameColumn('indikator_kinerja_id', 'sasaran_kegiatan_id');
        });

        // Add back old FK constraint
        Schema::table('rhk_pimpinan', function (Blueprint $table) {
            $table->foreign('sasaran_kegiatan_id')
                ->references('id')
                ->on('sasaran_kegiatan')
                ->onDelete('cascade');
        });
    }
};
