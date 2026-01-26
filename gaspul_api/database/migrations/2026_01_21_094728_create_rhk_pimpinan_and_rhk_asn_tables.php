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
        // Create rhk_pimpinan table
        Schema::create('rhk_pimpinan', function (Blueprint $table) {
            $table->id();
            $table->year('tahun');
            $table->enum('triwulan', ['TW1', 'TW2', 'TW3', 'TW4']);
            $table->text('rencana_hasil_kerja');
            $table->string('unit_kerja', 255)->nullable();
            $table->enum('status', ['AKTIF', 'NONAKTIF'])->default('AKTIF');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index(['tahun', 'triwulan']);
            $table->index('status');
        });

        // Create rhk_asn table
        Schema::create('rhk_asn', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('rhk_pimpinan_id')->constrained('rhk_pimpinan')->onDelete('restrict');
            $table->text('rencana_hasil_kerja_asn');
            $table->text('indikator_kinerja');
            $table->string('target', 255);
            $table->string('realisasi', 255)->nullable();
            $table->string('satuan', 50)->nullable();
            $table->enum('status', ['DRAFT', 'DIAJUKAN', 'DISETUJUI', 'DIKEMBALIKAN'])->default('DRAFT');
            $table->text('catatan_atasan')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('rhk_pimpinan_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rhk_asn');
        Schema::dropIfExists('rhk_pimpinan');
    }
};
