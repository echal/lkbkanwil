<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RencanaKerjaAsn extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'rencana_kerja_asn';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'skp_tahunan_id', // Referensi ke SKP Tahunan Header
        'skp_tahunan_detail_id', // Referensi ke SKP Tahunan Detail (Butir Kinerja)
        'sasaran_kegiatan_id',
        'indikator_kinerja_id',
        'tahun',
        'triwulan',
        'target',
        'satuan',
        'realisasi',
        'catatan_asn',
        'status',
        'catatan_atasan',
        'approved_by',
        'approved_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tahun' => 'integer',
        'target' => 'decimal:2',
        'realisasi' => 'decimal:2',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke User (ASN yang membuat)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke User (Atasan yang meng-approve)
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relasi ke Sasaran Kegiatan
     */
    public function sasaranKegiatan(): BelongsTo
    {
        return $this->belongsTo(SasaranKegiatan::class);
    }

    /**
     * Relasi ke SKP Tahunan (Header)
     */
    public function skpTahunan(): BelongsTo
    {
        return $this->belongsTo(SkpTahunan::class);
    }

    /**
     * Relasi ke SKP Tahunan Detail (Butir Kinerja)
     */
    public function skpTahunanDetail(): BelongsTo
    {
        return $this->belongsTo(SkpTahunanDetail::class, 'skp_tahunan_detail_id');
    }

    /**
     * Relasi ke Indikator Kinerja
     */
    public function indikatorKinerja(): BelongsTo
    {
        return $this->belongsTo(IndikatorKinerja::class);
    }

    /**
     * Relasi ke Bulanan (one SKP has many Bulanan)
     */
    public function bulanan(): HasMany
    {
        return $this->hasMany(Bulanan::class, 'rencana_kerja_asn_id');
    }

    /**
     * Scope untuk query hanya rencana kerja milik ASN tertentu
     */
    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope untuk query berdasarkan tahun
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('tahun', $year);
    }

    /**
     * Scope untuk query berdasarkan triwulan
     */
    public function scopeByTriwulan($query, $triwulan)
    {
        return $query->where('triwulan', $triwulan);
    }

    /**
     * Scope untuk query berdasarkan status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Calculate achievement percentage
     *
     * @return float
     */
    public function getCapaianPersenAttribute(): float
    {
        if ($this->target == 0) {
            return 0;
        }

        return round(($this->realisasi / $this->target) * 100, 2);
    }

    /**
     * Check if this rencana can be edited
     *
     * @return bool
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['DRAFT', 'DITOLAK']);
    }

    /**
     * Check if this rencana can be submitted for approval
     *
     * @return bool
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === 'DRAFT';
    }

    /**
     * Check if this rencana can be approved/rejected
     *
     * @return bool
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'DIAJUKAN';
    }
}
