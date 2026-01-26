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
        Schema::table('users', function (Blueprint $table) {
            // Add unit_id foreign key (nullable for transition period)
            $table->foreignId('unit_id')->nullable()->after('nip')->constrained('units')->onDelete('restrict');

            // Add jabatan and status fields
            $table->string('jabatan')->nullable()->after('unit_id');
            $table->enum('status', ['AKTIF', 'NONAKTIF'])->default('AKTIF')->after('jabatan');

            // Add index for better query performance
            $table->index('unit_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropIndex(['unit_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['unit_id', 'jabatan', 'status']);
        });
    }
};
