<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekapAbsensiHistori extends Model
{
    protected $table = 'rekap_absensi_histori';

    protected $fillable = [
        'rekap_absensi_id',
        'link_drive_lama',
        'revision_number',
        'tanggal_revisi',
    ];

    protected $casts = [
        'tanggal_revisi' => 'datetime',
    ];

    public function rekap(): BelongsTo
    {
        return $this->belongsTo(RekapAbsensiPusaka::class, 'rekap_absensi_id');
    }
}
