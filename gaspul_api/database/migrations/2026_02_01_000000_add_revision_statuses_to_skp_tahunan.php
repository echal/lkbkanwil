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
        Schema::table('skp_tahunan', function (Blueprint $table) {
            // Update enum to include revision statuses
            DB::statement("ALTER TABLE skp_tahunan MODIFY COLUMN status ENUM('DRAFT', 'DIAJUKAN', 'DISETUJUI', 'DITOLAK', 'REVISI_DIAJUKAN', 'REVISI_DITOLAK') DEFAULT 'DRAFT'");

            // Add columns for revision tracking
            $table->text('alasan_revisi')->nullable()->after('status');
            $table->timestamp('revisi_diajukan_at')->nullable()->after('alasan_revisi');
            $table->timestamp('revisi_disetujui_at')->nullable()->after('revisi_diajukan_at');
            $table->text('catatan_revisi')->nullable()->after('revisi_disetujui_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skp_tahunan', function (Blueprint $table) {
            $table->dropColumn([
                'alasan_revisi',
                'revisi_diajukan_at',
                'revisi_disetujui_at',
                'catatan_revisi'
            ]);

            // Revert enum to original statuses
            DB::statement("ALTER TABLE skp_tahunan MODIFY COLUMN status ENUM('DRAFT', 'DIAJUKAN', 'DISETUJUI', 'DITOLAK') DEFAULT 'DRAFT'");
        });
    }
};
