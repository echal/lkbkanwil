<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Model Laporan Bulanan Kinerja ASN
 *
 * Menyimpan ringkasan laporan kinerja bulanan yang dikirim ASN ke atasan.
 * Satu record per user per bulan per tahun.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $bulan          1–12
 * @property int         $tahun
 * @property int         $total_hari     Hari efektif kerja
 * @property int         $total_jam      Total jam kerja
 * @property float       $capaian_persen Persentase dari target 165 jam
 * @property string      $status         DRAFT | DIKIRIM | DISETUJUI | DITOLAK
 * @property int|null    $approved_by
 * @property Carbon|null $approved_at
 * @property string|null $catatan
 */
class LaporanBulananKinerja extends Model
{
    protected $table = 'laporan_bulanan_kinerja';

    const STATUS_DRAFT     = 'DRAFT';
    const STATUS_DIKIRIM   = 'DIKIRIM';
    const STATUS_DISETUJUI = 'DISETUJUI';
    const STATUS_DITOLAK   = 'DITOLAK';

    protected $fillable = [
        'user_id',
        'bulan',
        'tahun',
        'total_hari',
        'total_jam',
        'capaian_persen',
        'status',
        'approved_by',
        'approved_at',
        'catatan',
    ];

    protected $casts = [
        'bulan'          => 'integer',
        'tahun'          => 'integer',
        'total_hari'     => 'integer',
        'total_jam'      => 'integer',
        'capaian_persen' => 'float',
        'approved_at'    => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Accessors ──────────────────────────────────────────────────────────

    /** "Februari 2026" */
    public function getNamaBulanAttribute(): string
    {
        $names = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return ($names[$this->bulan] ?? '-') . ' ' . $this->tahun;
    }

    /** Label status untuk UI */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_DIKIRIM   => 'Menunggu Persetujuan',
            self::STATUS_DISETUJUI => 'Disetujui',
            self::STATUS_DITOLAK   => 'Ditolak',
            default                => $this->status,
        };
    }

    /** Tailwind badge class */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'bg-gray-100 text-gray-700',
            self::STATUS_DIKIRIM   => 'bg-yellow-100 text-yellow-800',
            self::STATUS_DISETUJUI => 'bg-green-100 text-green-800',
            self::STATUS_DITOLAK   => 'bg-red-100 text-red-800',
            default                => 'bg-gray-100 text-gray-600',
        };
    }

    /** Apakah laporan bisa dikirim ulang (setelah DITOLAK atau masih DRAFT) */
    public function getIsKirimableAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_DITOLAK]);
    }
}
