# 🔧 Troubleshooting Guide

## Common Errors & Solutions

### 1. ❌ Error: "View [xxx.xxx.xxx] not found"

**Penyebab:** View file belum dibuat untuk route tersebut.

**Solusi:**
Semua view placeholder sudah dibuat di:
```
resources/views/
├── asn/
│   ├── harian/
│   │   ├── index.blade.php
│   │   ├── tambah.blade.php
│   │   └── edit.blade.php
│   ├── rencana-kerja/
│   │   ├── index.blade.php
│   │   ├── tambah.blade.php
│   │   ├── detail.blade.php
│   │   └── edit.blade.php
│   ├── skp-tahunan/
│   │   └── index.blade.php
│   └── bulanan/
│       ├── index.blade.php
│       └── edit.blade.php
├── atasan/
│   ├── approval/
│   │   ├── index.blade.php
│   │   └── show.blade.php
│   ├── kinerja-bawahan/
│   │   └── index.blade.php
│   └── skp-tahunan/
│       ├── index.blade.php
│       └── show.blade.php
└── admin/
    ├── users/
    │   ├── index.blade.php
    │   ├── tambah.blade.php
    │   └── edit.blade.php
    ├── units/
    │   ├── index.blade.php
    │   ├── tambah.blade.php
    │   └── edit.blade.php
    └── sasaran-kegiatan/
        ├── index.blade.php
        ├── tambah.blade.php
        ├── edit.blade.php
        └── indikator.blade.php
```

Jika ada view yang hilang, copy template dari view lain dan sesuaikan title-nya.

---

### 2. ❌ Error: "Tidak dapat terhubung ke server" (Login)

**Status:** ✅ **SUDAH DIPERBAIKI**

**Solusi Lama (tidak berlaku lagi):**
- Login dulu memanggil API `/api/login` yang menyebabkan error

**Solusi Baru:**
- Login sekarang **langsung ke database** via `Auth::attempt()`
- Tidak perlu API call
- Pastikan user ada di table `users` dengan password ter-hash

**Test:**
```bash
mysql -u root gaspul_api -e "SELECT id, email, role FROM users WHERE email = 'admin@lkbkanwil.com';"
```

---

### 3. ❌ Error: "Email atau password salah"

**Penyebab:**
1. Email tidak ada di database
2. Password tidak match (hash salah)
3. User tidak aktif

**Solusi:**

**Cek user ada:**
```bash
mysql -u root gaspul_api -e "SELECT * FROM users WHERE email = 'admin@lkbkanwil.com';"
```

**Reset password ke `password123`:**
```bash
mysql -u root gaspul_api -e "UPDATE users SET password = '\$2y\$12\$2Qu3Wj4hZYHYnZIVCNFyTevfPrbl.eyuu9Xf7KwWlnsX9yBgRw.P6' WHERE email = 'admin@lkbkanwil.com';"
```

**Atau via Tinker:**
```bash
php artisan tinker
```
```php
$user = App\Models\User::where('email', 'admin@lkbkanwil.com')->first();
$user->password = bcrypt('password123');
$user->save();
exit
```

---

### 4. ❌ Error: "Route [xxx] not defined"

**Penyebab:** Route belum didefinisikan atau name salah.

**Solusi:**

**Cek route ada:**
```bash
php artisan route:list --name=xxx
```

**Verify route di `routes/web.php`:**
```php
Route::get('/path', [Controller::class, 'method'])->name('route.name');
```

---

### 5. ❌ Error: "Class 'CheckRole' not found"

**Penyebab:** Middleware CheckRole belum diregister.

**Solusi:**

**Cek di `bootstrap/app.php`:**
```php
use App\Http\Middleware\CheckRole;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => CheckRole::class,
    ]);
})
```

**Regenerate config:**
```bash
php artisan config:clear
php artisan optimize:clear
```

---

### 6. ❌ Error: 403 Forbidden (Access Denied)

**Penyebab:** User tidak memiliki role yang sesuai untuk mengakses halaman.

**Contoh:**
- User dengan role `ASN` mencoba akses `/atasan/approval`
- User dengan role `ATASAN` mencoba akses `/admin/users`

**Solusi:**

**Cek role user:**
```bash
mysql -u root gaspul_api -e "SELECT id, name, email, role FROM users WHERE email = 'xxx@xxx.com';"
```

**Update role jika salah:**
```bash
mysql -u root gaspul_api -e "UPDATE users SET role = 'ADMIN' WHERE email = 'xxx@xxx.com';"
```

**Role yang valid:**
- `ADMIN` → Akses ke `/admin/*`
- `ATASAN` → Akses ke `/atasan/*`
- `ASN` → Akses ke `/asn/*`

---

### 7. ❌ Error: "SQLSTATE[HY000] [1045] Access denied for user"

**Penyebab:** Database credentials di `.env` salah.

**Solusi:**

**Cek `.env`:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gaspul_api
DB_USERNAME=root
DB_PASSWORD=
```

**Test connection:**
```bash
mysql -u root -p gaspul_api -e "SHOW TABLES;"
```

**Clear config cache:**
```bash
php artisan config:clear
```

---

### 8. ❌ Sidebar tidak muncul atau menu kosong

**Penyebab:**
1. JavaScript Alpine.js tidak load
2. Role user tidak match dengan condition di sidebar

**Solusi:**

**Cek Alpine.js load:**
- Buka browser Developer Tools → Console
- Cari error terkait Alpine.js
- Pastikan CDN `https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js` accessible

**Cek role di sidebar blade:**
```blade
@if(auth()->user()->role === 'ASN')
    <!-- ASN Menu -->
@endif
```

Pastikan role di database match dengan condition (uppercase: ASN, ATASAN, ADMIN).

---

### 9. ❌ Logo Kemenag tidak muncul

**Penyebab:** File logo tidak ada di `public/images/logo/logo-kemenag.png`

**Solusi:**

**Upload logo:**
1. Buat folder: `public/images/logo/`
2. Upload file logo: `logo-kemenag.png`

**Atau pakai fallback:**
Login page sudah punya fallback (huruf "K" dalam circle) jika logo tidak ada.

**No action needed** - fallback akan otomatis muncul.

---

### 10. ❌ Dashboard data kosong

**Penyebab:** DashboardController mencoba fetch data dari API yang belum ada.

**Solusi:**

**Temporary fix (sudah implemented):**
Dashboard akan render dengan data kosong jika API gagal:
```php
'stats' => [
    'total_skp' => 0,
    'kinerja_bulan_ini' => 0,
    'rencana_aktif' => 0,
    'progres' => 0,
],
```

**Long-term fix (Tahap 2):**
Implement API endpoints untuk dashboard:
- `/api/asn/dashboard`
- `/api/atasan/dashboard`

---

### 11. ❌ Session expired / logged out automatically

**Penyebab:**
1. Session lifetime terlalu pendek
2. Session driver tidak cocok

**Solusi:**

**Update `.env`:**
```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

**Clear session:**
```bash
rm -rf storage/framework/sessions/*
php artisan config:clear
```

---

### 12. ⚠️ Warning: "Mixed content" di production (HTTPS)

**Penyebab:** Assets load via HTTP di halaman HTTPS.

**Solusi:**

**Update `.env` production:**
```env
APP_URL=https://lkbkanwil.gaspul.com
API_BASE_URL=https://lkbkanwil.gaspul.com/gaspul_api/public/api
```

**Atau force HTTPS di `AppServiceProvider.php`:**
```php
public function boot()
{
    if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
}
```

---

## Testing Checklist

### Local Development

```bash
# 1. Start server
cd gaspul_api
php artisan serve

# 2. Test login
curl -X POST http://localhost:8000/login \
  -d "email=admin@lkbkanwil.com" \
  -d "password=password123"

# 3. Open browser
open http://localhost:8000/login
```

**Expected result:**
- ✅ Login page loads (logo + form)
- ✅ Login successful
- ✅ Redirect to dashboard
- ✅ Sidebar shows with correct menu
- ✅ User dropdown works

### Production Deployment

```bash
# 1. Clear all cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 2. Set permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# 3. Test database connection
php artisan migrate:status

# 4. Test routes
php artisan route:list
```

---

## Quick Fixes

### Reset Everything

```bash
cd gaspul_api

# Clear all Laravel cache
php artisan optimize:clear

# Clear compiled views
rm -rf storage/framework/views/*

# Clear session
rm -rf storage/framework/sessions/*

# Regenerate autoload
composer dump-autoload

# Restart server
php artisan serve
```

### Reset Password Semua User ke `password123`

```bash
mysql -u root gaspul_api << EOF
UPDATE users
SET password = '\$2y\$12\$2Qu3Wj4hZYHYnZIVCNFyTevfPrbl.eyuu9Xf7KwWlnsX9yBgRw.P6';
EOF
```

---

## Getting Help

### Check Logs

```bash
# Laravel log
tail -f storage/logs/laravel.log

# Apache/Nginx log (production)
tail -f /var/log/apache2/error.log
```

### Debug Mode

**Enable debug di `.env` (local only):**
```env
APP_DEBUG=true
```

**⚠️ NEVER enable di production!**

---

## Contact

Jika masih ada error yang tidak tercantum di sini, cek:
1. Laravel logs: `storage/logs/laravel.log`
2. Browser console (F12 → Console tab)
3. Network tab (F12 → Network tab) untuk API errors
