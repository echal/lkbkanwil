# 🧪 Testing Guide - TAHAP 3 & 4

## ✅ Ready for Testing

Panduan testing untuk Halaman Monitoring Atasan dengan optimasi performa.

---

## 🚀 Setup Prerequisites

### 1. Run Migration

```bash
cd gaspul_api
php artisan migrate
```

Expected output:
```
Migrating: 2026_01_27_235326_add_indexes_to_progres_harian_table_for_performance
Migrated:  2026_01_27_235326_add_indexes_to_progres_harian_table_for_performance (xx.xxms)
```

### 2. Verify Routes

```bash
php artisan route:list --name=atasan.harian-bawahan
```

Expected output:
```
GET|HEAD  atasan/harian-bawahan .............. atasan.harian-bawahan.index
GET|HEAD  atasan/harian-bawahan/export-pdf/{user_id} atasan.harian-bawahan.export-pdf
```

### 3. Create Test Data

**A. Master Atasan (if not exists):**
```sql
-- Create Atasan user (if needed)
INSERT INTO users (name, nip, email, password, role)
VALUES ('Atasan Test', '198901012020011001', 'atasan@test.com', '$2y$10$...', 'ATASAN');

-- Link ASN to Atasan
INSERT INTO master_atasan (asn_id, atasan_id, tahun, status)
VALUES
(2, 1, 2026, 'AKTIF'),  -- ASN ID 2 bawahan dari Atasan ID 1
(3, 1, 2026, 'AKTIF'),  -- ASN ID 3 bawahan dari Atasan ID 1
(4, 1, 2026, 'AKTIF');  -- ASN ID 4 bawahan dari Atasan ID 1
```

**B. Sample Progres Data:**
```sql
-- ASN ID 2: Sudah isi (dengan bukti)
INSERT INTO progres_harian (user_id, tanggal, jam_mulai, jam_selesai, tipe_progres, rencana_kegiatan_harian, progres, satuan, bukti_dukung, status_bukti)
VALUES
(2, CURDATE(), '08:00', '10:00', 'KINERJA_HARIAN', 'Menyusun laporan', 1, 'Dokumen', 'https://drive.google.com/test', 'SUDAH_ADA');

-- ASN ID 3: Sudah isi (tanpa bukti)
INSERT INTO progres_harian (user_id, tanggal, jam_mulai, jam_selesai, tipe_progres, rencana_kegiatan_harian, progres, satuan, bukti_dukung, status_bukti)
VALUES
(3, CURDATE(), '09:00', '11:00', 'KINERJA_HARIAN', 'Membuat presentasi', 1, 'File', NULL, 'BELUM_ADA');

-- ASN ID 4: Belum isi (no data)
```

---

## 📋 Test Scenarios

### ✅ Scenario 1: Login as Atasan

**Steps:**
1. Navigate to: `http://localhost:8000/login`
2. Login dengan credentials:
   - NIP: `198901012020011001` (Atasan)
   - Password: `password`

**Expected:**
- ✅ Redirect to `/atasan/dashboard` atau `/dashboard`
- ✅ Sidebar shows "Harian Bawahan" menu
- ✅ Role badge shows "ATASAN"

---

### ✅ Scenario 2: Access Harian Bawahan Page

**Steps:**
1. Click "Harian Bawahan" di sidebar
2. OR navigate to: `http://localhost:8000/atasan/harian-bawahan`

**Expected:**
- ✅ Page loads successfully
- ✅ Header shows:
  - Nama Atasan
  - NIP
  - Jabatan (if set)
  - Tanggal hari ini
- ✅ Filter form visible (Periode, Tanggal/Bulan)
- ✅ 5 Statistics cards visible
- ✅ Table shows below

**URL:** `/atasan/harian-bawahan`

---

### ✅ Scenario 3: Verify Rekap Statistics

**Test Data:**
- Total ASN: 3
- ASN ID 2: Sudah isi + dengan bukti (HIJAU)
- ASN ID 3: Sudah isi + tanpa bukti (MERAH)
- ASN ID 4: Belum isi (MERAH)

**Expected Statistics:**

| Card | Expected Value |
|------|---------------|
| Total ASN | 3 |
| Sudah Isi | 2 (ASN 2 & 3) |
| Belum Isi | 1 (ASN 4) |
| Dengan Bukti | 1 (ASN 2) |
| Persentase | 66.7% (2/3 * 100) |

**Verification:**
- ✅ Numbers match test data
- ✅ Card colors correct (green/red/blue)
- ✅ Icons display properly

---

### ✅ Scenario 4: Verify Table Data

**Expected Table Rows:**

**Row 1: ASN ID 4 (Belum Isi)**
- NO: 1
- Nama: ASN 4 Name + NIP
- Colspan message: "Belum mengisi progres hari ini"
- Status: 🔴 MERAH
- Aksi: - (disabled)
- Background: `bg-red-50`

**Row 2: ASN ID 2 (Sudah Isi, Dengan Bukti)**
- NO: 2
- Nama: ASN 2 Name + NIP + Total durasi
- Waktu: 08:00 - 10:00 (120m)
- Kegiatan: Menyusun laporan
- Realisasi: 1 Dokumen
- Jenis: Badge "KH" (green)
- Status: 🟢 HIJAU (rowspan)
- Aksi: "Cetak PDF" (rowspan)

**Row 3: ASN ID 3 (Sudah Isi, Tanpa Bukti)**
- NO: 3
- Nama: ASN 3 Name + NIP + Total durasi
- Waktu: 09:00 - 11:00 (120m)
- Kegiatan: Membuat presentasi
- Realisasi: 1 File
- Jenis: Badge "KH" (green)
- Status: 🔴 MERAH (rowspan)
- Aksi: "Cetak PDF" (rowspan)

**Sorting:**
- ✅ Sorted by user name (alphabetical)

---

### ✅ Scenario 5: Filter Harian (Default)

**Steps:**
1. Ensure filter shows "Harian" selected
2. Date input shows today's date
3. Click "Filter" button

**Expected:**
- ✅ Page reloads with same data (today)
- ✅ URL: `/atasan/harian-bawahan?periode=harian&tanggal=2026-01-27`
- ✅ Table shows only progres from today

---

### ✅ Scenario 6: Filter Mingguan

**Steps:**
1. Change filter to "Mingguan"
2. Select a date in current week
3. Click "Filter"

**Expected:**
- ✅ Tanggal input still visible (not hidden)
- ✅ URL: `/atasan/harian-bawahan?periode=mingguan&tanggal=2026-01-27`
- ✅ Table shows progres from entire week (Mon-Sun)
- ✅ Statistics recalculated for week

---

### ✅ Scenario 7: Filter Bulanan

**Steps:**
1. Change filter to "Bulanan"
2. Month input appears (replaces tanggal)
3. Select "2026-01" (January 2026)
4. Click "Filter"

**Expected:**
- ✅ Tanggal input hidden (`x-show="periode !== 'bulanan'"`)
- ✅ Bulan input visible (`x-show="periode === 'bulanan'"`)
- ✅ URL: `/atasan/harian-bawahan?periode=bulanan&bulan=2026-01`
- ✅ Table shows all progres from January 2026
- ✅ Statistics recalculated for entire month

---

### ✅ Scenario 8: PDF Export (Placeholder)

**Steps:**
1. Find ASN that has progres (ASN 2 or 3)
2. Click "Cetak PDF" button in Aksi column

**Expected:**
- ✅ Alert popup: "PDF export coming soon"
- ✅ No error in console
- ✅ (Future: Download PDF file)

---

### ✅ Scenario 9: Empty State (No Bawahan)

**Setup:**
```sql
-- Remove all bawahan for Atasan
DELETE FROM master_atasan WHERE atasan_id = 1;
```

**Steps:**
1. Refresh page

**Expected:**
- ✅ Empty state shows:
  - Icon (users group)
  - Message: "Belum ada bawahan terdaftar"
  - Sub-message: "Hubungi admin untuk menambahkan bawahan"
- ✅ Statistics all show 0
- ✅ No table visible

**Restore Data:**
```sql
INSERT INTO master_atasan (asn_id, atasan_id, tahun, status)
VALUES (2, 1, 2026, 'AKTIF'), (3, 1, 2026, 'AKTIF'), (4, 1, 2026, 'AKTIF');
```

---

### ✅ Scenario 10: Multiple Progres (Same ASN)

**Setup:**
```sql
-- Add TLA for ASN ID 2
INSERT INTO progres_harian (user_id, tanggal, jam_mulai, jam_selesai, tipe_progres, tugas_atasan, bukti_dukung, status_bukti)
VALUES (2, CURDATE(), '13:00', '15:00', 'TUGAS_ATASAN', 'Rapat mendadak dengan Kepala', 'https://drive.google.com/test2', 'SUDAH_ADA');
```

**Expected Table for ASN ID 2:**
- Row 1:
  - NO: 2 (rowspan=2)
  - Nama: ASN 2 (rowspan=2)
  - Waktu: 08:00 - 10:00
  - Kegiatan: Menyusun laporan
  - Realisasi: 1 Dokumen
  - Jenis: KH (green badge)
  - Status: 🟢 HIJAU (rowspan=2)
  - Aksi: Cetak PDF (rowspan=2)
- Row 2:
  - Waktu: 13:00 - 15:00
  - Kegiatan: Rapat mendadak dengan Kepala
  - Realisasi: - (not applicable for TLA)
  - Jenis: TLA (blue badge)

**Total Durasi:**
- ✅ Shows under Nama: "Total: 4j 0m" (120 + 120 = 240 minutes = 4 hours)

---

## 🔍 Performance Testing

### Test 1: Query Count

**Tools:**
```bash
composer require barryvdh/laravel-debugbar --dev
```

**Steps:**
1. Enable Debugbar in `.env`: `APP_DEBUG=true`
2. Visit `/atasan/harian-bawahan`
3. Check "Queries" tab in bottom toolbar

**Expected:**
- ✅ Total queries: **≤ 5 queries**
  - 1x: Get bawahan IDs
  - 1x: Get progres with JOIN
  - 1x: Get users (if needed)
  - 1x: Get rekap (or cached)
  - 1x: Auth check
- ✅ **No N+1 queries**
- ✅ Query time: < 200ms total

---

### Test 2: Cache Verification

**Steps:**
1. First visit: `/atasan/harian-bawahan`
   - Check Debugbar → Queries → Note query time for rekap
2. Refresh page (within 5 minutes)
   - Check Debugbar → Queries → Rekap query should be missing

**Expected:**
- ✅ First load: Rekap query present (~50-100ms)
- ✅ Second load: Rekap from cache (~1-5ms)
- ✅ After 5 minutes: Cache expires, query again

**Manual Cache Clear:**
```bash
php artisan cache:clear
```

---

### Test 3: Index Usage

**SQL Explain:**
```sql
EXPLAIN SELECT ph.*, u.name, u.nip
FROM progres_harian ph
JOIN users u ON ph.user_id = u.id
WHERE ph.user_id IN (2, 3, 4)
AND ph.tanggal = CURDATE();
```

**Expected:**
- ✅ `key`: Shows index name (e.g., `idx_tipe_user_tanggal` or `tanggal`)
- ✅ `type`: `range` or `ref` (NOT `ALL`)
- ✅ `rows`: Small number (< 100)
- ✅ `Extra`: Using index condition

---

### Test 4: Load Time (250 ASN Simulation)

**Setup:**
Create test script to insert 250 ASN with progres:

```php
// database/seeders/LargeScaleTestSeeder.php
for ($i = 1; $i <= 250; $i++) {
    $asn = User::create([
        'name' => "ASN Test $i",
        'nip' => str_pad($i, 18, '0', STR_PAD_LEFT),
        'role' => 'ASN',
        ...
    ]);

    MasterAtasan::create([
        'asn_id' => $asn->id,
        'atasan_id' => 1,  // Your Atasan ID
        'tahun' => 2026,
        'status' => 'AKTIF',
    ]);

    ProgresHarian::create([
        'user_id' => $asn->id,
        'tanggal' => now(),
        'jam_mulai' => '08:00',
        'jam_selesai' => '17:00',
        'tipe_progres' => 'KINERJA_HARIAN',
        ...
    ]);
}
```

**Run:**
```bash
php artisan db:seed --class=LargeScaleTestSeeder
```

**Test:**
1. Visit `/atasan/harian-bawahan`
2. Check Debugbar → Timeline

**Expected:**
- ✅ Page load: < 500ms
- ✅ Query time: < 200ms
- ✅ Render time: < 300ms
- ✅ No timeout errors
- ✅ Memory usage: < 128MB

---

## 🔐 Security Testing

### Test 1: Role Protection

**Steps:**
1. Logout from Atasan
2. Login as ASN (role: 'ASN')
3. Try to access: `/atasan/harian-bawahan`

**Expected:**
- ✅ Redirect to `/dashboard` OR `/login`
- ✅ Error message: "Unauthorized" or "Access denied"
- ✅ No data visible

---

### Test 2: Data Isolation

**Setup:**
```sql
-- Create Atasan 2 with different bawahan
INSERT INTO users (name, nip, role) VALUES ('Atasan 2', '199001012021011001', 'ATASAN');
INSERT INTO master_atasan (asn_id, atasan_id, tahun, status) VALUES (5, 2, 2026, 'AKTIF');
```

**Steps:**
1. Login as Atasan 1
2. Visit `/atasan/harian-bawahan`
3. Verify only bawahan of Atasan 1 visible (ASN 2, 3, 4)
4. Logout and login as Atasan 2
5. Visit `/atasan/harian-bawahan`
6. Verify only bawahan of Atasan 2 visible (ASN 5)

**Expected:**
- ✅ Atasan 1 sees: ASN 2, 3, 4 only
- ✅ Atasan 2 sees: ASN 5 only
- ✅ No cross-data leakage

---

### Test 3: SQL Injection

**Steps:**
1. Try malicious inputs:
   ```
   /atasan/harian-bawahan?tanggal=2026-01-27' OR '1'='1
   /atasan/harian-bawahan?periode=harian'; DROP TABLE users; --
   ```

**Expected:**
- ✅ No SQL error
- ✅ Query uses prepared statements
- ✅ Invalid input ignored or sanitized

---

## 🐛 Common Issues & Solutions

### Issue 1: "Route [atasan.harian-bawahan.index] not defined"

**Solution:**
```bash
php artisan route:clear
php artisan config:clear
php artisan route:list --name=atasan
```

---

### Issue 2: "Class 'App\Http\Controllers\Atasan\HarianBawahanController' not found"

**Solution:**
```bash
composer dump-autoload
```

Verify file exists:
```bash
ls app/Http/Controllers/Atasan/HarianBawahanController.php
```

---

### Issue 3: Migration error "column already exists"

**Solution:**
```bash
# Check existing indexes
php artisan tinker
>>> Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('progres_harian');

# If index exists, skip migration or modify it
```

---

### Issue 4: Sidebar "Harian Bawahan" not showing

**Solution:**
- Verify user role: `auth()->user()->role === 'ATASAN'`
- Clear view cache: `php artisan view:clear`
- Check sidebar file updated correctly

---

### Issue 5: Empty table (no data showing)

**Debug:**
```php
// Add to controller temporarily
dd($bawahan_ids);  // Should show array of IDs
dd($progres_data);  // Should show collection of progres
dd($bawahan_list);  // Should show formatted data
```

**Check:**
- `master_atasan` table has data
- `progres_harian` table has data for today
- `user_id` column exists in `progres_harian`

---

## ✅ Final Acceptance Criteria

| Criterion | Status | Notes |
|-----------|--------|-------|
| Page loads successfully | [ ] | No errors |
| Atasan sees only own bawahan | [ ] | Data isolation |
| ASN belum isi shows RED | [ ] | Clear visual indicator |
| ASN sudah isi shows GREEN/MERAH | [ ] | 2-tier status |
| Filter harian works | [ ] | Correct date filtering |
| Filter mingguan works | [ ] | Week range correct |
| Filter bulanan works | [ ] | Month data correct |
| Statistics accurate | [ ] | Counts match data |
| Cache working | [ ] | 2nd load faster |
| Query count ≤ 5 | [ ] | No N+1 |
| Page load < 200ms | [ ] | For normal dataset |
| Role protection works | [ ] | ASN cannot access |
| No SQL injection | [ ] | Inputs sanitized |

---

## 🎉 Success Indicators

✅ **Functional:** All scenarios pass
✅ **Performance:** Page load < 200ms with caching
✅ **Security:** Role protection + data isolation working
✅ **UX:** Clear visualization, easy to understand
✅ **Scalable:** Works with 250+ ASN without issues

---

**Created by:** Claude Sonnet 4.5
**Date:** 27 Januari 2026
**Version:** TAHAP 3 & 4 Testing Guide
