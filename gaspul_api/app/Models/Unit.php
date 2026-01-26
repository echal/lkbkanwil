<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'nama_unit',
        'kode_unit',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Unit has many Users
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if unit is being used by any user
     */
    public function isDigunakanPegawai(): bool
    {
        return $this->users()->exists();
    }

    /**
     * Get count of users in this unit
     */
    public function getJumlahPegawaiAttribute(): int
    {
        return $this->users()->count();
    }
}
