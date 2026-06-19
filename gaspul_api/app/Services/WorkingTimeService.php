<?php

namespace App\Services;

use App\Helpers\HolidayHelper;
use Carbon\Carbon;

/**
 * Service untuk perhitungan target jam kerja ASN secara dinamis.
 *
 * Cut-off legacy:
 *   - Sebelum Mei 2026  → target tetap 165 jam (backward compatible)
 *   - Mei 2026 ke atas  → target dinamis (hari kerja × 7.5 jam)
 *
 * Tidak mengubah controller, blade, query, atau database yang sudah ada.
 */
class WorkingTimeService
{
    /** Jam kerja per hari sesuai regulasi ASN */
    private const JAM_PER_HARI = 7.5;

    /** Target legacy sebelum cut-off */
    private const TARGET_LEGACY = 165;

    /** Cut-off: bulan & tahun mulai berlaku perhitungan dinamis */
    private const CUTOFF_BULAN = 5;
    private const CUTOFF_TAHUN = 2026;

    /**
     * Hitung target jam kerja bulanan.
     * Kembalikan 165 untuk periode legacy, dinamis untuk periode baru.
     *
     * @param int $bulan  1–12
     * @param int $tahun  contoh: 2026
     * @return float
     */
    public function getTargetJamBulanan(int $bulan, int $tahun): float
    {
        if ($this->isLegacy($bulan, $tahun)) {
            return self::TARGET_LEGACY;
        }

        $hariKerja = HolidayHelper::countWorkingDaysInMonth($bulan, $tahun);

        return $hariKerja * self::JAM_PER_HARI;
    }

    /**
     * Versi static — untuk digunakan tanpa inject service.
     *
     * @param int $bulan
     * @param int $tahun
     * @return float
     */
    public static function targetJam(int $bulan, int $tahun): float
    {
        return (new self)->getTargetJamBulanan($bulan, $tahun);
    }

    /**
     * Hitung target jam kerja tahunan (seluruh 12 bulan).
     * Legacy tahun 2025 ke bawah → 1875 jam (250 hari × 7.5).
     * Tahun 2026 ke atas → jumlah hari kerja nyata × 7.5.
     *
     * @param int $tahun  contoh: 2026
     * @return float
     */
    public function getTargetJamTahunan(int $tahun): float
    {
        // Tahun yang seluruhnya legacy → hardcode 1875
        $cutoffTahun = self::CUTOFF_TAHUN;
        if ($tahun < $cutoffTahun) {
            return 1875;
        }

        $total = 0;
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $total += $this->getTargetJamBulanan($bulan, $tahun);
        }

        return $total;
    }

    /**
     * Hitung target jam kerja bulanan berbasis pola kerja user.
     * Delegasi ke getTargetMenitBulanan() agar konsisten dengan config working_time.php.
     * Periode legacy (sebelum 2026) → selalu 165 jam tanpa melihat pola.
     *
     * @param int                   $bulan  1–12
     * @param int                   $tahun  contoh: 2026
     * @param \App\Models\User|null $user   null → fallback SENIN_JUMAT
     * @return float
     */
    public function getTargetJamBulananUser(int $bulan, int $tahun, $user): float
    {
        if ($tahun < self::CUTOFF_TAHUN) {
            return $this->getTargetJamBulanan($bulan, $tahun);
        }

        return round(self::getTargetMenitBulanan($bulan, $tahun, $user) / 60, 2);
    }

    // =========================================================================
    // Phase 2 Foundation — Dynamic Daily Target (per weekday per pola kerja)
    // Metode di bawah TIDAK mengubah method lama di atas.
    // =========================================================================

    /**
     * Target menit kerja untuk satu tanggal tertentu.
     *
     * Membaca pola kerja dari $user->hari_kerja (SENIN_JUMAT | SENIN_SABTU).
     * Fallback ke SENIN_JUMAT jika nilai NULL atau tidak dikenal.
     * Hari libur nasional → 0 menit (via HolidayHelper).
     *
     * @param  Carbon                $date  Tanggal yang dicek
     * @param  \App\Models\User|null $user
     * @return int  Menit target (0 jika libur/weekend)
     *
     * Contoh:
     *   SENIN_SABTU + Jumat  → 270
     *   SENIN_SABTU + Sabtu  → 420
     *   SENIN_JUMAT + Sabtu  → 0
     *   Hari libur nasional  → 0
     */
    public static function getTargetMenitByDate(Carbon $date, $user = null): int
    {
        // Libur nasional → tidak ada target
        if (HolidayHelper::isNationalHoliday($date)) {
            return 0;
        }

        $pola      = self::resolvePolaKerja($user);
        $targets   = config('working_time.' . $pola, []);
        $dayOfWeek = $date->isoWeekday(); // 1=Senin … 7=Minggu (Carbon ISO)

        return (int) ($targets[$dayOfWeek] ?? 0);
    }

    /**
     * Total target menit kerja selama satu bulan penuh.
     *
     * Menjumlahkan getTargetMenitByDate() untuk setiap tanggal dalam bulan.
     * Sudah otomatis skip hari libur nasional dan Minggu.
     *
     * @param  int                   $bulan  1–12
     * @param  int                   $tahun  contoh: 2026
     * @param  \App\Models\User|null $user
     * @return int  Total menit target sebulan
     */
    public static function getTargetMenitBulanan(int $bulan, int $tahun, $user = null): int
    {
        $start   = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end     = $start->copy()->endOfMonth();
        $current = $start->copy();
        $total   = 0;

        while ($current->lte($end)) {
            $total += self::getTargetMenitByDate($current, $user);
            $current->addDay();
        }

        return $total;
    }

    /**
     * Apakah total menit harian sudah memenuhi target?
     *
     * @param  int                   $totalMenit  Menit yang sudah diinput ASN
     * @param  Carbon                $date        Tanggal yang dinilai
     * @param  \App\Models\User|null $user
     * @return bool
     */
    public static function isCompletedDay(int $totalMenit, Carbon $date, $user = null): bool
    {
        $target = self::getTargetMenitByDate($date, $user);

        // Hari libur/weekend (target = 0) tidak dievaluasi sebagai "selesai"
        if ($target === 0) {
            return false;
        }

        return $totalMenit >= $target;
    }

    /**
     * Resolve pola kerja dari user ke key config yang valid.
     * Fallback ke SENIN_JUMAT jika NULL atau value tidak dikenal.
     *
     * @param  \App\Models\User|null $user
     * @return string  'SENIN_JUMAT' | 'SENIN_SABTU'
     */
    private static function resolvePolaKerja($user): string
    {
        $valid = ['SENIN_JUMAT', 'SENIN_SABTU'];
        $pola  = $user->hari_kerja ?? null;

        // Jika user punya hari_kerja langsung, pakai itu
        if (in_array($pola, $valid, true)) {
            return $pola;
        }

        // Coba ambil dari unit kerja (cascade)
        $unitPola = optional($user?->unitKerja)->hari_kerja ?? null;
        if (in_array($unitPola, $valid, true)) {
            return $unitPola;
        }

        return 'SENIN_JUMAT';
    }

    /**
     * Apakah periode ini masuk kategori legacy (sebelum cut-off)?
     * Legacy → gunakan 165 jam (tidak mengubah data lama).
     *
     * @param int $bulan
     * @param int $tahun
     * @return bool
     */
    private function isLegacy(int $bulan, int $tahun): bool
    {
        return $tahun < 2026;
    }
}
