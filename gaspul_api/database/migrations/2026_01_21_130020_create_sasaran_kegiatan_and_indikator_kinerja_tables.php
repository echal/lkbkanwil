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
        // Tabel Sasaran Kegiatan (Level Organisasi/Unit Kerja)
        Schema::create('sasaran_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('unit_kerja'); // Nama unit kerja
            $table->text('sasaran_kegiatan'); // Sasaran strategis organisasi
            $table->enum('status', ['AKTIF', 'NONAKTIF'])->default('AKTIF');
            $table->timestamps();

            // Index untuk query yang sering digunakan
            $table->index('status');
            $table->index('unit_kerja');
        });

        // Tabel Indikator Kinerja (Turunan dari Sasaran Kegiatan)
        Schema::create('indikator_kinerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sasaran_kegiatan_id')
                ->constrained('sasaran_kegiatan')
                ->onDelete('cascade'); // Jika sasaran dihapus, indikator ikut terhapus
            $table->text('indikator_kinerja'); // Indikator kinerja organisasi
            $table->enum('status', ['AKTIF', 'NONAKTIF'])->default('AKTIF');
            $table->timestamps();

            // Index untuk query yang sering digunakan
            $table->index('status');
            $table->index('sasaran_kegiatan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indikator_kinerja');
        Schema::dropIfExists('sasaran_kegiatan');
    }
};
