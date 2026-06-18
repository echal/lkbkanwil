# GASPUL_API — HELPDESK DEPLOYMENT FILESET
**Tanggal       :** 2026-06-19  
**Mode          :** AUDIT + FILESET DEFINITION  
**Berdasarkan   :** `docs/GASPUL_API_PRE_COMMIT_AUDIT.md`  
**Tujuan        :** Commit terfokus khusus integrasi e_SARAku Helpdesk ke production

---

## UPDATE DARI PRE-COMMIT AUDIT

Blocker yang dilaporkan di `PRE_COMMIT_AUDIT.md` — status terkini:

| Blocker | Status Sebelumnya | Status Sekarang |
|---------|------------------|----------------|
| `public/check-error.php` ada secret hardcoded | ⚠️ BLOCKER | ⚠️ MASIH ADA — exclude dari staging |
| `config/cors.php` belum punya domain production | ⚠️ BLOCKER | ✅ **RESOLVED** — `https://esaraku.gaspul.com` + `https://helpdesk.gaspul.com` sudah ada |
| `.env.production.example` ter-ignore .gitignore | ⚠️ BLOCKER | ⚠️ MASIH TER-IGNORE — distribusi manual ke server |

---

## 1. DEPENDENCY CHAIN HELPDESK (Terverifikasi)

Diagram alur dependency semua komponen helpdesk:

```
public/index.php
└── Env::disablePutenv()                    [RUNTIME ISOLATION — wajib pertama]

bootstrap/app.php
└── append(PinMysqlDatabase::class)         [GLOBAL MIDDLEWARE]
    └── app/Http/Middleware/PinMysqlDatabase.php
        └── DB::statement('USE gaspulco_lkbkanwil_db')

app/Providers/AppServiceProvider.php
└── Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class)
    └── app/Models/PersonalAccessToken.php
        └── findToken() → USE gaspulco_lkbkanwil_db
        └── tokenable() → USE gaspulco_lkbkanwil_db

routes/web.php
└── POST /api/helpdesk-token
    └── middleware: auth, role:ASN, throttle:10,1
    └── app/Http/Controllers/Api/HelpdeskTokenController.php
        └── createToken('helpdesk-sso', ['*'], now()->addMinutes(5))
        └── return { token, helpdesk_url: config('services.helpdesk.url') }
            └── config/services.php → env('HELPDESK_URL', fallback)

app/Http/Controllers/Api/AuthController.php
└── me() → if token->name === 'helpdesk-sso':
    └── DB::transaction lockForUpdate → delete (single-use, race-proof)

resources/views/layouts/app.blade.php
└── @include('components.helpdesk-floating-button')
    └── resources/views/components/helpdesk-floating-button.blade.php
        └── config('services.helpdesk.url') → HD_BASE_FAB
        └── POST /api/helpdesk-token → redirect SSO
└── @include('components.helpdesk-chat-widget')
    └── resources/views/components/helpdesk-chat-widget.blade.php
        └── config('services.helpdesk.url') → HD_BASE

config/cors.php
└── allowed_origins: [
        'https://esaraku.gaspul.com',   ✅ SUDAH ADA
        'https://helpdesk.gaspul.com',  ✅ SUDAH ADA
        'http://localhost', ...          (dev only, tidak merusak production)
    ]
```

**Seluruh dependency chain terverifikasi lengkap dan konsisten.**

---

## 2. FILE MANDATORY — HELPDESK INTEGRATION (13 file)

Semua 13 file ini harus ikut dalam satu commit yang sama. Tidak bisa partial.

### 2A. File Baru (Untracked)

| # | File | Fungsi | Dependency |
|---|------|--------|-----------|
| 1 | `app/Http/Controllers/Api/HelpdeskTokenController.php` | SSO token generator — endpoint `POST /api/helpdesk-token` | Dipanggil oleh `routes/web.php` |
| 2 | `app/Http/Middleware/PinMysqlDatabase.php` | Runtime DB isolation per-request | Diaktifkan di `bootstrap/app.php` |
| 3 | `app/Models/PersonalAccessToken.php` | Sanctum override — `USE gaspulco_lkbkanwil_db` sebelum token lookup | Didaftarkan di `AppServiceProvider.php` |
| 4 | `resources/views/components/helpdesk-floating-button.blade.php` | UI floating button + modal bantuan ASN | Di-include `layouts/app.blade.php:125` |
| 5 | `resources/views/components/helpdesk-chat-widget.blade.php` | UI embedded chat widget | Di-include `layouts/app.blade.php:126` |

### 2B. File Modified (Berubah dari Commit Sebelumnya)

| # | File | Perubahan Helpdesk | Dependency |
|---|------|-------------------|-----------|
| 6 | `app/Http/Controllers/Api/AuthController.php` | `me()`: delete helpdesk-sso token single-use + DB row-lock race-proof (J-05/J-06) | Dipanggil oleh esaraku_helpdesk saat SSO consume token |
| 7 | `app/Providers/AppServiceProvider.php` | `Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class)` | Boot waktu aplikasi start |
| 8 | `bootstrap/app.php` | `$middleware->append(PinMysqlDatabase::class)` | Global middleware, aktif setiap request |
| 9 | `config/services.php` | +`helpdesk.url = env('HELPDESK_URL', fallback)` | Dibaca oleh kedua widget via `config('services.helpdesk.url')` |
| 10 | `config/cors.php` | +`https://esaraku.gaspul.com` + `https://helpdesk.gaspul.com` ke `allowed_origins` | Aktif saat browser fetch cross-origin |
| 11 | `public/index.php` | +`Env::disablePutenv()` | Jalan pertama kali setiap HTTP request masuk |
| 12 | `resources/views/layouts/app.blade.php` | +`@include('components.helpdesk-floating-button')` baris 125 + `@include('components.helpdesk-chat-widget')` baris 126 | Layout induk semua halaman ASN |
| 13 | `routes/web.php` | +`POST /api/helpdesk-token` route group (middleware auth+role:ASN+throttle:10,1) | Entry point SSO token |

---

## 3. FILE OPTIONAL — IKUT TAPI BUKAN HELPDESK

File-file ini **bukan bagian helpdesk** tapi stabil, sudah diuji di development, dan aman untuk ikut commit bersamaan.

### Login Controller (Security)

| File | Alasan Ikut |
|------|------------|
| `app/Http/Controllers/Auth/LoginController.php` | Blokir login akun NONAKTIF — bukan helpdesk tapi security improvement yang sudah siap production |

### Monitoring TV (Sudah Aktif Dipakai)

| File | Alasan Ikut |
|------|------------|
| `resources/views/monitoring/mamasa.blade.php` | Monitoring TV Mamasa |
| `resources/views/monitoring/bimas-islam.blade.php` | Monitoring Bimas Islam |
| `resources/views/monitoring/mamuju-tengah-tv.blade.php` | Monitoring TV Mamuju Tengah |
| `resources/views/errors/503.blade.php` | Halaman maintenance branded eSARAKu |

### Documentation

| File | Alasan Ikut |
|------|------------|
| `docs/GASPUL_API_HELPDESK_INTEGRATION_AUDIT.md` | Audit pre-integrasi Phase I |
| `docs/GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md` | Inventaris update production |
| `docs/GASPUL_API_PRE_COMMIT_AUDIT.md` | Audit pre-commit RC.4 |
| `docs/GASPUL_API_HELPDESK_DEPLOYMENT_FILESET.md` | Dokumen ini |
| `docs/PRODUCTION_CUTOVER_PLAN.md` | Panduan cutover hari H |
| `docs/PHASE_H_INTEGRATION_IMPACT_AUDIT.md` | Audit dampak integrasi |
| `docs/PHASE_IA_FIX_REPORT.md` | Laporan fix Phase I.A |
| `docs/PHASE_I_INTEGRATION_DESIGN.md` | Desain integrasi |
| `docs/CHANGELOG_IMPLEMENTASI_ESARAKU.md` | Changelog umum |

---

## 4. FILE EXCLUDED — JANGAN IKUT COMMIT INI

### 4A. File Debug / Ad-Hoc (WAJIB Exclude)

| File | Alasan | Action |
|------|--------|--------|
| `check_nip_min2.php` | Script debug root — tidak layak production | Jangan di-stage |
| `check_unit.php` | Script debug root | Jangan di-stage |
| `check_unit2.php` | Script debug root | Jangan di-stage |
| `public/check-db.php` | File audit modified dengan komentar "HAPUS setelah selesai" | Jangan di-stage (sudah modified, hati-hati `git add public/`) |
| `public/check-error.php` | Secret hardcoded `migrate-esaraku-2026` bisa diakses publik | **Hapus dari server production** setelah deploy |

### 4B. Fitur Belum Selesai (Tunda ke Commit Berikutnya)

| File/Group | Alasan Tunda |
|-----------|-------------|
| `app/Http/Controllers/Admin/RotasiJabatanController.php` + views | Fitur rotasi jabatan tidak selesai |
| `app/Http/Controllers/Admin/SurveiMonitoringController.php` + model Survei* + views survei + `database/seeders/SurveiSeeder.php` | Fitur survei tidak selesai |
| `app/Http/Controllers/Asn/CutiController.php` + `app/Models/CutiAsn.php` + views cuti | Fitur cuti tidak selesai |
| `app/Http/Controllers/Asn/TutorialController.php` + views tutorial | Tutorial video — youtube_id belum diisi |
| `app/Console/Commands/BackfillSkpRealisasi.php` | Command backfill tidak mendesak |
| `database/laporan_pengembangan_monitoring_kakanwil_15juni2026.md` | Laporan internal sementara |
| `docs/LAPORAN_ERROR_500_KINERJA_HARIAN.md` | Laporan error sudah resolved |
| `docs/LAPORAN_KEGIATAN_DASHBOARD_MAMASA_12062026.md` | Laporan kegiatan internal |

### 4C. Seluruh Fitur e-Kinerja (Commit Terpisah Setelah Helpdesk)

File-file ini berisi perubahan fungsionalitas e-Kinerja yang lebih besar dan harus di-commit terpisah setelah helpdesk berhasil di-deploy:

| Group | File Count | Catatan |
|-------|-----------|---------|
| Controllers ASN/Atasan/Admin | 13 file modified | Laporan bulanan, kinerja harian, SKP, rekap, monitoring |
| Models | 7 file modified | IndikatorKinerja, LaporanBulananKinerja, ProgresHarian, dll |
| Services | 2 file modified | LaporanBulananService, RekapAbsensiService |
| Helpers + WorkingTimeService | 3 file baru | HolidayHelper, WorkingTimeService, LiburKhususService, EvidenHelper |
| Views ASN/Atasan/Admin | 26 file modified | Form kinerja, bulanan, SKP, approval, dll |
| Config | `config/tutorial.php`, `config/working_time.php` | Tunda sampai fitur siap |
| Migrations (14 file) | Semua sudah "Ran" di lokal | **Perlu di-commit bersama source code yang memakainya** |
| Admin views baru | indikator-unit-kerja, koreksi-laporan, rotasi-jabatan, kalender-libur-khusus | Tergantung controller mana yang siap |

> **Catatan Migration:** Semua 14 migration untracked saat ini berstatus **"Ran"** di database lokal (0 Pending). Artinya schema sudah ada, tapi file migration belum di-commit ke git. File-file ini harus ikut commit e-Kinerja berikutnya agar server production bisa menjalankan `php artisan migrate --force`.

---

## 5. EXACT GIT ADD COMMANDS — HELPDESK COMMIT

Salin dan jalankan perintah berikut **secara berurutan** dari direktori root monorepo (`c:/xampp/htdocs/gaspul/`):

```bash
# ============================================================
# PASTIKAN BERADA DI ROOT MONOREPO
# ============================================================
cd c:/xampp/htdocs/gaspul

# ============================================================
# MANDATORY — FILE BARU HELPDESK (untracked)
# ============================================================
git add gaspul_api/app/Http/Controllers/Api/HelpdeskTokenController.php
git add gaspul_api/app/Http/Middleware/PinMysqlDatabase.php
git add gaspul_api/app/Models/PersonalAccessToken.php
git add gaspul_api/resources/views/components/helpdesk-floating-button.blade.php
git add gaspul_api/resources/views/components/helpdesk-chat-widget.blade.php

# ============================================================
# MANDATORY — FILE MODIFIED HELPDESK
# ============================================================
git add gaspul_api/app/Http/Controllers/Api/AuthController.php
git add gaspul_api/app/Providers/AppServiceProvider.php
git add gaspul_api/bootstrap/app.php
git add gaspul_api/config/services.php
git add gaspul_api/config/cors.php
git add gaspul_api/public/index.php
git add gaspul_api/resources/views/layouts/app.blade.php
git add gaspul_api/routes/web.php

# ============================================================
# OPTIONAL — SECURITY + MONITORING (aman ikut commit ini)
# ============================================================
git add gaspul_api/app/Http/Controllers/Auth/LoginController.php
git add gaspul_api/resources/views/monitoring/mamasa.blade.php
git add gaspul_api/resources/views/monitoring/bimas-islam.blade.php
git add gaspul_api/resources/views/monitoring/mamuju-tengah-tv.blade.php
git add gaspul_api/resources/views/errors/503.blade.php

# ============================================================
# OPTIONAL — DOCUMENTATION
# ============================================================
git add gaspul_api/docs/GASPUL_API_HELPDESK_INTEGRATION_AUDIT.md
git add gaspul_api/docs/GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md
git add gaspul_api/docs/GASPUL_API_PRE_COMMIT_AUDIT.md
git add gaspul_api/docs/GASPUL_API_HELPDESK_DEPLOYMENT_FILESET.md
git add gaspul_api/docs/PRODUCTION_CUTOVER_PLAN.md
git add gaspul_api/docs/PHASE_H_INTEGRATION_IMPACT_AUDIT.md
git add gaspul_api/docs/PHASE_IA_FIX_REPORT.md
git add gaspul_api/docs/PHASE_I_INTEGRATION_DESIGN.md
git add gaspul_api/docs/CHANGELOG_IMPLEMENTASI_ESARAKU.md

# ============================================================
# VERIFIKASI STAGING SEBELUM COMMIT
# ============================================================
git diff --cached --stat
# Expected: ~27-30 file staged
# Pastikan TIDAK ada:
#   check_nip_min2.php
#   check_unit.php
#   check_unit2.php
#   public/check-db.php
#   public/check-error.php
#   database/migrations/*  (jangan ikut commit ini)
#   fitur rotasi/survei/cuti/tutorial
```

### Commit Message

```bash
git commit -m "$(cat <<'EOF'
feat: integrate e_SARAku Helpdesk SSO, floating button, embedded chat widget

HELPDESK INTEGRATION (Phase I–J):
- HelpdeskTokenController: POST /api/helpdesk-token — SSO token 5-menit TTL
- PinMysqlDatabase: global middleware — runtime DB isolation Apache PDO pool
- PersonalAccessToken: Sanctum override — USE gaspulco_lkbkanwil_db sebelum findToken()
- AuthController: single-use helpdesk-sso token (DB row-lock race-proof, J-05/J-06)
- AppServiceProvider: Sanctum::usePersonalAccessTokenModel() registration
- bootstrap/app.php: PinMysqlDatabase registered sebagai global middleware
- config/services.php: HELPDESK_URL config key (env-driven)
- config/cors.php: whitelist https://helpdesk.gaspul.com + https://esaraku.gaspul.com
- public/index.php: Env::disablePutenv() — PHP env bleeding fix (Phase I.2A-B)
- layouts/app.blade.php: @include floating-button + chat-widget
- routes/web.php: POST /api/helpdesk-token (auth + role:ASN + throttle:10,1)
- helpdesk-floating-button.blade.php: FAB biru + modal Pusat Bantuan 3 opsi
- helpdesk-chat-widget.blade.php: embedded chat widget long-polling

SECURITY:
- LoginController: blokir login akun NONAKTIF setelah Auth::attempt() berhasil
- Maintenance page 503 branded eSARAKu

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

## 6. VERIFIKASI DEPENDENCY LENGKAP

| # | Komponen | File Tersedia | Status |
|---|----------|--------------|--------|
| 1 | SSO Token Endpoint | `HelpdeskTokenController.php` | ✅ |
| 2 | Route untuk endpoint | `routes/web.php` | ✅ |
| 3 | Runtime DB isolation | `PinMysqlDatabase.php` | ✅ |
| 4 | Middleware aktif global | `bootstrap/app.php` | ✅ |
| 5 | Sanctum token model override | `PersonalAccessToken.php` | ✅ |
| 6 | Sanctum model registration | `AppServiceProvider.php` | ✅ |
| 7 | Token single-use consume | `AuthController.php` | ✅ |
| 8 | HELPDESK_URL config key | `config/services.php` | ✅ |
| 9 | PHP env isolation | `public/index.php` | ✅ |
| 10 | CORS domain production | `config/cors.php` | ✅ |
| 11 | Widget floating button | `helpdesk-floating-button.blade.php` | ✅ |
| 12 | Widget chat embedded | `helpdesk-chat-widget.blade.php` | ✅ |
| 13 | Layout include keduanya | `layouts/app.blade.php` | ✅ |
| 14 | `HasApiTokens` pada User model | `app/Models/User.php` (sudah ada sejak awal) | ✅ |
| 15 | Sanctum `^4.2` di composer.json | `composer.json` (sudah ter-commit) | ✅ |

**0 dependency yang hilang.**

---

## 7. CATATAN MIGRATION

**Tidak ada migration yang terkait helpdesk di gaspul_api.** SSO menggunakan tabel `personal_access_tokens` yang sudah ada sejak migration awal (`2026_01_20_152536_create_personal_access_tokens_table.php`).

14 migration untracked yang ada semuanya berkaitan dengan fitur e-Kinerja non-helpdesk. Status di database lokal: **semua sudah "Ran" (0 Pending)**. Commit migration tersebut harus dilakukan bersamaan dengan commit fitur e-Kinerja berikutnya.

---

## 8. CATATAN .env.production.example

File `gaspul_api/.env.production.example` ter-ignore oleh `.gitignore` monorepo (pola `**/.env.*`). File ini **tidak akan masuk git** tanpa perubahan .gitignore.

**Rekomendasi:** Distribusikan template ini ke sysadmin server production secara manual (via SCP, FTP, atau lampiran dokumen) saat hari cutover. File sudah ada di working tree — cukup upload ke server bersama file-file deployment lain.

Referensi template: berisi `HELPDESK_URL=https://CHANGE_ME_HELPDESK.go.id` yang harus diubah ke `https://helpdesk.gaspul.com`.

---

## FINAL VERDICT

```
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║   ✅ READY FOR HELPDESK COMMIT                                               ║
║                                                                              ║
║   Semua 13 file mandatory helpdesk ada di working tree.                      ║
║   Dependency chain lengkap dan terverifikasi (15/15 komponen).               ║
║   CORS sudah punya domain production (https://helpdesk.gaspul.com).          ║
║   0 credential hardcoded, 0 debug statement.                                 ║
║   0 migration helpdesk — tidak ada schema change di gaspul_api.              ║
║                                                                              ║
║   LANGKAH:                                                                   ║
║   1. Jalankan git add commands di §5 (satu per satu, JANGAN git add .)      ║
║   2. Verifikasi: git diff --cached --stat (27-30 file)                      ║
║   3. Pastikan check_*.php, public/check-db.php, migrations TIDAK staging    ║
║   4. git commit dengan pesan di §5                                          ║
║   5. git push origin main (atau branch release)                             ║
║   6. Hapus public/check-error.php dari server production secara manual      ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

| Metrik | Nilai |
|--------|-------|
| File mandatory helpdesk | **13 file** |
| File optional (security + monitoring + docs) | **14 file** |
| File excluded (debug + fitur belum selesai) | **40+ file** |
| Migration dalam commit ini | **0** |
| Dependency hilang | **0** |
| Blocker keamanan tersisa | **1** (`public/check-error.php` — exclude dari staging, hapus dari server) |

---

*GASPUL_API_HELPDESK_DEPLOYMENT_FILESET.md*  
*Tanggal: 2026-06-19 | Berdasarkan: GASPUL_API_PRE_COMMIT_AUDIT.md*
