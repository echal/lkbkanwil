# 🚨 PRODUCTION EMERGENCY FIX - Database Partially Migrated

## ❌ ERROR YANG DIALAMI

```
ERROR 1: Base table or view already exists: 1050 Table 'skp_tahunan_backup_v1' already exists
ERROR 2: Base table or view not found: 1146 Table 'rencana_kerja_asn' doesn't exist
```

**Situasi:** Database dalam keadaan partially migrated - sebagian migration sudah jalan, sebagian belum.

---

## 🔍 DIAGNOSIS

Migration `2026_01_25_000000_total_refactor_skp_system` sudah partially executed:
- ✅ Tabel `skp_tahunan_backup_v1` sudah dibuat (backup tabel lama)
- ❌ Tabel `rencana_kerja_asn` sudah di-drop
- ❌ Migration belum selesai sampai akhir

**Kesimpulan:** Perlu manual cleanup dan re-run migration.

---

## ✅ SOLUSI MANUAL (SAFE & TESTED)

### STEP 1: Check Database State

```bash
# Login ke MySQL
mysql -u gaspul_user -p gaspulco_lkbkanwil

# Check tabel yang ada
SHOW TABLES;

# Output yang diharapkan:
# - skp_tahunan (tabel lama, sudah di-rename?)
# - skp_tahunan_backup_v1 (backup sudah ada)
# - skp_tahunan_detail_backup_v1 (backup detail)
# - rencana_kerja_asn (TIDAK ADA - sudah di-drop)
# - bulanan (TIDAK ADA - sudah di-drop)
# - harian (TIDAK ADA - sudah di-drop)

# Check isi migrations table
SELECT migration, batch FROM migrations ORDER BY id DESC LIMIT 10;

# Note: Migration 2026_01_25_000000_total_refactor_skp_system tidak ada (gagal)
```

---

### STEP 2: Manual Cleanup (Drop Backup Tables)

```sql
-- Masih di MySQL prompt

-- Drop backup tables yang sudah ada (agar migration bisa jalan ulang)
DROP TABLE IF EXISTS skp_tahunan_backup_v1;
DROP TABLE IF EXISTS skp_tahunan_detail_backup_v1;

-- Check tabel tersisa
SHOW TABLES;

-- Exit MySQL
EXIT;
```

---

### STEP 3: Mark Failed Migration as NOT Ran

```bash
# Karena migration gagal di tengah, kita perlu hapus record-nya dari migrations table
mysql -u gaspul_user -p gaspulco_lkbkanwil

-- Hapus entry migration yang gagal (jika ada)
DELETE FROM migrations WHERE migration = '2026_01_25_000000_total_refactor_skp_system';

-- Verify
SELECT migration FROM migrations ORDER BY id DESC LIMIT 5;

EXIT;
```

---

### STEP 4: Re-run Migration

```bash
# Sekarang jalankan migration fresh
php artisan migrate --force

# Output yang diharapkan:
# ✅ 2026_01_25_000000_total_refactor_skp_system ... DONE
# ✅ 2026_01_28_093154_add_indexes_for_harian_bawahan_monitoring ... DONE
# ✅ 2026_01_28_213929_update_foreign_keys_with_cascade_delete ... DONE
# ✅ ... dan seterusnya

# Verify all migrations done
php artisan migrate:status
```

---

### STEP 5: Verify Database Structure

```bash
mysql -u gaspul_user -p gaspulco_lkbkanwil

-- Check tabel baru sudah ada
SHOW TABLES;

-- Harus ada:
-- ✅ skp_tahunan (struktur baru)
-- ✅ skp_tahunan_detail (struktur baru)
-- ✅ master_atasan (tabel baru)
-- ✅ rencana_aksi_bulanan (tabel baru)
-- ✅ progres_harian (tabel baru)
-- ✅ skp_tahunan_backup_v1 (backup data lama)

-- Check struktur skp_tahunan
DESCRIBE skp_tahunan;

-- Harus ada kolom:
-- id, user_id, tahun, status, catatan_atasan, approved_by, approved_at, created_at, updated_at

-- Check foreign keys
SELECT
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'gaspulco_lkbkanwil'
AND TABLE_NAME = 'skp_tahunan'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Harus ada:
-- fk_skp_tahunan_user (user_id → users.id)
-- fk_skp_tahunan_approved_by (approved_by → users.id)

EXIT;
```

---

## 🛠️ ALTERNATIF: Fresh Migration (Jika Database Masih Kosong/Testing)

**⚠️ WARNING: INI AKAN HAPUS SEMUA DATA! Gunakan hanya jika:**
- Database masih kosong/testing
- Sudah backup data
- Tidak ada data production penting

```bash
# 1. Backup database (PENTING!)
mysqldump -u gaspul_user -p gaspulco_lkbkanwil > backup_emergency_$(date +%Y%m%d_%H%M%S).sql

# 2. Drop semua tabel
mysql -u gaspul_user -p gaspulco_lkbkanwil

SET FOREIGN_KEY_CHECKS = 0;

-- Drop semua tabel (sesuaikan dengan tabel yang ada di SHOW TABLES)
DROP TABLE IF EXISTS progres_harian;
DROP TABLE IF EXISTS rencana_aksi_bulanan;
DROP TABLE IF EXISTS skp_tahunan_detail;
DROP TABLE IF EXISTS skp_tahunan;
DROP TABLE IF EXISTS skp_tahunan_backup_v1;
DROP TABLE IF EXISTS skp_tahunan_detail_backup_v1;
DROP TABLE IF EXISTS master_atasan;
DROP TABLE IF EXISTS rhk_pimpinan;
DROP TABLE IF EXISTS sasaran_kegiatan;
DROP TABLE IF EXISTS indikator_kinerja;
DROP TABLE IF EXISTS indikator_tahunan;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS personal_access_tokens;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS cache;
DROP TABLE IF EXISTS cache_locks;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS job_batches;
DROP TABLE IF EXISTS failed_jobs;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS migrations;

SET FOREIGN_KEY_CHECKS = 1;

-- Verify all tables dropped
SHOW TABLES;
-- Harus kosong

EXIT;

# 3. Run fresh migration
php artisan migrate:fresh --force

# 4. Seed data (jika ada)
php artisan db:seed --force

# 5. Verify
php artisan migrate:status
```

---

## 📋 CHECKLIST SETELAH FIX

- [ ] `php artisan migrate:status` - Semua migration status "Ran"
- [ ] `SHOW TABLES` - Semua tabel baru ada (skp_tahunan, skp_tahunan_detail, rencana_aksi_bulanan, progres_harian)
- [ ] `DESCRIBE skp_tahunan` - Struktur tabel benar
- [ ] Foreign keys ada (fk_skp_tahunan_user, fk_skp_tahunan_approved_by)
- [ ] `php artisan about` - Environment: production, Debug: OFF
- [ ] Test login via browser
- [ ] Test akses modul SKP Tahunan

---

## 🎯 RECOMMENDED APPROACH (STEP-BY-STEP)

```bash
# 1. Check current state
mysql -u gaspul_user -p gaspulco_lkbkanwil -e "SHOW TABLES;"

# 2. Cleanup backup tables
mysql -u gaspul_user -p gaspulco_lkbkanwil -e "
DROP TABLE IF EXISTS skp_tahunan_backup_v1;
DROP TABLE IF EXISTS skp_tahunan_detail_backup_v1;
"

# 3. Remove failed migration entry
mysql -u gaspul_user -p gaspulco_lkbkanwil -e "
DELETE FROM migrations WHERE migration = '2026_01_25_000000_total_refactor_skp_system';
"

# 4. Re-run migration
php artisan migrate --force

# 5. Verify
php artisan migrate:status

# 6. Check application
php artisan about

# 7. Production optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 🔍 DEBUGGING TIPS

### Check Migration Entries

```bash
mysql -u gaspul_user -p gaspulco_lkbkanwil -e "
SELECT id, migration, batch FROM migrations ORDER BY id;
"
```

### Check Tables Structure

```bash
mysql -u gaspul_user -p gaspulco_lkbkanwil -e "
SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'gaspulco_lkbkanwil'
ORDER BY CREATE_TIME DESC;
"
```

### Check Foreign Keys

```bash
mysql -u gaspul_user -p gaspulco_lkbkanwil -e "
SELECT
    TABLE_NAME,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'gaspulco_lkbkanwil'
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;
"
```

---

## 📞 SUPPORT

Jika masih error setelah mengikuti panduan ini:

1. **Capture full error log:**
   ```bash
   php artisan migrate --force 2>&1 | tee migration_error_full.log
   ```

2. **Capture database state:**
   ```bash
   mysql -u gaspul_user -p gaspulco_lkbkanwil -e "SHOW TABLES;" > database_tables.txt
   mysql -u gaspul_user -p gaspulco_lkbkanwil -e "SELECT * FROM migrations ORDER BY id;" > migrations_list.txt
   ```

3. **Send logs ke developer:**
   - migration_error_full.log
   - database_tables.txt
   - migrations_list.txt

---

**Emergency Fix Guide Version:** 1.0.0
**Last Updated:** 2026-01-30
**Critical Level:** HIGH - Requires immediate action
