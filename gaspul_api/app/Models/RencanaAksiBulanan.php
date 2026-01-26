<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rencana Aksi Bulanan
 *
 * Managed by: ASN / PPPK
 * Menggantikan modul "Bulanan" versi lama
 * Breakdown dari SKP Tahunan Detail menjadi rencana aksi bulanan
 *
 * @property int $id
 * @property int $skp_tahunan_detail_id
 * @property int $bulan
 * @property int $tahun
 * @property string|null $rencana_aksi_bulanan
 * @property int $target_bulanan
 * @property string|null $satuan_target
 * @property int $realisasi_bulanan
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RencanaAksiBulanan extends Model
{
    protected $table = 'rencana_aksi_bulanan';

    protected $fillable = [
        'skp_tahunan_detail_id',
        'bulan',
        'tahun',
        'rencana_aksi_bulanan',
        'target_bulanan',
        'satuan_target',
        'realisasi_bulanan',
        'status',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'target_bulanan' => 'integer',
        'realisasi_bulanan' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['bulan_nama', 'capaian_persen'];

    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================

    /**
     * Rencana Aksi Bulanan belongs to SKP Tahunan Detail
     */
    public function skpTahunanDetail(): BelongsTo
    {
        return $this->belongsTo(SkpTahunanDetail::class, 'skp_tahunan_detail_id');
    }

    /**
     * Rencana Aksi Bulanan has many Progres Harian
     */
    public function progresHarian(): HasMany
    {
        return $this->hasMany(ProgresHarian::class, 'rencana_aksi_bulanan_id');
    }

    // ============================================================================
    // ACCESSORS
    // ============================================================================

    /**
     * Get nama bulan in Bahasa Indonesia
     */
    public function getBulanNamaAttribute(): string
    {
        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $namaBulan[$this->bulan] ?? '-';
    }

    /**
     * Get capaian percentage
     */
    public function getCapaianPersenAttribute(): float
    {
        if ($this->target_bulanan == 0) {
            return 0;
        }

        return round(($this->realisasi_bulanan / $this->target_bulanan) * 100, 2);
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    /**
     * Scope: By bulan
     */
    public function scopeByBulan($query, int $bulan)
    {
        return $query->where('bulan', $bulan);
    }

    /**
     * Scope: By tahun
     */
    public function scopeByTahun($query, int $tahun)
    {
        return $query->where('tahun', $tahun);
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Active (has been filled)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'AKTIF');
    }

    /**
     * Scope: Belum diisi
     */
    public function scopeBelumDiisi($query)
    {
        return $query->where('status', 'BELUM_DIISI');
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================

    /**
     * Check if rencana aksi has been filled
     */
    public function isFilled(): bool
    {
        return $this->status !== 'BELUM_DIISI' && !empty($this->rencana_aksi_bulanan);
    }

    /**
     * Check if rencana aksi is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'SELESAI';
    }

    /**
     * Update realisasi from progres harian
     */
    public function updateRealisasi(): void
    {
        $this->realisasi_bulanan = $this->progresHarian()->sum('progres');
        $this->save();
    }

    /**
     * Auto-generate rencana aksi bulanan for 12 months
     */
    public static function autoGenerateForDetail(int $skpTahunanDetailId, int $tahun): void
    {
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            self::firstOrCreate(
                [
                    'skp_tahunan_detail_id' => $skpTahunanDetailId,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                ],
                [
                    'status' => 'BELUM_DIISI',
                    'target_bulanan' => 0,
                    'realisasi_bulanan' => 0,
                ]
            );
        }
    }
}
