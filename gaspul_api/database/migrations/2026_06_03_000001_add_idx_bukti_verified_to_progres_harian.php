<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progres_harian', function (Blueprint $table) {
            $table->index(['status_bukti', 'verified_at'], 'idx_bukti_verified');
        });
    }

    public function down(): void
    {
        Schema::table('progres_harian', function (Blueprint $table) {
            $table->dropIndex('idx_bukti_verified');
        });
    }
};
