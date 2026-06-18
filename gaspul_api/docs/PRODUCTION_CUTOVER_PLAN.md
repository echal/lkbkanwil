# PRODUCTION CUTOVER PLAN
## e_SARAku Helpdesk v1.0.0-beta
## Pilot Kanwil Kementerian Agama Provinsi Sulawesi Barat

---

**Dokumen Versi :** 1.0  
**Tanggal Dibuat :** 2026-06-17  
**Status         :** FINAL — Siap digunakan saat hari deploy  
**Referensi      :**
- `docs/RELEASE_DEPLOYMENT_CHECKLIST.md`
- `docs/GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md`
- `docs/RELEASE_CANDIDATE_v1.0.0_BETA.md`
- `docs/BACKUP_RECOVERY_GUIDE.md`
- `docs/SOP_OPERATOR_HELPDESK_v1.0.md`

---

## 1. TUJUAN DEPLOY

Dokumen ini adalah panduan operasional step-by-step untuk melakukan **production cutover** sistem e_SARAku Helpdesk v1.0.0-beta ke server production Kanwil Kementerian Agama Provinsi Sulawesi Barat.

### Tujuan Utama

1. **Mengaktifkan sistem bantuan terpadu** bagi seluruh ASN Kanwil Kemenag Sulbar — menggantikan penanganan pertanyaan melalui WhatsApp/telepon dengan sistem tiket dan live chat terstruktur.

2. **Mengintegrasikan helpdesk ke e_SARAku** (gaspul_api) secara seamless — ASN tidak perlu login ulang, cukup klik tombol bantuan dari antarmuka e-Kinerja yang sudah mereka gunakan sehari-hari.

3. **Menjalankan pilot terkontrol** dengan volume pengguna terbatas untuk memvalidasi sistem sebelum rollout penuh ke seluruh unit kerja.

### Cakupan Pilot

- **Peserta awal:** Operator helpdesk yang ditunjuk (6 orang maks), ASN terpilih dari kantor pusat Kanwil
- **Durasi pilot:** 30 hari kalender sejak go-live
- **Evaluasi:** H+30 — keputusan rollout penuh atau perbaikan

### Risiko

| Level | Kondisi |
|-------|---------|
| **Low** | Tidak ada perubahan schema database gaspul_api — ASN tetap bisa menggunakan e-Kinerja normal jika helpdesk down |
| **Low** | Downtime ASN < 5 menit untuk update gaspul_api |
| **Medium** | Downtime helpdesk selama deploy awal (~30 menit, tapi layanan belum aktif) |
| **Mitigasi** | Rollback penuh < 30 menit jika ada masalah kritis |

---

## 2. SCOPE DEPLOY

### 2.1 e_SARAku Helpdesk (esaraku_helpdesk) — Deploy Baru

Ini adalah aplikasi **baru** yang belum pernah ada di production. Deploy pertama kali ke subdomain `helpdesk.gaspul.com`.

**Komponen yang di-deploy:**

| Komponen | Detail |
|----------|--------|
| Source code | v1.0.0-beta (tag git, branch `release/v1.0.0-beta`) |
| Database | Database baru `esaraku_helpdesk` — fresh install, 22 migration |
| Fitur Live Chat | Real-time ASN ↔ Operator (long-polling, tanpa WebSocket) |
| Sistem Tiket | State machine 5 status: open → in_progress ↔ waiting_user → resolved → closed |
| SSO | Terima token dari gaspul_api, validasi via `GET /api/me` |
| Backup Otomatis | `helpdesk:backup` setiap hari pukul 02.00, retensi 30 hari |
| Auto-Close Chat | `chat:close-idle --hours=24` setiap jam 00.00 |
| Super Admin | Dibuat via seeder dengan password random (wajib ganti di login pertama) |

### 2.2 e_SARAku (gaspul_api) — Update Integrasi

Ini adalah aplikasi **yang sudah berjalan**. Update kecil dan tidak breaking — hanya menambah komponen SSO dan widget helpdesk.

**File yang berubah (14 file MANDATORY):**

| Komponen | File | Perubahan |
|----------|------|-----------|
| SSO Token Endpoint | `app/Http/Controllers/Api/HelpdeskTokenController.php` | **Baru** — endpoint `POST /api/helpdesk-token` |
| Runtime Isolation | `app/Http/Middleware/PinMysqlDatabase.php` | **Baru** — pin DB connection per-request |
| Sanctum Override | `app/Models/PersonalAccessToken.php` | **Baru** — `USE gaspulco_lkbkanwil_db` sebelum token lookup |
| Token Single-Use | `app/Http/Controllers/Api/AuthController.php` | **Modified** — hapus helpdesk-sso token setelah `/api/me` |
| Sanctum Registration | `app/Providers/AppServiceProvider.php` | **Modified** — daftarkan custom PersonalAccessToken |
| Global Middleware | `bootstrap/app.php` | **Modified** — append PinMysqlDatabase |
| Config Helpdesk URL | `config/services.php` | **Modified** — `HELPDESK_URL` config key |
| CORS | `config/cors.php` | **Modified** — whitelist domain helpdesk |
| Env Isolation | `public/index.php` | **Modified** — `Env::disablePutenv()` |
| Floating Button | `resources/views/components/helpdesk-floating-button.blade.php` | **Baru** — FAB biru pojok kanan bawah |
| Chat Widget | `resources/views/components/helpdesk-chat-widget.blade.php` | **Baru** — modal embedded widget |
| Layout Include | `resources/views/layouts/app.blade.php` | **Modified** — +2 baris @include |
| SSO Route | `routes/web.php` | **Modified** — +route POST /api/helpdesk-token |
| .env Template | `.env.production.example` | **Baru** — template dengan HELPDESK_URL |

**Tidak ada migration baru di gaspul_api.** Schema database gaspul_api tidak berubah.

---

## 3. TIM DAN PERAN

| Role | PIC | Tugas |
|------|-----|-------|
| **Admin Server** | _(isi nama)_ | Akses cPanel/SSH; deploy file; konfigurasi cron job; permission storage |
| **Admin Database** | _(isi nama)_ | Buat database baru; jalankan migration; backup & restore jika rollback |
| **Admin Helpdesk** | _(isi nama)_ | Login pertama; ganti password super admin; buat akun operator; konfigurasi kategori tiket |
| **Operator Helpdesk (x6)** | _(isi nama)_ | Uji login; standby menerima chat dan tiket saat smoke test |
| **Penguji ASN Pilot** | _(isi nama)_ | Login sebagai ASN via SSO; uji floating button; buat tiket; mulai live chat |
| **Koordinator Deploy** | _(isi nama)_ | Memimpin timeline cutover; komunikasi tim; tanda tangan go-live |

---

## 4. ESTIMASI DOWNTIME

| Aktivitas | Estimasi | Keterangan |
|-----------|----------|-----------|
| Update gaspul_api (14 file) | **3–5 menit** | Upload file + config:cache + route:cache |
| Downtime ASN saat config:cache gaspul_api | **< 2 menit** | Request in-flight mungkin error sementara |
| Deploy helpdesk pertama kali | **20–30 menit** | composer install + migrate + seeder + cache |
| Konfigurasi cron job cPanel | **5 menit** | Satu baris perintah |
| Backup baseline pertama | **5–10 menit** | Tergantung ukuran storage |
| End-to-end smoke test | **30–45 menit** | 13 skenario test manual |
| **Total window cutover** | **~90 menit** | Dari mulai sampai go-live |
| **Downtime ASN nyata** | **< 5 menit** | Hanya saat cache gaspul_api di-rebuild |

**Target:** Cutover selesai dalam **1,5 jam**. Jadwalkan mulai pukul **07:00 WIB** (sebelum jam kerja ASN) agar downtime tidak dirasakan.

---

## 5. PRE-CUTOVER CHECKLIST (H-1)

Lakukan semua item ini **sehari sebelum hari deploy**. Jangan mulai cutover jika ada item yang belum ✅.

### 5.1 DNS & SSL

```
[ ] DNS subdomain helpdesk.gaspul.com sudah mengarah ke server production
    → nslookup helpdesk.gaspul.com
    → Harus resolve ke IP server yang sama dengan esaraku.gaspul.com

[ ] SSL certificate untuk helpdesk.gaspul.com sudah aktif
    → curl -I https://helpdesk.gaspul.com
    → Harus return HTTP/2 301 atau 200 (bukan SSL error)
    → Jika menggunakan Let's Encrypt di cPanel: aktifkan via SSL/TLS → AutoSSL

[ ] HTTPS redirect aktif
    → Akses http://helpdesk.gaspul.com (tanpa s) harus redirect ke https://
```

### 5.2 Database

```
[ ] Database baru sudah dibuat di production:
    CREATE DATABASE esaraku_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

[ ] User database sudah dibuat dan diberi akses:
    GRANT ALL ON esaraku_helpdesk.* TO 'helpdesk_user'@'localhost' IDENTIFIED BY '[PASSWORD_KUAT]';

[ ] Koneksi diverifikasi dari server:
    mysql -u helpdesk_user -p esaraku_helpdesk -e "SELECT 1;"
    → Harus return "1" tanpa error

[ ] Catat credentials (JANGAN simpan di plain text):
    DB_HOST      : _______________
    DB_DATABASE  : esaraku_helpdesk
    DB_USERNAME  : _______________
    DB_PASSWORD  : _______________ (simpan di password manager)
```

### 5.3 Backup gaspul_api

```
[ ] Backup database gaspulco_lkbkanwil_db SEBELUM apapun:
    mysqldump -u [USER] -p gaspulco_lkbkanwil_db \
      > /home/[cpanel_user]/backup/gaspul_pre_helpdesk_$(date +%Y%m%d).sql

[ ] Verifikasi ukuran backup:
    ls -lh /home/[cpanel_user]/backup/gaspul_pre_helpdesk_*.sql
    → Harus > 0 bytes

[ ] Simpan backup di lokasi aman (bukan dalam document root)
```

### 5.4 Git & Release

```
[ ] Verifikasi tag v1.0.0-beta ada di remote:
    git ls-remote --tags origin | grep v1.0.0-beta
    → Harus ada output

[ ] Verifikasi branch release/v1.0.0-beta ada di remote:
    git ls-remote --heads origin | grep release/v1.0.0-beta
    → Harus ada output

[ ] Download source code ke server sudah disiapkan
    (git clone ATAU upload ZIP dari release GitHub)
```

### 5.5 .env Production Files

```
[ ] File .env helpdesk sudah disiapkan berdasarkan .env.production.example:
    Semua CHANGE_ME sudah diisi:
    [ ] APP_URL=https://helpdesk.gaspul.com
    [ ] APP_KEY= (kosong — diisi php artisan key:generate)
    [ ] DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
    [ ] GASPUL_API_BASE_URL=https://esaraku.gaspul.com/public
    [ ] GASPUL_API_TIMEOUT=10
    [ ] SUPER_ADMIN_NAME=[nama lengkap admin]
    [ ] SUPER_ADMIN_EMAIL=[email admin helpdesk]
    [ ] SESSION_SECURE_COOKIE=true
    [ ] APP_ENV=production
    [ ] APP_DEBUG=false
    [ ] LOG_LEVEL=error

[ ] File .env gaspul_api sudah disiapkan dengan tambahan:
    [ ] HELPDESK_URL=https://helpdesk.gaspul.com
    [ ] Semua nilai lain tetap (tidak berubah dari production saat ini)

[ ] Verifikasi TIDAK ada nilai debug di .env yang disiapkan:
    grep "APP_DEBUG" helpdesk.env → harus "APP_DEBUG=false"
    grep "APP_ENV" helpdesk.env → harus "APP_ENV=production"
```

### 5.6 Server Requirements

```
[ ] PHP versi cek:
    php -v → harus PHP 8.2+ (optimal 8.4)

[ ] PHP extensions tersedia:
    php -m | grep -E "pdo_mysql|zip|mbstring|openssl|json|bcmath"
    → Semua harus muncul

[ ] mysqldump tersedia:
    which mysqldump → harus ada path
    mysqldump --version → harus menampilkan versi

[ ] Composer tersedia:
    composer --version → harus menampilkan versi

[ ] Disk space cukup (minimal 500 MB free):
    df -h → cek free space di partition home/document root
```

### 5.7 Storage & Permission (Pre-check)

```
[ ] Document root untuk helpdesk.gaspul.com sudah ditetapkan di cPanel:
    Domains → helpdesk.gaspul.com → Document Root: /home/[user]/esaraku_helpdesk/public

[ ] Folder storage tersedia dan bisa ditulis:
    Test: touch /home/[user]/esaraku_helpdesk/storage/test.txt
    → Harus sukses tanpa error permission
```

### 5.8 Komunikasi

```
[ ] Admin Helpdesk sudah siap standby pada hari H
[ ] Operator Helpdesk (6 orang) sudah diberi jadwal untuk standby saat smoke test
[ ] Penguji ASN sudah dikonfirmasi bisa hadir (atau remote)
[ ] Koordinator Deploy sudah membaca seluruh dokumen ini
```

---

## 6. CUTOVER TIMELINE MENIT-PER-MENIT

> **T0 = Waktu mulai deploy helpdesk pertama kali**
> Rekomendasi: **T0 = 07:00 WIB**

---

### T-60 (06:00 WIB) — Final Pre-Check

```
□ Semua anggota tim konfirmasi siap via WhatsApp/channel komunikasi
□ Buka dokumen ini di browser untuk referensi
□ Pastikan backup H-1 sudah ada dan ukurannya wajar
□ Pastikan akses cPanel/SSH siap (session tidak expired)
□ Verifikasi ulang DNS sudah propagasi:
    nslookup helpdesk.gaspul.com
□ Buka tab browser:
    - cPanel File Manager atau SSH session
    - GitHub release page v1.0.0-beta
    - Halaman login https://esaraku.gaspul.com (verifikasi masih berjalan normal)
```

---

### T-30 (06:30 WIB) — Upload Source Code Helpdesk

```
□ Di server: clone atau upload source code
    
    OPSI A — via git (jika git tersedia di server):
    cd /home/[user]/
    git clone --branch release/v1.0.0-beta \
      https://github.com/[owner]/esaraku_helpdesk.git \
      esaraku_helpdesk
    
    OPSI B — via FTP/cPanel File Manager:
    Upload ZIP dari GitHub release → extract ke esaraku_helpdesk/
    
□ Verifikasi folder ada:
    ls /home/[user]/esaraku_helpdesk/
    → Harus ada: app/ bootstrap/ config/ database/ public/ routes/ ...

□ cPanel → Domains → Verify document root:
    helpdesk.gaspul.com → /home/[user]/esaraku_helpdesk/public
```

---

### T-15 (06:45 WIB) — Backup Terakhir gaspul_api

```
□ Backup database production gaspul_api (terakhir sebelum modifikasi):
    mysqldump -u [USER] -p[PASS] gaspulco_lkbkanwil_db \
      > /home/[user]/backup/gaspul_pre_cutover_$(date +%Y%m%d_%H%M).sql
    
□ Verifikasi backup berhasil:
    ls -lh /home/[user]/backup/gaspul_pre_cutover_*.sql | tail -1
    → Harus > 1 MB
    
□ Catat path backup: ________________________________
```

---

### T-10 (06:50 WIB) — Deploy gaspul_api (Update Integrasi)

```
□ Upload 14 file MANDATORY ke server gaspul_api:
    
    WAJIB UPLOAD (baru atau ganti):
    app/Http/Controllers/Api/HelpdeskTokenController.php
    app/Http/Middleware/PinMysqlDatabase.php
    app/Models/PersonalAccessToken.php
    app/Http/Controllers/Api/AuthController.php
    app/Providers/AppServiceProvider.php
    bootstrap/app.php
    config/services.php
    config/cors.php
    public/index.php
    resources/views/components/helpdesk-floating-button.blade.php
    resources/views/components/helpdesk-chat-widget.blade.php
    resources/views/layouts/app.blade.php
    routes/web.php
    
□ Update .env gaspul_api — tambahkan HELPDESK_URL:
    echo "HELPDESK_URL=https://helpdesk.gaspul.com" >> /home/[user]/gaspul_api/.env
    
    ATAU edit manual via File Manager:
    Buka .env → cari HELPDESK_URL → ubah ke https://helpdesk.gaspul.com
    
□ Rebuild cache gaspul_api (downtime < 2 menit dimulai di sini):
    cd /home/[user]/gaspul_api
    php artisan config:clear
    php artisan config:cache
    php artisan route:clear
    php artisan route:cache
    php artisan view:clear
    php artisan view:cache
    
□ Verifikasi cepat gaspul_api masih berjalan:
    curl -s https://esaraku.gaspul.com/login | grep "e-SARAku"
    → Harus return HTML halaman login
    
□ Catat waktu selesai: _______________
```

---

### T0 (07:00 WIB) — Deploy Helpdesk: Install Dependencies

```
cd /home/[user]/esaraku_helpdesk

□ Install Composer dependencies (4–8 menit):
    composer install --no-dev --optimize-autoloader
    
    Jika ada error "ext-zip not found":
    → Aktifkan zip extension di cPanel → PHP → PHP Extensions → zip
    → Atau tambahkan extension=zip ke php.ini
    
□ Verifikasi vendor/ terbuat:
    ls vendor/autoload.php → harus ada
    
□ Catat waktu selesai: _______________
```

---

### T+5 (07:05 WIB) — Deploy Helpdesk: .env, Key, Permission

```
cd /home/[user]/esaraku_helpdesk

□ Upload/salin .env yang sudah disiapkan ke server:
    cp .env.production.example .env
    (lalu isi semua CHANGE_ME via nano/vi atau File Manager)
    
    ATAU upload langsung file .env yang sudah diisi di lokal
    PERHATIAN: Jangan upload .env development — pastikan nilai sudah production!
    
□ Generate application key:
    php artisan key:generate
    → Output: "Application key set successfully."
    
□ Set permission storage dan bootstrap/cache:
    chmod -R 775 storage/
    chmod -R 775 bootstrap/cache/
    
    Jika cPanel:
    chown -R [user]:[user] storage/
    chown -R [user]:[user] bootstrap/cache/
    
□ Buat storage symlink:
    php artisan storage:link
    → Output: "The [/public/storage] link has been connected to [storage/app/public]."
    
□ Catat waktu selesai: _______________
```

---

### T+10 (07:10 WIB) — Deploy Helpdesk: Migration & Seeder

```
cd /home/[user]/esaraku_helpdesk

□ Jalankan migration:
    php artisan migrate --force
    
    Expected output terakhir (22 migration):
    "... [22] Ran"
    
    Verifikasi tidak ada merah/error. Jika ada error:
    → STOP — lihat Rollback Plan Level 1 di §13
    
□ Verifikasi semua migration ran:
    php artisan migrate:status | grep Pending
    → Tidak ada output = semua sudah ran ✅
    
□ Jalankan seeder (buat super admin):
    php artisan db:seed
    
    Expected output:
    "[OK] Super admin dibuat: [email]"
    "Password sementara: [XXXXXXXXXXXXXXXXXXXX]"
    
    *** CATAT PASSWORD INI SEKARANG — TIDAK AKAN MUNCUL LAGI ***
    
    Email    : _________________________________
    Password : _________________________________
    (Simpan di password manager tim)
    
□ Catat waktu selesai: _______________
```

---

### T+15 (07:15 WIB) — Deploy Helpdesk: Cache & Verifikasi

```
cd /home/[user]/esaraku_helpdesk

□ Cache config, route, view:
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
□ Verifikasi route SSO terdaftar:
    php artisan route:list | grep sso
    → Harus ada: GET sso/login → Auth\SsoLoginController@handle
    
□ Verifikasi scheduler terdaftar:
    php artisan schedule:list
    → Harus menampilkan 2 tasks:
      0 * * * *  chat:close-idle --hours=24
      0 2 * * *  helpdesk:backup
    
□ Verifikasi GASPUL_API_BASE_URL di-load benar:
    php artisan tinker --execute="echo config('services.gaspul_api.base_url');"
    → Harus: https://esaraku.gaspul.com/public
    
□ Test akses halaman login:
    curl -s https://helpdesk.gaspul.com/login | grep -i "login\|SARAku\|helpdesk"
    → Harus return HTML halaman login
    
□ Catat waktu selesai: _______________
```

---

### T+20 (07:20 WIB) — Scheduler Activation (Cron cPanel)

```
□ Buka cPanel → Cron Jobs → Add New Cron Job

    Minute    : *
    Hour      : *
    Day       : *
    Month     : *
    Weekday   : *
    Command   : /usr/local/bin/php /home/[user]/esaraku_helpdesk/artisan schedule:run >> /dev/null 2>&1
    
    PENTING: Ganti path php dan artisan sesuai server Anda.
    Verifikasi path php: which php → misal /usr/local/bin/php
    
□ Tunggu 1–2 menit setelah cron dibuat
□ Verifikasi cron berjalan (cek tidak ada entry error di cPanel → Cron Jobs → History)

□ Alternatif jika perlu path absolute:
    * * * * * cd /home/[user]/esaraku_helpdesk && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1

□ Catat waktu selesai: _______________
```

---

### T+30 (07:30 WIB) — Baseline Backup

```
cd /home/[user]/esaraku_helpdesk

□ Jalankan backup pertama (akan buat dump + manifest):
    php artisan helpdesk:backup --tag=pre-pilot
    
    Expected output (ringkasan):
    "=== FULL BACKUP SELESAI ==="
    "Database : storage/app/backups/database/helpdesk-db-[timestamp]_pre-pilot.sql"
    "Manifest : storage/app/backups/manifest/..."
    
    Jika error "mysqldump: command not found":
    → Cek: which mysqldump  (harus ada)
    → cPanel biasanya punya mysqldump di /usr/bin/mysqldump
    → Edit command path di BackupDatabase.php atau symlink
    
    Jika error "ZipArchive not found":
    → Aktifkan ext-zip di PHP → lanjutkan deploy (backup DB tetap berjalan)
    
□ Verifikasi file backup ada:
    ls -lh storage/app/backups/database/
    → Harus ada: helpdesk-db-*pre-pilot.sql, ukuran > 0
    
□ Catat path backup pertama: ________________________________
□ Catat ukuran: ____________
□ Catat waktu selesai: _______________
```

---

### T+35 (07:35 WIB) — Super Admin First Login & Setup

```
□ Buka browser: https://helpdesk.gaspul.com/login
□ Login dengan email + password sementara dari T+10
□ Sistem akan paksa redirect ke halaman ganti password
□ Ganti password ke password kuat (minimal 12 karakter, ada angka + simbol)

*** CATAT PASSWORD BARU ***
Password baru : _________________________________

□ Setelah ganti password, akses dashboard admin → harus muncul ✅

□ Buat akun 6 Operator:
    Admin Panel → Users → Create User
    Role: operator
    Status: aktif
    
    Operator 1: email=_______________ password=_______________
    Operator 2: email=_______________ password=_______________
    Operator 3: email=_______________ password=_______________
    Operator 4: email=_______________ password=_______________
    Operator 5: email=_______________ password=_______________
    Operator 6: email=_______________ password=_______________

□ Buat akun Supervisor (jika ada):
    Role: supervisor
    email=_______________ password=_______________

□ Catat waktu selesai: _______________
```

---

### T+50 (07:50 WIB) — Smoke Test (§11 lengkap)

Lihat Bagian 11 untuk tabel lengkap. Timeline ini menyediakan 30–45 menit.

```
□ Minta penguji ASN siap di https://esaraku.gaspul.com
□ Minta operator helpdesk login ke https://helpdesk.gaspul.com
□ Jalankan 13 skenario smoke test secara berurutan
□ Catat status setiap skenario (PASS / FAIL)
```

---

### T+90 (08:30 WIB) — Go/No-Go Decision

```
□ Koordinator Deploy kumpulkan hasil semua smoke test
□ Jika semua PASS → GO (layanan dibuka ke pilot users)
□ Jika ada FAIL → evaluasi severity:
    - Critical FAIL → lihat Rollback Plan §13
    - Non-critical FAIL → catat, buka dengan catatan, fix di H+1
    
□ Kirim notifikasi ke ASN pilot bahwa layanan sudah aktif
□ Bagikan SOP Operator ke semua operator helpdesk
□ Tanda tangani Go-Live Approval (§15)
```

---

## 7. DEPLOYMENT PROCEDURE — e_SARAku HELPDESK (Rinci)

### 7.1 Persiapan

```bash
# Masuk ke direktori aplikasi
cd /home/[cpanel_user]/esaraku_helpdesk

# Pastikan sudah di versi yang benar
git log --oneline -1
# → Harus menampilkan commit dari release/v1.0.0-beta
```

### 7.2 Install Dependencies

```bash
composer install --no-dev --optimize-autoloader

# Verifikasi:
php -r "require 'vendor/autoload.php'; echo 'autoload OK';"
# → autoload OK
```

### 7.3 Environment

```bash
# Upload .env yang sudah diisi, atau:
cp .env.production.example .env
nano .env   # isi semua nilai CHANGE_ME

# Generate key (WAJIB sebelum migrate):
php artisan key:generate

# Verifikasi key terisi:
grep "APP_KEY" .env | head -1
# → APP_KEY=base64:[64 karakter]

# Verifikasi critical values:
php artisan tinker --execute="
  echo 'ENV: ' . config('app.env') . PHP_EOL;
  echo 'DEBUG: ' . (config('app.debug') ? 'TRUE-BAHAYA!' : 'false-aman') . PHP_EOL;
  echo 'GASPUL URL: ' . config('services.gaspul_api.base_url') . PHP_EOL;
  echo 'APP URL: ' . config('app.url') . PHP_EOL;
"
# → ENV: production
# → DEBUG: false-aman
# → GASPUL URL: https://esaraku.gaspul.com/public
# → APP URL: https://helpdesk.gaspul.com
```

### 7.4 Storage

```bash
# Symlink untuk storage publik (artikel, gambar)
php artisan storage:link
# → The [public/storage] link has been connected to [storage/app/public].

# Permission
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### 7.5 Database Migration

```bash
php artisan migrate --force

# Verifikasi tidak ada pending:
php artisan migrate:status
# → Semua baris: "Ran" (22 migration)
# → 0 baris "Pending"
```

### 7.6 Seeder

```bash
php artisan db:seed
# → CATAT: "[OK] Super admin dibuat" + password sementara
```

### 7.7 Cache

```bash
php artisan config:cache    # bekukan config (env() tidak lagi dibaca langsung)
php artisan route:cache     # cache routing table
php artisan view:cache      # compile blade templates

# Verifikasi:
php artisan route:list --path=sso | head -3
# → sso/login  GET  Auth\SsoLoginController@handle

php artisan route:list --path=helpdesk-token | head -3
# → tidak ada di helpdesk → ini route di gaspul_api ✅
```

### 7.8 Verifikasi Akhir

```bash
# Test HTTP response
curl -o /dev/null -s -w "%{http_code}" https://helpdesk.gaspul.com/login
# → 200

# Test halaman tidak bocorkan error
curl -s https://helpdesk.gaspul.com/login | grep -i "exception\|error\|trace"
# → Tidak ada output (tidak ada exception leak)
```

---

## 8. DEPLOYMENT PROCEDURE — e_SARAku / gaspul_api (Update)

### 8.1 Upload 14 File Mandatory

Upload melalui FTP, cPanel File Manager, atau git pull (jika git tersedia di server). Pastikan semua 14 file di bawah ini diganti/ditambahkan.

```
[ ] app/Http/Controllers/Api/HelpdeskTokenController.php   (FILE BARU)
[ ] app/Http/Middleware/PinMysqlDatabase.php               (FILE BARU)
[ ] app/Models/PersonalAccessToken.php                     (FILE BARU)
[ ] app/Http/Controllers/Api/AuthController.php            (GANTI)
[ ] app/Providers/AppServiceProvider.php                   (GANTI)
[ ] bootstrap/app.php                                      (GANTI)
[ ] config/services.php                                    (GANTI)
[ ] config/cors.php                                        (GANTI)
[ ] public/index.php                                       (GANTI)
[ ] resources/views/components/helpdesk-floating-button.blade.php  (FILE BARU)
[ ] resources/views/components/helpdesk-chat-widget.blade.php      (FILE BARU)
[ ] resources/views/layouts/app.blade.php                  (GANTI)
[ ] routes/web.php                                         (GANTI)
```

### 8.2 Update .env gaspul_api

```bash
cd /home/[user]/gaspul_api

# Verifikasi HELPDESK_URL belum ada:
grep "HELPDESK_URL" .env
# → Tidak ada output (belum ada) ATAU sudah ada tapi masih localhost

# Tambahkan atau update:
# Jika belum ada:
echo "" >> .env
echo "# Phase I.2 — e_SARAku Helpdesk SSO" >> .env
echo "HELPDESK_URL=https://helpdesk.gaspul.com" >> .env

# Jika sudah ada (masih localhost), edit manual via File Manager atau sed:
sed -i 's|HELPDESK_URL=.*|HELPDESK_URL=https://helpdesk.gaspul.com|' .env

# Verifikasi:
grep "HELPDESK_URL" .env
# → HELPDESK_URL=https://helpdesk.gaspul.com
```

### 8.3 Rebuild Cache gaspul_api

```bash
cd /home/[user]/gaspul_api

php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache
```

### 8.4 Verifikasi Checklist

```
[ ] Route helpdesk-token terdaftar:
    php artisan route:list | grep helpdesk-token
    → POST api/helpdesk-token ... HelpdeskTokenController@issue ✅

[ ] HELPDESK_URL terbaca benar:
    php artisan tinker --execute="echo config('services.helpdesk.url');"
    → https://helpdesk.gaspul.com ✅

[ ] CORS config include domain helpdesk:
    php artisan tinker --execute="print_r(config('cors.allowed_origins'));"
    → Array berisi https://helpdesk.gaspul.com ✅

[ ] Halaman e-SARAku masih normal:
    curl -o /dev/null -s -w "%{http_code}" https://esaraku.gaspul.com/login
    → 200 ✅

[ ] Tidak ada migration pending di gaspul_api:
    php artisan migrate:status | grep Pending
    → Tidak ada output ✅
```

---

## 9. SCHEDULER ACTIVATION

### 9.1 Cron Job di cPanel

```
Menu: cPanel → Cron Jobs → Add New Cron Job

Setting:
  Common Settings: Once Per Minute (*)
  
  Atau isi manual:
  Minute  : *
  Hour    : *
  Day     : *
  Month   : *
  Weekday : *
  
Command:
  /usr/local/bin/php /home/[cpanel_user]/esaraku_helpdesk/artisan schedule:run >> /dev/null 2>&1

CATATAN PATH:
- Ganti /usr/local/bin/php dengan path PHP yang benar di server Anda
  Cek: which php  → misal /usr/local/bin/php atau /opt/cpanel/ea-php84/root/usr/bin/php
- Ganti /home/[cpanel_user] dengan home directory cPanel Anda
```

### 9.2 Verifikasi Scheduler

```bash
# Daftar task yang terjadwal:
php artisan schedule:list

Expected output:
  0 * * * *  php artisan chat:close-idle --hours=24  .. Next Due: X minutes from now
  0 2 * * *  php artisan helpdesk:backup             .. Next Due: Y hours from now

# Test manual schedule:run (tidak menjalankan task, hanya verifikasi):
php artisan schedule:run --verbose
# → "No scheduled commands are ready to run." (normal di luar waktu jadwal)
# → Atau jika tepat jam 00/02: task akan berjalan

# Tunggu 2 menit setelah cron dibuat → cek log:
tail -20 storage/logs/laravel.log
# → Tidak ada error dari schedule:run
```

### 9.3 Pengujian Backup Manual (Verifikasi scheduler command berjalan)

```bash
# Test command backup secara manual:
php artisan helpdesk:backup --tag=test-scheduler

# Verifikasi output muncul dan file terbuat:
ls storage/app/backups/database/ | grep test-scheduler
# → Ada file: helpdesk-db-[timestamp]_test-scheduler.sql
```

---

## 10. BASELINE BACKUP

### 10.1 Backup Pre-Pilot (Wajib)

```bash
cd /home/[user]/esaraku_helpdesk

php artisan helpdesk:backup --tag=pre-pilot
```

### 10.2 Expected Output

```
=== MULAI BACKUP PENUH ===
[Database] Menjalankan mysqldump...
[Database] Sukses: storage/app/backups/database/helpdesk-db-20260617-XXXXXX_pre-pilot.sql (XXX KB)
[Files] Mengarsip lampiran tiket...
[Files] Sukses: storage/app/backups/files/helpdesk-files-20260617-XXXXXX_pre-pilot.zip (X KB)
[Manifest] Menulis manifest...
[Manifest] Sukses: storage/app/backups/manifest/backup-20260617-XXXXXX_pre-pilot.json
[Retention] Membersihkan backup lama (> 30 hari)...
[Retention] 0 file dihapus.
=== FULL BACKUP SELESAI ===
```

### 10.3 Verifikasi

```bash
# Cek semua komponen backup ada:
ls -lh storage/app/backups/database/ | grep pre-pilot
# → helpdesk-db-*_pre-pilot.sql  (ukuran > 0, meski fresh install kecil)

ls -lh storage/app/backups/manifest/ | grep pre-pilot
# → backup-*_pre-pilot.json ✅

# Verifikasi manifest valid JSON:
php -r "print_r(json_decode(file_get_contents(glob('storage/app/backups/manifest/*pre-pilot.json')[0]), true));"
# → Array dengan keys: tag, timestamp, database, files, ...
```

---

## 11. SMOKE TEST

Jalankan semua skenario secara berurutan. Satu orang sebagai **Koordinator** mencatat hasil, satu **Penguji ASN** yang sudah punya akun e-SARAku, satu **Operator** helpdesk, dan satu **Admin Helpdesk**.

| No | Skenario | Akun | Expected Result | Status |
|----|----------|------|-----------------|--------|
| 1 | Login Admin Helpdesk | super_admin | Dashboard admin tampil, tidak ada redirect error | ☐ |
| 2 | Login Operator | operator@... | Dashboard operator tampil, queue chat terlihat | ☐ |
| 3 | Login ASN via SSO | ASN (dari esaraku.gaspul.com) | Floating button biru muncul di pojok kanan bawah | ☐ |
| 4 | Buka Modal Bantuan | ASN | Klik floating button → modal terbuka dengan 3 opsi: FAQ, Tiket Saya, Live Chat | ☐ |
| 5 | Akses FAQ | ASN | Klik FAQ di modal → redirect ke helpdesk halaman FAQ tanpa login ulang (SSO seamless) | ☐ |
| 6 | Akses Tiket Saya | ASN | Klik Tiket Saya → redirect ke daftar tiket ASN via SSO | ☐ |
| 7 | Mulai Live Chat | ASN | Klik Mulai Live Chat → percakapan dibuat otomatis, widget chat terbuka | ☐ |
| 8 | Balas Chat (Operator) | Operator | Login helpdesk → klaim chat dari antrian → kirim pesan → muncul di widget ASN dalam < 5 detik (long-poll) | ☐ |
| 9 | Eskalasi Chat | Operator | Klik "Eskalasi ke Supervisor" → supervisor mendapat notifikasi | ☐ |
| 10 | Konversi Chat ke Tiket | Operator | Klik "Buat Tiket dari Chat" → tiket terbuat terhubung ke percakapan, ID tiket muncul | ☐ |
| 11 | Buat Tiket Manual | ASN | Via SSO → Tiket Saya → Buat Tiket → isi form + lampiran < 500 KB → tiket berhasil dibuat | ☐ |
| 12 | Lampiran > 500 KB | ASN | Coba upload file > 500 KB → sistem menolak dengan pesan error yang jelas | ☐ |
| 13 | Update Status Tiket | Operator | Buka tiket → ubah status ke "Sedang Ditangani" → status berubah di halaman ASN | ☐ |
| 14 | Komentar Internal | Operator | Tambah komentar dengan centang "Internal" → komentar TIDAK muncul di halaman ASN | ☐ |
| 15 | Tutup Tiket (Supervisor) | Supervisor | Buka tiket resolved → klik Tutup → status menjadi closed | ☐ |
| 16 | Backup Command | Admin/SSH | `php artisan helpdesk:backup --tag=smoke-test` → selesai tanpa error | ☐ |
| 17 | Token Single-Use | Penguji | Salin URL SSO redirect → paste di tab baru → harus 401 "Token tidak valid" | ☐ |

**Semua harus PASS sebelum Go-Live.**

Untuk item FAIL: catat di kolom catatan dan eskalasikan ke Koordinator Deploy.

---

## 12. PILOT GO-LIVE CRITERIA

Semua kondisi di bawah WAJIB terpenuhi sebelum pilot dibuka ke pengguna:

```
[ ] 17/17 smoke test PASS (atau semua critical PASS, non-critical dicatat)

[ ] Scheduler aktif dan terverifikasi (cron job berjalan, schedule:list menampilkan 2 tasks)

[ ] Baseline backup berhasil dibuat (file pre-pilot.sql ada, ukuran > 0)

[ ] Super Admin sudah ganti password dari password sementara seeder

[ ] 6 akun Operator sudah dibuat dan bisa login

[ ] GASPUL_API_BASE_URL di .env helpdesk = https://esaraku.gaspul.com/public
    (bukan localhost atau CHANGE_ME)

[ ] HELPDESK_URL di .env gaspul_api = https://helpdesk.gaspul.com
    (bukan localhost atau CHANGE_ME)

[ ] APP_ENV=production dan APP_DEBUG=false di kedua aplikasi

[ ] SESSION_SECURE_COOKIE=true di helpdesk (HTTPS aktif)

[ ] SSL aktif di https://helpdesk.gaspul.com (tidak ada browser warning)

[ ] SOP Operator sudah dibagikan ke semua operator

[ ] Koordinator Deploy menandatangani Go-Live Approval (§15)
```

Jika ada kondisi yang belum terpenuhi: **TUNDA go-live, atasi item, lakukan verifikasi ulang.**

---

## 13. ROLLBACK PLAN

### Level 1 — Rollback gaspul_api Saja (< 10 menit)

Gunakan jika: floating button error, SSO token gagal, atau update gaspul_api menyebabkan masalah.  
**gaspul_api tetap berfungsi normal bahkan jika helpdesk dimatikan.**

```bash
# Kembalikan 14 file ke versi sebelumnya:
cd /home/[user]/gaspul_api

git checkout [commit-sebelum-update] -- \
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

# Hapus HELPDESK_URL dari .env:
sed -i '/HELPDESK_URL/d' .env

# Rebuild cache:
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache

# Verifikasi:
curl -o /dev/null -s -w "%{http_code}" https://esaraku.gaspul.com/login
# → 200 ✅

# ASN tidak melihat floating button lagi → normal untuk rollback ini
```

---

### Level 2 — Rollback Helpdesk (Matikan Layanan) (< 15 menit)

Gunakan jika: helpdesk mengalami error fatal, halaman tidak bisa diakses, atau ada bug kritis di deploy awal.

```bash
# Matikan akses helpdesk sementara:
# Buat file maintenance.html di public/ dan redirect via .htaccess

cd /home/[user]/esaraku_helpdesk/public

cat > maintenance.html << 'EOF'
<!DOCTYPE html>
<html lang="id">
<head><title>e-SARAku Helpdesk — Maintenance</title></head>
<body>
<h1>Sistem Helpdesk Sedang Dalam Pemeliharaan</h1>
<p>Layanan akan kembali dalam waktu dekat. Untuk keperluan mendesak, hubungi Admin.</p>
</body>
</html>
EOF

# Tambahkan redirect sementara ke .htaccess (HANYA jika perlu matikan akses):
cat >> .htaccess << 'EOF'
# ROLLBACK MAINTENANCE
RewriteRule ^(?!maintenance\.html).*$ /maintenance.html [R=302,L]
EOF
```

```bash
# Setelah masalah selesai, hapus baris maintenance dari .htaccess:
# Buka .htaccess via File Manager → hapus 2 baris terakhir
# Hapus maintenance.html
# Rebuild cache:
php artisan config:cache && php artisan route:cache
```

---

### Level 3 — Rollback Database (Kembalikan Data) (< 30 menit)

Gunakan jika: ada data corruption, migration gagal di tengah jalan, atau data konsistensi rusak.

```bash
# PERINGATAN: Ini akan MENGHAPUS SEMUA DATA helpdesk yang sudah masuk

# 1. Matikan akses (Level 2 di atas)

# 2. Drop dan recreate database:
mysql -u root -p -e "
  DROP DATABASE esaraku_helpdesk;
  CREATE DATABASE esaraku_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  GRANT ALL ON esaraku_helpdesk.* TO 'helpdesk_user'@'localhost';
"

# 3. Restore dari backup pre-pilot:
mysql -u helpdesk_user -p esaraku_helpdesk \
  < storage/app/backups/database/helpdesk-db-*pre-pilot.sql

# 4. Clear cache artisan:
php artisan config:clear && php artisan cache:clear

# 5. Re-deploy dari awal (mulai dari T0)
```

---

### Pengambilan Keputusan Rollback

| Kondisi | Action |
|---------|--------|
| Floating button tidak muncul di gaspul_api | Level 1 — rollback gaspul_api |
| SSO redirect error 404/500 | Level 1 — rollback gaspul_api + check HELPDESK_URL |
| Helpdesk loading error 500 | Level 2 — matikan sementara, debug, fix |
| Migration helpdesk gagal di tengah | Level 3 — drop DB + migrate ulang |
| Data ASN tidak muncul setelah SSO | Level 1 check GASPUL_API_BASE_URL + AuthController |
| gaspul_api error 500 setelah update | Level 1 segera — rollback semua 14 file |

---

## 14. POST-DEPLOY MONITORING

### H+1 (Sehari Setelah Go-Live)

```
[ ] Cek error log:
    tail -100 storage/logs/laravel.log | grep -i "error\|exception\|fatal"
    → Idealnya: hanya INFO level, tidak ada error PHP

[ ] Verifikasi scheduler berjalan (backup kemarin sore pukul 02:00):
    ls -lh storage/app/backups/database/ | tail -3
    → Harus ada file dari tanggal hari ini atau kemarin

[ ] Cek volume tiket & chat:
    php artisan tinker --execute="
      echo 'Tiket total: ' . \App\Models\Ticket::count() . PHP_EOL;
      echo 'Chat aktif : ' . \App\Models\Conversation::where('status','open')->count() . PHP_EOL;
    "

[ ] Cek disk usage storage:
    du -sh storage/app/backups/
    du -sh storage/app/private/

[ ] Tanya operator: ada kendala saat menggunakan sistem?
[ ] Tanya ASN pilot: floating button muncul? SSO berhasil?
```

---

### H+7 (Seminggu Setelah Go-Live)

```
[ ] Review log seminggu terakhir:
    grep -i "error\|exception" storage/logs/*.log | wc -l
    → Target: < 10 baris error signifikan

[ ] Verifikasi 7 backup database sudah terbuat (satu per hari):
    ls storage/app/backups/database/ | wc -l
    → Harus ada minimal 7 file

[ ] Cek backup retention berjalan:
    ls storage/app/backups/database/
    → Tidak ada file > 30 hari (meski di minggu pertama belum relevan)

[ ] Review volume penggunaan:
    php artisan tinker --execute="
      use Carbon\Carbon;
      \$week = Carbon::now()->subWeek();
      echo 'Tiket minggu ini: ' . \App\Models\Ticket::where('created_at', '>=', \$week)->count() . PHP_EOL;
      echo 'Chat minggu ini : ' . \App\Models\Conversation::where('created_at', '>=', \$week)->count() . PHP_EOL;
      echo 'Total users     : ' . \App\Models\User::count() . PHP_EOL;
    "

[ ] Cek ukuran storage growth:
    du -sh storage/app/
    → Catat baseline untuk monitoring H+30

[ ] Wawancara singkat operator (5 menit):
    - Ada bug yang ditemukan?
    - Ada fitur yang membingungkan?
    - Volume chat/tiket rasanya wajar?

[ ] Wawancara singkat ASN pilot (5 menit):
    - Floating button mudah ditemukan?
    - SSO berjalan lancar?
    - Ada kendala saat buat tiket?
```

---

### H+30 (Sebulan Setelah Go-Live — Evaluasi Pilot)

```
[ ] Pull rekap lengkap penggunaan:
    php artisan tinker --execute="
      use App\Models\{Ticket, Conversation};
      use Carbon\Carbon;
      \$start = Carbon::now()->subMonth();
      
      echo '=== PILOT H+30 METRICS ===' . PHP_EOL;
      echo 'Tiket dibuat        : ' . Ticket::where('created_at','>=',\$start)->count() . PHP_EOL;
      echo 'Tiket selesai       : ' . Ticket::where('status','closed')->where('updated_at','>=',\$start)->count() . PHP_EOL;
      echo 'Chat dimulai        : ' . Conversation::where('created_at','>=',\$start)->count() . PHP_EOL;
      echo 'Chat ditutup        : ' . Conversation::where('status','closed')->where('created_at','>=',\$start)->count() . PHP_EOL;
      echo 'User terdaftar      : ' . \App\Models\User::count() . PHP_EOL;
    "

[ ] Verifikasi 30 backup terbuat:
    ls storage/app/backups/database/ | wc -l
    → Harus mendekati 30

[ ] Cek backup retention telah aktif (file > 30 hari sudah dihapus):
    ls storage/app/backups/database/ | sort | head -3
    → File tertua tidak boleh lebih dari 30 hari lalu

[ ] Cek disk usage total dan pertumbuhan:
    du -sh storage/app/
    → Bandingkan dengan baseline H+7

[ ] Review semua error log sebulan terakhir untuk pattern:
    grep -i "exception\|error" storage/logs/*.log | grep -v "404\|NOTICE" | sort | uniq -c | sort -rn | head -20

[ ] Evaluasi keputusan rollout:
    Pertanyaan kunci:
    a. Apakah sistem stabil? (target: 0 downtime tidak terencana)
    b. Apakah scheduler berjalan setiap hari? (verifikasi 30 backup)
    c. Apakah feedback operator positif? (target: tidak ada keluhan serius)
    d. Apakah SSO berjalan konsisten? (tidak ada laporan login gagal)
    
    → JIKA semua positif: LANJUT ke rollout penuh unit kerja berikutnya
    → JIKA ada masalah: perbaiki dulu sebelum rollout
```

---

## 15. FINAL GO-LIVE APPROVAL

Dokumen ini harus ditandatangani (atau dikonfirmasi via channel komunikasi resmi) sebelum layanan dibuka ke pengguna pilot.

| Role | Nama Lengkap | Jabatan | Tanggal | Status |
|------|-------------|---------|---------|--------|
| Koordinator Deploy | __________________ | __________________ | __________ | ☐ GO ☐ NO-GO |
| Admin Server | __________________ | __________________ | __________ | ☐ CONFIRMED |
| Admin Database | __________________ | __________________ | __________ | ☐ CONFIRMED |
| Admin Helpdesk | __________________ | __________________ | __________ | ☐ CONFIRMED |
| Perwakilan Operator | __________________ | __________________ | __________ | ☐ CONFIRMED |

### Ringkasan Status Deployment

```
Waktu mulai deploy          : _______________
Waktu selesai smoke test    : _______________
Total durasi cutover        : _______________

URL e_SARAku Helpdesk       : https://helpdesk.gaspul.com
URL e_SARAku (gaspul_api)   : https://esaraku.gaspul.com
Versi helpdesk              : v1.0.0-beta

Migration dijalankan        : 22 migration ✅
Backup pre-pilot dibuat     : ☐ YA — path: _______________________
Scheduler aktif             : ☐ YA
Super Admin password diganti: ☐ YA
17/17 Smoke test PASS       : ☐ YA ☐ Partial (catat: _____________)

KEPUTUSAN FINAL:
☐ GO — Layanan dibuka untuk pilot Kanwil Kemenag Sulbar
☐ NO-GO — Masalah: _______________________________________________

Catatan tambahan:
________________________________________________________________
________________________________________________________________
```

---

## APPENDIX — QUICK REFERENCE

### Command Paling Sering Dibutuhkan

```bash
# Cek scheduler
php artisan schedule:list

# Backup manual
php artisan helpdesk:backup --tag=[nama-tag]

# Cek log terbaru
tail -50 storage/logs/laravel.log

# Rebuild cache (jika .env berubah)
php artisan config:clear && php artisan config:cache

# Cek migration status
php artisan migrate:status | grep Pending

# Cek disk backup
du -sh storage/app/backups/

# Hitung tiket aktif
php artisan tinker --execute="echo \App\Models\Ticket::whereNotIn('status',['closed'])->count();"
```

### Kontak Darurat

| Kebutuhan | Tindakan |
|-----------|---------|
| Server down | Hubungi Admin Server |
| Database error | Hubungi Admin Database + lihat §13 Level 3 |
| SSO gagal semua ASN | Cek GASPUL_API_BASE_URL → Level 1 rollback jika perlu |
| Helpdesk tidak bisa diakses | Cek error log → Level 2 rollback jika perlu |
| Kehilangan data | Level 3 rollback dari backup terbaru |

### Referensi Dokumen

| Dokumen | Lokasi |
|---------|--------|
| Panduan Backup & Restore | `docs/BACKUP_RECOVERY_GUIDE.md` |
| SOP Operator Helpdesk | `docs/SOP_OPERATOR_HELPDESK_v1.0.md` |
| Release Candidate Report | `docs/RELEASE_CANDIDATE_v1.0.0_BETA.md` |
| gaspul_api Update Inventory | `docs/GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md` |
| Deployment Checklist | `docs/RELEASE_DEPLOYMENT_CHECKLIST.md` |
| Scheduler Verification | `docs/PHASE_K2_SCHEDULER_VERIFICATION_REPORT.md` |

---

*PRODUCTION_CUTOVER_PLAN.md — e_SARAku Helpdesk v1.0.0-beta*  
*Dibuat: 2026-06-17 | Status: FINAL*  
*Berlaku untuk: Pilot Kanwil Kementerian Agama Provinsi Sulawesi Barat*
