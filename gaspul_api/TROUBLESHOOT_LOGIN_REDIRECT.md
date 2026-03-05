# TROUBLESHOOT - LOGIN REDIRECT KE SPBE

## MASALAH
Setelah login, aplikasi redirect ke SPBE bukan GASPUL

## PENYEBAB
1. Browser cache/cookies masih menyimpan session SPBE
2. URL bookmark salah
3. Session database tidak ter-clear

---

## SOLUSI STEP-BY-STEP

### 1. CLEAR BROWSER CACHE (WAJIB!)

**Chrome/Edge:**
```
1. Tekan: Ctrl + Shift + Delete
2. Centang: Cookies and other site data
3. Centang: Cached images and files
4. Time range: All time
5. Klik: Clear data
6. Restart browser
```

**Atau gunakan Incognito Mode:**
```
Ctrl + Shift + N (Chrome)
Ctrl + Shift + P (Edge)
```

---

### 2. CLEAR SESSION DATABASE

```bash
# Masuk ke phpMyAdmin
http://localhost/phpmyadmin

# Pilih database: gaspul_api
# Klik tab "SQL"
# Jalankan query:

TRUNCATE TABLE sessions;
```

Atau via command line:
```bash
cd c:\xampp\htdocs\gaspul\gaspul_api
php artisan tinker

# Di tinker, ketik:
DB::table('sessions')->truncate();
exit
```

---

### 3. CLEAR LARAVEL CACHE

```bash
cd c:\xampp\htdocs\gaspul\gaspul_api

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

### 4. RESTART PHP SERVER

```bash
# Stop semua PHP process
taskkill /F /IM php.exe

# Jalankan ulang
cd c:\xampp\htdocs\gaspul\gaspul_api
php artisan serve
```

---

### 5. URL YANG BENAR

**LOGIN:**
```
http://localhost:8000/login
```

**DASHBOARD (setelah login):**
```
ADMIN → http://localhost:8000/admin/users
ATASAN → http://localhost:8000/dashboard
ASN → http://localhost:8000/dashboard
```

**MONITORING KH & TLA (ADMIN only):**
```
http://localhost:8000/admin/pegawai
```

---

### 6. CEK .ENV FILE

Pastikan APP_URL di file `.env` sudah benar:

```env
APP_URL=http://localhost:8000
```

**JANGAN:**
```env
APP_URL=http://localhost/spbe  ❌
APP_URL=http://localhost/gaspul ❌
```

Setelah ubah .env, jalankan:
```bash
php artisan config:clear
```

---

### 7. CEK REDIRECT DI LoginController

File: `app/Http/Controllers/Auth/LoginController.php`

**Baris 72-83 harus seperti ini:**
```php
private function redirectBasedOnRole($role)
{
    switch (strtoupper($role)) {
        case 'ADMIN':
            return redirect()->route('admin.users.index'); // ✅ Benar
        case 'ATASAN':
            return redirect()->route('dashboard'); // ✅ Benar
        case 'ASN':
        default:
            return redirect()->route('dashboard'); // ✅ Benar
    }
}
```

**JANGAN ada redirect ke URL SPBE!**

---

### 8. TEST LOGIN DI INCOGNITO

**Cara tercepat untuk memastikan:**

1. Buka Incognito: `Ctrl + Shift + N`
2. Akses: `http://localhost:8000/login`
3. Login dengan kredensial Anda
4. Cek URL setelah login

**Jika tetap redirect ke SPBE di Incognito:**
→ Berarti ada masalah di kode Laravel (kemungkinan kecil)

**Jika berhasil di Incognito tapi gagal di normal browser:**
→ Clear browser cache/cookies lebih aggressive atau reinstall browser

---

## KREDENSIAL DEFAULT (untuk testing)

Jika Anda lupa password, gunakan kredensial seeder:

**ADMIN:**
```
Email: admin@gaspul.com
Password: password
```

**ATASAN:**
```
Email: atasan@gaspul.com
Password: password
```

**ASN:**
```
Email: asn@gaspul.com
Password: password
```

---

## CEK APAKAH SUDAH BENAR

Setelah login berhasil, cek:

1. **URL di browser:**
   - ✅ `http://localhost:8000/dashboard` (ATASAN/ASN)
   - ✅ `http://localhost:8000/admin/users` (ADMIN)
   - ❌ **BUKAN** `http://localhost/spbe/...`

2. **Logo di header:**
   - ✅ Logo Kemenag
   - ✅ Judul "LAPORAN HARIAN"
   - ❌ **BUKAN** Logo/Judul SPBE

3. **Menu sidebar:**
   - ✅ Ada menu "Kinerja Harian", "SKP Tahunan", dll
   - ❌ **BUKAN** menu SPBE

---

## KONTAK DEVELOPER

Jika masih bermasalah setelah semua langkah di atas, screenshot:
1. URL di browser setelah login
2. Halaman yang muncul
3. Error message (jika ada)

Dan kirim ke tim developer.

---

**TERAKHIR DIUPDATE:** 12 Februari 2026
**APLIKASI:** GASPUL LKH Kanwil Kemenag Sulbar
**LARAVEL VERSION:** 11.x
