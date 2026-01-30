# Migrasi UI dari Next.js ke Laravel Blade + Alpine.js

## 📋 Overview

Migrasi ini fokus **HANYA pada layer UI/tampilan**, tanpa mengubah:
- ❌ API endpoints
- ❌ Controller bisnis logic
- ❌ Database structure
- ✅ Hanya tampilan frontend

---

## 🗂️ Struktur File yang Dibuat

### 1. Views (Blade Templates)

```
resources/views/
├── layouts/
│   └── app.blade.php              # Layout utama dengan sidebar & navbar
├── auth/
│   └── login.blade.php            # Halaman login
├── components/
│   ├── sidebar.blade.php          # Sidebar kiri
│   └── navbar.blade.php           # Navbar atas
├── asn/
│   └── dashboard.blade.php        # Dashboard ASN
└── atasan/
    └── dashboard.blade.php        # Dashboard Atasan
```

### 2. Controllers

```
app/Http/Controllers/
├── Auth/
│   └── LoginController.php        # Handle login/logout
├── DashboardController.php        # Route dashboard berdasarkan role
├── Asn/
│   ├── HarianController.php       # Kinerja harian
│   ├── RencanaKerjaController.php # Rencana kerja
│   ├── SkpTahunanController.php   # SKP Tahunan
│   └── BulananController.php      # Laporan bulanan
├── Atasan/
│   ├── ApprovalController.php     # Persetujuan
│   ├── KinerjaBawahanController.php # Monitoring kinerja
│   └── SkpTahunanAtasanController.php
└── Admin/
    ├── UserController.php         # Manajemen user
    ├── UnitController.php         # Manajemen unit
    └── SasaranKegiatanController.php
```

### 3. Middleware

```
app/Http/Middleware/
└── CheckRole.php                  # Middleware untuk role-based access
```

### 4. Routes

```
routes/
└── web.php                        # Web routes dengan role middleware
```

---

## 🚀 Cara Menjalankan

### Local Development (XAMPP)

1. **Pastikan .env sudah dikonfigurasi:**
```env
APP_NAME="GASPUL LKH"
APP_URL=http://localhost:8000
API_BASE_URL=http://localhost:8000/api

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gaspul_api
DB_USERNAME=root
DB_PASSWORD=
```

2. **Jalankan Laravel:**
```bash
cd gaspul_api
php artisan serve
```

3. **Akses aplikasi:**
```
http://localhost:8000/login
```

4. **Test login dengan user yang ada:**
```
Email: admin@lkbkanwil.com
Password: password123
```

---

## 🌐 Deployment ke Production (cPanel)

### 1. Upload File ke Server

Upload folder `gaspul_api` ke:
```
~/lkbkanwil.gaspul.com/gaspul_api/
```

### 2. Update .env Production

File: `~/lkbkanwil.gaspul.com/gaspul_api/.env`
```env
APP_NAME="GASPUL LKH"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lkbkanwil.gaspul.com
API_BASE_URL=https://lkbkanwil.gaspul.com/gaspul_api/public/api

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=gaspulco_lkbkanwil
DB_USERNAME=gaspulco_root
DB_PASSWORD=23123Password@
```

### 3. Setup .htaccess di Root

File: `~/lkbkanwil.gaspul.com/.htaccess`

**PENTING:** Ubah proxy dari Next.js (port 3001) ke Laravel:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Exclude static files and API
    RewriteCond %{REQUEST_URI} !^/gaspul_api/public/
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d

    # Proxy semua request ke Laravel public folder
    RewriteRule ^(.*)$ /gaspul_api/public/$1 [L]
</IfModule>
```

**Atau redirect langsung ke folder public:**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ gaspul_api/public/ [L]
    RewriteRule ^((?!gaspul_api/public/).*)$ gaspul_api/public/$1 [L,NC]
</IfModule>
```

### 4. Symlink Storage (di cPanel via SSH)

```bash
cd ~/lkbkanwil.gaspul.com/gaspul_api
php artisan storage:link
```

### 5. Clear Cache

```bash
cd ~/lkbkanwil.gaspul.com/gaspul_api
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### 6. Set Permissions

```bash
chmod -R 755 ~/lkbkanwil.gaspul.com/gaspul_api/storage
chmod -R 755 ~/lkbkanwil.gaspul.com/gaspul_api/bootstrap/cache
```

### 7. Stop PM2 (Next.js sudah tidak dipakai)

```bash
pm2 stop lkbkanwil-nextjs
pm2 delete lkbkanwil-nextjs
pm2 save
```

---

## 🔐 Authentication Flow

### Login Process

1. User mengisi form login di `/login`
2. `LoginController::login()` mengirim request ke API `/api/login`
3. Jika berhasil:
   - Simpan `api_token` di session
   - Simpan data `user` di session
   - Login user menggunakan `Auth::loginUsingId()`
   - Redirect ke dashboard sesuai role

4. Dashboard Controller akan:
   - Cek role user
   - Fetch data dari API menggunakan token
   - Render view sesuai role (ASN/Atasan/Admin)

### Logout Process

1. User klik logout
2. `LoginController::logout()` memanggil API `/api/logout`
3. Hapus session token & user data
4. Logout dari Laravel Auth
5. Redirect ke login page

---

## 🎨 UI Components

### Layout Utama (`layouts/app.blade.php`)

**Stack yang digunakan:**
- Tailwind CSS (via CDN)
- Alpine.js (untuk interaktivity minimal)
- Google Fonts Inter

**Fitur:**
- Sidebar responsif (toggle dengan Alpine.js)
- Navbar dengan user dropdown
- Content area yang scrollable

### Login Page (`auth/login.blade.php`)

**Sesuai standar instansi:**
- Logo Kemenag
- Judul: "LAPORAN HARIAN - Kanwil Kementerian Agama Provinsi Sulawesi Barat"
- Form email & password
- Footer: "© 2026 Sistem Informasi dan Data"
- Error handling

### Sidebar (`components/sidebar.blade.php`)

**Menu berdasarkan role:**

**ASN:**
- Dashboard
- SKP Tahunan
- Kinerja Harian
- Rencana Kerja
- Laporan Bulanan

**Atasan:**
- Dashboard
- SKP Tahunan
- Persetujuan
- Kinerja Bawahan

**Admin:**
- Dashboard
- Data Pegawai
- Unit Kerja
- Sasaran Kegiatan

### Dashboard ASN

**Statistik Cards:**
- Total SKP Tahunan
- Kinerja Bulan Ini
- Rencana Kerja Aktif
- Progres Keseluruhan

**Quick Actions:**
- Tambah Kinerja Harian
- Lihat SKP Tahunan
- Buat Rencana Kerja

**Aktivitas Terbaru:**
- List aktivitas dengan tanggal

### Dashboard Atasan

**Statistik Cards:**
- Total Bawahan
- Perlu Persetujuan
- Sudah Disetujui
- Rata-rata Kinerja

**Quick Actions:**
- Validasi Persetujuan
- Kinerja Bawahan
- SKP Tahunan

**Persetujuan Menunggu:**
- List persetujuan dengan tombol Review

**Kinerja Tim:**
- Progress bar per pegawai

---

## 🔧 Konfigurasi Penting

### Config: `config/app.php`

Ditambahkan konfigurasi `api_url`:
```php
'api_url' => env('API_BASE_URL', env('APP_URL', 'http://localhost') . '/api'),
```

### Middleware: `CheckRole`

```php
Route::middleware('role:ASN')->group(function () {
    // ASN routes
});
```

### Timezone

Diubah dari UTC ke `Asia/Makassar` untuk WIT (Waktu Indonesia Timur - Sulawesi Barat)

---

## 📝 Data yang Dibutuhkan dari API

### Dashboard ASN - `/api/asn/dashboard`

**Response expected:**
```json
{
  "total_skp": 5,
  "kinerja_bulan_ini": 23,
  "rencana_aktif": 3,
  "progres": 75,
  "recent_activities": [
    {
      "title": "Laporan Harian Diterima",
      "description": "Laporan tanggal 27 Januari 2026 telah disetujui",
      "date": "2 jam yang lalu"
    }
  ]
}
```

### Dashboard Atasan - `/api/atasan/dashboard`

**Response expected:**
```json
{
  "total_bawahan": 12,
  "pending_approval": 5,
  "approved": 45,
  "avg_kinerja": 82,
  "pending_approvals": [
    {
      "id": 1,
      "pegawai": "Ahmad Suharli",
      "title": "Laporan Kinerja Harian",
      "date": "27 Januari 2026"
    }
  ],
  "team_performance": [
    {
      "name": "Ahmad Suharli",
      "unit": "Bagian Umum",
      "progress": 85
    }
  ]
}
```

---

## ✅ Testing Checklist

### Local Testing

- [ ] Login berhasil dengan user test
- [ ] Redirect ke dashboard sesuai role
- [ ] Sidebar muncul dengan menu sesuai role
- [ ] Navbar menampilkan user info
- [ ] User dropdown berfungsi (Alpine.js)
- [ ] Sidebar toggle berfungsi (Alpine.js)
- [ ] Logout berhasil dan kembali ke login
- [ ] Session terhapus setelah logout

### Production Testing

- [ ] `.htaccess` redirect ke Laravel public
- [ ] Logo Kemenag tampil (atau fallback)
- [ ] HTTPS berfungsi tanpa mixed content
- [ ] API calls ke production endpoint
- [ ] Session persistent
- [ ] Role-based access bekerja
- [ ] Error handling tampil dengan baik

---

## 🐛 Troubleshooting

### Error: "Route [login] not defined"

**Solusi:** Pastikan route login sudah didefinisikan di `routes/web.php`:
```php
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
```

### Error: "Class 'CheckRole' not found"

**Solusi:** Pastikan middleware sudah diregister di `bootstrap/app.php`:
```php
$middleware->alias([
    'role' => \App\Http\Middleware\CheckRole::class,
]);
```

### Error: "Tidak dapat terhubung ke server"

**Solusi:** Cek konfigurasi `API_BASE_URL` di `.env` dan pastikan API endpoint accessible.

### Logo tidak muncul

**Solusi:**
1. Upload logo ke `public/images/logo/logo-kemenag.png`
2. Atau biarkan fallback (huruf "K" dalam circle) yang muncul

### Mixed Content Warning (HTTPS)

**Solusi:** Pastikan `API_BASE_URL` menggunakan `https://` di production.

---

## 📦 Dependencies

**Server Requirements:**
- PHP >= 8.2
- MySQL >= 5.7
- Laravel 12
- Composer

**Frontend Stack:**
- Tailwind CSS 3.x (via CDN)
- Alpine.js 3.x (via CDN)
- Google Fonts Inter

**Tidak perlu:**
- ❌ Node.js
- ❌ npm build
- ❌ PM2
- ❌ Next.js

---

## 🎯 Next Steps (Tahap 2 - Implementasi Fitur)

Setelah UI layer berjalan, tahap selanjutnya:

1. **Implementasi CRUD untuk setiap modul**
2. **Integrasi penuh dengan API endpoints**
3. **Upload file & attachment handling**
4. **Reporting & export PDF**
5. **Notification system**
6. **Real-time updates (optional)**

---

## 📞 Support

Jika ada masalah saat deployment, cek:
1. Laravel logs: `storage/logs/laravel.log`
2. Apache error logs di cPanel
3. Browser console untuk JavaScript errors
4. Network tab untuk API request failures

---

**🚀 Migrasi UI Layer Selesai!**

Login page, Layout, Sidebar, Dashboard ASN & Atasan sudah siap digunakan.
Tidak ada perubahan pada API backend atau database.
