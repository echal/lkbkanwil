<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('harian', function (Blueprint $table) {
            $table->id();

            // Foreign key to Bulanan
            $table->foreignId('bulanan_id')
                ->constrained('bulanan')
                ->onDelete('cascade')
                ->comment('ID Bulanan parent');

            // Tanggal kegiatan
            $table->date('tanggal')
                ->comment('Tanggal kegiatan harian');

            // Kegiatan harian
            $table->text('kegiatan_harian')
                ->comment('Deskripsi kegiatan yang dilakukan');

            // Progres dan satuan
            $table->integer('progres')
                ->default(0)
                ->comment('Progres/realisasi kegiatan');

            $table->string('satuan', 50)
                ->comment('Satuan progres (misal: dokumen, laporan, kegiatan)');

            // Waktu kerja (dalam menit atau jam)
            $table->integer('waktu_kerja')
                ->nullable()
                ->comment('Waktu kerja dalam menit');

            // Bukti (MANDATORY) - bisa file atau link
            $table->string('bukti_type', 20)
                ->comment('Tipe bukti: file atau link');

            $table->string('bukti_path')
                ->nullable()
                ->comment('Path file bukti (jika upload file)');

            $table->text('bukti_link')
                ->nullable()
                ->comment('Link bukti (jika link eksternal)');

            // Metadata
            $table->string('keterangan', 500)
                ->nullable()
                ->comment('Keterangan tambahan');

            $table->timestamps();

            // Index for faster queries
            $table->index('tanggal');
            $table->index(['bulanan_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harian');
    }
};
