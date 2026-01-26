<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabel ini menyimpan Rencana Kerja ASN (SKP Triwulan)
     * ASN memilih Sasaran Kegiatan & Indikator Kinerja dari master ADMIN,
     * lalu menambahkan Tahun, Triwulan, Target, dan Realisasi mereka sendiri.
     */
    public function up(): void
    {
        Schema::create('rencana_kerja_asn', function (Blueprint $table) {
            $table->id();

            // Relasi ke User (ASN yang membuat)
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('ASN yang membuat rencana kerja');

            // Relasi ke Sasaran Kegiatan (dipilih dari master)
            $table->foreignId('sasaran_kegiatan_id')
                ->constrained('sasaran_kegiatan')
                ->onDelete('restrict')
                ->comment('Sasaran Kegiatan yang dipilih ASN dari master');

            // Relasi ke Indikator Kinerja (dipilih dari master)
            $table->foreignId('indikator_kinerja_id')
                ->constrained('indikator_kinerja')
                ->onDelete('restrict')
                ->comment('Indikator Kinerja yang dipilih ASN dari master');

            // Data Periode (Level ASN - BARU DIMULAI DI SINI)
            $table->year('tahun')
                ->comment('Tahun pelaksanaan SKP');

            $table->enum('triwulan', ['I', 'II', 'III', 'IV'])
                ->comment('Triwulan pelaksanaan (I, II, III, IV)');

            // Data Target & Realisasi (Level ASN)
            $table->decimal('target', 10, 2)
                ->comment('Target yang ditetapkan ASN');

            $table->string('satuan', 100)
                ->comment('Satuan pengukuran (misal: %, jumlah, dokumen)');

            $table->decimal('realisasi', 10, 2)
                ->default(0)
                ->comment('Realisasi yang dicapai ASN');

            // Catatan & Status
            $table->text('catatan_asn')
                ->nullable()
                ->comment('Catatan dari ASN tentang rencana kerja ini');

            $table->enum('status', ['DRAFT', 'DIAJUKAN', 'DISETUJUI', 'DITOLAK'])
                ->default('DRAFT')
                ->comment('Status approval rencana kerja');

            $table->text('catatan_atasan')
                ->nullable()
                ->comment('Catatan dari Atasan saat approval');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('User (Atasan) yang meng-approve');

            $table->timestamp('approved_at')
                ->nullable()
                ->comment('Waktu di-approve');

            $table->timestamps();

            // Indexes untuk query performance
            $table->index('user_id');
            $table->index('sasaran_kegiatan_id');
            $table->index('indikator_kinerja_id');
            $table->index('tahun');
            $table->index('triwulan');
            $table->index('status');

            // Unique constraint: 1 ASN tidak bisa buat duplikat rencana untuk sasaran-indikator-tahun-triwulan yang sama
            $table->unique(['user_id', 'sasaran_kegiatan_id', 'indikator_kinerja_id', 'tahun', 'triwulan'], 'unique_rencana_kerja');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rencana_kerja_asn');
    }
};
