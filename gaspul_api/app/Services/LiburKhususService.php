<?php

namespace App\Services;

use App\Models\KalenderLiburKhusus;
use App\Models\UnitKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Service untuk kalender libur khusus per jabatan.
 *
 * Stage 1: hanya menangani target_khusus = 'GURU'.
 * Stage 2+: PENYULUH, PENGHULU bisa ditambahkan tanpa mengubah interface.
 *
 * Identifikasi Guru:
 *   role = 'ASN' AND jabatan LIKE '%guru%' (case-insensitive)
 *   Kepala Madrasah (role ATASAN) TIDAK termasuk — mereka tetap wajib isi.
 *
 * Cache key: kalender_libur_khusus_{unitId}_{bulan}_{tahun}, TTL 10 menit.
 * Cache dihapus saat admin simpan/ubah/aktifkan entri kalender.
 */
class LiburKhususService
{
    private const CACHE_TTL = 600; // 10 menit

    // =========================================================================
    // Identifikasi jabatan
    // =========================================================================

    /**
     * Apakah user adalah Guru (ASN dengan jabatan mengandung kata "guru")?
     * Kepala Madrasah (role ATASAN) bukan guru dalam konteks ini.
     */
    public function isGuru(User $user): bool
    {
        return $user->role === 'ASN'
            && str_contains(strtolower($user->jabatan ?? ''), 'guru');
    }

    /**
     * Apakah user adalah Penyuluh Agama?
     * Stage 2 — belum diaktifkan, method tersedia untuk persiapan.
     */
    public function isPenyuluh(User $user): bool
    {
        return $user->role === 'ASN'
            && str_contains(strtolower($user->jabatan ?? ''), 'penyuluh');
    }

    /**
     * Apakah user adalah Penghulu?
     * Stage 2 — belum diaktifkan.
     */
    public function isPenghulu(User $user): bool
    {
        return $user->role === 'ASN'
            && str_contains(strtolower($user->jabatan ?? ''), 'penghulu');
    }

    // =========================================================================
    // Cek libur khusus
    // =========================================================================

    /**
     * Apakah user sedang dalam periode libur khusus pada tanggal tertentu?
     *
     * Algoritma:
     * 1. Tentukan target_khusus user (hanya GURU di Stage 1).
     * 2. Ambil entri kalender AKTIF yang berlaku untuk unit_kerja_id user
     *    (langsung atau via berlaku_ke_anak cascade dari unit induk).
     * 3. Cek apakah tanggal jatuh dalam rentang tanggal_mulai – tanggal_selesai.
     *
     * @param User   $user
     * @param Carbon $tanggal
     * @return bool
     */
    public function isLiburKhusus(User $user, Carbon $tanggal): bool
    {
        // Stage 1: hanya Guru
        if (! $this->isGuru($user)) {
            return false;
        }

        $targetKhusus = KalenderLiburKhusus::TARGET_GURU;

        return $this->adaLiburKhususUntuk($user->unit_kerja_id, $targetKhusus, $tanggal);
    }

    /**
     * Versi yang lebih eksplisit — untuk digunakan dari controller/view.
     * Mengembalikan entri KalenderLiburKhusus pertama yang cocok, atau null.
     */
    public function getLiburKhususAktif(User $user, Carbon $tanggal): ?KalenderLiburKhusus
    {
        if (! $this->isGuru($user)) {
            return null;
        }

        return $this->findLiburKhusus(
            $user->unit_kerja_id,
            KalenderLiburKhusus::TARGET_GURU,
            $tanggal
        );
    }

    // =========================================================================
    // Bulk helper — untuk controller monitoring (hindari N+1)
    // =========================================================================

    /**
     * Ambil semua tanggal libur khusus Guru dalam satu bulan untuk sekumpulan unit kerja.
     * Mengembalikan: ['YYYY-MM-DD' => true] untuk tanggal yang ada libur khusus.
     *
     * Digunakan oleh monitoring controller untuk batch-check tanpa N+1.
     *
     * @param int[] $unitIds  Daftar unit_kerja_id yang perlu dicek
     * @param int   $bulan
     * @param int   $tahun
     * @return array<string, bool>  Set tanggal libur khusus Guru
     */
    public function getTanggalLiburGuruBulanan(array $unitIds, int $bulan, int $tahun): array
    {
        if (empty($unitIds)) return [];

        // Ambil semua ID unit yang relevan (termasuk induk yang berlaku_ke_anak)
        $allRelevantUnitIds = $this->getRelevantUnitIds($unitIds);

        $startBulan = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
        $endBulan   = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();

        $entries = KalenderLiburKhusus::where('status', KalenderLiburKhusus::STATUS_AKTIF)
            ->where('target_khusus', KalenderLiburKhusus::TARGET_GURU)
            ->where('tanggal_mulai', '<=', $endBulan)
            ->where('tanggal_selesai', '>=', $startBulan)
            ->where(function ($q) use ($allRelevantUnitIds, $unitIds) {
                // Berlaku langsung ke unit ini
                $q->whereIn('unit_kerja_id', $unitIds)
                  // ATAU unit induk yang berlaku_ke_anak
                  ->orWhere(function ($q2) use ($allRelevantUnitIds, $unitIds) {
                      $indukIds = array_diff($allRelevantUnitIds, $unitIds);
                      if (! empty($indukIds)) {
                          $q2->whereIn('unit_kerja_id', $indukIds)
                             ->where('berlaku_ke_anak', true);
                      }
                  });
            })
            ->get(['tanggal_mulai', 'tanggal_selesai']);

        $tanggalSet = [];
        foreach ($entries as $entry) {
            $cur = $entry->tanggal_mulai->copy();
            while ($cur->lte($entry->tanggal_selesai)) {
                $key = $cur->format('Y-m-d');
                if ($cur->format('Y-m') === sprintf('%04d-%02d', $tahun, $bulan)) {
                    $tanggalSet[$key] = true;
                }
                $cur->addDay();
            }
        }

        return $tanggalSet;
    }

    /**
     * Cek apakah tanggal tertentu adalah libur khusus Guru.
     * Versi tunggal (per tanggal) — dengan cache per unit+bulan.
     */
    private function adaLiburKhususUntuk(int $unitId, string $targetKhusus, Carbon $tanggal): bool
    {
        return $this->findLiburKhusus($unitId, $targetKhusus, $tanggal) !== null;
    }

    private function findLiburKhusus(int $unitId, string $targetKhusus, Carbon $tanggal): ?KalenderLiburKhusus
    {
        $tanggalStr = $tanggal->format('Y-m-d');
        $bulan      = (int) $tanggal->format('m');
        $tahun      = (int) $tanggal->format('Y');
        $cacheKey   = "kalender_libur_khusus_{$unitId}_{$bulan}_{$tahun}";

        // Cache: daftar entri AKTIF per unit per bulan
        $entries = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($unitId, $bulan, $tahun, $targetKhusus) {
            $startBulan = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
            $endBulan   = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();

            // Ambil entri yang berlaku untuk unit ini atau induknya
            $relevantIds = $this->getRelevantUnitIds([$unitId]);

            return KalenderLiburKhusus::where('status', KalenderLiburKhusus::STATUS_AKTIF)
                ->where('target_khusus', $targetKhusus)
                ->where('tanggal_mulai', '<=', $endBulan)
                ->where('tanggal_selesai', '>=', $startBulan)
                ->where(function ($q) use ($unitId, $relevantIds) {
                    $q->where('unit_kerja_id', $unitId)
                      ->orWhere(function ($q2) use ($relevantIds, $unitId) {
                          $indukIds = array_diff($relevantIds, [$unitId]);
                          if (! empty($indukIds)) {
                              $q2->whereIn('unit_kerja_id', $indukIds)
                                 ->where('berlaku_ke_anak', true);
                          }
                      });
                })
                ->get(['id', 'tanggal_mulai', 'tanggal_selesai'])
                ->toArray();
        });

        foreach ($entries as $entry) {
            $mulai    = Carbon::parse($entry['tanggal_mulai']);
            $selesai  = Carbon::parse($entry['tanggal_selesai']);
            $cekTgl   = Carbon::parse($tanggalStr);
            if ($cekTgl->between($mulai, $selesai)) {
                return KalenderLiburKhusus::find($entry['id']);
            }
        }

        return null;
    }

    // =========================================================================
    // Target kerja — koreksi untuk laporan bulanan
    // =========================================================================

    /**
     * Hitung total menit kerja yang hilang akibat Libur Khusus dalam satu bulan.
     *
     * Hanya menghitung tanggal yang:
     * 1. Masuk dalam periode Libur Khusus AKTIF untuk user ini.
     * 2. Benar-benar hari kerja menurut pola kerja user (via getTargetMenitByDate).
     *
     * Digunakan oleh generateBulanan() untuk mengurangi target_menit dan target_jam.
     *
     * @param  User $user
     * @param  int  $bulan
     * @param  int  $tahun
     * @return int  Total menit yang harus dikurangi dari target bulanan
     */
    public function countMenitLiburKhususBulanan(User $user, int $bulan, int $tahun): int
    {
        if (! $this->isGuru($user)) {
            return 0;
        }

        $tanggalLiburSet = $this->getTanggalLiburGuruBulanan(
            [$user->unit_kerja_id],
            $bulan,
            $tahun
        );

        if (empty($tanggalLiburSet)) {
            return 0;
        }

        $totalMenit = 0;
        foreach (array_keys($tanggalLiburSet) as $dateStr) {
            $carbon      = Carbon::parse($dateStr);
            $menitHariIni = \App\Services\WorkingTimeService::getTargetMenitByDate($carbon, $user);
            $totalMenit  += $menitHariIni;
        }

        return $totalMenit;
    }

    // =========================================================================
    // Cache management
    // =========================================================================

    /**
     * Hapus cache untuk unit kerja dan bulan tertentu.
     * Dipanggil dari KalenderLiburKhususController saat simpan/update/hapus.
     */
    public function clearCacheForUnit(int $unitId, Carbon $tanggalMulai, Carbon $tanggalSelesai): void
    {
        // Hapus cache semua bulan dalam rentang tanggal
        $cur = $tanggalMulai->copy()->startOfMonth();
        while ($cur->lte($tanggalSelesai)) {
            $bulan = (int) $cur->format('m');
            $tahun = (int) $cur->format('Y');
            Cache::forget("kalender_libur_khusus_{$unitId}_{$bulan}_{$tahun}");
            $cur->addMonth();
        }
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    /**
     * Ambil semua unit ID yang relevan: unit itu sendiri + semua induknya (untuk cascade).
     * Induk yang berlaku_ke_anak=true bisa mempengaruhi unit anak.
     *
     * @param int[] $unitIds
     * @return int[]
     */
    private function getRelevantUnitIds(array $unitIds): array
    {
        // Ambil parent_id dari semua unit yang ditanyakan (satu level ke atas)
        $units = UnitKerja::whereIn('id', $unitIds)
            ->whereNotNull('parent_id')
            ->pluck('parent_id')
            ->toArray();

        $allIds = array_unique(array_merge($unitIds, $units));

        // Satu level lagi ke atas (untuk madrasah → kankemenag → kanwil)
        if (! empty($units)) {
            $grandparents = UnitKerja::whereIn('id', $units)
                ->whereNotNull('parent_id')
                ->pluck('parent_id')
                ->toArray();
            $allIds = array_unique(array_merge($allIds, $grandparents));
        }

        return $allIds;
    }
}
