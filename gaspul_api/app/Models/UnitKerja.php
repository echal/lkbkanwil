<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitKerja extends Model
{
    protected $table = 'unit_kerja';

    protected $fillable = [
        'kode_unit',
        'nama_unit',
        'eselon',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 1 Unit → banyak Pegawai
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'unit_kerja_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'AKTIF');
    }
}
