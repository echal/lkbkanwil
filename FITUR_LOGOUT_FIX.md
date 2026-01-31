# Fix Error 419 pada Logout

## Masalah
User mengalami error page 419 (CSRF Token Mismatch) saat melakukan logout, terutama jika session sudah expired atau idle terlalu lama.

## Penyebab
- CSRF token expired karena session timeout
- Token mismatch antara form dan server
- Session cookie sudah tidak valid

## Solusi yang Diterapkan

### 1. JavaScript Logout Handler (Primary Solution)
**File**: `resources/views/components/navbar.blade.php`

Menambahkan JavaScript handler yang:
- Intercept form submission logout
- Menggunakan Fetch API dengan CSRF token fresh
- Redirect ke login page regardless of response (success atau error)
- Menghindari error page 419 dengan graceful handling

```javascript
document.getElementById('logout-form').addEventListener('submit', function(e) {
    e.preventDefault();

    fetch('{{ route("logout") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
        credentials: 'same-origin'
    })
    .then(() => window.location.href = '{{ route("login") }}')
    .catch(() => window.location.href = '{{ route("login") }}');
});
```

### 2. Route GET Logout Fallback
**File**: `routes/web.php`

Menambahkan route GET untuk logout sebagai fallback:
```php
Route::get('/logout', [LoginController::class, 'logout'])->name('logout.get');
```

Ini memungkinkan user untuk logout bahkan jika POST request gagal.

### 3. Custom Error Page 419
**File**: `resources/views/errors/419.blade.php`

Membuat halaman error 419 yang user-friendly dengan:
- Design modern dan clean
- Pesan yang jelas: "Sesi Anda Telah Berakhir"
- Tombol "Login Kembali"
- Auto redirect ke login page setelah 3 detik
- Tips untuk user

## Cara Testing

### Test 1: Logout Normal
1. Login ke aplikasi
2. Klik tombol "Keluar"
3. **Expected**: Redirect smooth ke login page tanpa error

### Test 2: Session Expired
1. Login ke aplikasi
2. Tunggu session expire (atau clear session manually di browser)
3. Klik tombol "Keluar"
4. **Expected**: Muncul halaman 419 yang friendly, auto redirect ke login

### Test 3: Manual Access Error 419
1. Buka URL: `http://localhost/logout` tanpa login
2. **Expected**: Muncul halaman 419 yang friendly

## File yang Dimodifikasi

1. `resources/views/components/navbar.blade.php`
   - Menambahkan ID `logout-form` pada form
   - Menambahkan JavaScript handler untuk logout

2. `routes/web.php`
   - Menambahkan route GET untuk logout fallback

3. `resources/views/errors/419.blade.php` (NEW)
   - Custom error page untuk CSRF token mismatch

## Keuntungan Solusi Ini

1. **User Experience**: Tidak ada error page merah yang menakutkan
2. **Graceful Degradation**: Tetap redirect ke login meskipun ada error
3. **Clear Communication**: Pesan error yang jelas dan informatif
4. **Auto Recovery**: Auto redirect ke login page
5. **Backward Compatible**: Tidak mengubah flow logout yang existing

## Catatan

- Solusi ini HANYA di-apply ke localhost
- Belum di-commit dan push ke repository
- Untuk production deployment, perlu review dan approval terlebih dahulu
- Meta tag CSRF sudah ada di `layouts/app.blade.php` line 6

## Rekomendasi Tambahan

Untuk mencegah session expired terlalu cepat, bisa adjust di `.env`:

```env
SESSION_LIFETIME=120  # Default 120 menit
SESSION_EXPIRE_ON_CLOSE=false
```

Atau di `config/session.php`:
```php
'lifetime' => env('SESSION_LIFETIME', 120),
'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
```
