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
            // Make rencana_kegiatan_harian, progres, satuan nullable
            // For TUGAS_ATASAN, these fields are not used
            $table->text('rencana_kegiatan_harian')->nullable()->change();
            $table->string('satuan', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progres_harian', function (Blueprint $table) {
            $table->text('rencana_kegiatan_harian')->nullable(false)->change();
            $table->string('satuan', 50)->nullable(false)->change();
        });
    }
};
