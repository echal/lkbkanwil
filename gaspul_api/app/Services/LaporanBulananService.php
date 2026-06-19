<?php

namespace App\Services;

use App\Models\LaporanBulananKinerja;
use App\Models\ProgresHarian;
use App\Models\CutiAsn;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use App\Helpers\HolidayHelper;
use App\Services\WorkingTimeService;
use App\Services\LiburKhususService;

/**
 * Service untuk Laporan Bulanan Kinerja ASN.
 *
 * Tanggung jawab:
 * 1. getRekapHarian()    — rekap per hari (1 query, no N+1), dipakai Controller & PDF
 * 2. getSummary()        — ringkasan bulanan dari rekap harian
 * 3. generateBulanan()   — upsert record laporan_bulanan_kinerja
 * 4. kirimKeAtasan()     — ubah status DRAFT/DITOLAK → DIKIRIM
 * 5. approve()           — Atasan menyetujui laporan
 * 6. tolak()             — Atasan menolak laporan + catatan
 * 7. getRiwayatUser()    — semua laporan milik user (untuk tab Riwayat)
 * 8. getApprovalList()   — laporan DIKIRIM dari bawahan langsung (untuk Atasan)
 */
class LaporanBulananService
{
    private WorkingTimeService $workingTime;
    private LiburKhususService $liburKhusus;

    public function __construct()
    {
        $this->workingTime = new WorkingTimeService();
        $this->liburKhusus = new LiburKhususService();
    }
    // ── 1. Rekap harian (presentation layer) ─────────────────────────────────

    /**
     * Generate rekap kinerja harian untuk 1 bulan penuh.
     * 1 query untuk seluruh bulan, grouping di PHP — tidak ada N+1.
     *
     * @return array<int, array{tanggal:string, hari:string, total_jam:string,
     *     total_menit:int, kh:int, tla:int, status:string, status_code:string}>
     */
    public function getRekapHarian(int $userId, int $bulan, int $tahun, $user = null): array
    {
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate   = Carbon::create($tahun, $bulan, 1)->endOfMonth();

        $semuaProgres = ProgresHarian::where('user_id', $userId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get(['id', 'user_id', 'tanggal', 'tipe_progres', 'durasi_menit', 'status_bukti']);

        $grupPerHari = $semuaProgres->groupBy(
            fn(ProgresHarian $row) => $row->tanggal->format('Y-m-d')
        );

        // 1 query cuti seluruh bulan — build set tanggal cuti untuk lookup O(1)
        $cutiList   = CutiAsn::getForBulan($userId, $bulan, $tahun);
        $cutiPerHari = [];  // ['Y-m-d' => jenis_cuti]
        foreach ($cutiList as $cuti) {
            $cur = $cuti->tanggal_mulai->copy();
            while ($cur->lte($cuti->tanggal_selesai)) {
                $cutiPerHari[$cur->format('Y-m-d')] = $cuti->jenis;
                $cur->addDay();
            }
        }

        // Tentukan pola kerja sekali di luar loop
        $pola = $user !== null
            ? \App\Helpers\HolidayHelper::getHariKerjaUser($user)
            : 'SENIN_JUMAT';

        // Pre-load tanggal libur khusus Guru satu kali untuk seluruh bulan (menghindari N+1)
        $tanggalLiburKhususSet = [];
        if ($user !== null && $this->liburKhusus->isGuru($user)) {
            $tanggalLiburKhususSet = $this->liburKhusus->getTanggalLiburGuruBulanan(
                [$user->unit_kerja_id],
                $bulan,
                $tahun
            );
        }

        $rekap     = [];
        $totalHari = $startDate->daysInMonth;

        for ($day = 1; $day <= $totalHari; $day++) {
            $tanggal     = Carbon::create($tahun, $bulan, $day);
            $dateKey     = $tanggal->format('Y-m-d');
            $hariSingkat = $tanggal->locale('id')->isoFormat('ddd');

            // Cuti / Dinas Luar — prioritas di atas libur & kosong
            if (isset($cutiPerHari[$dateKey])) {
                $rekap[] = $this->rowCuti($tanggal->format('d'), $hariSingkat, $cutiPerHari[$dateKey]);
                continue;
            }

            // Libur khusus (misal: libur semester Guru) — sebelum cek hari kerja normal
            // Hanya berlaku untuk laporan yang belum DISETUJUI (dicek di generateBulanan)
            if (isset($tanggalLiburKhususSet[$dateKey]) && ! $grupPerHari->has($dateKey)) {
                $rekap[] = $this->rowLiburKhusus($tanggal->format('d'), $hariSingkat);
                continue;
            }

            // Target menit untuk hari ini — 0 berarti bukan hari kerja / libur
            $targetMenit = WorkingTimeService::getTargetMenitByDate($tanggal, $user);
            $isLibur     = $targetMenit === 0;

            if ($isLibur && ! $grupPerHari->has($dateKey)) {
                $rekap[] = $this->rowLibur($tanggal->format('d'), $hariSingkat);
                continue;
            }

            if (! $grupPerHari->has($dateKey)) {
                $rekap[] = $this->rowKosong($tanggal->format('d'), $hariSingkat);
                continue;
            }

            $hariProgres = $grupPerHari->get($dateKey);
            $totalMenit  = (int) $hariProgres->sum('durasi_menit');
            $countKH     = $hariProgres->where('tipe_progres', 'KINERJA_HARIAN')->count();
            $countTLA    = $hariProgres->where('tipe_progres', 'TUGAS_ATASAN')->count();
            $adaBukti    = $hariProgres->where('status_bukti', 'SUDAH_ADA')->isNotEmpty();

            [$statusCode, $statusLabel] = $this->hitungStatus($totalMenit, $tanggal, $user, $adaBukti);

            $rekap[] = [
                'tanggal'     => $tanggal->format('d'),
                'hari'        => $hariSingkat,
                'total_jam'   => $this->formatDurasi($totalMenit),
                'total_menit' => $totalMenit,
                'kh'          => $countKH,
                'tla'         => $countTLA,
                'status'      => $statusLabel,
                'status_code' => $statusCode,
            ];
        }

        return $rekap;
    }

    /**
     * Ringkasan bulanan dari array rekap harian.
     */
    public function getSummary(array $rekapHarian, int $tahun, int $bulan): array
    {
        $collection = collect($rekapHarian);
        // CUTI dan LIBUR_KHUSUS dikecualikan dari hari_kerja — tidak menaikkan/menurunkan capaian
        $hariKerja  = $collection->whereNotIn('status_code', ['LIBUR', 'LIBUR_KHUSUS', 'EMPTY', 'CUTI'])->count();
        $totalMenit = $collection->sum('total_menit');

        return [
            'total_hari'        => count($rekapHarian),
            'hari_kerja'        => $hariKerja,
            'hari_kosong'       => $collection->where('status_code', 'EMPTY')->count(),
            'hari_libur'        => $collection->where('status_code', 'LIBUR')->count(),
            'hari_libur_khusus' => $collection->where('status_code', 'LIBUR_KHUSUS')->count(),
            'hari_cuti'         => $collection->where('status_code', 'CUTI')->count(),
            'hari_green'        => $collection->where('status_code', 'GREEN')->count(),
            'hari_yellow'       => $collection->where('status_code', 'YELLOW')->count(),
            'hari_red'          => $collection->where('status_code', 'RED')->count(),
            'total_menit' => $totalMenit,
            'total_jam'   => $this->formatDurasi($totalMenit),
            'total_kh'    => $collection->sum('kh'),
            'total_tla'   => $collection->sum('tla'),
            'avg_jam'     => $hariKerja > 0
                              ? number_format(round($totalMenit / $hariKerja / 60, 1), 1)
                              : '0.0',
        ];
    }

    // ── 2. Business Logic — Laporan Bulanan Kinerja ───────────────────────────

    /**
     * Buat / perbarui record laporan_bulanan_kinerja dari progres harian.
     * Dipanggil sebelum kirimKeAtasan() agar data summary selalu up-to-date.
     */
    public function generateBulanan(int $userId, int $bulan, int $tahun): LaporanBulananKinerja
    {
        // Guard: laporan yang sudah DISETUJUI tidak boleh di-recalculate
        $existing = LaporanBulananKinerja::where('user_id', $userId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        if ($existing && $existing->status === LaporanBulananKinerja::STATUS_DISETUJUI) {
            return $existing;
        }

        // Guard: laporan yang sedang direview atasan tidak boleh di-recalculate
        if ($existing && $existing->status === LaporanBulananKinerja::STATUS_DIKIRIM) {
            return $existing;
        }

        $user        = \App\Models\User::with('unitKerja')->find($userId);
        $rekapHarian = $this->getRekapHarian($userId, $bulan, $tahun, $user);
        $summary     = $this->getSummary($rekapHarian, $tahun, $bulan);

        $totalJam = (int) floor($summary['total_menit'] / 60);

        // Target dasar dari konfigurasi pola kerja
        $targetMenitSnapshot = WorkingTimeService::getTargetMenitBulanan($bulan, $tahun, $user);

        // Kurangi target jika Guru sedang dalam periode Libur Khusus aktif
        $menitLiburKhusus = $this->liburKhusus->countMenitLiburKhususBulanan($user, $bulan, $tahun);
        if ($menitLiburKhusus > 0) {
            $targetMenitSnapshot = max(0, $targetMenitSnapshot - $menitLiburKhusus);
        }

        $targetJam = round($targetMenitSnapshot / 60, 2);
        $capaian   = $targetJam > 0
            ? round(($summary['total_menit'] / 60) / $targetJam * 100, 2)
            : 0;

        // Snapshot — dibekukan saat generate untuk audit trail
        $hariKerjaSnapshot   = $summary['hari_kerja'];
        $polaKerjaSnapshot   = HolidayHelper::getHariKerjaUser($user);

        $laporan = LaporanBulananKinerja::firstOrNew(
            ['user_id' => $userId, 'bulan' => $bulan, 'tahun' => $tahun]
        );

        $laporan->fill([
            'total_hari'                    => $hariKerjaSnapshot,
            'total_jam'                     => $totalJam,
            'target_jam'                    => $targetJam,
            'capaian_persen'                => min($capaian, 999.99),
            'target_menit_bulanan_snapshot' => $targetMenitSnapshot,
            'target_jam_bulanan_snapshot'   => round($targetMenitSnapshot / 60, 2),
            'hari_kerja_snapshot'           => $hariKerjaSnapshot,
            'pola_kerja_snapshot'           => $polaKerjaSnapshot,
        ]);

        // Pastikan status selalu terisi — set DRAFT hanya jika record baru (belum ada status)
        if (empty($laporan->status)) {
            $laporan->status = LaporanBulananKinerja::STATUS_DRAFT;
        }

        $laporan->save();
        return $laporan;
    }

    /**
     * Kirim laporan ke atasan.
     * Jika record belum ada, generate dulu dari progres harian.
     * Status: DRAFT atau DITOLAK → DIKIRIM.
     */
    public function kirimKeAtasan(int $userId, int $bulan, int $tahun): LaporanBulananKinerja
    {
        // Audit: catat jika ASN kirim laporan bulan berjalan di awal bulan (hari 1-5)
        $wita = now()->setTimezone('Asia/Makassar');
        if ($wita->day <= 5 && $bulan === (int) $wita->month && $tahun === (int) $wita->year) {
            \Illuminate\Support\Facades\Log::warning('Potensi salah kirim laporan bulanan', [
                'user_id'     => $userId,
                'bulan'       => $bulan,
                'tahun'       => $tahun,
                'tanggal_kirim' => $wita->format('Y-m-d'),
                'hari_ke'     => $wita->day,
            ]);
        }

        // Pastikan summary terbarukan
        $laporan = $this->generateBulanan($userId, $bulan, $tahun);

        if (! in_array($laporan->status, [
            LaporanBulananKinerja::STATUS_DRAFT,
            LaporanBulananKinerja::STATUS_DITOLAK,
        ])) {
            throw ValidationException::withMessages([
                'laporan' => 'Laporan tidak bisa dikirim. Status saat ini: ' . $laporan->status_label,
            ]);
        }

        $laporan->update([
            'status'      => LaporanBulananKinerja::STATUS_DIKIRIM,
            'catatan'     => null,    // hapus catatan tolak sebelumnya
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return $laporan->fresh();
    }

    /**
     * Atasan menyetujui laporan bulanan bawahan.
     * Validasi: laporan harus milik bawahan langsung (user.atasan_id == $atasanId).
     */
    public function approve(int $laporanId, int $atasanId, ?string $catatan = null): LaporanBulananKinerja
    {
        $laporan = LaporanBulananKinerja::whereHas(
                'user', fn($q) => $q->where('atasan_id', $atasanId)
            )
            ->where('status', LaporanBulananKinerja::STATUS_DIKIRIM)
            ->findOrFail($laporanId);

        $laporan->update([
            'status'      => LaporanBulananKinerja::STATUS_DISETUJUI,
            'approved_by' => $atasanId,
            'approved_at' => now(),
            'catatan'     => $catatan,
        ]);

        return $laporan->fresh(['user', 'approver']);
    }

    /**
     * Atasan menolak laporan bulanan bawahan.
     * Catatan penolakan wajib diisi.
     */
    public function tolak(int $laporanId, int $atasanId, string $catatan): LaporanBulananKinerja
    {
        $laporan = LaporanBulananKinerja::whereHas(
                'user', fn($q) => $q->where('atasan_id', $atasanId)
            )
            ->where('status', LaporanBulananKinerja::STATUS_DIKIRIM)
            ->findOrFail($laporanId);

        $laporan->update([
            'status'      => LaporanBulananKinerja::STATUS_DITOLAK,
            'approved_by' => $atasanId,
            'approved_at' => now(),
            'catatan'     => $catatan,
        ]);

        return $laporan->fresh(['user', 'approver']);
    }

    // ── 3. Query Methods ──────────────────────────────────────────────────────

    /**
     * Semua laporan milik user (untuk tab Riwayat di halaman ASN).
     * Eager load approver — no N+1.
     */
    public function getRiwayatUser(int $userId): Collection
    {
        return LaporanBulananKinerja::with(['approver:id,name'])
            ->where('user_id', $userId)
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->get();
    }

    /**
     * Semua laporan dari bawahan langsung (untuk halaman Atasan).
     * Filter status dan bulan opsional.
     * Eager load user + unitKerja — no N+1.
     */
    public function getApprovalList(int $atasanId, ?string $filterStatus = null, ?string $filterBulan = null): Collection
    {
        $query = LaporanBulananKinerja::with([
                'user:id,name,nip,jabatan,unit_kerja_id,atasan_id',
                'user.unitKerja:id,nama_unit',
            ])
            ->whereHas('user', fn($q) => $q->where('atasan_id', $atasanId));

        if ($filterStatus && $filterStatus !== 'semua') {
            $query->where('status', strtoupper($filterStatus));
        }

        // Filter bulan format "YYYY-MM" misal "2026-02"
        if ($filterBulan && $filterBulan !== 'semua') {
            [$thn, $bln] = explode('-', $filterBulan);
            $query->where('bulan', (int) $bln)->where('tahun', (int) $thn);
        }

        return $query->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('updated_at')->get();
    }

    /**
     * Hitung laporan DIKIRIM (pending) dari bawahan langsung — untuk badge counter.
     */
    public function getPendingCount(int $atasanId): int
    {
        return LaporanBulananKinerja::whereHas('user', fn($q) => $q->where('atasan_id', $atasanId))
            ->where('status', LaporanBulananKinerja::STATUS_DIKIRIM)
            ->count();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function rowLibur(string $tgl, string $hari): array
    {
        return ['tanggal' => $tgl, 'hari' => $hari, 'total_jam' => '-',
                'total_menit' => 0, 'kh' => 0, 'tla' => 0,
                'status' => 'Libur', 'status_code' => 'LIBUR'];
    }

    private function rowLiburKhusus(string $tgl, string $hari): array
    {
        return ['tanggal' => $tgl, 'hari' => $hari, 'total_jam' => '-',
                'total_menit' => 0, 'kh' => 0, 'tla' => 0,
                'status' => 'Libur Khusus', 'status_code' => 'LIBUR_KHUSUS'];
    }

    private function rowKosong(string $tgl, string $hari): array
    {
        return ['tanggal' => $tgl, 'hari' => $hari, 'total_jam' => '-',
                'total_menit' => 0, 'kh' => 0, 'tla' => 0,
                'status' => 'Tidak Ada', 'status_code' => 'EMPTY'];
    }

    private function rowCuti(string $tgl, string $hari, string $jenis): array
    {
        return ['tanggal' => $tgl, 'hari' => $hari, 'total_jam' => '-',
                'total_menit' => 0, 'kh' => 0, 'tla' => 0,
                'status' => 'Cuti', 'status_code' => 'CUTI', 'jenis_cuti' => $jenis];
    }

    /**
     * Hitung status harian berdasarkan target menit dinamis per pola kerja.
     *
     * @return array{0: string, 1: string}  [status_code, status_label]
     */
    private function hitungStatus(int $totalMenit, Carbon $date, $user, bool $adaBukti): array
    {
        $targetMenit = WorkingTimeService::getTargetMenitByDate($date, $user);

        if ($targetMenit === 0) return ['LIBUR', 'Hari Libur'];
        if (! $adaBukti)        return ['RED',   'Belum Bukti'];
        if ($totalMenit >= $targetMenit) return ['GREEN',  'Lengkap'];
        if ($totalMenit > 0)    return ['YELLOW', 'Kurang'];
        return ['RED', 'Belum Input'];
    }

    private function formatDurasi(int $menit): string
    {
        if ($menit <= 0) return '-';
        $jam       = intdiv($menit, 60);
        $sisaMenit = $menit % 60;
        return $sisaMenit > 0 ? "{$jam}j {$sisaMenit}m" : "{$jam}j";
    }
}
