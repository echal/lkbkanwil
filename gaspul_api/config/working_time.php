<?php

/**
 * Konfigurasi target menit kerja harian per pola kerja ASN.
 *
 * Numbering hari mengikuti Carbon::dayOfWeek (ISO):
 *   1 = Senin, 2 = Selasa, 3 = Rabu, 4 = Kamis,
 *   5 = Jumat, 6 = Sabtu, 7 = Minggu
 *
 * Pola kerja ditentukan dari users.hari_kerja:
 *   SENIN_JUMAT → 5 hari kerja, 450 menit/hari, total 2250 menit/minggu
 *   SENIN_SABTU → 6 hari kerja, target berbeda per hari, total 2250 menit/minggu
 */

return [

    /*
     * ASN pola Senin–Jumat (default).
     * 450 menit (7.5 jam) setiap hari kerja.
     */
    'SENIN_JUMAT' => [
        1 => 450, // Senin
        2 => 450, // Selasa
        3 => 450, // Rabu
        4 => 450, // Kamis
        5 => 450, // Jumat
        6 => 0,   // Sabtu — libur
        7 => 0,   // Minggu — libur
    ],

    /*
     * ASN pola Senin–Sabtu (Guru & Staf TU Sekolah).
     * Total tetap 2250 menit (37.5 jam) per minggu.
     *
     * Senin–Kamis : 390 menit (6.5 jam)
     * Jumat       : 270 menit (4.5 jam)
     * Sabtu       : 420 menit (7 jam)
     */
    'SENIN_SABTU' => [
        1 => 390, // Senin
        2 => 390, // Selasa
        3 => 390, // Rabu
        4 => 390, // Kamis
        5 => 270, // Jumat
        6 => 420, // Sabtu
        7 => 0,   // Minggu — libur
    ],

];
