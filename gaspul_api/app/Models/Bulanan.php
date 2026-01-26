<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bulanan extends Model
{
    protected $table = 'bulanan';

    protected $fillable = [
        'rencana_kerja_asn_id',
        'bulan',
        'tahun',
        'target_bulanan',
        'rencana_kerja_bulanan',
        'realisasi_bulanan',
        'status',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'target_bulanan' => 'integer',
        'realisasi_bulanan' => 'integer',
    ];

    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================

    /**
     * Bulanan belongs to RencanaKerjaAsn (SKP Triwulan)
     */
    public function rencanaKerjaAsn(): BelongsTo
    {
        return $this->belongsTo(RencanaKerjaAsn::class, 'rencana_kerja_asn_id');
    }

    /**
     * Bulanan has many Harian
     */
    public function harian(): HasMany
    {
        return $this->hasMany(Harian::class, 'bulanan_id');
    }

    // ============================================================================
    // QUERY SCOPES
    // ============================================================================

    /**
     * Scope by year
     */
    public function scopeByYear($query, $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    /**
     * Scope by month
     */
    public function scopeByMonth($query, $bulan)
    {
        return $query->where('bulan', $bulan);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for specific SKP
     */
    public function scopeForSkp($query, $skpId)
    {
        return $query->where('rencana_kerja_asn_id', $skpId);
    }

    // ============================================================================
    // BUSINESS LOGIC
    // ============================================================================

    /**
     * Check if target has been filled
     */
    public function hasTargetFilled(): bool
    {
        return $this->target_bulanan !== null && $this->target_bulanan > 0;
    }

    /**
     * Check if Harian can be created
     * Requirement: target_bulanan must be filled
     */
    public function canCreateHarian(): bool
    {
        return $this->hasTargetFilled() &&
               $this->rencanaKerjaAsn->status === 'DISETUJUI' &&
               $this->status === 'AKTIF';
    }

    /**
     * Calculate capaian percentage for this month
     */
    public function getCapaianPersenAttribute(): float
    {
        if ($this->target_bulanan == 0) {
            return 0;
        }
        return round(($this->realisasi_bulanan / $this->target_bulanan) * 100, 2);
    }

    /**
     * Get month name in Indonesian
     */
    public function getBulanNamaAttribute(): string
    {
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $namaBulan[$this->bulan] ?? '-';
    }

    /**
     * Update realisasi from Harian sum
     */
    public function updateRealisasiFromHarian(): void
    {
        $totalRealisasi = $this->harian()->sum('progres');
        $this->update(['realisasi_bulanan' => $totalRealisasi]);
    }
}
