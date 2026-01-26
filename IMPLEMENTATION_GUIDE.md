# IMPLEMENTATION GUIDE - TOTAL REFACTOR SKP SYSTEM
## Version 2.0.0

**Status**: BACKEND READY ✅
**Tanggal**: 2026-01-25

---

## 📋 SUMMARY - APA YANG SUDAH DIKERJAKAN

### ✅ **FASE 1: BACKEND COMPLETE**

#### 1. **Database Design** ✅
File: [REFACTOR_DATABASE_DESIGN.md](REFACTOR_DATABASE_DESIGN.md)
- ERD lengkap dengan 7 tabel (3 baru, 4 update)
- Validasi bisnis dan contoh data
- Dokumentasi flow lengkap

#### 2. **Migration File** ✅
File: `gaspul_api/database/migrations/2026_01_25_000000_total_refactor_skp_system.php`

**Yang Dilakukan Migration:**
```sql
❌ DROP TABLE: harian, bulanan, rencana_kerja_asn
✅ RENAME: indikator_kinerja → rhk_pimpinan
✅ RECREATE: skp_tahunan & skp_tahunan_detail
✅ CREATE: master_atasan, rencana_aksi_bulanan, progres_harian
```

#### 3. **Models (5 files)** ✅
| File | Status | Features |
|------|--------|----------|
| `RhkPimpinan.php` | Updated | Relationships, scopes, validasi |
| `SkpTahunanDetail.php` | Updated | Auto-generate 12 bulan, update realisasi |
| `MasterAtasan.php` | NEW | Helper methods, static finders |
| `RencanaAksiBulanan.php` | NEW | Auto-calculate capaian, bulan nama |
| `ProgresHarian.php` | NEW | Validasi durasi 7.5 jam, auto-update realisasi |

#### 4. **Controllers (5 files)** ✅
| File | Purpose | Endpoints |
|------|---------|-----------|
| `MasterAtasanController.php` | Admin: Manage ASN-Atasan | CRUD + helpers |
| `RhkPimpinanController.php` | Atasan: Manage RHK | CRUD + by sasaran |
| `SkpTahunanControllerV2.php` | ASN: SKP Header+Detail | Create/Get, Add/Edit/Delete detail, Submit |
| `RencanaAksiBulananController.php` | ASN: Rencana Aksi | List, Update, Summary |
| `ProgresHarianController.php` | ASN: Progres Harian | CRUD, Calendar, Bukti dukung |

#### 5. **Routes** ✅
File: `gaspul_api/routes/api_v2.php`
- Routes terstruktur per role (Admin, Atasan, ASN)
- Prefix `/api/admin/v2`, `/api/atasan/v2`, `/api/asn/v2`
- Ready to include in main `api.php`

---

## 🚀 CARA IMPLEMENTASI

### **STEP 1: BACKUP DATABASE** ⚠️

```bash
# PENTING! Backup database sebelum migration
mysqldump -u root gaspul_api > backup_gaspul_$(date +%Y%m%d_%H%M%S).sql
```

### **STEP 2: INCLUDE ROUTES V2**

Edit file: `gaspul_api/routes/api.php`

Tambahkan di bagian akhir file (sebelum closing tag):

```php
/*
|--------------------------------------------------------------------------
| API ROUTES V2.0 - TOTAL REFACTOR
|--------------------------------------------------------------------------
*/
require __DIR__ . '/api_v2.php';
```

### **STEP 3: RUN MIGRATION**

```bash
cd /c/xampp/htdocs/gaspul/gaspul_api

# Preview migration (dry run)
php artisan migrate:status

# Run migration
php artisan migrate

# Jika ada error, rollback:
php artisan migrate:rollback

# Jika butuh fresh install (HAPUS SEMUA DATA):
php artisan migrate:fresh
```

**OUTPUT YANG DIHARAPKAN:**
```
Migration table created successfully.
Migrating: 2026_01_25_000000_total_refactor_skp_system
Migrated:  2026_01_25_000000_total_refactor_skp_system (XXX ms)
```

### **STEP 4: VERIFY DATABASE**

```sql
-- Check tables created
SHOW TABLES LIKE '%rhk_pimpinan%';
SHOW TABLES LIKE '%master_atasan%';
SHOW TABLES LIKE '%rencana_aksi_bulanan%';
SHOW TABLES LIKE '%progres_harian%';

-- Check old tables dropped
SHOW TABLES LIKE '%harian%';
SHOW TABLES LIKE '%bulanan%';
SHOW TABLES LIKE '%rencana_kerja_asn%';
```

### **STEP 5: SEED DATA (Optional)**

Buat seeder untuk data testing:

```bash
php artisan make:seeder MasterAtasanSeeder
php artisan make:seeder RhkPimpinanSeeder

# Run seeder
php artisan db:seed --class=MasterAtasanSeeder
```

### **STEP 6: TEST API ENDPOINTS**

**Tool**: Postman / Thunder Client / Insomnia

#### Test 1: Login
```http
POST http://localhost:8000/api/login
Content-Type: application/json

{
  "nip": "198001012005011001",
  "password": "password"
}
```

#### Test 2: Create Master Atasan (Admin)
```http
POST http://localhost:8000/api/admin/v2/master-atasan
Authorization: Bearer {token}
Content-Type: application/json

{
  "asn_id": 5,
  "atasan_id": 3,
  "tahun": 2026,
  "status": "AKTIF"
}
```

#### Test 3: Create RHK Pimpinan (Atasan)
```http
POST http://localhost:8000/api/atasan/v2/rhk-pimpinan
Authorization: Bearer {token}
Content-Type: application/json

{
  "sasaran_kegiatan_id": 1,
  "rhk_pimpinan": "Terlaksananya Layanan Keagamaan Berbasis IT",
  "status": "AKTIF"
}
```

#### Test 4: Create SKP Tahunan (ASN)
```http
POST http://localhost:8000/api/asn/v2/skp-tahunan/create-or-get
Authorization: Bearer {token}
Content-Type: application/json

{
  "tahun": 2026
}
```

#### Test 5: Add SKP Detail (ASN)
```http
POST http://localhost:8000/api/asn/v2/skp-tahunan/{skp_id}/detail
Authorization: Bearer {token}
Content-Type: application/json

{
  "rhk_pimpinan_id": 1,
  "target_tahunan": 12,
  "satuan": "Laporan",
  "rencana_aksi": "Melakukan maintenance jaringan internet"
}
```

**Response Expected:**
```json
{
  "message": "Butir kinerja berhasil ditambahkan. 12 periode bulanan telah dibuat.",
  "data": { ... }
}
```

---

## 📊 API ENDPOINTS REFERENCE

### **ADMIN ENDPOINTS**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/admin/v2/master-atasan` | List all master atasan |
| POST | `/api/admin/v2/master-atasan` | Create master atasan |
| GET | `/api/admin/v2/master-atasan/{id}` | Get detail |
| PUT | `/api/admin/v2/master-atasan/{id}` | Update |
| DELETE | `/api/admin/v2/master-atasan/{id}` | Delete |
| GET | `/api/admin/v2/master-atasan/asn/list` | Get ASN list for dropdown |
| GET | `/api/admin/v2/master-atasan/atasan/list` | Get Atasan list for dropdown |

### **ATASAN ENDPOINTS**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/atasan/v2/rhk-pimpinan` | List RHK Pimpinan |
| POST | `/api/atasan/v2/rhk-pimpinan` | Create RHK |
| GET | `/api/atasan/v2/rhk-pimpinan/{id}` | Get detail |
| PUT | `/api/atasan/v2/rhk-pimpinan/{id}` | Update |
| DELETE | `/api/atasan/v2/rhk-pimpinan/{id}` | Delete |
| GET | `/api/atasan/v2/rhk-pimpinan/active/list` | Get active RHK (dropdown) |
| GET | `/api/atasan/v2/rhk-pimpinan/by-sasaran/{id}` | Get by Sasaran Kegiatan |

### **ASN ENDPOINTS**

#### SKP Tahunan
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/asn/v2/skp-tahunan` | List SKP headers |
| GET | `/api/asn/v2/skp-tahunan/{id}` | Get detail with all butir kinerja |
| POST | `/api/asn/v2/skp-tahunan/create-or-get` | Create or get header |
| POST | `/api/asn/v2/skp-tahunan/{id}/submit` | Submit for approval |
| POST | `/api/asn/v2/skp-tahunan/{id}/detail` | Add butir kinerja |
| PUT | `/api/asn/v2/skp-tahunan/{id}/detail/{detailId}` | Update butir kinerja |
| DELETE | `/api/asn/v2/skp-tahunan/{id}/detail/{detailId}` | Delete butir kinerja |

#### Rencana Aksi Bulanan
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/asn/v2/rencana-aksi-bulanan` | List all |
| GET | `/api/asn/v2/rencana-aksi-bulanan/{id}` | Get detail |
| GET | `/api/asn/v2/rencana-aksi-bulanan/by-detail/{detailId}` | Get 12 bulan for SKP detail |
| PUT | `/api/asn/v2/rencana-aksi-bulanan/{id}` | Update (ISI RENCANA AKSI) |
| GET | `/api/asn/v2/rencana-aksi-bulanan/summary/year` | Get summary per tahun |

#### Progres Harian
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/asn/v2/progres-harian` | List all |
| GET | `/api/asn/v2/progres-harian/{id}` | Get detail |
| POST | `/api/asn/v2/progres-harian` | Create new progres |
| PUT | `/api/asn/v2/progres-harian/{id}` | Update |
| DELETE | `/api/asn/v2/progres-harian/{id}` | Delete |
| POST | `/api/asn/v2/progres-harian/by-date` | Get by specific date |
| POST | `/api/asn/v2/progres-harian/calendar` | Get calendar data |
| PUT | `/api/asn/v2/progres-harian/{id}/bukti-dukung` | Upload bukti |

---

## 🔄 NEXT STEPS (FRONTEND)

Setelah backend testing selesai, lanjut ke frontend:

### 1. **Master Atasan (Admin)**
- Tabel CRUD master atasan
- Dropdown ASN & Atasan
- Filter by tahun

### 2. **RHK Pimpinan (Atasan)**
- Form manage RHK
- Dropdown Sasaran Kegiatan
- List with usage count

### 3. **SKP Tahunan V2 (ASN)**
- ❌ HAPUS: Tampilan Sasaran Kegiatan
- ✅ TAMPILKAN: Hanya RHK Pimpinan dropdown
- Form add detail: RHK + Target + Satuan + Rencana Aksi
- Validasi unique: RHK + Rencana Aksi

### 4. **Rencana Aksi Bulanan (ASN)**
- Tab view 12 bulan
- Form isi: Rencana Aksi + Target + Satuan
- Progress bar per bulan

### 5. **Progres Harian (ASN)**
- Kalender bulanan
- Tab: Progres Bulanan, Mingguan, Harian
- Form input: Tanggal + Jam Mulai/Selesai + Kegiatan
- Validasi: Max 7 jam 30 menit per hari
- Upload bukti (link Google Drive)
- Status visual: Bar merah (belum ada bukti)

---

## ⚠️ IMPORTANT NOTES

### **Database Changes**
- ❌ **PERMANENT DELETE**: `harian`, `bulanan`, `rencana_kerja_asn`
- ✅ **BACKUP CREATED**: `skp_tahunan_backup_v1`, `skp_tahunan_detail_backup_v1`
- ⚠️ **NO ROLLBACK**: Data lama tidak dapat direstore otomatis

### **Breaking Changes**
1. Frontend lama **TIDAK COMPATIBLE** dengan backend baru
2. API endpoints berubah total (prefix `/v2`)
3. Request/response format berubah

### **Migration Strategy**
**Opsi 1: Fresh Start (Recommended)**
```bash
php artisan migrate:fresh
php artisan db:seed
```

**Opsi 2: Preserve Data**
- Manual data migration required
- Map old structure to new structure
- Review backup files

---

## 📞 SUPPORT

Jika ada error:
1. Check Laravel log: `storage/logs/laravel.log`
2. Check database migrations: `php artisan migrate:status`
3. Review error messages di response API
4. Baca dokumentasi: [REFACTOR_DATABASE_DESIGN.md](REFACTOR_DATABASE_DESIGN.md)

---

**Status**: READY FOR TESTING ✅
**Next**: Run migration → Test API → Build frontend
