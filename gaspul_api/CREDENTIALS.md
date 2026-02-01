# 🔐 Login Credentials

**Updated:** 2026-01-29 - All passwords reset dengan Bcrypt hash

## Test Users

### 1. Admin (Primary)
```
Email: admin@lkbkanwil.com
Password: password123
Role: ADMIN
Status: ✅ TESTED & WORKING
```

### 2. Admin (Alternative)
```
Email: admin@informasi.com
Password: password123
Role: ADMIN
Status: ✅ PASSWORD RESET
```

### 3. Atasan
```
Email: atasan@test.com
Password: password123
Role: ATASAN
Status: ✅ PASSWORD RESET
```

### 4. ASN
```
Email: asn@test.com
Password: password123
Role: ASN
Status: ✅ PASSWORD RESET
```

---

## Cara Reset Password User Lain

Jika ingin reset password user lain ke `password123`:

### Via MySQL:
```bash
mysql -u root gaspul_api -e "UPDATE users SET password = '\$2y\$12\$2Qu3Wj4hZYHYnZIVCNFyTevfPrbl.eyuu9Xf7KwWlnsX9yBgRw.P6' WHERE email = 'asn@test.com';"
```

### Via Tinker:
```bash
php artisan tinker
```
```php
$user = App\Models\User::where('email', 'asn@test.com')->first();
$user->password = bcrypt('password123');
$user->save();
exit
```

---

## Generate Password Hash Baru

Jika ingin membuat password hash untuk password berbeda:

```bash
php -r "echo password_hash('passwordAnda', PASSWORD_BCRYPT);"
```

Lalu update di database:
```sql
UPDATE users SET password = 'hash_yang_dihasilkan' WHERE email = 'email@user.com';
```

---

## User Production (Server)

Di server production, pastikan user sudah ada di table `users` dengan:
- Password ter-hash menggunakan bcrypt
- Role yang sesuai (ADMIN/ATASAN/ASN)
- Status AKTIF

---

## Troubleshooting Login

### Error: "Email atau password salah"
- Cek apakah email ada di database: `SELECT * FROM users WHERE email = 'xxx';`
- Pastikan password sudah di-hash dengan bcrypt
- Pastikan field `status` = 'AKTIF' (jika ada validasi)

### Error: "Tidak dapat terhubung ke server"
- ✅ **SUDAH DIPERBAIKI**: Login sekarang langsung ke database (tidak via API)
- Pastikan database connection di `.env` sudah benar

---

## Testing Login di Local

1. Start Laravel server:
```bash
cd gaspul_api
php artisan serve
```

2. Buka browser:
```
http://localhost:8000/login
```

3. Login dengan credential di atas

4. Jika berhasil, akan redirect ke dashboard sesuai role
