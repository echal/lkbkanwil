<?php

namespace App\Services;

use App\Models\SkpTahunan;
use Illuminate\Support\Facades\Auth;

/**
 * SKP Access Service
 *
 * Single source of truth untuk mengecek apakah ASN boleh mengakses
 * fitur RHK Bulanan dan Kinerja Harian berdasarkan status SKP Tahunan.
 *
 * ATURAN:
 * - ASN hanya boleh mengisi RHK Bulanan & Kinerja Harian jika SKP Tahunan DISETUJUI
 * - ASN SELALU boleh mengisi Tugas Atasan Langsung (TLA)
 * - ASN SELALU boleh membuat & mengajukan SKP Tahunan
 */
class SkpAccessService
{
    /**
     * Cek apakah ASN memiliki SKP Tahunan yang disetujui untuk tahun tertentu
     *
     * @param int|null $userId User ID (default: auth user)
     * @param int|null $tahun Tahun SKP (default: tahun sekarang)
     * @return bool
     */
    public static function hasApprovedSkp(?int $userId = null, ?int $tahun = null): bool
    {
        $userId = $userId ?? Auth::id();
        $tahun = $tahun ?? now()->year;

        if (!$userId) {
            return false;
        }

        return SkpTahunan::where('user_id', $userId)
            ->where('tahun', $tahun)
            ->where('status', 'DISETUJUI')
            ->exists();
    }

    /**
     * Get SKP Tahunan yang disetujui
     *
     * @param int|null $userId
     * @param int|null $tahun
     * @return SkpTahunan|null
     */
    public static function getApprovedSkp(?int $userId = null, ?int $tahun = null): ?SkpTahunan
    {
        $userId = $userId ?? Auth::id();
        $tahun = $tahun ?? now()->year;

        if (!$userId) {
            return null;
        }

        return SkpTahunan::where('user_id', $userId)
            ->where('tahun', $tahun)
            ->where('status', 'DISETUJUI')
            ->first();
    }

    /**
     * Get status SKP untuk user
     *
     * @param int|null $userId
     * @param int|null $tahun
     * @return array
     */
    public static function getSkpStatus(?int $userId = null, ?int $tahun = null): array
    {
        $userId = $userId ?? Auth::id();
        $tahun = $tahun ?? now()->year;

        if (!$userId) {
            return [
                'has_skp' => false,
                'status' => null,
                'is_approved' => false,
                'message' => 'User tidak ditemukan',
            ];
        }

        $skp = SkpTahunan::where('user_id', $userId)
            ->where('tahun', $tahun)
            ->first();

        if (!$skp) {
            return [
                'has_skp' => false,
                'status' => null,
                'is_approved' => false,
                'message' => "Anda belum memiliki SKP Tahunan {$tahun}. Silakan buat SKP Tahunan terlebih dahulu.",
            ];
        }

        $isApproved = $skp->status === 'DISETUJUI';

        $statusMessages = [
            'DRAFT' => 'SKP Tahunan masih berstatus DRAFT. Silakan ajukan ke atasan untuk disetujui.',
            'DIAJUKAN' => 'SKP Tahunan sedang menunggu persetujuan atasan.',
            'DITOLAK' => 'SKP Tahunan ditolak oleh atasan. Silakan perbaiki dan ajukan kembali.',
            'DISETUJUI' => 'SKP Tahunan sudah disetujui. Anda dapat mengisi RHK dan Kinerja Harian.',
            'REVISI_DIAJUKAN' => 'Permintaan revisi SKP sedang menunggu persetujuan atasan.',
            'REVISI_DITOLAK' => 'Permintaan revisi ditolak. SKP tetap berlaku.',
        ];

        return [
            'has_skp' => true,
            'status' => $skp->status,
            'is_approved' => $isApproved,
            'skp' => $skp,
            'message' => $statusMessages[$skp->status] ?? 'Status SKP tidak dikenali.',
        ];
    }

    /**
     * Cek apakah user bisa akses fitur RHK Bulanan
     * HANYA jika SKP Tahunan sudah DISETUJUI
     *
     * @param int|null $userId
     * @param int|null $tahun
     * @return bool
     */
    public static function canAccessRhk(?int $userId = null, ?int $tahun = null): bool
    {
        return self::hasApprovedSkp($userId, $tahun);
    }

    /**
     * Cek apakah user bisa akses fitur Kinerja Harian
     * HANYA jika SKP Tahunan sudah DISETUJUI
     *
     * NOTE: Ini untuk form-kinerja, bukan form-tla
     *
     * @param int|null $userId
     * @param int|null $tahun
     * @return bool
     */
    public static function canAccessKinerjaHarian(?int $userId = null, ?int $tahun = null): bool
    {
        return self::hasApprovedSkp($userId, $tahun);
    }

    /**
     * Cek apakah user bisa akses fitur Tugas Atasan Langsung
     * TLA SELALU boleh diakses tanpa melihat status SKP
     *
     * @param int|null $userId
     * @return bool
     */
    public static function canAccessTla(?int $userId = null): bool
    {
        // TLA selalu boleh diakses
        return true;
    }

    /**
     * Get access denied message yang user-friendly
     *
     * @param int|null $userId
     * @param int|null $tahun
     * @return string
     */
    public static function getAccessDeniedMessage(?int $userId = null, ?int $tahun = null): string
    {
        $status = self::getSkpStatus($userId, $tahun);

        if (!$status['has_skp']) {
            return $status['message'];
        }

        if (!$status['is_approved']) {
            return "SKP Tahunan belum disetujui atasan. " . $status['message'];
        }

        return 'Akses ditolak.';
    }
}
