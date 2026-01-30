# INSTALL DOMPDF - Panduan Instalasi

## Langkah Instalasi

Jalankan command berikut di terminal/command prompt:

```bash
cd c:\xampp\htdocs\gaspul\gaspul_api
composer require barryvdh/laravel-dompdf
```

## Verifikasi Instalasi

Setelah instalasi, pastikan file `composer.json` sudah memiliki entry:

```json
"require": {
    "barryvdh/laravel-dompdf": "^3.0"
}
```

## Konfigurasi (Opsional)

Publish konfigurasi dompdf (jika diperlukan):

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

## Test Instalasi

Buat route test di `routes/web.php`:

```php
Route::get('/test-pdf', function() {
    $pdf = Pdf::loadView('test-pdf');
    return $pdf->download('test.pdf');
});
```

## Status

✅ Library: barryvdh/laravel-dompdf (recommended untuk Laravel 10+)
✅ Stable: Ya
✅ Shared Hosting Compatible: Ya
✅ Paper Size: A4, Letter, Legal
✅ Orientation: Portrait, Landscape
