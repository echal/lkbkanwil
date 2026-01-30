# 📤 PANDUAN UPLOAD DATABASE VIA PHPMYADMIN (cPanel)

**Error yang Anda alami:**
```
#1044 - Access denied for user 'gaspulco'@'localhost' to database 'bmnkanwil'
```

**Penyebab:** File SQL dump mengandung statement `CREATE DATABASE` yang tidak diizinkan.

**Solusi:** Export ulang database TANPA statement `CREATE DATABASE`.

---

## ✅ STEP 1: Export Database CLEAN (Localhost)

### Via mysqldump (RECOMMENDED)

```bash
# Di folder gaspul
cd c:\xampp\htdocs\gaspul

# Export database tanpa CREATE DATABASE
mysqldump -u root gaspul_api --no-create-db --skip-add-drop-database > gaspul_production_ready.sql
```

**File yang dihasilkan:** `gaspul_production_ready.sql` (sekitar 50-100 KB)

✅ **File sudah tersedia:** `c:\xampp\htdocs\gaspul\gaspul_production_ready_clean.sql` (64 KB)

---

### ATAU Via phpMyAdmin Localhost (Alternative)

1. Buka http://localhost/phpmyadmin
2. Pilih database **gaspul_api** di sidebar kiri
3. Klik tab **"Export"**
4. Pilih **"Custom"** export method
5. **PENTING:** Di bagian **"Object creation options"**, UNCHECK:
   - ❌ `CREATE DATABASE / USE statement`
   - ✅ `CREATE TABLE` (tetap dicentang)
   - ✅ `IF NOT EXISTS` (dicentang untuk safety)
6. Format: **SQL**
7. Klik **"Export"**

**File yang didownload:** `gaspul_api.sql`

---

## ✅ STEP 2: Bersihkan Database Production (phpMyAdmin cPanel)

### 2.1 Login ke cPanel

```
URL: https://cpanel.your-hosting.com
Username: gaspulco
Password: (password cPanel Anda)
```

### 2.2 Buka phpMyAdmin

1. Cari icon **"phpMyAdmin"** di cPanel
2. Klik untuk membuka
3. Di sidebar kiri, pilih database **gaspulco_lkbkanwil**

### 2.3 Drop Semua Tabel (Clean Slate)

**Option A: Via GUI (Click-Click)**

1. Klik tab **"Structure"**
2. Scroll ke bawah
3. Klik **"Check All"** (centang semua tabel)
4. Di dropdown **"With selected:"**, pilih **"Drop"**
5. Konfirmasi **"Yes"**
6. Refresh → Harus muncul "No tables found in database"

**Option B: Via SQL (Lebih Cepat)**

1. Klik tab **"SQL"**
2. Copy-paste script ini:

```sql
SET FOREIGN_KEY_CHECKS = 0;

-- Drop semua tabel yang ada
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
DROP TABLE IF EXISTS rencana_kerja_asn;
DROP TABLE IF EXISTS bulanan;
DROP TABLE IF EXISTS harian;
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
```

3. Klik **"Go"**
4. Verify: Tab **"Structure"** → "No tables found in database"

---

## ✅ STEP 3: Import Database Production-Ready

### 3.1 Prepare File

**File yang akan di-upload:**
- Dari localhost: `gaspul_production_ready_clean.sql` (64 KB)
- ATAU `gaspul_production_ready.sql` (jika Anda export ulang)

**⚠️ PENTING: Pastikan file TIDAK mengandung:**
```sql
CREATE DATABASE IF NOT EXISTS `bmnkanwil` ...  ❌ SALAH
CREATE DATABASE IF NOT EXISTS `gaspul_api` ... ❌ SALAH
USE `bmnkanwil` ...                            ❌ SALAH
```

**✅ File yang BENAR hanya mengandung:**
```sql
CREATE TABLE `cache` ( ... );               ✅ BENAR
CREATE TABLE `users` ( ... );               ✅ BENAR
INSERT INTO `users` VALUES ( ... );         ✅ BENAR
```

### 3.2 Import via phpMyAdmin

1. Masih di phpMyAdmin, database **gaspulco_lkbkanwil** terpilih
2. Klik tab **"Import"**
3. Klik **"Choose File"**
4. Pilih file: `gaspul_production_ready_clean.sql`
5. Settings:
   - **Format:** SQL
   - **Character set:** utf8mb4_unicode_ci
   - **Format-specific options:**
     - ✅ Allow interrupt of import
     - Partial import: OFF (biarkan kosong)
6. Scroll ke bawah
7. Klik **"Import"**

### 3.3 Tunggu Proses Import

**Progress indicator akan muncul:**
```
Importing into the current server
...
```

**Jika file besar (>2MB), upload mungkin butuh beberapa menit.**

### 3.4 Success Message

```
✓ Import has been successfully finished
✓ XX queries executed
```

---

## ✅ STEP 4: Verify Database

### 4.1 Check Tables

1. Refresh database di sidebar kiri (klik icon refresh atau F5)
2. Expand database **gaspulco_lkbkanwil**
3. Harus muncul tabel-tabel:

```
✅ cache
✅ cache_locks
✅ failed_jobs
✅ indikator_kinerja (atau rhk_pimpinan)
✅ job_batches
✅ jobs
✅ master_atasan
✅ migrations
✅ personal_access_tokens
✅ progres_harian
✅ rencana_aksi_bulanan
✅ sasaran_kegiatan
✅ sessions
✅ skp_tahunan
✅ skp_tahunan_detail
✅ units
✅ users
```

### 4.2 Check Data

Klik tab **"SQL"**, jalankan query ini:

```sql
-- Check jumlah data
SELECT 'users' as tabel, COUNT(*) as jumlah FROM users
UNION ALL
SELECT 'migrations', COUNT(*) FROM migrations
UNION ALL
SELECT 'sasaran_kegiatan', COUNT(*) FROM sasaran_kegiatan
UNION ALL
SELECT 'indikator_kinerja', COUNT(*) FROM indikator_kinerja;
```

**Expected output:**
- users: minimal 1+ (ada user admin/test)
- migrations: 32-35 rows (all migration records)
- sasaran_kegiatan: sesuai data master Anda
- indikator_kinerja: sesuai data master Anda

### 4.3 Check Sample User

```sql
-- Check user pertama
SELECT id, name, email, role FROM users LIMIT 5;
```

**Harus muncul data user** (admin, test, dll.)

---

## ✅ STEP 5: Update .env di Server Production

### Via SSH

```bash
ssh gaspulco@s3282
cd /home/gaspulco/public_html/gaspul_api
nano .env
```

### Via cPanel File Manager

1. Di cPanel, buka **"File Manager"**
2. Navigate: `/home/gaspulco/public_html/gaspul_api`
3. Cari file `.env`
4. Klik kanan → **"Edit"**

### Update Database Credentials

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gaspulco_lkbkanwil
DB_USERNAME=gaspul_user
DB_PASSWORD=actual_password_here  # ⚠️ PASSWORD YANG BENAR!
```

**Save** (Ctrl+X, Y, Enter untuk nano)

---

## ✅ STEP 6: Test Koneksi Database

```bash
# Via SSH
cd /home/gaspulco/public_html/gaspul_api

# Test via artisan
php artisan tinker

# Di tinker:
>>> \DB::connection()->getPdo();
# Harus muncul: PDO object

>>> \DB::table('users')->count();
# Harus muncul: angka (jumlah users)

>>> \DB::table('migrations')->count();
# Harus muncul: 32-35 (jumlah migrations)

>>> exit
```

**ATAU via MySQL:**

```bash
mysql -u gaspul_user -p gaspulco_lkbkanwil

# Di MySQL prompt:
SELECT COUNT(*) FROM users;
# Harus muncul: angka

SHOW TABLES;
# Harus muncul: 15-20 tabel

EXIT;
```

---

## ✅ STEP 7: Clear Cache & Optimize

```bash
cd /home/gaspulco/public_html/gaspul_api

# Clear old caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Create production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Verify application
php artisan about
```

**Expected output:**
```
Environment ........... production
Debug Mode ............ OFF
Database .............. mysql (gaspulco_lkbkanwil)
```

---

## ✅ STEP 8: Test via Browser

```
1. Buka: https://lkh.kemenag-sulbar.go.id (atau domain Anda)
2. Harus muncul: Halaman Login
3. Login dengan credentials dari localhost:
   - Username: admin (atau sesuai data Anda)
   - Password: password_dari_localhost
4. Test fitur:
   - Dashboard
   - Master Data
   - SKP Tahunan
   - Kinerja Harian
   - PDF Generation
```

---

## 🔧 TROUBLESHOOTING

### Error: "Max upload size exceeded"

**Solusi 1: Split File SQL (jika >8MB)**

```bash
# Split file menjadi 5MB chunks
split -b 5M gaspul_production_ready.sql gaspul_part_

# Upload satu per satu:
# - gaspul_part_aa
# - gaspul_part_ab
# - gaspul_part_ac
```

**Solusi 2: Upload via cPanel Terminal/SSH**

```bash
# Upload file via FTP/SFTP ke server dulu
# Lalu import via command line:

ssh gaspulco@s3282
cd /home/gaspulco
mysql -u gaspul_user -p gaspulco_lkbkanwil < gaspul_production_ready.sql
```

---

### Error: "Foreign key constraint fails"

**Penyebab:** Tabel di-import dalam urutan yang salah.

**Solusi:** Edit file SQL, tambahkan di awal:

```sql
SET FOREIGN_KEY_CHECKS = 0;

-- Semua CREATE TABLE dan INSERT di sini...

SET FOREIGN_KEY_CHECKS = 1;
```

---

### Error: "Table already exists"

**Penyebab:** Database belum bersih (ada tabel yang tertinggal).

**Solusi:** Drop semua tabel dulu (lihat STEP 2.3)

---

### Database Imported tapi Login Gagal

**Penyebab:** User tidak ada atau password salah.

**Check user:**

```sql
SELECT id, name, email, role FROM users;
```

**Jika tidak ada user, create manual:**

```sql
INSERT INTO users (name, email, password, role, created_at, updated_at)
VALUES (
    'Admin',
    'admin@kemenag.go.id',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5iGCYKBbBbNji',  -- password: admin123
    'ADMIN',
    NOW(),
    NOW()
);
```

---

## 📋 CHECKLIST

Setelah semua step selesai:

- [ ] File SQL di-export TANPA `CREATE DATABASE`
- [ ] Database production di-clean (semua tabel di-drop)
- [ ] File SQL berhasil di-import (success message)
- [ ] Semua tabel ada (15-20 tabel)
- [ ] Data ada (users, migrations, master data)
- [ ] .env credentials updated
- [ ] `php artisan about` - Database connected
- [ ] Caches cleared & optimized
- [ ] Login via browser berhasil
- [ ] Fitur aplikasi berjalan normal

---

## 🎯 SUMMARY

**Quick Steps:**
1. ✅ Export DB localhost: `mysqldump -u root gaspul_api --no-create-db > gaspul.sql`
2. ✅ Drop tabel production (phpMyAdmin → SQL → DROP TABLE)
3. ✅ Import file SQL (phpMyAdmin → Import → gaspul.sql)
4. ✅ Update .env credentials
5. ✅ Clear cache & optimize
6. ✅ Test login

**File Ready:** `c:\xampp\htdocs\gaspul\gaspul_production_ready_clean.sql` (64 KB)

**Upload file ini ke phpMyAdmin cPanel!**

---

**Last Updated:** 2026-01-30
**Status:** ✅ READY TO UPLOAD
