<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_bulanan_kinerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('bulan');   // 1–12
            $table->unsignedSmallInteger('tahun');  // 2025, 2026, ...
            $table->unsignedSmallInteger('total_hari')->default(0);
            $table->unsignedSmallInteger('total_jam')->default(0);
            $table->decimal('capaian_persen', 5, 2)->default(0.00); // misal 87.50
            $table->enum('status', ['DRAFT', 'DIKIRIM', 'DISETUJUI', 'DITOLAK'])->default('DRAFT');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan')->nullable(); // catatan atasan saat tolak/setujui

            $table->timestamps();

            // Satu laporan per user per bulan per tahun
            $table->unique(['user_id', 'bulan', 'tahun']);

            // Index untuk query umum
            $table->index('user_id');
            $table->index('status');
            $table->index(['bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_bulanan_kinerja');
    }
};
