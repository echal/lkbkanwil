<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan unique index pada kolom NIP di tabel users
     * untuk mencegah duplikasi NIP
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add unique index to nip column
            // Using whereNotNull to allow multiple NULL values
            $table->unique('nip', 'users_nip_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Menghapus unique index dari kolom NIP
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop unique index
            $table->dropUnique('users_nip_unique');
        });
    }
};
