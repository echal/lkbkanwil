@echo off
echo ========================================
echo MIGRATION: Add Revision to SKP Tahunan
echo ========================================
echo.
echo File migration: 2026_02_01_000000_add_revision_statuses_to_skp_tahunan.php
echo.
echo PERUBAHAN:
echo - Menambah ENUM status: REVISI_DIAJUKAN, REVISI_DITOLAK
echo - Menambah kolom: alasan_revisi, revisi_diajukan_at, revisi_disetujui_at, catatan_revisi
echo.
pause
echo.
echo Running migration...
php artisan migrate
echo.
echo ========================================
echo Migration completed!
echo ========================================
echo.
pause
