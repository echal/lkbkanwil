# PHASE I.C — IMPACT AUDIT
## Dampak Integrasi gaspul_api terhadap Ticketing System Phase H
### e-SARAku Helpdesk

**Tanggal   :** 15 Juni 2026  
**Mode      :** Read-Only Audit — tidak ada perubahan kode  
**Scope     :** Seluruh komponen Phase H (Ticketing) dievaluasi terhadap arsitektur integrasi SSO Shadow User  

---

## SECTION 1 — KOMPONEN YANG TETAP VALID

### 1.1 Schema Database

| Tabel | Status | Alasan |
|-------|--------|--------|
| `tickets` | ✅ Valid | `user_id` FK ke helpdesk `users.id` — shadow user sebagai pemilik tiket. Tidak perlu berubah. |
| `ticket_comments` | ✅ Valid | `user_id` FK ke helpdesk `users.id` — komentar terhubung ke shadow user atau staff. Tidak perlu berubah. |
| `ticket_status_logs` | ✅ Valid | `changed_by` nullable FK ke helpdesk `users.id`. Mendukung null (system actor). Tidak perlu berubah. |
| `ticket_attachments` | ✅ Valid | `uploaded_by` FK ke helpdesk `users.id`. Tidak perlu berubah. |
| `ticket_sequences` | ✅ Valid | Sequence generator tidak terkait identitas user. |

**Kesimpulan:** Semua 5 tabel Phase H tidak memerlukan perubahan apapun.

### 1.2 Model Layer

| Model | Status | Alasan |
|-------|--------|--------|
| `Ticket` | ✅ Valid | State machine, scope, relasi — semua berbasis `user_id` lokal. Tidak terdampak. |
| `TicketComment` | ✅ Valid | Relasi ke `User` lokal. `is_internal` flag tidak terkait identitas. |
| `TicketStatusLog` | ✅ Valid | `changed_by` nullable — sudah mendukung system actor (F-01 fix). |
| `TicketAttachment` | ✅ Valid | Tidak ada logika role/identitas di model. |
| `AuditLog` | ✅ Valid | `user_id` lokal; mendukung null. Mencatat `ip_address` dan `session_id`. |

### 1.3 Service Layer

| Service | Status | Alasan |
|---------|--------|--------|
| `TicketService` | ✅ Valid | `createTicket($data['user_id'])` — selalu dari auth lokal helpdesk. Shadow user punya `id` lokal. |
| `TicketWorkflowService` | ✅ Valid | `handleStatusChange(Ticket, User $actor)` — `$actor` adalah helpdesk `User` (shadow atau staff). |
| `TicketCommentService` | ✅ Valid | `addComment(Ticket, User $author)` — `$author` adalah helpdesk User. `isAsn()` berdasarkan `role`. |
| `TicketCommentService::getVisibleComments` | ✅ Valid | `$viewer->isAsn()` berdasarkan helpdesk User role. Shadow ASN punya `role='asn'`. |

### 1.4 Policy Layer

| Policy | Status | Alasan |
|--------|--------|--------|
| `TicketPolicy::view` | ✅ Valid | Cek `assigned_to === $user->id` dan `user_id === $user->id` — berbasis ID lokal. |
| `TicketPolicy::create` | ✅ Valid | Cek role lokal (`isAsn()`, dll). Shadow ASN punya role `asn`. |
| `TicketPolicy::delete` | ✅ Valid | Hanya `super_admin` — tidak terkait ASN eksternal. |
| `TicketPolicy::assign` | ✅ Valid | Hanya `super_admin`/`admin_helpdesk` — tidak terkait ASN eksternal. |
| `TicketPolicy::changeStatus` | ✅ Valid | Cek `assigned_to === $user->id` — berbasis ID lokal. |
| `TicketPolicy::close` | ✅ Valid | Hanya staff — tidak melibatkan ASN. |
| `TicketCommentPolicy::create` | ✅ Valid | Cek `user_id === $user->id` — berbasis ID lokal. |
| `TicketCommentPolicy::createInternal` | ✅ Valid | Berdasarkan role — shadow ASN dengan `role='asn'` ditolak. Benar. |

### 1.5 Controller Layer

| Controller | Status | Alasan |
|-----------|--------|--------|
| `Operator/TicketController` | ✅ Valid | Semua auth via `auth()->user()` → helpdesk User. |
| `Supervisor/TicketController` | ✅ Valid | Sama. |
| `Admin/TicketController` | ✅ Valid | Sama. `create()` form menampilkan ASN dari `User::where('role','asn')` — shadow ASN akan muncul. |

### 1.6 Audit Events

| Event | Status | Alasan |
|-------|--------|--------|
| `ticket_created` | ✅ Valid | `userId` = shadow user id lokal. Sudah ada `ticket_number` di log. |
| `ticket_updated` | ✅ Valid | Tidak terkait identitas eksternal. |
| `ticket_assigned` | ✅ Valid | `userId` = admin/staff yang assign. |
| `ticket_closed` | ✅ Valid | Null actor didukung (auto-close). |
| `ticket_commented` | ✅ Valid | `userId` = shadow user atau staff. |
| `ticket_status_changed` | ✅ Valid | Tidak terkait identitas eksternal. |

---

## SECTION 2 — KOMPONEN YANG HARUS DIREVISI

### 2.1 `Admin/TicketController::create()` — Form Dropdown ASN

**Lokasi:** `app/Http/Controllers/Admin/TicketController.php`, method `create()`

```php
$users = User::where('role', 'asn')->active()->orderBy('name')->get(['id', 'name']);
```

**Isu:** Saat ini, query ini mengembalikan ASN yang **sudah pernah login ke helpdesk** (shadow user). ASN yang belum pernah login tidak akan muncul di dropdown, meskipun mereka terdaftar di gaspul_api.

**Dampak:** Admin tidak bisa membuat tiket atas nama ASN yang belum pernah login ke helpdesk.

**Revisi yang Diperlukan:** Tambahkan endpoint gaspul_api `GET /api/admin/users?role=ASN` sebagai sumber data alternatif, atau pastikan semua ASN sudah di-seed ke shadow table sebelum fitur ini digunakan.

**Severity:** Medium — tidak memblokir penggunaan normal (ASN buat tiket sendiri), hanya berdampak pada alur admin buat tiket atas nama ASN.

### 2.2 `User` Model Helpdesk — Tidak Ada `nip` Field

**Lokasi:** `app/Models/User.php` (helpdesk)

`fillable` saat ini: `['name', 'email', 'password', 'role', 'status', 'last_login_at']`

Kolom `nip`, `jabatan`, `unit_name`, `gaspul_user_id` **belum ada** di schema dan model.

**Revisi yang Diperlukan:** Migration + update `$fillable` (Section 3).

### 2.3 `Admin/TicketController::show()` — Tampilan Info ASN

**Lokasi:** `app/Http/Controllers/Admin/TicketController.php`, method `show()`

```php
'user:id,name,email',
```

Saat ini hanya menampilkan name dan email. Setelah integrasi, pimpinan mungkin ingin melihat NIP dan unit kerja ASN pada detail tiket.

**Dampak:** Bukan breaking change — hanya enhancement untuk UX. Setelah shadow user punya `nip` dan `unit_name`, kolom ini bisa ditambahkan ke eager load.

**Severity:** Low — tidak memblokir fungsionalitas.

---

## SECTION 3 — MIGRATION TAMBAHAN YANG DIPERLUKAN

### Di `esaraku_helpdesk` (1 migration wajib)

```php
// File: 2026_XX_XX_add_gaspul_fields_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->unsignedBigInteger('gaspul_user_id')
          ->nullable()
          ->unique()
          ->after('id');

    $table->string('nip', 18)
          ->nullable()
          ->unique()
          ->after('gaspul_user_id');

    $table->string('jabatan')
          ->nullable()
          ->after('last_login_at');

    $table->string('unit_name')
          ->nullable()
          ->after('jabatan');

    $table->index('gaspul_user_id');
    $table->index('nip');
});
```

**Tidak ada migration baru di gaspul_api** — semua field sudah tersedia.

---

## SECTION 4 — BREAKING CHANGES

**Tidak ada breaking change** terhadap komponen Phase H yang sudah ada.

Seluruh Phase H (schema, model, service, policy, controller, audit) berfungsi penuh tanpa modifikasi karena:

1. Shadow user memiliki **ID lokal helpdesk** yang valid — semua FK terpenuhi
2. Shadow user memiliki **role `asn`** — semua policy check benar
3. State machine, workflow, dan audit log tidak bergantung pada asal-usul user (lokal vs SSO)

Satu-satunya risiko operasional (bukan breaking change):
- Admin helpdesk membuat tiket atas nama ASN yang belum pernah login (shadow belum ada) → dropdown kosong untuk ASN tersebut. Ini edge case yang bisa diatasi dengan onboarding.

---

## SECTION 5 — REKOMENDASI IMPLEMENTASI

### Urutan Prioritas

**Langkah 1 — Wajib sebelum integrasi (helpdesk)**
Jalankan migration tambah `gaspul_user_id`, `nip`, `jabatan`, `unit_name` ke `users` helpdesk.

**Langkah 2 — Core SSO implementation (helpdesk)**
Buat `GaspulAuthService` di helpdesk:
```php
class GaspulAuthService {
    public function loginViaSso(string $email, string $password): User
    {
        // 1. POST ke /api/login gaspul
        // 2. GET /api/me untuk profil lengkap
        // 3. firstOrCreate shadow user berdasarkan gaspul_user_id
        // 4. Update nama/jabatan/unit saat login
        // 5. Return shadow User
    }
}
```

**Langkah 3 — Modifikasi `LoginController` helpdesk**
Deteksi apakah credentials adalah ASN (bisa dicek dari email domain atau coba login gaspul dulu). Jika berhasil → SSO flow. Jika gagal → coba login lokal (untuk staff helpdesk non-ASN).

**Langkah 4 — Seed ASN populer (opsional)**
Untuk menghindari empty dropdown di admin buat tiket, lakukan one-time import shadow ASN dari `GET /api/admin/users?role=ASN` ke helpdesk `users`.

**Langkah 5 — Hardening (setelah SSO stabil)**
- Set `sanctum.expiration = 1440`
- Shadow user `password` = random hash (tidak bisa login langsung)
- Tambah kolom NIP + unit ke tampilan detail tiket admin

---

## FINAL VERDICT

### **READY WITH MINOR REVISIONS**

Phase H Ticketing System **tidak memerlukan refactor**. Arsitektur sudah kompatibel sepenuhnya dengan model Shadow User SSO karena seluruh logika berbasis ID + role lokal helpdesk.

**Yang harus dilakukan SEBELUM integrasi masuk produksi:**

| No. | Item | File/Komponen | Urgency |
|-----|------|---------------|---------|
| R-01 | Migration: tambah `gaspul_user_id`, `nip`, `jabatan`, `unit_name` ke helpdesk `users` | helpdesk migration | **WAJIB** |
| R-02 | Buat `GaspulAuthService` di helpdesk | helpdesk service baru | **WAJIB** |
| R-03 | Modifikasi `LoginController` helpdesk untuk SSO flow | helpdesk controller | **WAJIB** |
| R-04 | Update helpdesk `User::$fillable` dengan field baru | helpdesk model | **WAJIB** |
| R-05 | Set `sanctum.expiration = 1440` di gaspul | gaspul config | Disarankan |
| R-06 | Tambah NIP/unit_name ke tampilan detail tiket admin | helpdesk view | Opsional |
| R-07 | Seed shadow ASN dari gaspul API | one-time script | Opsional |

**Tidak ada satupun file Phase H existing yang perlu diubah.**
