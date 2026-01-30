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
     * Menambahkan kolom unit_kerja_id ke tabel rhk_pimpinan
     * untuk membatasi akses RHK berdasarkan unit kerja
     */
    public function up(): void
    {
        Schema::table('rhk_pimpinan', function (Blueprint $table) {
            // Add unit_kerja_id column after indikator_kinerja_id
            $table->unsignedBigInteger('unit_kerja_id')->nullable()->after('indikator_kinerja_id');

            // Add foreign key constraint
            $table->foreign('unit_kerja_id')
                ->references('id')
                ->on('unit_kerja')
                ->onDelete('cascade');

            // Add index for better query performance
            $table->index('unit_kerja_id', 'idx_rhk_unit_kerja_id');
        });

        // Set default unit_kerja_id = 1 (Sekretariat) for existing data
        DB::table('rhk_pimpinan')
            ->whereNull('unit_kerja_id')
            ->update(['unit_kerja_id' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rhk_pimpinan', function (Blueprint $table) {
            // Drop foreign key and index
            $table->dropForeign(['unit_kerja_id']);
            $table->dropIndex('idx_rhk_unit_kerja_id');

            // Drop column
            $table->dropColumn('unit_kerja_id');
        });
    }
};
