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
        Schema::create('skp_tahunan', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ASN yang membuat SKP Tahunan');

            $table->foreignId('sasaran_kegiatan_id')
                ->constrained('sasaran_kegiatan')
                ->onDelete('restrict')
                ->comment('Sasaran Kegiatan dari master Admin');

            $table->foreignId('indikator_kinerja_id')
                ->constrained('indikator_kinerja')
                ->onDelete('restrict')
                ->comment('Indikator Kinerja dari master Admin');

            // SKP Tahunan Fields
            $table->integer('tahun')->comment('Tahun SKP (misal: 2026)');
            $table->integer('target_tahunan')->comment('Target kinerja untuk 1 tahun penuh');
            $table->string('satuan', 50)->comment('Satuan target (%, Dokumen, Laporan, dll)');
            $table->text('rencana_aksi')->nullable()->comment('Rencana aksi tahunan');

            // Realisasi (dihitung dari agregasi Triwulan)
            $table->integer('realisasi_tahunan')->default(0)->comment('Realisasi dari agregasi Triwulan');

            // Status Workflow
            $table->enum('status', ['DRAFT', 'DIAJUKAN', 'DISETUJUI', 'DITOLAK'])
                ->default('DRAFT')
                ->comment('Status persetujuan SKP Tahunan');

            // Catatan Atasan
            $table->text('catatan_atasan')->nullable()->comment('Catatan dari Atasan jika Ditolak/Disetujui');

            // Approval Tracking
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('ID Atasan yang menyetujui');
            $table->timestamp('approved_at')->nullable()->comment('Waktu persetujuan');

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('tahun');
            $table->index('status');

            // Unique constraint: 1 ASN hanya bisa punya 1 SKP Tahunan per Sasaran per Tahun
            $table->unique(['user_id', 'sasaran_kegiatan_id', 'tahun'], 'unique_skp_tahunan_per_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skp_tahunan');
    }
};
