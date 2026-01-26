<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rencana_kerja_asn', function (Blueprint $table) {
            // Add reference to SKP Tahunan
            $table->foreignId('skp_tahunan_id')
                ->nullable()
                ->after('user_id')
                ->constrained('skp_tahunan')
                ->onDelete('cascade')
                ->comment('Referensi ke SKP Tahunan (WAJIB untuk alur baru)');

            // Make sasaran_kegiatan_id and indikator_kinerja_id nullable
            // Karena akan diambil dari SKP Tahunan
            $table->foreignId('sasaran_kegiatan_id')->nullable()->change();
            $table->foreignId('indikator_kinerja_id')->nullable()->change();

            // Make tahun nullable karena akan mengikuti SKP Tahunan
            $table->integer('tahun')->nullable()->change();

            // Add index
            $table->index('skp_tahunan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rencana_kerja_asn', function (Blueprint $table) {
            $table->dropForeign(['skp_tahunan_id']);
            $table->dropColumn('skp_tahunan_id');

            // Revert nullable changes
            $table->foreignId('sasaran_kegiatan_id')->nullable(false)->change();
            $table->foreignId('indikator_kinerja_id')->nullable(false)->change();
            $table->integer('tahun')->nullable(false)->change();
        });
    }
};
