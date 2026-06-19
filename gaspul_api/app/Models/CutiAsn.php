<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CutiAsn extends Model
{
    protected $table = 'cuti_asn';

    protected $fillable = [
        'user_id',
        'jenis',
        'kategori',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'bukti_dukung',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cek apakah tanggal tertentu masuk dalam periode cuti user.
     * Query ringan — 1 row lookup dengan index (user_id, tanggal_mulai, tanggal_selesai).
     */
    public static function isSedangCuti(int $userId, string $tanggal): bool
    {
        return self::where('user_id', $userId)
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->exists();
    }

    /**
     * Ambil semua record cuti dalam satu bulan untuk user tertentu.
     * Dipakai oleh LaporanBulananService — 1 query per bulan, no N+1.
     */
    public static function getForBulan(int $userId, int $bulan, int $tahun): \Illuminate\Support\Collection
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
        $endDate   = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();

        return self::where('user_id', $userId)
            ->where('tanggal_mulai', '<=', $endDate)
            ->where('tanggal_selesai', '>=', $startDate)
            ->get(['id', 'jenis', 'kategori', 'tanggal_mulai', 'tanggal_selesai']);
    }

    /**
     * Cek overlap dengan periode cuti lain milik user yang sama.
     * Excludes $excludeId untuk keperluan edit.
     */
    public static function hasOverlap(int $userId, string $mulai, string $selesai, ?int $excludeId = null): bool
    {
        $query = self::where('user_id', $userId)
            ->where('tanggal_mulai', '<=', $selesai)
            ->where('tanggal_selesai', '>=', $mulai);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
