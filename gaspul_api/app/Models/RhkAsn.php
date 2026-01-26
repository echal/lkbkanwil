<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RhkAsn extends Model
{
    protected $table = 'rhk_asn';

    protected $fillable = [
        'user_id',
        'rhk_pimpinan_id',
        'tahun',
        'triwulan',
        'rencana_hasil_kerja_asn',
        'indikator_kinerja',
        'target',
        'realisasi',
        'satuan',
        'status',
        'catatan_atasan',
    ];

    protected $casts = [
        'tahun' => 'integer',
    ];

    /**
     * Get the ASN user who owns this RHK
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the RHK Pimpinan that this RHK ASN is based on
     */
    public function rhkPimpinan(): BelongsTo
    {
        return $this->belongsTo(RhkPimpinan::class, 'rhk_pimpinan_id');
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get only draft RHK ASN
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'DRAFT');
    }

    /**
     * Scope to get only submitted RHK ASN
     */
    public function scopeDiajukan($query)
    {
        return $query->where('status', 'DIAJUKAN');
    }

    /**
     * Scope to get only approved RHK ASN
     */
    public function scopeDisetujui($query)
    {
        return $query->where('status', 'DISETUJUI');
    }
}
