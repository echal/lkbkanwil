<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_kerja', function (Blueprint $table) {
            $table->enum('hari_kerja', ['SENIN_JUMAT', 'SENIN_SABTU'])
                  ->default('SENIN_JUMAT')
                  ->after('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('hari_kerja', ['SENIN_JUMAT', 'SENIN_SABTU'])
                  ->nullable()
                  ->default(null)
                  ->after('status_pegawai');
        });
    }

    public function down(): void
    {
        Schema::table('unit_kerja', function (Blueprint $table) {
            $table->dropColumn('hari_kerja');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('hari_kerja');
        });
    }
};
