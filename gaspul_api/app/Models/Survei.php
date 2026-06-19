<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Survei extends Model
{
    protected $table = 'survei';

    protected $fillable = [
        'judul', 'deskripsi', 'periode', 'is_required', 'status',
        'dibuka_at', 'ditutup_at',
    ];

    protected $casts = [
        'dibuka_at'  => 'datetime',
        'ditutup_at' => 'datetime',
        'is_required' => 'boolean',
    ];

    const STATUS_DRAFT = 'DRAFT';
    const STATUS_AKTIF = 'AKTIF';
    const STATUS_TUTUP = 'TUTUP';

    public function pertanyaan()
    {
        return $this->hasMany(SurveiPertanyaan::class)->orderBy('urutan');
    }

    public function jawaban()
    {
        return $this->hasMany(SurveiJawaban::class);
    }

    public function jawabanUser(int $userId)
    {
        return $this->jawaban()->where('user_id', $userId)->first();
    }

    public function scopeAktif($query)
    {
        return $query->where('status', self::STATUS_AKTIF);
    }
}
