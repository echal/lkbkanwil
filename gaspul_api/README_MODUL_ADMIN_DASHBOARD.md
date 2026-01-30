# 📊 MODUL DASHBOARD ADMIN - COMPLETE

## ✅ STATUS: IMPLEMENTED

Dokumentasi lengkap 4 modul Admin Dashboard untuk Sistem Kinerja ASN Kanwil Kemenag Sulbar.

---

## 1️⃣ SASARAN KEGIATAN

### Struktur Database
```sql
CREATE TABLE sasaran_kegiatan (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    kode_sasaran VARCHAR(20) UNIQUE NOT NULL,
    nama_sasaran VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    status ENUM('AKTIF','NONAKTIF') DEFAULT 'AKTIF',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_status (status)
);
```

### Model
```php
// app/Models/SasaranKegiatan.php
class SasaranKegiatan extends Model
{
    protected $table = 'sasaran_kegiatan';
    protected $fillable = ['kode_sasaran', 'nama_sasaran', 'deskripsi', 'status'];

    public function indikatorKinerja(): HasMany
    {
        return $this->hasMany(IndikatorKinerja::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'AKTIF');
    }
}
```

### Controller Methods
```php
// app/Http/Controllers/Admin/SasaranKegiatanController.php
index()    - List semua sasaran dengan relasi indikator
create()   - Form tambah
store()    - Simpan data baru (validasi unique kode)
edit($id)  - Form edit
update()   - Update data (validasi unique kode kecuali diri sendiri)
destroy()  - Hapus data
```

### Routes
```php
Route::resource('sasaran-kegiatan', SasaranKegiatanController::class);
```

### View Files
```
admin/sasaran-kegiatan/
├── index.blade.php  - Tabel list
├── tambah.blade.php - Form create
└── edit.blade.php   - Form update
```

---

## 2️⃣ INDIKATOR KINERJA

### Struktur Database
```sql
CREATE TABLE indikator_kinerja (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    sasaran_kegiatan_id BIGINT NOT NULL,
    kode_indikator VARCHAR(20) UNIQUE NOT NULL,
    nama_indikator VARCHAR(255) NOT NULL,
    satuan VARCHAR(50) NOT NULL,
    tipe_target ENUM('ANGKA','DOKUMEN','PERSENTASE') DEFAULT 'ANGKA',
    status ENUM('AKTIF','NONAKTIF') DEFAULT 'AKTIF',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (sasaran_kegiatan_id) REFERENCES sasaran_kegiatan(id) ON DELETE RESTRICT,
    INDEX idx_sasaran_status (sasaran_kegiatan_id, status)
);
```

### Model
```php
// app/Models/IndikatorKinerja.php
class IndikatorKinerja extends Model
{
    protected $table = 'indikator_kinerja';
    protected $fillable = [
        'sasaran_kegiatan_id', 'kode_indikator', 'nama_indikator',
        'satuan', 'tipe_target', 'status'
    ];

    public function sasaranKegiatan(): BelongsTo
    {
        return $this->belongsTo(SasaranKegiatan::class);
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'AKTIF');
    }
}
```

### Controller Methods
```php
// app/Http/Controllers/Admin/IndikatorKinerjaController.php
index()    - List dengan eager load sasaran_kegiatan
create()   - Form dengan dropdown sasaran aktif
store()    - Validasi FK sasaran_kegiatan_id
edit($id)  - Form edit dengan dropdown sasaran
update()   - Update dengan validasi unique kode
destroy()  - Hapus
```

### Routes
```php
Route::resource('indikator-kinerja', IndikatorKinerjaController::class);
```

---

## 3️⃣ UNIT KERJA

### Struktur Database
```sql
CREATE TABLE unit_kerja (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    kode_unit VARCHAR(20) UNIQUE NOT NULL,
    nama_unit VARCHAR(255) NOT NULL,
    eselon VARCHAR(20) NULL,
    status ENUM('AKTIF','NONAKTIF') DEFAULT 'AKTIF',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_status (status)
);
```

### Model
```php
// app/Models/UnitKerja.php
class UnitKerja extends Model
{
    protected $table = 'unit_kerja';
    protected $fillable = ['kode_unit', 'nama_unit', 'eselon', 'status'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'unit_kerja_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'AKTIF');
    }
}
```

### Controller Methods
```php
// app/Http/Controllers/Admin/UnitKerjaController.php
index()    - List unit dengan count pegawai
create()   - Form tambah
store()    - Simpan dengan validasi unique kode
edit($id)  - Form edit
update()   - Update
destroy()  - Hapus (check FK users)
```

---

## 4️⃣ DATA PEGAWAI

### Struktur Database (Update users table)
```sql
ALTER TABLE users ADD COLUMN (
    unit_kerja_id BIGINT NULL,
    jabatan VARCHAR(255) NULL,
    status_pegawai ENUM('AKTIF','NONAKTIF') DEFAULT 'AKTIF',
    FOREIGN KEY (unit_kerja_id) REFERENCES unit_kerja(id) ON DELETE SET NULL,
    INDEX idx_unit (unit_kerja_id),
    INDEX idx_status_pegawai (status_pegawai)
);
```

### Model Relation
```php
// app/Models/User.php
protected $fillable = [
    'name', 'email', 'password', 'role', 'nip',
    'unit_kerja_id', 'jabatan', 'status_pegawai'
];

public function unitKerja(): BelongsTo
{
    return $this->belongsTo(UnitKerja::class);
}
```

### Controller Methods
```php
// app/Http/Controllers/Admin/PegawaiController.php
index()    - List pegawai dengan unit_kerja
create()   - Form dengan dropdown unit & role
store()    - Create user (hash password)
edit($id)  - Form edit
update()   - Update (conditional password)
destroy()  - Hapus (check relasi)
```

---

## 🔐 SECURITY - Admin Only Middleware

Semua route menggunakan middleware:
```php
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:ADMIN'])
    ->group(function () {
        // All admin routes here
    });
```

---

## 📋 BLADE VIEWS TEMPLATE

### Index Template (Example)
```blade
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Sasaran Kegiatan</h1>
        <a href="{{ route('admin.sasaran-kegiatan.create') }}"
           class="px-4 py-2 bg-green-600 text-white rounded">
            Tambah
        </a>
    </div>

    <!-- Success Message -->
    @if(session('success'))
    <div class="bg-green-100 p-4 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    <!-- Table -->
    <table class="w-full bg-white shadow rounded">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2">No</th>
                <th class="px-4 py-2">Kode</th>
                <th class="px-4 py-2">Nama Sasaran</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sasaran as $item)
            <tr>
                <td class="px-4 py-2">{{ $loop->iteration }}</td>
                <td class="px-4 py-2">{{ $item->kode_sasaran }}</td>
                <td class="px-4 py-2">{{ $item->nama_sasaran }}</td>
                <td class="px-4 py-2">
                    <span class="px-2 py-1 rounded text-xs
                        {{ $item->status == 'AKTIF' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $item->status }}
                    </span>
                </td>
                <td class="px-4 py-2">
                    <a href="{{ route('admin.sasaran-kegiatan.edit', $item->id) }}"
                       class="text-blue-600">Edit</a>
                    <form action="{{ route('admin.sasaran-kegiatan.destroy', $item->id) }}"
                          method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button onclick="return confirm('Yakin hapus?')"
                                class="text-red-600">Hapus</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
```

### Form Template (Example)
```blade
@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4">
    <h1 class="text-2xl font-bold mb-6">Tambah Sasaran Kegiatan</h1>

    <form action="{{ route('admin.sasaran-kegiatan.store') }}" method="POST"
          class="bg-white p-6 rounded shadow">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Kode Sasaran</label>
            <input type="text" name="kode_sasaran" required
                   class="w-full px-4 py-2 border rounded"
                   value="{{ old('kode_sasaran') }}">
            @error('kode_sasaran')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Nama Sasaran</label>
            <input type="text" name="nama_sasaran" required
                   class="w-full px-4 py-2 border rounded"
                   value="{{ old('nama_sasaran') }}">
            @error('nama_sasaran')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Deskripsi</label>
            <textarea name="deskripsi" rows="3"
                      class="w-full px-4 py-2 border rounded">{{ old('deskripsi') }}</textarea>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Status</label>
            <select name="status" class="w-full px-4 py-2 border rounded">
                <option value="AKTIF">AKTIF</option>
                <option value="NONAKTIF">NONAKTIF</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit"
                    class="px-6 py-2 bg-green-600 text-white rounded">
                Simpan
            </button>
            <a href="{{ route('admin.sasaran-kegiatan.index') }}"
               class="px-6 py-2 bg-gray-200 rounded">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
```

---

## 🚀 INSTALASI

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Sample Data (Optional)
```bash
php artisan db:seed --class=AdminModuleSeeder
```

### 3. Verify Routes
```bash
php artisan route:list --name=admin
```

Expected:
```
admin.sasaran-kegiatan.index
admin.sasaran-kegiatan.create
admin.sasaran-kegiatan.store
admin.sasaran-kegiatan.edit
admin.sasaran-kegiatan.update
admin.sasaran-kegiatan.destroy

admin.indikator-kinerja.index
... (similar for others)

admin.unit-kerja.index
...

admin.pegawai.index
...
```

---

## ✅ TESTING CHECKLIST

### Sasaran Kegiatan
- [ ] List tampil semua data
- [ ] Tambah data baru sukses
- [ ] Edit data sukses
- [ ] Hapus data sukses
- [ ] Validasi unique kode_sasaran
- [ ] Status AKTIF/NONAKTIF

### Indikator Kinerja
- [ ] List dengan nama sasaran
- [ ] Dropdown sasaran hanya yang aktif
- [ ] Validasi FK sasaran_kegiatan_id
- [ ] Tipe target (ANGKA/DOKUMEN/PERSENTASE)

### Unit Kerja
- [ ] List unit dengan count pegawai
- [ ] Validasi unique kode_unit
- [ ] Eselon opsional

### Data Pegawai
- [ ] List dengan nama unit
- [ ] Dropdown unit & role
- [ ] Password ter-hash
- [ ] Edit tanpa ubah password (jika kosong)
- [ ] Filter by unit/role

---

## 📝 ASUMSI & CATATAN

1. **Tidak mengubah modul lain** - Progres Harian, TLA, status warna tetap utuh
2. **Middleware Admin** - Semua route dilindungi role ADMIN
3. **Soft Delete** - Tidak digunakan (hard delete)
4. **Validasi FK** - ON DELETE RESTRICT untuk sasaran/indikator, SET NULL untuk unit/user
5. **Password** - Harus di-hash dengan bcrypt
6. **UI Framework** - Tailwind CSS (sesuai existing)
7. **Alpine.js** - Untuk interaktivity jika diperlukan
8. **Relasi Master-Detail** - Sasaran → Indikator, Unit → Pegawai

---

**Status:** ✅ READY FOR IMPLEMENTATION
**Date:** 28 Januari 2026
**Version:** Admin Modules v1.0
