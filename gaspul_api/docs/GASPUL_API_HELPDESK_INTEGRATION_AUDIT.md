# PHASE I — PRE-INTEGRATION AUDIT
## gaspul_api ↔ e-SARAku Helpdesk
### Kesiapan gaspul_api sebagai Identity Provider

**Tanggal Audit  :** 15 Juni 2026  
**Mode           :** READ-ONLY — tidak ada perubahan kode, migrasi, atau file baru (kecuali dokumen ini)  
**Auditor        :** Claude Code / Tim Pengembang e-SARAku  
**Scope          :** Kesiapan teknis integrasi autentikasi & identitas ASN

---

## RINGKASAN EKSEKUTIF

| Aspek | Status | Catatan |
|-------|--------|---------|
| **Sanctum Token Auth** | ✅ SIAP | `HasApiTokens`, endpoint `/api/login` berfungsi |
| **Data ASN yang dibutuhkan** | ✅ TERSEDIA | id, name, nip, email, role, jabatan, unit_id |
| **CORS untuk helpdesk** | ⚠️ PERLU TAMBAH | Domain helpdesk belum di-whitelist |
| **Token expiration** | ⚠️ PERLU KEPUTUSAN | Saat ini `null` (tidak pernah kedaluwarsa) |
| **Field mapping helpdesk ↔ gaspul** | ⚠️ GAP ADA | helpdesk `users` tidak punya `nip`, `unit_kerja_id`, `jabatan` |
| **Endpoint profile ASN** | ✅ ADA | `GET /api/asn/profile` mengembalikan data lengkap |
| **Role mapping** | ⚠️ BEDA SKEMA | gaspul: ASN/ATASAN/ADMIN vs helpdesk: asn/operator/supervisor/admin_helpdesk/super_admin |
| **Status pegawai** | ⚠️ BELUM DICEK DI LOGIN** | Login tidak memfilter `status = AKTIF` |
| **Ticket `user_id`** | ✅ KOMPATIBEL | FK ke helpdesk `users.id` — bukan ke gaspul |

---

## A. INFRASTRUKTUR AUTENTIKASI GASPUL_API

### A1. Sanctum Configuration
- **File:** `config/sanctum.php`
- `expiration: null` — token **tidak pernah kedaluwarsa** secara otomatis
- `stateful domains`: localhost:3000, 127.0.0.1:8000 — belum mencakup domain helpdesk production
- `supports_credentials: false` di CORS — konsisten dengan token-based auth

**Temuan:** Token berlaku selamanya kecuali di-logout secara manual. Untuk use case SSO helpdesk ini bisa menjadi risiko keamanan (token helpdesk yang "dipinjam" dari gaspul tidak ada masa berlakunya).

### A2. Endpoint Login
```
POST /api/login
Body: { email, password }
```

**Response:**
```json
{
  "message": "Login berhasil",
  "access_token": "<plain_text_token>",
  "token_type": "Bearer",
  "user": {
    "id": ...,
    "name": "...",
    "email": "...",
    "role": "ASN|ATASAN|ADMIN",
    "nip": "..."
  }
}
```

**Temuan kritis:** `AuthController::login()` menggunakan `Auth::attempt()` yang hanya memvalidasi email + password. **Tidak ada pengecekan `status = 'AKTIF'`**. Artinya ASN berstatus `NONAKTIF` masih bisa login dan mendapatkan token yang valid.

### A3. Endpoint `/api/me`
```
GET /api/me
Header: Authorization: Bearer <token>
```

**Response:** Mengembalikan **seluruh kolom model User** tanpa filtering (`$request->user()` langsung). Ini termasuk field sensitif seperti `password` (yang sudah di-hash, tapi tetap sebaiknya tidak dikirim).

### A4. Endpoint Profile ASN
```
GET /api/asn/profile
Middleware: auth:sanctum + role:ASN
```

**Response terstruktur:**
```json
{
  "success": true,
  "data": {
    "id": ..., "name": "...", "nip": "...", "email": "...",
    "role": "ASN", "unit_id": ..., "unit_name": "...",
    "unit_kode": "...", "jabatan": "...", "status": "AKTIF",
    "created_at": "...", "updated_at": "..."
  }
}
```

**Temuan:** Endpoint ini terbatas untuk role `ASN` saja (middleware `role:ASN`). Jika helpdesk perlu mengambil profil user dengan role `ATASAN`, endpoint ini tidak bisa digunakan.

---

## B. STRUKTUR DATA PENGGUNA GASPUL_API

### B1. Tabel `users` — Kolom yang Relevan

Berdasarkan migrasi yang terkumpul:

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | ID unik pegawai |
| `name` | string | Nama lengkap |
| `email` | string unique | Email (digunakan untuk login) |
| `password` | string (hashed) | — |
| `role` | enum | `ASN`, `ATASAN`, `ADMIN` |
| `nip` | string(18) unique | NIP ASN |
| `unit_kerja_id` | string (legacy) | Field lama unit kerja (text) |
| `unit_id` | FK → units | FK ke tabel `units` (field baru) |
| `jabatan` | string nullable | Jabatan pegawai |
| `status` | enum | `AKTIF`, `NONAKTIF` |
| `atasan_id` | FK → users | Self-referencing, hierarki approval |
| `hari_kerja` | enum nullable | `SENIN_JUMAT`, `SENIN_SABTU` |

**Temuan:** Ada dua field unit: `unit_kerja_id` (string lama) dan `unit_id` (FK baru ke tabel `units`). Kedua field masih digunakan bersamaan.

### B2. Endpoint Admin untuk Listing User
```
GET /api/admin/users          (list semua)
GET /api/admin/users/{id}     (detail per user)
Middleware: auth:sanctum + role:ADMIN
```

Data yang dikembalikan mencakup: `id, name, nip, email, role, unit_id, unit_name, jabatan, status` — cukup untuk keperluan helpdesk.

---

## C. CORS & AKSESIBILITAS JARINGAN

### C1. Konfigurasi CORS
**File:** `config/cors.php`

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
    // Tambahkan production domain di sini nanti
],
'supports_credentials' => false,
```

**Temuan kritis:** Domain helpdesk (`http://localhost` port standar, atau domain production helpdesk) **BELUM ada dalam daftar `allowed_origins`**. Request dari helpdesk ke API gaspul akan diblokir oleh browser karena CORS violation.

**Solusi yang diperlukan (1 baris config):** Tambahkan URL helpdesk ke `allowed_origins`. Ini perubahan kecil dan tidak memerlukan migrasi atau perubahan kode.

---

## D. STRUKTUR DATA HELPDESK (esaraku_helpdesk)

### D1. Tabel `users` Helpdesk

Berdasarkan migrasi helpdesk:

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint (PK) | ID internal helpdesk |
| `name` | string | Nama |
| `email` | string unique | Email login helpdesk |
| `password` | string (hashed) | Password lokal helpdesk |
| `role` | enum | `super_admin`, `admin_helpdesk`, `supervisor`, `operator`, `asn` |
| `status` | enum | `active`, `inactive` |
| `last_login_at` | timestamp | — |

**Kolom yang TIDAK ADA di helpdesk:** `nip`, `unit_kerja_id`, `unit_id`, `jabatan`, `atasan_id`

### D2. Implikasi untuk Integrasi

ASN di helpdesk disimpan sebagai **akun lokal biasa** (name, email, password sendiri, role=`asn`). Saat ini helpdesk **tidak menyimpan** NIP, unit kerja, atau jabatan ASN.

Jika integrasi dilakukan dengan pendekatan **SSO** (helpdesk meminta gaspul_api untuk validasi), tabel `users` helpdesk perlu ditambah kolom `nip` (atau `gaspul_user_id`) sebagai kunci tautan. Ini memerlukan migration baru di helpdesk.

### D3. Ticket `user_id`
Kolom `tickets.user_id` adalah FK ke helpdesk `users.id` — bukan ke gaspul `users.id`. Dengan demikian, jika ASN login via SSO ke helpdesk, mereka **tetap mendapat akun lokal di helpdesk** (minimal dengan `id`, `name`, `email`, `role=asn`), dan tiket tetap terhubung ke akun lokal tersebut. Ini adalah pola yang benar dan tidak perlu diubah.

---

## E. ROLE MAPPING GASPUL ↔ HELPDESK

| Role gaspul_api | Role helpdesk (ekuivalen) | Catatan |
|-----------------|--------------------------|---------|
| `ASN` | `asn` | Pelapor tiket — cocok langsung |
| `ATASAN` | *(belum ada)* | Atasan tidak memiliki role di helpdesk |
| `ADMIN` | `super_admin` atau `admin_helpdesk` | ADMIN gaspul ≠ admin helpdesk |

**Temuan:** Role `ATASAN` di gaspul tidak memiliki padanan di helpdesk. Jika ATASAN ingin login ke helpdesk, perlu keputusan: apakah mereka mendapat role `supervisor` atau `admin_helpdesk` di helpdesk?

---

## F. KEAMANAN & KENDALA TEKNIS

### F1. Login Tanpa Filter Status
`AuthController::login()` tidak memfilter `status = 'AKTIF'`. Pegawai dengan status `NONAKTIF` di gaspul_api **bisa mendapat token yang valid**.

**Risiko untuk helpdesk:** Jika helpdesk menggunakan token gaspul untuk autentikasi, ASN yang sudah pensiun/nonaktif bisa tetap membuat tiket.

**Solusi:** Tambahkan pengecekan status di `login()`:
```php
if ($user->status !== 'AKTIF') {
    return response()->json(['message' => 'Akun tidak aktif'], 403);
}
```

### F2. Token Tidak Pernah Kedaluwarsa
`expiration: null` berarti token berlaku selamanya. Jika helpdesk menyimpan token gaspul sebagai session identifier, satu token yang bocor bisa digunakan tanpa batas waktu.

**Rekomendasi:** Set expiration, minimal 24 jam (1440 menit) untuk use case helpdesk.

### F3. `/api/me` Mengembalikan Data Tanpa Filter
Response `me()` adalah `$request->user()` mentah — termasuk kolom `password` (hash). Meskipun hash bukan plain text, best practice adalah memfilter kolom sensitif.

### F4. Multi-Token Tanpa Batas
Komentar `// $user->tokens()->delete();` di `login()` di-comment-out, artinya setiap login membuat token baru **tanpa menghapus token lama**. Seorang ASN yang login berkali-kali akan memiliki banyak token aktif. Ini bukan blocker untuk integrasi, tapi perlu dikelola.

---

## G. ENDPOINT YANG TERSEDIA UNTUK INTEGRASI

### G1. Endpoint yang DAPAT digunakan helpdesk secara langsung

| Endpoint | Kegunaan di helpdesk |
|----------|---------------------|
| `POST /api/login` | SSO — ASN login via kredensial gaspul |
| `GET /api/me` | Verifikasi token (meskipun response belum terfilter) |
| `GET /api/asn/profile` | Ambil detail profil ASN (hanya untuk role ASN) |
| `GET /api/admin/users` | Sinkronisasi daftar ASN ke helpdesk (ADMIN only) |
| `GET /api/admin/users/{id}` | Lookup detail ASN per id |

### G2. Endpoint yang TIDAK ADA / DIPERLUKAN

| Kebutuhan helpdesk | Status |
|-------------------|--------|
| Verifikasi token tanpa batasan role (bukan hanya ASN/ATASAN) | ⚠️ `/api/me` ada tapi response belum bersih |
| Lookup ASN by NIP | ❌ Tidak ada — hanya by `{id}` |
| Endpoint listing ASN (tanpa hak ADMIN) | ❌ Tidak ada — `GET /api/admin/users` butuh role ADMIN |
| Validasi apakah NIP terdaftar | ❌ Tidak ada |

---

## H. ARSITEKTUR INTEGRASI YANG DIREKOMENDASIKAN

Berdasarkan audit, terdapat **dua pendekatan** yang bisa ditempuh:

### Opsi 1 — SSO Terpusat (Delegasi Autentikasi)
Helpdesk tidak menyimpan password ASN. Saat ASN login ke helpdesk, helpdesk meneruskan kredensial ke `POST /api/login` gaspul, menerima token, lalu membuat/update akun lokal ASN di helpdesk.

**Kelebihan:** ASN hanya perlu satu password (gaspul).  
**Kekurangan:** Helpdesk harus memodifikasi `LoginController`, menambah kolom `nip` atau `gaspul_user_id` ke tabel `users` helpdesk, dan mengelola sinkronisasi data.

### Opsi 2 — Akun Lokal + Import Manual
Admin helpdesk mengimpor daftar ASN (dari `GET /api/admin/users`) dan membuat akun lokal di helpdesk. ASN tetap login dengan password lokal helpdesk (yang di-set oleh admin).

**Kelebihan:** Tidak ada dependensi runtime antar dua sistem.  
**Kekurangan:** Duplikasi data; perubahan data di gaspul tidak otomatis terefleksi di helpdesk.

**Rekomendasi:** Opsi 1 lebih tepat secara arsitektur, mengingat gaspul_api sudah menjadi sistem master ASN. Namun membutuhkan persiapan lebih banyak.

---

## I. CHECKLIST KESIAPAN INTEGRASI

| No. | Item | Status | Prioritas |
|-----|------|--------|-----------|
| I-01 | Sanctum `HasApiTokens` sudah terpasang di User model | ✅ Siap | — |
| I-02 | Endpoint `POST /api/login` berfungsi | ✅ Siap | — |
| I-03 | Endpoint `GET /api/me` berfungsi | ✅ Siap (perlu bersih) | Rendah |
| I-04 | CORS mengizinkan domain helpdesk | ❌ Belum | **TINGGI** |
| I-05 | Login memfilter `status = AKTIF` | ❌ Belum | **TINGGI** |
| I-06 | Token expiration dikonfigurasi | ❌ `null` | Sedang |
| I-07 | Endpoint lookup ASN by NIP | ❌ Tidak ada | Sedang |
| I-08 | Field `nip` / `gaspul_user_id` di helpdesk `users` | ❌ Belum | **TINGGI** (jika Opsi 1) |
| I-09 | Endpoint verifikasi token tanpa batasan role | ⚠️ Ada via `/me`, perlu filter | Sedang |
| I-10 | Dokumentasi API untuk konsumsi helpdesk | ❌ Belum ada | Rendah |

---

## J. KESIMPULAN & REKOMENDASI

### VERDICT: **CONDITIONAL GO**

gaspul_api **secara fundamental siap** menjadi identity provider — Sanctum sudah terpasang, endpoint login sudah ada, data ASN lengkap tersedia. Namun ada **3 blocker** yang harus diselesaikan sebelum integrasi bisa dibangun:

**Blocker 1 (CORS):** Tanpa menambahkan domain helpdesk ke `allowed_origins`, semua request dari browser helpdesk ke gaspul API akan gagal. Ini 1 baris config, risiko NOL, harus dilakukan segera.

**Blocker 2 (Status filter):** Login tanpa pengecekan `status = AKTIF` adalah celah keamanan nyata. ASN yang sudah nonaktif bisa membuat tiket helpdesk. Ini perbaikan 2-3 baris kode di `AuthController`.

**Blocker 3 (Schema helpdesk):** Tabel `users` helpdesk tidak memiliki kolom untuk menyimpan `nip` atau `gaspul_user_id`. Tanpa kolom ini, sistem tidak bisa menghubungkan akun helpdesk dengan identitas gaspul. Memerlukan migration baru di helpdesk.

### Non-Blocker yang Perlu Dijadwalkan:
- Set token expiration (keamanan jangka panjang)
- Filter `/api/me` response (hilangkan kolom sensitif)  
- Endpoint lookup by NIP (kemudahan implementasi)

---

*Dokumen ini bersifat READ-ONLY audit — tidak ada kode yang diubah dalam proses penyusunan laporan ini.*  
*Kantor Wilayah Kementerian Agama Provinsi Sulawesi Barat*  
*Mamuju, 15 Juni 2026*
