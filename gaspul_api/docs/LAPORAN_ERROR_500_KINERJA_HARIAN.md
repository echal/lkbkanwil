# LAPORAN INSIDEN TEKNIS
## Error 500 — Gagal Simpan Kinerja Harian (KH & TLA)
### Aplikasi e-SARAku | Kantor Wilayah Kementerian Agama Sulawesi Barat

---

| | |
|---|---|
| **Tanggal Insiden** | 8 Juni 2026 |
| **Waktu Terdeteksi** | 09:32 WIB |
| **Waktu Resolved** | 8 Juni 2026 (hari yang sama) |
| **Tingkat Keparahan** | Kritikal — seluruh ASN tidak dapat menyimpan data kinerja harian |
| **Dilaporkan oleh** | Pranata Komputer Kanwil Kemenag Sulbar |
| **Status** | ✅ RESOLVED |

---

## 1. Ringkasan Eksekutif

Pada tanggal 8 Juni 2026 pukul 09:32 WIB, sistem e-SARAku mengalami gangguan kritikal di mana seluruh ASN pengguna aplikasi tidak dapat menyimpan data Kinerja Harian (KH) maupun Tugas Langsung Atasan (TLA). Setiap percobaan penyimpanan menghasilkan halaman **Error 500 (Internal Server Error)**. Gangguan ini berdampak pada seluruh pengguna ASN yang sedang aktif menggunakan sistem pada hari tersebut.

Penyebab ditemukan, diperbaiki, dan sistem kembali normal pada hari yang sama.

---

## 2. Kronologi Kejadian

| Waktu | Kejadian |
|---|---|
| 09:32:33 WIB | Error 500 pertama tercatat di log server (userId: 498) |
| 09:32:34 WIB | Error berulang dari ASN lain (userId: 4217) |
| 09:32:40 WIB | Error terjadi pada alur TLA (userId: 3226) |
| (siang hari) | Laporan diterima dari pengguna — dilakukan investigasi log |
| (siang hari) | Root cause ditemukan: file `EvidenHelper.php` tidak ada di server |
| (siang hari) | File di-upload ke server production |
| (siang hari) | Cache aplikasi dibersihkan (`php artisan optimize:clear`) |
| (siang hari) | ✅ Sistem kembali normal — ASN dapat menyimpan data |

---

## 3. Penyebab Teknis (Root Cause)

### 3.1 Penjelasan Umum

Kesalahan terjadi karena sebuah file kode program baru bernama `EvidenHelper.php` yang dibutuhkan oleh sistem **tidak ikut ter-upload ke server production** pada saat pembaruan aplikasi sebelumnya.

Ibarat sebuah buku yang merujuk ke lampiran, namun lampirannya tidak tersedia — sistem langsung gagal saat mencoba membuka lampiran tersebut.

### 3.2 Detail Teknis

**File yang bermasalah:** `app/Helpers/EvidenHelper.php`

File ini adalah sebuah *helper class* yang bertugas memvalidasi bahwa link bukti/eviden yang diinput ASN menggunakan layanan Google yang sah (Google Drive, Google Docs, dll.). File ini dibuat sebagai fitur keamanan agar ASN tidak memasukkan link sembarang sebagai bukti kinerja.

**Pesan error di server log:**
```
[2026-06-08 09:32:33] production.ERROR: Class "App\Helpers\EvidenHelper" not found
at /home/gaspulco/lkbkanwil.gaspul.com/gaspul_api/app/Http/Controllers/Asn/HarianController.php:350
```

**Di mana kode memanggil file tersebut:**

File utama pengendali form simpan kinerja (`HarianController.php`) memanggil `EvidenHelper` di baris 350 (untuk simpan KH) dan baris 489 (untuk simpan TLA):

```php
// HarianController.php — baris 349–354 (alur simpan KH)
// VALIDASI: Domain link eviden harus Google (tanpa HTTP request)
if (!EvidenHelper::isValid($validated['link_bukti'] ?? null)) {
    return redirect()->back()
        ->withInput()
        ->with('error', EvidenHelper::ERROR_DOMAIN);
}
```

```php
// HarianController.php — baris 488–493 (alur simpan TLA)
// VALIDASI: Domain link eviden harus Google (tanpa HTTP request)
if (!EvidenHelper::isValid($validated['link_bukti'] ?? null)) {
    return redirect()->back()
        ->withInput()
        ->with('error', EvidenHelper::ERROR_DOMAIN);
}
```

Karena `EvidenHelper.php` tidak ada di server, PHP langsung melempar error sebelum sempat menjalankan perintah simpan ke database — akibatnya seluruh proses batal dan pengguna melihat halaman Error 500.

**Isi file `EvidenHelper.php` yang dimaksud:**

```php
<?php
// File: app/Helpers/EvidenHelper.php
// Fungsi: Validasi bahwa link eviden ASN menggunakan domain Google resmi

namespace App\Helpers;

class EvidenHelper
{
    // Daftar domain Google yang diizinkan
    private const ALLOWED_DOMAINS = [
        'drive.google.com',
        'docs.google.com',
        'sheets.google.com',
        'forms.google.com',
        'slides.google.com',
    ];

    // Cek apakah link valid (kosong = boleh, terisi = harus domain Google)
    public static function isValid(?string $url): bool
    {
        if (empty($url)) {
            return true; // link eviden bersifat opsional
        }
        return self::isAllowedDomain($url);
    }

    // ...
}
```

### 3.3 Mengapa Bisa Terjadi

Proses deployment (upload kode ke server) dilakukan secara manual file per file. Pada pembaruan sebelumnya, file `HarianController.php` — yang sudah menggunakan `EvidenHelper` — berhasil di-upload, namun file `EvidenHelper.php` sendiri terlewat tidak ikut di-upload. Sistem berjalan normal selama tidak ada proses yang memanggil bagian kode tersebut, namun begitu ASN mencoba menyimpan data kinerja, sistem langsung gagal.

---

## 4. Dampak

| Aspek | Detail |
|---|---|
| **Fitur terdampak** | Simpan Kinerja Harian (KH) dan Tugas Langsung Atasan (TLA) |
| **Pengguna terdampak** | Seluruh ASN pengguna e-SARAku |
| **Data terdampak** | Tidak ada — tidak ada data yang rusak atau hilang. Kegagalan terjadi sebelum proses simpan ke database |
| **Fitur lain** | Tidak terdampak (login, laporan bulanan, monitoring atasan tetap normal) |
| **Durasi gangguan** | Mulai 09:32 WIB hingga diperbaiki pada hari yang sama |

---

## 5. Tindakan Perbaikan

### Yang sudah dilakukan:
1. **Upload file `EvidenHelper.php`** ke server production di path:
   ```
   /home/gaspulco/lkbkanwil.gaspul.com/gaspul_api/app/Helpers/EvidenHelper.php
   ```

2. **Membersihkan cache aplikasi** via terminal server:
   ```bash
   php artisan optimize:clear
   ```

3. **Verifikasi** — ASN kembali dapat menyimpan data KH dan TLA dengan normal.

---

## 6. Rekomendasi Pencegahan

| No | Rekomendasi | Prioritas |
|---|---|---|
| 1 | **Buat checklist deployment** — setiap upload controller baru, periksa apakah ada `use App\Helpers\...` atau `use App\Services\...` yang juga perlu di-upload | Tinggi |
| 2 | **Gunakan deployment via Git** — dengan `git pull` di server, semua file yang berubah akan otomatis ikut terupload tanpa ada yang terlewat | Tinggi |
| 3 | **Lakukan smoke test setelah deployment** — setelah setiap upload, coba simpan satu data KH/TLA sebagai verifikasi | Sedang |
| 4 | **Pantau log error secara berkala** — periksa `laravel.log` minimal satu kali per hari, terutama setelah ada pembaruan | Sedang |

---

## 7. Penutup

Insiden ini tidak menyebabkan kehilangan atau kerusakan data. Semua data ASN yang gagal disimpan saat terjadi error perlu diinput ulang oleh masing-masing ASN untuk tanggal yang bersangkutan (8 Juni 2026). Data yang tersimpan sebelum insiden tidak terpengaruh.

Sistem e-SARAku saat ini telah kembali berfungsi normal sepenuhnya.

---

*Laporan dibuat oleh: Pranata Komputer Kantor Wilayah Kementerian Agama Sulawesi Barat*
*Tanggal laporan: 8 Juni 2026*
