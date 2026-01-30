# NIP Unique Index

## Migration: 2026_01_28_221736_add_unique_index_to_users_nip_column.php

### Tujuan
Menambahkan unique index pada kolom `nip` di tabel `users` untuk mencegah duplikasi NIP pegawai.

### Fitur
- ✅ NIP yang sama tidak bisa digunakan oleh lebih dari 1 pegawai
- ✅ Multiple NULL values diperbolehkan (untuk user yang belum punya NIP)
- ✅ Database akan otomatis reject jika ada attempt insert/update NIP duplikat

### Cara Rollback
Jika perlu menghapus unique constraint:
```bash
php artisan migrate:rollback --step=1
```

### Testing
1. **Test Insert Duplikat** (harus gagal):
```php
$user = User::create([
    'nip' => '198201262008011013', // NIP yang sudah ada
    // ... field lainnya
]);
// Error: SQLSTATE[23000]: Integrity constraint violation
```

2. **Test Update ke NIP yang sudah ada** (harus gagal via validation):
```php
// Di PegawaiController sudah ada validasi:
'nip' => ['required', 'max:18', Rule::unique('users')->ignore($pegawai)]
```

### Catatan
- Migration ini dijalankan setelah membersihkan data duplikat yang ada
- Sebelum migration, user ID 7 (Admin LKB) yang memiliki NIP duplikat sudah diset ke NULL
