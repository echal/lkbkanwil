# CHANGELOG IMPLEMENTASI ESARAKU

Dokumen ini adalah referensi utama seluruh perubahan sistem yang telah selesai
diimplementasikan dan diupload ke production pada proyek **ESARAKU** (e-Kinerja ASN).

- **Aplikasi:** ESARAKU
- **Stack:** Laravel 10 + Tailwind CSS + Alpine.js
- **Database:** MySQL (`gaspulco_lkbkanwil_db`)
- **Server:** cPanel / shared hosting
- **Lokal:** XAMPP `c:\xampp\htdocs\gaspul\gaspul_api`

---

## Daftar Isi

1. [Kalender Libur Khusus Guru — Stage 1](#1-kalender-libur-khusus-guru--stage-1)
2. [Blok Input KH/TLA Saat Libur Khusus — Stage 1.1](#2-blok-input-khtla-saat-libur-khusus--stage-11)
3. [Audit Target Jam Bulanan](#3-audit-target-jam-bulanan)
4. [Patch A — Perbaikan Legacy Bug Target Jam SENIN_SABTU](#4-patch-a--perbaikan-legacy-bug-target-jam-senin_sabtu)
5. [Patch B — Target Jam Berkurang Saat Libur Khusus](#5-patch-b--target-jam-berkurang-saat-libur-khusus)
6. [Status Eviden pada Rekap Kerja Harian ASN](#6-status-eviden-pada-rekap-kerja-harian-asn)
7. [Ringkasan Validasi Eviden ASN](#7-ringkasan-validasi-eviden-asn)
8. [Penyempurnaan UI Validasi Eviden](#8-penyempurnaan-ui-validasi-eviden)
- [Daftar Keputusan Arsitektur](#daftar-keputusan-arsitektur)
- [Daftar File Terdampak Kumulatif](#daftar-file-terdampak-kumulatif)

---

## 1. Kalender Libur Khusus Guru — Stage 1

**Tanggal implementasi:** 2026-06-06
**Status:** PRODUCTION

### Tujuan

Memberikan mekanisme kalender libur khusus berbasis jabatan untuk Guru di Madrasah,
tanpa mengganggu ASN lain (non-guru) dan tanpa mengubah hari libur nasional.

Sebelum fitur ini, tidak ada cara bagi admin untuk mendefinisikan periode libur
yang hanya berlaku untuk Guru (misalnya: libur semester, libur penerimaan siswa baru).
Guru tetap masuk dalam daftar "belum isi" di monitoring Kakanwil meskipun mereka sedang libur.

### Komponen yang Dibangun

| Komponen | Keterangan |
|---|---|
| Tabel `kalender_libur_khusus` | Menyimpan periode libur per unit kerja per jabatan |
| Model `KalenderLiburKhusus` | Status `DRAFT`/`AKTIF`, target `GURU`/`PENYULUH`/`PENGHULU` |
| `LiburKhususService` | Identifikasi Guru, cascade `berlaku_ke_anak` 2 level, cache 10 menit |
| Admin CRUD | Tambah, edit, hapus, toggle DRAFT ↔ AKTIF |
| Route explicit | 7 route manual (bukan `Route::resource`) karena bug truncation nama parameter |

### Hasil

- Guru dalam periode libur khusus AKTIF tidak masuk daftar "belum isi" di monitoring Kakanwil dan Bimas Islam.
- Cache per unit+bulan otomatis dihapus saat admin simpan/ubah/aktifkan entri.
- `berlaku_ke_anak = true` membuat satu entri di induk berlaku ke seluruh madrasah di bawahnya (cascade 2 level).

### File yang Berubah

```
database/migrations/2026_06_06_000001_create_kalender_libur_khusus_table.php  (BARU)
app/Models/KalenderLiburKhusus.php                                             (BARU)
app/Services/LiburKhususService.php                                            (BARU)
app/Http/Controllers/Admin/KalenderLiburKhususController.php                   (BARU)
app/Http/Controllers/MonitoringKakanwilController.php                          (DIMODIFIKASI)
app/Http/Controllers/MonitoringBimasIslamController.php                        (BARU)
resources/views/admin/kalender-libur-khusus/index.blade.php                    (BARU)
resources/views/admin/kalender-libur-khusus/tambah.blade.php                   (BARU)
resources/views/admin/kalender-libur-khusus/edit.blade.php                     (BARU)
resources/views/components/sidebar.blade.php                                   (DIMODIFIKASI)
routes/web.php                                                                 (DIMODIFIKASI)
```

### Catatan Teknis

- Route `Route::resource('kalender-libur-khusus', ...)` menghasilkan parameter `{kalender_libur_khusu}` (truncated oleh Laravel). Solusi: 7 route explicit dengan parameter `{kalender}`.
- Identifikasi Guru: `role = 'ASN' AND jabatan LIKE '%guru%'` (case-insensitive). Kepala Madrasah (role ATASAN) tidak termasuk.
- Stage 2 (PENYULUH, PENGHULU) sudah disiapkan di service tapi belum diaktifkan.

### Risiko Regression

Nihil terhadap fitur existing. LiburKhususService hanya membaca data, tidak menulis.

---

## 2. Blok Input KH/TLA Saat Libur Khusus — Stage 1.1

**Tanggal implementasi:** 2026-06-06
**Status:** PRODUCTION

### Tujuan

Mencegah Guru mengisi Kinerja Harian (KH) atau Tugas dari Luar (TLA) pada hari
yang masuk dalam periode Libur Khusus AKTIF. Tanpa blokir ini, Guru bisa tetap
mengisi kinerja di hari libur khusus, yang mengacaukan data monitoring.

### Implementasi

6 gate `isLiburKhusus()` ditambahkan di `HarianController`, masing-masing untuk:

| Method | Gate |
|---|---|
| `formKinerja()` | Redirect dengan pesan jika libur khusus |
| `formTla()` | Redirect dengan pesan jika libur khusus |
| `storeKinerja()` | Abort 403 / redirect jika libur khusus |
| `storeTla()` | Abort 403 / redirect jika libur khusus |
| `update()` | Abort 403 / redirect jika libur khusus |
| `updateTla()` | Abort 403 / redirect jika libur khusus |

**Urutan validasi di setiap method:**

```
canInputData()     → cek laporan sudah DISETUJUI/DIKIRIM
→ isLiburKhusus()  → cek periode libur khusus (BARU)
→ isSedangCuti()   → cek cuti ASN
→ domain logic
```

### File yang Berubah

```
app/Http/Controllers/Asn/HarianController.php  (DIMODIFIKASI — 6 gate baru)
```

### Risiko Regression

Nihil. Gate baru diletakkan setelah `canInputData()` sehingga laporan yang sudah
DISETUJUI/DIKIRIM tetap terproteksi oleh guard yang lebih awal.

---

## 3. Audit Target Jam Bulanan

**Tanggal audit:** 2026-06-06

### Root Cause

`WorkingTimeService::getTargetJamBulananUser()` menggunakan rumus:

```php
// SALAH untuk SENIN_SABTU
$hariKerja × 7.5
```

Padahal pola `SENIN_SABTU` memiliki jam kerja berbeda per hari:
- Senin–Kamis: 390 menit (6,5 jam)
- Jumat: 270 menit (4,5 jam)
- Sabtu: 420 menit (7 jam)

Total per minggu SENIN_SABTU = 37,5 jam = sama dengan SENIN_JUMAT, tetapi
distribusi per hari berbeda. Rumus `hari × 7.5` salah karena mengasumsikan
setiap hari kerja = 7,5 jam.

### Temuan Forensik Production

- **840 laporan** terdampak legacy bug ini.
- **742 DISETUJUI** — tidak dapat dikoreksi (protected by guard), dibiarkan as-is.
- **90 DIKIRIM** — tidak dikoreksi (protected by guard).
- **7 DRAFT** — akan terkoreksi otomatis saat generate berikutnya.
- **1 DITOLAK** — akan terkoreksi otomatis.

### Keputusan

Laporan DISETUJUI dan DIKIRIM **tidak dikoreksi** (prinsip: tidak mengubah data historis
yang sudah disetujui). Hanya DRAFT dan DITOLAK yang akan otomatis terkoreksi.

---

## 4. Patch A — Perbaikan Legacy Bug Target Jam SENIN_SABTU

**Tanggal implementasi:** 2026-06-06
**Status:** PRODUCTION

### Tujuan

Memperbaiki `getTargetJamBulananUser()` agar konsisten dengan `getTargetMenitBulanan()`
sebagai single source of truth. Mencegah inkonsistensi antara `target_jam` dan
`target_menit_bulanan_snapshot / 60` di laporan bulanan.

### Perubahan

**File:** `app/Services/WorkingTimeService.php`
**Method:** `getTargetJamBulananUser()`

```php
// SEBELUM (salah untuk SENIN_SABTU)
$hariKerja = HolidayHelper::countWorkingDaysInMonth($bulan, $tahun);
return $hariKerja * self::JAM_PER_HARI;

// SESUDAH (benar — delegasi ke source of truth)
return round(self::getTargetMenitBulanan($bulan, $tahun, $user) / 60, 2);
```

**Guard legacy:** Periode sebelum 2026 tetap mengembalikan 165 jam (backward compatible).

### Hasil Validasi

| Pola Kerja | Sebelum | Sesudah |
|---|---|---|
| SENIN_JUMAT | Benar | Benar (tidak berubah) |
| SENIN_SABTU | Salah | Benar |
| Pre-2026 | 165 jam | 165 jam (tidak berubah) |

### File yang Berubah

```
app/Services/WorkingTimeService.php  (DIMODIFIKASI — 1 method)
```

### Risiko Regression

Nihil untuk SENIN_JUMAT dan pre-2026. SENIN_SABTU terkoreksi ke nilai yang lebih akurat.

---

## 5. Patch B — Target Jam Berkurang Saat Libur Khusus

**Tanggal implementasi:** 2026-06-06
**Status:** PRODUCTION

### Tujuan

Menyinkronkan efek Kalender Libur Khusus (Stage 1) ke perhitungan target jam
bulanan Guru. Tanpa Patch B, target_jam tetap penuh meskipun Guru sedang dalam
periode libur khusus, sehingga capaian persen Guru menjadi tidak adil.

### Perubahan

**File 1:** `app/Services/LiburKhususService.php`
Method baru: `countMenitLiburKhususBulanan(User $user, int $bulan, int $tahun): int`

```php
// Hitung total menit hari kerja yang jatuh dalam periode Libur Khusus
// Reuse getTanggalLiburGuruBulanan() + filter via getTargetMenitByDate()
// Minggu otomatis 0 menit, Sabtu SENIN_SABTU = 420 menit (baca config aktual)
```

**File 2:** `app/Services/LaporanBulananService.php`
Method `generateBulanan()` — tambahan 4 baris setelah `$targetMenitSnapshot`:

```php
$menitLiburKhusus = $this->liburKhusus->countMenitLiburKhususBulanan($user, $bulan, $tahun);
if ($menitLiburKhusus > 0) {
    $targetMenitSnapshot = max(0, $targetMenitSnapshot - $menitLiburKhusus);
}
```

### Guard Tidak Berubah

Laporan berstatus `DISETUJUI` dan `DIKIRIM` **tidak di-recalculate** — guard existing
di `generateBulanan()` tetap berlaku dan tidak diubah.

### File yang Berubah

```
app/Services/LiburKhususService.php     (DIMODIFIKASI — tambah 1 method)
app/Services/LaporanBulananService.php  (DIMODIFIKASI — 4 baris di generateBulanan)
```

### Risiko Regression

- ASN non-Guru: zero impact (`countMenitLiburKhususBulanan` return 0 langsung).
- Guru tanpa Libur Khusus aktif: zero impact (`menitLiburKhusus = 0`).
- `max(0, ...)` mencegah target negatif jika libur khusus lebih panjang dari bulan kerja.

---

## 6. Status Eviden pada Rekap Kerja Harian ASN

**Tanggal implementasi:** 2026-06-07
**Status:** PRODUCTION

### Tujuan

Menampilkan hasil verifikasi eviden oleh atasan pada tabel Rekap Kerja Harian
di halaman Laporan Bulanan ASN. Sebelumnya ASN tidak bisa melihat apakah
eviden KH/TLA mereka sudah diverifikasi dan hasilnya apa.

### Latar Belakang

Field verifikasi (`verifikasi_eviden`, `catatan_verifikasi`, `verified_by`, `verified_at`)
sudah ada di tabel `progres_harian` sejak migration `2026_05_31`. Verifikasi sudah
berjalan dari sisi ATASAN via `HarianBawahanController`. Yang belum ada hanya
tampilan pada sisi ASN.

### Perubahan

**File 1:** `app/Http/Controllers/Asn/BulananController.php`
Method `buildRekapKerjaHarianDetail()` — tambah 4 key ke array output:

```php
'verifikasi_eviden'  => $progres->verifikasi_eviden,
'catatan_verifikasi' => $progres->catatan_verifikasi,
'verified_by_id'     => $progres->verified_by,
'verified_at'        => $progres->verified_at,
```

**File 2:** `resources/views/asn/bulanan/partials/rekap-detail-harian.blade.php`
Tambah kolom **Status Eviden** di antara kolom Jenis dan Aksi.

### Aturan Tampilan

| Status | Badge | Warna | Catatan |
|---|---|---|---|
| `NULL` | Belum Diverifikasi | Abu-abu | Tidak tampil |
| `SESUAI` | ✓ Sesuai | Hijau | Tidak tampil |
| `KURANG` | ⚠ Kurang | Kuning | Tampil jika ada |
| `TIDAK_SESUAI` | ✗ Tidak Sesuai | Merah | Tampil jika ada |

### Data Production Saat Implementasi

| Status | Jumlah Record |
|---|---|
| Belum diverifikasi | 262.933 |
| SESUAI | 3.520 |
| KURANG | 562 |
| TIDAK_SESUAI | 144 |

### File yang Berubah

```
app/Http/Controllers/Asn/BulananController.php                            (DIMODIFIKASI)
resources/views/asn/bulanan/partials/rekap-detail-harian.blade.php         (DIMODIFIKASI)
```

### Query Tambahan

Nihil. Query `->get()` di baris 99–105 sudah `SELECT *` — `verifikasi_eviden`
sudah ada di collection sebelum perubahan ini.

### Risiko Regression

Nihil. Perubahan hanya menambah key ke array PHP dan kolom di blade. Tidak
menyentuh kalkulasi, service, atau flow approval.

---

## 7. Ringkasan Validasi Eviden ASN

**Tanggal implementasi:** 2026-06-07
**Status:** PRODUCTION

### Tujuan

Memberikan informasi ringkasan validasi eviden pada halaman Laporan Bulanan ASN,
tepat di bawah 4 card ringkasan existing (Total Hari Kerja, Total Jam Kerja,
Capaian Jam, Status Capaian). Informasi ini **hanya monitoring** dan tidak
mempengaruhi capaian resmi.

### Informasi yang Ditampilkan

```
Ringkasan Validasi Eviden

✓  Jam Kerja Valid     : X jam Y menit
✗  Jam Tidak Sesuai   : X jam Y menit
📊 Persentase Validasi : X%
```

### Perubahan

**File 1:** `app/Http/Controllers/Asn/BulananController.php`
Tambah kalkulasi di method `index()` setelah `$sisaMenit` (baris ~116):

```php
// Zero query addition — semua dari $progresHarianList yang sudah di-load
$menitTidakSesuai    = $progresHarianList->where('verifikasi_eviden', 'TIDAK_SESUAI')->sum('durasi_menit');
$menitValid          = $totalDurasiMenit - $menitTidakSesuai;
$jamValid            = floor($menitValid / 60);
$sisaMenutValid      = $menitValid % 60;
$jamTidakSesuai      = floor($menitTidakSesuai / 60);
$sisaMenitTidakSesuai = $menitTidakSesuai % 60;
$persentaseValidasi  = $totalDurasiMenit > 0
    ? round($menitValid / $totalDurasiMenit * 100, 1)
    : 100;
```

6 variabel baru dikirim ke view: `jamValid`, `sisaMenutValid`, `jamTidakSesuai`,
`sisaMenitTidakSesuai`, `persentaseValidasi`, `menitTidakSesuai`.

**File 2:** `resources/views/asn/bulanan/partials/ringkasan.blade.php`
Tambah section baru di bawah grid 4 card, dipisah border tipis.

### File yang Berubah

```
app/Http/Controllers/Asn/BulananController.php               (DIMODIFIKASI)
resources/views/asn/bulanan/partials/ringkasan.blade.php      (DIMODIFIKASI)
```

### Query Tambahan

Nihil. `$progresHarianList->where()->sum()` adalah operasi PHP Collection
di memory, bukan query database.

### Yang Tidak Berubah

- `$persentaseJamKerja` (capaian resmi) — tidak diubah
- `$targetJamKerjaBulanan` — tidak diubah
- `generateBulanan()` — tidak diubah
- Monitoring Kakanwil, Bimas Islam, Atasan — tidak diubah
- PDF bulanan — tidak diubah
- Flow approval — tidak diubah

### Risiko Regression

Nihil. Semua kalkulasi baru bersifat read-only dari collection yang sudah ada.

---

## 8. Penyempurnaan UI Validasi Eviden

**Tanggal implementasi:** 2026-06-07
**Status:** PRODUCTION

### Tujuan

Menambahkan kalimat ringkasan yang mudah dipahami ASN di bawah section
Ringkasan Validasi Eviden, sehingga ASN tidak perlu menghitung sendiri
hubungan antara total jam kerja, jam valid, dan persentase validasi.

### Tampilan Baru

```
15 jam dari total 30 jam telah tervalidasi.
* Informasi ini hanya untuk monitoring. Capaian resmi tidak berubah.
```

### Aturan Tampilan

| Kondisi | Teks |
|---|---|
| `totalDurasiMenit > 0` | `{jamValidText} dari total {totalJamKerjaText} telah tervalidasi.` |
| `totalDurasiMenit == 0` | `Belum ada jam kerja pada periode ini.` |

Format jam: `X jam` jika sisa menit = 0, `X jam Y menit` jika ada sisa menit.

### Perubahan

**File:** `resources/views/asn/bulanan/partials/ringkasan.blade.php`
Tambah blok `@php` untuk format string dan 1 paragraf `<p>` — murni presentasi.

### File yang Berubah

```
resources/views/asn/bulanan/partials/ringkasan.blade.php  (DIMODIFIKASI)
```

### Query Tambahan

Nihil. Semua variable (`$totalJamKerja`, `$sisaMenit`, `$jamValid`, `$sisaMenutValid`)
sudah tersedia dari controller.

### Risiko Regression

Nihil. Perubahan murni teks di blade, tidak ada logika baru.

---

## Daftar Keputusan Arsitektur

Keputusan-keputusan berikut diambil secara eksplisit selama implementasi dan
harus dipertahankan pada perubahan mendatang.

### Keputusan Data & Perhitungan

| # | Keputusan | Alasan |
|---|---|---|
| 1 | `getTargetMenitBulanan()` adalah single source of truth untuk target waktu | Menghindari dua jalur perhitungan yang bisa tidak sinkron |
| 2 | `getTargetJamBulananUser()` harus delegasi ke `getTargetMenitBulanan()/60` | Konsistensi antara `target_jam` dan `target_menit_bulanan_snapshot` |
| 3 | Libur khusus **mengurangi target** (bukan mengurangi realisasi) | Lebih adil bagi Guru — mereka tidak dihukum karena libur resmi |
| 4 | Status eviden saat ini **hanya monitoring**, belum mempengaruhi capaian resmi | Perlu keputusan regulasi + audit lebih lanjut sebelum bisa diterapkan |
| 5 | Persentase validasi = `menitValid / totalDurasiMenit`, bukan dibandingkan dengan target | Lebih mudah dipahami ASN, tidak membingungkan dengan capaian jam |

### Keputusan Data Historis

| # | Keputusan | Alasan |
|---|---|---|
| 6 | Laporan berstatus **DISETUJUI tidak pernah di-recalculate** | Integritas data — laporan yang sudah disetujui atasan tidak boleh berubah |
| 7 | Laporan berstatus **DIKIRIM tidak di-recalculate** | Sedang dalam review atasan — recalculate bisa membingungkan |
| 8 | 840 laporan SENIN_SABTU dengan legacy bug dibiarkan as-is | 742 sudah DISETUJUI, tidak etis mengubah data yang sudah disetujui atasan |

### Keputusan Arsitektur Service

| # | Keputusan | Alasan |
|---|---|---|
| 9 | `LiburKhususService` tidak membuat service baru untuk setiap jabatan | Interface di-design extensible: `isGuru()`, `isPenyuluh()`, `isPenghulu()` sudah ada |
| 10 | Bulk pre-load `getTanggalLiburGuruBulanan()` di monitoring controller | Mencegah N+1 query saat render daftar ASN yang panjang |
| 11 | Cache libur khusus TTL 10 menit per unit+bulan | Balance antara freshness dan performa |
| 12 | Cache dihapus otomatis saat admin simpan/ubah/aktifkan kalender | Cache invalidation berbasis event, bukan TTL semata |

### Keputusan Controller & Route

| # | Keputusan | Alasan |
|---|---|---|
| 13 | Route `kalender-libur-khusus` menggunakan 7 route explicit, bukan `Route::resource` | Bug truncation Laravel: `{kalender_libur_khusu}` — parameter terpotong |
| 14 | Controller atasan (`ApprovalController`) tidak diubah | Sudah production-stable, tidak ada alasan untuk menyentuhnya |
| 15 | Tidak membuat middleware baru untuk Libur Khusus | Gate diletakkan langsung di controller method untuk keterbacaan |

### Keputusan yang Ditunda

| # | Keputusan | Alasan Ditunda |
|---|---|---|
| D1 | `TIDAK_SESUAI` mengurangi realisasi jam | 61 dari 68 ASN terdampak kehilangan 100% jam — kemungkinan ada kesalahan verifikasi massal di atasan. Perlu investigasi + keputusan regulasi |
| D2 | `hitungStatus` per-hari berbasis pola kerja | Risiko tinggi — mempengaruhi warna status di semua tampilan harian |
| D3 | Stage 5: KinerjaBawahanController user-aware + MonitoringKakanwil per-user | Tunda — prioritas lain lebih tinggi |
| D4 | Normalisasi target persen RAB | Tunda — perlu keputusan regulasi SKP |
| D5 | Libur Khusus untuk PENYULUH dan PENGHULU | Stage 2 — interface sudah disiapkan di `LiburKhususService` |

---

## Daftar File Terdampak Kumulatif

### File Baru (tidak ada sebelumnya)

```
database/migrations/2026_06_03_000001_add_idx_bukti_verified_to_progres_harian.php
database/migrations/2026_06_06_000001_create_kalender_libur_khusus_table.php
app/Models/KalenderLiburKhusus.php
app/Services/LiburKhususService.php
app/Http/Controllers/Admin/KalenderLiburKhususController.php
app/Http/Controllers/MonitoringBimasIslamController.php
resources/views/admin/kalender-libur-khusus/index.blade.php
resources/views/admin/kalender-libur-khusus/tambah.blade.php
resources/views/admin/kalender-libur-khusus/edit.blade.php
```

### File Dimodifikasi

```
app/Services/WorkingTimeService.php                                      (Patch A)
app/Services/LiburKhususService.php                                      (Patch B — method baru)
app/Services/LaporanBulananService.php                                   (Patch B — generateBulanan)
app/Http/Controllers/Asn/HarianController.php                            (Stage 1.1 — 6 gate)
app/Http/Controllers/Asn/BulananController.php                           (Status Eviden + Ringkasan)
app/Http/Controllers/MonitoringKakanwilController.php                    (Stage 1 — bulk libur khusus)
resources/views/asn/bulanan/partials/rekap-detail-harian.blade.php       (Status Eviden)
resources/views/asn/bulanan/partials/ringkasan.blade.php                 (Ringkasan Validasi)
resources/views/components/sidebar.blade.php                             (Menu Kalender Libur Khusus)
routes/web.php                                                           (7 route explicit)
config/working_time.php                                                  (Konfigurasi menit per hari)
```

### Migration yang Sudah Dijalankan di Production

```
2026_06_03_000001_add_idx_bukti_verified_to_progres_harian  ← index composite bukti+verified
2026_06_06_000001_create_kalender_libur_khusus_table        ← tabel baru
```

---

*Dokumen ini dibuat pada 2026-06-07 dan akan diperbarui setiap kali ada
implementasi baru yang sudah selesai diupload ke production.*
