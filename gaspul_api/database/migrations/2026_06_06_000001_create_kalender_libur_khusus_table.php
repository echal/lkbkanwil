<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kalender_libur_khusus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_kerja_id')->constrained('unit_kerja')->cascadeOnDelete();
            $table->boolean('berlaku_ke_anak')->default(true);
            $table->enum('target_khusus', ['GURU', 'PENYULUH', 'PENGHULU'])->index();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('keterangan', 255);
            $table->enum('status', ['DRAFT', 'AKTIF'])->default('DRAFT');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal_mulai', 'tanggal_selesai']);
            $table->index(['unit_kerja_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kalender_libur_khusus');
    }
};
