# PEROMBAKAN TOTAL - DATABASE DESIGN
## Sistem Manajemen Kinerja ASN (GASPUL)

**Tanggal**: 2026-01-25
**Versi**: 2.0.0 (Total Refactor)
**Arsitektur**: Clean Architecture + Business-Driven Design

---

## 📋 RINGKASAN PERUBAHAN

### ❌ TABEL YANG DIHAPUS
1. **`rencana_kerja_asn`** (SKP Triwulan) - DIHAPUS TOTAL
2. **`bulanan`** (Rencana Kerja Bulanan versi lama) - DIHAPUS TOTAL
3. **`harian`** (Laporan Kegiatan Harian versi lama) - DIHAPUS TOTAL

### ✅ TABEL YANG DIUBAH/DITAMBAH
1. **`sasaran_kegiatan`** - TETAP (tanpa perubahan)
2. **`indikator_kinerja`** → **`rhk_pimpinan`** - RENAME + RESTRUCTURE
3. **`skp_tahunan`** - REVISI TOTAL (struktur baru)
4. **`skp_tahunan_detail`** - REVISI TOTAL (struktur baru)
5. **`rencana_aksi_bulanan`** - TABEL BARU (menggantikan `bulanan`)
6. **`progres_harian`** - TABEL BARU (menggantikan `harian`)
7. **`master_atasan`** - TABEL BARU

---

## 🗂️ STRUKTUR TABEL BARU

### 1. **`sasaran_kegiatan`** (TIDAK BERUBAH)
**Peran**: Dikelola oleh **Kepala Kantor**
**Fungsi**: Master sasaran strategis organisasi/unit kerja

```sql
CREATE TABLE sasaran_kegiatan (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    unit_kerja VARCHAR(255) NOT NULL COMMENT 'Nama unit kerja',
    sasaran_kegiatan TEXT NOT NULL COMMENT 'Sasaran strategis organisasi',
    status ENUM('AKTIF', 'NONAKTIF') DEFAULT 'AKTIF',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_status (status),
    INDEX idx_unit_kerja (unit_kerja)
);
```

**Contoh Data**:
```
ID  | Unit Kerja       | Sasaran Kegiatan
----|------------------|----------------------------------
1   | Sekretariat      | Terlaksananya Layanan Keagamaan Berbasis IT
```

---

### 2. **`rhk_pimpinan`** (RENAME dari `indikator_kinerja`)
**Peran**: Dikelola oleh **Atasan Langsung / Kabid**
**Fungsi**: Rencana Hasil Kerja Pimpinan yang di-intervensi

```sql
-- DROP TABLE indikator_kinerja IF EXISTS
-- CREATE TABLE rhk_pimpinan
CREATE TABLE rhk_pimpinan (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sasaran_kegiatan_id BIGINT UNSIGNED NOT NULL,
    rhk_pimpinan TEXT NOT NULL COMMENT 'Rencana Hasil Kerja Pimpinan yang di Intervensi',
    status ENUM('AKTIF', 'NONAKTIF') DEFAULT 'AKTIF',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY fk_rhk_sasaran (sasaran_kegiatan_id)
        REFERENCES sasaran_kegiatan(id) ON DELETE CASCADE,

    INDEX idx_status (status),
    INDEX idx_sasaran_kegiatan_id (sasaran_kegiatan_id)
);
```

**Contoh Data**:
```
ID  | Sasaran Kegiatan ID | RHK Pimpinan
----|---------------------|----------------------------------------
1   | 1                   | Terlaksananya Layanan Keagamaan Berbasis IT
```

**Catatan Migrasi**:
```sql
-- Migrate data dari indikator_kinerja ke rhk_pimpinan
INSERT INTO rhk_pimpinan (id, sasaran_kegiatan_id, rhk_pimpinan, status, created_at, updated_at)
SELECT id, sasaran_kegiatan_id, indikator_kinerja, status, created_at, updated_at
FROM indikator_kinerja;
```

---

### 3. **`master_atasan`** (TABEL BARU)
**Peran**: Dikelola oleh **Admin**
**Fungsi**: Relasi antara ASN dengan Atasan Langsung

```sql
CREATE TABLE master_atasan (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    asn_id BIGINT UNSIGNED NOT NULL COMMENT 'ID User (ASN/PPPK)',
    atasan_id BIGINT UNSIGNED NOT NULL COMMENT 'ID User (Atasan Langsung)',
    tahun YEAR NOT NULL COMMENT 'Tahun berlaku',
    status ENUM('AKTIF', 'NONAKTIF') DEFAULT 'AKTIF',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY fk_master_atasan_asn (asn_id)
        REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY fk_master_atasan_atasan (atasan_id)
        REFERENCES users(id) ON DELETE CASCADE,

    UNIQUE KEY unique_asn_per_year (asn_id, tahun),
    INDEX idx_atasan_id (atasan_id),
    INDEX idx_tahun (tahun),
    INDEX idx_status (status)
);
```

**Contoh Data**:
```
ID  | ASN ID | Atasan ID | Tahun | Status
----|--------|-----------|-------|-------
1   | 5      | 3         | 2026  | AKTIF
```

---

### 4. **`skp_tahunan`** (REVISI TOTAL)
**Peran**: Dikelola oleh **ASN / PPPK**
**Fungsi**: Header SKP Tahunan (user_id + tahun + status)

**PERUBAHAN KRUSIAL**:
- ❌ **HAPUS**: `sasaran_kegiatan_id`, `indikator_kinerja_id` dari header
- ✅ **UNIQUE**: Hanya `user_id` + `tahun`
- ✅ ASN boleh membuat SKP Tahunan **SEKALI per tahun**, tetapi bisa punya **banyak detail**

```sql
-- DROP & RECREATE skp_tahunan
DROP TABLE IF EXISTS skp_tahunan_detail;
DROP TABLE IF EXISTS skp_tahunan;

CREATE TABLE skp_tahunan (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL COMMENT 'ID ASN/PPPK',
    tahun YEAR NOT NULL COMMENT 'Tahun SKP',
    status ENUM('DRAFT', 'DIAJUKAN', 'DISETUJUI', 'DITOLAK') DEFAULT 'DRAFT',
    catatan_atasan TEXT NULL COMMENT 'Catatan dari atasan',
    approved_by BIGINT UNSIGNED NULL COMMENT 'ID Atasan yang approve',
    approved_at TIMESTAMP NULL COMMENT 'Waktu approval',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY fk_skp_tahunan_user (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY fk_skp_tahunan_approved_by (approved_by)
        REFERENCES users(id) ON DELETE SET NULL,

    UNIQUE KEY unique_skp_per_user_per_year (user_id, tahun),
    INDEX idx_status (status),
    INDEX idx_tahun (tahun),
    INDEX idx_user_tahun_status (user_id, tahun, status)
);
```

---

### 5. **`skp_tahunan_detail`** (REVISI TOTAL)
**Peran**: Dikelola oleh **ASN / PPPK**
**Fungsi**: Detail SKP Tahunan (Butir Kinerja)

**PERUBAHAN KRUSIAL**:
- ❌ **HAPUS**: `sasaran_kegiatan_id` (tidak perlu ditampilkan)
- ✅ **UBAH**: `indikator_kinerja_id` → `rhk_pimpinan_id`
- ✅ **TAMBAH**: `rencana_aksi` (TEXT - rencana aksi ASN)
- ✅ **NO UNIQUE CONSTRAINT** - ASN boleh tambah RHK yang sama **berkali-kali**
- ✅ **VALIDASI UNIQUE**: `skp_tahunan_id` + `rhk_pimpinan_id` + `rencana_aksi` (di level aplikasi)

```sql
CREATE TABLE skp_tahunan_detail (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    skp_tahunan_id BIGINT UNSIGNED NOT NULL COMMENT 'ID SKP Tahunan Header',
    rhk_pimpinan_id BIGINT UNSIGNED NOT NULL COMMENT 'ID RHK Pimpinan',
    target_tahunan INT UNSIGNED NOT NULL COMMENT 'Target tahunan',
    satuan VARCHAR(50) NOT NULL COMMENT 'Satuan target',
    rencana_aksi TEXT NOT NULL COMMENT 'Rencana Aksi ASN untuk mencapai RHK',
    realisasi_tahunan INT UNSIGNED DEFAULT 0 COMMENT 'Realisasi tahunan (aggregated)',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY fk_std_skp_tahunan (skp_tahunan_id)
        REFERENCES skp_tahunan(id) ON DELETE CASCADE,
    FOREIGN KEY fk_std_rhk_pimpinan (rhk_pimpinan_id)
        REFERENCES rhk_pimpinan(id) ON DELETE RESTRICT,

    INDEX idx_skp_tahunan_id (skp_tahunan_id),
    INDEX idx_rhk_pimpinan_id (rhk_pimpinan_id)
);
```

**Contoh Data**:
```
ID  | SKP Tahunan ID | RHK Pimpinan ID | Target | Satuan   | Rencana Aksi
----|----------------|-----------------|--------|----------|-----------------------------------
1   | 1              | 1               | 12     | Laporan  | Melakukan maintenance jaringan internet
2   | 1              | 1               | 5      | Kegiatan | Membuat dokumentasi sistem IT
```

**VALIDASI APLIKASI** (bukan database):
```php
// Di Controller: Cek apakah kombinasi ini sudah ada
$exists = SkpTahunanDetail::where('skp_tahunan_id', $skpTahunanId)
    ->where('rhk_pimpinan_id', $rhkPimpinanId)
    ->where('rencana_aksi', $rencanaAksi)
    ->exists();

if ($exists) {
    throw new Exception('RHK Pimpinan dengan Rencana Aksi yang sama sudah ada');
}
```

---

### 6. **`rencana_aksi_bulanan`** (TABEL BARU - menggantikan `bulanan`)
**Peran**: Dikelola oleh **ASN / PPPK**
**Fungsi**: Breakdown SKP Tahunan Detail menjadi rencana aksi bulanan

**ALUR BARU**:
1. Sistem **AUTO-GENERATE** periode bulanan setelah SKP Tahunan Detail dibuat
2. ASN mengisi **Rencana Aksi Bulanan** untuk setiap bulan
3. **Target Bulanan** + **Satuan Target** diisi ASN

```sql
CREATE TABLE rencana_aksi_bulanan (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    skp_tahunan_detail_id BIGINT UNSIGNED NOT NULL COMMENT 'ID SKP Tahunan Detail',
    bulan TINYINT UNSIGNED NOT NULL COMMENT 'Bulan (1-12)',
    tahun YEAR NOT NULL COMMENT 'Tahun',
    rencana_aksi_bulanan TEXT NULL COMMENT 'Rencana Aksi Bulanan (diisi ASN)',
    target_bulanan INT UNSIGNED DEFAULT 0 COMMENT 'Target bulanan (diisi ASN)',
    satuan_target VARCHAR(100) NULL COMMENT 'Dokumen, Data, Laporan, Kegiatan, Persentase, Berkas, Dokumentasi',
    realisasi_bulanan INT UNSIGNED DEFAULT 0 COMMENT 'Realisasi bulanan (sum dari progres_harian)',
    status ENUM('BELUM_DIISI', 'AKTIF', 'SELESAI') DEFAULT 'BELUM_DIISI',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY fk_rab_skp_detail (skp_tahunan_detail_id)
        REFERENCES skp_tahunan_detail(id) ON DELETE CASCADE,

    UNIQUE KEY unique_aksi_per_bulan (skp_tahunan_detail_id, bulan, tahun),
    INDEX idx_bulan_tahun (bulan, tahun),
    INDEX idx_status (status)
);
```

**Contoh Data**:
```
ID  | SKP Detail ID | Bulan | Tahun | Rencana Aksi Bulanan             | Target | Satuan
----|---------------|-------|-------|----------------------------------|--------|--------
1   | 1             | 1     | 2026  | Melakukan maintenance jaringan   | 2      | Laporan
2   | 1             | 2     | 2026  | Melakukan maintenance jaringan   | 2      | Laporan
```

**AUTO-GENERATE Logic**:
```php
// Setelah SKP Tahunan Detail dibuat, generate 12 bulan
for ($bulan = 1; $bulan <= 12; $bulan++) {
    RencanaAksiBulanan::create([
        'skp_tahunan_detail_id' => $detailId,
        'bulan' => $bulan,
        'tahun' => $tahun,
        'status' => 'BELUM_DIISI',
    ]);
}
```

---

### 7. **`progres_harian`** (TABEL BARU - menggantikan `harian`)
**Peran**: Dikelola oleh **ASN / PPPK**
**Fungsi**: Input progres harian berbasis Rencana Aksi Bulanan

**FITUR BARU**:
- ✅ **Jam Mulai** + **Jam Selesai** (validasi total 7 jam 30 menit per hari)
- ✅ **Bukti Dukung** (Google Drive link) - MANDATORY
- ✅ **Status Visual** (Bar merah jika belum ada bukti)

```sql
CREATE TABLE progres_harian (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    rencana_aksi_bulanan_id BIGINT UNSIGNED NOT NULL COMMENT 'ID Rencana Aksi Bulanan',
    tanggal DATE NOT NULL COMMENT 'Tanggal kegiatan',
    jam_mulai TIME NOT NULL COMMENT 'Jam mulai kegiatan',
    jam_selesai TIME NOT NULL COMMENT 'Jam selesai kegiatan',
    durasi_menit INT UNSIGNED GENERATED ALWAYS AS (
        TIMESTAMPDIFF(MINUTE,
            CONCAT(tanggal, ' ', jam_mulai),
            CONCAT(tanggal, ' ', jam_selesai)
        )
    ) STORED COMMENT 'Durasi kerja dalam menit (auto-calculated)',
    rencana_kegiatan_harian TEXT NOT NULL COMMENT 'Deskripsi rencana kegiatan harian',
    progres INT UNSIGNED DEFAULT 0 COMMENT 'Progres kegiatan (angka)',
    satuan VARCHAR(50) NOT NULL COMMENT 'Satuan progres',
    bukti_dukung TEXT NULL COMMENT 'Link Google Drive atau link lainnya',
    status_bukti ENUM('BELUM_ADA', 'SUDAH_ADA') DEFAULT 'BELUM_ADA',
    keterangan TEXT NULL COMMENT 'Keterangan tambahan',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY fk_ph_rencana_aksi (rencana_aksi_bulanan_id)
        REFERENCES rencana_aksi_bulanan(id) ON DELETE CASCADE,

    INDEX idx_tanggal (tanggal),
    INDEX idx_rencana_aksi_tanggal (rencana_aksi_bulanan_id, tanggal),
    INDEX idx_status_bukti (status_bukti)
);
```

**Contoh Data**:
```
ID  | Rencana Aksi Bulanan ID | Tanggal    | Jam Mulai | Jam Selesai | Rencana Kegiatan
----|-------------------------|------------|-----------|-------------|-----------------------------------
1   | 1                       | 2026-01-20 | 08:00     | 10:30       | Membuat schedule maintenance pada Sekretariat
2   | 1                       | 2026-01-21 | 08:00     | 12:00       | Melakukan pengecekan jaringan
```

**VALIDASI WAKTU KERJA**:
```php
// Total durasi kerja per hari harus = 7 jam 30 menit (450 menit)
$totalDurasiHariIni = ProgresHarian::whereDate('tanggal', $tanggal)
    ->where('rencana_aksi_bulanan_id', $rencanaAksiId)
    ->sum('durasi_menit');

if ($totalDurasiHariIni + $durasiBaruMenit > 450) {
    throw new Exception('Total waktu kerja harian tidak boleh melebihi 7 jam 30 menit');
}
```

---

## 📊 ERD RELASI FINAL

```
┌─────────────────────┐
│  sasaran_kegiatan   │ (Kepala Kantor)
│  - id               │
│  - unit_kerja       │
│  - sasaran_kegiatan │
└──────────┬──────────┘
           │ 1
           │
           │ N
┌──────────▼──────────┐
│   rhk_pimpinan      │ (Atasan / Kabid)
│  - id               │
│  - sasaran_kegiatan_id
│  - rhk_pimpinan     │
└──────────┬──────────┘
           │ 1
           │
           │ N
┌──────────▼──────────┐       ┌─────────────────┐
│ skp_tahunan_detail  │       │  skp_tahunan    │ (ASN)
│  - id               │◄──────┤  - id           │
│  - skp_tahunan_id   │ N   1 │  - user_id      │
│  - rhk_pimpinan_id  │       │  - tahun        │
│  - target_tahunan   │       │  - status       │
│  - satuan           │       └─────────────────┘
│  - rencana_aksi     │
└──────────┬──────────┘
           │ 1
           │
           │ N
┌──────────▼──────────┐
│ rencana_aksi_bulanan│ (ASN)
│  - id               │
│  - skp_tahunan_detail_id
│  - bulan            │
│  - tahun            │
│  - rencana_aksi_bulanan
│  - target_bulanan   │
│  - satuan_target    │
└──────────┬──────────┘
           │ 1
           │
           │ N
┌──────────▼──────────┐
│   progres_harian    │ (ASN)
│  - id               │
│  - rencana_aksi_bulanan_id
│  - tanggal          │
│  - jam_mulai        │
│  - jam_selesai      │
│  - rencana_kegiatan_harian
│  - bukti_dukung     │
└─────────────────────┘

┌─────────────────────┐
│   master_atasan     │ (Admin)
│  - id               │
│  - asn_id           │
│  - atasan_id        │
│  - tahun            │
└─────────────────────┘
```

---

## 🔄 FLOW BISNIS BARU

### A. ADMIN
1. Kelola **Unit Kerja** (units)
2. Kelola **Master Pegawai** (users)
3. Kelola **Master Atasan** (master_atasan) ← BARU
4. Approve Laporan

### B. KEPALA KANTOR
1. Kelola **Sasaran Kegiatan** (sasaran_kegiatan)

### C. ATASAN LANGSUNG / KABID
1. Kelola **RHK Pimpinan** (rhk_pimpinan)
2. Review & Approve **SKP Tahunan** ASN

### D. ASN / PPPK
1. **SKP Tahunan**:
   - Pilih **RHK Pimpinan** (HANYA RHK, tidak tampilkan Sasaran)
   - Input **Target Tahunan**, **Satuan**, **Rencana Aksi**
   - Boleh pilih RHK yang sama **berkali-kali**, asal **Rencana Aksi berbeda**

2. **Rencana Aksi Bulanan**:
   - Sistem auto-generate 12 bulan setelah SKP Detail dibuat
   - ASN isi: **Rencana Aksi Bulanan**, **Target Bulanan**, **Satuan Target**

3. **Progres Harian**:
   - Klik kalender, pilih tanggal
   - Input: **Jam Mulai**, **Jam Selesai**, **Rencana Kegiatan Harian**
   - Total waktu kerja harian: **7 jam 30 menit**
   - Status visual: **Bar merah** jika belum ada **Bukti Dukung**
   - Klik bar merah → Isi **Link Google Drive** → Status berubah **Selesai**

---

## ✅ VALIDASI BARU

### 1. SKP Tahunan Header
```php
// UNIQUE: user_id + tahun
$exists = SkpTahunan::where('user_id', $userId)
    ->where('tahun', $tahun)
    ->exists();
```

### 2. SKP Tahunan Detail
```php
// UNIQUE: skp_tahunan_id + rhk_pimpinan_id + rencana_aksi (di aplikasi)
$exists = SkpTahunanDetail::where('skp_tahunan_id', $skpId)
    ->where('rhk_pimpinan_id', $rhkId)
    ->where('rencana_aksi', $rencanaAksi)
    ->exists();
```

### 3. Rencana Aksi Bulanan
```php
// UNIQUE: skp_tahunan_detail_id + bulan + tahun (database constraint)
// Status: BELUM_DIISI → AKTIF (setelah diisi) → SELESAI (setelah bulan selesai)
```

### 4. Progres Harian
```php
// Validasi total waktu kerja per hari = 7 jam 30 menit (450 menit)
$totalDurasi = ProgresHarian::whereDate('tanggal', $tanggal)
    ->sum('durasi_menit');

if ($totalDurasi + $durasiBaru > 450) {
    throw new Exception('Total waktu kerja harian melebihi 7 jam 30 menit');
}
```

---

## 🚨 CATATAN MIGRASI

### 1. DROP TABLE LAMA (dalam urutan ini)
```sql
DROP TABLE IF EXISTS harian;           -- Drop dulu (FK ke bulanan)
DROP TABLE IF EXISTS bulanan;          -- Drop kedua (FK ke rencana_kerja_asn)
DROP TABLE IF EXISTS rencana_kerja_asn; -- Drop ketiga
```

### 2. RENAME & MIGRATE DATA
```sql
-- Rename indikator_kinerja → rhk_pimpinan
ALTER TABLE indikator_kinerja RENAME TO rhk_pimpinan;
ALTER TABLE rhk_pimpinan CHANGE indikator_kinerja rhk_pimpinan TEXT NOT NULL;
```

### 3. RECREATE SKP TAHUNAN
```sql
-- Backup dulu
RENAME TABLE skp_tahunan_detail TO skp_tahunan_detail_backup;
RENAME TABLE skp_tahunan TO skp_tahunan_backup;

-- Create new structure
CREATE TABLE skp_tahunan (...);
CREATE TABLE skp_tahunan_detail (...);

-- Migrate data (manual review required)
```

---

## 📝 CONTOH DATA LENGKAP

```sql
-- 1. Sasaran Kegiatan (Kepala Kantor)
INSERT INTO sasaran_kegiatan VALUES
(1, 'Sekretariat', 'Terlaksananya Layanan Keagamaan Berbasis IT', 'AKTIF', NOW(), NOW());

-- 2. RHK Pimpinan (Atasan / Kabid)
INSERT INTO rhk_pimpinan VALUES
(1, 1, 'Terlaksananya Layanan Keagamaan Berbasis IT', 'AKTIF', NOW(), NOW());

-- 3. SKP Tahunan (ASN - user_id = 5, tahun 2026)
INSERT INTO skp_tahunan VALUES
(1, 5, 2026, 'DRAFT', NULL, NULL, NULL, NOW(), NOW());

-- 4. SKP Tahunan Detail (ASN bisa tambah RHK yang sama berkali-kali)
INSERT INTO skp_tahunan_detail VALUES
(1, 1, 1, 12, 'Laporan', 'Melakukan maintenance jaringan internet', 0, NOW(), NOW()),
(2, 1, 1, 5, 'Kegiatan', 'Membuat dokumentasi sistem IT', 0, NOW(), NOW());

-- 5. Rencana Aksi Bulanan (Auto-generate 12 bulan untuk setiap detail)
INSERT INTO rencana_aksi_bulanan VALUES
(1, 1, 1, 2026, 'Melakukan maintenance jaringan', 2, 'Laporan', 0, 'AKTIF', NOW(), NOW());

-- 6. Progres Harian
INSERT INTO progres_harian VALUES
(1, 1, '2026-01-20', '08:00', '10:30', NULL, 'Membuat schedule maintenance pada Sekretariat', 1, 'Laporan', 'https://drive.google.com/...', 'SUDAH_ADA', NULL, NOW(), NOW());
```

---

## 🎯 NEXT STEPS

1. ✅ Review dokumen ini
2. Buat migration file
3. Buat/update models
4. Buat/update controllers
5. Buat/update frontend
6. Testing end-to-end

---

**Status**: READY FOR IMPLEMENTATION
**Approval**: PENDING USER CONFIRMATION
