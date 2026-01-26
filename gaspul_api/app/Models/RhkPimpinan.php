<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * RHK Pimpinan (Rencana Hasil Kerja Pimpinan yang di Intervensi)
 *
 * Renamed from: indikator_kinerja
 * Managed by: Atasan Langsung / Kabid
 *
 * Version: 2.0.0 (Total Refactor)
 *
 * @property int $id
 * @property int $indikator_kinerja_id
 * @property string $rhk_pimpinan
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RhkPimpinan extends Model
{
    protected $table = 'rhk_pimpinan';

    protected $fillable = [
        'indikator_kinerja_id',
        'rhk_pimpinan',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================

    /**
     * RHK Pimpinan belongs to Indikator Kinerja
     */
    public function indikatorKinerja(): BelongsTo
    {
        return $this->belongsTo(IndikatorKinerja::class);
    }

    /**
     * RHK Pimpinan has many SKP Tahunan Details
     */
    public function skpTahunanDetails(): HasMany
    {
        return $this->hasMany(SkpTahunanDetail::class, 'rhk_pimpinan_id');
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    /**
     * Scope: Only active RHK
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'AKTIF');
    }

    /**
     * Scope: By Indikator Kinerja
     */
    public function scopeByIndikatorKinerja($query, int $indikatorKinerjaId)
    {
        return $query->where('indikator_kinerja_id', $indikatorKinerjaId);
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================

    /**
     * Check if RHK is active
     */
    public function isActive(): bool
    {
        return $this->status === 'AKTIF';
    }

    /**
     * Get the count of SKP details using this RHK
     */
    public function getUsageCountAttribute(): int
    {
        return $this->skpTahunanDetails()->count();
    }
}
