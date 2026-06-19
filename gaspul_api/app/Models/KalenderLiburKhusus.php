<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KalenderLiburKhusus extends Model
{
    protected $table = 'kalender_libur_khusus';

    protected $fillable = [
        'unit_kerja_id',
        'berlaku_ke_anak',
        'target_khusus',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
        'berlaku_ke_anak' => 'boolean',
    ];

    const STATUS_DRAFT = 'DRAFT';
    const STATUS_AKTIF = 'AKTIF';

    const TARGET_GURU     = 'GURU';
    const TARGET_PENYULUH = 'PENYULUH';
    const TARGET_PENGHULU = 'PENGHULU';

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', self::STATUS_AKTIF);
    }
}
