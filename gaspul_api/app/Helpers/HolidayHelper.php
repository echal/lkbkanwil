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

            // Nyepi
            '2026-03-22' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)',

            // Wafat Yesus Kristus
            '2026-04-03' => 'Wafat Yesus Kristus',

            // Idul Fitri
            '2026-04-19' => 'Hari Raya Idul Fitri 1447 H',
            '2026-04-20' => 'Hari Raya Idul Fitri 1447 H',

            // Cuti bersama Idul Fitri (estimate)
            '2026-04-17' => 'Cuti Bersama Idul Fitri',
            '2026-04-21' => 'Cuti Bersama Idul Fitri',
            '2026-04-22' => 'Cuti Bersama Idul Fitri',

            // Hari Buruh
            '2026-05-01' => 'Hari Buruh Internasional',

            // Kenaikan Yesus
            '2026-05-14' => 'Kenaikan Yesus Kristus',

            // Waisak
            '2026-05-24' => 'Hari Raya Waisak 2570',

            // Pancasila
            '2026-06-01' => 'Hari Lahir Pancasila',

            // Idul Adha
            '2026-06-27' => 'Hari Raya Idul Adha 1447 H',

            // Tahun Baru Islam
            '2026-07-18' => 'Tahun Baru Islam 1448 H',

            // Kemerdekaan RI
            '2026-08-17' => 'Hari Kemerdekaan RI',

            // Maulid Nabi
            '2026-09-26' => 'Maulid Nabi Muhammad SAW',

            // Natal
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
     * Check apakah tanggal adalah hari kerja (Senin-Jumat, bukan libur)
     *
     * @param string|Carbon $date
     * @return bool
     */
    public static function isWorkingDay($date): bool
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        // Check weekend (Sabtu = 6, Minggu = 0)
        if ($carbon->isWeekend()) {
            return false;
        }

        // Check hari libur nasional
        if (self::isNationalHoliday($carbon)) {
            return false;
        }

        return true;
    }

    /**
     * Get warna badge untuk tanggal tertentu
     *
     * @param string|Carbon $date
     * @param bool $hasLkh
     * @param bool $hasRhk
     * @return array ['bg' => 'bg-color', 'text' => 'text-color', 'label' => 'Label']
     */
    public static function getDateBadge($date, bool $hasLkh = false, bool $hasRhk = false): array
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

        // Priority 4: Weekend
        if ($carbon->isWeekend()) {
            return [
                'bg' => 'bg-gray-100',
                'text' => 'text-gray-600',
                'label' => 'Weekend',
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
     * Check apakah tanggal bisa input LKH/RHK
     *
     * @param string|Carbon $date
     * @return bool
     */
    public static function canInputData($date): bool
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);

        // Tidak bisa input di weekend
        if ($carbon->isWeekend()) {
            return false;
        }

        // Tidak bisa input di hari libur nasional
        if (self::isNationalHoliday($carbon)) {
            return false;
        }

        // Tidak bisa input untuk tanggal masa depan
        if ($carbon->isFuture()) {
            return false;
        }

        return true;
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
}
