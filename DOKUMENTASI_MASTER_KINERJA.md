# Dokumentasi Master Kinerja Organisasi
## Aplikasi e-Kinerja ASN (Laravel API + Next.js)

---

## 📚 KONSEP YANG BENAR (Sesuai SAKIP & SKP ASN)

### Hirarki Kinerja

```
🏛️ LEVEL ORGANISASI (ADMIN)
│
├─ Unit Kerja
│   └─ Sasaran Kegiatan (Strategis Organisasi)
│       └─ Indikator Kinerja Organisasi
│
👤 LEVEL ASN (INDIVIDU)
│
├─ SKP Tahunan
│   └─ Triwulan I–IV
│       ├─ Memilih Sasaran Kegiatan (dari master admin)
│       ├─ Memilih Indikator Kinerja (dari master admin)
│       ├─ Target Individu
│       └─ Realisasi
│           └─ Bulanan
│               └─ Harian (Laporan + Bukti)
```

### Contoh Implementasi

**ADMIN (Organisasi):**
- Unit Kerja: `Kantor Wilayah Kementerian Agama Provinsi Jawa Barat`
- Sasaran Kegiatan: `Meningkatnya jaminan beragama, toleransi, dan cinta kemanusiaan umat beragama`
- Indikator Kinerja: `Nilai Indeks Kerukunan Umat Beragama`

**ASN (Individu):**
- Memilih Sasaran & Indikator di atas
- Tahun: `2026`
- Triwulan: `I`
- Target: `85%`
- Realisasi: `Input oleh ASN per bulan/harian`

---

## 🗄️ STRUKTUR DATABASE

### Tabel: `sasaran_kegiatan`

Master data sasaran strategis organisasi/unit kerja

| Field | Type | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| unit_kerja | varchar(255) | Nama unit kerja |
| sasaran_kegiatan | text | Sasaran strategis organisasi |
| status | enum(AKTIF, NONAKTIF) | Status sasaran |
| created_at | timestamp | Waktu dibuat |
| updated_at | timestamp | Waktu diupdate |

**Indexes:**
- `status` - Untuk query sasaran aktif
- `unit_kerja` - Untuk filter per unit

### Tabel: `indikator_kinerja`

Master data indikator kinerja yang terkait dengan sasaran kegiatan

| Field | Type | Deskripsi |
|-------|------|-----------|
| id | bigint | Primary key |
| sasaran_kegiatan_id | bigint | Foreign key ke sasaran_kegiatan |
| indikator_kinerja | text | Indikator kinerja organisasi |
| status | enum(AKTIF, NONAKTIF) | Status indikator |
| created_at | timestamp | Waktu dibuat |
| updated_at | timestamp | Waktu diupdate |

**Indexes:**
- `status` - Untuk query indikator aktif
- `sasaran_kegiatan_id` - Untuk relasi dengan sasaran

**Foreign Key:**
- `sasaran_kegiatan_id` → `sasaran_kegiatan(id)` ON DELETE CASCADE

---

## 🔌 API ENDPOINTS

### Base URL
```
http://localhost:8000/api
```

### Authentication
Semua endpoint memerlukan Bearer Token:
```
Authorization: Bearer {access_token}
```

---

## 📍 SASARAN KEGIATAN ENDPOINTS

### 1. Get All Sasaran Kegiatan (Admin)

**GET** `/admin/sasaran-kegiatan`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "unit_kerja": "Kantor Wilayah Kementerian Agama Prov. Jabar",
      "sasaran_kegiatan": "Meningkatnya jaminan beragama...",
      "status": "AKTIF",
      "jumlah_indikator": 3,
      "digunakan_asn": true,
      "created_at": "2026-01-21T12:00:00Z",
      "updated_at": "2026-01-21T12:00:00Z"
    }
  ]
}
```

### 2. Get Single Sasaran Kegiatan (Admin)

**GET** `/admin/sasaran-kegiatan/{id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "unit_kerja": "Kantor Wilayah Kementerian Agama Prov. Jabar",
    "sasaran_kegiatan": "Meningkatnya jaminan beragama...",
    "status": "AKTIF",
    "jumlah_indikator": 3,
    "digunakan_asn": true,
    "indikator_kinerja": [...],
    "created_at": "2026-01-21T12:00:00Z",
    "updated_at": "2026-01-21T12:00:00Z"
  }
}
```

### 3. Create Sasaran Kegiatan (Admin)

**POST** `/admin/sasaran-kegiatan`

**Request Body:**
```json
{
  "unit_kerja": "Kantor Wilayah Kementerian Agama Prov. Jabar",
  "sasaran_kegiatan": "Meningkatnya jaminan beragama, toleransi...",
  "status": "AKTIF"
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "message": "Sasaran Kegiatan berhasil ditambahkan",
  "data": { ... }
}
```

### 4. Update Sasaran Kegiatan (Admin)

**PUT** `/admin/sasaran-kegiatan/{id}`

**Request Body:**
```json
{
  "unit_kerja": "Kantor Wilayah Kementerian Agama Prov. Jabar",
  "sasaran_kegiatan": "Meningkatnya jaminan beragama...",
  "status": "AKTIF"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Sasaran Kegiatan berhasil diperbarui",
  "data": { ... }
}
```

### 5. Toggle Status Sasaran Kegiatan (Admin)

**PATCH** `/admin/sasaran-kegiatan/{id}/toggle-status`

**Response:**
```json
{
  "success": true,
  "message": "Status Sasaran Kegiatan berhasil diubah menjadi NONAKTIF",
  "data": { ... }
}
```

### 6. Delete Sasaran Kegiatan (Admin)

**DELETE** `/admin/sasaran-kegiatan/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Sasaran Kegiatan berhasil dihapus"
}
```

**Error jika digunakan ASN:**
```json
{
  "success": false,
  "message": "Sasaran Kegiatan tidak dapat dihapus karena sedang digunakan oleh ASN"
}
```

---

## 📍 INDIKATOR KINERJA ENDPOINTS

### 1. Get All Indikator Kinerja (Admin)

**GET** `/admin/indikator-kinerja`

Optional query param: `?sasaran_kegiatan_id=1`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "sasaran_kegiatan_id": 1,
      "sasaran_kegiatan": {
        "id": 1,
        "unit_kerja": "Kantor Wilayah...",
        "sasaran_kegiatan": "Meningkatnya jaminan..."
      },
      "indikator_kinerja": "Nilai Indeks Kerukunan Umat Beragama",
      "status": "AKTIF",
      "digunakan_asn": true,
      "created_at": "2026-01-21T12:00:00Z",
      "updated_at": "2026-01-21T12:00:00Z"
    }
  ]
}
```

### 2. Create Indikator Kinerja (Admin)

**POST** `/admin/indikator-kinerja`

**Request Body:**
```json
{
  "sasaran_kegiatan_id": 1,
  "indikator_kinerja": "Nilai Indeks Kerukunan Umat Beragama",
  "status": "AKTIF"
}
```

**Response:** `201 Created`

### 3. Update, Toggle, Delete

Sama seperti endpoint Sasaran Kegiatan, hanya path-nya:
- **PUT** `/admin/indikator-kinerja/{id}`
- **PATCH** `/admin/indikator-kinerja/{id}/toggle-status`
- **DELETE** `/admin/indikator-kinerja/{id}`

---

## 🧑‍💻 FRONTEND IMPLEMENTATION

### Struktur Folder Next.js

```
gaspul_lkh/
├── app/
│   ├── admin/
│   │   ├── dashboard/
│   │   │   └── page.tsx             # Dashboard Admin dengan menu
│   │   ├── sasaran-kegiatan/
│   │   │   ├── page.tsx             # List Sasaran Kegiatan
│   │   │   ├── tambah/
│   │   │   │   └── page.tsx         # Form Tambah
│   │   │   └── edit/[id]/
│   │   │       └── page.tsx         # Form Edit
│   │   └── indikator-kinerja/
│   │       ├── page.tsx             # List Indikator (by Sasaran)
│   │       ├── tambah/
│   │       │   └── page.tsx         # Form Tambah
│   │       └── edit/[id]/
│   │           └── page.tsx         # Form Edit
│   └── lib/
│       ├── api.ts                   # Base API fetch
│       └── master-kinerja-api.ts    # API functions untuk Master Kinerja
```

### Contoh Penggunaan API di Frontend

```typescript
import {
  getSasaranKegiatanList,
  createSasaranKegiatan
} from '@/app/lib/master-kinerja-api';

// Load data
const loadData = async () => {
  const data = await getSasaranKegiatanList();
  setSasaranList(data);
};

// Create new
const handleSubmit = async (formData) => {
  await createSasaranKegiatan(formData);
  router.push('/admin/sasaran-kegiatan');
};
```

---

## 🔐 ROLE & PERMISSION

### ADMIN
- ✅ CRUD Sasaran Kegiatan
- ✅ CRUD Indikator Kinerja
- ✅ Toggle status AKTIF/NONAKTIF
- ❌ Delete jika sudah digunakan ASN

### ASN
- ✅ View Sasaran Kegiatan (aktif saja)
- ✅ View Indikator Kinerja (aktif saja)
- ✅ Memilih saat membuat SKP
- ❌ CRUD Master Data

---

## ⚠️ VALIDASI & BUSINESS RULES

### Sasaran Kegiatan
1. Unit Kerja wajib diisi (max 255 char)
2. Sasaran Kegiatan wajib diisi (max 5000 char)
3. Status: AKTIF atau NONAKTIF
4. Tidak bisa dihapus jika:
   - Sudah digunakan oleh ASN
   - Masih punya Indikator Kinerja aktif

### Indikator Kinerja
1. Harus terhubung dengan Sasaran Kegiatan valid
2. Indikator wajib diisi (max 5000 char)
3. Status: AKTIF atau NONAKTIF
4. Tidak bisa dihapus jika sudah digunakan oleh ASN
5. Cascade delete: jika Sasaran dihapus, Indikator ikut terhapus

---

## 🚀 CARA MENJALANKAN

### Backend (Laravel)

```bash
cd gaspul_api

# Install dependencies
composer install

# Setup .env
cp .env.example .env
php artisan key:generate

# Run migration
php artisan migrate

# Start server
php artisan serve
```

### Frontend (Next.js)

```bash
cd gaspul_lkh

# Install dependencies
npm install

# Setup .env.local
echo "NEXT_PUBLIC_API_URL=http://localhost:8000/api" > .env.local

# Start dev server
npm run dev
```

---

## 📊 FLOW PENGGUNAAN

### 1. Admin Setup Master Data

```
1. Login sebagai ADMIN
2. Dashboard → Manajemen Kinerja → Sasaran Kegiatan
3. Tambah Sasaran Kegiatan baru
4. Tambah Indikator Kinerja untuk sasaran tersebut
5. Set status AKTIF agar muncul di form ASN
```

### 2. ASN Menggunakan Master Data

```
1. Login sebagai ASN
2. Buat SKP Triwulan baru
3. Pilih Sasaran Kegiatan (dropdown berisi data dari admin)
4. Pilih Indikator Kinerja (otomatis filter berdasarkan sasaran)
5. Tentukan Target & Realisasi
6. Input laporan bulanan & harian
```

---

## 🎯 CHECKLIST IMPLEMENTASI

### Backend (Laravel) ✅
- [x] Migration `sasaran_kegiatan` & `indikator_kinerja`
- [x] Model `SasaranKegiatan` dengan relasi
- [x] Model `IndikatorKinerja` dengan relasi
- [x] Controller `SasaranKegiatanController` (CRUD + Toggle)
- [x] Controller `IndikatorKinerjaController` (CRUD + Toggle)
- [x] Run migration

### Frontend (Next.js) ⏳
- [x] API functions di `master-kinerja-api.ts`
- [ ] Halaman List Sasaran Kegiatan
- [ ] Halaman Form Tambah/Edit Sasaran
- [ ] Halaman List Indikator Kinerja
- [ ] Halaman Form Tambah/Edit Indikator
- [ ] Update menu di Admin Dashboard

### Routes (Laravel) ⏳
- [ ] Tambahkan routes ke `routes/api.php`

---

## 📝 NEXT STEPS

1. ✅ ~~Hapus file RHKP yang salah konsep~~
2. ✅ ~~Buat struktur database yang benar~~
3. ✅ ~~Implement backend API~~
4. ⏳ **Tambahkan routes di Laravel**
5. ⏳ **Buat halaman frontend Admin**
6. ⏳ **Testing end-to-end**
7. ⏳ **Implement ASN side (pilih sasaran & indikator)**

---

## 📞 SUPPORT

Jika ada pertanyaan atau issue:
1. Cek dokumentasi ini terlebih dahulu
2. Review kode di Model & Controller
3. Test API menggunakan Postman/Insomnia
4. Debug dengan `php artisan tinker`

---

**Dokumentasi ini dibuat untuk memastikan konsep SAKIP & SKP ASN diimplementasikan dengan benar.**

**Tanggal:** 21 Januari 2026
**Versi:** 1.0.0
