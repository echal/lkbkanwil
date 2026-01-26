<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndikatorTahunan extends Model
{
    protected $table = 'indikator_tahunan';

    protected $fillable = [
        'user_id',
        'nama_indikator',
        'deskripsi',
        'tahun',
        'target',
        'satuan',
        'status',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'target' => 'decimal:2',
    ];

    /**
     * Relasi ke User (ASN yang membuat indikator)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
