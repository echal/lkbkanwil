# 📄 FITUR CETAK PDF LAPORAN KINERJA ASN - DOKUMENTASI LENGKAP

## 🎯 OVERVIEW

Implementasi lengkap fitur cetak PDF untuk:
1. **Laporan Kinerja Harian (LKH)** - Format Portrait A4
2. **Rekap Kinerja Bulanan** - Format Landscape A4

---

## ✅ STATUS IMPLEMENTASI

✅ **Controller**: `LaporanCetakController.php` (COMPLETE)
✅ **Routes**: 2 routes added (COMPLETE)
✅ **Blade Views**: 2 PDF templates (COMPLETE)
✅ **Library**: barryvdh/laravel-dompdf (NEEDS INSTALLATION)

---

## 📦 INSTALASI

### Step 1: Install Library DomPDF

Jalankan di terminal:

```bash
cd c:\xampp\htdocs\gaspul\gaspul_api
composer require barryvdh/laravel-dompdf
```

### Step 2: Verifikasi Instalasi

Pastikan `composer.json` memiliki entry:

```json
"require": {
    "barryvdh/laravel-dompdf": "^3.0"
}
```

### Step 3: Clear Cache (Opsional)

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 🚀 PENGGUNAAN

### 1. Cetak PDF Laporan Kinerja Harian

**URL Format:**
```
GET /asn/laporan/cetak-harian?date=2026-01-30
```

**Parameter:**
- `date` (optional): Format `Y-m-d`, default = hari ini

**Contoh Request:**

```php
// Dari blade view, tambahkan button:
<a href="{{ route('asn.laporan.cetak-harian', ['date' => '2026-01-30']) }}"
   target="_blank"
   class="btn btn-primary">
    <i class="fas fa-file-pdf"></i> Cetak PDF Harian
</a>

// Untuk tanggal hari ini:
<a href="{{ route('asn.laporan.cetak-harian') }}"
   target="_blank"
   class="btn btn-primary">
    <i class="fas fa-file-pdf"></i> Cetak PDF Hari Ini
</a>
```

**Output:**
- File PDF: `LKH_NamaASN_2026-01-30_20260130120530.pdf`
- Format: Portrait A4
- Auto download

---

### 2. Cetak PDF Rekap Kinerja Bulanan

**URL Format:**
```
GET /asn/laporan/cetak-bulanan?bulan=1&tahun=2026
```

**Parameter:**
- `bulan` (optional): 1-12, default = bulan sekarang
- `tahun` (optional): 2020-2100, default = tahun sekarang

**Contoh Request:**

```php
// Dari blade view, tambahkan button:
<a href="{{ route('asn.laporan.cetak-bulanan', ['bulan' => 1, 'tahun' => 2026]) }}"
   target="_blank"
   class="btn btn-success">
    <i class="fas fa-file-pdf"></i> Cetak PDF Bulanan
</a>

// Untuk bulan/tahun saat ini:
<a href="{{ route('asn.laporan.cetak-bulanan') }}"
   target="_blank"
   class="btn btn-success">
    <i class="fas fa-file-pdf"></i> Cetak PDF Bulan Ini
</a>
```

**Output:**
- File PDF: `Rekap_Bulanan_NamaASN_Januari_2026_20260130120530.pdf`
- Format: Landscape A4
- Auto download

---

## 📋 ISI DOKUMEN PDF

### Laporan Kinerja Harian (LKH)

**Header:**
- Kop surat Kementerian Agama
- Judul: LAPORAN KINERJA HARIAN (LKH)
- Tanggal laporan

**Informasi ASN:**
- Nama ASN
- NIP
- Jabatan
- Unit Kerja
- Tanggal Laporan
- Status Harian (LENGKAP / KURANG / BELUM UPLOAD BUKTI / KOSONG)

**Rincian Kegiatan:**
Tabel dengan kolom:
- NO
- JAM (mulai - selesai)
- DURASI
- URAIAN KEGIATAN (dengan badge KH/TLA)
- PROGRES
- BUKTI (✓ / -)

**Ringkasan:**
- Total Durasi Kerja
- Total Kinerja Harian (KH)
- Total Tugas Langsung Atasan (TLA)
- Total Kegiatan
- Status Kelengkapan

**Footer:**
- Tanda tangan ASN
- Tanggal cetak
- Keterangan sistem

---

### Rekap Kinerja Bulanan

**Header:**
- Kop surat Kementerian Agama
- Judul: REKAP KINERJA ASN BULANAN
- Periode: Bulan Tahun

**Informasi ASN:**
- Nama ASN, NIP
- Jabatan, Unit Kerja

**Rencana Aksi Bulanan:**
- Daftar rencana aksi bulan ini (dari SKP)
- Target dan satuan

**Tabel Rekap Per Hari:**
2 kolom paralel (untuk menghemat ruang):
- TGL
- HARI
- TOTAL JAM
- KH (count)
- TLA (count)
- STATUS (LENGKAP / < 7.5 JAM / BELUM BUKTI / -)

**Ringkasan Bulanan:**
- Total Hari dalam Bulan
- Hari Kerja (ada input)
- Hari Kosong
- Hari LENGKAP (hijau)
- Hari KURANG (kuning)
- Hari BELUM UPLOAD BUKTI (merah)
- Total KH & TLA
- Total Durasi Kerja
- Rata-rata Jam Kerja per Hari
- Tingkat Kepatuhan (%)

**Footer:**
- Tanda tangan ASN
- Tanggal cetak

---

## 🎨 DESAIN DOKUMEN

### Typography
- **Font Family**: Times New Roman (aman untuk PDF)
- **Font Size**:
  - Body: 10-11pt
  - Header: 12-14pt
  - Footer: 8-9pt
  - Table: 8-10pt

### Paper Size
- **Harian**: A4 Portrait (210 x 297 mm)
- **Bulanan**: A4 Landscape (297 x 210 mm)

### Color Scheme
- **Status GREEN**: #d4edda (background), #155724 (text)
- **Status YELLOW**: #fff3cd (background), #856404 (text)
- **Status RED**: #f8d7da (background), #721c24 (text)
- **Status GRAY/EMPTY**: #e2e3e5 (background), #383d41 (text)

### Borders & Spacing
- Table borders: 1px solid black
- Header border: 3px double black
- Padding: Consistent 20-30px margins

---

## 🔧 FILE STRUCTURE

```
gaspul_api/
├── app/
│   └── Http/
│       └── Controllers/
│           └── Asn/
│               └── LaporanCetakController.php    ← BARU (Controller)
├── resources/
│   └── views/
│       └── asn/
│           └── laporan/
│               └── pdf/
│                   ├── harian.blade.php          ← BARU (View PDF Harian)
│                   └── bulanan.blade.php         ← BARU (View PDF Bulanan)
└── routes/
    └── web.php                                   ← UPDATED (2 routes added)
```

---

## 📝 ROUTES YANG DITAMBAHKAN

File: `routes/web.php`

```php
use App\Http\Controllers\Asn\LaporanCetakController;

// Di dalam Route::prefix('asn')->name('asn.')->middleware('role:ASN')->group()
Route::get('/laporan/cetak-harian', [LaporanCetakController::class, 'cetakHarian'])
    ->name('laporan.cetak-harian');

Route::get('/laporan/cetak-bulanan', [LaporanCetakController::class, 'cetakBulanan'])
    ->name('laporan.cetak-bulanan');
```

---

## 🔍 CONTROLLER METHODS

### 1. cetakHarian(Request $request)

**Query:**
```php
ProgresHarian::where('user_id', $asn->id)
    ->whereDate('tanggal', $tanggal)
    ->with(['rencanaAksiBulanan.skpTahunanDetail.indikatorKinerja'])
    ->orderBy('jam_mulai')
    ->get();
```

**Logic:**
- Get progres harian untuk tanggal tertentu
- Calculate: total menit, total KH, total TLA
- Determine status (KOSONG/BELUM UPLOAD BUKTI/KURANG DARI 7.5 JAM/LENGKAP)
- Pass data ke view PDF
- Return PDF download

---

### 2. cetakBulanan(Request $request)

**Query 1 - Progres Harian:**
```php
ProgresHarian::where('user_id', $asn->id)
    ->whereBetween('tanggal', [$startDate, $endDate])
    ->with(['rencanaAksiBulanan.skpTahunanDetail.indikatorKinerja'])
    ->orderBy('tanggal')
    ->get();
```

**Query 2 - Rencana Aksi:**
```php
RencanaAksiBulanan::whereHas('skpTahunanDetail.skpTahunan', function($q) use ($asn, $tahun) {
        $q->where('user_id', $asn->id)
          ->where('tahun', $tahun);
    })
    ->where('bulan', $bulan)
    ->where('tahun', $tahun)
    ->whereNotNull('rencana_aksi_bulanan')
    ->get();
```

**Logic:**
- Build array rekap per hari (1-31)
- Calculate summary statistics
- Pass data + rencana aksi ke view PDF
- Return PDF download (landscape)

---

## ⚡ BEST PRACTICES

### Performance
✅ **Eager Loading**: Use `->with()` untuk relasi
✅ **Indexing**: Query sudah menggunakan indexed columns (user_id, tanggal)
✅ **Pagination**: Tidak perlu (per tanggal/bulan)

### Security
✅ **Auth Middleware**: Protected by `role:ASN`
✅ **User Filtering**: `where('user_id', Auth::user()->id)`
✅ **Input Validation**: Date validation via Carbon

### Compatibility
✅ **Shared Hosting**: DomPDF tidak memerlukan binary eksternal
✅ **Font Safe**: Times New Roman tersedia di semua sistem
✅ **CSS Simple**: Tidak menggunakan CSS3 advanced

### Printing
✅ **Paper Size**: Standard A4
✅ **Margins**: Cukup untuk printer biasa
✅ **No JavaScript**: Pure HTML/CSS
✅ **No External Assets**: Tidak ada gambar/font eksternal

---

## 🐛 TROUBLESHOOTING

### Problem: "Class 'Barryvdh\DomPDF\Facade\Pdf' not found"

**Solution:**
```bash
composer require barryvdh/laravel-dompdf
php artisan config:clear
```

---

### Problem: PDF blank atau error

**Solution:**
1. Check blade syntax error:
```bash
php artisan view:clear
```

2. Debug dengan return HTML instead of PDF:
```php
// Di controller, ganti:
return $pdf->download($filename);

// Menjadi:
return view('asn.laporan.pdf.harian', $data);
```

---

### Problem: Fonts tidak muncul

**Solution:**
DomPDF otomatis menggunakan font default. Untuk custom fonts:

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

Lalu edit `config/dompdf.php`

---

### Problem: Timeout pada bulan dengan banyak data

**Solution:**
Increase timeout di config:

```php
// config/dompdf.php
'timeout' => 120, // Default 30
```

---

## 📊 TESTING CHECKLIST

### Manual Testing

**Harian:**
- [ ] PDF generate untuk hari dengan data
- [ ] PDF generate untuk hari kosong
- [ ] Status badge warna correct
- [ ] Tabel progres harian complete
- [ ] Summary calculations correct
- [ ] Filename format correct

**Bulanan:**
- [ ] PDF generate untuk bulan penuh
- [ ] PDF generate untuk bulan kosong
- [ ] Rekap per hari dalam tabel 2 kolom
- [ ] Rencana aksi tampil (jika ada)
- [ ] Summary statistics correct
- [ ] Tingkat kepatuhan % correct

---

## 🎯 NEXT STEPS

### Integration ke UI

**1. Tambahkan Button di Halaman Harian:**

File: `resources/views/asn/harian/index.blade.php`

```blade
<!-- Tambahkan di header halaman -->
<div class="flex justify-between mb-4">
    <h2>Kinerja Harian</h2>
    <a href="{{ route('asn.laporan.cetak-harian', ['date' => $selectedDate]) }}"
       target="_blank"
       class="btn btn-primary">
        <i class="fas fa-file-pdf"></i> Cetak PDF Hari Ini
    </a>
</div>
```

---

**2. Tambahkan Button di Halaman Bulanan:**

File: `resources/views/asn/bulanan/index.blade.php`

```blade
<!-- Tambahkan di header halaman -->
<div class="flex justify-between mb-4">
    <h2>Laporan Bulanan - {{ $namaBulan }} {{ $tahun }}</h2>
    <a href="{{ route('asn.laporan.cetak-bulanan', ['bulan' => $bulan, 'tahun' => $tahun]) }}"
       target="_blank"
       class="btn btn-success">
        <i class="fas fa-file-pdf"></i> Cetak PDF Bulanan
    </a>
</div>
```

---

### Advanced Features (Future)

- [ ] Email PDF ke atasan
- [ ] Batch download multiple months
- [ ] Digital signature integration
- [ ] QR code untuk verifikasi
- [ ] Export to Excel

---

## 📚 RESOURCES

### Documentation
- [Laravel DomPDF](https://github.com/barryvdh/laravel-dompdf)
- [DomPDF Options](https://github.com/dompdf/dompdf/wiki/Usage)

### Support
- Issues: Create GitHub issue
- Questions: Contact dev team

---

## ✅ COMPLETION CHECKLIST

- [x] Controller created (`LaporanCetakController.php`)
- [x] Routes added (2 routes)
- [x] Blade view PDF Harian created
- [x] Blade view PDF Bulanan created
- [x] Documentation complete
- [ ] **Install DomPDF library** ← USER ACTION REQUIRED
- [ ] Test PDF Harian
- [ ] Test PDF Bulanan
- [ ] Add UI buttons

---

## 🚀 READY TO USE

Setelah install DomPDF, fitur cetak PDF sudah SIAP DIGUNAKAN!

**Quick Test:**

1. Install library:
```bash
cd c:\xampp\htdocs\gaspul\gaspul_api
composer require barryvdh/laravel-dompdf
```

2. Test URL (ganti dengan user ASN yang login):
```
http://localhost/gaspul/gaspul_api/public/asn/laporan/cetak-harian
http://localhost/gaspul/gaspul_api/public/asn/laporan/cetak-bulanan
```

3. Jika sukses, PDF akan auto-download!

---

**END OF DOCUMENTATION**
Last Updated: 30 Januari 2026
