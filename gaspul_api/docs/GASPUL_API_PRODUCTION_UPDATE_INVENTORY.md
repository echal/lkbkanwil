# GASPUL_API — PRODUCTION UPDATE INVENTORY
**Tanggal:** 2026-06-17  
**Mode:** AUDIT ONLY — tidak ada perubahan kode  
**Scope:** Seluruh perubahan gaspul_api terkait integrasi e-SARAku Helpdesk (Phase I–K.3)  
**Cakupan:** SSO Token, Floating Button, Embedded Chat Widget, Runtime Isolation, Security Hardening

---

## RINGKASAN EKSEKUTIF

| Metrik | Nilai |
|--------|-------|
| **File MANDATORY deploy** | **9 file** |
| **Migration baru** | **0 migration** |
| **Config env baru** | **1 key** (`HELPDESK_URL`) |
| **Config modified** | **2 file** (`config/services.php`, `config/cors.php`) |
| **Estimasi waktu deploy** | **15–30 menit** |
| **Estimasi downtime ASN** | **< 5 menit** (config:cache + route:cache) |
| **Risiko deploy** | **Low** |

---

## STEP 1 — GIT CHANGE INVENTORY

### Status Working Tree (Audit per 2026-06-17)

```
git status --porcelain gaspul_api/
 M = modified (ada di git, ada perubahan lokal yang belum di-commit)
 ?? = untracked (file baru, belum pernah di-commit)
```

**Total modified (M):** 33 file  
**Total untracked (??):** 28+ entry

**Dari semua ini, yang TERKAIT HELPDESK/SSO:**

---

### A. Source Code — PHP

#### Untracked (File Baru, Belum di-commit)

| File | Phase | Fungsi |
|------|-------|--------|
| `app/Http/Controllers/Api/HelpdeskTokenController.php` | I.2 / J | SSO token generator — endpoint `POST /api/helpdesk-token` |
| `app/Http/Middleware/PinMysqlDatabase.php` | I.2A-B | Middleware runtime isolation — pin DB connection ke gaspulco_lkbkanwil_db |
| `app/Models/PersonalAccessToken.php` | I.2A-B / J | Override Sanctum PAT — `USE gaspulco_lkbkanwil_db` di `findToken()` |

#### Modified (File Sudah di-commit, Ada Perubahan)

| File | Phase | Perubahan Terkait Helpdesk |
|------|-------|--------------------------|
| `app/Http/Controllers/Api/AuthController.php` | I.2 / J-05/J-06 | `me()` endpoint: single-use helpdesk-sso token, DB row-lock race-proof delete |
| `app/Providers/AppServiceProvider.php` | I.2A-B | `Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class)` |
| `bootstrap/app.php` | I.2A-B | `$middleware->append(PinMysqlDatabase::class)` — global middleware |
| `config/services.php` | I.2 | +`helpdesk.url` via `env('HELPDESK_URL', ...)` |
| `config/cors.php` | I.2 | +`http://localhost` dan `http://localhost:8000` di allowed_origins |
| `public/index.php` | I.2A-B | +`Env::disablePutenv()` — PHP env bleeding fix |
| `routes/web.php` | I.2 | +`POST /api/helpdesk-token` route dengan middleware `role:ASN, throttle:10,1` |

---

### B. Blade Views

#### Untracked (File Baru, Belum di-commit)

| File | Phase | Fungsi |
|------|-------|--------|
| `resources/views/components/helpdesk-floating-button.blade.php` | I.2 | FAB (Floating Action Button) chat biru di pojok kanan bawah — hanya ASN |
| `resources/views/components/helpdesk-chat-widget.blade.php` | I.2A | Embedded chat widget + modal Pusat Bantuan dengan 3 opsi: FAQ, Tiket, Live Chat |

#### Modified

| File | Phase | Perubahan Terkait Helpdesk |
|------|-------|--------------------------|
| `resources/views/layouts/app.blade.php` | I.2 | +`@include('components.helpdesk-floating-button')` dan `@include('components.helpdesk-chat-widget')` di baris 125–126 |

---

### C. Config

| File | Status | Perubahan |
|------|--------|-----------|
| `config/services.php` | Modified | +blok `'helpdesk' => ['url' => env('HELPDESK_URL', 'http://localhost/esaraku_helpdesk/public')]` |
| `config/cors.php` | Modified | +allowed_origins `http://localhost`, `http://localhost:8000` |

---

### D. Routes

| File | Status | Perubahan |
|------|--------|-----------|
| `routes/web.php` | Modified | +`POST /api/helpdesk-token` (HelpdeskTokenController) dengan middleware auth+role:ASN+throttle:10,1 |
| `routes/api.php` | Modified (non-helpdesk) | Tidak ada perubahan helpdesk — route `/api/me` sudah ada sejak awal, dimodifikasi di `AuthController.php` |

---

### E. Migration

| Status | Detail |
|--------|--------|
| **Tidak ada migration baru terkait helpdesk** | SSO tidak butuh kolom baru di gaspul_api. Tabel `personal_access_tokens` sudah ada sejak awal (Sanctum). Token expiry ditangani di kode, bukan schema. |

---

### F. Middleware

| File | Status | Cara Diaktifkan |
|------|--------|----------------|
| `app/Http/Middleware/PinMysqlDatabase.php` | Untracked — Baru | Registered di `bootstrap/app.php` sebagai global middleware |

---

### G. Services

| File | Status | Keterangan |
|------|--------|-----------|
| Tidak ada service file baru di gaspul_api | — | Logic SSO token di Controller + Model, bukan service layer |

---

### H. Console Commands

| File | Status | Keterangan |
|------|--------|-----------|
| Tidak ada console command baru | — | gaspul_api tidak butuh scheduled task untuk helpdesk |

---

### I. Documentation

| File | Status |
|------|--------|
| `docs/GASPUL_API_HELPDESK_INTEGRATION_AUDIT.md` | Untracked — baru, DOCUMENTATION ONLY |
| `docs/GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md` | Untracked — baru (dokumen ini) |

---

## STEP 2 — PRODUCTION IMPACT CLASSIFICATION

### MANDATORY (Wajib Deploy — Fungsionalitas Rusak Tanpa Ini)

| File | Alasan |
|------|--------|
| `app/Http/Controllers/Api/HelpdeskTokenController.php` | **Inti SSO** — tanpa ini `POST /api/helpdesk-token` return 404, seluruh SSO gagal |
| `app/Http/Middleware/PinMysqlDatabase.php` | **Runtime isolation** — tanpa ini, Apache PDO pool bisa use DB esaraku_helpdesk saat validasi token gaspul_api → SSO 401 palsu |
| `app/Models/PersonalAccessToken.php` | **Token integrity** — override `findToken()` untuk pin DB. Tanpa ini, `USE` statement tidak dijalankan sebelum query token |
| `app/Http/Controllers/Api/AuthController.php` | **Single-use token** — `me()` sekarang menghapus token helpdesk-sso (J-05/J-06). Tanpa versi baru, token bisa di-replay |
| `app/Providers/AppServiceProvider.php` | **Registrasi model** — `Sanctum::usePersonalAccessTokenModel()` harus ada agar overridePersonalAccessToken aktif |
| `bootstrap/app.php` | **Global middleware** — mendaftarkan `PinMysqlDatabase` ke semua request |
| `config/services.php` | **URL helpdesk** — tanpa ini, `config('services.helpdesk.url')` return null → token response tidak punya `helpdesk_url` |
| `config/cors.php` | **CORS policy** — tanpa domain helpdesk di allowed_origins, browser blokir request cross-origin dari widget |
| `public/index.php` | **Env isolation** — `Env::disablePutenv()` mencegah env bleeding; tanpa ini, `.env` gaspul_api bisa ter-overwrite oleh `.env` esaraku_helpdesk di proses Apache yang sama |

**Total MANDATORY: 9 file**

---

### MANDATORY — Views (Wajib untuk UX)

| File | Alasan |
|------|--------|
| `resources/views/components/helpdesk-floating-button.blade.php` | Tombol akses helpdesk dari antarmuka ASN |
| `resources/views/components/helpdesk-chat-widget.blade.php` | Modal embedded chat widget |
| `resources/views/layouts/app.blade.php` | Include kedua komponen di atas — tanpa baris @include, widget tidak muncul |

**Total MANDATORY Views: 3 file**

**GRAND TOTAL MANDATORY: 12 file**

---

### OPTIONAL

| File | Alasan |
|------|--------|
| `routes/web.php` baris helpdesk route | Route sudah embedded di file ini — tidak bisa dipisah; wajib ikut deploy tapi perubahan ini kecil dan tidak merusak route lain |

---

### DOCUMENTATION ONLY (Tidak Deploy ke Server)

| File | Keterangan |
|------|-----------|
| `docs/GASPUL_API_HELPDESK_INTEGRATION_AUDIT.md` | Pre-integration audit report Phase I |
| `docs/GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md` | Dokumen ini |

---

## STEP 3 — DATABASE IMPACT

### Verdict: TIDAK ADA MIGRATION BARU

Integrasi helpdesk di gaspul_api tidak membutuhkan perubahan schema database karena:

| Alasan | Detail |
|--------|--------|
| Token SSO menggunakan tabel `personal_access_tokens` yang sudah ada sejak Sanctum install (migration `2026_01_20_152536`) | ✅ Tidak perlu migration baru |
| Token expiry ditangani di kode (`createToken(..., now()->addMinutes(5))`) bukan kolom schema | ✅ Tidak perlu ALTER TABLE |
| Shadow user fields ada di **esaraku_helpdesk**, bukan gaspul_api | ✅ Tidak menyentuh gaspul_api schema |
| `PinMysqlDatabase` hanya menjalankan `USE database_name` — tidak mengubah schema | ✅ Tidak perlu migration |

### Tabel yang digunakan (sudah ada):

| Tabel | Kegunaan |
|-------|---------|
| `personal_access_tokens` | Menyimpan helpdesk-sso token + expiry + revocation |
| `users` | Data ASN yang dikembalikan oleh `/api/me` |

---

## STEP 4 — CONFIG IMPACT

### Tabel Config Comparison

| Setting | Nilai Development | Nilai Production | Wajib? |
|---------|------------------|-----------------|--------|
| `APP_ENV` | `local` | `production` | ✅ Wajib `production` |
| `APP_DEBUG` | `true` | `false` | ✅ Wajib `false` |
| `APP_URL` | `http://localhost/gaspul/gaspul_api/public` | `https://[domain].go.id/[path]` | ✅ Wajib sesuaikan |
| `LOG_LEVEL` | `debug` | `error` | ✅ Wajib `error` di production |
| `SESSION_DRIVER` | `database` | `database` | Sama |
| `SESSION_SECURE_COOKIE` | `false` (default) | `true` | ✅ Wajib `true` jika HTTPS |
| **`HELPDESK_URL`** | `http://localhost/esaraku_helpdesk/public` | `https://[domain-helpdesk].go.id/[path]` | ✅ **Key baru — wajib diisi** |

### .env.production.example Status

Gaspul_api **sudah punya** `.env.production.example` dengan `HELPDESK_URL=https://CHANGE_ME_HELPDESK.go.id` ✅

### Fallback URL Risk

```php
// config/services.php
'url' => env('HELPDESK_URL', 'http://localhost/esaraku_helpdesk/public'),
```

Jika `HELPDESK_URL` tidak diset di `.env` production:
- Token response akan mengirim `helpdesk_url: "http://localhost/esaraku_helpdesk/public"`
- Browser ASN di production akan redirect ke `localhost` → **SSO gagal silently**
- **Mitigasi: wajib set `HELPDESK_URL` sebelum traffic pertama**

---

## STEP 5 — DEPLOYMENT INVENTORY LENGKAP

| File | Status | Alasan |
|------|--------|--------|
| `app/Http/Controllers/Api/HelpdeskTokenController.php` | **MANDATORY** | SSO token generator, inti integrasi |
| `app/Http/Middleware/PinMysqlDatabase.php` | **MANDATORY** | Runtime isolation, cegah PDO pool bleeding |
| `app/Models/PersonalAccessToken.php` | **MANDATORY** | Override Sanctum — pin DB sebelum token lookup |
| `app/Http/Controllers/Api/AuthController.php` | **MANDATORY** | Single-use token delete + race-proof (J-05/J-06) |
| `app/Providers/AppServiceProvider.php` | **MANDATORY** | Daftarkan PersonalAccessToken override ke Sanctum |
| `bootstrap/app.php` | **MANDATORY** | Aktifkan PinMysqlDatabase sebagai global middleware |
| `config/services.php` | **MANDATORY** | Simpan HELPDESK_URL config |
| `config/cors.php` | **MANDATORY** | Whitelist domain helpdesk untuk CORS |
| `public/index.php` | **MANDATORY** | `Env::disablePutenv()` env isolation |
| `resources/views/components/helpdesk-floating-button.blade.php` | **MANDATORY** | UI widget, tombol akses helpdesk ASN |
| `resources/views/components/helpdesk-chat-widget.blade.php` | **MANDATORY** | UI embedded chat widget |
| `resources/views/layouts/app.blade.php` | **MANDATORY** | Include kedua komponen widget |
| `routes/web.php` | **MANDATORY** | Route POST /api/helpdesk-token |
| `.env.production.example` | **MANDATORY** | Template env production dengan HELPDESK_URL |
| `docs/GASPUL_API_HELPDESK_INTEGRATION_AUDIT.md` | DOCUMENTATION ONLY | Audit pre-integration |
| `docs/GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md` | DOCUMENTATION ONLY | Dokumen ini |

**Catatan penting:** Semua file di atas saat ini **belum di-commit** ke git (status `??` untracked atau `M` modified). Artinya, git pull di server production **tidak akan mengambil file-file ini** sampai ada commit baru.

---

## STEP 6 — SAFE DEPLOYMENT PLAN

### Urutan Deploy Production (gaspul_api saja)

```bash
# ============================================================
# PRE-DEPLOY — Jalankan di server production
# ============================================================

# 1. Backup database production sebelum apapun
cd /path/to/gaspul_api
mysqldump -u [DB_USER] -p[DB_PASSWORD] gaspulco_lkbkanwil_db \
  > /backup/gaspul_api_pre_helpdesk_$(date +%Y%m%d_%H%M%S).sql
echo "Backup selesai: $(ls -lh /backup/gaspul_api_pre_helpdesk_*.sql | tail -1)"

# ============================================================
# DEPLOY — Pull & update
# ============================================================

# 2. Git pull (setelah commit di lokal & push ke remote)
git pull origin main
# Verifikasi file yang berubah:
git diff HEAD~1 --name-only | grep -E "helpdesk|PinMysql|PersonalAccessToken|AuthController|AppServiceProvider|bootstrap|cors|services|index\.php|layouts/app|floating|chat-widget|web\.php"

# 3. Composer install (karena ada file PHP baru)
composer install --no-dev --optimize-autoloader

# 4. Migration (TIDAK ADA migration baru untuk integrasi helpdesk)
# Verifikasi tidak ada migration pending:
php artisan migrate:status | grep Pending
# Jika ada pending non-helpdesk dari batch sebelumnya — jalankan:
# php artisan migrate --force

# ============================================================
# CONFIG UPDATE (PENTING — lakukan sebelum cache)
# ============================================================

# 5. Update .env production — WAJIB tambah/verifikasi
grep "HELPDESK_URL" .env
# Jika belum ada, tambahkan:
echo "HELPDESK_URL=https://[domain-helpdesk-production].go.id" >> .env

# Verifikasi nilai sudah benar:
grep "HELPDESK_URL" .env
# → HELPDESK_URL=https://esaraku.kanwilkemenag-sulbar.go.id (sesuaikan)

# ============================================================
# CACHE REBUILD — Bersihkan cache lama, rebuild
# ============================================================

# 6. Clear semua cache lama dulu
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 7. Rebuild cache dengan konfigurasi baru
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ============================================================
# SMOKE TEST
# ============================================================

# 8. Verifikasi route helpdesk terdaftar
php artisan route:list | grep helpdesk
# → POST api/helpdesk-token ... HelpdeskTokenController@issue

# 9. Verifikasi CORS config di-load benar
php artisan tinker --execute="dd(config('cors.allowed_origins'));"
# → Array dengan 'http://localhost' (atau URL production helpdesk)

# 10. Verifikasi HELPDESK_URL terbaca
php artisan tinker --execute="echo config('services.helpdesk.url');"
# → https://[domain-helpdesk-production].go.id

# ============================================================
# INTEGRASI HELPDESK TEST (Post-Deploy)
# ============================================================

# 11. Login sebagai ASN di browser production
# → Floating button biru muncul di pojok kanan bawah ✅

# 12. Klik floating button
# → Modal Pusat Bantuan terbuka ✅
# → Tidak ada error di browser console ✅

# 13. Klik "Mulai Live Chat"
# → POST /api/helpdesk-token terpanggil ✅
# → Redirect ke helpdesk dengan token ✅
# → Login di helpdesk berhasil tanpa perlu input password ✅

# 14. Verifikasi token single-use
# → Buka tab baru, paste URL yang sama (dengan token yang sama)
# → Harus mendapat 401 "Token tidak valid atau sudah kedaluwarsa" ✅
```

---

## STEP 7 — FINAL REPORT RINGKASAN

### 1. File Wajib Deploy (MANDATORY) — 14 file

| # | File |
|---|------|
| 1 | `app/Http/Controllers/Api/HelpdeskTokenController.php` |
| 2 | `app/Http/Middleware/PinMysqlDatabase.php` |
| 3 | `app/Models/PersonalAccessToken.php` |
| 4 | `app/Http/Controllers/Api/AuthController.php` |
| 5 | `app/Providers/AppServiceProvider.php` |
| 6 | `bootstrap/app.php` |
| 7 | `config/services.php` |
| 8 | `config/cors.php` |
| 9 | `public/index.php` |
| 10 | `resources/views/components/helpdesk-floating-button.blade.php` |
| 11 | `resources/views/components/helpdesk-chat-widget.blade.php` |
| 12 | `resources/views/layouts/app.blade.php` |
| 13 | `routes/web.php` |
| 14 | `.env.production.example` (template referensi) |

### 2. File Opsional

Tidak ada file opsional — semua file yang terkait helpdesk bersifat MANDATORY karena membentuk satu kesatuan fungsional.

### 3. File Dokumentasi (Tidak Deploy ke Server)

| File | Catatan |
|------|---------|
| `docs/GASPUL_API_HELPDESK_INTEGRATION_AUDIT.md` | Bisa ikut git untuk arsip, tidak perlu di server |
| `docs/GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md` | Dokumen ini |

### 4. Migration Impact

**NIHIL** — tidak ada migration baru. Deploy gaspul_api untuk helpdesk tidak menyentuh schema database.

### 5. Config Impact

| Key | Action |
|-----|--------|
| `HELPDESK_URL` | **WAJIB ditambahkan** ke `.env` production sebelum config:cache |
| `APP_ENV` | Pastikan `production` |
| `APP_DEBUG` | Pastikan `false` |
| `SESSION_SECURE_COOKIE` | `true` jika HTTPS |

### 6. Deployment Sequence Summary

```
1. Backup DB → 2. git pull → 3. composer install → 4. Update .env (HELPDESK_URL)
→ 5. config:clear → 6. config:cache → 7. route:cache → 8. view:cache
→ 9. Smoke test → 10. SSO end-to-end test
```

### 7. Rollback Plan

```bash
# Jika ada masalah setelah deploy:

# Option A — Rollback kode saja (jika error di PHP/view)
git checkout HEAD~1 -- \
  app/Http/Controllers/Api/HelpdeskTokenController.php \
  app/Http/Middleware/PinMysqlDatabase.php \
  app/Models/PersonalAccessToken.php \
  app/Http/Controllers/Api/AuthController.php \
  app/Providers/AppServiceProvider.php \
  bootstrap/app.php \
  config/services.php \
  config/cors.php \
  public/index.php \
  resources/views/components/helpdesk-floating-button.blade.php \
  resources/views/components/helpdesk-chat-widget.blade.php \
  resources/views/layouts/app.blade.php \
  routes/web.php
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache

# Option B — Rollback DB (jika ada kerusakan data — tidak ada migration, sangat jarang dibutuhkan)
mysql -u [USER] -p[PASS] gaspulco_lkbkanwil_db < /backup/gaspul_api_pre_helpdesk_*.sql

# Estimasi waktu rollback kode: < 10 menit
# Estimasi waktu rollback DB: < 20 menit (jarang diperlukan)
```

---

## FINAL VERDICT

```
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║   ✅ READY FOR PRODUCTION UPDATE                                             ║
║                                                                              ║
║   File wajib deploy       : 14 file                                          ║
║   Migration baru          : 0 (nihil — tidak ada perubahan schema)           ║
║   Config baru             : 1 key (HELPDESK_URL wajib diisi di .env)         ║
║   Estimasi waktu deploy   : 15–30 menit                                      ║
║   Estimasi downtime ASN   : < 5 menit (config:cache + route:cache saja)      ║
║   Risiko deploy           : LOW                                               ║
║                                                                              ║
║   CATATAN KRITIS:                                                            ║
║   1. Set HELPDESK_URL ke URL production helpdesk di .env SEBELUM            ║
║      config:cache — jika tidak, ASN akan diredirect ke localhost             ║
║   2. Semua 14 file harus masuk commit yang sama — tidak bisa partial         ║
║      (dependency chain: bootstrap → AppServiceProvider → PersonalAccessToken ║
║      → AuthController harus semua versi baru bersamaan)                      ║
║   3. Tidak ada migration → tidak ada risiko data corruption                   ║
║   4. Downtime sangat minimal — tidak ada schema change, tidak ada seeder     ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

---

*GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md*  
*Tanggal audit: 2026-06-17 | Mode: AUDIT ONLY — tidak ada perubahan kode*  
*Dokumen terkait: docs/GASPUL_API_HELPDESK_INTEGRATION_AUDIT.md*  
*Dokumen esaraku_helpdesk: docs/RELEASE_DEPLOYMENT_CHECKLIST.md*
