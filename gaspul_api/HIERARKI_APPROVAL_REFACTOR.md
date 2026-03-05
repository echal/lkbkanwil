# HIERARKI APPROVAL REFACTOR - DOKUMENTASI LENGKAP

## 📋 OVERVIEW

Refactor approval logic dari **berbasis role & unit_kerja** menjadi **berbasis hierarki relasi (atasan_id)**.

**Tanggal:** 14 Februari 2026
**Status:** ✅ COMPLETED (LOCAL TESTING ONLY)
**Author:** Claude Sonnet 4.5

---

## 🎯 TUJUAN REFACTOR

### **Masalah Lama:**
- Approval berbasis `role` (ADMIN, ATASAN, ASN)
- Approval berbasis `unit_kerja_id`
- Hardcode logic conditional
- **Kabid/Kabag (Eselon III) tidak bisa membuat LKB** (dianggap atasan)
- **JF Ahli Madya tidak bisa membuat LKB** (tidak ada atasan yang jelas)

### **Solusi Baru:**
- ✅ Approval berbasis **relasi hierarki** (`atasan_id`)
- ✅ Kabid/Kabag **bisa membuat LKB**, approve oleh Kakanwil
- ✅ JF Ahli Madya **bisa membuat LKB**, approve oleh Kakanwil
- ✅ Backward compatible (data lama tetap jalan)
- ✅ Tidak merusak approval existing

---

## 📂 FILE YANG DIUBAH/DITAMBAHKAN

### **1️⃣ MIGRATION**
```
database/migrations/2026_02_14_202012_add_atasan_id_to_users_table.php
```
- ✅ Tambah kolom `atasan_id` (nullable, foreign key ke users)
- ✅ Safe rollback
- ✅ Tidak merusak data existing

### **2️⃣ MODEL**
```
app/Models/User.php
```
**Perubahan:**
- ✅ Tambah `atasan_id` di `$fillable`
- ✅ Relasi `atasan()` - belongsTo User
- ✅ Relasi `bawahan()` - hasMany User
- ✅ Helper method `isAtasanDari(User $user)`
- ✅ Helper method `isBawahanDari(User $user)`
- ✅ Helper method `getBawahanPendingApproval()`
- ✅ Helper method `hasAtasan()` & `hasBawahan()`

### **3️⃣ CONTROLLER**
```
app/Http/Controllers/Atasan/SkpTahunanAtasanController.php
```
**Perubahan:**
- ✅ `index()`: Filter berbasis hierarki atasan_id (+ fallback unit_kerja)
- ✅ `show()`: Validasi berbasis atasan_id (+ fallback unit_kerja)
- ✅ `approve()`: Validasi berbasis atasan_id (+ fallback unit_kerja)
- ✅ `reject()`: Validasi berbasis atasan_id (+ fallback unit_kerja)

### **4️⃣ SEEDER (OPSIONAL)**
```
database/seeders/HierarchiApprovalSeeder.php
```
- ✅ Auto-map hierarki berdasarkan jabatan
- ✅ Kakanwil → atasan_id = null (puncak)
- ✅ Kabid/Kabag → atasan_id = Kakanwil
- ✅ JF Ahli Madya → atasan_id = Kakanwil
- ✅ ASN → atasan_id = Kabid/Kabag (atau Kakanwil fallback)
- ✅ Tidak overwrite data existing (hanya isi yang NULL)

### **5️⃣ DOKUMENTASI**
```
HIERARKI_APPROVAL_REFACTOR.md (file ini)
```

---

## 🔧 HIERARKI ORGANISASI

```
Kakanwil (Eselon II)
    atasan_id: null
    role: ATASAN
    |
    ├── Kabid/Kabag (Eselon III)
    |       atasan_id: Kakanwil
    |       role: ATASAN → ASN (setelah refactor, bisa membuat LKB)
    |       |
    |       └── ASN Staff
    |               atasan_id: Kabid/Kabag
    |               role: ASN
    |
    └── JF Ahli Madya
            atasan_id: Kakanwil
            role: ASN
```

---

## 🚀 CARA SETUP (LANGKAH-LANGKAH)

### **1️⃣ Jalankan Migration**

```bash
cd c:\xampp\htdocs\gaspul\gaspul_api
php artisan migrate
```

**Output yang diharapkan:**
```
INFO  Running migrations.

2026_02_14_202012_add_atasan_id_to_users_table ........ DONE
```

**Verify kolom sudah ada:**
```bash
php artisan tinker
Schema::getColumnListing('users');
# Harus ada 'atasan_id'
```

---

### **2️⃣ Jalankan Seeder (OPSIONAL)**

**⚠️ PENTING:** Seeder ini **OPSIONAL**. Jika Anda ingin setup hierarki manual via admin panel, **SKIP langkah ini**.

```bash
php artisan db:seed --class=HierarchiApprovalSeeder
```

**Output yang diharapkan:**
```
🔄 Starting Hierarchi Approval Seeder...
✓ Kakanwil: SUHARLI, S.Ag., M.Pd (ID: 5)
✓ Updated 3 Kabid/Kabag → atasan = Kakanwil
✓ Updated 2 JF Ahli Madya → atasan = Kakanwil
✓ Updated 15 ASN → atasan = Kabid/Kabag (atau Kakanwil)

=== HIERARKI APPROVAL SUMMARY ===
+--------------------------------------------+--------+
| Kategori                                   | Jumlah |
+--------------------------------------------+--------+
| User dengan atasan                         | 20     |
| User tanpa atasan (puncak hierarki)        | 1      |
| Kabid/Kabag updated                        | 3      |
| JF Ahli Madya updated                      | 2      |
| ASN updated                                | 15     |
+--------------------------------------------+--------+

✅ Hierarki Approval Seeder completed!
⚠️  Catatan: Data existing TIDAK di-overwrite (hanya yang atasan_id NULL)
```

---

### **3️⃣ Setup Manual (Alternatif Seeder)**

Jika tidak pakai seeder, setup manual via tinker atau admin panel:

```bash
php artisan tinker
```

```php
// Set Kakanwil sebagai puncak hierarki
$kakanwil = User::where('jabatan', 'LIKE', '%Kakanwil%')->first();
$kakanwil->update(['atasan_id' => null]);

// Set Kabid/Kabag atasan = Kakanwil
$kabid = User::find(5); // ID Kabid
$kabid->update(['atasan_id' => $kakanwil->id]);

// Set ASN atasan = Kabid
$asn = User::find(3); // ID ASN
$asn->update(['atasan_id' => $kabid->id]);
```

---

## 🧪 TESTING MANUAL

### **Checklist Testing:**

#### **✅ Test 1: Approval Hierarki Baru**

**Scenario:** ASN submit SKP → Kabid approve

**Steps:**
1. Login sebagai **ASN** (atasan_id = Kabid)
2. Buat & submit SKP Tahunan
3. Logout
4. Login sebagai **Kabid** (atasan dari ASN tsb)
5. Buka **Atasan → SKP Tahunan**
6. **Expected:** SKP ASN muncul di list
7. Klik **Approve**
8. **Expected:** Berhasil approve

**Validation:**
```sql
SELECT user_id, status, approved_by
FROM skp_tahunan
WHERE user_id = <ASN_ID>;
```

---

#### **✅ Test 2: Kabid Buat LKB, Approve oleh Kakanwil**

**Scenario:** Kabid (Eselon III) buat LKB → Kakanwil approve

**Steps:**
1. Login sebagai **Kabid** (atasan_id = Kakanwil, role = ASN atau ATASAN)
2. Buat & submit SKP Tahunan
3. Logout
4. Login sebagai **Kakanwil**
5. Buka **Atasan → SKP Tahunan**
6. **Expected:** SKP Kabid muncul di list
7. Klik **Approve**
8. **Expected:** Berhasil approve

**Validation:**
```sql
SELECT user_id, status, approved_by
FROM skp_tahunan
WHERE user_id = <KABID_ID>;
```

---

#### **✅ Test 3: JF Ahli Madya Buat LKB, Approve oleh Kakanwil**

**Scenario:** JF Ahli Madya buat LKB → Kakanwil approve

**Steps:**
1. Login sebagai **JF Ahli Madya** (atasan_id = Kakanwil)
2. Buat & submit SKP Tahunan
3. Logout
4. Login sebagai **Kakanwil**
5. Buka **Atasan → SKP Tahunan**
6. **Expected:** SKP JF muncul di list
7. Klik **Approve**
8. **Expected:** Berhasil approve

---

#### **✅ Test 4: Backward Compatible - Data Lama Tetap Jalan**

**Scenario:** SKP lama (sebelum refactor) masih bisa di-approve

**Steps:**
1. Ambil SKP lama yang user-nya **BELUM** punya atasan_id
2. Login sebagai Atasan di unit kerja yang sama
3. Buka **Atasan → SKP Tahunan**
4. **Expected:** SKP tetap muncul (fallback ke logic unit_kerja)
5. Klik **Approve**
6. **Expected:** Berhasil approve

**Validation:**
```sql
SELECT u.atasan_id, s.status
FROM skp_tahunan s
JOIN users u ON s.user_id = u.id
WHERE u.atasan_id IS NULL;
```

---

#### **✅ Test 5: Validasi Akses - Bukan Atasan Tidak Bisa Approve**

**Scenario:** User yang bukan atasan langsung tidak bisa approve

**Steps:**
1. Login sebagai **ASN A** (atasan_id = Kabid A)
2. Buat SKP
3. Logout
4. Login sebagai **Kabid B** (bukan atasan dari ASN A)
5. Coba akses SKP ASN A langsung via URL
6. **Expected:** Error 403 "Anda bukan atasan langsung pegawai ini"

---

## 📊 QUERY BERGUNA

### **Cek Hierarki User:**
```sql
SELECT
    u.id,
    u.name,
    u.jabatan,
    u.role,
    u.atasan_id,
    atasan.name AS nama_atasan
FROM users u
LEFT JOIN users atasan ON u.atasan_id = atasan.id
ORDER BY u.atasan_id NULLS FIRST, u.id;
```

### **Cek Bawahan dari User Tertentu:**
```sql
SELECT
    id,
    name,
    jabatan,
    role
FROM users
WHERE atasan_id = <USER_ID>;
```

### **Cek SKP Pending Approval untuk Atasan:**
```sql
SELECT
    s.id,
    u.name AS nama_asn,
    u.jabatan,
    s.tahun,
    s.status
FROM skp_tahunan s
JOIN users u ON s.user_id = u.id
WHERE u.atasan_id = <ATASAN_ID>
  AND s.status = 'DIAJUKAN';
```

---

## ⚠️ CATATAN PENTING

### **❌ JANGAN:**
- ❌ Commit & push ke production tanpa testing lengkap
- ❌ Jalankan seeder di production tanpa backup database
- ❌ Ubah atasan_id user yang sudah punya SKP approved
- ❌ Hapus kolom unit_kerja_id (masih dipakai untuk fallback)
- ❌ Hapus logic lama sebelum memastikan logic baru 100% aman

### **✅ LAKUKAN:**
- ✅ Backup database sebelum jalankan seeder
- ✅ Test di local dulu sampai yakin 100%
- ✅ Verifikasi setiap hierarki user sebelum deploy production
- ✅ Monitor log error setelah deploy
- ✅ Siapkan rollback plan (migration down)

---

## 🔄 ROLLBACK PLAN

Jika ada masalah dan ingin rollback:

```bash
# Rollback migration (hapus kolom atasan_id)
php artisan migrate:rollback --step=1

# Atau manual via SQL
ALTER TABLE users DROP FOREIGN KEY users_atasan_id_foreign;
ALTER TABLE users DROP COLUMN atasan_id;
```

**File controller yang diubah:**
- Restore dari git: `git checkout app/Http/Controllers/Atasan/SkpTahunanAtasanController.php`

---

## 📝 CHECKLIST DEPLOYMENT KE PRODUCTION

```
☐ 1. Backup database production (full dump)
☐ 2. Test semua scenario di local (checklist testing di atas)
☐ 3. Verifikasi hierarki user di database production
☐ 4. Upload file yang diubah:
     - Migration file
     - app/Models/User.php
     - app/Http/Controllers/Atasan/SkpTahunanAtasanController.php
     - database/seeders/HierarchiApprovalSeeder.php (opsional)
☐ 5. Jalankan migration di production:
     php artisan migrate
☐ 6. (Opsional) Jalankan seeder di production:
     php artisan db:seed --class=HierarchiApprovalSeeder
☐ 7. Test approval flow di production (1-2 SKP test)
☐ 8. Monitor log error selama 1-2 hari
☐ 9. Jika ada masalah, rollback segera
```

---

## 📞 TROUBLESHOOTING

### **Problem: SKP tidak muncul di list atasan**

**Solusi:**
```sql
-- Cek atasan_id user
SELECT id, name, atasan_id FROM users WHERE id = <USER_ID>;

-- Jika atasan_id NULL, set manual
UPDATE users SET atasan_id = <ATASAN_ID> WHERE id = <USER_ID>;
```

---

### **Problem: Error 403 saat approve**

**Penyebab:** User bukan atasan langsung

**Solusi:**
```sql
-- Cek hierarki
SELECT
    u.name AS asn_name,
    u.atasan_id,
    atasan.name AS atasan_name
FROM users u
LEFT JOIN users atasan ON u.atasan_id = atasan.id
WHERE u.id = <USER_ID>;

-- Set atasan yang benar
UPDATE users SET atasan_id = <CORRECT_ATASAN_ID> WHERE id = <USER_ID>;
```

---

### **Problem: Seeder tidak berjalan**

**Solusi:**
```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# Jalankan ulang
php artisan db:seed --class=HierarchiApprovalSeeder
```

---

## 📚 REFERENSI

- [Laravel Self-Referencing Relationships](https://laravel.com/docs/11.x/eloquent-relationships#one-to-many-polymorphic-relations)
- [Database Migrations](https://laravel.com/docs/11.x/migrations)
- [Eloquent Relationships](https://laravel.com/docs/11.x/eloquent-relationships)

---

**SAFE HIERARCHY APPROVAL REFACTOR – LOCAL ONLY** ✅

**Tanggal Selesai:** 14 Februari 2026
**Testing Status:** ⚠️ PENDING (Belum ditest manual)
**Production Status:** ❌ NOT DEPLOYED
