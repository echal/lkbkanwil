# 🚀 PANDUAN DEPLOYMENT PRODUCTION SERVER

**Server:** s3282 (gaspulco@s3282)
**Database:** gaspulco_lkbkanwil
**User:** gaspul_user
**Application:** GASPUL LKH - Sistem Laporan Kinerja Harian

---

## 📋 LANGKAH DEPLOYMENT (COPY-PASTE READY)

### STEP 1: Pull Latest Code dari GitHub

```bash
# Login ke server
ssh gaspulco@s3282

# Masuk ke folder aplikasi
cd /home/gaspulco/public_html/gaspul_api
# ATAU sesuaikan dengan path aplikasi Anda

# Pull latest code
git pull origin main

# Verify update
git log --oneline -3
# Harus muncul:
# 8e622ff fix: resolve duplicate foreign key constraint error
# e1475c3 chore: full migration to Laravel monolith
```

---

### STEP 2: Update Dependencies

```bash
# Install/update Composer dependencies (production mode)
composer install --no-dev --optimize-autoloader

# Verify DomPDF installed
composer show | grep dompdf
# Harus muncul: barryvdh/laravel-dompdf
```

---

### STEP 3: Configure Environment

```bash
# Backup .env lama
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Edit .env untuk production
nano .env

# Update nilai berikut:
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lkh.kemenag-sulbar.go.id  # sesuaikan domain

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gaspulco_lkbkanwil
DB_USERNAME=gaspul_user
DB_PASSWORD=your_actual_password  # password yang benar!

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_DOMAIN=lkh.kemenag-sulbar.go.id  # sesuaikan domain
SESSION_SECURE_COOKIE=true  # jika pakai HTTPS

CACHE_STORE=database
LOG_CHANNEL=daily

# Save & Exit (Ctrl+X, Y, Enter)
```

---

### STEP 4: Fix Database Credentials (Jika Error Access Denied)

```bash
# Test koneksi database
mysql -u gaspul_user -p gaspulco_lkbkanwil

# Jika error "Access denied", create user:
mysql -u root -p

# Di MySQL prompt:
CREATE USER IF NOT EXISTS 'gaspul_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON gaspulco_lkbkanwil.* TO 'gaspul_user'@'localhost';
FLUSH PRIVILEGES;

# Create database jika belum ada
CREATE DATABASE IF NOT EXISTS gaspulco_lkbkanwil CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Verify
SHOW DATABASES LIKE 'gaspulco%';
SELECT User, Host FROM mysql.user WHERE User = 'gaspul_user';

EXIT;

# Test lagi
mysql -u gaspul_user -p gaspulco_lkbkanwil
# Jika berhasil, ketik: SHOW TABLES; lalu EXIT;
```

---

### STEP 5: Backup Database (PENTING!)

```bash
# Backup database sebelum migration
mysqldump -u gaspul_user -p gaspulco_lkbkanwil > backup_production_$(date +%Y%m%d_%H%M%S).sql

# Verify backup
ls -lh backup_production_*.sql
```

---

### STEP 6: Run Database Migrations

```bash
# Check migration status
php artisan migrate:status

# Run migrations (akan otomatis fix duplicate foreign key)
php artisan migrate --force

# Jika masih error "Duplicate key", jalankan manual fix:
mysql -u gaspul_user -p gaspulco_lkbkanwil

# Di MySQL prompt, jalankan:
ALTER TABLE skp_tahunan DROP FOREIGN KEY IF EXISTS fk_skp_tahunan_user;
ALTER TABLE skp_tahunan DROP FOREIGN KEY IF EXISTS fk_skp_tahunan_approved_by;
EXIT;

# Jalankan ulang migration
php artisan migrate --force

# Verify all migrations done
php artisan migrate:status
# Semua harus status "Ran"
```

---

### STEP 7: Setup Storage & Permissions

```bash
# Create symbolic link storage
php artisan storage:link

# Set permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Verify storage writable
touch storage/logs/test.log && rm storage/logs/test.log
# Jika tidak error, berarti writable
```

---

### STEP 8: Production Optimization

```bash
# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# Verify caches created
ls -la bootstrap/cache/
# Harus ada: config.php, routes-v7.php, events.php, services.php
```

---

### STEP 9: Verify Application

```bash
# Check application status
php artisan about

# Harus muncul:
# Environment ........... production
# Debug Mode ............ OFF
# Database .............. mysql (gaspulco_lkbkanwil)

# Check routes
php artisan route:list | grep -E "(login|dashboard|skp|harian)"

# Test database connection
php artisan tinker
>>> \DB::connection()->getPdo();
# Jika muncul PDO object, berarti koneksi berhasil
>>> exit
```

---

### STEP 10: Setup Web Server (Apache/Nginx)

**Untuk Apache (.htaccess sudah ada):**

```bash
# Pastikan mod_rewrite enabled
# File .htaccess sudah ada di public/

# Verify .htaccess
cat public/.htaccess | head -20

# Set DocumentRoot ke /path/to/gaspul_api/public
# Edit virtual host config di /etc/httpd/conf.d/ atau sesuai setup
```

**Untuk Nginx:**

```nginx
# Edit nginx config
server {
    listen 80;
    server_name lkh.kemenag-sulbar.go.id;
    root /home/gaspulco/public_html/gaspul_api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Restart nginx
sudo systemctl restart nginx
```

---

### STEP 11: Setup HTTPS (SSL/TLS)

```bash
# Jika menggunakan Let's Encrypt (Certbot)
sudo certbot --nginx -d lkh.kemenag-sulbar.go.id

# ATAU manual install certificate
# (sesuaikan dengan provider SSL Anda)

# Update .env
SESSION_SECURE_COOKIE=true
```

---

### STEP 12: Setup Cron Jobs (Optional)

```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /home/gaspulco/public_html/gaspul_api && php artisan schedule:run >> /dev/null 2>&1

# Save & exit
```

---

### STEP 13: Final Testing

```bash
# 1. Test via browser
# Buka: https://lkh.kemenag-sulbar.go.id
# Harus muncul halaman login

# 2. Test login
# Username: admin (sesuai data seeder)
# Password: (sesuai data seeder)

# 3. Test fitur utama:
# - Login as Admin → Dashboard → Master Data
# - Login as ASN → SKP Tahunan → Kinerja Harian
# - Login as Atasan → Approval → Monitoring

# 4. Test PDF generation
# - ASN: Cetak Laporan Harian
# - Atasan: Cetak Rekap Kinerja
```

---

## ✅ CHECKLIST DEPLOYMENT

Pastikan semua item ini sudah done:

- [ ] Code ter-pull dari GitHub (commit terbaru: 8e622ff)
- [ ] Dependencies installed (`composer install --no-dev --optimize-autoloader`)
- [ ] .env configured untuk production
- [ ] Database credentials benar (test koneksi berhasil)
- [ ] Database backup created
- [ ] Migrations berhasil (semua status "Ran")
- [ ] Storage link created & writable
- [ ] Production caches created (config, routes, views)
- [ ] Web server configured (Apache/Nginx)
- [ ] HTTPS/SSL configured
- [ ] Application accessible via browser
- [ ] Login test berhasil
- [ ] PDF generation test berhasil

---

## 🐛 TROUBLESHOOTING

### Error: "Duplicate key on write or update" (errno 121)

**Solution:** Sudah di-fix di migration terbaru. Jalankan:

```bash
php artisan migrate:rollback --step=1 --force
php artisan migrate --force
```

Atau lihat detail di: [PRODUCTION_SERVER_MIGRATION_FIX.md](PRODUCTION_SERVER_MIGRATION_FIX.md)

---

### Error: "Access denied for user 'gaspul_user'"

**Solution:**

```bash
mysql -u root -p

CREATE USER IF NOT EXISTS 'gaspul_user'@'localhost' IDENTIFIED BY 'password_baru';
GRANT ALL PRIVILEGES ON gaspulco_lkbkanwil.* TO 'gaspul_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Update .env dengan password yang benar
```

---

### Error: "Base table or view not found"

**Solution:**

```bash
# Database mungkin belum ada
mysql -u root -p

CREATE DATABASE gaspulco_lkbkanwil CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

php artisan migrate:fresh --force
```

---

### Error: "Storage not writable"

**Solution:**

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage  # untuk Apache
# ATAU
chown -R nginx:nginx storage  # untuk Nginx
```

---

### Error: "Class 'Barryvdh\DomPDF\ServiceProvider' not found"

**Solution:**

```bash
composer require barryvdh/laravel-dompdf
php artisan config:cache
```

---

## 📊 MONITORING POST-DEPLOYMENT

### Check Application Logs

```bash
# Real-time logs
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log

# Errors only
grep -i error storage/logs/laravel-$(date +%Y-%m-%d).log
```

### Check Web Server Logs

```bash
# Apache
tail -f /var/log/httpd/error_log

# Nginx
tail -f /var/log/nginx/error.log
```

### Check Database Performance

```bash
mysql -u gaspul_user -p gaspulco_lkbkanwil

# Check table counts
SELECT 'users' as tabel, COUNT(*) as jumlah FROM users
UNION ALL
SELECT 'skp_tahunan', COUNT(*) FROM skp_tahunan
UNION ALL
SELECT 'progres_harian', COUNT(*) FROM progres_harian;

# Check recent activity
SELECT * FROM progres_harian ORDER BY created_at DESC LIMIT 10;

EXIT;
```

---

## 📞 SUPPORT

**Repository:** https://github.com/echal/lkbkanwil.git
**Documentation:** PRODUCTION_DEPLOYMENT_COMPLETE.md
**Migration Fix:** PRODUCTION_SERVER_MIGRATION_FIX.md

**Jika ada error:**
1. Check logs: `storage/logs/laravel-*.log`
2. Verify .env credentials
3. Test database connection
4. Contact development team dengan error log lengkap

---

**Deployment Guide Version:** 2.0.0
**Last Updated:** 2026-01-30
**Author:** Lead Software Architect
