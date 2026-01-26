<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix foreign key constraint that was pointing to skp_tahunan_old_backup
     * instead of the current skp_tahunan table
     */
    public function up(): void
    {
        Schema::table('rencana_kerja_asn', function (Blueprint $table) {
            // Drop the incorrect foreign key constraint
            $table->dropForeign('rencana_kerja_asn_skp_tahunan_id_foreign');

            // Add the correct foreign key constraint pointing to skp_tahunan
            $table->foreign('skp_tahunan_id')
                ->references('id')
                ->on('skp_tahunan')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rencana_kerja_asn', function (Blueprint $table) {
            // Drop the correct foreign key
            $table->dropForeign(['skp_tahunan_id']);

            // Restore the old foreign key (pointing to backup table)
            $table->foreign('skp_tahunan_id')
                ->references('id')
                ->on('skp_tahunan_old_backup')
                ->onDelete('cascade');
        });
    }
};
