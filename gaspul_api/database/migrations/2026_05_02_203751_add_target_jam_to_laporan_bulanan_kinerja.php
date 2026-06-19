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
        Schema::table('laporan_bulanan_kinerja', function (Blueprint $table) {
            // Snapshot target jam saat laporan dikirim — nullable agar data lama tidak terpengaruh
            $table->decimal('target_jam', 6, 2)->nullable()->after('total_jam');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporan_bulanan_kinerja', function (Blueprint $table) {
            $table->dropColumn('target_jam');
        });
    }
};
