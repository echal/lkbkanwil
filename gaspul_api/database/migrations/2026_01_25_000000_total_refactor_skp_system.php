<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * PEROMBAKAN TOTAL SISTEM SKP
 *
 * CHANGELOG:
 * ❌ DROP: rencana_kerja_asn (SKP Triwulan)
 * ❌ DROP: bulanan (versi lama)
 * ❌ DROP: harian (versi lama)
 * ✅ RENAME: indikator_kinerja → rhk_pimpinan
 * ✅ RECREATE: skp_tahunan (struktur baru)
 * ✅ RECREATE: skp_tahunan_detail (struktur baru)
 * ✅ NEW: master_atasan
 * ✅ NEW: rencana_aksi_bulanan
 * ✅ NEW: progres_harian
 *
 * @version 2.0.0
 * @date 2026-01-25
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ========================================================================
        // STEP 1: DROP TABEL LAMA (dalam urutan yang benar untuk menghindari FK error)
        // ========================================================================

        Schema::dropIfExists('harian');              // Drop dulu (FK ke bulanan)
        Schema::dropIfExists('bulanan');             // Drop kedua (FK ke rencana_kerja_asn)
        Schema::dropIfExists('rencana_kerja_asn');   // Drop ketiga (SKP Triwulan)

        // ========================================================================
        // STEP 2: BACKUP & DROP SKP TAHUNAN LAMA
        // ========================================================================

        if (Schema::hasTable('skp_tahunan_detail')) {
            Schema::rename('skp_tahunan_detail', 'skp_tahunan_detail_backup_v1');
        }

        if (Schema::hasTable('skp_tahunan')) {
            Schema::rename('skp_tahunan', 'skp_tahunan_backup_v1');
        }

        // ========================================================================
        // STEP 3: UPDATE rhk_pimpinan structure
        // ========================================================================

        // SKIPPED - rhk_pimpinan already exists with different structure
        // Will be handled by separate migration: 2026_01_25_000001_update_rhk_pimpinan_structure.php

        // NOTE: indikator_kinerja table will remain for backup table references
        // It will not be used in new system

        // ========================================================================
        // STEP 4: CREATE master_atasan (TABEL BARU)
        // ========================================================================

        Schema::create('master_atasan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asn_id')->comment('ID User (ASN/PPPK)');
            $table->unsignedBigInteger('atasan_id')->comment('ID User (Atasan Langsung)');
            $table->year('tahun')->comment('Tahun berlaku');
            $table->enum('status', ['AKTIF', 'NONAKTIF'])->default('AKTIF');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('asn_id', 'fk_master_atasan_asn')
                ->references('id')->on('users')->onDelete('cascade');
            $table->foreign('atasan_id', 'fk_master_atasan_atasan')
                ->references('id')->on('users')->onDelete('cascade');

            // Constraints & Indexes
            $table->unique(['asn_id', 'tahun'], 'unique_asn_per_year');
            $table->index('atasan_id');
            $table->index('tahun');
            $table->index('status');
        });

        // ========================================================================
        // STEP 5: RECREATE skp_tahunan (HEADER - struktur baru)
        // ========================================================================

        Schema::create('skp_tahunan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('ID ASN/PPPK');
            $table->year('tahun')->comment('Tahun SKP');
            $table->enum('status', ['DRAFT', 'DIAJUKAN', 'DISETUJUI', 'DITOLAK'])->default('DRAFT');
            $table->text('catatan_atasan')->nullable()->comment('Catatan dari atasan');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('ID Atasan yang approve');
            $table->timestamp('approved_at')->nullable()->comment('Waktu approval');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('user_id', 'fk_skp_tahunan_user')
                ->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by', 'fk_skp_tahunan_approved_by')
                ->references('id')->on('users')->onDelete('set null');

            // UNIQUE CONSTRAINT: user_id + tahun ONLY
            $table->unique(['user_id', 'tahun'], 'unique_skp_per_user_per_year');

            // Indexes
            $table->index('status');
            $table->index('tahun');
            $table->index(['user_id', 'tahun', 'status']);
        });

        // ========================================================================
        // STEP 6: RECREATE skp_tahunan_detail (DETAIL - struktur baru)
        // ========================================================================

        Schema::create('skp_tahunan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skp_tahunan_id')->comment('ID SKP Tahunan Header')
                ->constrained('skp_tahunan')->onDelete('cascade');
            $table->foreignId('rhk_pimpinan_id')->comment('ID RHK Pimpinan')
                ->constrained('rhk_pimpinan')->onDelete('restrict');
            $table->integer('target_tahunan')->unsigned()->comment('Target tahunan');
            $table->string('satuan', 50)->comment('Satuan target');
            $table->text('rencana_aksi')->comment('Rencana Aksi ASN untuk mencapai RHK');
            $table->integer('realisasi_tahunan')->unsigned()->default(0)->comment('Realisasi tahunan (aggregated)');
            $table->timestamps();

            // NO UNIQUE CONSTRAINT - ASN boleh tambah RHK yang sama berkali-kali
            // Validasi UNIQUE di level aplikasi: skp_tahunan_id + rhk_pimpinan_id + rencana_aksi

            // Indexes
            $table->index('skp_tahunan_id');
            $table->index('rhk_pimpinan_id');
        });

        // ========================================================================
        // STEP 7: CREATE rencana_aksi_bulanan (TABEL BARU - menggantikan bulanan)
        // ========================================================================

        Schema::create('rencana_aksi_bulanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skp_tahunan_detail_id')->comment('ID SKP Tahunan Detail')
                ->constrained('skp_tahunan_detail')->onDelete('cascade');
            $table->tinyInteger('bulan')->unsigned()->comment('Bulan (1-12)');
            $table->year('tahun')->comment('Tahun');
            $table->text('rencana_aksi_bulanan')->nullable()->comment('Rencana Aksi Bulanan (diisi ASN)');
            $table->integer('target_bulanan')->unsigned()->default(0)->comment('Target bulanan (diisi ASN)');
            $table->string('satuan_target', 100)->nullable()
                ->comment('Dokumen, Data, Laporan, Kegiatan, Persentase, Berkas, Dokumentasi');
            $table->integer('realisasi_bulanan')->unsigned()->default(0)
                ->comment('Realisasi bulanan (sum dari progres_harian)');
            $table->enum('status', ['BELUM_DIISI', 'AKTIF', 'SELESAI'])->default('BELUM_DIISI');
            $table->timestamps();

            // UNIQUE CONSTRAINT: skp_tahunan_detail_id + bulan + tahun
            $table->unique(['skp_tahunan_detail_id', 'bulan', 'tahun'], 'unique_aksi_per_bulan');

            // Indexes
            $table->index(['bulan', 'tahun']);
            $table->index('status');
        });

        // ========================================================================
        // STEP 8: CREATE progres_harian (TABEL BARU - menggantikan harian)
        // ========================================================================

        Schema::create('progres_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rencana_aksi_bulanan_id')->comment('ID Rencana Aksi Bulanan')
                ->constrained('rencana_aksi_bulanan')->onDelete('cascade');
            $table->date('tanggal')->comment('Tanggal kegiatan');
            $table->time('jam_mulai')->comment('Jam mulai kegiatan');
            $table->time('jam_selesai')->comment('Jam selesai kegiatan');

            // Durasi otomatis dihitung (stored generated column)
            // Note: MySQL 5.7+ mendukung generated columns
            DB::statement('
                ALTER TABLE progres_harian
                ADD COLUMN durasi_menit INT UNSIGNED
                GENERATED ALWAYS AS (
                    TIMESTAMPDIFF(MINUTE,
                        CONCAT(tanggal, " ", jam_mulai),
                        CONCAT(tanggal, " ", jam_selesai)
                    )
                ) STORED
                COMMENT "Durasi kerja dalam menit (auto-calculated)"
            ');

            $table->text('rencana_kegiatan_harian')->comment('Deskripsi rencana kegiatan harian');
            $table->integer('progres')->unsigned()->default(0)->comment('Progres kegiatan (angka)');
            $table->string('satuan', 50)->comment('Satuan progres');
            $table->text('bukti_dukung')->nullable()->comment('Link Google Drive atau link lainnya');
            $table->enum('status_bukti', ['BELUM_ADA', 'SUDAH_ADA'])->default('BELUM_ADA');
            $table->text('keterangan')->nullable()->comment('Keterangan tambahan');
            $table->timestamps();

            // Indexes
            $table->index('tanggal');
            $table->index(['rencana_aksi_bulanan_id', 'tanggal']);
            $table->index('status_bukti');
        });

        // ========================================================================
        // STEP 9: MIGRATE DATA (Optional - tergantung kebutuhan)
        // ========================================================================

        // Jika ada data di skp_tahunan_backup_v1 dan skp_tahunan_detail_backup_v1
        // yang perlu dimigrate, lakukan di sini.
        // CATATAN: Karena struktur berubah drastis, migrasi data perlu review manual.

        // Contoh migrasi header (simplified):
        if (Schema::hasTable('skp_tahunan_backup_v1')) {
            try {
                DB::statement("
                    INSERT INTO skp_tahunan (user_id, tahun, status, catatan_atasan, approved_by, approved_at, created_at, updated_at)
                    SELECT DISTINCT
                        user_id,
                        tahun,
                        status,
                        catatan_atasan,
                        approved_by,
                        approved_at,
                        created_at,
                        updated_at
                    FROM skp_tahunan_backup_v1
                    GROUP BY user_id, tahun
                ");
            } catch (\Exception $e) {
                // Log error but continue - manual migration may be needed
                \Log::warning('SKP Tahunan migration skipped: ' . $e->getMessage());
            }
        }

        // Migrasi detail (MANUAL REVIEW REQUIRED)
        // Karena field berubah dari sasaran_kegiatan_id + indikator_kinerja_id
        // menjadi rhk_pimpinan_id + rencana_aksi, perlu mapping manual
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new tables
        Schema::dropIfExists('progres_harian');
        Schema::dropIfExists('rencana_aksi_bulanan');
        Schema::dropIfExists('skp_tahunan_detail');
        Schema::dropIfExists('skp_tahunan');
        Schema::dropIfExists('master_atasan');

        // Restore rhk_pimpinan → indikator_kinerja
        if (Schema::hasTable('rhk_pimpinan')) {
            Schema::table('rhk_pimpinan', function (Blueprint $table) {
                $table->renameColumn('rhk_pimpinan', 'indikator_kinerja');
            });
            Schema::rename('rhk_pimpinan', 'indikator_kinerja');
        }

        // Restore backup tables
        if (Schema::hasTable('skp_tahunan_backup_v1')) {
            Schema::rename('skp_tahunan_backup_v1', 'skp_tahunan');
        }

        if (Schema::hasTable('skp_tahunan_detail_backup_v1')) {
            Schema::rename('skp_tahunan_detail_backup_v1', 'skp_tahunan_detail');
        }

        // Note: rencana_kerja_asn, bulanan, harian tidak dapat direstore
        // karena strukturnya sudah berubah total. Restore manual dari backup jika diperlukan.
    }
};
