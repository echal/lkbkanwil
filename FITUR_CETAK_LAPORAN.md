# 🖨️ FITUR CETAK LAPORAN KH & TLA - DOKUMENTASI

## ✅ STATUS IMPLEMENTASI

**Fitur cetak laporan sudah SELESAI dan READY TO USE!**

---

## 📋 OVERVIEW

Fitur cetak laporan memungkinkan Atasan untuk mencetak:
1. **Laporan Kinerja Harian (LKH)** - Untuk progres tipe KINERJA_HARIAN
2. **Laporan Tugas Langsung Atasan (TLA)** - Untuk progres tipe TUGAS_ATASAN

### Output Format:
- Template HTML resmi dengan kop surat Kemenag
- Ready untuk print atau save as PDF
- Format A4 dengan margin standar
- Include tanda tangan Atasan dan ASN

---

## 🎯 CARA MENGGUNAKAN

### Dari Halaman Kinerja Bawahan:

#### 1. Pilih ASN
Lihat tabel ASN bawahan yang ingin dicetak laporannya.

#### 2. Klik Tombol Cetak
- **Tombol "📄 KH"** → Cetak Laporan Kinerja Harian
- **Tombol "📄 TLA"** → Cetak Laporan Tugas Langsung Atasan

#### 3. Window Baru Terbuka
Window baru akan otomatis terbuka dengan preview laporan.

#### 4. Cetak atau Save
- **Cetak Langsung**: Klik tombol "🖨️ Cetak" di pojok kanan atas, atau tekan `Ctrl+P`
- **Save as PDF**: Pilih "Save as PDF" di printer options

---

## 📄 TEMPLATE LAPORAN

### Header Resmi
```
KEMENTERIAN AGAMA REPUBLIK INDONESIA
KANTOR WILAYAH PROVINSI SULAWESI BARAT
Jl. Abdul Malik Pattana Endeng No. 49 Mamuju 91511
Telepon: (0426) 21065 | Email: kanwil.sulbar@kemenag.go.id
─────────────────────────────────────────────────────
```

### Judul
```
LAPORAN KINERJA HARIAN (LKH)
atau
LAPORAN TUGAS LANGSUNG ATASAN (TLA)
```

### Informasi ASN
```
Nama        : Faisal Kassim, S.Kom
NIP         : 199203152020121001
Jabatan     : Penyuluh Agama
Unit Kerja  : Seksi Bimas Islam
Periode     : 1 Januari 2026 s.d. 31 Januari 2026
```

### Tabel Kegiatan

#### Untuk LKH (Kinerja Harian):
| No | Tanggal | Waktu | Durasi | Kegiatan | Realisasi | Bukti Dukung |
|----|---------|-------|--------|----------|-----------|--------------|
| 1 | 25 Januari 2026 | 08:00 - 10:00 | 2 jam 0 menit | Membuat laporan | 1 dokumen | Ada |
| 2 | 26 Januari 2026 | 13:00 - 15:00 | 2 jam 0 menit | Survei lapangan | 2 lokasi | Ada |
| **TOTAL DURASI:** | | | | | **24.5 Jam** | |

#### Untuk TLA (Tugas Langsung Atasan):
| No | Tanggal | Waktu | Durasi | Tugas | Bukti Dukung |
|----|---------|-------|--------|-------|--------------|
| 1 | 25 Januari 2026 | 10:00 - 11:00 | 1 jam 0 menit | Rapat koordinasi | Ada |
| 2 | 26 Januari 2026 | 15:00 - 16:00 | 1 jam 0 menit | Briefing tim | - |
| **TOTAL DURASI:** | | | | **5.5 Jam** | |

### Tanda Tangan
```
Mengetahui,                              Mamuju, 25 Januari 2026
Atasan Langsung                          Yang Melaporkan




─────────────────                        ─────────────────
Dr. Ahmad Fauzi, M.Si                    Faisal Kassim, S.Kom
NIP. 197805122008011001                  NIP. 199203152020121001
```

---

## 🔧 TECHNICAL DETAILS

### Function: `cetakLaporanHTML()`

**Location:** `/atasan/kinerja-bawahan/page.tsx`

**Parameters:**
- `data` - Data laporan dari API
- `tipe` - 'KH' atau 'TLA'

**Process Flow:**
```
1. Open new window (window.open)
2. Generate HTML template
3. Inject data ke template
4. Write HTML to window
5. User dapat print atau save PDF
```

### API Endpoints Used:
```
GET /api/atasan/v2/kinerja-bawahan/cetak-kh/{userId}?tanggal_mulai=...&tanggal_akhir=...
GET /api/atasan/v2/kinerja-bawahan/cetak-tla/{userId}?tanggal_mulai=...&tanggal_akhir=...
```

### Response Structure:
```json
{
  "message": "Data laporan KH",
  "data": {
    "atasan": {
      "nama": "Dr. Ahmad Fauzi, M.Si",
      "nip": "197805122008011001",
      "jabatan": "Kepala Bidang"
    },
    "asn": {
      "nama": "Faisal Kassim, S.Kom",
      "nip": "199203152020121001",
      "jabatan": "Penyuluh Agama",
      "unit_kerja": "Seksi Bimas Islam"
    },
    "periode": {
      "tanggal_mulai": "2026-01-01",
      "tanggal_akhir": "2026-01-31"
    },
    "progres": [
      {
        "tanggal": "2026-01-25",
        "jam_mulai": "08:00",
        "jam_selesai": "10:00",
        "durasi_menit": 120,
        "kegiatan": "Membuat laporan bulanan",
        "realisasi": 1,
        "satuan": "dokumen",
        "bukti_dukung": "https://drive.google.com/..."
      }
    ],
    "total_durasi_jam": 24.5
  }
}
```

---

## 🎨 STYLING & PRINT

### CSS Print Media Query
```css
@media print {
  @page {
    size: A4;
    margin: 2cm;
  }
  body {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .no-print {
    display: none;  /* Hide print button */
  }
}
```

### Font & Formatting:
- **Font**: Times New Roman (formal document)
- **Font Size**: 12pt body, 14pt headers
- **Line Height**: 1.5
- **Border**: Black 1px solid
- **Table**: Full width, collapsed borders

---

## 📱 BROWSER COMPATIBILITY

### Tested On:
- ✅ Chrome/Edge (Chromium) - Recommended
- ✅ Firefox
- ✅ Safari
- ⚠️ Internet Explorer - Not tested (deprecated)

### Print Features:
- ✅ Print to PDF
- ✅ Print to printer
- ✅ Page break control
- ✅ Color preservation
- ✅ Responsive layout

---

## 🚨 TROUBLESHOOTING

### Issue: Pop-up Blocker
**Symptom:** Window cetak tidak muncul

**Solution:**
1. Allow pop-up untuk situs ini
2. Browser akan menampilkan notifikasi untuk allow pop-up
3. Klik allow dan coba lagi

### Issue: Data Kosong
**Symptom:** Laporan muncul tapi tabel kosong

**Solution:**
- Check periode yang dipilih
- Pastikan ASN ada data progres di periode tersebut
- Text akan muncul: "Tidak ada data untuk periode ini"

### Issue: Format Print Tidak Rapi
**Symptom:** Layout berantakan saat print

**Solution:**
1. Use Chrome atau Edge (recommended)
2. Set print options: A4, Portrait, Margins: Default
3. Disable "Headers and footers" (optional)

---

## 📊 PERIODE LAPORAN

### Mode Harian:
- Laporan untuk 1 hari tertentu
- Periode: tanggal yang sama untuk mulai & akhir

### Mode Bulanan:
- Laporan untuk 1 bulan penuh
- Periode: Hari pertama s.d. hari terakhir bulan tersebut

**Example:**
- **Harian**: 25 Januari 2026 s.d. 25 Januari 2026
- **Bulanan**: 1 Januari 2026 s.d. 31 Januari 2026

---

## 🔐 SECURITY

### Access Control:
- ✅ Only ATASAN role can access
- ✅ Can only print reports for bawahan (verified via `master_atasan`)
- ✅ 403 error if trying to print non-bawahan

### Data Protection:
- ✅ Data fetched via authenticated API
- ✅ No data stored in browser
- ✅ Window closed after print/save

---

## 📝 SAMPLE USE CASES

### Use Case 1: Monthly Report
**Scenario:** Atasan ingin cetak laporan bulanan untuk review kinerja

**Steps:**
1. Pilih mode "Bulanan"
2. Pilih bulan dan tahun
3. Klik "📄 KH" pada ASN yang diinginkan
4. Review laporan
5. Klik "🖨️ Cetak" → Save as PDF
6. File PDF tersimpan untuk arsip

### Use Case 2: Daily Report
**Scenario:** Atasan ingin cetak laporan harian untuk verifikasi

**Steps:**
1. Pilih mode "Harian"
2. Pilih tanggal tertentu
3. Klik "📄 KH" untuk LKH atau "📄 TLA" untuk TLA
4. Print langsung ke printer

### Use Case 3: Bulk Print
**Scenario:** Atasan ingin cetak laporan semua bawahan

**Steps:**
1. Pilih periode (bulanan)
2. Loop untuk setiap ASN:
   - Klik "📄 KH"
   - Save as PDF dengan nama file: `LKH_NamaASN_Bulan.pdf`
3. Simpan semua PDF untuk arsip

---

## 🎯 FUTURE ENHANCEMENTS (Optional)

### Phase 2:
1. **Batch Print** - Cetak multiple ASN sekaligus
2. **Custom Template** - Pilih template cetak (formal/informal)
3. **Email Send** - Kirim laporan via email langsung
4. **Export Excel** - Download dalam format Excel
5. **Digital Signature** - Tanda tangan digital untuk laporan

---

## ✅ CHECKLIST PRODUKSI

### Pre-Deploy:
- [x] Template HTML created
- [x] Print functionality implemented
- [x] CSS print media query added
- [x] Browser compatibility tested
- [x] Pop-up blocker handling added

### Post-Deploy Verification:
```bash
# 1. Test cetak KH
Login as ATASAN → Kinerja Bawahan → Pilih ASN → Klik "📄 KH"
Expected: Window baru dengan laporan muncul

# 2. Test cetak TLA
Login as ATASAN → Kinerja Bawahan → Pilih ASN → Klik "📄 TLA"
Expected: Window baru dengan laporan muncul

# 3. Test print to PDF
Click "🖨️ Cetak" → Choose "Save as PDF"
Expected: PDF file downloaded

# 4. Verify data accuracy
Check: Nama, NIP, Jabatan, Periode, Kegiatan, Total Durasi
Expected: All data correct and formatted properly
```

---

## 📞 SUPPORT

**Fitur Status:** 🟢 **PRODUCTION READY**

**Documentation Updated:** 2026-01-25

**Known Issues:** None

**Browser Recommendation:** Chrome/Edge Chromium-based

---

## 🎉 SUMMARY

Fitur cetak laporan KH & TLA telah **SELESAI** diimplementasikan dengan:

✅ **Template Resmi** - Kop surat Kemenag + format formal
✅ **Print Ready** - Langsung print atau save PDF
✅ **Responsive** - Layout optimal untuk A4
✅ **Secure** - Verified access via master_atasan
✅ **User Friendly** - Satu klik untuk cetak
✅ **Professional** - Dengan tanda tangan dan metadata lengkap

**Status:** READY FOR PRODUCTION USE! 🚀
