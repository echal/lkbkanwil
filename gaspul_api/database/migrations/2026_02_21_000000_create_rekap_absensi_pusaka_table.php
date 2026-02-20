<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_absensi_pusaka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('bulan', 7); // format: YYYY-MM
            $table->text('link_drive');
            $table->enum('status', ['pending', 'valid', 'ditolak'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'bulan']);
            $table->index('user_id');
            $table->index('bulan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_absensi_pusaka');
    }
};
