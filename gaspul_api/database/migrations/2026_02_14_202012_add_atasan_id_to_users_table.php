<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan kolom atasan_id untuk hierarki approval berbasis relasi
     * - nullable: tidak merusak data existing
     * - foreign key dengan onDelete('set null'): aman jika atasan dihapus
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan kolom atasan_id setelah unit_kerja_id
            $table->unsignedBigInteger('atasan_id')->nullable()->after('unit_kerja_id');

            // Foreign key ke tabel users sendiri (self-referencing)
            $table->foreign('atasan_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null'); // Jika atasan dihapus, set null (aman)

            // Index untuk performa query
            $table->index('atasan_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Rollback yang aman: drop foreign key dulu, baru drop column
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key constraint dulu
            $table->dropForeign(['atasan_id']);

            // Lalu drop column
            $table->dropColumn('atasan_id');
        });
    }
};
