<?php

namespace App\Helpers;

/**
 * Helper validasi dan klasifikasi link eviden e_SARAku.
 *
 * Tidak melakukan request HTTP ke Google sama sekali.
 * Semua validasi berbasis parsing URL di PHP — zero latency, zero rate limit risk.
 */
class EvidenHelper
{
    /**
     * Domain Google yang diizinkan sebagai eviden.
     */
    private const ALLOWED_DOMAINS = [
        'drive.google.com',
        'docs.google.com',
        'sheets.google.com',
        'forms.google.com',
        'slides.google.com',
    ];

    /**
     * Pesan error standar yang ditampilkan ke ASN.
     */
    public const ERROR_DOMAIN = 'Link eviden harus menggunakan Google Drive atau layanan Google Workspace yang didukung (Drive, Docs, Sheets, Forms, Slides).';

    /**
     * Cek apakah link_bukti valid untuk disimpan.
     *
     * Aturan:
     * - Kosong/null → valid (eviden opsional)
     * - Terisi → harus domain Google yang diizinkan
     *
     * @param string|null $url
     * @return bool
     */
    public static function isValid(?string $url): bool
    {
        if (empty($url)) {
            return true; // kosong = boleh
        }

        return self::isAllowedDomain($url);
    }

    /**
     * Cek apakah host URL termasuk domain yang diizinkan.
     */
    public static function isAllowedDomain(?string $url): bool
    {
        if (empty($url)) {
            return true;
        }

        $host = parse_url(trim($url), PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        // Normalisasi: hapus www. jika ada
        $host = strtolower(preg_replace('/^www\./i', '', $host));

        foreach (self::ALLOWED_DOMAINS as $allowed) {
            if ($host === $allowed) {
                return true;
            }
        }

        return false;
    }

    /**
     * Klasifikasi link untuk monitoring kualitas.
     *
     * Return: 'GOOGLE_VALID' | 'NON_STANDAR' | 'KOSONG'
     */
    public static function classify(?string $url): string
    {
        if (empty($url)) {
            return 'KOSONG';
        }

        return self::isAllowedDomain($url) ? 'GOOGLE_VALID' : 'NON_STANDAR';
    }

    /**
     * Daftar domain yang diizinkan — untuk tampil di UI.
     */
    public static function allowedDomains(): array
    {
        return self::ALLOWED_DOMAINS;
    }

    /**
     * Validasi ringan di frontend (JavaScript) — kembalikan string JSON
     * berisi whitelist untuk dipakai di Alpine.js tanpa request server.
     */
    public static function allowedDomainsJson(): string
    {
        return json_encode(self::ALLOWED_DOMAINS);
    }
}
