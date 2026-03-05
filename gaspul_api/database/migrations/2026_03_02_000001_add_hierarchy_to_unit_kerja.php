<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom hierarki ke tabel unit_kerja.
     *
     * - parent_id: nullable FK ke unit_kerja.id (self-referencing)
     * - level: integer default 1 (root = 1, child = parent.level + 1)
     *
     * Data existing: semua unit otomatis parent_id=NULL, level=1 (sudah sesuai default).
     */
    public function up(): void
    {
        Schema::table('unit_kerja', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('eselon');
            $table->unsignedTinyInteger('level')->default(1)->after('parent_id');

            $table->foreign('parent_id')
                  ->references('id')
                  ->on('unit_kerja')
                  ->onDelete('set null');

            $table->index('parent_id');
        });
    }

    /**
     * Rollback: hapus kolom hierarki.
     */
    public function down(): void
    {
        Schema::table('unit_kerja', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn(['parent_id', 'level']);
        });
    }
};
