<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SKP Tahunan Detail (Butir Kinerja)
 *
 * Version: 2.0.0 (Total Refactor)
 *
 * PERUBAHAN KRUSIAL:
 * - HAPUS: sasaran_kegiatan_id (tidak perlu ditampilkan)
 * - UBAH: indikator_kinerja_id → rhk_pimpinan_id
 * - TAMBAH: rencana_aksi (TEXT - rencana aksi ASN)
 * - NO UNIQUE CONSTRAINT - ASN boleh tambah RHK yang sama berkali-kali
 * - VALIDASI UNIQUE di level aplikasi: skp_tahunan_id + rhk_pimpinan_id + rencana_aksi
 */
class SkpTahunanDetail extends Model
{
    use HasFactory;

    protected $table = 'skp_tahunan_detail';

    protected $fillable = [
        'skp_tahunan_id',
        'rhk_pimpinan_id',
        'target_tahunan',
        'satuan',
        'rencana_aksi',
        'realisasi_tahunan',
    ];

    protected $casts = [
        'target_tahunan' => 'integer',
        'realisasi_tahunan' => 'integer',
    ];

    protected $appends = ['capaian_persen'];

    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================

    /**
     * SKP Tahunan Detail belongs to SKP Tahunan (Header)
     */
    public function skpTahunan(): BelongsTo
    {
        return $this->belongsTo(SkpTahunan::class, 'skp_tahunan_id');
    }

    /**
     * SKP Tahunan Detail belongs to RHK Pimpinan
     */
    public function rhkPimpinan(): BelongsTo
    {
        return $this->belongsTo(RhkPimpinan::class, 'rhk_pimpinan_id');
    }

    /**
     * SKP Tahunan Detail has many Rencana Aksi Bulanan
     */
    public function rencanaAksiBulanan(): HasMany
    {
        return $this->hasMany(RencanaAksiBulanan::class, 'skp_tahunan_detail_id');
    }

    // ============================================================================
    // ACCESSORS
    // ============================================================================

    /**
     * Get capaian percentage
     */
    public function getCapaianPersenAttribute(): int
    {
        if ($this->target_tahunan == 0) {
            return 0;
        }

        return (int) round(($this->realisasi_tahunan / $this->target_tahunan) * 100);
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================

    /**
     * Get display string for dropdown/list
     */
    public function getDisplayNameAttribute(): string
    {
        return sprintf(
            '%s (%d %s) - %s',
            $this->rhkPimpinan->rhk_pimpinan ?? '-',
            $this->target_tahunan,
            $this->satuan,
            \Str::limit($this->rencana_aksi, 50)
        );
    }

    /**
     * Check if detail can be edited/deleted
     */
    public function canBeEdited(): bool
    {
        return $this->skpTahunan->canEditDetails();
    }

    /**
     * Update realisasi tahunan from rencana aksi bulanan
     */
    public function updateRealisasi(): void
    {
        $this->realisasi_tahunan = $this->rencanaAksiBulanan()->sum('realisasi_bulanan');
        $this->save();
    }

    // ============================================================================
    // EVENTS
    // ============================================================================

    /**
     * Boot method untuk handle events
     */
    protected static function boot()
    {
        parent::boot();

        // After create, auto-generate rencana aksi bulanan (12 bulan)
        static::created(function ($detail) {
            RencanaAksiBulanan::autoGenerateForDetail($detail->id, $detail->skpTahunan->tahun);
        });

        // After delete, cleanup rencana aksi bulanan (cascade handled by FK)
    }
}

