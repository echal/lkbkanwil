# GASPUL_API — PRE-COMMIT AUDIT
**Tanggal       :** 2026-06-19  
**Mode          :** AUDIT ONLY — tidak ada perubahan kode  
**Root git repo :** `c:/xampp/htdocs/gaspul/` (monorepo)  
**Subdir audit  :** `gaspul_api/`  
**Commit terakhir:** `fcb80f8` feat: add monitoring TV Pasangkayu, sticky header & auto-scroll  

---

## RINGKASAN NUMERIK

| Kategori | Jumlah |
|----------|--------|
| File **modified** (tracked, ada perubahan) | 60 |
| File/folder **untracked** (baru, belum di-commit) | 74 file individual |
| File **staged** | 0 |
| **Total akan masuk commit** | ~134 file |

---

## 1. INVENTARIS FILE MODIFIED (60 file)

### A. Controllers — Modified

| File | Keterangan |
|------|-----------|
| `app/Http/Controllers/Admin/PegawaiController.php` | Form pegawai + atasan_id + hari_kerja |
| `app/Http/Controllers/Admin/UnitKerjaController.php` | Hierarki unit kerja + hari_kerja |
| `app/Http/Controllers/Api/AuthController.php` | ⚠️ **HELPDESK CRITICAL** — single-use SSO token delete (J-05/J-06) |
| `app/Http/Controllers/Asn/BulananController.php` | Laporan bulanan + rekap absensi PUSAKA |
| `app/Http/Controllers/Asn/HarianController.php` | Kinerja harian + verifikasi bukti |
| `app/Http/Controllers/Asn/LaporanCetakController.php` | Cetak PDF harian/bulanan |
| `app/Http/Controllers/Asn/SkpTahunanController.php` | SKP + submit safety guard |
| `app/Http/Controllers/Atasan/ApprovalController.php` | Approval rekap/laporan/SKP |
| `app/Http/Controllers/Atasan/HarianBawahanController.php` | Monitoring harian bawahan (atasan_id) |
| `app/Http/Controllers/Atasan/KinerjaBawahanController.php` | Monitoring kinerja bawahan |
| `app/Http/Controllers/Atasan/RekapKinerjaCetakController.php` | Cetak rekap kinerja |
| `app/Http/Controllers/Atasan/SkpTahunanAtasanController.php` | SKP bawahan view atasan |
| `app/Http/Controllers/Auth/LoginController.php` | Login throttle J-02 |
| `app/Http/Controllers/MonitoringKakanwilController.php` | Monitoring TV Kakanwil + Pasangkayu |

**Subtotal: 14 controller modified**

### B. Models — Modified

| File | Keterangan |
|------|-----------|
| `app/Models/IndikatorKinerja.php` | Relasi tambahan |
| `app/Models/LaporanBulananKinerja.php` | Snapshot fields + target_jam |
| `app/Models/ProgresHarian.php` | Kolom verifikasi + idx bukti |
| `app/Models/RekapAbsensiPusaka.php` | Status kankemenag + kakanwil fields |
| `app/Models/RencanaAksiBulanan.php` | Relasi update |
| `app/Models/UnitKerja.php` | hari_kerja field |
| `app/Models/User.php` | hari_kerja field |

**Subtotal: 7 model modified**

### C. Services — Modified

| File | Keterangan |
|------|-----------|
| `app/Services/LaporanBulananService.php` | Filter bulan + status approval |
| `app/Services/RekapAbsensiService.php` | getForKabid/getForKakanwil |

**Subtotal: 2 service modified**

### D. Policies — Modified

| File | Keterangan |
|------|-----------|
| `app/Policies/SkpTahunanPolicy.php` | Hierarki approval + revision |

### E. Helpers — Modified

| File | Keterangan |
|------|-----------|
| `app/Helpers/HolidayHelper.php` | User-aware 6 hari kerja (Stage 2) |

### F. Providers — Modified

| File | Keterangan |
|------|-----------|
| `app/Providers/AppServiceProvider.php` | ⚠️ **HELPDESK CRITICAL** — `Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class)` |

### G. Config — Modified

| File | Keterangan | Helpdesk |
|------|-----------|---------|
| `config/app.php` | Timezone, locale update | — |
| `config/cors.php` | ⚠️ **HELPDESK CRITICAL** — whitelist localhost helpdesk | ✅ |
| `config/services.php` | ⚠️ **HELPDESK CRITICAL** — +`helpdesk.url` = `env('HELPDESK_URL', ...)` | ✅ |

### H. Bootstrap & Public — Modified

| File | Keterangan | Helpdesk |
|------|-----------|---------|
| `bootstrap/app.php` | ⚠️ **HELPDESK CRITICAL** — `append(PinMysqlDatabase::class)` | ✅ |
| `public/index.php` | ⚠️ **HELPDESK CRITICAL** — `Env::disablePutenv()` runtime isolation | ✅ |
| `public/check-db.php` | ⚠️ **MASALAH** — file audit READ-ONLY, ada komentar "HAPUS setelah selesai" | ❌ |

### I. Blade Views — Modified

| File | Keterangan |
|------|-----------|
| `resources/views/admin/pegawai/edit.blade.php` | +atasan_id + hari_kerja field |
| `resources/views/admin/pegawai/tambah.blade.php` | +atasan_id + hari_kerja field |
| `resources/views/admin/unit-kerja/edit.blade.php` | +hari_kerja dropdown |
| `resources/views/admin/unit-kerja/tambah.blade.php` | +hari_kerja dropdown |
| `resources/views/asn/bulanan/edit.blade.php` | Laporan bulanan UI |
| `resources/views/asn/bulanan/index.blade.php` | Laporan bulanan + rekap PUSAKA tab |
| `resources/views/asn/bulanan/partials/rekap-detail-harian.blade.php` | Rekap detail harian |
| `resources/views/asn/bulanan/partials/ringkasan.blade.php` | Ringkasan bulanan |
| `resources/views/asn/harian/calendar.blade.php` | Kalender kinerja harian |
| `resources/views/asn/harian/form-kinerja.blade.php` | Form input kinerja |
| `resources/views/asn/harian/form-tla.blade.php` | Form TLA |
| `resources/views/asn/harian/index.blade.php` | Index harian |
| `resources/views/asn/harian/pilih.blade.php` | Pilih jenis kinerja |
| `resources/views/asn/laporan/pdf/bulanan.blade.php` | PDF bulanan |
| `resources/views/asn/laporan/pdf/harian.blade.php` | PDF harian |
| `resources/views/asn/laporan/pdf/kinerja-harian-single.blade.php` | PDF single |
| `resources/views/asn/skp-tahunan/create.blade.php` | Form SKP |
| `resources/views/asn/skp-tahunan/edit.blade.php` | Edit SKP |
| `resources/views/asn/skp-tahunan/index.blade.php` | Daftar SKP |
| `resources/views/atasan/approval/index.blade.php` | Daftar approval |
| `resources/views/atasan/harian-bawahan/detail.blade.php` | Detail harian bawahan |
| `resources/views/atasan/harian-bawahan/index.blade.php` | Index harian bawahan |
| `resources/views/atasan/skp-tahunan/index.blade.php` | SKP bawahan |
| `resources/views/components/sidebar.blade.php` | Sidebar navigasi |
| `resources/views/layouts/app.blade.php` | ⚠️ **HELPDESK CRITICAL** — +@include floating-button + chat-widget | ✅ |
| `resources/views/monitoring/kakanwil.blade.php` | Monitoring TV Kakanwil |

**Subtotal: 26 view modified**

### J. Routes — Modified

| File | Keterangan | Helpdesk |
|------|-----------|---------|
| `routes/api.php` | Route API update | — |
| `routes/web.php` | ⚠️ **HELPDESK CRITICAL** — +`POST /api/helpdesk-token` route | ✅ |

---

## 2. INVENTARIS FILE UNTRACKED (74 file — BARU, belum pernah di-commit)

### A. Controllers — Baru

| File | Tipe | Helpdesk |
|------|------|---------|
| `app/Http/Controllers/Api/HelpdeskTokenController.php` | ⚠️ **HELPDESK CRITICAL** | ✅ SSO Token Generator |
| `app/Http/Controllers/Admin/IndikatorUnitKerjaController.php` | Non-helpdesk | — |
| `app/Http/Controllers/Admin/KalenderLiburKhususController.php` | Non-helpdesk | — |
| `app/Http/Controllers/Admin/KoreksiLaporanController.php` | Non-helpdesk | — |
| `app/Http/Controllers/Admin/RotasiJabatanController.php` | Non-helpdesk | — |
| `app/Http/Controllers/Admin/SurveiMonitoringController.php` | Non-helpdesk | — |
| `app/Http/Controllers/Asn/CutiController.php` | Non-helpdesk | — |
| `app/Http/Controllers/Asn/TutorialController.php` | Non-helpdesk | — |
| `app/Http/Controllers/MonitoringBimasIslamController.php` | Non-helpdesk | — |
| `app/Http/Controllers/MonitoringMamasaController.php` | Non-helpdesk | — |
| `app/Http/Controllers/MonitoringMamujuTengahController.php` | Non-helpdesk | — |
| `app/Http/Controllers/SurveiController.php` | Non-helpdesk | — |

### B. Middleware — Baru

| File | Tipe | Helpdesk |
|------|------|---------|
| `app/Http/Middleware/PinMysqlDatabase.php` | ⚠️ **HELPDESK CRITICAL** | ✅ Runtime DB isolation |

### C. Models — Baru

| File | Tipe | Helpdesk |
|------|------|---------|
| `app/Models/PersonalAccessToken.php` | ⚠️ **HELPDESK CRITICAL** | ✅ Sanctum override + DB pin |
| `app/Models/CutiAsn.php` | Non-helpdesk | — |
| `app/Models/KalenderLiburKhusus.php` | Non-helpdesk | — |
| `app/Models/Survei.php` | Non-helpdesk | — |
| `app/Models/SurveiJawaban.php` | Non-helpdesk | — |
| `app/Models/SurveiPertanyaan.php` | Non-helpdesk | — |

### D. Services — Baru

| File | Keterangan |
|------|-----------|
| `app/Services/WorkingTimeService.php` | Kalkulasi jam kerja 5/6 hari |
| `app/Services/LiburKhususService.php` | Libur khusus per-instansi |
| `app/Services/SubordinateService.php` | Helper bawahan chain |

### E. Helpers — Baru

| File | Keterangan |
|------|-----------|
| `app/Helpers/EvidenHelper.php` | Helper evidensi kinerja |

### F. Console Commands — Baru

| File | Keterangan |
|------|-----------|
| `app/Console/Commands/BackfillSkpRealisasi.php` | Backfill realisasi SKP lama |

### G. Config — Baru

| File | Keterangan |
|------|-----------|
| `config/tutorial.php` | Konfigurasi tutorial video |
| `config/working_time.php` | Konfigurasi jam kerja 5/6 hari |

### H. Migrations — Baru (13 migration)

| File | Fungsi |
|------|--------|
| `2026_03_09_000001_create_indikator_unit_kerja_table.php` | Indikator per unit kerja |
| `2026_04_06_000001_change_progres_columns_to_decimal.php` | Decimal progres |
| `2026_05_02_000001_add_kankemenag_status_to_rekap_absensi_pusaka.php` | Status kankemenag rekap |
| `2026_05_02_000002_add_hari_kerja_to_unit_kerja_and_users.php` | 6 hari kerja |
| `2026_05_02_203751_add_target_jam_to_laporan_bulanan_kinerja.php` | Target jam bulanan |
| `2026_05_06_000001_create_laporan_koreksi_log_table.php` | Log koreksi laporan |
| `2026_05_09_000001_create_survei_table.php` | Tabel survei |
| `2026_05_09_000002_create_survei_pertanyaan_table.php` | Pertanyaan survei |
| `2026_05_09_000003_create_survei_jawaban_table.php` | Jawaban survei |
| `2026_05_14_000001_add_snapshot_fields_to_laporan_bulanan_kinerja_table.php` | Snapshot fields |
| `2026_05_26_000001_create_cuti_asn_table.php` | Cuti ASN |
| `2026_05_31_000001_add_verifikasi_to_progres_harian.php` | Kolom verifikasi |
| `2026_06_03_000001_add_idx_bukti_verified_to_progres_harian.php` | Index performa |
| `2026_06_06_000001_create_kalender_libur_khusus_table.php` | Kalender libur instansi |

**Total: 14 migration baru**

### I. Seeders — Baru

| File | Keterangan |
|------|-----------|
| `database/seeders/SurveiSeeder.php` | Data awal survei |

### J. Views — Baru

| File | Helpdesk |
|------|---------|
| `resources/views/components/helpdesk-floating-button.blade.php` | ✅ **HELPDESK CRITICAL** |
| `resources/views/components/helpdesk-chat-widget.blade.php` | ✅ **HELPDESK CRITICAL** |
| `resources/views/errors/503.blade.php` | Halaman maintenance branded |
| `resources/views/admin/indikator-unit-kerja/index.blade.php` | — |
| `resources/views/admin/kalender-libur-khusus/edit.blade.php` | — |
| `resources/views/admin/kalender-libur-khusus/index.blade.php` | — |
| `resources/views/admin/kalender-libur-khusus/tambah.blade.php` | — |
| `resources/views/admin/koreksi-laporan/index.blade.php` | — |
| `resources/views/admin/rotasi-jabatan/index.blade.php` | — |
| `resources/views/admin/survei/index.blade.php` | — |
| `resources/views/asn/cuti/create.blade.php` | — |
| `resources/views/asn/cuti/index.blade.php` | — |
| `resources/views/asn/tutorial/index.blade.php` | — |
| `resources/views/atasan/monitoring-verifikasi/index.blade.php` | — |
| `resources/views/components/survei-popup.blade.php` | — |
| `resources/views/monitoring/bimas-islam.blade.php` | — |
| `resources/views/monitoring/mamasa.blade.php` | — |
| `resources/views/monitoring/mamuju-tengah-tv.blade.php` | — |
| `resources/views/survei/show.blade.php` | — |

### K. Documentation — Baru

| File | Keterangan |
|------|-----------|
| `docs/CHANGELOG_IMPLEMENTASI_ESARAKU.md` | Changelog umum |
| `docs/GASPUL_API_HELPDESK_INTEGRATION_AUDIT.md` | Audit pre-integrasi Phase I |
| `docs/GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md` | Inventaris update production helpdesk |
| `docs/LAPORAN_ERROR_500_KINERJA_HARIAN.md` | Laporan error sementara |
| `docs/LAPORAN_KEGIATAN_DASHBOARD_MAMASA_12062026.md` | Laporan kegiatan monitoring |
| `docs/PHASE_H_INTEGRATION_IMPACT_AUDIT.md` | Audit dampak integrasi Phase H |
| `docs/PHASE_IA_FIX_REPORT.md` | Laporan fix Phase I.A |
| `docs/PHASE_I_INTEGRATION_DESIGN.md` | Desain integrasi Phase I |
| `docs/PRODUCTION_CUTOVER_PLAN.md` | Panduan cutover production |
| `database/laporan_pengembangan_monitoring_kakanwil_15juni2026.md` | Laporan pengembangan |

### L. File Lain (Root & Public) — Baru

| File | Keterangan | Risk |
|------|-----------|------|
| `check_nip_min2.php` | Script debug ad-hoc | ⚠️ TIDAK BOLEH COMMIT |
| `check_unit.php` | Script debug ad-hoc | ⚠️ TIDAK BOLEH COMMIT |
| `check_unit2.php` | Script debug ad-hoc | ⚠️ TIDAK BOLEH COMMIT |
| `public/check-error.php` | Script debug dengan secret key hardcoded | ⚠️ TIDAK BOLEH COMMIT |

---

## 3. TEMUAN KEAMANAN & KUALITAS

### 3.1 Credential Hardcoded — BERSIH ✅

```
grep -rn "APP_KEY|password.*=.*'" app/ config/ --include="*.php"
→ Tidak ada credential literal di source code PHP
→ Semua password menggunakan validation rules (required|min:8), bukan nilai hardcoded
→ HASIL: ✅ BERSIH
```

### 3.2 Debug Statements (dd/dump/var_dump) — BERSIH ✅

```
grep -rn "dd(\|dump(\|var_dump(\|print_r(" app/ --include="*.php"
→ Tidak ada match nyata (0 baris)
→ HASIL: ✅ BERSIH
```

### 3.3 Log::debug Sementara — BERSIH ✅

```
grep -rn "Log::debug\|logger()->debug" app/ --include="*.php"
→ Tidak ada log debug yang ditinggalkan
→ HASIL: ✅ BERSIH
```

### 3.4 File SQL/Backup di Root — DIKECUALIKAN ✅

```
File ditemukan: gaspulco_lkbkanwil_db 0306.sql, gaspulco_lkbkanwil_db1.sql, gaspulco_lkbkanwil_db_0603.sql
git check-ignore → .gitignore:23:*.sql → SEMUA TER-IGNORE
→ HASIL: ✅ File backup SQL tidak akan masuk commit
```

### 3.5 File Debug PHP Ad-Hoc — ⚠️ PERLU PERHATIAN

| File | Status di .gitignore | Masalah |
|------|---------------------|---------|
| `check_nip_min2.php` | **TIDAK ter-ignore** — akan masuk commit | ⚠️ Script debug, tidak layak production |
| `check_unit.php` | **TIDAK ter-ignore** — akan masuk commit | ⚠️ Script debug |
| `check_unit2.php` | **TIDAK ter-ignore** — akan masuk commit | ⚠️ Script debug |
| `public/check-db.php` | **MODIFIED, tidak ter-ignore** — akan masuk commit | ⚠️ Ada komentar "HAPUS setelah selesai!" |
| `public/check-error.php` | **TIDAK ter-ignore** — akan masuk commit | ⚠️ Ada secret key: `migrate-esaraku-2026` |

**`public/check-error.php` paling berisiko** — script dengan query-string secret yang tersedia publik di URL `/check-error.php`.

### 3.6 .env.production.example — ⚠️ TER-IGNORE OLEH .gitignore

```
git check-ignore -v gaspul_api/.env.production.example
→ .gitignore:10:**/.env.* → TER-IGNORE
```

Pola `**/.env.*` menangkap `.env.production.example`. File ini **tidak akan masuk commit** kecuali di-whitelist dulu.

Namun karena file ini berisi template aman (semua CHANGE_ME), ada dua opsi:
- **Opsi A:** Biarkan ter-ignore — admin server butuh diberi file ini secara manual (via SCP/FTP)
- **Opsi B:** Tambahkan whitelist `!gaspul_api/.env.production.example` ke .gitignore — template bisa di-commit bersama source code

### 3.7 Localhost URL di config/cors.php — ⚠️ MEDIUM RISK

```php
'allowed_origins' => [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    'http://localhost',       // esaraku_helpdesk (XAMPP)
    'http://localhost:8000',
    ...
]
// Tidak ada: https://helpdesk.gaspul.com
```

Di production, CORS hanya perlu `https://helpdesk.gaspul.com` (dan `https://esaraku.gaspul.com`). Localhost entries tidak merusak di production (karena tidak ada request dari localhost), tapi sebaiknya ditambahkan domain production untuk kejelasan dan keamanan CORS yang proper.

**Saat ini:** Widget helpdesk dan SSO berjalan via web session (same-origin dengan gaspul_api), bukan via fetch() cross-origin CORS ke endpoint API — sehingga risiko ini **Low** untuk fungsionalitas SSO. Namun untuk completeness sebaiknya ditambahkan.

### 3.8 HELPDESK_URL Fallback Localhost — Low Risk

```php
// config/services.php
'url' => env('HELPDESK_URL', 'http://localhost/esaraku_helpdesk/public'),
```

Fallback ke localhost jika `HELPDESK_URL` tidak di-set di `.env` production. Sudah terdokumentasi sebagai WAJIB ISI di `PRODUCTION_CUTOVER_PLAN.md` Step T-10.

---

## 4. VERIFIKASI KOMPONEN HELPDESK PRODUCTION

Checklist 14 file MANDATORY dari `GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md`:

| # | File | Ada di working tree | Masalah |
|---|------|---------------------|---------|
| 1 | `app/Http/Controllers/Api/HelpdeskTokenController.php` | ✅ (untracked) | Siap commit |
| 2 | `app/Http/Middleware/PinMysqlDatabase.php` | ✅ (untracked) | Siap commit |
| 3 | `app/Models/PersonalAccessToken.php` | ✅ (untracked) | Siap commit |
| 4 | `app/Http/Controllers/Api/AuthController.php` | ✅ (modified) | Siap commit |
| 5 | `app/Providers/AppServiceProvider.php` | ✅ (modified) | Siap commit |
| 6 | `bootstrap/app.php` | ✅ (modified) | Siap commit |
| 7 | `config/services.php` | ✅ (modified) | Siap commit — fallback localhost (low risk, override via env) |
| 8 | `config/cors.php` | ✅ (modified) | ⚠️ Domain production belum ditambahkan — **lihat §3.7** |
| 9 | `public/index.php` | ✅ (modified) | Siap commit |
| 10 | `resources/views/components/helpdesk-floating-button.blade.php` | ✅ (untracked) | Siap commit |
| 11 | `resources/views/components/helpdesk-chat-widget.blade.php` | ✅ (untracked) | Siap commit |
| 12 | `resources/views/layouts/app.blade.php` | ✅ (modified) | Siap commit |
| 13 | `routes/web.php` | ✅ (modified) | Siap commit |
| 14 | `.env.production.example` | ⚠️ **TER-IGNORE** oleh .gitignore | Butuh whitelist atau distribusi manual |

---

## 5. KLASIFIKASI FILE

### CRITICAL — Wajib ikut commit deployment helpdesk

| File | Alasan |
|------|--------|
| `app/Http/Controllers/Api/HelpdeskTokenController.php` | SSO token generator |
| `app/Http/Middleware/PinMysqlDatabase.php` | Runtime DB isolation |
| `app/Models/PersonalAccessToken.php` | Sanctum override |
| `app/Http/Controllers/Api/AuthController.php` | Single-use token |
| `app/Providers/AppServiceProvider.php` | Sanctum registration |
| `bootstrap/app.php` | Middleware global |
| `config/services.php` | HELPDESK_URL key |
| `config/cors.php` | CORS whitelist |
| `public/index.php` | Env isolation |
| `resources/views/components/helpdesk-floating-button.blade.php` | Widget UI |
| `resources/views/components/helpdesk-chat-widget.blade.php` | Widget UI |
| `resources/views/layouts/app.blade.php` | Include widget |
| `routes/web.php` | Route SSO |

### PENTING — Fungsionalitas e-Kinerja (bukan helpdesk, tapi harus ikut)

Semua 60 file modified sisanya (controllers ASN/Atasan, models, services, views) berisi perubahan fungsionalitas e-Kinerja yang sudah berjalan di development dan perlu disinkronisasi ke production.

### TUNDA / EXCLUDE — Jangan commit sekarang

| File | Alasan |
|------|--------|
| `check_nip_min2.php` | Script debug ad-hoc — hapus atau exclude dari staging |
| `check_unit.php` | Script debug ad-hoc |
| `check_unit2.php` | Script debug ad-hoc |
| `public/check-db.php` | File audit dengan komentar "HAPUS setelah selesai" — exclude atau hapus |
| `public/check-error.php` | Script dengan secret key hardcoded — **harus dihapus** dari server production |

### AMAN DITUNDA — Fitur belum selesai / tidak blocking deployment

| File/Group | Alasan tunda |
|-----------|-------------|
| `app/Http/Controllers/Admin/RotasiJabatanController.php` + views | Fitur rotasi jabatan belum production-ready |
| `app/Http/Controllers/Admin/SurveiMonitoringController.php` + models survei + views survei | Fitur survei belum selesai |
| `app/Http/Controllers/Asn/CutiController.php` + `app/Models/CutiAsn.php` + views cuti | Fitur cuti belum selesai |
| `app/Console/Commands/BackfillSkpRealisasi.php` | Command backfill — tidak mendesak |
| `database/seeders/SurveiSeeder.php` | Seeder survei — belum diperlukan |

---

## 6. MASALAH YANG PERLU DISELESAIKAN SEBELUM COMMIT

### BLOCKER (harus diselesaikan):

1. **`public/check-error.php`** — file ini mengandung `$secret = 'migrate-esaraku-2026'` dan bisa diakses publik di URL `/check-error.php`. Harus **dihapus dari server** sebelum commit (atau ditambahkan ke .gitignore).

2. **`config/cors.php` belum punya domain production** — perlu menambahkan `https://helpdesk.gaspul.com` ke `allowed_origins`. Saat ini hanya ada localhost entries.

3. **`.env.production.example` ter-ignore** — butuh keputusan: whitelist di .gitignore ATAU distribusi manual ke server.

### NON-BLOCKER (disarankan diselesaikan):

4. **`check_nip_min2.php`, `check_unit.php`, `check_unit2.php`** — exclude dari staging (jangan di-`git add`) atau hapus dari working tree.

5. **`public/check-db.php`** — file modified dengan komentar "HAPUS setelah selesai" — exclude atau hapus.

---

## 7. REKOMENDASI COMMIT

### Opsi A — Commit Targeted (Rekomendasi)

Hanya commit file yang diperlukan untuk deployment helpdesk + update e-Kinerja yang sudah stabil. Exclude file debug:

```bash
# Stage file CRITICAL helpdesk
git add gaspul_api/app/Http/Controllers/Api/HelpdeskTokenController.php
git add gaspul_api/app/Http/Middleware/PinMysqlDatabase.php
git add gaspul_api/app/Models/PersonalAccessToken.php
git add gaspul_api/app/Http/Controllers/Api/AuthController.php
git add gaspul_api/app/Providers/AppServiceProvider.php
git add gaspul_api/bootstrap/app.php
git add gaspul_api/config/services.php
git add gaspul_api/config/cors.php
git add gaspul_api/public/index.php
git add gaspul_api/resources/views/components/helpdesk-floating-button.blade.php
git add gaspul_api/resources/views/components/helpdesk-chat-widget.blade.php
git add gaspul_api/resources/views/layouts/app.blade.php
git add gaspul_api/routes/web.php
git add gaspul_api/routes/api.php

# Stage semua modified files e-Kinerja (kecuali check-db.php)
git add gaspul_api/app/Helpers/HolidayHelper.php
git add gaspul_api/app/Http/Controllers/Admin/
git add gaspul_api/app/Http/Controllers/Asn/
git add gaspul_api/app/Http/Controllers/Atasan/
git add gaspul_api/app/Http/Controllers/Auth/LoginController.php
git add gaspul_api/app/Http/Controllers/MonitoringKakanwilController.php
git add gaspul_api/app/Models/
git add gaspul_api/app/Policies/
git add gaspul_api/app/Services/
git add gaspul_api/config/app.php
git add gaspul_api/resources/views/admin/
git add gaspul_api/resources/views/asn/
git add gaspul_api/resources/views/atasan/
git add gaspul_api/resources/views/components/sidebar.blade.php
git add gaspul_api/resources/views/monitoring/kakanwil.blade.php
git add gaspul_api/resources/views/errors/503.blade.php

# Stage controllers non-helpdesk yang sudah stabil
git add gaspul_api/app/Http/Controllers/MonitoringBimasIslamController.php
git add gaspul_api/app/Http/Controllers/MonitoringMamasaController.php
git add gaspul_api/app/Http/Controllers/MonitoringMamujuTengahController.php
git add gaspul_api/app/Services/WorkingTimeService.php
git add gaspul_api/app/Services/LiburKhususService.php
git add gaspul_api/app/Services/SubordinateService.php
git add gaspul_api/app/Helpers/EvidenHelper.php
git add gaspul_api/config/tutorial.php
git add gaspul_api/config/working_time.php

# Stage migrations
git add gaspul_api/database/migrations/

# Stage docs
git add gaspul_api/docs/

# JANGAN stage:
# gaspul_api/check_nip_min2.php
# gaspul_api/check_unit.php
# gaspul_api/check_unit2.php
# gaspul_api/public/check-db.php  (atau hapus dulu)
# gaspul_api/public/check-error.php  (HAPUS dari server dulu)
# gaspul_api/database/laporan_pengembangan_monitoring_kakanwil_15juni2026.md
# gaspul_api/app/Http/Controllers/Admin/RotasiJabatanController.php (tunda)
# gaspul_api/app/Http/Controllers/Admin/SurveiMonitoringController.php (tunda)
# gaspul_api/app/Http/Controllers/Asn/CutiController.php (tunda)
```

### Commit Message Rekomendasi

```
feat: helpdesk SSO integration + e-Kinerja updates (Phase I–K.3, Stage 2–4)

HELPDESK INTEGRATION (Phase I–J):
- HelpdeskTokenController: POST /api/helpdesk-token — SSO token 5-menit TTL
- PinMysqlDatabase middleware: runtime DB isolation Apache PDO pool
- PersonalAccessToken: Sanctum override USE gaspulco_lkbkanwil_db
- AuthController: single-use helpdesk-sso token (race-proof DB row lock)
- AppServiceProvider: Sanctum::usePersonalAccessTokenModel()
- bootstrap/app.php: PinMysqlDatabase global middleware
- config/services.php: HELPDESK_URL config key
- config/cors.php: whitelist domain helpdesk
- public/index.php: Env::disablePutenv() env isolation
- layouts/app.blade.php: @include floating-button + chat-widget
- routes/web.php: POST /api/helpdesk-token (throttle:10,1)
- Floating button + embedded chat widget components

E-KINERJA UPDATES:
- HolidayHelper: user-aware 6-hari kerja (Stage 2)
- WorkingTimeService: kalkulasi target jam (Stage 3)
- LaporanBulananService/RekapAbsensiService: filter + status update
- MonitoringKakanwilController: TV monitoring + Pasangkayu + histori
- 14 migration baru: hari_kerja, survei, cuti, verifikasi, koreksi, libur khusus
- Maintenance page 503 branded eSARAKu

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

---

## FINAL VERDICT

```
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║   ⚠️  NOT READY TO COMMIT — 3 BLOCKER HARUS DISELESAIKAN DULU               ║
║                                                                              ║
║   BLOCKER 1: public/check-error.php                                          ║
║   → Hapus file ini dari server sebelum commit                                ║
║   → Secret key `migrate-esaraku-2026` bisa diakses publik                   ║
║                                                                              ║
║   BLOCKER 2: config/cors.php belum ada domain production                     ║
║   → Tambahkan https://helpdesk.gaspul.com ke allowed_origins                ║
║   → Tanpa ini, widget helpdesk tidak bisa CORS ke API jika dibutuhkan        ║
║                                                                              ║
║   BLOCKER 3: .env.production.example ter-ignore .gitignore                   ║
║   → Ambil keputusan: whitelist di .gitignore ATAU distribusi manual          ║
║   → Template ini wajib ada di server production untuk panduan sysadmin       ║
║                                                                              ║
║   SETELAH 3 blocker diselesaikan:                                            ║
║   → Exclude check_nip_min2.php, check_unit*.php, public/check-db.php        ║
║      dari git add                                                            ║
║   → Jalankan commit targeted (lihat §7)                                     ║
║   → Status akan menjadi: ✅ READY TO COMMIT                                  ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝
```

| Metrik | Nilai |
|--------|-------|
| File modified | 60 |
| File untracked baru | 74 |
| File CRITICAL helpdesk | 13 (semua ada di working tree) ✅ |
| Credential hardcoded | 0 ✅ |
| Debug dd()/dump() | 0 ✅ |
| File SQL/backup | 0 (semua ter-ignore) ✅ |
| Blocker keamanan | 3 ⚠️ |
| Verdict | **NOT READY** (perlu selesaikan 3 blocker) |

---

*GASPUL_API_PRE_COMMIT_AUDIT.md — e_SARAku v1.0*  
*Tanggal audit: 2026-06-19 | Mode: AUDIT ONLY*
