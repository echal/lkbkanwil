<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * UPDATE RHK PIMPINAN STRUCTURE
 *
 * Mengubah struktur rhk_pimpinan yang sudah ada
 * dari struktur lama ke struktur baru
 */
return new class extends Migration
{
    public function up(): void
    {
        // Check if rhk_pimpinan exists and has old structure
        if (Schema::hasTable('rhk_pimpinan')) {
            Schema::table('rhk_pimpinan', function (Blueprint $table) {
                // Drop old foreign keys if exist
                try {
                    $table->dropForeign(['created_by']);
                } catch (\Exception $e) {
                    // Ignore if not exists
                }

                try {
                    $table->dropForeign(['updated_by']);
                } catch (\Exception $e) {
                    // Ignore if not exists
                }

                // Drop old columns
                if (Schema::hasColumn('rhk_pimpinan', 'rencana_hasil_kerja')) {
                    $table->dropColumn('rencana_hasil_kerja');
                }
                if (Schema::hasColumn('rhk_pimpinan', 'unit_kerja')) {
                    $table->dropColumn('unit_kerja');
                }
                if (Schema::hasColumn('rhk_pimpinan', 'created_by')) {
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('rhk_pimpinan', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }

                // Add new columns
                if (!Schema::hasColumn('rhk_pimpinan', 'sasaran_kegiatan_id')) {
                    $table->foreignId('sasaran_kegiatan_id')->after('id')
                        ->constrained('sasaran_kegiatan')->onDelete('cascade');
                }

                if (!Schema::hasColumn('rhk_pimpinan', 'rhk_pimpinan')) {
                    $table->text('rhk_pimpinan')->after('sasaran_kegiatan_id')
                        ->comment('Rencana Hasil Kerja Pimpinan yang di Intervensi');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rhk_pimpinan')) {
            Schema::table('rhk_pimpinan', function (Blueprint $table) {
                // Restore old structure
                if (Schema::hasColumn('rhk_pimpinan', 'sasaran_kegiatan_id')) {
                    $table->dropForeign(['sasaran_kegiatan_id']);
                    $table->dropColumn('sasaran_kegiatan_id');
                }

                if (Schema::hasColumn('rhk_pimpinan', 'rhk_pimpinan')) {
                    $table->dropColumn('rhk_pimpinan');
                }

                // Add back old columns
                $table->text('rencana_hasil_kerja')->after('id');
                $table->string('unit_kerja')->nullable()->after('rencana_hasil_kerja');
                $table->unsignedBigInteger('created_by')->after('status');
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }
};
