<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SkpTahunanDetail;
use App\Models\UnitKerja;

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
        'unit_kerja_id',
        'kode_indikator',
        'nama_indikator',
        'satuan',
        'tipe_target',
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
    public function scopeAktif($query)
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
     * Relasi ke SKP Tahunan Detail
     * Satu indikator kinerja bisa dipilih di banyak SKP Tahunan Detail
     */
    public function skpTahunanDetails(): HasMany
    {
        return $this->hasMany(SkpTahunanDetail::class, 'indikator_kinerja_id');
    }

    /**
     * Relasi ke Unit Kerja
     * Indikator bisa dibatasi untuk unit kerja tertentu
     */
    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

}
