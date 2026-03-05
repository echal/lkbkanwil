<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_absensi_histori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_absensi_id')
                  ->constrained('rekap_absensi_pusaka')
                  ->onDelete('cascade');
            $table->text('link_drive_lama');
            $table->unsignedSmallInteger('revision_number'); // nomor revisi sebelum diganti
            $table->timestamp('tanggal_revisi');
            $table->timestamps();

            $table->index('rekap_absensi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_absensi_histori');
    }
};
