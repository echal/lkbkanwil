# ⚡ QUICK FIX COMMANDS - Production Server

**Copy-paste langsung ke terminal production server (s3282)**

---

## 🚨 CURRENT ERROR

```
Base table or view already exists: 1050 Table 'skp_tahunan_backup_v1' already exists
```

---

## ✅ FIX (3 Commands Only)

### Command 1: Run Cleanup SQL

```bash
mysql -u gaspul_user -p gaspulco_lkbkanwil < gaspul_api/database/sql/production_cleanup.sql
```

**Apa yang dilakukan:**
- Drop tabel backup yang menghalangi migration
- Hapus entry migration yang gagal
- Output: "Cleanup completed"

---

### Command 2: Re-run Migration

```bash
cd gaspul_api
php artisan migrate --force
```

**Output yang diharapkan:**
```
✅ 2026_01_25_000000_total_refactor_skp_system ... DONE
✅ 2026_01_28_093154_add_indexes_for_harian_bawahan_monitoring ... DONE
✅ 2026_01_28_213929_update_foreign_keys_with_cascade_delete ... DONE
✅ 2026_01_28_221736_add_unique_index_to_users_nip_column ... DONE
✅ 2026_01_28_234628_add_unit_kerja_id_to_rhk_pimpinan_table ... DONE
✅ 2026_01_29_203733_consolidate_rhk_architecture_rename_column ... DONE
✅ 2026_01_30_000000_fix_duplicate_foreign_key_constraint ... DONE
```

---

### Command 3: Verify & Optimize

```bash
php artisan migrate:status
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan about
```

**Output yang diharapkan:**
```
Environment: production
Debug Mode: OFF
Database: mysql (gaspulco_lkbkanwil)
```

---

## 🎯 ALL-IN-ONE COMMAND (Copy-Paste 1x)

```bash
cd /home/gaspulco/public_html && \
mysql -u gaspul_user -p gaspulco_lkbkanwil < gaspul_api/database/sql/production_cleanup.sql && \
cd gaspul_api && \
php artisan migrate --force && \
php artisan config:cache && \
php artisan route:cache && \
php artisan view:cache && \
php artisan optimize && \
echo "✅ FIX COMPLETED - Check output above"
```

**Masukkan password MySQL ketika diminta.**

---

## 📋 VERIFICATION

Setelah command selesai, check:

```bash
# 1. Migration status
php artisan migrate:status
# Semua harus "Ran"

# 2. Database tables
mysql -u gaspul_user -p gaspulco_lkbkanwil -e "SHOW TABLES;"
# Harus ada: skp_tahunan, skp_tahunan_detail, rencana_aksi_bulanan, progres_harian

# 3. Application status
php artisan about
# Environment: production, Debug Mode: OFF

# 4. Test via browser
# https://lkh.kemenag-sulbar.go.id (atau domain yang digunakan)
```

---

## ❌ IF STILL ERROR

**Error: "Access denied for user 'gaspul_user'"**

```bash
# Fix user credentials
mysql -u root -p

CREATE USER IF NOT EXISTS 'gaspul_user'@'localhost' IDENTIFIED BY 'NEW_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON gaspulco_lkbkanwil.* TO 'gaspul_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Update .env
nano .env
# Change: DB_PASSWORD=NEW_PASSWORD_HERE
```

---

**Error: "Migration still fails"**

Baca panduan lengkap: `PRODUCTION_EMERGENCY_FIX.md`

Atau hubungi developer dengan log:

```bash
php artisan migrate --force 2>&1 | tee migration_error.log
# Send file migration_error.log
```

---

## 📞 CONTACT

**Emergency?** Read: `PRODUCTION_EMERGENCY_FIX.md`
**Full Guide?** Read: `PANDUAN_DEPLOYMENT_PRODUCTION_SERVER.md`

---

**Last Updated:** 2026-01-30
**Tested On:** Server s3282 (gaspulco)
