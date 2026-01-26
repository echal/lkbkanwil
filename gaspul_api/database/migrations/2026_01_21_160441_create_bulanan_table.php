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
        Schema::create('bulanan', function (Blueprint $table) {
            $table->id();

            // Foreign key to Rencana Kerja ASN (SKP Triwulan)
            $table->foreignId('rencana_kerja_asn_id')
                ->constrained('rencana_kerja_asn')
                ->onDelete('cascade')
                ->comment('ID Rencana Kerja Triwulan');

            // Bulan (1-12)
            $table->tinyInteger('bulan')
                ->comment('Bulan (1-12)');

            // Tahun (extracted from parent SKP for easy querying)
            $table->year('tahun')
                ->comment('Tahun dari SKP parent');

            // Target dan Rencana Kerja Bulanan (filled by ASN after auto-generation)
            $table->integer('target_bulanan')
                ->nullable()
                ->default(0)
                ->comment('Target bulanan yang diisi ASN');

            $table->text('rencana_kerja_bulanan')
                ->nullable()
                ->comment('Rencana kerja bulanan yang diisi ASN');

            // Realisasi bulanan (calculated from Harian)
            $table->integer('realisasi_bulanan')
                ->default(0)
                ->comment('Realisasi bulanan (sum dari Harian)');

            // Status
            $table->enum('status', ['AKTIF', 'SELESAI'])
                ->default('AKTIF')
                ->comment('Status bulanan');

            $table->timestamps();

            // Unique constraint: one record per SKP per month
            $table->unique(['rencana_kerja_asn_id', 'bulan'], 'unique_bulanan_per_skp');

            // Index for faster queries
            $table->index(['tahun', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulanan');
    }
};
