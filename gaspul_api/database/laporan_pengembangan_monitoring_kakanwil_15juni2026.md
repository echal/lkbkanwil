# LAPORAN KEGIATAN PENGEMBANGAN SISTEM
## Peningkatan Fitur Dashboard Monitoring Kepatuhan SKP
### Aplikasi e-SARAku — Kantor Wilayah Kementerian Agama Provinsi Sulawesi Barat

---

**Nomor Laporan :** 001/DEV/eSARAku/VI/2026
**Tanggal       :** Mamuju, 15 Juni 2026
**Kategori      :** Pengembangan & Peningkatan Sistem Informasi
**Status        :** Selesai — Telah Diterapkan ke Lingkungan Produksi

---

## I. PENDAHULUAN

### 1.1 Latar Belakang

Dashboard Monitoring Kakanwil pada aplikasi e-SARAku merupakan sarana utama bagi pimpinan Kantor Wilayah Kementerian Agama Provinsi Sulawesi Barat dalam memantau tingkat kepatuhan pegawai terhadap kewajiban penyusunan Sasaran Kinerja Pegawai (SKP) serta keaktifan kinerja harian ASN di seluruh satuan kerja.

Dalam evaluasi penggunaan sistem, ditemukan bahwa tabel **"Detail Kepatuhan per Unit Kerja"** pada dashboard hanya menampilkan angka agregat per satuan kerja tanpa memberikan kemampuan untuk menelusuri data hingga tingkat individu. Pimpinan tidak dapat mengetahui secara langsung nama-nama ASN yang belum menyusun SKP, siapa yang SKP-nya telah disetujui, maupun siapa yang SKP-nya masih dalam proses (berstatus Draft, Diajukan, atau Ditolak) hanya melalui tampilan dashboard.

Kondisi tersebut menyebabkan proses tindak lanjut dan pembinaan oleh pimpinan menjadi kurang efisien karena memerlukan penelusuran manual ke dalam sistem yang lebih dalam.

### 1.2 Tujuan

Kegiatan pengembangan ini bertujuan untuk:

1. Menambahkan kemampuan **drill-down** pada tabel kepatuhan SKP, sehingga pimpinan dapat melihat daftar nama ASN secara langsung dari dashboard tanpa perlu berpindah halaman;
2. Menyajikan informasi yang lebih lengkap, khususnya daftar ASN yang SKP-nya **belum mendapat persetujuan** (masih berstatus Draft, Diajukan, atau Ditolak), agar dapat menjadi bahan pembinaan yang terarah;
3. Meningkatkan akurasi perhitungan data pada tabel kepatuhan per unit kerja.

---

## II. PELAKSANAAN KEGIATAN

Kegiatan dilaksanakan dalam satu hari kerja dengan pembagian dua sesi sebagai berikut:

| Sesi | Waktu | Kegiatan |
|------|-------|----------|
| Sesi I | 07.30 – 12.00 WITA | Audit sistem, desain teknis, perbaikan kalkulasi data, dan pembangunan endpoint baru |
| Sesi II | 13.00 – 16.00 WITA | Pembangunan antarmuka modal interaktif dengan tab, pengujian, dan penerapan ke produksi |

**Penanggung Jawab Teknis :** Tim Pengembang Sistem e-SARAku
**Lingkungan Pengerjaan   :** Pengembangan lokal → Pengujian → Produksi (server Kantor Wilayah)

---

## III. URAIAN PEKERJAAN

### Sesi I — 07.30 s.d. 12.00 WITA

#### A. Audit dan Temuan Sistem

Sebelum implementasi, dilakukan audit menyeluruh terhadap komponen sistem yang berkaitan dengan fitur yang akan dikembangkan. Hasil audit menemukan dua permasalahan teknis yang perlu diperbaiki bersamaan dengan pengembangan fitur baru:

**Temuan 1 — Potensi *Double-count* pada Kolom "Sudah Buat SKP"**
Query yang digunakan untuk menghitung jumlah ASN yang telah menyusun SKP menggunakan `COUNT(s.id)` (menghitung baris data), bukan `COUNT(DISTINCT s.user_id)` (menghitung individu unik). Apabila seorang ASN memiliki lebih dari satu catatan SKP dalam satu tahun (misalnya akibat revisi data), angka pada kolom tersebut akan terhitung lebih dari satu, sehingga berpotensi menghasilkan nilai yang tidak akurat.

**Temuan 2 — Identitas Unit Kerja Tidak Tersimpan dalam Data**
Data yang dikirimkan ke tampilan dashboard untuk setiap baris unit kerja tidak menyertakan identitas unit (`unit_id`). Tanpa identitas ini, sistem tidak dapat mengetahui unit mana yang dipilih saat pengguna mengklik angka pada tabel, sehingga fitur drill-down tidak dapat dibangun.

#### B. Perbaikan Kalkulasi Data

Berdasarkan temuan di atas, dilakukan perbaikan sebagai berikut:

1. Query penghitungan "Sudah Buat SKP" diubah dari `COUNT(s.id)` menjadi `COUNT(DISTINCT s.user_id)` untuk memastikan setiap ASN hanya dihitung satu kali;
2. Identitas unit kerja (`unit_id`) ditambahkan ke dalam setiap baris data yang dikirimkan ke tampilan dashboard, sebagai kunci untuk pemanggilan data lebih lanjut.

#### C. Pembangunan Endpoint Data Baru

Dibangun satu *endpoint* (saluran data) baru pada sistem:

- **Alamat:** `/monitoring-kakanwil/skp-detail`
- **Fungsi:** Menyediakan daftar nama ASN berdasarkan unit kerja dan kategori yang dipilih
- **Kategori yang didukung:**
  - `belum_buat` — ASN yang sama sekali belum menyusun SKP tahun berjalan
  - `disetujui` — ASN yang SKP-nya telah mendapat persetujuan atasan
  - `belum_disetujui` — ASN yang sudah menyusun SKP namun belum mendapat persetujuan (mencakup status: Draft, Diajukan, Ditolak, dan Revisi Diajukan)

---

### Sesi II — 13.00 s.d. 16.00 WITA

#### D. Pembangunan Antarmuka Interaktif

Pada sesi ini dibangun tampilan modal (jendela pop-up) interaktif yang muncul ketika pimpinan mengklik angka pada kolom tabel kepatuhan.

**Fitur klik kolom "Belum Buat":**
- Menampilkan daftar nama, NIP, dan jabatan seluruh ASN di unit kerja tersebut yang belum menyusun SKP tahun 2026

**Fitur klik kolom "Disetujui":**
- Menampilkan jendela modal dengan **dua tab** yang dapat dipindah-pindah:

  | Tab | Isi |
  |-----|-----|
  | **✅ Disetujui** | Daftar ASN yang SKP-nya sudah disetujui, beserta nama, NIP, dan jabatan |
  | **⏳ Belum Disetujui** | Daftar ASN yang sudah menyusun SKP namun belum disetujui, dilengkapi kolom status (Draft / Diajukan / Ditolak / Revisi) dengan penanda warna |

Kedua tab dapat diakses tanpa perlu menutup atau membuka ulang jendela. Data kedua tab dimuat secara bersamaan saat jendela pertama kali dibuka, sehingga perpindahan antartab berlangsung instan tanpa penundaan.

#### E. Pengujian dan Penerapan

Sebelum diterapkan ke lingkungan produksi, dilakukan pengujian fungsional terhadap seluruh skenario:

- Klik kolom "Belum Buat" pada unit kerja dengan dan tanpa data
- Klik kolom "Disetujui" → verifikasi tab pertama menampilkan daftar yang benar
- Perpindahan ke tab "Belum Disetujui" → verifikasi nama dan status ASN sesuai data sistem
- Pengujian kasus khusus: unit kerja dengan kepatuhan 100% (tab "Belum Disetujui" menampilkan pesan konfirmasi)

Setelah dinyatakan lulus uji, perubahan diterapkan ke server produksi dan dikonfirmasi berjalan normal.

---

## IV. HASIL DAN KONDISI SISTEM PASCAPENERAPAN

### 4.1 Kondisi Data SKP per 15 Juni 2026

| Status SKP | Jumlah ASN |
|------------|-----------|
| Disetujui | 3.933 |
| Draft | 94 |
| Diajukan | 11 |
| Revisi Diajukan | 6 |
| Ditolak | 7 |
| **Total ASN Aktif** | **3.937** |
| **Total Unit Kerja** | **173** |

Tingkat kepatuhan SKP (disetujui / total pegawai aktif) per tanggal pelaporan: **≥ 99%**.

### 4.2 Kemampuan Baru Sistem

Sebelum dan sesudah pengembangan ini, kemampuan dashboard monitoring mengalami peningkatan sebagai berikut:

| Aspek | Sebelum | Sesudah |
|-------|---------|---------|
| Data per unit kerja | Angka agregat saja | Angka + dapat diklik untuk lihat nama ASN |
| Identifikasi ASN bermasalah | Tidak tersedia di dashboard | Tersedia langsung melalui klik pada tabel |
| Visibilitas status SKP individu | Tidak ada | Tersedia dengan penanda warna per status |
| Akurasi hitungan "Sudah Buat SKP" | Berpotensi *double-count* | Diperbaiki, berbasis individu unik |

---

## V. REKOMENDASI DAN TINDAK LANJUT

Berdasarkan data kondisi sistem per 15 Juni 2026, terdapat **118 ASN** (94 Draft + 11 Diajukan + 6 Revisi + 7 Ditolak) yang SKP Tahun 2026 nya belum mendapat persetujuan. Kepada pimpinan disarankan:

1. **Segera menindaklanjuti 7 ASN berstatus "Ditolak"** — SKP yang ditolak memerlukan perbaikan aktif dari ASN bersangkutan. Data nama per unit kerja kini dapat diakses langsung melalui dashboard;
2. **Mendorong 94 ASN berstatus "Draft"** agar segera mengajukan SKP kepada atasan langsung untuk mendapatkan persetujuan;
3. **Menginstruksikan para Kepala Satuan Kerja** untuk memanfaatkan fitur tab "Belum Disetujui" pada dashboard sebagai alat monitoring pembinaan internal.

---

## VI. PENUTUP

Seluruh rangkaian kegiatan pengembangan pada tanggal 15 Juni 2026 telah diselesaikan sesuai rencana dalam dua sesi kerja. Fitur drill-down pada tabel kepatuhan SKP telah aktif dan dapat digunakan oleh pimpinan melalui Dashboard Monitoring Kakanwil aplikasi e-SARAku.

Tidak terdapat perubahan pada struktur basis data yang memerlukan migrasi, sehingga penerapan ke produksi dilakukan tanpa gangguan layanan.

---

*Laporan ini disusun sebagai dokumentasi pengembangan sistem e-SARAku*
*Kantor Wilayah Kementerian Agama Provinsi Sulawesi Barat*
*Mamuju, 15 Juni 2026*
