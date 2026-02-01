# Implementasi Fitur AJUKAN REVISI SKP TAHUNAN

## 📋 OVERVIEW

Implementasi lengkap fitur "Ajukan Revisi SKP Tahunan" dengan state machine workflow yang aman.

**Status:** ✅ READY FOR TESTING (Localhost)
**Tanggal:** 2026-02-01
**Developer:** Senior Laravel Engineer

---

## 🎯 FITUR YANG DIIMPLEMENTASIKAN

### Business Flow:
1. **ASN** dapat mengajukan permintaan revisi jika SKP statusnya **DISETUJUI**
2. **ATASAN** (PIMPINAN) dapat menyetujui atau menolak permintaan revisi
3. Jika **DISETUJUI**: SKP kembali ke status **DRAFT** (ASN dapat edit)
4. Jika **DITOLAK**: SKP tetap **DISETUJUI**, status menjadi **REVISI_DITOLAK**
5. **RHK dan Kinerja Harian TIDAK terpengaruh** (data tetap aman)

---

## 📁 FILE YANG DIMODIFIKASI/DIBUAT

### 1. Database Migration
**File:** `database/migrations/2026_02_01_000000_add_revision_statuses_to_skp_tahunan.php`

**Changes:**
- ✅ Menambah ENUM status: `REVISI_DIAJUKAN`, `REVISI_DITOLAK`
- ✅ Menambah kolom: `alasan_revisi` (TEXT, nullable)
- ✅ Menambah kolom: `revisi_diajukan_at` (TIMESTAMP, nullable)
- ✅ Menambah kolom: `revisi_disetujui_at` (TIMESTAMP, nullable)
- ✅ Menambah kolom: `catatan_revisi` (TEXT, nullable)

**Cara Run:**
```bash
cd gaspul_api
php artisan migrate
```

---

### 2. Model SkpTahunan
**File:** `app/Models/SkpTahunan.php`

**Changes:**
- ✅ Menambah fillable fields untuk revisi
- ✅ Menambah datetime casts untuk timestamps revisi
- ✅ Menambah helper methods:
  - `canRequestRevision()`: Cek apakah bisa ajukan revisi (status = DISETUJUI)
  - `isPendingRevision()`: Cek apakah sedang menunggu approval revisi
  - `isRevisionRejected()`: Cek apakah revisi ditolak
- ✅ Update `canEditDetails()`: Include status REVISI_DITOLAK

---

### 3. Policy Authorization
**File:** `app/Policies/SkpTahunanPolicy.php`

**New Policy Methods:**
- ✅ `requestRevision()`: Cek apakah ASN bisa ajukan revisi
- ✅ `approveRevision()`: Cek apakah PIMPINAN bisa approve revisi
- ✅ `rejectRevision()`: Cek apakah PIMPINAN bisa reject revisi

**Registered in:** `app/Providers/AppServiceProvider.php`

---

### 4. Routes
**File:** `routes/web.php`

**ASN Routes:**
```php
Route::post('/skp-tahunan/{skpTahunan}/ajukan-revisi', [SkpTahunanController::class, 'ajukanRevisi'])
    ->name('skp-tahunan.ajukan-revisi');
```

**ATASAN Routes:**
```php
Route::post('/skp-tahunan/{skpTahunan}/setujui-revisi', [SkpTahunanAtasanController::class, 'setujuiRevisi'])
    ->name('skp-tahunan.setujui-revisi');

Route::post('/skp-tahunan/{skpTahunan}/tolak-revisi', [SkpTahunanAtasanController::class, 'tolakRevisi'])
    ->name('skp-tahunan.tolak-revisi');
```

---

### 5. Controllers

#### ASN Controller
**File:** `app/Http/Controllers/Asn/SkpTahunanController.php`

**New Method:**
- ✅ `ajukanRevisi()`: Handle permintaan revisi dari ASN
  - Validasi: alasan_revisi (required, min:10, max:1000)
  - Authorization: menggunakan Policy
  - Update: status → REVISI_DIAJUKAN

#### ATASAN Controller
**File:** `app/Http/Controllers/Atasan/SkpTahunanAtasanController.php`

**New Methods:**
- ✅ `setujuiRevisi()`: Approve permintaan revisi
  - Validasi: catatan_revisi (optional, max:1000)
  - Authorization: menggunakan Policy
  - Update: status → DRAFT (ASN bisa edit)

- ✅ `tolakRevisi()`: Reject permintaan revisi
  - Validasi: catatan_revisi (required, min:10, max:1000)
  - Authorization: menggunakan Policy
  - Update: status → REVISI_DITOLAK

---

### 6. Blade Views

#### ASN View
**File:** `resources/views/asn/skp-tahunan/index.blade.php`

**Changes:**
- ✅ Menambah status badge untuk `REVISI_DIAJUKAN` dan `REVISI_DITOLAK`
- ✅ Menambah alert box untuk status revisi (yellow & orange)
- ✅ Menambah form ajukan revisi (collapsible dengan Alpine.js)
- ✅ Menggunakan `@can('requestRevision', $skpTahunan)` untuk authorization

#### ATASAN View
**File:** `resources/views/atasan/skp-tahunan/show.blade.php`

**Changes:**
- ✅ Menambah status badge untuk `REVISI_DIAJUKAN` dan `REVISI_DITOLAK`
- ✅ Menambah section approval revisi (sebelum approval normal)
- ✅ Menampilkan alasan revisi dari ASN
- ✅ Form setujui/tolak revisi dengan konfirmasi
- ✅ Menggunakan `@can('approveRevision', $skp)` untuk authorization

---

## 🧪 TESTING GUIDE

### Prerequisite:
1. Pastikan database sudah di-import dari production
2. Jalankan migration: `php artisan migrate`
3. Login sebagai ASN yang memiliki SKP DISETUJUI
4. Login sebagai ATASAN (PIMPINAN) untuk approve/reject

### Test Case 1: ASN Ajukan Revisi
1. Login sebagai **ASN**
2. Buka menu **SKP Tahunan**
3. Pastikan SKP status = **DISETUJUI**
4. Akan muncul section **"Perlu Revisi SKP?"** dengan button **"Ajukan Revisi"**
5. Klik **Ajukan Revisi**
6. Isi **Alasan Revisi** (minimal 10 karakter)
7. Klik **Kirim Permintaan Revisi**
8. ✅ **Expected:** Status berubah menjadi **REVISI_DIAJUKAN** (badge kuning)

### Test Case 2: ATASAN Setujui Revisi
1. Login sebagai **ATASAN** (PIMPINAN)
2. Buka menu **SKP Tahunan** → Pilih SKP dengan status **REVISI_DIAJUKAN**
3. Akan muncul section **"Permintaan Revisi SKP Tahunan"** (warna orange)
4. Klik button **Setujui Revisi**
5. Isi catatan (opsional)
6. Klik **Konfirmasi Setujui Revisi**
7. ✅ **Expected:** Status berubah menjadi **DRAFT** (ASN bisa edit)

### Test Case 3: ATASAN Tolak Revisi
1. Login sebagai **ATASAN** (PIMPINAN)
2. Buka menu **SKP Tahunan** → Pilih SKP dengan status **REVISI_DIAJUKAN**
3. Klik button **Tolak Revisi**
4. Isi **Alasan Penolakan** (minimal 10 karakter, WAJIB)
5. Klik **Konfirmasi Tolak Revisi**
6. ✅ **Expected:** Status berubah menjadi **REVISI_DITOLAK** (badge ungu/orange)
7. ✅ **Expected:** ASN TIDAK bisa edit SKP

### Test Case 4: Data Integrity Check
1. Sebelum ajukan revisi, cek jumlah **RHK** dan **Kinerja Harian**
2. Lakukan proses revisi (approval/reject)
3. ✅ **Expected:** Jumlah RHK dan Kinerja Harian **TETAP SAMA** (tidak terpengaruh)

---

## 🔒 SECURITY CHECKLIST

- ✅ **Authorization:** Menggunakan Laravel Policy (`@can`, `$this->authorize()`)
- ✅ **Route Model Binding:** Semua route menggunakan `{skpTahunan}` parameter
- ✅ **Validation:** Semua input divalidasi (min, max, required)
- ✅ **CSRF Protection:** Semua form menggunakan `@csrf`
- ✅ **Ownership Check:** Policy memastikan user hanya bisa akses SKP milik sendiri
- ✅ **Role Check:** Hanya PIMPINAN yang bisa approve/reject revisi
- ✅ **State Machine:** Status transition terkontrol dengan baik

---

## 📊 STATE MACHINE DIAGRAM

```
┌──────────┐
│  DRAFT   │◄───────────────────────────────┐
└────┬─────┘                                 │
     │ submit()                              │
     ▼                                       │
┌──────────┐                                 │
│ DIAJUKAN │                                 │
└────┬─────┘                                 │
     │                                       │
     ├──────► approve() ──► DISETUJUI ───────┤
     │                           │           │ setujuiRevisi()
     └──────► reject() ──► DITOLAK           │
                                │            │
                                │ ajukanRevisi()
                                ▼            │
                         REVISI_DIAJUKAN ────┤
                                │            │
                                │            │
                    tolakRevisi()│            │
                                ▼            │
                         REVISI_DITOLAK      │
                                             │
                                             │
                         (ASN tidak bisa edit)
```

---

## 🚀 DEPLOYMENT CHECKLIST

### Sebelum Deploy ke Production:
- [ ] Testing lengkap di localhost (semua test case)
- [ ] Verifikasi data integrity (RHK, Kinerja Harian tidak terpengaruh)
- [ ] Testing authorization (ASN vs ATASAN)
- [ ] Testing validation (alasan revisi minimal 10 karakter)
- [ ] Review UI/UX (status badge, alert boxes)
- [ ] Backup database production
- [ ] Commit semua perubahan ke git

### Deployment Steps:
1. Push ke repository
2. Pull di production server
3. Run migration: `php artisan migrate --force`
4. Clear cache: `php artisan config:clear && php artisan cache:clear`
5. Test login sebagai ASN dan ATASAN
6. Verifikasi fitur berfungsi dengan baik

---

## 📝 NOTES

- **Alpine.js:** Digunakan untuk toggle form (collapsible)
- **Tailwind CSS:** Semua styling menggunakan utility classes
- **Confirmation:** Menggunakan native JavaScript `confirm()` untuk konfirmasi
- **Flash Messages:** Menggunakan Laravel session flash (success, warning, error)

---

## 🐛 TROUBLESHOOTING

### Issue: Button "Ajukan Revisi" tidak muncul
**Solution:** Pastikan:
- SKP status = `DISETUJUI`
- Policy `SkpTahunanPolicy` sudah registered
- User adalah pemilik SKP

### Issue: Error 403 saat ajukan revisi
**Solution:** Pastikan:
- Base Controller menggunakan trait `AuthorizesRequests`
- Policy method `requestRevision()` mengembalikan `true`

### Issue: Migration error "column already exists"
**Solution:**
```bash
php artisan migrate:rollback --step=1
php artisan migrate
```

---

## 📞 CONTACT

Jika ada pertanyaan atau issue:
1. Cek file log: `storage/logs/laravel.log`
2. Gunakan `dd()` atau `Log::info()` untuk debugging
3. Pastikan semua dependency sudah terinstall

---

**READY FOR TESTING! 🎉**

Silakan test di localhost terlebih dahulu sebelum deploy ke production.
