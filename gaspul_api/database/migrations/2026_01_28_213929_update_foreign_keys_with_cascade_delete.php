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
        // Drop existing foreign key constraints using actual constraint names
        DB::statement('ALTER TABLE skp_tahunan_detail DROP FOREIGN KEY skp_tahunan_detail_ibfk_2');
        DB::statement('ALTER TABLE rencana_aksi_bulanan DROP FOREIGN KEY rencana_aksi_bulanan_ibfk_1');

        // Re-add foreign keys with CASCADE DELETE
        Schema::table('skp_tahunan_detail', function (Blueprint $table) {
            $table->foreign('rhk_pimpinan_id')
                ->references('id')
                ->on('rhk_pimpinan')
                ->onDelete('cascade');
        });

        Schema::table('rencana_aksi_bulanan', function (Blueprint $table) {
            $table->foreign('skp_tahunan_detail_id')
                ->references('id')
                ->on('skp_tahunan_detail')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop cascade foreign keys
        Schema::table('skp_tahunan_detail', function (Blueprint $table) {
            $table->dropForeign(['rhk_pimpinan_id']);
        });

        Schema::table('rencana_aksi_bulanan', function (Blueprint $table) {
            $table->dropForeign(['skp_tahunan_detail_id']);
        });

        // Re-add foreign keys WITHOUT cascade (restore original)
        DB::statement('ALTER TABLE skp_tahunan_detail ADD CONSTRAINT skp_tahunan_detail_ibfk_2 FOREIGN KEY (rhk_pimpinan_id) REFERENCES rhk_pimpinan(id)');
        DB::statement('ALTER TABLE rencana_aksi_bulanan ADD CONSTRAINT rencana_aksi_bulanan_ibfk_1 FOREIGN KEY (skp_tahunan_detail_id) REFERENCES skp_tahunan_detail(id)');
    }
};
