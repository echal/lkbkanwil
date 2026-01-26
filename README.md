# Sistem Laporan Kinerja Harian (GASPUL)

**Kanwil Kementerian Agama Provinsi Sulawesi Barat**

Aplikasi web untuk manajemen dan pelaporan kinerja harian pegawai berbasis Laravel & Next.js.

---

## 📋 Deskripsi

Sistem Laporan Kinerja Harian adalah aplikasi manajemen kinerja yang memungkinkan:
- ASN mencatat dan melaporkan kegiatan harian
- Atasan memonitor kinerja bawahan
- Admin mengelola master data dan users
- Cetak laporan kinerja dalam format PDF

---

## 🏗️ Struktur Project (Monorepo)

```
gaspul/
├── gaspul_api/          # Backend Laravel 11
├── gaspul_lkh/          # Frontend Next.js 14
├── gaspul_frontend/     # Legacy frontend (optional)
└── README.md
```

---

## 🚀 Tech Stack

### Backend (gaspul_api)
- **Framework:** Laravel 11
- **Database:** MySQL 8.0
- **Authentication:** Laravel Sanctum (Token-based)
- **PDF Generation:** DomPDF
- **API:** RESTful API

### Frontend (gaspul_lkh)
- **Framework:** Next.js 14 (App Router)
- **Language:** TypeScript
- **Styling:** Tailwind CSS 4
- **State Management:** React Context
- **HTTP Client:** Fetch API

---

## 📦 Instalasi

### Prasyarat
- PHP >= 8.2
- Composer
- Node.js >= 18.x
- MySQL >= 8.0
- Git

### 1. Clone Repository

```bash
git clone https://github.com/echal/lkbkanwil.git
cd lkbkanwil
```

### 2. Setup Backend (Laravel)

```bash
cd gaspul_api

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Konfigurasi database di .env
# DB_DATABASE=gaspul_api
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations
php artisan migrate

# Seed initial data (optional)
php artisan db:seed

# Start server
php artisan serve
# Server berjalan di http://localhost:8000
```

### 3. Setup Frontend (Next.js)

```bash
cd gaspul_lkh

# Install dependencies
npm install

# Copy environment file
cp .env.example .env.local

# Konfigurasi API URL di .env.local
# NEXT_PUBLIC_API_URL=http://localhost:8000/api

# Start development server
npm run dev
# Server berjalan di http://localhost:3000
```

---

## 🔑 Default Credentials (Development)

Setelah seeding database:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@kemenag.go.id | password |
| ASN | asn@kemenag.go.id | password |
| Atasan | atasan@kemenag.go.id | password |

**⚠️ PENTING:** Ubah credentials default sebelum production!

---

## 📚 Dokumentasi

Dokumentasi lengkap tersedia di:
- [Production Readiness Checklist](PRODUCTION_READINESS_CHECKLIST.md)
- [Implementation Guide](IMPLEMENTATION_GUIDE.md)
- [Kinerja Bawahan Documentation](KINERJA_BAWAHAN_DOCUMENTATION.md)
- [Fitur Cetak Laporan](FITUR_CETAK_LAPORAN.md)

---

## 🔒 Keamanan

### File Sensitif (TIDAK BOLEH DI-PUSH)
- ❌ `.env` files
- ❌ Database credentials
- ❌ API keys & tokens
- ❌ Private keys (*.key, *.pem)
- ❌ SQL backup files

### Sudah Dikonfigurasi
- ✅ `.gitignore` komprehensif
- ✅ `.env.example` untuk template
- ✅ Token-based authentication
- ✅ Input validation & sanitization
- ✅ CORS protection

---

## 🛠️ Development

### Backend Commands

```bash
# Run migrations
php artisan migrate

# Create migration
php artisan make:migration create_table_name

# Create controller
php artisan make:controller ControllerName

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Frontend Commands

```bash
# Development
npm run dev

# Build for production
npm run build

# Start production server
npm run start

# Lint
npm run lint
```

---

## 📊 Fitur Utama

### Role: ASN
- ✅ Input Rencana Kerja Harian (RKH)
- ✅ Input Progres Harian
- ✅ Upload bukti kegiatan
- ✅ Lihat riwayat laporan
- ✅ Cetak laporan kinerja (PDF)

### Role: Atasan
- ✅ Monitor kinerja bawahan
- ✅ Filter laporan per tanggal/bulan
- ✅ Validasi dan approval progres
- ✅ Export data kinerja

### Role: Admin
- ✅ Manajemen users
- ✅ Manajemen master data
- ✅ Konfigurasi sistem
- ✅ Audit log

---

## 🚀 Deployment

### Backend Deployment

1. Upload ke server production
2. Run `composer install --optimize-autoloader --no-dev`
3. Set `APP_ENV=production` dan `APP_DEBUG=false`
4. Run `php artisan migrate --force`
5. Run `php artisan config:cache`
6. Run `php artisan route:cache`
7. Configure web server (Apache/Nginx)

### Frontend Deployment

1. Build project: `npm run build`
2. Upload folder `.next`, `public`, `package.json`
3. Install dependencies: `npm install --production`
4. Start: `npm run start`
5. Configure reverse proxy

Atau deploy ke Vercel:
```bash
vercel deploy --prod
```

---

## 🤝 Kontribusi

Untuk kontribusi internal Kanwil Kemenag Sulbar:
1. Fork repository
2. Buat branch fitur: `git checkout -b feature/nama-fitur`
3. Commit perubahan: `git commit -m 'feat: deskripsi fitur'`
4. Push ke branch: `git push origin feature/nama-fitur`
5. Buat Pull Request

---

## 📝 License

Proprietary - © 2026 Kanwil Kementerian Agama Provinsi Sulawesi Barat

**Hak Cipta Dilindungi.** Aplikasi ini adalah milik Kementerian Agama dan hanya digunakan untuk kepentingan internal instansi.

---

## 📞 Kontak

**Pengembang:** Tim IT Kanwil Kemenag Sulbar

**Support:** Hubungi bagian IT untuk bantuan teknis.

---

## 🔄 Changelog

### Version 1.0.0 (Januari 2026)
- ✅ Implementasi sistem login resmi dengan branding Kemenag
- ✅ Fitur laporan kinerja harian lengkap
- ✅ Fitur cetak laporan PDF
- ✅ Dashboard monitoring untuk atasan
- ✅ Manajemen master data
- ✅ Sterilisasi UI login untuk production
- ✅ Production-ready deployment

---

**Status:** ✅ Production Ready

**Last Updated:** 26 Januari 2026
