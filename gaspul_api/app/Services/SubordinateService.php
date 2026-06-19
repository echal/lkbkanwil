<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * SubordinateService
 *
 * Menentukan daftar bawahan yang dapat dimonitor oleh seorang atasan.
 *
 * Untuk Kepala Kankemenag Kabupaten (atasan_id = Kakanwil Provinsi):
 *
 *   BISA DILIHAT:
 *   - Semua L1: Kepala Seksi, Penyelenggara, Kepala KUA, Kepala MAN/MTsN/MIN, staf langsung
 *   - L2 dari Kepala Seksi/Penyelenggara/non-madrasah/non-KUA: semua staf
 *   - L2 dari Kepala Madrasah (MAN/MTsN/MIN): hanya Kepala TU (jabatan LIKE '%Tata Usaha%')
 *
 *   TIDAK BISA DILIHAT:
 *   - L2 dari Kepala KUA (Penyuluh, Penghulu, staf KUA)
 *   - L2 dari Kepala Madrasah yang bukan Kepala TU (Guru, staf biasa)
 *   - L2 dari Kepala TU itu sendiri (staf di bawah Kepala TU)
 *
 * Untuk atasan lain (Kepala Seksi, Kepala Madrasah, Kepala KUA):
 *   - Hanya bawahan langsung (L1) via atasan_id seperti biasa
 */
class SubordinateService
{
    // ID Kakanwil Provinsi Sulbar — puncak hierarki
    const KAKANWIL_PROVINSI_ID = 293;

    /**
     * Apakah user ini adalah Kepala Kankemenag Kabupaten?
     */
    public function isKepalaKankemenagKab($user): bool
    {
        return $user->role === 'ATASAN'
            && $user->atasan_id == self::KAKANWIL_PROVINSI_ID;
    }

    /**
     * Ambil ID semua user yang dapat dimonitor oleh $user.
     */
    public function getMonitorableIds($user): \Illuminate\Support\Collection
    {
        if ($this->isKepalaKankemenagKab($user)) {
            return $this->getStrategicSubordinates($user->id)->pluck('id');
        }

        // Atasan biasa: hanya L1 ASN langsung
        return DB::table('users')
            ->where('atasan_id', $user->id)
            ->where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->pluck('id');
    }

    /**
     * Ambil data lengkap user yang dapat dimonitor oleh $user.
     */
    public function getMonitorableUsers($user): \Illuminate\Support\Collection
    {
        if ($this->isKepalaKankemenagKab($user)) {
            return $this->getStrategicSubordinates($user->id);
        }

        return DB::table('users')
            ->where('atasan_id', $user->id)
            ->where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->orderBy('name')
            ->get();
    }

    /**
     * Bangun daftar strategis untuk Kepala Kankemenag Kab.
     *
     * Tipe L1 ditentukan dari nama unit kerja:
     *   - Madrasah: nama_unit LIKE '%MAN%' atau '%MTsN%' atau '%MIN%'
     *   - KUA     : nama_unit LIKE '%KUA%'
     *   - Lainnya : Kepala Seksi, Penyelenggara, staf kantor — ambil semua L2
     */
    private function getStrategicSubordinates(int $kepalaKabId): \Illuminate\Support\Collection
    {
        // L1: semua bawahan langsung — ATASAN maupun ASN
        $l1All = DB::table('users as u')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->where('u.atasan_id', $kepalaKabId)
            ->where('u.status_pegawai', 'AKTIF')
            ->select('u.id', 'u.name', 'u.jabatan', 'u.role', 'u.unit_kerja_id', 'u.nip', 'uk.nama_unit')
            ->get();

        // L1 tanpa join (fallback untuk yang unit_kerja_id null)
        $l1NoUnit = DB::table('users')
            ->where('atasan_id', $kepalaKabId)
            ->where('status_pegawai', 'AKTIF')
            ->whereNotIn('id', $l1All->pluck('id'))
            ->get(['id', 'name', 'jabatan', 'role', 'unit_kerja_id', 'nip'])
            ->map(function($u) { $u->nama_unit = null; return $u; });

        $l1All = $l1All->merge($l1NoUnit);

        // Klasifikasi L1 ATASAN berdasarkan unit kerja
        $l1AtasanMadrasah = collect(); // Kepala MAN/MTsN/MIN
        $l1AtasanKua      = collect(); // Kepala KUA
        $l1AtasanLain     = collect(); // Kepala Seksi, Penyelenggara, dll

        foreach ($l1All->where('role', 'ATASAN') as $u) {
            $namaUnit = strtolower($u->nama_unit ?? '');
            if ($this->isMadrasah($namaUnit)) {
                $l1AtasanMadrasah->push($u);
            } elseif ($this->isKua($namaUnit)) {
                $l1AtasanKua->push($u);
            } else {
                $l1AtasanLain->push($u);
            }
        }

        $result = collect();

        // Masukkan semua L1 (ATASAN + ASN)
        $result = $result->merge($l1All);

        // L2 dari Kepala Madrasah: hanya Kepala TU
        if ($l1AtasanMadrasah->isNotEmpty()) {
            $madrasahIds = $l1AtasanMadrasah->pluck('id');
            $l2Tu = DB::table('users')
                ->whereIn('atasan_id', $madrasahIds)
                ->where('status_pegawai', 'AKTIF')
                ->whereRaw("LOWER(jabatan) LIKE '%tata usaha%'")
                ->get(['id', 'name', 'jabatan', 'role', 'unit_kerja_id', 'nip'])
                ->map(function($u) { $u->nama_unit = null; return $u; });
            $result = $result->merge($l2Tu);
        }

        // L2 dari Kepala Seksi/Penyelenggara/lainnya: semua staf kecuali Guru
        if ($l1AtasanLain->isNotEmpty()) {
            $lainIds = $l1AtasanLain->pluck('id');
            $l2Lain = DB::table('users')
                ->whereIn('atasan_id', $lainIds)
                ->where('role', 'ASN')
                ->where('status_pegawai', 'AKTIF')
                ->whereRaw("LOWER(jabatan) NOT LIKE '%guru%'")
                ->get(['id', 'name', 'jabatan', 'role', 'unit_kerja_id', 'nip'])
                ->map(function($u) { $u->nama_unit = null; return $u; });
            $result = $result->merge($l2Lain);
        }

        // L2 dari Kepala KUA: tidak dimasukkan (skip)

        return $result->unique('id')->sortBy('name')->values();
    }

    private function isMadrasah(string $namaUnit): bool
    {
        return str_contains($namaUnit, 'man ')
            || str_contains($namaUnit, 'man\t')
            || preg_match('/\bman\b/', $namaUnit)
            || str_contains($namaUnit, 'mtsn')
            || str_contains($namaUnit, 'min ')
            || preg_match('/\bmin\b/', $namaUnit)
            || str_contains($namaUnit, 'madrasah aliyah')
            || str_contains($namaUnit, 'madrasah tsanawiyah')
            || str_contains($namaUnit, 'madrasah ibtidaiyah');
    }

    private function isKua(string $namaUnit): bool
    {
        return str_contains($namaUnit, 'kua');
    }
}
