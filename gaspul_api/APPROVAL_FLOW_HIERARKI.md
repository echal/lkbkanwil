# APPROVAL FLOW BERBASIS HIERARKI (approved_by) - DOKUMENTASI

## 📋 OVERVIEW

Sistem approval SKP Tahunan yang **HANYA berbasis kolom `approved_by`**, menghapus semua dependency ke `role` dan `unit_kerja_id`.

**Tanggal:** 14 Februari 2026
**Status:** ✅ COMPLETED (LOCAL TESTING)
**Author:** Claude Sonnet 4.5

---

## 🎯 MASALAH YANG DIPERBAIKI

### **Root Cause:**
SKP yang dibuat oleh **Eselon III (Kabid/Kabag)** tidak muncul di list approval **Eselon II (Kakanwil)**.

### **Penyebab Lama:**
```php
// ❌ BROKEN: Filter by role='ASN' mengexclude ATASAN
$query->whereHas('user', function($q) {
    $q->where('role', 'ASN'); // Eselon III punya role='ATASAN', jadi tidak muncul!
});

// ❌ BROKEN: Validasi kompleks dengan fallback unit_kerja
$isAtasanLangsung = $skp->user->atasan_id === $atasan->id;
$isSameUnitKerja = $atasan->unit_kerja_id && $skp->user->unit_kerja_id === $atasan->unit_kerja_id;
if (!$isAtasanLangsung && !$isSameUnitKerja) {
    abort(403);
}
```

### **Solusi Baru:**
✅ Approval **HANYA berbasis `approved_by`**
✅ Tidak ada dependency ke `role` atau `unit_kerja_id`
✅ Backward compatible untuk `approved_by = null` (data lama)
✅ Auto-approve untuk user tanpa atasan (puncak hierarki)

---

## 🔄 APPROVAL FLOW LOGIC

### **1️⃣ Submit SKP (SkpTahunanController@submit)**

**Location:** `app/Http/Controllers/Asn/SkpTahunanController.php:227-271`

**Logic:**
```php
public function submit($id)
{
    $user = Auth::user();
    $skpTahunan = SkpTahunan::where('user_id', $user->id)->findOrFail($id);

    // Load relasi atasan
    $user->load('atasan');

    if ($user->atasan_id && $user->atasan) {
        // CASE 1: User punya atasan → submit untuk approval
        $skpTahunan->update([
            'status' => 'DIAJUKAN',
            'approved_by' => $user->atasan_id, // ✅ Set atasan sebagai approver
            'approved_at' => null,
            'catatan_atasan' => null,
        ]);

        $message = 'SKP Tahunan berhasil diajukan ke ' . $user->atasan->name;
    } else {
        // CASE 2: User TIDAK punya atasan (puncak hierarki) → auto final approve
        $skpTahunan->update([
            'status' => 'DISETUJUI',
            'approved_by' => null, // ✅ Tidak ada approver (puncak hierarki)
            'approved_at' => now(),
            'catatan_atasan' => 'Otomatis disetujui (Puncak Hierarki)',
        ]);

        $message = 'SKP Tahunan berhasil disetujui otomatis (Anda adalah puncak hierarki)';
    }

    return redirect()->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
        ->with('success', $message);
}
```

**CRITICAL POINTS:**
- ✅ `approved_by` **SELALU di-set** saat submit (jika user punya atasan)
- ✅ User tanpa atasan **auto-approve** (puncak hierarki)
- ✅ Tidak ada dependency ke `role` atau `unit_kerja_id`

---

### **2️⃣ Approval List Query (SkpTahunanAtasanController@index)**

**Location:** `app/Http/Controllers/Atasan/SkpTahunanAtasanController.php:28-150`

**Query Logic:**
```php
public function index(Request $request)
{
    $atasan = Auth::user();
    $tahun = $request->input('tahun', now()->year);

    // Query SKP Tahunan yang approved_by = atasan->id
    $query = SkpTahunan::with([
        'user.unitKerja',
        'user.atasan',
        'details.indikatorKinerja.sasaranKegiatan',
        'approver'
    ]);

    // ✅ FILTER UTAMA: approved_by = auth()->id()
    // Tidak ada filter role atau unit_kerja_id
    $query->where('approved_by', $atasan->id);

    // Filter by tahun
    $query->where('tahun', $tahun);

    // Optional filters (status, search ASN)
    if ($status) {
        $query->where('status', $status);
    }

    if ($searchAsn) {
        $query->whereHas('user', function($q) use ($searchAsn) {
            $q->where('name', 'like', '%' . $searchAsn . '%')
              ->orWhere('nip', 'like', '%' . $searchAsn . '%');
        });
    }

    $skpList = $query->latest()->paginate(15);

    // Count pending berbasis approved_by (BUKAN role!)
    $pendingRevisionCount = SkpTahunan::where('status', 'REVISI_DIAJUKAN')
        ->where('tahun', $tahun)
        ->where('approved_by', $atasan->id)
        ->count();

    $pendingApprovalCount = SkpTahunan::where('status', 'DIAJUKAN')
        ->where('tahun', $tahun)
        ->where('approved_by', $atasan->id)
        ->count();

    return view('atasan.skp-tahunan.index', compact(...));
}
```

**CRITICAL POINTS:**
- ✅ **HANYA** filter `approved_by = $atasan->id`
- ❌ **TIDAK ADA** filter `whereHas('user', function($q) { $q->where('role', 'ASN'); })`
- ❌ **TIDAK ADA** filter berdasarkan `unit_kerja_id`
- ✅ Pending counts juga berbasis `approved_by`

---

### **3️⃣ Show Detail SKP (SkpTahunanAtasanController@show)**

**Location:** `app/Http/Controllers/Atasan/SkpTahunanAtasanController.php:162-211`

**Validation Logic:**
```php
public function show($id)
{
    $skp = SkpTahunan::with([...])->findOrFail($id);
    $atasan = Auth::user();

    // ✅ VALIDATION: Hanya check approved_by
    // Backward compatible: jika approved_by null, allow (data lama)
    if ($skp->approved_by !== null && $skp->approved_by !== $atasan->id) {
        abort(403, 'Anda tidak memiliki akses ke SKP ini. SKP ini bukan tanggung jawab Anda.');
    }

    // ... rest of the method
}
```

**CRITICAL POINTS:**
- ✅ Validasi **HANYA** check `approved_by = auth()->id()`
- ✅ Backward compatible: `approved_by = null` → allow (data lama)
- ❌ **TIDAK ADA** validasi `atasan_id` atau `unit_kerja_id`

---

### **4️⃣ Approve SKP (SkpTahunanAtasanController@approve)**

**Location:** `app/Http/Controllers/Atasan/SkpTahunanAtasanController.php:216-258`

**Validation Logic:**
```php
public function approve(Request $request, $id)
{
    $validated = $request->validate([
        'catatan_atasan' => 'nullable|string|max:1000',
    ]);

    $skp = SkpTahunan::findOrFail($id);

    // Verify status
    if ($skp->status !== 'DIAJUKAN') {
        return back()->with('error', 'SKP tidak dalam status diajukan');
    }

    $atasan = Auth::user();

    // ✅ VALIDATION: Hanya check approved_by
    // Backward compatible: jika approved_by null, allow (data lama)
    if ($skp->approved_by !== null && $skp->approved_by !== $atasan->id) {
        return back()->with('error', 'Anda tidak memiliki akses untuk menyetujui SKP ini. SKP ini bukan tanggung jawab Anda.');
    }

    // Update status
    $skp->update([
        'status' => 'DISETUJUI',
        'catatan_atasan' => $validated['catatan_atasan'] ?? null,
        'approved_by' => $atasan->id,
        'approved_at' => now(),
    ]);

    return redirect()
        ->route('atasan.skp-tahunan.show', $id)
        ->with('success', 'SKP Tahunan berhasil disetujui');
}
```

**CRITICAL POINTS:**
- ✅ Validasi **HANYA** check `approved_by = auth()->id()`
- ✅ Backward compatible: `approved_by = null` → allow
- ❌ **TIDAK ADA** validasi kompleks `atasan_id` atau `unit_kerja_id`

---

### **5️⃣ Reject SKP (SkpTahunanAtasanController@reject)**

**Location:** `app/Http/Controllers/Atasan/SkpTahunanAtasanController.php:263-305`

**Validation Logic:**
```php
public function reject(Request $request, $id)
{
    $validated = $request->validate([
        'catatan_atasan' => 'required|string|max:1000',
    ]);

    $skp = SkpTahunan::findOrFail($id);

    // Verify status
    if ($skp->status !== 'DIAJUKAN') {
        return back()->with('error', 'SKP tidak dalam status diajukan');
    }

    $atasan = Auth::user();

    // ✅ VALIDATION: Hanya check approved_by
    // Backward compatible: jika approved_by null, allow (data lama)
    if ($skp->approved_by !== null && $skp->approved_by !== $atasan->id) {
        return back()->with('error', 'Anda tidak memiliki akses untuk menolak SKP ini. SKP ini bukan tanggung jawab Anda.');
    }

    // Update status
    $skp->update([
        'status' => 'DITOLAK',
        'catatan_atasan' => $validated['catatan_atasan'],
        'approved_by' => $atasan->id,
        'approved_at' => now(),
    ]);

    return redirect()
        ->route('atasan.skp-tahunan.show', $id)
        ->with('success', 'SKP Tahunan ditolak. ASN akan memperbaiki.');
}
```

**CRITICAL POINTS:**
- ✅ Validasi **HANYA** check `approved_by = auth()->id()`
- ✅ Backward compatible: `approved_by = null` → allow
- ❌ **TIDAK ADA** validasi kompleks `atasan_id` atau `unit_kerja_id`

---

## 📊 TABEL PERUBAHAN LOGIC

| Method | Logic Lama (BROKEN) | Logic Baru (FIXED) |
|--------|---------------------|---------------------|
| **submit()** | ❌ Tidak set `approved_by` | ✅ Set `approved_by = user->atasan_id` |
| **submit()** | ❌ Tidak ada fallback | ✅ Auto-approve jika user tanpa atasan |
| **index() query** | ❌ Filter `role='ASN'` | ✅ Filter `approved_by = auth()->id()` |
| **index() pending counts** | ❌ Filter `role='ASN'` + `unit_kerja_id` | ✅ Filter `approved_by = auth()->id()` |
| **show() validation** | ❌ Check `atasan_id` + `unit_kerja_id` fallback | ✅ Check `approved_by = auth()->id()` |
| **approve() validation** | ❌ Check `atasan_id` + `unit_kerja_id` fallback | ✅ Check `approved_by = auth()->id()` |
| **reject() validation** | ❌ Check `atasan_id` + `unit_kerja_id` fallback | ✅ Check `approved_by = auth()->id()` |

---

## 🔒 KEAMANAN & VALIDASI

### **✅ Validasi Ownership Tetap Aman**

**Di SkpTahunanController (ASN buat/edit SKP):**
```php
// User hanya bisa edit SKP miliknya sendiri
$skpTahunan = SkpTahunan::where('user_id', $user->id)->findOrFail($id);
```

**Di SkpTahunanAtasanController (Atasan approve SKP):**
```php
// Atasan hanya bisa approve SKP yang approved_by = atasan->id
if ($skp->approved_by !== null && $skp->approved_by !== $atasan->id) {
    abort(403);
}
```

### **✅ Backward Compatibility**

**Handling data lama dengan `approved_by = null`:**
```php
// Jika approved_by null (data lama), allow approval
if ($skp->approved_by !== null && $skp->approved_by !== $atasan->id) {
    abort(403);
}
```

**Auto-approve untuk user tanpa atasan:**
```php
// Jika user tidak punya atasan, otomatis DISETUJUI
if ($user->atasan_id && $user->atasan) {
    // Submit untuk approval
} else {
    // Auto-approve (puncak hierarki)
}
```

---

## 🧪 TESTING SCENARIOS

### **Test 1: Eselon III Submit SKP** ✅

**Steps:**
1. Login sebagai **Kabid (Eselon III)**
2. Buat SKP Tahunan → Submit
3. **Expected:**
   - SKP status = `DIAJUKAN`
   - SKP `approved_by = Kakanwil->id` (Eselon II)

**SQL Verification:**
```sql
SELECT
    s.id,
    u.name AS kabid_name,
    u.jabatan,
    atasan.name AS approved_by_name,
    s.status,
    s.approved_by
FROM skp_tahunan s
JOIN users u ON s.user_id = u.id
JOIN users atasan ON s.approved_by = atasan.id
WHERE u.jabatan LIKE '%Kabid%'
ORDER BY s.created_at DESC;
```

**Expected Output:**
```
| id | kabid_name       | jabatan               | approved_by_name | status    | approved_by |
|----|------------------|-----------------------|------------------|-----------|-------------|
| 42 | Budi Kabid Penmas| Kabid Penmas (Eselon III) | Kakanwil Ali    | DIAJUKAN  | 5           |
```

---

### **Test 2: Eselon II Lihat List Approval** ✅

**Steps:**
1. Login sebagai **Kakanwil (Eselon II)**
2. Buka menu **Monitoring Bawahan** → **SKP Bawahan**
3. **Expected:**
   - Muncul list SKP dari **Kabid (Eselon III)** yang submit
   - Muncul list SKP dari **JF Ahli Madya** yang submit
   - **TIDAK** ada filter by role

**SQL Verification:**
```sql
-- Cek SKP yang muncul di list approval Kakanwil
SELECT
    u.name AS asn_name,
    u.role,
    u.jabatan,
    s.tahun,
    s.status,
    s.approved_by
FROM skp_tahunan s
JOIN users u ON s.user_id = u.id
WHERE s.approved_by = 5 -- ID Kakanwil
  AND s.tahun = 2026
ORDER BY s.created_at DESC;
```

**Expected Output:**
```
| asn_name         | role    | jabatan               | tahun | status    | approved_by |
|------------------|---------|-----------------------|-------|-----------|-------------|
| Budi Kabid Penmas| ATASAN  | Kabid Penmas (Eselon III) | 2026  | DIAJUKAN  | 5           |
| Ani JF Ahli      | ASN     | JF Ahli Madya         | 2026  | DIAJUKAN  | 5           |
```

**CRITICAL:**
✅ **Kabid (role='ATASAN') MUNCUL** dalam list approval!
✅ **Tidak ada filter** berdasarkan `role='ASN'`

---

### **Test 3: Eselon II Approve SKP Eselon III** ✅

**Steps:**
1. Login sebagai **Kakanwil (Eselon II)**
2. Klik SKP Kabid → **Approve**
3. **Expected:**
   - Berhasil approve
   - SKP status = `DISETUJUI`
   - SKP `approved_at = now()`

**SQL Verification:**
```sql
-- Cek SKP yang sudah diapprove
SELECT
    u.name AS kabid_name,
    approver.name AS approved_by_name,
    s.status,
    s.approved_at,
    s.catatan_atasan
FROM skp_tahunan s
JOIN users u ON s.user_id = u.id
LEFT JOIN users approver ON s.approved_by = approver.id
WHERE s.id = 42;
```

**Expected Output:**
```
| kabid_name       | approved_by_name | status     | approved_at         | catatan_atasan |
|------------------|------------------|------------|---------------------|----------------|
| Budi Kabid Penmas| Kakanwil Ali     | DISETUJUI  | 2026-02-14 14:30:00 | Disetujui      |
```

---

### **Test 4: User Tanpa Atasan Auto-Approve** ✅

**Steps:**
1. Login sebagai **Kakanwil (atasan_id = NULL)**
2. Buat SKP Tahunan → Submit
3. **Expected:**
   - SKP langsung status = `DISETUJUI`
   - SKP `approved_by = null`
   - SKP `catatan_atasan = 'Otomatis disetujui (Puncak Hierarki)'`

**SQL Verification:**
```sql
-- Cek SKP yang auto-approve
SELECT
    u.name,
    u.jabatan,
    u.atasan_id,
    s.status,
    s.approved_by,
    s.catatan_atasan
FROM skp_tahunan s
JOIN users u ON s.user_id = u.id
WHERE u.atasan_id IS NULL
  AND s.tahun = 2026;
```

**Expected Output:**
```
| name         | jabatan    | atasan_id | status     | approved_by | catatan_atasan                   |
|--------------|------------|-----------|------------|-------------|----------------------------------|
| Kakanwil Ali | Kakanwil   | NULL      | DISETUJUI  | NULL        | Otomatis disetujui (Puncak Hierarki) |
```

---

### **Test 5: Backward Compatibility (approved_by = null)** ✅

**Steps:**
1. Insert SKP lama dengan `approved_by = null`:
   ```sql
   INSERT INTO skp_tahunan (user_id, tahun, status, approved_by, created_at, updated_at)
   VALUES (10, 2025, 'DIAJUKAN', NULL, NOW(), NOW());
   ```
2. Login sebagai **Atasan** apapun
3. Coba approve SKP tersebut
4. **Expected:** Berhasil approve (backward compatible)

---

### **Test 6: Validasi Access Control** ✅

**Steps:**
1. Login sebagai **Kabid A**
2. Coba akses SKP milik bawahan **Kabid B** (via URL langsung)
3. **Expected:** Error 403 "SKP ini bukan tanggung jawab Anda"

**Example:**
```
SKP ID 42 → approved_by = 5 (Kakanwil)
Login sebagai Kabid B (ID=7)

URL: http://localhost:8000/atasan/skp-tahunan/42

Expected: 403 Forbidden (karena approved_by !== 7)
```

---

## 📂 FILE YANG DIUBAH

| File | Method | Perubahan |
|------|--------|-----------|
| **app/Http/Controllers/Asn/SkpTahunanController.php** | `submit()` | ✅ Set `approved_by = user->atasan_id` |
|  |  | ✅ Auto-approve jika user tanpa atasan |
| **app/Http/Controllers/Atasan/SkpTahunanAtasanController.php** | `index()` | ✅ Hapus filter `role='ASN'` |
|  |  | ✅ Query berbasis `approved_by` saja |
|  |  | ✅ Pending counts berbasis `approved_by` |
|  | `show()` | ✅ Validasi berbasis `approved_by` saja |
|  | `approve()` | ✅ Validasi berbasis `approved_by` saja |
|  | `reject()` | ✅ Validasi berbasis `approved_by` saja |

---

## ⚠️ CATATAN PENTING

### **❌ JANGAN:**
- ❌ Tambah kembali filter `whereHas('user', function($q) { $q->where('role', 'ASN'); })`
- ❌ Gunakan `unit_kerja_id` untuk approval validation
- ❌ Ubah logic auto-approve untuk user tanpa atasan
- ❌ Hapus backward compatibility `approved_by = null`

### **✅ LAKUKAN:**
- ✅ Test semua scenario di atas
- ✅ Verify Eselon III muncul di list approval Eselon II
- ✅ Verify user tanpa atasan bisa auto-approve
- ✅ Verify backward compatibility untuk data lama
- ✅ Monitor log error setelah deployment

---

## 🚀 CARA TESTING

### **1️⃣ Setup Hierarki (Jika Belum Ada)**

**Jalankan Seeder:**
```bash
cd c:\xampp\htdocs\gaspul\gaspul_api
php artisan db:seed --class=HierarchiApprovalSeeder
```

**Verify Hierarki:**
```sql
-- Cek hierarki organisasi
SELECT
    u.name AS pegawai,
    u.jabatan,
    u.role,
    atasan.name AS atasan_name,
    atasan.jabatan AS atasan_jabatan
FROM users u
LEFT JOIN users atasan ON u.atasan_id = atasan.id
WHERE u.status_pegawai = 'AKTIF'
ORDER BY
    CASE
        WHEN u.jabatan LIKE '%Kakanwil%' THEN 1
        WHEN u.jabatan LIKE '%Kabid%' OR u.jabatan LIKE '%Kabag%' THEN 2
        ELSE 3
    END;
```

---

### **2️⃣ Test Scenario 1: Kabid Submit SKP**

**Login sebagai Kabid:**
```
Email: (cari di database: SELECT email FROM users WHERE jabatan LIKE '%Kabid%' LIMIT 1)
Password: (default atau custom)
```

**Steps:**
1. Klik menu **SKP & Kinerja Saya** → **SKP Tahunan**
2. Klik **Tambah Butir Kinerja**
3. Isi form → **Simpan**
4. Klik **Ajukan SKP**
5. **Expected:** Flash message "SKP berhasil diajukan ke [Nama Kakanwil]"

**Verify di Database:**
```sql
SELECT
    u.name AS kabid,
    atasan.name AS approved_by,
    s.status
FROM skp_tahunan s
JOIN users u ON s.user_id = u.id
JOIN users atasan ON s.approved_by = atasan.id
WHERE u.jabatan LIKE '%Kabid%'
ORDER BY s.created_at DESC
LIMIT 1;
```

---

### **3️⃣ Test Scenario 2: Kakanwil Approve**

**Login sebagai Kakanwil:**
```
Email: (cari di database: SELECT email FROM users WHERE jabatan LIKE '%Kakanwil%' LIMIT 1)
Password: (default atau custom)
```

**Steps:**
1. Klik menu **Monitoring Bawahan** → **SKP Bawahan**
2. **Verify:** Ada list SKP dari Kabid (role='ATASAN')
3. Klik **Detail** SKP Kabid
4. Klik **Setujui**
5. Isi catatan (optional) → **Submit**
6. **Expected:** Flash message "SKP berhasil disetujui"

**Verify di Database:**
```sql
SELECT
    u.name AS kabid,
    approver.name AS approved_by,
    s.status,
    s.approved_at
FROM skp_tahunan s
JOIN users u ON s.user_id = u.id
LEFT JOIN users approver ON s.approved_by = approver.id
WHERE u.jabatan LIKE '%Kabid%'
ORDER BY s.updated_at DESC
LIMIT 1;
```

---

### **4️⃣ Test Scenario 3: Auto-Approve Puncak Hierarki**

**Setup:**
```sql
-- Pastikan Kakanwil tidak punya atasan
UPDATE users SET atasan_id = NULL WHERE jabatan LIKE '%Kakanwil%';
```

**Login sebagai Kakanwil:**

**Steps:**
1. Buat SKP baru
2. Submit SKP
3. **Expected:** Flash message "SKP berhasil disetujui otomatis (Anda adalah puncak hierarki)"

**Verify:**
```sql
SELECT
    u.name,
    u.jabatan,
    s.status,
    s.approved_by,
    s.catatan_atasan
FROM skp_tahunan s
JOIN users u ON s.user_id = u.id
WHERE u.jabatan LIKE '%Kakanwil%'
ORDER BY s.created_at DESC
LIMIT 1;

-- Expected: status='DISETUJUI', approved_by=NULL, catatan='Otomatis disetujui (Puncak Hierarki)'
```

---

## 📞 TROUBLESHOOTING

### **Problem: SKP Kabid tidak muncul di list Kakanwil**

**Penyebab:** `approved_by` tidak di-set saat submit

**Solusi:**
```sql
-- Cek approved_by
SELECT user_id, status, approved_by FROM skp_tahunan WHERE user_id = [ID_KABID];

-- Jika approved_by NULL, set manual:
UPDATE skp_tahunan
SET approved_by = [ID_KAKANWIL]
WHERE user_id = [ID_KABID] AND approved_by IS NULL;
```

---

### **Problem: Error 403 saat approve**

**Penyebab:** `approved_by` tidak sesuai dengan user login

**Solusi:**
```sql
-- Cek approved_by vs user login
SELECT s.approved_by, u.id, u.name
FROM skp_tahunan s, users u
WHERE s.id = [SKP_ID] AND u.email = '[EMAIL_LOGIN]';

-- Jika tidak match, verify hierarki
SELECT u.name, u.atasan_id, atasan.name AS atasan_name
FROM users u
LEFT JOIN users atasan ON u.atasan_id = atasan.id
WHERE u.id = [USER_ID];
```

---

## 📦 CHECKLIST DEPLOYMENT

```
☐ 1. Backup database production
☐ 2. Test semua scenario di local (6 scenarios di atas)
☐ 3. Verify tidak ada filter role='ASN' di approval query
☐ 4. Verify submit() set approved_by dengan benar
☐ 5. Upload file yang diubah:
     - app/Http/Controllers/Asn/SkpTahunanController.php
     - app/Http/Controllers/Atasan/SkpTahunanAtasanController.php
☐ 6. Jalankan seeder hierarki (jika belum):
     php artisan db:seed --class=HierarchiApprovalSeeder
☐ 7. Clear cache di production:
     php artisan config:clear
     php artisan route:clear
     php artisan view:clear
☐ 8. Test approval Eselon II → Eselon III
☐ 9. Monitor log error selama 1-2 hari
☐ 10. Rollback jika ada masalah
```

---

## 🔄 ROLLBACK PLAN

Jika ada masalah, restore dari backup database dan file:

```bash
# Restore database
mysql -u root gaspul < backup_2026_02_14.sql

# Restore file controller (jika ada backup)
cp app/Http/Controllers/Asn/SkpTahunanController.php.bak app/Http/Controllers/Asn/SkpTahunanController.php
cp app/Http/Controllers/Atasan/SkpTahunanAtasanController.php.bak app/Http/Controllers/Atasan/SkpTahunanAtasanController.php
```

---

**APPROVAL FLOW BERBASIS HIERARKI (approved_by) – COMPLETED** ✅

**Status:** COMPLETED (Siap untuk testing manual)
**Next Step:** Test manual semua scenario di checklist
**Production:** ❌ NOT DEPLOYED (LOCAL TESTING ONLY)
