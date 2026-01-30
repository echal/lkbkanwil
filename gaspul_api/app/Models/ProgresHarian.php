<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Progres Harian
 *
 * Managed by: ASN / PPPK
 * Menggantikan modul "Harian" versi lama
 * Input progres harian berbasis Rencana Aksi Bulanan
 *
 * @property int $id
 * @property int $rencana_aksi_bulanan_id
 * @property string $tanggal
 * @property string $jam_mulai
 * @property string $jam_selesai
 * @property int $durasi_menit
 * @property string $rencana_kegiatan_harian
 * @property int $progres
 * @property string $satuan
 * @property string|null $bukti_dukung
 * @property string $status_bukti
 * @property string|null $keterangan
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProgresHarian extends Model
{
    protected $table = 'progres_harian';

    protected $fillable = [
        'user_id', // For TUGAS_ATASAN ownership
        'rencana_aksi_bulanan_id',
        'tipe_progres', // KINERJA_HARIAN | TUGAS_ATASAN
        'tugas_atasan', // Untuk tipe TUGAS_ATASAN
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        // 'durasi_menit' is auto-calculated, not fillable
        'rencana_kegiatan_harian',
        'progres',
        'satuan',
        'bukti_dukung',
        'status_bukti',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'progres' => 'integer',
        'durasi_menit' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['durasi_jam'];

    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================

    /**
     * Progres Harian belongs to User (ASN)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Progres Harian belongs to Rencana Aksi Bulanan
     */
    public function rencanaAksiBulanan(): BelongsTo
    {
        return $this->belongsTo(RencanaAksiBulanan::class, 'rencana_aksi_bulanan_id');
    }

    // ============================================================================
    // ACCESSORS
    // ============================================================================

    /**
     * Get durasi in hours format (e.g., "2.5 jam")
     */
    public function getDurasiJamAttribute(): string
    {
        if (!$this->durasi_menit) {
            return '0 jam';
        }

        $jam = floor($this->durasi_menit / 60);
        $menit = $this->durasi_menit % 60;

        if ($menit == 0) {
            return "{$jam} jam";
        }

        return "{$jam} jam {$menit} menit";
    }

    // ============================================================================
    // SCOPES
    // ============================================================================

    /**
     * Scope: By tanggal
     */
    public function scopeByTanggal($query, $tanggal)
    {
        return $query->whereDate('tanggal', $tanggal);
    }

    /**
     * Scope: By bulan
     */
    public function scopeByBulan($query, int $bulan, int $tahun)
    {
        return $query->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun);
    }

    /**
     * Scope: By status bukti
     */
    public function scopeByStatusBukti($query, string $status)
    {
        return $query->where('status_bukti', $status);
    }

    /**
     * Scope: Belum ada bukti
     */
    public function scopeBelumAdaBukti($query)
    {
        return $query->where('status_bukti', 'BELUM_ADA');
    }

    /**
     * Scope: Sudah ada bukti
     */
    public function scopeSudahAdaBukti($query)
    {
        return $query->where('status_bukti', 'SUDAH_ADA');
    }

    // ============================================================================
    // HELPER METHODS
    // ============================================================================

    /**
     * Check if bukti dukung has been uploaded
     */
    public function hasBukti(): bool
    {
        return $this->status_bukti === 'SUDAH_ADA' && !empty($this->bukti_dukung);
    }

    /**
     * Upload/update bukti dukung
     */
    public function updateBuktiDukung(string $link): void
    {
        $this->bukti_dukung = $link;
        $this->status_bukti = 'SUDAH_ADA';
        $this->save();

        // Trigger update realisasi bulanan
        $this->rencanaAksiBulanan->updateRealisasi();
    }

    /**
     * Get total durasi kerja for specific date and rencana aksi
     */
    public static function getTotalDurasiHarian(int $rencanaAksiId, string $tanggal, ?int $excludeId = null): int
    {
        $query = self::where('rencana_aksi_bulanan_id', $rencanaAksiId)
            ->whereDate('tanggal', $tanggal);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->sum('durasi_menit') ?? 0;
    }

    /**
     * Validate total durasi tidak melebihi 7 jam 30 menit (450 menit)
     */
    public static function validateDurasiHarian(int $rencanaAksiId, string $tanggal, int $durasiBaru, ?int $excludeId = null): bool
    {
        $totalDurasi = self::getTotalDurasiHarian($rencanaAksiId, $tanggal, $excludeId);
        return ($totalDurasi + $durasiBaru) <= 450;
    }

    /**
     * Calculate durasi_menit from jam_mulai and jam_selesai
     * Note: This is auto-calculated by database, but useful for validation
     */
    public function calculateDurasi(): int
    {
        $start = Carbon::parse($this->tanggal . ' ' . $this->jam_mulai);
        $end = Carbon::parse($this->tanggal . ' ' . $this->jam_selesai);

        return $start->diffInMinutes($end);
    }

    // ============================================================================
    // EVENTS
    // ============================================================================

    /**
     * ✅ CRITICAL FIX: Boot method dengan CONDITIONAL observer trigger
     *
     * Observer HANYA dipanggil jika field yang MEMPENGARUHI REALISASI diubah:
     * - jam_mulai / jam_selesai (mengubah durasi_menit)
     * - progres (mengubah persentase realisasi)
     * - durasi_menit (auto-generated dari jam)
     *
     * Observer TIDAK dipanggil untuk:
     * - bukti_dukung (link bukti tidak mengubah realisasi)
     * - keterangan (catatan tidak mengubah realisasi)
     * - status_bukti (status tidak mengubah realisasi)
     */
    protected static function boot()
    {
        parent::boot();

        // After create/update, update realisasi bulanan (only for KINERJA_HARIAN)
        static::saved(function ($progres) {
            // ✅ Check if rencanaAksiBulanan exists (null for TUGAS_ATASAN)
            if (!$progres->rencanaAksiBulanan) {
                return;
            }

            // ✅ CRITICAL: Only trigger if fields that AFFECT realisasi changed
            // isDirty() checks if field was changed in current save
            $affectsRealisasi = $progres->isDirty([
                'jam_mulai',
                'jam_selesai',
                'durasi_menit',
                'progres',
                'rencana_kegiatan_harian',
            ]);

            // ✅ Also trigger on CREATE (wasRecentlyCreated)
            if ($progres->wasRecentlyCreated || $affectsRealisasi) {
                $progres->rencanaAksiBulanan->updateRealisasi();
            }

            // ✅ If only bukti_dukung/keterangan/status_bukti changed → SKIP updateRealisasi()
        });

        // After delete, update realisasi bulanan (only for KINERJA_HARIAN)
        static::deleted(function ($progres) {
            // ✅ Check if rencanaAksiBulanan exists (null for TUGAS_ATASAN)
            if ($progres->rencanaAksiBulanan) {
                $progres->rencanaAksiBulanan->updateRealisasi();
            }
        });
    }
}
