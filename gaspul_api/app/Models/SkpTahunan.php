<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SKP Tahunan (HEADER)
 *
 * REFACTORED TO HEADER-DETAIL PATTERN:
 * - Table skp_tahunan = HEADER (user_id + tahun + status)
 * - Table skp_tahunan_detail = DETAIL (butir kinerja, multiple rows)
 * - UNIQUE constraint: user_id + tahun ONLY
 * - ASN boleh tambah berkali-kali butir kinerja meskipun sasaran/indikator sama
 */
class SkpTahunan extends Model
{
    protected $table = 'skp_tahunan';

    protected $fillable = [
        'user_id',
        'tahun',
        'status',
        'catatan_atasan',
        'approved_by',
        'approved_at',
        'alasan_revisi',
        'revisi_diajukan_at',
        'revisi_disetujui_at',
        'catatan_revisi',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'approved_at' => 'datetime',
        'revisi_diajukan_at' => 'datetime',
        'revisi_disetujui_at' => 'datetime',
    ];

    protected $appends = ['total_butir_kinerja'];

    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================

    /**
     * SKP Tahunan (Header) belongs to User (ASN)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * SKP Tahunan (Header) has many Details (Butir Kinerja)
     */
    public function details(): HasMany
    {
        return $this->hasMany(SkpTahunanDetail::class, 'skp_tahunan_id');
    }

    /**
     * Atasan yang menyetujui
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ============================================================================
    // BUSINESS LOGIC METHODS
    // ============================================================================

    /**
     * Check if SKP Tahunan sudah disetujui
     */
    public function isApproved(): bool
    {
        return $this->status === 'DISETUJUI';
    }

    /**
     * Check if SKP Tahunan bisa diajukan revisi
     * (hanya jika status DISETUJUI)
     */
    public function canRequestRevision(): bool
    {
        return $this->status === 'DISETUJUI';
    }

    /**
     * Check if SKP Tahunan sedang menunggu persetujuan revisi
     */
    public function isPendingRevision(): bool
    {
        return $this->status === 'REVISI_DIAJUKAN';
    }

    /**
     * Check if revisi sudah ditolak
     */
    public function isRevisionRejected(): bool
    {
        return $this->status === 'REVISI_DITOLAK';
    }

    /**
     * Check if SKP Tahunan bisa disubmit
     * (status DRAFT atau DITOLAK, dan ada minimal 1 butir kinerja)
     */
    public function canBeSubmitted(): bool
    {
        return in_array($this->status, ['DRAFT', 'DITOLAK'])
            && $this->details()->count() > 0;
    }

    /**
     * Check if SKP Tahunan bisa ditambahkan butir kinerja baru
     * (status DRAFT atau DITOLAK)
     */
    public function canAddDetails(): bool
    {
        return in_array($this->status, ['DRAFT', 'DITOLAK']);
    }

    /**
     * Check if detail bisa diedit/dihapus
     * (status DRAFT atau DITOLAK)
     *
     * CATATAN: REVISI_DITOLAK TIDAK boleh edit karena SKP tetap DISETUJUI
     */
    public function canEditDetails(): bool
    {
        return in_array($this->status, ['DRAFT', 'DITOLAK']);
    }

    /**
     * Get total butir kinerja
     */
    public function getTotalButirKinerjaAttribute(): int
    {
        return $this->details()->count();
    }

    /**
     * Hitung total target tahunan dari semua detail
     */
    public function getTotalTargetAttribute(): int
    {
        return $this->details()->sum('target_tahunan');
    }

    /**
     * Hitung total realisasi tahunan dari semua detail
     */
    public function getTotalRealisasiAttribute(): int
    {
        return $this->details()->sum('realisasi_tahunan');
    }

    /**
     * Hitung capaian persentase rata-rata dari semua detail
     */
    public function getCapaianPersenAttribute(): int
    {
        $details = $this->details;

        if ($details->count() === 0) {
            return 0;
        }

        $totalCapaian = $details->sum('capaian_persen');
        return (int) round($totalCapaian / $details->count());
    }

    /**
     * Get display string untuk dropdown/list
     */
    public function getDisplayNameAttribute(): string
    {
        $butirCount = $this->total_butir_kinerja;
        return sprintf(
            'SKP Tahunan %d (%d Butir Kinerja)',
            $this->tahun,
            $butirCount
        );
    }

    /**
     * Scope: Get SKP for specific user and year
     */
    public function scopeForUserAndYear($query, int $userId, int $tahun)
    {
        return $query->where('user_id', $userId)->where('tahun', $tahun);
    }

    /**
     * Scope: Get SKP that are approved
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'DISETUJUI');
    }
}
