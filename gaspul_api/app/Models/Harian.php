<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Harian extends Model
{
    protected $table = 'harian';

    protected $fillable = [
        'bulanan_id',
        'tanggal',
        'kegiatan_harian',
        'progres',
        'satuan',
        'waktu_kerja',
        'bukti_type',
        'bukti_path',
        'bukti_link',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'progres' => 'integer',
        'waktu_kerja' => 'integer',
    ];

    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================

    /**
     * Harian belongs to Bulanan
     */
    public function bulanan(): BelongsTo
    {
        return $this->belongsTo(Bulanan::class, 'bulanan_id');
    }

    // ============================================================================
    // QUERY SCOPES
    // ============================================================================

    /**
     * Scope by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    /**
     * Scope by month and year
     */
    public function scopeByMonthYear($query, $bulan, $tahun)
    {
        return $query->whereYear('tanggal', $tahun)
                     ->whereMonth('tanggal', $bulan);
    }

    /**
     * Scope for specific Bulanan
     */
    public function scopeForBulanan($query, $bulananId)
    {
        return $query->where('bulanan_id', $bulananId);
    }

    // ============================================================================
    // BUSINESS LOGIC
    // ============================================================================

    /**
     * Check if date is valid for the parent Bulanan
     * Date must be within the month of Bulanan
     */
    public function isValidDateForBulanan(): bool
    {
        if (!$this->bulanan) {
            return false;
        }

        $tanggalCarbon = Carbon::parse($this->tanggal);
        return $tanggalCarbon->year == $this->bulanan->tahun &&
               $tanggalCarbon->month == $this->bulanan->bulan;
    }

    /**
     * Check if bukti is provided
     * Either file or link must be provided
     */
    public function hasBukti(): bool
    {
        if ($this->bukti_type === 'file') {
            return !empty($this->bukti_path);
        } elseif ($this->bukti_type === 'link') {
            return !empty($this->bukti_link);
        }
        return false;
    }

    /**
     * Get bukti display text
     */
    public function getBuktiDisplayAttribute(): string
    {
        if ($this->bukti_type === 'file') {
            return $this->bukti_path ? basename($this->bukti_path) : 'Tidak ada file';
        } elseif ($this->bukti_type === 'link') {
            return $this->bukti_link ?? 'Tidak ada link';
        }
        return 'Tidak ada bukti';
    }

    /**
     * Get full bukti URL for file
     */
    public function getBuktiUrlAttribute(): ?string
    {
        if ($this->bukti_type === 'file' && $this->bukti_path) {
            return url('storage/' . $this->bukti_path);
        }
        return $this->bukti_link;
    }

    // ============================================================================
    // EVENTS
    // ============================================================================

    /**
     * Boot model events
     */
    protected static function booted()
    {
        // After creating Harian, update Bulanan realisasi
        static::created(function ($harian) {
            $harian->bulanan->updateRealisasiFromHarian();
        });

        // After updating Harian, update Bulanan realisasi
        static::updated(function ($harian) {
            $harian->bulanan->updateRealisasiFromHarian();
        });

        // After deleting Harian, update Bulanan realisasi
        static::deleted(function ($harian) {
            $harian->bulanan->updateRealisasiFromHarian();
        });
    }
}
