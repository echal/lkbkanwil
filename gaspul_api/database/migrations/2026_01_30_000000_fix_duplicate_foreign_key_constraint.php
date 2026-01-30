<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * FIX: Duplicate Foreign Key Constraint Error
 *
 * Error: errno 121 "Duplicate key on write or update"
 * Penyebab: Foreign key fk_skp_tahunan_user sudah ada dari migrasi sebelumnya
 *
 * Solusi: Drop foreign key lama sebelum membuat yang baru
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if skp_tahunan table exists
        if (Schema::hasTable('skp_tahunan')) {
            // Get all foreign keys for skp_tahunan table
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'skp_tahunan'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");

            // Drop all existing foreign keys
            Schema::table('skp_tahunan', function (Blueprint $table) use ($foreignKeys) {
                foreach ($foreignKeys as $fk) {
                    try {
                        $table->dropForeign($fk->CONSTRAINT_NAME);
                    } catch (\Exception $e) {
                        // Continue if foreign key doesn't exist
                    }
                }
            });

            // Recreate foreign keys with correct names
            Schema::table('skp_tahunan', function (Blueprint $table) {
                // Check if user_id column exists
                if (Schema::hasColumn('skp_tahunan', 'user_id')) {
                    try {
                        $table->foreign('user_id', 'fk_skp_tahunan_user')
                            ->references('id')->on('users')->onDelete('cascade');
                    } catch (\Exception $e) {
                        // Foreign key might already exist
                    }
                }

                // Check if approved_by column exists
                if (Schema::hasColumn('skp_tahunan', 'approved_by')) {
                    try {
                        $table->foreign('approved_by', 'fk_skp_tahunan_approved_by')
                            ->references('id')->on('users')->onDelete('set null');
                    } catch (\Exception $e) {
                        // Foreign key might already exist
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed - foreign keys remain
    }
};
