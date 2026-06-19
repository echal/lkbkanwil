<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survei_jawaban', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survei_id')->constrained('survei')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('unit_kerja_id')->nullable()->constrained('unit_kerja')->nullOnDelete();

            // 9 pertanyaan skala (1–5)
            $table->unsignedTinyInteger('q1')->nullable();
            $table->unsignedTinyInteger('q2')->nullable();
            $table->unsignedTinyInteger('q3')->nullable();
            $table->unsignedTinyInteger('q4')->nullable();
            $table->unsignedTinyInteger('q5')->nullable();
            $table->unsignedTinyInteger('q6')->nullable();
            $table->unsignedTinyInteger('q7')->nullable();
            $table->unsignedTinyInteger('q8')->nullable();
            $table->unsignedTinyInteger('q9')->nullable();

            // 1 pertanyaan teks bebas
            $table->text('saran')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // 1 ASN hanya bisa isi 1x per survei
            $table->unique(['survei_id', 'user_id']);

            // Index untuk monitoring per unit kerja
            $table->index(['survei_id', 'unit_kerja_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survei_jawaban');
    }
};
