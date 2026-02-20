<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapAbsensiPusaka extends Model
{
    protected $table = 'rekap_absensi_pusaka';

    protected $fillable = [
        'user_id',
        'bulan',
        'link_drive',
        'status',
        'verified_by',
        'catatan',
    ];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeByBulan($query, string $bulan)
    {
        return $query->where('bulan', $bulan);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Format bulan YYYY-MM menjadi nama bulan Indonesia
     * Contoh: "2026-02" → "Februari 2026"
     */
    public function getNamaBulanAttribute(): string
    {
        $namaBulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',   '05' => 'Mei',       '06' => 'Juni',
            '07' => 'Juli',    '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober', '11' => 'November',  '12' => 'Desember',
        ];

        [$tahun, $bulan] = explode('-', $this->bulan);
        return ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun;
    }

    /**
     * Label badge status untuk tampilan UI
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'valid'    => 'Valid',
            'ditolak'  => 'Ditolak',
            default    => 'Pending',
        };
    }

    /**
     * CSS class Tailwind untuk badge status
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'valid'   => 'bg-green-100 text-green-800',
            'ditolak' => 'bg-red-100 text-red-800',
            default   => 'bg-yellow-100 text-yellow-800',
        };
    }
}
