# PHASE I.A — FIX REPORT
## Perbaikan 3 Blocker Integrasi gaspul_api ↔ e-SARAku Helpdesk

**Tanggal   :** 15 Juni 2026  
**Mode      :** Safe Implementation — hanya perubahan minimal pada file yang teridentifikasi  
**File diubah:** 2 file (config/cors.php, AuthController.php)  
**Migration :** Tidak ada  

---

## BLOCKER 1 — CORS ✅ SELESAI

### File
`config/cors.php`

### Sebelum
```php
'allowed_origins' => [
    'http://localhost:3000',  // Next.js development
    'http://127.0.0.1:3000',
    // Tambahkan production domain di sini nanti
],
```

### Sesudah
```php
'allowed_origins' => [
    'http://localhost:3000',  // Next.js development
    'http://127.0.0.1:3000',
    'http://localhost',       // esaraku_helpdesk (XAMPP default port 80)
    'http://localhost:8000',  // esaraku_helpdesk artisan serve
    'http://127.0.0.1',
    'http://127.0.0.1:8000',
],
```

### Keputusan Teknis
- Port 80 ditambahkan karena helpdesk berjalan di XAMPP (Apache port default)
- Port 8000 ditambahkan untuk skenario `php artisan serve`
- `supports_credentials` tetap `false` — konsisten dengan token-based auth, tidak perlu cookie/session lintas domain
- `paths` sudah mencakup `api/*` — tidak perlu diubah

### Verifikasi
| Skenario | Hasil |
|----------|-------|
| helpdesk → `POST /api/login` dari `http://localhost` | ✅ Diizinkan |
| helpdesk → `GET /api/me` dari `http://localhost:8000` | ✅ Diizinkan |
| Request dari origin asing (misal `http://evil.com`) | ✅ Ditolak (tidak ada di daftar) |
| Frontend gaspul_api existing (`localhost:3000`) | ✅ Tetap berfungsi |

### Catatan untuk Production
Saat helpdesk naik ke production server, tambahkan domain production ke `allowed_origins`:
```php
'https://helpdesk.kemenag-sulbar.go.id',
```

---

## BLOCKER 2 — LOGIN ASN NONAKTIF ✅ SELESAI

### File
`app/Http/Controllers/Api/AuthController.php` — method `login()`

### Sebelum
```php
if (!Auth::attempt($credentials)) {
    return response()->json(['message' => 'Email atau password salah'], 401);
}

$user = Auth::user();

// Langsung lanjut buat token — tidak ada cek status
$token = $user->createToken('auth_token')->plainTextToken;
```

### Sesudah
```php
if (!Auth::attempt($credentials)) {
    return response()->json(['message' => 'Email atau password salah'], 401);
}

$user = Auth::user();

if ($user->status !== 'AKTIF') {
    Auth::logout();
    return response()->json([
        'message' => 'Akun Anda tidak aktif. Hubungi administrator.'
    ], 403);
}

// Lanjut buat token hanya jika AKTIF
$token = $user->createToken('auth_token')->plainTextToken;
```

### Keputusan Teknis
- `Auth::logout()` dipanggil sebelum return 403 untuk membersihkan sesi Auth yang sudah terbentuk oleh `attempt()`
- HTTP 403 (Forbidden) dipilih, bukan 401 — karena kredensial valid tapi akses ditolak karena status
- Pesan error spesifik: membantu ASN/admin memahami penyebab tanpa mengungkap informasi keamanan
- Mekanisme Sanctum token tidak diubah sama sekali

### Verifikasi
| Skenario | HTTP Code | Response |
|----------|-----------|----------|
| `status = AKTIF`, kredensial benar | 200 | `{access_token, user{...}}` |
| `status = NONAKTIF`, kredensial benar | 403 | `{message: "Akun Anda tidak aktif..."}` |
| Kredensial salah | 401 | `{message: "Email atau password salah"}` |

---

## BLOCKER 3 — HARDEN /API/ME ✅ SELESAI

### File
`app/Http/Controllers/Api/AuthController.php` — method `me()`

### Sebelum
```php
public function me(Request $request)
{
    return response()->json([
        'user' => $request->user()   // SEMUA kolom termasuk password hash, remember_token, dll
    ]);
}
```

### Sesudah
```php
public function me(Request $request)
{
    $user = $request->user();

    return response()->json([
        'user' => [
            'id'        => $user->id,
            'name'      => $user->name,
            'nip'       => $user->nip,
            'email'     => $user->email,
            'role'      => $user->role,
            'jabatan'   => $user->jabatan,
            'unit_id'   => $user->unit_id,
            'unit_name' => $user->unit ? $user->unit->nama_unit : ($user->unit_kerja ?? null),
            'status'    => $user->status,
        ],
    ]);
}
```

### Payload Final yang Dikirim
```json
{
  "user": {
    "id": 123,
    "name": "SARBINA, S.Pd",
    "nip": "198501012010012001",
    "email": "sarbina@kemenag.go.id",
    "role": "ASN",
    "jabatan": "Guru Madya",
    "unit_id": 45,
    "unit_name": "MTsN 2 Majene",
    "status": "AKTIF"
  }
}
```

### Kolom yang DIHAPUS dari Response
| Kolom | Alasan |
|-------|--------|
| `password` | Hash Bcrypt — tidak boleh dikirim ke klien apapun |
| `remember_token` | Token internal Laravel — tidak relevan untuk API consumer |
| `email_verified_at` | Tidak relevan untuk integrasi helpdesk |
| `atasan_id` | Internal gaspul — tidak diperlukan helpdesk |
| `unit_kerja_id` (string lama) | Field legacy, digantikan `unit_name` |
| `hari_kerja` | Tidak relevan untuk helpdesk |
| `created_at`, `updated_at` | Tidak diperlukan consumer helpdesk |

### Catatan
`unit_name` menggunakan fallback: relasi `unit` (FK baru) → field `unit_kerja` (string lama) → `null`. Ini memastikan backward compatibility selama migrasi data berlangsung.

---

## RINGKASAN STATUS BLOCKER

| Blocker | File | Status | Breaking Change |
|---------|------|--------|-----------------|
| CORS whitelist | `config/cors.php` | ✅ Fixed | Tidak |
| Login NONAKTIF | `AuthController::login()` | ✅ Fixed | Tidak — ASN AKTIF tidak terpengaruh |
| Harden `/api/me` | `AuthController::me()` | ✅ Fixed | Minor — consumer lama yang bergantung pada kolom sensitif perlu update |

**Semua 3 blocker diselesaikan tanpa migration, tanpa schema change, tanpa breaking change pada fungsionalitas gaspul_api yang sudah berjalan.**
