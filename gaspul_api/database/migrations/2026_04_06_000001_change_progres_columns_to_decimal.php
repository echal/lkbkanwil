<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah kolom progres di tabel progres_harian
        Schema::table('progres_harian', function (Blueprint $table) {
            $table->decimal('progres', 8, 2)->default(0)->change();
        });

        // Ubah kolom target_bulanan & realisasi_bulanan di tabel rencana_aksi_bulanan
        Schema::table('rencana_aksi_bulanan', function (Blueprint $table) {
            $table->decimal('target_bulanan', 8, 2)->default(0)->change();
            $table->decimal('realisasi_bulanan', 8, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('progres_harian', function (Blueprint $table) {
            $table->integer('progres')->default(0)->change();
        });

        Schema::table('rencana_aksi_bulanan', function (Blueprint $table) {
            $table->integer('target_bulanan')->default(0)->change();
            $table->integer('realisasi_bulanan')->default(0)->change();
        });
    }
};
