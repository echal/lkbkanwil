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
        Schema::table('progres_harian', function (Blueprint $table) {
            // Tambah tipe progres: KINERJA_HARIAN (default) atau TUGAS_ATASAN
            $table->enum('tipe_progres', ['KINERJA_HARIAN', 'TUGAS_ATASAN'])
                ->default('KINERJA_HARIAN')
                ->after('rencana_aksi_bulanan_id');

            // Tugas langsung atasan (untuk tipe TUGAS_ATASAN)
            $table->text('tugas_atasan')->nullable()->after('tipe_progres');

            // Ubah rencana_aksi_bulanan_id menjadi nullable (karena TUGAS_ATASAN tidak pakai)
            $table->unsignedBigInteger('rencana_aksi_bulanan_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progres_harian', function (Blueprint $table) {
            $table->dropColumn(['tipe_progres', 'tugas_atasan']);

            // Kembalikan rencana_aksi_bulanan_id menjadi required
            $table->unsignedBigInteger('rencana_aksi_bulanan_id')->nullable(false)->change();
        });
    }
};
