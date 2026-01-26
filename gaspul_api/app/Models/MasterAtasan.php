<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Master Atasan
 *
 * Managed by: Admin
 * Relasi antara ASN dengan Atasan Langsung per tahun
 *
 * @property int $id
 * @property int $asn_id
 * @property int $atasan_id
 * @property int $tahun
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MasterAtasan extends Model
{
    protected $table = 'master_atasan';

    protected $fillable = [
        'asn_id',
        'atasan_id',
        'tahun',
        'status',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================

    /**
     * ASN yang di-supervisi
     */
    public function asn(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asn_id');
    }

    /**
     * Atasan Langsung
     */
    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    /**
     * Scope: Only active relationships
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'AKTIF');
    }

    /**
     * Scope: By year
     */
    public function scopeByYear($query, int $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    /**
     * Scope: Get atasan for specific ASN
     */
    public function scopeForAsn($query, int $asnId)
    {
        return $query->where('asn_id', $asnId);
    }

    /**
     * Scope: Get all ASN under specific atasan
     */
    public function scopeUnderAtasan($query, int $atasanId)
    {
        return $query->where('atasan_id', $atasanId);
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================

    /**
     * Check if relationship is active
     */
    public function isActive(): bool
    {
        return $this->status === 'AKTIF';
    }

    /**
     * Get atasan for specific ASN and year
     */
    public static function getAtasanForAsn(int $asnId, int $tahun): ?User
    {
        $masterAtasan = self::active()
            ->forAsn($asnId)
            ->byYear($tahun)
            ->first();

        return $masterAtasan?->atasan;
    }

    /**
     * Get all ASN under specific atasan for year
     */
    public static function getAsnUnderAtasan(int $atasanId, int $tahun)
    {
        return self::active()
            ->underAtasan($atasanId)
            ->byYear($tahun)
            ->with('asn')
            ->get()
            ->pluck('asn');
    }
}
