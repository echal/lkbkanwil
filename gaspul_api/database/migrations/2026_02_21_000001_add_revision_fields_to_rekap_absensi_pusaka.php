<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekap_absensi_pusaka', function (Blueprint $table) {
            $table->unsignedSmallInteger('revision_count')->default(0)->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('rekap_absensi_pusaka', function (Blueprint $table) {
            $table->dropColumn('revision_count');
        });
    }
};
