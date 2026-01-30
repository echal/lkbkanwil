<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add indexes for Harian Bawahan Monitoring Dashboard
     * Performance target: < 200ms for 250 ASN
     */
    public function up(): void
    {
        Schema::table('progres_harian', function (Blueprint $table) {
            // Composite index for filtering by user + date range
            $table->index(['user_id', 'tanggal'], 'idx_progres_user_tanggal');

            // Index for tipe_progres filtering (KH vs TLA)
            $table->index('tipe_progres', 'idx_progres_tipe');

            // Index for status_bukti filtering
            $table->index('status_bukti', 'idx_progres_status_bukti');

            // Composite index for date range queries
            $table->index(['tanggal', 'user_id'], 'idx_progres_tanggal_user');
        });

        Schema::table('users', function (Blueprint $table) {
            // Index for filtering ASN by unit_kerja_id (security constraint)
            $table->index(['unit_kerja_id', 'role', 'status_pegawai'], 'idx_users_unit_role_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progres_harian', function (Blueprint $table) {
            $table->dropIndex('idx_progres_user_tanggal');
            $table->dropIndex('idx_progres_tipe');
            $table->dropIndex('idx_progres_status_bukti');
            $table->dropIndex('idx_progres_tanggal_user');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_unit_role_status');
        });
    }
};
