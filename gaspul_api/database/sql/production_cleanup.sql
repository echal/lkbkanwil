-- ============================================
-- PRODUCTION DATABASE CLEANUP SCRIPT
-- ============================================
-- Purpose: Fix partially migrated database
-- Error: skp_tahunan_backup_v1 already exists
-- Safe to run: YES (only drops backup tables)
-- ============================================

-- Step 1: Drop backup tables that block migration
DROP TABLE IF EXISTS skp_tahunan_backup_v1;
DROP TABLE IF EXISTS skp_tahunan_detail_backup_v1;

-- Step 2: Remove failed migration entry
DELETE FROM migrations
WHERE migration = '2026_01_25_000000_total_refactor_skp_system';

-- Step 3: Verify cleanup
SELECT 'Cleanup completed. Run: php artisan migrate --force' AS status;

-- ============================================
-- VERIFICATION QUERIES (Optional)
-- ============================================

-- Check remaining tables
-- SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME;

-- Check migrations
-- SELECT id, migration, batch FROM migrations ORDER BY id DESC LIMIT 10;

-- ============================================
-- END OF CLEANUP SCRIPT
-- ============================================
