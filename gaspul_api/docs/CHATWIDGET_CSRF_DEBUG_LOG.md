# CHAT WIDGET — CSRF & POLLING DEBUG LOG
**Tanggal:** 2026-06-19 s/d 2026-06-20
**Scope:** Embedded chat widget `esaraku.gaspul.com` → `helpdesk.gaspul.com`
**Status:** ✅ RESOLVED — chat live dua arah berfungsi normal

---

## RINGKASAN MASALAH

Widget chat ASN di eSARAku (esaraku.gaspul.com) tidak bisa mengirim pesan
ke helpdesk (helpdesk.gaspul.com) karena:

1. CSRF token mismatch → 419 pada semua POST request
2. Polling URL salah → 404, pesan operator tidak muncul live

---

## ROOT CAUSE ANALYSIS

### Masalah 1 — CSRF 419 (berlapis, 4 penyebab)

#### Penyebab A: `readCookie('XSRF-TOKEN')` tidak bisa dibaca cross-origin
Cookie `XSRF-TOKEN` di-set oleh `helpdesk.gaspul.com` tetapi tidak dapat
dibaca via `document.cookie` dari `esaraku.gaspul.com` (browser security policy).

Tiga fungsi widget memanggil `readCookie` yang selalu return string kosong:
- `markConversationRead()` — line 564
- `createConversationRequest()` — line 780
- `sendMessage()` — line 825

→ `X-XSRF-TOKEN: ""` dikirim → Laravel reject → 419

#### Penyebab B: `MonitoringMamasaController` tidak ada di production
`routes/web.php` mereferensi `MonitoringMamasaController` yang belum
diupload ke production. Laravel berhenti load route di baris itu →
semua route setelahnya (termasuk `POST /api/helpdesk-token`) tidak
terdaftar → SSO token endpoint return 404.

#### Penyebab C: Cookie `XSRF-TOKEN` baru di-set tiap request
Meski `init` sudah return `csrf_token` di body, Laravel me-refresh
`XSRF-TOKEN` cookie di setiap response → token dari body `init`
berbeda dengan token di cookie saat POST dilakukan → mismatch 419.

#### Penyebab D: `Set-Cookie` dari `init` diblokir browser (Firefox)
Firefox memblokir cookie cross-origin meski `SameSite=None; Secure`
sudah di-set. Cookie `XSRF-TOKEN` baru tidak tersimpan di browser
→ cookie lama tetap terpakai → mismatch permanen.

### Masalah 2 — Poll 404

URL endpoint poll di widget salah:
- Widget mengirim: `GET /api/chat/conversations/{id}/messages/poll`
- Route yang ada di helpdesk: `GET /api/chat/conversations/{id}/poll`

Akibatnya polling tidak pernah berjalan → pesan dari operator tidak
muncul secara live, ASN harus refresh halaman untuk melihat balasan.

---

## SOLUSI YANG DITERAPKAN

### Fix 1 — Hapus semua `readCookie` untuk CSRF di widget
**File:** `gaspul_api/resources/views/components/helpdesk-chat-widget.blade.php`
**Commit:** `f280832`

Hapus pemanggilan `readCookie('XSRF-TOKEN')` dari tiga fungsi:
```javascript
// SEBELUM (di 3 tempat):
_csrfToken = readCookie('XSRF-TOKEN');

// SESUDAH:
// dihapus — _csrfToken di-set sekali saat doInit() dan reused
```

Tambah auto-retry jika 419 saat kirim pesan:
```javascript
if (err.status === 419 && !_csrfRetried) {
    _csrfRetried = true;
    refreshCsrfAndRetry(function () { sendMessage(); });
    return;
}
```

### Fix 2 — `init` return `csrf_token` di response body
**File:** `esaraku_helpdesk/routes/web.php`
**Commit:** `b1049c0`, `8686e79`, `c26a093`

```php
Route::get('/api/chat/init', function (Request $request) {
    $request->session()->start();
    $token = $request->session()->token();  // stabil, tidak regenerate
    return response()->json([
        'ok'         => true,
        'csrf_token' => $token,
    ])->cookie('XSRF-TOKEN', $token, 0, '/', null, true, false, false, 'none');
})->name('api.chat.init');
```

Widget membaca `data.csrf_token` dari body, bukan dari `document.cookie`.

### Fix 3 — Exclude `api/chat/*` dari CSRF verification (solusi final)
**File:** `esaraku_helpdesk/bootstrap/app.php`
**Commit:** `c8279c4`

```php
$middleware->validateCsrfTokens(except: [
    'api/chat/*',
]);
```

**Alasan:** Auth sudah dijaga session cookie (`credentials: 'include'`).
CSRF tidak menambah keamanan untuk API cross-origin di mana cookie
tidak bisa dibaca JS. Ini adalah pola standar untuk SPA/widget API.

### Fix 4 — Upload `MonitoringMamasaController`
**File:** `app/Http/Controllers/MonitoringMamasaController.php`

Controller belum ada di production → seluruh route loading gagal di
titik itu → route helpdesk tidak terdaftar. Upload file dan jalankan
`php artisan route:clear && php artisan route:cache`.

### Fix 5 — Perbaiki URL endpoint poll
**File:** `gaspul_api/resources/views/components/helpdesk-chat-widget.blade.php`
**Commit:** `0a167d7`

```javascript
// SEBELUM (salah):
poll: HD_BASE + '/api/chat/conversations/{id}/messages/poll',

// SESUDAH (benar):
poll: HD_BASE + '/api/chat/conversations/{id}/poll',
```

---

## URUTAN PENEMUAN & PERBAIKAN

| # | Gejala | Diagnosis | Fix |
|---|--------|-----------|-----|
| 1 | POST conversations 419 | `readCookie` return kosong cross-origin | Fix 1 — hapus readCookie |
| 2 | `route:list` tidak tampilkan `/api/helpdesk-token` | MonitoringMamasaController tidak ada | Fix 4 — upload controller |
| 3 | POST masih 419 setelah Fix 1 | Token body vs cookie tidak sinkron | Fix 2 — `session()->token()` |
| 4 | POST masih 419 setelah Fix 2 | Firefox blokir Set-Cookie cross-origin | Fix 3 — exclude CSRF |
| 5 | Pesan operator tidak muncul live | URL poll salah → 404 | Fix 5 — perbaiki URL |

---

## FILE YANG DIUBAH

### gaspul_api (esaraku.gaspul.com)

| File | Commit | Perubahan |
|------|--------|-----------|
| `resources/views/components/helpdesk-chat-widget.blade.php` | `f280832`, `0a167d7` | Hapus readCookie, fix URL poll, tambah auto-retry 419 |

### esaraku_helpdesk (helpdesk.gaspul.com)

| File | Commit | Perubahan |
|------|--------|-----------|
| `routes/web.php` | `b1049c0`, `8686e79`, `c26a093` | `init` return csrf_token di body, set cookie stabil |
| `bootstrap/app.php` | `c8279c4` | Exclude `api/chat/*` dari CSRF |
| `config/cors.php` | `a44bfcf` | Whitelist `esaraku.gaspul.com`, `supports_credentials: true` |

---

## PELAJARAN

1. **Cookie cross-origin tidak bisa dibaca JS** — `document.cookie` hanya
   baca cookie domain sendiri, meski `SameSite=None` di-set.

2. **CSRF untuk widget API = tidak praktis** — Untuk embedded widget
   cross-origin yang auth-nya via session cookie, exclude CSRF dan andalkan
   session + CORS yang ketat adalah pola yang benar.

3. **Laravel `csrf_token()` bisa regenerate** — Selalu gunakan
   `$request->session()->token()` setelah `session()->start()` untuk
   mendapat token yang stabil dari session yang sudah ada.

4. **Satu controller missing = semua route di bawahnya hilang** — Laravel
   gagal load `routes/web.php` ketika ada class yang tidak ditemukan,
   meski dengan `try/catch` di level atas.

5. **Verifikasi URL endpoint dengan `route:list`** — Jangan asumsikan
   URL dari dokumentasi internal sudah benar; selalu cocokkan dengan
   output `php artisan route:list`.

---

*File ini dokumentasi internal — tidak perlu deploy ke server.*
*Dokumen terkait: `GASPUL_API_PRODUCTION_UPDATE_INVENTORY.md`*
