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
        'role',
        'nip',
        'unit_kerja_id',
        'atasan_id', // Tambahan untuk hierarki approval
        'jabatan',
        'status_pegawai',
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

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class);
    }

    /**
     * User hasMany Kinerja Harian (ProgresHarian tipe KINERJA_HARIAN)
     */
    public function kinerjaHarian()
    {
        return $this->hasMany(ProgresHarian::class, 'user_id')->where('tipe_progres', 'KINERJA_HARIAN');
    }

    /**
     * User hasMany Tugas Atasan Langsung (ProgresHarian tipe TUGAS_ATASAN)
     */
    public function tugasAtasanLangsung()
    {
        return $this->hasMany(ProgresHarian::class, 'user_id')->where('tipe_progres', 'TUGAS_ATASAN');
    }

    // ========================================================================
    // HIERARKI APPROVAL - RELASI ATASAN & BAWAHAN
    // ========================================================================

    /**
     * User belongsTo Atasan (self-referencing)
     *
     * Relasi ke atasan langsung yang meng-approve LKB user ini.
     * Contoh:
     * - ASN → atasan_id = Kabid
     * - Kabid → atasan_id = Kakanwil
     * - JF Ahli Madya → atasan_id = Kakanwil
     * - Kakanwil → atasan_id = null (puncak hierarki)
     */
    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    /**
     * User hasMany Bawahan (self-referencing)
     *
     * Relasi ke semua bawahan yang LKB-nya harus di-approve oleh user ini.
     */
    public function bawahan()
    {
        return $this->hasMany(User::class, 'atasan_id');
    }

    // ========================================================================
    // HELPER METHODS UNTUK APPROVAL HIERARKI
    // ========================================================================

    /**
     * Cek apakah user ini adalah atasan langsung dari user lain
     *
     * @param User $user User yang dicek
     * @return bool
     */
    public function isAtasanDari(User $user): bool
    {
        return $user->atasan_id === $this->id;
    }

    /**
     * Cek apakah user ini adalah bawahan langsung dari user lain
     *
     * @param User $user User yang dicek
     * @return bool
     */
    public function isBawahanDari(User $user): bool
    {
        return $this->atasan_id === $user->id;
    }

    /**
     * Get semua bawahan yang LKB-nya PENDING approval
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBawahanPendingApproval()
    {
        return $this->bawahan()
            ->whereHas('skpTahunan', function ($query) {
                $query->where('status', 'MENUNGGU_APPROVAL');
            })
            ->with(['skpTahunan' => function ($query) {
                $query->where('status', 'MENUNGGU_APPROVAL');
            }])
            ->get();
    }

    /**
     * Cek apakah user punya atasan (bukan puncak hierarki)
     *
     * @return bool
     */
    public function hasAtasan(): bool
    {
        return !is_null($this->atasan_id);
    }

    /**
     * Cek apakah user punya bawahan
     *
     * @return bool
     */
    public function hasBawahan(): bool
    {
        return $this->bawahan()->exists();
    }
}
