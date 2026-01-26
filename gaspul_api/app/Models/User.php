<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // ASN | ATASAN | ADMIN
        'nip',
        'unit_kerja', // DEPRECATED - Use unit_id instead (kept for backward compatibility)
        'unit_id', // FK to units table
        'jabatan',
        'status', // AKTIF | NONAKTIF
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relationship: User belongs to Unit
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get unit name (with fallback to unit_kerja for backward compatibility)
     */
    public function getUnitNameAttribute(): ?string
    {
        // Prefer unit relationship if exists
        if ($this->unit_id && $this->unit) {
            return $this->unit->nama_unit;
        }

        // Fallback to old unit_kerja field
        return $this->unit_kerja;
    }
}
