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
        'kode_sasaran',
        'nama_sasaran',
        'deskripsi',
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

    public function scopeAktif($query)
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

}
