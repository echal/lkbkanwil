<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RekapAbsensiPusaka extends Model
{
    protected $table = 'rekap_absensi_pusaka';

    // =========================================================================
    // STATUS CONSTANTS
    // =========================================================================

    const STATUS_PENDING_KABID     = 'pending_kabid';
    const STATUS_PENDING_KAKANWIL  = 'pending_kakanwil';
    const STATUS_APPROVED          = 'approved';
    const STATUS_REJECTED_KABID    = 'rejected_kabid';
    const STATUS_REJECTED_KAKANWIL = 'rejected_kakanwil';

    protected $fillable = [
        'user_id',
        'bulan',
        'link_drive',
        'status',
        'verified_by',
        'verified_by_kakanwil',
        'catatan',
        'catatan_kakanwil',
        'revision_count',
    ];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifierKakanwil(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_kakanwil');
    }

    public function histori(): HasMany
    {
        return $this->hasMany(RekapAbsensiHistori::class, 'rekap_absensi_id')
                    ->orderBy('revision_number');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeByBulan($query, string $bulan)
    {
        return $query->where('bulan', $bulan);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Format bulan YYYY-MM menjadi nama bulan Indonesia
     * Contoh: "2026-02" → "Februari 2026"
     */
    public function getNamaBulanAttribute(): string
    {
        $namaBulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',   '05' => 'Mei',       '06' => 'Juni',
            '07' => 'Juli',    '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober', '11' => 'November',  '12' => 'Desember',
        ];

        [$tahun, $bulan] = explode('-', $this->bulan);
        return ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun;
    }

    /**
     * Batas waktu upload/revisi: tanggal 5 bulan berikutnya, pukul 23:59:59
     * Contoh: bulan 2026-01 → deadline 5 Februari 2026 23:59:59
     */
    public function getDeadlineUploadAttribute(): Carbon
    {
        [$tahun, $bulan] = explode('-', $this->bulan);
        return Carbon::create((int) $tahun, (int) $bulan, 1)
                     ->addMonth()
                     ->day(5)
                     ->endOfDay();
    }

    /**
     * Apakah deadline upload/revisi sudah terlewat?
     */
    public function getIsDeadlinePastAttribute(): bool
    {
        return now()->gt($this->deadline_upload);
    }

    /**
     * Apakah ASN masih boleh merevisi rekap ini?
     * Syarat: status rejected (kabid atau kakanwil) DAN deadline belum lewat.
     */
    public function getIsRevisableAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_REJECTED_KABID, self::STATUS_REJECTED_KAKANWIL])
            && ! $this->is_deadline_past;
    }

    /**
     * Label badge status untuk tampilan UI
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_KABID     => 'Menunggu Kabid',
            self::STATUS_PENDING_KAKANWIL  => 'Menunggu Kakanwil',
            self::STATUS_APPROVED          => 'Disetujui',
            self::STATUS_REJECTED_KABID    => 'Ditolak Kabid',
            self::STATUS_REJECTED_KAKANWIL => 'Ditolak Kakanwil',
            default                        => 'Tidak Diketahui',
        };
    }

    /**
     * CSS class Tailwind untuk badge status
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING_KABID,
            self::STATUS_PENDING_KAKANWIL  => 'bg-yellow-100 text-yellow-800',
            self::STATUS_APPROVED          => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED_KABID,
            self::STATUS_REJECTED_KAKANWIL => 'bg-red-100 text-red-800',
            default                        => 'bg-gray-100 text-gray-600',
        };
    }
}
