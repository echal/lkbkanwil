<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * REFACTOR SKP TAHUNAN: HEADER-DETAIL PATTERN
     *
     * SEBELUM:
     * - skp_tahunan (flat table)
     * - validasi unik: user_id + tahun + sasaran_kegiatan_id
     * - ASN tidak bisa input multiple butir kinerja dengan sasaran sama
     *
     * SESUDAH:
     * - skp_tahunan (HEADER: user_id + tahun + status)
     * - skp_tahunan_detail (DETAIL: multiple rows per header)
     * - ASN BOLEH input berkali-kali dengan sasaran/indikator sama
     */
    public function up(): void
    {
        // BACKUP data lama ke temporary table
        if (Schema::hasTable('skp_tahunan')) {
            Schema::rename('skp_tahunan', 'skp_tahunan_old_backup');
        }

        // 1. CREATE: skp_tahunan (HEADER - Policy Level)
        Schema::create('skp_tahunan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->year('tahun');
            $table->enum('status', ['DRAFT', 'DIAJUKAN', 'DISETUJUI', 'DITOLAK'])->default('DRAFT');
            $table->text('catatan_atasan')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // UNIQUE CONSTRAINT: user_id + tahun ONLY
            $table->unique(['user_id', 'tahun'], 'unique_skp_tahunan_per_user_per_year');

            // Foreign Keys
            $table->foreign('user_id', 'fk_skp_tahunan_user')
                ->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by', 'fk_skp_tahunan_approved_by')
                ->references('id')->on('users')->onDelete('set null');

            // Indexes for query performance
            $table->index('status');
            $table->index(['user_id', 'tahun', 'status']);
        });

        // 2. CREATE: skp_tahunan_detail (DETAIL - Butir Kinerja)
        Schema::create('skp_tahunan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skp_tahunan_id')->constrained('skp_tahunan')->onDelete('cascade');
            $table->foreignId('sasaran_kegiatan_id')->constrained('sasaran_kegiatan')->onDelete('restrict');
            $table->foreignId('indikator_kinerja_id')->constrained('indikator_kinerja')->onDelete('restrict');
            $table->integer('target_tahunan')->unsigned();
            $table->string('satuan', 50);
            $table->text('rencana_aksi')->nullable();
            $table->integer('realisasi_tahunan')->unsigned()->default(0);
            $table->timestamps();

            // NO UNIQUE CONSTRAINT on sasaran/indikator
            // ASN BOLEH menambahkan detail berkali-kali dengan sasaran/indikator sama

            // Indexes for query performance
            $table->index('skp_tahunan_id');
            $table->index('sasaran_kegiatan_id');
            $table->index('indikator_kinerja_id');
        });

        // 3. MIGRATE data from backup table
        if (Schema::hasTable('skp_tahunan_old_backup')) {
            // First, create header records (grouped by user_id + tahun)
            DB::statement("
                INSERT INTO skp_tahunan (user_id, tahun, status, catatan_atasan, approved_by, approved_at, created_at, updated_at)
                SELECT
                    user_id,
                    tahun,
                    MAX(status) as status,
                    MAX(catatan_atasan) as catatan_atasan,
                    MAX(approved_by) as approved_by,
                    MAX(approved_at) as approved_at,
                    MIN(created_at) as created_at,
                    MAX(updated_at) as updated_at
                FROM skp_tahunan_old_backup
                GROUP BY user_id, tahun
                ORDER BY user_id, tahun
            ");

            // Then, migrate all detail records
            DB::statement("
                INSERT INTO skp_tahunan_detail (
                    skp_tahunan_id,
                    sasaran_kegiatan_id,
                    indikator_kinerja_id,
                    target_tahunan,
                    satuan,
                    rencana_aksi,
                    realisasi_tahunan,
                    created_at,
                    updated_at
                )
                SELECT
                    new_header.id,
                    old.sasaran_kegiatan_id,
                    old.indikator_kinerja_id,
                    old.target_tahunan,
                    old.satuan,
                    old.rencana_aksi,
                    old.realisasi_tahunan,
                    old.created_at,
                    old.updated_at
                FROM skp_tahunan_old_backup old
                INNER JOIN skp_tahunan new_header
                    ON old.user_id = new_header.user_id
                    AND old.tahun = new_header.tahun
                ORDER BY old.id
            ");
        }

        // 4. UPDATE: rencana_kerja_asn foreign key reference
        if (Schema::hasTable('rencana_kerja_asn')) {
            // Add new column for detail reference
            Schema::table('rencana_kerja_asn', function (Blueprint $table) {
                $table->foreignId('skp_tahunan_detail_id')->nullable()->after('skp_tahunan_id')
                    ->constrained('skp_tahunan_detail')->onDelete('restrict');
            });

            // Migrate FK: map old skp_tahunan_id to new skp_tahunan_detail_id
            // Strategy: match first detail record of matching header
            DB::statement("
                UPDATE rencana_kerja_asn rk
                INNER JOIN skp_tahunan_old_backup old_skp ON rk.skp_tahunan_id = old_skp.id
                INNER JOIN skp_tahunan new_header ON old_skp.user_id = new_header.user_id AND old_skp.tahun = new_header.tahun
                INNER JOIN skp_tahunan_detail std ON new_header.id = std.skp_tahunan_id
                SET rk.skp_tahunan_detail_id = std.id
                WHERE rk.skp_tahunan_detail_id IS NULL
                LIMIT 1
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore FK in rencana_kerja_asn
        if (Schema::hasTable('rencana_kerja_asn')) {
            Schema::table('rencana_kerja_asn', function (Blueprint $table) {
                $table->dropForeign(['skp_tahunan_detail_id']);
                $table->dropColumn('skp_tahunan_detail_id');
            });
        }

        // Drop new tables
        Schema::dropIfExists('skp_tahunan_detail');
        Schema::dropIfExists('skp_tahunan');

        // Restore old table
        if (Schema::hasTable('skp_tahunan_old_backup')) {
            Schema::rename('skp_tahunan_old_backup', 'skp_tahunan');
        }
    }
};
