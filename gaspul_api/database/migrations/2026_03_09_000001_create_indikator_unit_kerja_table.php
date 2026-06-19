<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indikator_unit_kerja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('indikator_kinerja_id');
            $table->unsignedBigInteger('unit_kerja_id');
            $table->timestamps();

            $table->unique(['indikator_kinerja_id', 'unit_kerja_id'], 'uq_indikator_unit');
            $table->foreign('indikator_kinerja_id')
                  ->references('id')->on('indikator_kinerja')->onDelete('cascade');
            $table->foreign('unit_kerja_id')
                  ->references('id')->on('unit_kerja')->onDelete('cascade');
            $table->index('unit_kerja_id');
        });

        // Isi pivot otomatis dari data existing (indikator yang sudah punya unit_kerja_id)
        DB::statement("
            INSERT INTO indikator_unit_kerja (indikator_kinerja_id, unit_kerja_id, created_at, updated_at)
            SELECT id, unit_kerja_id, NOW(), NOW()
            FROM indikator_kinerja
            WHERE unit_kerja_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('indikator_unit_kerja');
    }
};
