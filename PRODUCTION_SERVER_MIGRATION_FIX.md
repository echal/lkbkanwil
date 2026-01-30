# 🔧 PRODUCTION SERVER MIGRATION FIX

**Error yang Dialami:**
```
SQLSTATE[HY000]: General error: 1005 Can't create table `gaspulco_lkbkanwil`.`skp_tahunan`
(errno: 121 "Duplicate key on write or update")
```

**Penyebab:**
Migration `2026_01_25_000000_total_refactor_skp_system.php` mencoba membuat foreign key `fk_skp_tahunan_user` yang sudah ada dari migrasi sebelumnya.

---

## ✅ SOLUSI LANGSUNG (PRODUCTION SERVER)

### Option 1: Rollback Migration yang Gagal

```bash
# 1. Login ke server production
ssh gaspulco@s3282

# 2. Masuk ke folder aplikasi
cd /path/to/gaspul_api

# 3. Rollback 1 migration terakhir yang gagal
php artisan migrate:rollback --step=1 --force

# 4. Drop foreign key constraint yang duplikat secara manual
mysql -u gaspul_user -p gaspulco_lkbkanwil

# Di MySQL prompt:
ALTER TABLE skp_tahunan DROP FOREIGN KEY fk_skp_tahunan_user;
ALTER TABLE skp_tahunan DROP FOREIGN KEY fk_skp_tahunan_approved_by;
EXIT;

# 5. Jalankan ulang migration
php artisan migrate --force
```

---

### Option 2: Skip Migration yang Bermasalah (TIDAK DIREKOMENDASIKAN)

```bash
# Mark migration as done without running
php artisan migrate:install  # Ensure migrations table exists

# Manual insert to migrations table
mysql -u gaspul_user -p gaspulco_lkbkanwil

INSERT INTO migrations (migration, batch)
VALUES ('2026_01_25_000000_total_refactor_skp_system', 2);
EXIT;

# Continue with remaining migrations
php artisan migrate --force
```

---

### Option 3: Fresh Migration dengan Backup (RECOMMENDED untuk Fresh Install)

**⚠️ WARNING: Ini akan MENGHAPUS semua data! Gunakan hanya jika database masih kosong atau sudah backup.**

```bash
# 1. Backup database dulu
mysqldump -u gaspul_user -p gaspulco_lkbkanwil > backup_before_fix_$(date +%Y%m%d_%H%M%S).sql

# 2. Drop semua tabel
mysql -u gaspul_user -p gaspulco_lkbkanwil

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS personal_access_tokens;
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
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS cache;
DROP TABLE IF EXISTS cache_locks;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS job_batches;
DROP TABLE IF EXISTS failed_jobs;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS migrations;
SET FOREIGN_KEY_CHECKS = 1;
EXIT;

# 3. Jalankan migration fresh
php artisan migrate:fresh --force

# 4. Jalankan seeder jika ada
php artisan db:seed --force
```

---

## 🛠️ SOLUSI PERMANEN (UPDATE KODE)

Saya telah membuat migration fix baru yang otomatis mengatasi masalah ini:

**File:** `database/migrations/2026_01_30_000000_fix_duplicate_foreign_key_constraint.php`

Migration ini akan:
1. Detect foreign key yang sudah ada di tabel `skp_tahunan`
2. Drop foreign key lama
3. Recreate dengan nama yang benar
4. Handle error jika foreign key sudah ada

**Cara Menggunakan:**

```bash
# 1. Pull update terbaru dari GitHub
git pull origin main

# 2. Jalankan migration (fix akan otomatis)
php artisan migrate --force
```

---

## 📋 CHECKLIST SETELAH FIX

Setelah berhasil migration, verifikasi:

```bash
# 1. Check migration status
php artisan migrate:status

# 2. Verify semua tabel ada
mysql -u gaspul_user -p gaspulco_lkbkanwil

SHOW TABLES;

# Harus ada:
# - users
# - sasaran_kegiatan
# - indikator_kinerja (atau rhk_pimpinan)
# - skp_tahunan
# - skp_tahunan_detail
# - master_atasan
# - rencana_aksi_bulanan
# - progres_harian
# - units
# - sessions
# - cache
# - jobs

# 3. Check foreign keys di skp_tahunan
SELECT
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'gaspulco_lkbkanwil'
AND TABLE_NAME = 'skp_tahunan'
AND REFERENCED_TABLE_NAME IS NOT NULL;

# Harus ada:
# - fk_skp_tahunan_user (user_id → users.id)
# - fk_skp_tahunan_approved_by (approved_by → users.id)

EXIT;

# 4. Test aplikasi
php artisan about
php artisan route:list | grep skp
```

---

## 🔍 TROUBLESHOOTING TAMBAHAN

### Error: Access denied for user 'gaspul_user'@'localhost'

```bash
# Check MySQL user privileges
mysql -u root -p

SELECT User, Host FROM mysql.user WHERE User = 'gaspul_user';
SHOW GRANTS FOR 'gaspul_user'@'localhost';

# Jika user tidak ada, buat user baru
CREATE USER 'gaspul_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON gaspulco_lkbkanwil.* TO 'gaspul_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Update .env dengan password yang benar
vi .env
# DB_PASSWORD=your_secure_password
```

### Error: Database 'gaspulco_lkbkanwil' doesn't exist

```bash
# Create database
mysql -u root -p

CREATE DATABASE gaspulco_lkbkanwil CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SHOW DATABASES;
EXIT;

# Jalankan migration
php artisan migrate --force
```

---

## 📞 KONTAK SUPPORT

Jika masih error setelah mengikuti panduan ini:

1. **Capture error lengkap:**
   ```bash
   php artisan migrate --force 2>&1 | tee migration_error.log
   ```

2. **Check database state:**
   ```bash
   mysql -u gaspul_user -p gaspulco_lkbkanwil -e "SHOW TABLES;"
   ```

3. **Send log ke developer**

---

## 📝 NOTES

- **JANGAN** jalankan `migrate:fresh` di production jika sudah ada data!
- **SELALU** backup database sebelum migration
- **VERIFIKASI** .env database credentials sebelum migration
- **TEST** di staging server dulu jika memungkinkan

---

**Last Updated:** 2026-01-30
**Author:** Lead Software Architect
