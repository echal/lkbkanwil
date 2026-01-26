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
        // Remove triwulan and tahun from rhk_pimpinan (strategic master data)
        Schema::table('rhk_pimpinan', function (Blueprint $table) {
            $table->dropIndex(['tahun', 'triwulan']); // Drop composite index first
            $table->dropColumn(['tahun', 'triwulan']);
        });

        // Add tahun and triwulan to rhk_asn (ASN determines period)
        Schema::table('rhk_asn', function (Blueprint $table) {
            $table->year('tahun')->after('rhk_pimpinan_id');
            $table->enum('triwulan', ['TW1', 'TW2', 'TW3', 'TW4'])->after('tahun');

            // Add indexes
            $table->index(['user_id', 'tahun', 'triwulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore tahun and triwulan to rhk_pimpinan
        Schema::table('rhk_pimpinan', function (Blueprint $table) {
            $table->year('tahun')->after('id');
            $table->enum('triwulan', ['TW1', 'TW2', 'TW3', 'TW4'])->after('tahun');
            $table->index(['tahun', 'triwulan']);
        });

        // Remove tahun and triwulan from rhk_asn
        Schema::table('rhk_asn', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'tahun', 'triwulan']);
            $table->dropColumn(['tahun', 'triwulan']);
        });
    }
};
