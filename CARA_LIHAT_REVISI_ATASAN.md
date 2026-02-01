# Cara ATASAN Melihat Permintaan Revisi SKP

## 🎯 LOKASI MELIHAT PERMINTAAN REVISI

### Metode 1: Melalui Dashboard (Notifikasi Box)
1. Login sebagai **ATASAN** (role PIMPINAN)
2. Buka menu **SKP Tahunan** → **Daftar SKP Tahunan Bawahan**
3. Di bagian atas halaman, akan muncul **alert box orange** jika ada permintaan revisi:
   ```
   🔔 X Permintaan Revisi Menunggu Persetujuan
   ASN mengajukan permintaan untuk merevisi SKP yang sudah disetujui
   ```
4. Klik link **"Lihat Semua"** pada alert box untuk filter otomatis

### Metode 2: Menggunakan Filter Status
1. Login sebagai **ATASAN**
2. Buka menu **SKP Tahunan** → **Daftar SKP Tahunan Bawahan**
3. Di bagian **Filter**, pilih dropdown **Status**
4. Pilih **"🔔 Revisi Diajukan"**
5. Klik tombol **Filter**
6. Sistem akan menampilkan semua SKP dengan status **REVISI_DIAJUKAN**

### Metode 3: Lihat di Tabel (Badge Orange)
1. Login sebagai **ATASAN**
2. Buka menu **SKP Tahunan** → **Daftar SKP Tahunan Bawahan**
3. Pada tabel, cari baris dengan badge **status orange** bertuliskan:
   ```
   🔔 Revisi Diajukan
   ```
4. Klik tombol **"Detail"** pada baris tersebut

---

## 📋 CARA APPROVE/REJECT PERMINTAAN REVISI

### Step 1: Buka Detail SKP
1. Klik tombol **"Detail"** pada SKP dengan status **REVISI_DIAJUKAN**
2. Halaman detail akan terbuka

### Step 2: Lihat Informasi Revisi
Pada halaman detail, Anda akan melihat **section orange** di atas:
- **Judul:** "Permintaan Revisi SKP Tahunan"
- **Alasan Revisi dari ASN:** Alasan yang ditulis ASN
- **Tanggal Pengajuan:** Kapan revisi diajukan

### Step 3A: SETUJUI Revisi
1. Klik tombol **"Setujui Revisi"** (hijau)
2. Form akan muncul
3. Isi **Catatan Persetujuan Revisi** (opsional)
4. Klik **"Konfirmasi Setujui Revisi"**
5. Konfirmasi pop-up akan muncul → Klik **OK**
6. ✅ **Hasil:** SKP kembali ke status **DRAFT** dan ASN dapat mengedit

### Step 3B: TOLAK Revisi
1. Klik tombol **"Tolak Revisi"** (merah)
2. Form akan muncul
3. Isi **Alasan Penolakan Revisi** (WAJIB, minimal 10 karakter)
4. Klik **"Konfirmasi Tolak Revisi"**
5. Konfirmasi pop-up akan muncul → Klik **OK**
6. ✅ **Hasil:** SKP tetap **DISETUJUI**, status menjadi **REVISI_DITOLAK**, dan ASN tidak dapat mengedit

---

## 🔍 STATUS BADGE YANG ADA

| Status Badge | Warna | Arti |
|-------------|-------|------|
| **Draft** | Abu-abu | SKP masih draft, ASN sedang menyusun |
| **Menunggu Persetujuan** | Kuning | SKP baru diajukan, butuh approval |
| **Disetujui** | Hijau | SKP sudah disetujui atasan |
| **Ditolak** | Merah | SKP ditolak, ASN harus perbaiki |
| **🔔 Revisi Diajukan** | Orange | ASN minta izin revisi SKP yang sudah disetujui |
| **Revisi Ditolak** | Ungu | Permintaan revisi ditolak atasan |

---

## ❓ FAQ

**Q: Apa bedanya "Menunggu Persetujuan" dengan "Revisi Diajukan"?**
- **Menunggu Persetujuan** (Kuning): SKP **baru** yang belum pernah disetujui
- **Revisi Diajukan** (Orange): SKP yang **sudah disetujui** sebelumnya, tapi ASN minta izin untuk revisi

**Q: Jika saya setujui revisi, apakah data RHK dan Kinerja Harian akan hilang?**
- **TIDAK.** Data RHK dan Kinerja Harian **TETAP AMAN**, tidak terpengaruh sama sekali.

**Q: Jika saya tolak revisi, apakah ASN bisa ajukan lagi?**
- **YA.** Jika perlu, ASN masih bisa mengajukan permintaan revisi lagi di lain waktu.

**Q: Berapa lama permintaan revisi menunggu approval?**
- **Tidak ada batas waktu.** Permintaan akan tetap pending sampai atasan approve atau reject.

**Q: Apakah saya bisa lihat riwayat revisi?**
- Saat ini belum ada fitur riwayat. Sistem hanya menyimpan data revisi terakhir.

---

## 🚨 PENTING!

### Jika SETUJUI Revisi:
- ✅ SKP kembali ke status **DRAFT**
- ✅ ASN **DAPAT** mengedit SKP (tambah/edit/hapus butir kinerja)
- ✅ RHK dan Kinerja Harian **TETAP ADA** (tidak terpengaruh)
- ⚠️ Setelah ASN selesai edit, mereka harus **mengajukan ulang** SKP untuk approval

### Jika TOLAK Revisi:
- ❌ SKP **TETAP DISETUJUI** (tidak berubah)
- ❌ ASN **TIDAK DAPAT** mengedit SKP
- ❌ Status menjadi **REVISI_DITOLAK**
- ℹ️ Alasan penolakan akan ditampilkan ke ASN

---

## 📞 TROUBLESHOOTING

**Problem:** Tidak muncul notifikasi permintaan revisi
**Solution:**
1. Pastikan ada SKP dengan status **REVISI_DIAJUKAN**
2. Pastikan tahun filter sudah benar
3. Refresh halaman (F5)

**Problem:** Tombol "Setujui Revisi" / "Tolak Revisi" tidak muncul
**Solution:**
1. Pastikan SKP status = **REVISI_DIAJUKAN** (bukan status lain)
2. Pastikan Anda login sebagai **PIMPINAN**
3. Clear cache browser (Ctrl+Shift+Del)

---

**UPDATE:** 2026-02-01
**Developer:** Senior Laravel Engineer
