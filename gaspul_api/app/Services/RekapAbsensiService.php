<?php

namespace App\Services;

use App\Models\RekapAbsensiPusaka;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RekapAbsensiService
{
    // =========================================================================
    // UPLOAD
    // =========================================================================

    /**
     * Upload rekap absensi PUSAKA untuk user dan bulan tertentu.
     *
     * @throws ValidationException jika link tidak valid atau bulan sudah ada
     */
    public function upload(int $userId, string $bulan, string $link): RekapAbsensiPusaka
    {
        // Validasi link harus mengandung drive.google.com
        if (!str_contains($link, 'drive.google.com')) {
            throw ValidationException::withMessages([
                'link_drive' => 'Link harus berupa Google Drive (drive.google.com).',
            ]);
        }

        // Cek apakah bulan yang sama sudah pernah diupload
        $existing = RekapAbsensiPusaka::where('user_id', $userId)
            ->where('bulan', $bulan)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'bulan' => 'Rekap absensi untuk bulan ' . $existing->nama_bulan . ' sudah pernah diupload.',
            ]);
        }

        return RekapAbsensiPusaka::create([
            'user_id'    => $userId,
            'bulan'      => $bulan,
            'link_drive' => $link,
            'status'     => 'pending',
        ]);
    }

    // =========================================================================
    // QUERY
    // =========================================================================

    /**
     * Ambil semua rekap absensi milik user, diurutkan terbaru.
     */
    public function getByUser(int $userId): Collection
    {
        return RekapAbsensiPusaka::where('user_id', $userId)
            ->with('verifier:id,name')
            ->orderByDesc('bulan')
            ->get();
    }

    /**
     * Ambil rekap absensi untuk user dan bulan tertentu.
     */
    public function getByUserAndBulan(int $userId, string $bulan): ?RekapAbsensiPusaka
    {
        return RekapAbsensiPusaka::where('user_id', $userId)
            ->where('bulan', $bulan)
            ->with('verifier:id,name')
            ->first();
    }

    /**
     * Hitung compliance upload rekap absensi untuk bulan tertentu.
     *
     * @return array{total_asn: int, sudah_upload: int, belum_upload: int, persen: int}
     */
    public function getComplianceBulan(string $bulan): array
    {
        $totalAsn = User::where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->count();

        $sudahUpload = RekapAbsensiPusaka::where('bulan', $bulan)
            ->whereHas('user', fn($q) => $q->where('role', 'ASN')->where('status_pegawai', 'AKTIF'))
            ->count();

        $belumUpload = max(0, $totalAsn - $sudahUpload);
        $persen = $totalAsn > 0 ? round(($sudahUpload / $totalAsn) * 100) : 0;

        return [
            'total_asn'    => $totalAsn,
            'sudah_upload' => $sudahUpload,
            'belum_upload' => $belumUpload,
            'persen'       => $persen,
        ];
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    /**
     * Generate pilihan 12 bulan terakhir untuk dropdown.
     *
     * @return array<string, string> ['YYYY-MM' => 'Nama Bulan YYYY']
     */
    public function getBulanOptions(): array
    {
        $namaBulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',   '05' => 'Mei',       '06' => 'Juni',
            '07' => 'Juli',    '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober', '11' => 'November',  '12' => 'Desember',
        ];

        $options = [];
        for ($i = 0; $i < 12; $i++) {
            $date  = now()->subMonths($i);
            $key   = $date->format('Y-m');
            $bulan = $date->format('m');
            $tahun = $date->format('Y');
            $options[$key] = ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun;
        }

        return $options;
    }
}
