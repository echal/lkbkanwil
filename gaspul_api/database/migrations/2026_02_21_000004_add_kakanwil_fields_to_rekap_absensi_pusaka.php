<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekap_absensi_pusaka', function (Blueprint $table) {
            $table->foreignId('verified_by_kakanwil')
                  ->nullable()
                  ->after('verified_by')
                  ->constrained('users')
                  ->onDelete('set null');

            $table->text('catatan_kakanwil')
                  ->nullable()
                  ->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('rekap_absensi_pusaka', function (Blueprint $table) {
            $table->dropForeign(['verified_by_kakanwil']);
            $table->dropColumn(['verified_by_kakanwil', 'catatan_kakanwil']);
        });
    }
};
