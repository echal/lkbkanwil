<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_bulanan_kinerja', function (Blueprint $table) {
            // Snapshot dibekukan saat laporan di-generate — NULL berarti data lama (fallback dinamis)
            $table->bigInteger('target_menit_bulanan_snapshot')->nullable()->after('target_jam');
            $table->decimal('target_jam_bulanan_snapshot', 8, 2)->nullable()->after('target_menit_bulanan_snapshot');
            $table->integer('hari_kerja_snapshot')->nullable()->after('target_jam_bulanan_snapshot');
            $table->string('pola_kerja_snapshot', 30)->nullable()->after('hari_kerja_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_bulanan_kinerja', function (Blueprint $table) {
            $table->dropColumn([
                'target_menit_bulanan_snapshot',
                'target_jam_bulanan_snapshot',
                'hari_kerja_snapshot',
                'pola_kerja_snapshot',
            ]);
        });
    }
};
