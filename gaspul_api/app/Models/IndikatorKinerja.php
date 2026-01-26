<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndikatorKinerja extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'indikator_kinerja';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sasaran_kegiatan_id',
        'indikator_kinerja',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope untuk query hanya indikator yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'AKTIF');
    }

    /**
     * Relasi ke Sasaran Kegiatan
     * Banyak indikator belongs to satu sasaran
     */
    public function sasaranKegiatan(): BelongsTo
    {
        return $this->belongsTo(SasaranKegiatan::class);
    }

    /**
     * Check if this indikator is being used by any ASN
     *
     * @return bool
     */
    public function isDigunakanAsn(): bool
    {
        // TODO: Implement logic to check if Indikator is used by ASN
        // This should check the relationship with ASN's SKP data
        return false;
    }
}
