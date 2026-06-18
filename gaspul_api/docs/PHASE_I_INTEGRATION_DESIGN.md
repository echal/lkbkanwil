# PHASE I — INTEGRATION DESIGN DOCUMENT
## gaspul_api ↔ e-SARAku Helpdesk
### Arsitektur Integrasi Resmi

**Versi       :** 1.0  
**Tanggal     :** 15 Juni 2026  
**Status      :** DESIGN — Belum Diimplementasi  
**Scope       :** Desain integrasi autentikasi + identitas ASN  

---

## 1. ARCHITECTURE OVERVIEW

```
┌─────────────────────────────────────────────────────────────┐
│                        ASN (Browser)                        │
│                  Akun tunggal: NIP + Password               │
└──────────────────────┬──────────────────────────────────────┘
                       │
          ┌────────────▼────────────┐
          │      gaspul_api         │
          │  (Identity Provider)    │
          │                         │
          │  POST /api/login        │
          │  GET  /api/me           │
          │  GET  /api/asn/profile  │
          │                         │
          │  DB: users (master ASN) │
          │  Sanctum Token          │
          └────────────┬────────────┘
                       │ Sanctum Bearer Token
                       │
          ┌────────────▼────────────┐
          │    esaraku_helpdesk     │
          │  (Service Consumer)     │
          │                         │
          │  Login → relay ke API  │
          │  Terima token + profil  │
          │  Buat shadow session    │
          │                         │
          │  DB: users (shadow)     │
          │  DB: tickets            │
          │  DB: conversations      │
          └─────────────────────────┘
```

**Prinsip utama:**
- gaspul_api adalah **satu-satunya sumber kebenaran** identitas ASN
- Helpdesk **tidak menyimpan password** ASN
- ASN menggunakan **satu kredensial** untuk kedua sistem
- Tiket dan chat tetap terhubung ke **akun shadow lokal helpdesk**

---

## 2. AUTHENTICATION FLOW

### Langkah Login ASN ke Helpdesk

```
ASN mengisi form login helpdesk
         │
         ▼
[1] helpdesk LoginController menerima {email, password}
         │
         ▼
[2] helpdesk POST → gaspul_api /api/login
    Body: {email, password}
         │
         ├─ 401 → kredensial salah
         │         helpdesk: tampilkan "Email atau password salah"
         │
         ├─ 403 → akun NONAKTIF
         │         helpdesk: tampilkan "Akun Anda tidak aktif"
         │
         └─ 200 → {access_token, user{id,name,email,role,nip}}
                   │
                   ▼
[3] helpdesk ambil profil lengkap
    GET /api/me (Authorization: Bearer <token>)
    Response: {user{id,name,nip,email,role,jabatan,unit_id,unit_name,status}}
                   │
                   ▼
[4] helpdesk cari atau buat shadow user
    WHERE gaspul_user_id = gaspul_api.user.id
    Jika tidak ada → INSERT users(name,email,role='asn',gaspul_user_id,nip,...)
    Jika ada       → UPDATE name, unit_name, jabatan (sinkronisasi)
                   │
                   ▼
[5] helpdesk buat session lokal untuk shadow user
    Auth::login($shadowUser)
                   │
                   ▼
[6] Simpan gaspul_token di session (untuk keperluan token relay jika diperlukan)
    Session: ['gaspul_token' => $accessToken]
                   │
                   ▼
[7] Redirect ke dashboard helpdesk
```

### Token Verification (request berikutnya)
Token gaspul tidak perlu dikirim ulang ke API untuk setiap request helpdesk. Session lokal helpdesk sudah cukup. Token disimpan di session hanya jika helpdesk perlu memanggil endpoint gaspul_api yang lain (misal: ambil data kinerja).

---

## 3. IDENTITY MAPPING

### Field Mapping: gaspul_api → helpdesk shadow user

| Field gaspul_api | Field helpdesk `users` | Sifat | Catatan |
|------------------|----------------------|-------|---------|
| `id` | `gaspul_user_id` (baru) | **WAJIB** | Kunci tautan permanen |
| `nip` | `nip` (baru) | **WAJIB** | Identitas unik ASN |
| `name` | `name` | **WAJIB** | Selalu diperbarui saat login |
| `email` | `email` | **WAJIB** | Email login (mungkin berubah di gaspul) |
| `role` = `ASN` | `role` = `asn` | **WAJIB** | Mapping tetap: ASN → asn |
| `jabatan` | `jabatan` (baru) | Opsional | Untuk tampilan tiket |
| `unit_name` | `unit_name` (baru) | Opsional | Untuk tampilan tiket |
| `status` = `AKTIF` | `status` = `active` | Tersirat | Dijamin oleh login filter |
| `password` | *(tidak disimpan)* | — | SSO: password hanya di gaspul |

### Field helpdesk yang TIDAK berasal dari gaspul
| Field helpdesk | Sumber | Keterangan |
|----------------|--------|------------|
| `id` (PK) | Auto-increment helpdesk | ID internal helpdesk |
| `password` | `null` atau random hash | ASN tidak boleh login dengan form biasa |
| `last_login_at` | Diupdate oleh helpdesk | Saat shadow session dibuat |
| `status` | Dikelola helpdesk | Default `active`; bisa dinonaktifkan oleh admin helpdesk |

---

## 4. SESSION STRATEGY

### Analisis Tiga Opsi

**A. Token Relay**
Helpdesk meneruskan Sanctum token gaspul ke setiap request helpdesk. Tidak ada session lokal — setiap request helpdesk diverifikasi ke gaspul_api.

- Pro: Tidak perlu shadow table; token selalu fresh
- Kontra: Setiap aksi helpdesk (tambah komentar, ubah status) = 1 HTTP request tambahan ke gaspul. Latensi tinggi. gaspul_api menjadi SPOF (single point of failure) untuk helpdesk.
- **Tidak direkomendasikan**

**B. Session Bridge (DIPILIH)**
Helpdesk relay login sekali → buat shadow user → buat session lokal helpdesk. Semua aksi helpdesk berikutnya hanya menyentuh DB helpdesk. Token gaspul disimpan di session untuk keperluan data enrichment jika diperlukan.

- Pro: Performa baik; helpdesk independen setelah login; natural untuk Laravel session auth
- Kontra: Data profil ASN perlu di-sync saat login (nama/jabatan berubah di gaspul tidak auto-update kecuali login ulang)
- **Direkomendasikan**

**C. SSO Penuh (OIDC/OAuth2)**
Implement OAuth2 flow di gaspul_api sebagai authorization server. Helpdesk sebagai OAuth2 client.

- Pro: Standar industri; token refresh, scope, revocation semua terdefinisi
- Kontra: Memerlukan implementasi OAuth2 di gaspul_api (Laravel Passport atau custom). Kompleksitas sangat tinggi untuk skala sistem ini.
- **Tidak direkomendasikan** — over-engineering untuk kebutuhan saat ini

### Rekomendasi Final: **Session Bridge (Opsi B)**

---

## 5. TICKET OWNERSHIP STRATEGY

### Analisis Tiga Opsi

**OPTION A — `tickets.user_id` (existing, FK → helpdesk users.id)**
Tiket terhubung ke shadow user lokal helpdesk. Saat ini sudah diimplementasi.

```sql
tickets.user_id → helpdesk.users.id (shadow ASN)
```

- Pro: Tidak perlu ubah schema apapun; relasi sudah ada; policy sudah benar
- Kontra: `user_id` adalah ID lokal helpdesk, bukan ID gaspul — perlu join ke `users.gaspul_user_id` jika ingin query lintas sistem
- **Direkomendasikan** — tetap gunakan ini dengan shadow user

**OPTION B — `tickets.gaspul_user_id`**
Ganti atau tambah FK langsung ke gaspul user ID.

- Pro: Traceability langsung ke gaspul
- Kontra: gaspul ID bukan FK yang bisa di-enforce di DB helpdesk (cross-database); butuh migrasi schema tiket
- **Tidak direkomendasikan**

**OPTION C — `tickets.external_user_id` (generic)**
Field string untuk menyimpan ID dari sistem eksternal manapun.

- Pro: Fleksibel untuk masa depan
- Kontra: Kehilangan type safety; tidak bisa di-join; over-engineering
- **Tidak direkomendasikan**

### Rekomendasi Final: **OPTION A** — Tetap `tickets.user_id → helpdesk.users.id`

Shadow user cukup sebagai jembatan. Tambah kolom `gaspul_user_id` di `helpdesk.users` sebagai lookup key saat perlu cross-reference.

---

## 6. CHAT OWNERSHIP STRATEGY

### Tabel yang Terdampak
- `conversations` — kolom `user_id` (FK → helpdesk users)
- `messages` — kolom `sender_id` (FK → helpdesk users, nullable)

### Analisis Dampak
Saat ASN dari gaspul login ke helpdesk melalui SSO:
1. Shadow user dibuat/diperbarui di helpdesk `users`
2. Conversation tetap terhubung ke `conversations.user_id = shadow_user.id`
3. Messages tetap terhubung ke `messages.sender_id = shadow_user.id` atau `operator_id`

Tidak ada perubahan schema yang diperlukan pada chat. Shadow user model yang sama digunakan untuk tiket dan chat.

### Catatan
Jika ASN dihapus dari gaspul_api, shadow user di helpdesk **tidak otomatis dihapus**. Ini by design — histori tiket dan chat harus tetap ada. Admin helpdesk dapat menonaktifkan shadow user secara manual.

---

## 7. SYNCHRONIZATION STRATEGY

### Analisis Tiga Opsi

**Real-time API (polling)**
Helpdesk memanggil gaspul setiap X menit untuk sync data ASN.

- Kontra: Overhead tinggi, gaspul harus online selalu
- **Tidak direkomendasikan**

**Local Cache / Shadow Table (DIPILIH)**
Data ASN di-cache ke shadow `users` helpdesk saat login. Update terjadi natural: setiap kali ASN login, data profil diperbarui dari `GET /api/me`.

- Pro: Performa baik; offline-resilient; natural dengan session bridge
- Kontra: Data profil stale antara login sessions (nama jabatan berubah baru terupdate saat login berikutnya) — ini acceptable
- **Direkomendasikan**

**Event-driven webhook**
gaspul_api push perubahan data ke helpdesk webhook saat ada update pegawai.

- Pro: Data selalu sinkron
- Kontra: gaspul tidak memiliki sistem event/webhook — memerlukan implementasi baru yang signifikan
- **Tidak direkomendasikan** untuk fase ini

### Rekomendasi Final: **Shadow Table + sync on login**

---

## 8. SECURITY MODEL

### Analisis Risiko

| Risiko | Skenario | Mitigasi |
|--------|----------|---------|
| **Token leakage** | Token gaspul bocor dari session helpdesk | Session disimpan server-side (database/file); token tidak pernah dikirim ke browser client |
| **Replay attack** | Token lama digunakan kembali | Sanctum token adalah opaque string — tidak ada informasi waktu. Mitigasi: set `expiration` di sanctum.php (saat ini null → perlu di-set) |
| **Impersonation** | Seseorang memalsukan request dengan NIP orang lain | Login selalu melalui gaspul_api — password divalidasi gaspul. Helpdesk tidak menerima NIP saja tanpa token valid |
| **Expired token** | Token helpdesk session berakhir | Session helpdesk menggunakan lifetime Laravel standar (120 menit by default). Token gaspul disimpan di session, ikut kedaluwarsa dengan session |
| **Revoked token** | Admin gaspul hapus token ASN | Shadow session helpdesk tetap aktif sampai session berakhir. ASN perlu logout-login ulang. Ini akceptable untuk use case saat ini |
| **ASN NONAKTIF dapat tiket lama** | ASN dinonaktifkan di gaspul, tiket lama masih ada | Shadow user tetap ada di helpdesk; admin helpdesk nonaktifkan shadow user secara manual jika perlu |

### Rekomendasi Keamanan Tambahan
1. Set `sanctum.expiration` = 1440 (24 jam) di gaspul `config/sanctum.php`
2. Hapus semua token lama saat login (`$user->tokens()->delete()` sebelum `createToken()`)
3. Password shadow user di helpdesk: set `password = bcrypt(random_bytes(32))` — tidak bisa digunakan untuk login langsung

---

## 9. MIGRATION IMPACT (HELPDESK)

Migration baru yang diperlukan di `esaraku_helpdesk`:

### Migration 1 — Tambah kolom identitas gaspul ke `users`
```php
// File: 2026_XX_XX_add_gaspul_fields_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->unsignedBigInteger('gaspul_user_id')->nullable()->unique()->after('id');
    $table->string('nip', 18)->nullable()->unique()->after('gaspul_user_id');
    $table->string('jabatan')->nullable()->after('last_login_at');
    $table->string('unit_name')->nullable()->after('jabatan');
    $table->index('gaspul_user_id');
    $table->index('nip');
});
```

### Migration 2 — Buat tabel `gaspul_tokens` (opsional, untuk token management)
Jika diperlukan token relay di masa depan, tabel ini menyimpan token gaspul per user helpdesk. Untuk fase ini, cukup simpan di session Laravel — **migration ini DITUNDA**.

### Perubahan di helpdesk `users` model
```php
protected $fillable = [
    // tambah:
    'gaspul_user_id', 'nip', 'jabatan', 'unit_name',
];
```

**Total migration baru: 1 wajib, 1 opsional (ditunda)**

---

## 10. READINESS SCORE

| Dimensi | Score | Keterangan |
|---------|-------|------------|
| **Database** | 75/100 | gaspul siap; helpdesk perlu 1 migration tambahan |
| **Authentication** | 85/100 | Sanctum siap; 3 blocker sudah di-fix; token expiration masih null |
| **Security** | 70/100 | CORS OK; status filter OK; token expiration perlu di-set; shadow password perlu random |
| **Scalability** | 90/100 | Session bridge + shadow table: overhead minimal, tidak ada real-time dependency |
| **Integration** | 65/100 | Desain lengkap tersedia; implementasi belum dimulai; perlu 1 sprint development |

**Overall Readiness Score: 77/100**

### Kondisi untuk Mencapai 90+
1. Set `sanctum.expiration = 1440`
2. Jalankan migration helpdesk (tambah `gaspul_user_id`, `nip`, `jabatan`, `unit_name`)
3. Implementasi `GaspulAuthController` di helpdesk
4. Implementasi shadow user creation/update
5. Uji e2e: login gaspul → tiket dibuat → operator melihat nama ASN benar

---

## LAMPIRAN — Endpoint Summary untuk Helpdesk Developer

| Endpoint | Method | Auth | Kegunaan |
|----------|--------|------|---------|
| `/api/login` | POST | Publik | Login ASN, dapatkan token |
| `/api/me` | GET | Bearer token | Verifikasi token + profil lengkap |
| `/api/logout` | POST | Bearer token | Invalidate token saat logout helpdesk |

*Cukup 3 endpoint ini untuk implementasi Session Bridge.*
