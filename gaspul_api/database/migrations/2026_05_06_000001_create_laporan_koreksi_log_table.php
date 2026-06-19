<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_koreksi_log', function (Blueprint $table) {
            $table->id();

            // ASN yang dikoreksi
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Snapshot laporan LAMA sebelum dihapus
            $table->unsignedTinyInteger('bulan_lama');
            $table->unsignedSmallInteger('tahun_lama');
            $table->unsignedSmallInteger('total_hari_lama')->default(0);
            $table->unsignedSmallInteger('total_jam_lama')->default(0);
            $table->decimal('target_jam_lama', 6, 2)->nullable();
            $table->decimal('capaian_persen_lama', 5, 2)->default(0);
            $table->string('status_lama', 20);
            $table->unsignedBigInteger('approved_by_lama')->nullable();
            $table->timestamp('approved_at_lama')->nullable();

            // Target koreksi
            $table->unsignedTinyInteger('bulan_baru');
            $table->unsignedSmallInteger('tahun_baru');

            // Admin yang mengkoreksi + alasan
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->text('alasan')->nullable();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_koreksi_log');
    }
};
