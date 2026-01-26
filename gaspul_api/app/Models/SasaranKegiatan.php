<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SasaranKegiatan extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sasaran_kegiatan';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unit_kerja',
        'sasaran_kegiatan',
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
     * Scope untuk query hanya sasaran yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'AKTIF');
    }

    /**
     * Relasi ke Indikator Kinerja
     * Satu sasaran bisa punya banyak indikator
     */
    public function indikatorKinerja(): HasMany
    {
        return $this->hasMany(IndikatorKinerja::class);
    }

    /**
     * Get only active indikator kinerja
     */
    public function indikatorKinerjaAktif(): HasMany
    {
        return $this->hasMany(IndikatorKinerja::class)->where('status', 'AKTIF');
    }

    /**
     * Check if this sasaran is being used by any ASN
     *
     * @return bool
     */
    public function isDigunakanAsn(): bool
    {
        // TODO: Implement logic to check if Sasaran is used by ASN
        // This should check the relationship with ASN's SKP data
        return false;
    }
}
