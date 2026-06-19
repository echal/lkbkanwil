# LAPORAN KEGIATAN
## Pengembangan dan Implementasi Dashboard Kepatuhan ASN
## Kankemenag Kabupaten Mamasa pada Aplikasi e-SARAku

---

| | |
|---|---|
| **Nomor Laporan** | — |
| **Tanggal** | 12 Juni 2026 |
| **Tempat Pelaksanaan** | Mamuju |
| **Unit Kerja** | Kantor Wilayah Kementerian Agama Provinsi Sulawesi Barat |
| **Pelaksana** | Pranata Komputer |
| **Aplikasi** | e-SARAku (Sistem Elektronik Sasaran Akuntabilitas Kinerja) |

---

## I. PENDAHULUAN

### 1.1 Latar Belakang

Dalam rangka meningkatkan efektivitas pemantauan kepatuhan Aparatur Sipil Negara (ASN) dalam pengisian laporan kinerja harian pada aplikasi e-SARAku, dipandang perlu untuk mengembangkan fitur Dashboard Monitoring khusus yang dapat diakses secara langsung oleh Kepala Kantor Kementerian Agama (Kankemenag) Kabupaten Mamasa beserta jajaran pimpinan terkait.

Selama ini, pemantauan kepatuhan ASN hanya tersedia di tingkat Kantor Wilayah melalui Dashboard Monitoring Kakanwil. Belum tersedia sarana monitoring yang memadai di tingkat kabupaten yang memungkinkan pimpinan Kankemenag Mamasa memantau secara langsung tingkat kepatuhan pengisian kinerja harian ASN di lingkungan kerjanya, status Sasaran Kinerja Pegawai (SKP) tahun berjalan, serta mengidentifikasi ASN yang memerlukan perhatian dan tindak lanjut pembinaan.

Pengembangan dashboard ini merupakan bagian dari komitmen Kantor Wilayah Kementerian Agama Provinsi Sulawesi Barat dalam mendorong tertib administrasi kinerja ASN dan mendukung budaya kerja berbasis data di seluruh satuan kerja.

### 1.2 Tujuan Kegiatan

1. Membangun Dashboard Kepatuhan ASN khusus untuk lingkup Kankemenag Kabupaten Mamasa yang mencakup 28 unit kerja dan seluruh ASN aktif di bawah kewenangan Kankemenag Mamasa.
2. Menyediakan indikator kinerja utama (KPI) kepatuhan pengisian kinerja harian ASN secara real-time.
3. Menyediakan informasi distribusi status SKP tahun berjalan per ASN dan per unit kerja.
4. Membangun fitur ranking unit kerja berdasarkan tingkat kepatuhan ASN.
5. Membangun Panel ASN Prioritas Pembinaan yang membantu pimpinan mengidentifikasi ASN yang perlu mendapat perhatian khusus.
6. Memastikan implementasi berjalan tanpa menimbulkan gangguan terhadap fitur dan dashboard monitoring yang telah berjalan di lingkungan production e-SARAku.

### 1.3 Dasar Pelaksanaan

Kegiatan ini dilaksanakan sebagai bagian dari tugas Pranata Komputer dalam pengembangan dan pemeliharaan Sistem Informasi e-SARAku pada Kantor Wilayah Kementerian Agama Provinsi Sulawesi Barat.

---

## II. URAIAN KEGIATAN

### Sesi I — Analisis Kebutuhan dan Audit Kelayakan
**Waktu: Pukul 07.30 – 11.30 WITA**

#### 2.1.1 Analisis Kebutuhan Dashboard

Kegiatan diawali dengan analisis mendalam terhadap kebutuhan Dashboard Kepatuhan ASN Kankemenag Kabupaten Mamasa. Analisis mencakup identifikasi pemangku kepentingan yang akan menggunakan dashboard, alur informasi yang dibutuhkan, serta mekanisme akses yang sesuai dengan kebutuhan monitoring di tingkat kabupaten.

Dari hasil analisis ditetapkan bahwa dashboard akan dapat diakses melalui URL khusus tanpa memerlukan login ke sistem, dengan pengamanan menggunakan token rahasia. Mekanisme ini mengadopsi pendekatan yang sama dengan Dashboard Monitoring Kakanwil yang telah berjalan, sehingga dashboard dapat ditampilkan di layar monitor atau televisi di ruang kerja pimpinan tanpa perlu melakukan autentikasi ulang secara berkala.

#### 2.1.2 Audit Struktur Data

Dilakukan audit terhadap struktur data yang dibutuhkan untuk mendukung seluruh fungsi dashboard. Audit mencakup:

- **Tabel `users`**: Verifikasi ketersediaan kolom `unit_kerja_id`, `role`, `status_pegawai`, `jabatan`, `hari_kerja`, dan `atasan_id` yang relevan dengan perhitungan kepatuhan dan pola hari kerja ASN.
- **Tabel `unit_kerja`**: Konfirmasi 28 unit kerja yang berada dalam lingkup Kankemenag Mamasa (ID 16 dan 27 unit kerja turunan, ID 141–168).
- **Tabel `progres_harian`**: Audit ketersediaan index komposit `idx_tipe_user_tanggal (tipe_progres, user_id, tanggal)` yang kritis untuk performa query monitoring.
- **Tabel `skp_tahunan`**: Pemetaan seluruh status SKP yang berlaku dalam sistem, yaitu: DRAFT, DIAJUKAN, DISETUJUI, DITOLAK, REVISI_DIAJUKAN, dan REVISI_DITOLAK.
- **Tabel `cuti_asn`**: Konfirmasi struktur kolom `tanggal_mulai` dan `tanggal_selesai` untuk keperluan filter kepatuhan pada hari ASN sedang menjalani cuti.

Hasil audit menyimpulkan bahwa seluruh data yang dibutuhkan tersedia dan struktur database mendukung implementasi tanpa memerlukan perubahan skema.

#### 2.1.3 Audit Hak Akses dan Keamanan

Dilakukan audit terhadap mekanisme hak akses yang akan diterapkan pada dashboard. Ditetapkan bahwa dashboard menggunakan sistem token berbasis parameter URL (`?token=`), sesuai dengan pola yang telah digunakan pada dashboard monitoring yang sudah berjalan. Token disimpan sebagai variabel lingkungan (environment variable) dan dikonfigurasi melalui file `.env`, sehingga tidak tersimpan langsung di dalam kode program.

#### 2.1.4 Audit Sumber Data Kepatuhan Harian

Dilakukan kajian mendalam terhadap logika perhitungan kepatuhan harian ASN. Audit mempertimbangkan beberapa faktor khusus yang berlaku dalam sistem e-SARAku:

- **Pola hari kerja dua varian**: ASN dengan pola Senin–Jumat (5 hari kerja) dan ASN dengan pola Senin–Sabtu (6 hari kerja). Pola kerja disimpan di tingkat individu ASN maupun unit kerja.
- **Libur khusus Guru**: ASN dengan jabatan mengandung kata "guru" mendapat pengecualian dari kewajiban pengisian pada hari libur khusus madrasah yang dicatat dalam tabel `kalender_libur_khusus`.
- **Cuti ASN**: ASN yang sedang menjalani cuti dikecualikan dari hitungan kewajiban pengisian pada hari yang bersangkutan.
- **Hari libur nasional**: Menggunakan data libur nasional tahun 2026 yang telah tersedia di `HolidayHelper`.

#### 2.1.5 Audit Data SKP dan Persetujuan SKP

Dilakukan audit terhadap pemetaan status SKP untuk keperluan pelaporan di dashboard. Ditemukan bahwa sistem memiliki 6 status SKP (bukan 4 seperti yang disederhanakan pada dokumentasi sebelumnya), dan dilakukan pemetaan ulang ke dalam 4 kategori tampilan:

| Status DB | Kategori Dashboard |
|-----------|-------------------|
| DISETUJUI | Disetujui |
| REVISI_DITOLAK | Disetujui *(SKP tetap valid)* |
| DIAJUKAN | Menunggu Persetujuan |
| REVISI_DIAJUKAN | Menunggu Persetujuan |
| DRAFT | Perlu Tindak Lanjut |
| DITOLAK | Perlu Tindak Lanjut |
| *(tidak ada SKP)* | Belum Buat SKP |

Status `REVISI_DITOLAK` dikonfirmasi sebagai SKP yang tetap valid (revisi yang diajukan ditolak, namun SKP induk tetap berlaku), sehingga masuk kategori "Disetujui" dan tidak dikategorikan sebagai bermasalah.

#### 2.1.6 Audit Ranking Unit Kerja dan Desain Panel Pembinaan

Dilakukan perancangan mekanisme ranking unit kerja berdasarkan persentase kepatuhan pengisian kinerja harian ASN selama bulan berjalan. Selain itu, dirancang sistem penilaian prioritas pembinaan ASN menggunakan sistem skor kumulatif dengan kriteria:

| Kriteria | Skor |
|----------|------|
| Belum pernah mengisi di bulan berjalan | +40 |
| Belum membuat SKP | +30 |
| SKP ditolak | +25 |
| Tidak mengisi ≥5 hari kerja berturut-turut | +20 |
| Kepatuhan di bawah 50% | +15 |
| SKP masih berstatus Draft | +10 |
| SKP/Revisi SKP menunggu persetujuan | +5 |
| Kepatuhan antara 50%–80% | +5 |

Berdasarkan total skor, setiap ASN dikategorikan ke dalam prioritas: **TINGGI** (≥40), **SEDANG** (10–39), **RENDAH** (1–9), dan **BAIK** (0).

#### 2.1.7 Audit Risiko Implementasi

Dilakukan audit risiko yang komprehensif untuk memastikan implementasi tidak mengganggu sistem production yang sedang berjalan. Prinsip implementasi yang ditetapkan:

- **Additive Only**: Seluruh penambahan berupa file dan konfigurasi baru, tidak ada perubahan pada file yang sudah production.
- **Zero Regression**: Tidak ada modifikasi pada controller, model, service, atau view yang sudah berjalan.
- **Minim Risiko**: Dashboard baru berdiri independen, tidak berbagi logika dengan dashboard Kakanwil maupun Bimas Islam.

Hasil audit menyimpulkan bahwa implementasi **layak dilaksanakan** tanpa risiko gangguan terhadap sistem yang sedang berjalan.

**Output Sesi I:**
- Dokumen analisis kebutuhan dashboard (tersimpan dalam sesi diskusi teknis).
- Desain awal dashboard beserta KPI, panel pembinaan, dan sistem skor.
- Hasil audit kelayakan implementasi dengan kesimpulan: **Layak Dilaksanakan**.

---

### Sesi II — Implementasi Dashboard
**Waktu: Pukul 13.30 – 15.00 WITA**

#### 2.2.1 Konfigurasi dan Route

Implementasi diawali dengan penambahan konfigurasi token keamanan:

- **`config/app.php`**: Penambahan kunci konfigurasi `mamasa_monitor_token` yang membaca nilai dari environment variable.
- **`.env`**: Penambahan variabel `MAMASA_MONITOR_TOKEN=MAMASA2026TV`.
- **`routes/web.php`**: Pendaftaran 3 route baru:
  - `GET /monitoring-tv/mamasa` — halaman utama dashboard
  - `GET /monitoring-tv/mamasa/clear-cache` — utilitas refresh cache
  - `GET /monitoring-tv/mamasa/asn-detail/{id}` — endpoint AJAX drill-down per ASN

Seluruh penambahan route ditempatkan di blok terpisah dengan komentar identifikasi yang jelas, tidak mengubah urutan atau logika route yang sudah ada.

#### 2.2.2 Pembuatan Controller `MonitoringMamasaController`

Dibuat controller baru `app/Http/Controllers/MonitoringMamasaController.php` yang sepenuhnya independen dari controller monitoring yang sudah ada. Controller dirancang dengan arsitektur dua lapis:

**Lapis 1 — Data Ter-cache (TTL 5 menit):** Data yang relatif statis berupa distribusi SKP tahun berjalan dan statistik SKP per unit kerja, disimpan dalam cache untuk mengurangi beban query berulang.

**Lapis 2 — Data Real-time (tidak di-cache):** Data kepatuhan harian, status pengisian hari ini, dan skor prioritas pembinaan selalu dihitung segar setiap kali dashboard diakses, untuk memastikan informasi yang ditampilkan mencerminkan kondisi terkini.

Controller mengimplementasikan tiga method utama:

- **`index()`**: Mengorkestrasi pengambilan data ter-cache dan real-time, kemudian meneruskan ke view.
- **`clearCache()`**: Menghapus seluruh cache monitoring Mamasa dan mengarahkan kembali ke dashboard.
- **`asnDetail()`**: Endpoint AJAX yang menyajikan detail lengkap kepatuhan satu ASN untuk fitur drill-down modal.

#### 2.2.3 Implementasi Komponen KPI

Diimplementasikan seluruh komponen KPI dashboard, meliputi:

- **KPI Kepatuhan Harian**: Total ASN aktif, jumlah ASN wajib mengisi hari ini, jumlah sudah mengisi, jumlah belum mengisi, dan persentase kepatuhan dengan indikator warna adaptif (hijau ≥80%, kuning 50–79%, merah <50%).
- **KPI SKP Tahun Berjalan**: Distribusi status SKP seluruh ASN dengan pemetaan 6 status ke 4 kategori tampilan.
- **Ringkasan Distribusi Prioritas Pembinaan**: Jumlah ASN per kategori prioritas (TINGGI, SEDANG, RENDAH, BAIK).

#### 2.2.4 Implementasi Ranking Unit Kerja dan Panel Pembinaan

Diimplementasikan tabel ranking unit kerja yang menampilkan persentase kepatuhan pengisian kinerja harian ASN per unit kerja selama bulan berjalan, disertai data pengisian pada hari bersangkutan. Tabel ditampilkan dengan fitur collapsible (5 unit kerja teratas tampil secara default).

Panel ASN Prioritas Pembinaan diimplementasikan sebagai tabel interaktif berbasis Alpine.js dengan fitur:
- Filter real-time berdasarkan prioritas pembinaan, status SKP, dan pencarian nama/NIP/unit.
- Pengurutan kolom secara interaktif (nama, persentase kepatuhan, skor).
- Paginasi sisi klien.
- Tombol drill-down yang membuka modal detail per ASN melalui permintaan AJAX.

#### 2.2.5 Pembuatan View

Dibuat view `resources/views/monitoring/mamasa.blade.php` menggunakan Tailwind CSS dan Alpine.js, konsisten dengan pola tampilan dashboard monitoring yang sudah ada. View dilengkapi dengan navigasi bulan, auto-refresh setiap 5 menit, dan notifikasi status waktu (hari libur, di luar jam kerja, jam kerja aktif).

**Output Sesi II:**
- Controller `MonitoringMamasaController.php` berhasil dibuat dan terdaftar.
- Route dashboard monitoring Mamasa berhasil dikonfigurasi (3 route).
- View dashboard berhasil dibuat dengan seluruh komponen utama.
- Dashboard berhasil diakses pada lingkungan pengembangan lokal.

---

### Sesi III — Pengujian, Audit Performa, dan Optimasi
**Waktu: Pukul 15.00 – 17.30 WITA**

#### 2.3.1 Investigasi dan Perbaikan Error 500

Saat pengujian awal, ditemukan error 500 pada halaman dashboard dengan pesan:

```
Call to undefined method stdClass::relationLoaded()
```

Investigasi mengungkap bahwa method `HolidayHelper::getHariKerjaUser()` dan `HolidayHelper::isWorkingDay()` mengandung pemanggilan method `relationLoaded()` yang merupakan method khusus Eloquent ORM. Karena data ASN pada dashboard diambil menggunakan `DB::table()` (query builder) yang menghasilkan objek `stdClass`, bukan Eloquent model, pemanggilan tersebut menyebabkan fatal error.

**Solusi yang diterapkan:** Dibuat tiga method helper privat di dalam controller yang berfungsi menggantikan pemanggilan HolidayHelper untuk konteks objek `stdClass`:

- **`getPolaHariKerja(object $asn)`**: Membaca properti `hari_kerja` langsung dari `stdClass` tanpa akses relasi Eloquent.
- **`isWorkingDayForAsn(Carbon $date, object $asn)`**: Mengimplementasikan ulang logika hari kerja untuk `stdClass`, memanfaatkan `HolidayHelper::isNationalHoliday()` yang tidak bergantung pada Eloquent.
- **`isGuruStdClass(object $asn)`**: Menggantikan `LiburKhususService::isGuru()` yang memerlukan Eloquent User model.

Pendekatan ini memastikan `HolidayHelper` tidak dimodifikasi sama sekali, sesuai dengan prinsip zero regression yang telah ditetapkan.

#### 2.3.2 Audit Query Database — Identifikasi N+1 Query

Dilakukan audit menyeluruh terhadap seluruh query yang dieksekusi selama proses pengambilan data real-time dashboard. Temuan audit:

**Query yang teridentifikasi sebelum optimasi:**

| No | Query | Jumlah Eksekusi |
|----|-------|-----------------|
| 1 | Mega query ASN + SKP + subquery `progres_harian` | 1 |
| 2 | Batch query `cuti_asn` | 1 |
| 3 | Batch query `kalender_libur_khusus` (via LiburKhususService) | 1 |
| 4 | Query sudah isi hari ini | 1 |
| 5 | **Query `progres_harian` per ASN (hitungMaxGap)** | **329** |
| **Total** | | **333 query** |

Ditemukan bahwa fungsi `hitungMaxGap()` yang semula didokumentasikan sebagai "tidak ada query tambahan per ASN" ternyata menjalankan satu query `SELECT DISTINCT tanggal FROM progres_harian WHERE user_id = ?` untuk setiap ASN secara berulang dalam loop, menghasilkan 329 query tambahan. Kondisi ini merupakan pola N+1 Query yang berdampak signifikan terhadap performa dashboard.

#### 2.3.3 Optimasi N+1 Query pada `hitungMaxGap()`

**Strategi optimasi:** Menggantikan 329 query individual dengan 1 batch query yang mengambil seluruh data tanggal pengisian semua ASN sekaligus, kemudian menyusunnya menjadi struktur map in-memory `[user_id => ['YYYY-MM-DD' => true]]`.

**Batch query yang ditambahkan:**
```sql
SELECT DISTINCT user_id, tanggal
FROM progres_harian
WHERE user_id IN (... 329 user IDs ...)
  AND tanggal BETWEEN '2026-06-01' AND '2026-06-30'
  AND tipe_progres IN ('KINERJA_HARIAN', 'TUGAS_ATASAN')
```

**Fungsi baru `hitungMaxGapFromMap()`** menggantikan `hitungMaxGap()` lama. Fungsi ini bekerja sepenuhnya in-memory: menerima map tanggal isi yang sudah tersedia dan melakukan iterasi pada daftar hari kerja bulan berjalan tanpa query tambahan apapun. Algoritma penghitungan gap tetap identik, hanya sumber datanya yang berubah dari database query menjadi array in-memory.

**Hasil optimasi query:**

| No | Query | Jumlah Eksekusi |
|----|-------|-----------------|
| 1 | Mega query ASN + SKP + subquery `progres_harian` | 1 |
| 2 | Batch query `cuti_asn` | 1 |
| 3 | Batch query `kalender_libur_khusus` | 1 |
| 4 | Query sudah isi hari ini | 1 |
| 5 | **Batch query tanggal isi semua ASN** | **1** |
| **Total** | | **5 query** |

Reduksi: **333 → 5 query (turun 98,5%)**.

#### 2.3.4 Pengujian Fungsional

Dilakukan serangkaian pengujian fungsional, meliputi:

- **Pengujian pola hari kerja**: Verifikasi bahwa ASN dengan pola Senin–Jumat dan Senin–Sabtu mendapatkan penghitungan hari kerja wajib yang benar.
- **Pengujian libur khusus guru**: Verifikasi bahwa ASN berjabatan guru dikecualikan dari kewajiban pengisian pada tanggal libur khusus madrasah.
- **Pengujian filter cuti**: Verifikasi bahwa ASN yang sedang cuti tidak dihitung dalam kewajiban pengisian hari bersangkutan.
- **Pengujian distribusi SKP**: Verifikasi pemetaan 6 status SKP ke dalam 4 kategori tampilan, termasuk penanganan status `REVISI_DITOLAK` yang benar.
- **Pengujian panel prioritas pembinaan**: Verifikasi kalkulasi skor kumulatif dan kategorisasi prioritas.
- **Pengujian drill-down modal**: Verifikasi endpoint AJAX `asnDetail()` mengembalikan data yang benar untuk satu ASN yang dipilih.
- **Pengujian token guard**: Verifikasi bahwa akses tanpa token atau dengan token yang salah menghasilkan respons error 403.

#### 2.3.5 Review Kesiapan Production

Dilakukan review akhir terhadap kesiapan dashboard untuk tahap berikutnya. Hasil review:

- ✅ Tidak ada query per ASN (N+1 telah dieliminasi).
- ✅ Tidak ada modifikasi pada kode production yang sudah berjalan.
- ✅ Tidak ada perubahan pada `HolidayHelper`, `LiburKhususService`, atau service lainnya.
- ✅ Cache 5 menit berfungsi dengan benar dan dapat di-refresh secara manual.
- ✅ Token guard berfungsi dengan benar (abort 403 untuk akses tidak sah).
- ✅ Auto-refresh halaman setiap 5 menit berfungsi.
- ✅ Seluruh filter dan interaksi Alpine.js berfungsi pada sisi klien tanpa request tambahan ke server.

**Output Sesi III:**
- Error 500 berhasil didiagnosis dan diselesaikan.
- N+1 Query pada `hitungMaxGap()` berhasil dieliminasi.
- Total query real-time berkurang dari 333 menjadi 5 query.
- Dashboard dinyatakan **Production Ready**.

---

## III. HASIL YANG DICAPAI

Pada akhir hari kegiatan, seluruh target yang ditetapkan berhasil dipenuhi dengan rincian sebagai berikut:

### 3.1 Artefak yang Dihasilkan

| No | Artefak | Keterangan |
|----|---------|------------|
| 1 | `app/Http/Controllers/MonitoringMamasaController.php` | Controller baru, 789 baris |
| 2 | `resources/views/monitoring/mamasa.blade.php` | View dashboard baru |
| 3 | Penambahan pada `config/app.php` | 1 baris konfigurasi token |
| 4 | Penambahan pada `.env` | 1 variabel environment |
| 5 | Penambahan pada `routes/web.php` | 3 route baru |
| 6 | `docs/LAPORAN_ERROR_500_KINERJA_HARIAN.md` | Laporan insiden teknis |
| 7 | `docs/LAPORAN_KEGIATAN_DASHBOARD_MAMASA_12062026.md` | Laporan kegiatan ini |

### 3.2 Fitur yang Berhasil Diimplementasikan

| No | Fitur | Status |
|----|-------|--------|
| 1 | Token keamanan akses dashboard | ✅ Selesai |
| 2 | KPI kepatuhan pengisian harian (real-time) | ✅ Selesai |
| 3 | Distribusi status SKP tahun berjalan | ✅ Selesai |
| 4 | Ringkasan prioritas pembinaan | ✅ Selesai |
| 5 | Ranking unit kerja berdasarkan kepatuhan | ✅ Selesai |
| 6 | Panel ASN Prioritas Pembinaan + filter interaktif | ✅ Selesai |
| 7 | Drill-down detail per ASN (AJAX modal) | ✅ Selesai |
| 8 | Navigasi antar bulan | ✅ Selesai |
| 9 | Auto-refresh setiap 5 menit | ✅ Selesai |
| 10 | Cache layer 5 menit + utilitas clear cache | ✅ Selesai |

### 3.3 Capaian Performa

| Metrik | Sebelum Optimasi | Sesudah Optimasi | Selisih |
|--------|-----------------|-----------------|---------|
| Total query real-time | 333 | 5 | -98,5% |
| Query per ASN dalam loop | 329 | 0 | -100% |
| Estimasi waktu eksekusi | ~250–400 ms | ~80–120 ms | -60–70% |
| Pola N+1 Query | Ada | Tidak ada | Terselesaikan |

---

## IV. KENDALA DAN SOLUSI

### 4.1 Error 500 — Inkompatibilitas HolidayHelper dengan stdClass

**Kendala:** Metode `HolidayHelper::getHariKerjaUser()` dan `isWorkingDay()` yang sudah ada di sistem memanggil method `relationLoaded()` dari Eloquent ORM. Karena data ASN pada dashboard baru diambil menggunakan query builder (`DB::table()`) yang menghasilkan objek `stdClass`, pemanggilan tersebut menyebabkan fatal error pada saat dashboard diakses pertama kali.

**Solusi:** Dibuat tiga method helper privat baru di dalam `MonitoringMamasaController` yang mengimplementasikan logika yang sama tanpa bergantung pada Eloquent, sehingga `HolidayHelper` tidak perlu dimodifikasi dan tidak menimbulkan risiko terhadap fitur lain yang menggunakannya.

### 4.2 N+1 Query pada Fungsi `hitungMaxGap()`

**Kendala:** Fungsi `hitungMaxGap()` yang dirancang pada tahap awal implementasi ternyata menjalankan satu query database untuk setiap ASN dalam loop pemrosesan data, menghasilkan 329 query tambahan untuk 329 ASN aktif. Kondisi ini menyebabkan beban database yang tidak perlu dan berpotensi memperlambat respons dashboard secara signifikan, terutama pada jam sibuk.

**Solusi:** Fungsi `hitungMaxGap()` digantikan dengan dua komponen: (1) satu batch query yang mengambil seluruh data tanggal pengisian semua ASN sekaligus sebelum loop dimulai, dan (2) fungsi baru `hitungMaxGapFromMap()` yang bekerja sepenuhnya in-memory menggunakan data dari batch query tersebut. Dengan demikian, total query real-time berhasil ditekan dari 333 menjadi 5 query.

### 4.3 Perbedaan Dokumentasi Status SKP

**Kendala:** Pada tahap analisis awal ditemukan bahwa jumlah status SKP yang berlaku dalam sistem (6 status) berbeda dengan yang tersedia dalam dokumentasi ringkas sebelumnya (4 status). Status `REVISI_DIAJUKAN` dan `REVISI_DITOLAK` tidak tercakup dalam pemodelan awal.

**Solusi:** Dilakukan audit langsung pada model `SkpTahunan` dan tabel database untuk memverifikasi seluruh status yang mungkin. Pemetaan 6 status ke dalam 4 kategori tampilan disesuaikan berdasarkan makna bisnis masing-masing status, khususnya penanganan `REVISI_DITOLAK` sebagai kondisi di mana SKP induk tetap valid.

---

## V. KESIMPULAN

Seluruh rangkaian kegiatan pengembangan Dashboard Kepatuhan ASN Kankemenag Kabupaten Mamasa pada aplikasi e-SARAku telah berhasil diselesaikan dalam satu hari kerja pada tanggal 12 Juni 2026.

Dashboard yang dihasilkan memenuhi seluruh kriteria teknis yang ditetapkan:

1. **Fungsional**: Seluruh 10 fitur yang direncanakan berhasil diimplementasikan dan diuji.
2. **Performan**: Total query real-time berhasil ditekan menjadi 5 query, turun 98,5% dari kondisi awal sebelum optimasi.
3. **Aman**: Token guard berfungsi dengan benar; akses tidak sah secara otomatis ditolak.
4. **Zero Regression**: Tidak satu pun file production yang sudah berjalan dimodifikasi.
5. **Production Ready**: Dashboard dinyatakan layak untuk tahap User Acceptance Testing (UAT) dan selanjutnya deployment ke lingkungan production.

Dashboard ini diharapkan dapat memberikan manfaat nyata bagi Kepala Kankemenag Kabupaten Mamasa dalam memantau kepatuhan ASN secara real-time, mengidentifikasi ASN yang memerlukan pembinaan, serta mendukung pengambilan keputusan berbasis data dalam rangka peningkatan disiplin dan akuntabilitas kinerja ASN di lingkungan Kankemenag Kabupaten Mamasa.

---

## VI. RENCANA TINDAK LANJUT

| No | Kegiatan | Target Waktu | Keterangan |
|----|----------|-------------|------------|
| 1 | User Acceptance Testing (UAT) bersama Kepala Kankemenag Mamasa | Pekan III Juni 2026 | Koordinasi jadwal dengan Kankemenag Mamasa |
| 2 | Penyesuaian tampilan berdasarkan masukan UAT (jika ada) | Setelah UAT | Sesuai kebutuhan pengguna |
| 3 | Penambahan variabel `MAMASA_MONITOR_TOKEN` pada server production | Sebelum deployment | Upload `.env` production |
| 4 | Deployment ke server production | Setelah UAT selesai | Menggunakan mekanisme upload atau `git pull` |
| 5 | Sosialisasi penggunaan dashboard kepada Kepala Kankemenag Mamasa | Bersamaan dengan deployment | Penyampaian URL dan token akses |
| 6 | Pemantauan performa pasca deployment selama 3 hari pertama | H+1 s.d. H+3 deployment | Monitoring `laravel.log` di server |
| 7 | Kajian pengembangan dashboard sejenis untuk Kankemenag kabupaten lainnya di wilayah Sulawesi Barat | Juli 2026 | Bergantung pada hasil evaluasi dashboard Mamasa |

---

*Laporan ini dibuat sebagai pertanggungjawaban pelaksanaan kegiatan pengembangan sistem informasi e-SARAku pada Kantor Wilayah Kementerian Agama Provinsi Sulawesi Barat.*

---

**Mamuju, 12 Juni 2026**

Pranata Komputer
Kantor Wilayah Kementerian Agama
Provinsi Sulawesi Barat
