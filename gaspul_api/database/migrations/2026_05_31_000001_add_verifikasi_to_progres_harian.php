<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progres_harian', function (Blueprint $table) {
            $table->enum('verifikasi_eviden', ['SESUAI', 'KURANG', 'TIDAK_SESUAI'])
                  ->nullable()
                  ->after('status_bukti')
                  ->comment('NULL = Belum Diverifikasi');

            $table->text('catatan_verifikasi')
                  ->nullable()
                  ->after('verifikasi_eviden');

            $table->unsignedBigInteger('verified_by')
                  ->nullable()
                  ->after('catatan_verifikasi');

            $table->timestamp('verified_at')
                  ->nullable()
                  ->after('verified_by');

            $table->foreign('verified_by', 'fk_progres_verified_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            $table->index(['user_id', 'verifikasi_eviden'], 'idx_user_verifikasi');
        });
    }

    public function down(): void
    {
        Schema::table('progres_harian', function (Blueprint $table) {
            $table->dropForeign('fk_progres_verified_by');
            $table->dropIndex('idx_user_verifikasi');
            $table->dropColumn([
                'verifikasi_eviden',
                'catatan_verifikasi',
                'verified_by',
                'verified_at',
            ]);
        });
    }
};
