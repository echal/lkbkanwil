<?php

namespace App\Helpers;

use Carbon\Carbon;

class HolidayHelper
{
    /**
     * Daftar hari libur nasional Indonesia 2026
     * Format: 'YYYY-MM-DD' => 'Nama Libur'
     *
     * @return array
     */
    public static function getNationalHolidays2026(): array
    {
        return [
            // Tahun Baru
            '2026-01-01' => 'Tahun Baru Masehi',

            // Imlek
            '2026-02-17' => 'Tahun Baru Imlek 2577',

            // Isra Miraj
            '2026-03-01' => 'Isra Miraj Nabi Muhammad SAW',

            // Idul Fitri (1 Syawal 1447 H — perkiraan 20-21 Maret 2026)
            '2026-03-20' => 'Hari Raya Idul Fitri 1447 H',
            '2026-03-21' => 'Hari Raya Idul Fitri 1447 H',

            // Cuti Bersama Idul Fitri (perkiraan)
            '2026-03-18' => 'Cuti Bersama Idul Fitri',
            '2026-03-19' => 'Cuti Bersama Idul Fitri',
            '2026-03-23' => 'Cuti Bersama Idul Fitri',
            '2026-03-24' => 'Cuti Bersama Idul Fitri',

            // Nyepi
            '2026-03-22' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)',

            // Wafat Yesus Kristus
            '2026-04-03' => 'Wafat Yesus Kristus',

            // Hari Buruh
            '2026-05-01' => 'Hari Buruh Internasional',

            // Kenaikan Yesus
            '2026-05-14' => 'Kenaikan Yesus Kristus',
            '2026-05-15' => 'Cuti Bersama Kenaikan Yesus Kristus',

            // Waisak
            '2026-05-24' => 'Hari Raya Waisak 2570',
            '2026-05-27' => 'Cuti Bersama Hari Raya Idul Adha 1447 H',

            // Pancasila
            '2026-06-01' => 'Hari Lahir Pancasila',

            // Idul Adha (1447 H — perkiraan sekitar 28-29 Mei 2026)
            '2026-05-28' => 'Hari Raya Idul Adha 1447 H',

            // Tahun Baru Islam
            '2026-06-16' => 'Tahun Baru Islam 1448 H',

            // Kemerdekaan RI
            '2026-08-17' => 'Hari Kemerdekaan RI',

            // Maulid Nabi
            '2026-08-25' => 'Maulid Nabi Muhammad SAW',

            // Natal
            '2026-12-24' => 'Cuti Bersama Kelahiran Yesus Kristus',
            '2026-12-25' => 'Hari Raya Natal',
        ];
    }

    /**
     * Check apakah tanggal tertentu adalah hari libur nasional
     *
     * @param string|Carbon $date
     * @return bool
     */
    public static function isNationalHoliday($date): bool
    {
        $dateString = $date instanceof Carbon ? $date->format('Y-m-d') : $date;
        return array_key_exists($dateString, self::getNationalHolidays2026());
    }

    /**
     * Get nama hari libur
     *
     * @param string|Carbon $date
     * @return string|null
     */
    public static function getHolidayName($date): ?string
    {
        $dateString = $date instanceof Carbon ? $date->format('Y-m-d') : $date;
        return self::getNationalHolidays2026()[$dateString] ?? null;
    }

    /**
     * Ambil pola hari kerja user: override personal → default unit → fallback SENIN_JUMAT.
     *
     * @param \App\Models\User $user
     * @return string 'SENIN_JUMAT' | 'SENIN_SABTU'
     */
    public static function getHariKerjaUser($user): string
    {
        if (! empty($user->hari_kerja)) {
            return $user->hari_kerja;
        }

        if ($user->relationLoaded('unitKerja') && $user->unitKerja && ! empty($user->unitKerja->hari_kerja)) {
            return $user->unitKerja->hari_kerja;
        }

        // Lazy-load unit kerja jika belum dimuat
        $unitKerja = $user->unitKerja;
        if ($unitKerja && ! empty($unitKerja->hari_kerja)) {
            return $unitKerja->hari_kerja;
        }

        return 'SENIN_JUMAT';
    }

    /**
     * Check apakah tanggal adalah hari kerja.
     *
     * Signature lama isWorkingDay($date) tetap identik — $user = null
     * menjaga backward compatibility untuk semua caller yang tidak meneruskan user.
     *
     * @param string|Carbon       $date
     * @param \App\Models\User|null $user  Jika null → logic lama (Sabtu = weekend)
     * @return bool
     */
    public static function isWorkingDay($date, $user = null): bool
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        // Hari Minggu → selalu non-kerja untuk semua pola
        if ($carbon->isSunday()) {
            return false;
        }

        // Hari libur nasional → selalu non-kerja (termasuk jika jatuh Sabtu)
        if (self::isNationalHoliday($carbon)) {
            return false;
        }

        // Hari Sabtu — tergantung pola kerja
        if ($carbon->isSaturday()) {
            if ($user !== null && self::getHariKerjaUser($user) === 'SENIN_SABTU') {
                return true;
            }
            return false; // logic lama: Sabtu = weekend
        }

        // Senin–Jumat → hari kerja
        return true;
    }

    /**
     * Get warna badge untuk tanggal tertentu.
     *
     * @param string|Carbon      $date
     * @param bool               $hasLkh
     * @param bool               $hasRhk
     * @param \App\Models\User|null $user  Null → fallback behavior lama (Sabtu = non-kerja)
     * @return array ['bg' => 'bg-color', 'text' => 'text-color', 'label' => 'Label', 'border' => '...']
     */
    public static function getDateBadge($date, bool $hasLkh = false, bool $hasRhk = false, $user = null): array
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        // Priority 1: LKH terisi (paling penting)
        if ($hasLkh) {
            return [
                'bg' => 'bg-blue-500',
                'text' => 'text-white',
                'label' => 'LKH',
                'border' => 'border-blue-500',
            ];
        }

        // Priority 2: RHK terisi
        if ($hasRhk) {
            return [
                'bg' => 'bg-purple-500',
                'text' => 'text-white',
                'label' => 'RHK',
                'border' => 'border-purple-500',
            ];
        }

        // Priority 3: Hari libur nasional
        if (self::isNationalHoliday($carbon)) {
            return [
                'bg' => 'bg-red-100',
                'text' => 'text-red-800',
                'label' => self::getHolidayName($carbon),
                'border' => 'border-red-300',
            ];
        }

        // Priority 4: Bukan hari kerja (user-aware: Sabtu = kerja untuk SENIN_SABTU)
        if (!self::isWorkingDay($carbon, $user)) {
            return [
                'bg' => 'bg-gray-100',
                'text' => 'text-gray-600',
                'label' => 'Libur / Weekend',
                'border' => 'border-gray-300',
            ];
        }

        // Priority 5: Hari kerja biasa (belum ada LKH/RHK)
        return [
            'bg' => 'bg-green-50',
            'text' => 'text-green-700',
            'label' => 'Hari Kerja',
            'border' => 'border-green-200',
        ];
    }

    /**
     * Check apakah tanggal bisa input LKH/RHK.
     * HANYA bisa input di HARI INI saja (tanggal kemarin sudah terkunci).
     *
     * @param string|Carbon       $date
     * @param \App\Models\User|null $user  Jika null → logic lama (Sabtu tidak bisa input)
     * @return bool
     */
    public static function canInputData($date, $user = null): bool
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $today  = Carbon::today();

        // Gunakan isWorkingDay dengan konteks user (backward-compatible)
        if (! self::isWorkingDay($carbon, $user)) {
            return false;
        }

        // Tidak bisa input untuk tanggal masa depan
        if ($carbon->isFuture()) {
            return false;
        }

        // PENTING: Tidak bisa input untuk tanggal KEMARIN (hanya hari ini)
        // Setelah jam 00:00 hari berganti, tanggal sebelumnya terkunci
        if (! $carbon->isSameDay($today)) {
            return false;
        }

        return true;
    }

    /**
     * Hitung sisa hari kerja dari hari ini s/d akhir bulan (inklusif hari ini).
     * Menghormati pola kerja user (SENIN_JUMAT | SENIN_SABTU) dan libur nasional.
     * Jika bulan sudah lewat → return 0.
     *
     * @param int                  $bulan  1–12
     * @param int                  $tahun
     * @param \App\Models\User|null $user  null → fallback SENIN_JUMAT
     * @return int
     */
    public static function countRemainingWorkingDays(int $bulan, int $tahun, $user = null): int
    {
        $today = Carbon::today();
        $end   = Carbon::create($tahun, $bulan, 1)->endOfMonth()->startOfDay();

        // Bulan sudah lewat seluruhnya
        if ($today->gt($end)) {
            return 0;
        }

        // Mulai dari hari ini atau awal bulan, mana yang lebih akhir
        $start   = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $current = $today->gte($start) ? $today->copy() : $start->copy();
        $count   = 0;

        while ($current->lte($end)) {
            if (self::isWorkingDay($current, $user)) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Get semua hari libur dalam bulan tertentu
     *
     * @param int $month
     * @param int $year
     * @return array
     */
    public static function getHolidaysInMonth(int $month, int $year): array
    {
        $holidays = self::getNationalHolidays2026();
        $result = [];

        foreach ($holidays as $date => $name) {
            $carbon = Carbon::parse($date);
            if ($carbon->month == $month && $carbon->year == $year) {
                $result[$date] = $name;
            }
        }

        return $result;
    }

    /**
     * Hitung jumlah hari kerja dalam satu bulan.
     * Mengecualikan Sabtu, Minggu, dan hari libur nasional.
     *
     * @param int $bulan  1–12
     * @param int $tahun  contoh: 2026
     * @return int
     */
    public static function countWorkingDaysInMonth(int $bulan, int $tahun): int
    {
        $start   = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end     = $start->copy()->endOfMonth();
        $current = $start->copy();
        $count   = 0;

        while ($current->lte($end)) {
            if (self::isWorkingDay($current)) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Hitung jumlah hari kerja dalam satu bulan — aware terhadap pola kerja user.
     *
     * Berbeda dari countWorkingDaysInMonth() yang selalu pakai Senin–Jumat,
     * method ini mempertimbangkan apakah user masuk Sabtu (SENIN_SABTU).
     * Libur nasional selalu dikecualikan terlepas dari pola kerja.
     *
     * @param int                  $bulan  1–12
     * @param int                  $tahun  contoh: 2026
     * @param \App\Models\User|null $user  null → fallback SENIN_JUMAT (sama dengan method lama)
     * @return int
     */
    public static function countWorkingDaysInMonthUser(int $bulan, int $tahun, $user = null): int
    {
        $pola    = $user !== null ? self::getHariKerjaUser($user) : 'SENIN_JUMAT';
        $start   = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end     = $start->copy()->endOfMonth();
        $current = $start->copy();
        $count   = 0;

        while ($current->lte($end)) {
            // Minggu → selalu bukan hari kerja
            if ($current->isSunday()) {
                $current->addDay();
                continue;
            }

            // Sabtu → hanya hari kerja jika pola SENIN_SABTU
            if ($current->isSaturday() && $pola === 'SENIN_JUMAT') {
                $current->addDay();
                continue;
            }

            // Libur nasional → dikecualikan untuk semua pola (termasuk Sabtu libur)
            if (self::isNationalHoliday($current)) {
                $current->addDay();
                continue;
            }

            $count++;
            $current->addDay();
        }

        return $count;
    }
}
