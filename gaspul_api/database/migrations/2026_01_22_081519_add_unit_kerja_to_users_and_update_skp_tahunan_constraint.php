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
        // Add unit_kerja to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('unit_kerja')->nullable()->after('nip')
                ->comment('Unit kerja tempat ASN ditempatkan');
        });

        // Update unique constraint on skp_tahunan table
        // Drop old constraint and create new one
        Schema::table('skp_tahunan', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique('unique_skp_tahunan_per_user');

            // Add new unique constraint: 1 ASN bisa punya BANYAK SKP Tahunan
            // tapi TIDAK BOLEH duplikat untuk sasaran_kegiatan yang sama di tahun yang sama
            $table->unique(['user_id', 'sasaran_kegiatan_id', 'tahun'], 'unique_skp_per_sasaran_per_tahun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove unit_kerja from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('unit_kerja');
        });

        // Revert unique constraint
        Schema::table('skp_tahunan', function (Blueprint $table) {
            $table->dropUnique('unique_skp_per_sasaran_per_tahun');
            $table->unique(['user_id', 'sasaran_kegiatan_id', 'tahun'], 'unique_skp_tahunan_per_user');
        });
    }
};
