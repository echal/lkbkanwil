<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Helpers\HolidayHelper;
use App\Services\LiburKhususService;
use Carbon\Carbon;

/**
 * Dashboard Kepatuhan ASN Kankemenag Kabupaten Mamasa
 *
 * Cakupan: 28 unit kerja di bawah Kankemenag Mamasa (ID 16 + child/grandchild)
 * Akses  : token publik via ?token=MAMASA_MONITOR_TOKEN (tidak perlu login)
 * Data   : users, unit_kerja, skp_tahunan, progres_harian, cuti_asn
 */
class MonitoringMamasaController extends Controller
{
    private const CACHE_KEY = 'monitoring_mamasa';
    private const CACHE_TTL = 300; // 5 menit

    // =========================================================================
    // Unit kerja lingkup Kankemenag Kabupaten Mamasa
    // ID 16 = Kankemenag Mamasa (level 2)
    // ID 141–168 = child/grandchild (level 3–4)
    // Diaudit: 2026-06-11, total 329 ASN aktif
    // =========================================================================
    private const UNIT_IDS = [
        16,
        141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151,
        152, 153, 154, 155, 156,
        158, 159, 160, 161, 162, 163, 164, 165,
        166, 167, 168,
    ];

    // =========================================================================
    // TOKEN GUARD
    // =========================================================================

    private function guardToken(Request $request): void
    {
        $token = $request->query('token');
        if (empty($token) || $token !== config('app.mamasa_monitor_token')) {
            abort(403, 'Akses ditolak.');
        }
    }

    // =========================================================================
    // ROUTES
    // =========================================================================

    public function index(Request $request)
    {
        $this->guardToken($request);

        $tahun = (int) $request->query('tahun', now()->year);
        $bulan = (int) $request->query('bulan', now()->month);

        // Cache key per bulan+tahun — data harian selalu fresh via real-time inject
        $cacheKey = self::CACHE_KEY . "_{$tahun}_{$bulan}";

        $cached = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tahun, $bulan) {
            return $this->buildCachedData($tahun, $bulan);
        });

        // Data real-time — TIDAK dicache, selalu fresh
        $wita          = now()->setTimezone('Asia/Makassar');
        $tanggalStr    = $wita->toDateString();
        $realtime      = $this->buildRealtimeData($tanggalStr, $tahun, $bulan);

        // Merge: realtime override ke dalam cached
        $data = array_merge($cached, $realtime);

        $isHariKerja = HolidayHelper::isWorkingDay($wita);
        $jamSekarang = (int) $wita->format('H');
        $isJamKerja  = $jamSekarang >= 7 && $jamSekarang <= 17;

        if (!$isHariKerja) {
            $statusWaktu  = 'libur';
            $messageWaktu = 'Hari libur — aktivitas kerja tidak berlangsung.';
        } elseif (!$isJamKerja) {
            $statusWaktu  = 'belum_jam_kerja';
            $messageWaktu = 'Di luar jam kerja — data akan terisi saat jam kerja.';
        } else {
            $statusWaktu  = 'jam_kerja';
            $messageWaktu = null;
        }

        return view('monitoring.mamasa', [
            'data'         => $data,
            'tahun'        => $tahun,
            'bulan'        => $bulan,
            'token'        => $request->query('token'),
            'lastUpdate'   => $wita->format('d M Y, H:i:s') . ' WITA',
            'statusWaktu'  => $statusWaktu,
            'messageWaktu' => $messageWaktu,
            'jamSekarang'  => $wita->format('H:i') . ' WITA',
            'tanggalStr'   => $tanggalStr,
        ]);
    }

    public function clearCache(Request $request)
    {
        $this->guardToken($request);

        foreach (range(now()->year - 1, now()->year + 1) as $y) {
            foreach (range(1, 12) as $m) {
                Cache::forget(self::CACHE_KEY . "_{$y}_{$m}");
            }
        }

        return redirect()->route('monitoring.mamasa', ['token' => $request->query('token')])
            ->with('info', 'Cache monitoring Mamasa berhasil di-refresh.');
    }

    /**
     * AJAX — Detail satu ASN untuk drill-down modal.
     * Endpoint: GET /monitoring-tv/mamasa/asn-detail/{id}?token=...
     */
    public function asnDetail(Request $request, int $id)
    {
        $this->guardToken($request);

        $tahun = (int) $request->query('tahun', now()->year);
        $bulan = (int) $request->query('bulan', now()->month);

        // Pastikan ASN ini memang ada di lingkup Mamasa
        $asn = DB::table('users as u')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->leftJoin('users as atasan', 'u.atasan_id', '=', 'atasan.id')
            ->leftJoin('skp_tahunan as s', function ($j) use ($tahun) {
                $j->on('s.user_id', '=', 'u.id')->where('s.tahun', $tahun);
            })
            ->select(
                'u.id', 'u.name', 'u.nip', 'u.jabatan', 'u.unit_kerja_id', 'u.hari_kerja',
                'uk.nama_unit',
                's.status as skp_status',
                'atasan.name as nama_atasan',
                'atasan.jabatan as jabatan_atasan'
            )
            ->where('u.id', $id)
            ->whereIn('u.unit_kerja_id', self::UNIT_IDS)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->first();

        if (!$asn) {
            return response()->json(['error' => 'ASN tidak ditemukan.'], 404);
        }

        // Riwayat pengisian bulan ini
        $startBulan = sprintf('%04d-%02d-01', $tahun, $bulan);
        $endBulan   = Carbon::create($tahun, $bulan, 1)->endOfMonth()->format('Y-m-d');

        $riwayat = DB::table('progres_harian')
            ->where('user_id', $id)
            ->where('tanggal', '>=', $startBulan)
            ->where('tanggal', '<=', $endBulan)
            ->whereIn('tipe_progres', ['KINERJA_HARIAN', 'TUGAS_ATASAN'])
            ->select('tanggal', 'tipe_progres', 'jam_mulai', 'jam_selesai', 'durasi_menit')
            ->orderByDesc('tanggal')
            ->orderBy('tipe_progres')
            ->get();

        // Hitung hari kerja bulan ini yang sudah berlalu
        $today          = now()->setTimezone('Asia/Makassar')->startOfDay();
        $hariKerjaWajib = $this->hitungHariKerjaWajib($tahun, $bulan, $asn);
        $hariIsi        = $riwayat->pluck('tanggal')->unique()->count();
        $pct            = $hariKerjaWajib > 0 ? round($hariIsi / $hariKerjaWajib * 100, 1) : 100.0;

        $lastKh  = DB::table('progres_harian')->where('user_id', $id)->where('tipe_progres', 'KINERJA_HARIAN')->max('tanggal');
        $lastTla = DB::table('progres_harian')->where('user_id', $id)->where('tipe_progres', 'TUGAS_ATASAN')->max('tanggal');

        // Rangkum per tanggal
        $perTanggal = $riwayat->groupBy('tanggal')->map(function ($rows, $tgl) {
            return [
                'tanggal'    => $tgl,
                'ada_kh'     => $rows->where('tipe_progres', 'KINERJA_HARIAN')->isNotEmpty(),
                'ada_tla'    => $rows->where('tipe_progres', 'TUGAS_ATASAN')->isNotEmpty(),
                'total_menit'=> $rows->sum('durasi_menit'),
            ];
        })->values();

        return response()->json([
            'asn' => [
                'id'             => $asn->id,
                'name'           => $asn->name,
                'nip'            => $asn->nip ?? '-',
                'jabatan'        => $asn->jabatan ?? '-',
                'nama_unit'      => $asn->nama_unit,
                'nama_atasan'    => $asn->nama_atasan ?? '-',
                'jabatan_atasan' => $asn->jabatan_atasan ?? '-',
                'skp_status'     => $asn->skp_status,
            ],
            'kepatuhan' => [
                'hari_wajib' => $hariKerjaWajib,
                'hari_isi'   => $hariIsi,
                'persen'     => $pct,
                'last_kh'    => $lastKh,
                'last_tla'   => $lastTla,
            ],
            'riwayat'   => $perTanggal,
        ]);
    }

    // =========================================================================
    // BUILD DATA — CACHED (SKP + unit ranking)
    // =========================================================================

    private function buildCachedData(int $tahun, int $bulan): array
    {
        $skpDist = $this->buildSkpDistribusi($tahun);
        $perUnit = $this->buildPerUnitSkp($tahun);

        return [
            'skp'      => $skpDist,
            'per_unit' => $perUnit,
            'tahun'    => $tahun,
            'bulan'    => $bulan,
        ];
    }

    // =========================================================================
    // BUILD DATA — REAL-TIME (kepatuhan harian + pembinaan)
    // =========================================================================

    private function buildRealtimeData(string $tanggalStr, int $tahun, int $bulan): array
    {
        // ── Satu mega query: ASN + SKP + last KH/TLA + hari isi bulan ini ────
        $startBulan = sprintf('%04d-%02d-01', $tahun, $bulan);
        $endBulan   = Carbon::create($tahun, $bulan, 1)->endOfMonth()->format('Y-m-d');

        $asnRows = DB::table('users as u')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->leftJoin('skp_tahunan as s', function ($j) use ($tahun) {
                $j->on('s.user_id', '=', 'u.id')->where('s.tahun', $tahun);
            })
            ->leftJoin('users as atasan', 'u.atasan_id', '=', 'atasan.id')
            ->leftJoin(
                DB::raw("(
                    SELECT ph.user_id,
                           COUNT(DISTINCT ph.tanggal) AS hari_isi,
                           MAX(CASE WHEN ph.tipe_progres='KINERJA_HARIAN' THEN ph.tanggal END) AS last_kh,
                           MAX(CASE WHEN ph.tipe_progres='TUGAS_ATASAN'   THEN ph.tanggal END) AS last_tla
                    FROM progres_harian ph
                    WHERE ph.tanggal BETWEEN '{$startBulan}' AND '{$endBulan}'
                      AND ph.tipe_progres IN ('KINERJA_HARIAN','TUGAS_ATASAN')
                    GROUP BY ph.user_id
                ) AS ph_agg"),
                'ph_agg.user_id',
                '=',
                'u.id'
            )
            ->select(
                'u.id', 'u.name', 'u.nip', 'u.jabatan', 'u.role',
                'u.unit_kerja_id', 'u.hari_kerja', 'u.atasan_id',
                'uk.nama_unit',
                's.status AS skp_status',
                'atasan.name AS nama_atasan',
                DB::raw('COALESCE(ph_agg.hari_isi, 0) AS hari_isi'),
                'ph_agg.last_kh',
                'ph_agg.last_tla'
            )
            ->whereIn('u.unit_kerja_id', self::UNIT_IDS)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->get();

        // ── Pre-load cuti aktif hari ini — batch, no N+1 ─────────────────────
        $userIds        = $asnRows->pluck('id')->toArray();
        $sedangCutiIds  = DB::table('cuti_asn')
            ->where('tanggal_mulai', '<=', $tanggalStr)
            ->where('tanggal_selesai', '>=', $tanggalStr)
            ->whereIn('user_id', $userIds)
            ->pluck('user_id')
            ->flip(); // O(1) lookup

        // ── Pre-load libur khusus guru bulan ini — batch ─────────────────────
        $liburService       = new LiburKhususService();
        $tanggalLiburGuru   = $liburService->getTanggalLiburGuruBulanan(
            self::UNIT_IDS,
            $bulan,
            $tahun
        );
        $isLiburKhususHariIni = isset($tanggalLiburGuru[$tanggalStr]);

        // ── Hari kerja bulan ini yang sudah berlalu ───────────────────────────
        $today             = now()->setTimezone('Asia/Makassar')->startOfDay();
        $hariKerjaBerlalu  = $this->getHariKerjaBerlalu($tahun, $bulan, $today);

        // ── Sudah isi hari ini ────────────────────────────────────────────────
        $sudahIsiHariIni = DB::table('progres_harian as ph')
            ->join('users as u', 'ph.user_id', '=', 'u.id')
            ->where('ph.tanggal', $tanggalStr)
            ->whereIn('ph.tipe_progres', ['KINERJA_HARIAN', 'TUGAS_ATASAN'])
            ->whereIn('u.unit_kerja_id', self::UNIT_IDS)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->distinct()
            ->pluck('ph.user_id')
            ->flip();

        // ── Batch pre-load semua tanggal isi bulan ini (untuk hitungMaxGap) ──
        // 1 query untuk semua 329 ASN, hasilkan map: [user_id => ['YYYY-MM-DD' => true]]
        // Menggantikan 329 query terpisah per ASN di dalam hitungMaxGap().
        $tglIsiPerUser = DB::table('progres_harian')
            ->whereIn('user_id', $userIds)
            ->where('tanggal', '>=', $startBulan)
            ->where('tanggal', '<=', $endBulan)
            ->whereIn('tipe_progres', ['KINERJA_HARIAN', 'TUGAS_ATASAN'])
            ->select('user_id', 'tanggal')
            ->distinct()
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('tanggal')->flip()->toArray())
            ->toArray();

        // ── Build per-ASN: hitung hari wajib, persen, skor, prioritas ────────
        $pembinaan   = [];
        $totalWajib  = 0;
        $totalSudah  = 0;
        $perUnitHarian = [];

        foreach ($asnRows as $asn) {
            // Hitung hari kerja wajib bulan ini (filter cuti + libur khusus)
            $hkWajib = $this->hitungHariKerjaWajibFromArray(
                $hariKerjaBerlalu, $asn, $tanggalLiburGuru
            );

            // Cek apakah wajib isi hari ini
            $isGuru              = $this->isGuruStdClass($asn);
            $isLiburKhususGuru   = $isLiburKhususHariIni && $isGuru;
            $isCuti              = isset($sedangCutiIds[$asn->id]);
            $isHariKerjaUser     = $this->isWorkingDayForAsn(Carbon::parse($tanggalStr), $asn);
            $wajibIsiHariIni     = $isHariKerjaUser && !$isLiburKhususGuru && !$isCuti;

            // Agregat KPI harian
            $totalWajib += $hkWajib;
            if (isset($sudahIsiHariIni[$asn->id])) {
                $totalSudah++;
            }

            // Persen kepatuhan bulan ini
            $hariIsi = (int) $asn->hari_isi;
            $pct     = $hkWajib > 0 ? round($hariIsi / $hkWajib * 100, 1) : 100.0;

            // Gap berturut-turut — baca dari batch map, zero extra query
            $maxGap = $this->hitungMaxGapFromMap(
                $tglIsiPerUser[$asn->id] ?? [],
                $hariKerjaBerlalu
            );

            // Skor pembinaan
            $skor   = 0;
            $alasan = [];

            if ($hariIsi === 0 && count($hariKerjaBerlalu) > 0) {
                $skor += 40;
                $alasan[] = 'Belum Pernah Isi Bulan Ini';
            }
            if ($asn->skp_status === null) {
                $skor += 30;
                $alasan[] = 'Belum Buat SKP';
            }
            if ($asn->skp_status === 'DITOLAK') {
                $skor += 25;
                $alasan[] = 'SKP Ditolak';
            }
            if ($maxGap >= 5) {
                $skor += 20;
                $alasan[] = "Tidak Isi {$maxGap} Hari Kerja Berturut-turut";
            }
            if ($hariIsi > 0 && $pct < 50) {
                $skor += 15;
                $alasan[] = 'Kepatuhan < 50%';
            }
            if ($asn->skp_status === 'DRAFT') {
                $skor += 10;
                $alasan[] = 'SKP Masih Draft';
            }
            if ($asn->skp_status === 'DIAJUKAN') {
                $skor += 5;
                $alasan[] = 'SKP Menunggu Persetujuan';
            }
            if ($asn->skp_status === 'REVISI_DIAJUKAN') {
                $skor += 5;
                $alasan[] = 'Revisi SKP Menunggu Persetujuan';
            }
            if ($hariIsi > 0 && $pct >= 50 && $pct < 80) {
                $skor += 5;
                $alasan[] = 'Kepatuhan 50%–80%';
            }

            $prioritas = match(true) {
                $skor >= 40 => 'TINGGI',
                $skor >= 10 => 'SEDANG',
                $skor >= 1  => 'RENDAH',
                default     => 'BAIK',
            };

            // Tanggal terakhir isi (mana yang lebih baru antara KH dan TLA)
            $lastKh  = $asn->last_kh;
            $lastTla = $asn->last_tla;
            $lastIsi = match(true) {
                $lastKh && $lastTla => max($lastKh, $lastTla),
                $lastKh !== null    => $lastKh,
                $lastTla !== null   => $lastTla,
                default             => null,
            };

            $pembinaan[] = [
                'id'           => $asn->id,
                'name'         => $asn->name,
                'nip'          => $asn->nip ?? '-',
                'jabatan'      => $asn->jabatan ?? '-',
                'unit_kerja_id'=> $asn->unit_kerja_id,
                'nama_unit'    => $asn->nama_unit,
                'nama_atasan'  => $asn->nama_atasan ?? '-',
                'hari_isi'     => $hariIsi,
                'hari_wajib'   => $hkWajib,
                'persen'       => $pct,
                'skp_status'   => $asn->skp_status,
                'skor'         => $skor,
                'prioritas'    => $prioritas,
                'alasan'       => $alasan,
                'last_isi'     => $lastIsi,
                'last_kh'      => $lastKh,
                'last_tla'     => $lastTla,
                'sudah_isi_hari_ini' => isset($sudahIsiHariIni[$asn->id]),
            ];

            // Akumulasi per unit untuk ranking harian
            $uid = $asn->unit_kerja_id;
            if (!isset($perUnitHarian[$uid])) {
                $perUnitHarian[$uid] = [
                    'unit_id'   => $uid,
                    'nama_unit' => $asn->nama_unit,
                    'wajib'     => 0,
                    'sudah'     => 0,
                    'sudah_hari_ini' => 0,
                    'wajib_hari_ini' => 0,
                ];
            }
            $perUnitHarian[$uid]['wajib']  += $hkWajib;
            $perUnitHarian[$uid]['sudah']  += $hariIsi;
            if ($wajibIsiHariIni) {
                $perUnitHarian[$uid]['wajib_hari_ini']++;
                if (isset($sudahIsiHariIni[$asn->id])) {
                    $perUnitHarian[$uid]['sudah_hari_ini']++;
                }
            }
        }

        // Sort pembinaan: skor DESC → name ASC
        usort($pembinaan, function ($a, $b) {
            if ($b['skor'] !== $a['skor']) return $b['skor'] <=> $a['skor'];
            return strcmp($a['name'], $b['name']);
        });

        // Hitung distribusi prioritas
        $distribusi = ['TINGGI' => 0, 'SEDANG' => 0, 'RENDAH' => 0, 'BAIK' => 0];
        foreach ($pembinaan as $p) {
            $distribusi[$p['prioritas']]++;
        }

        // Ranking unit kerja berdasarkan kepatuhan bulanan
        $rankingUnit = $this->buildRankingUnit($perUnitHarian);

        // KPI harian
        $totalAsn      = count($pembinaan);
        $wajibHariIni  = $asnRows->filter(function ($asn) use ($tanggalStr, $isLiburKhususHariIni, $sedangCutiIds) {
            if (isset($sedangCutiIds[$asn->id])) return false;
            if ($isLiburKhususHariIni && $this->isGuruStdClass($asn)) return false;
            return $this->isWorkingDayForAsn(Carbon::parse($tanggalStr), $asn);
        })->count();

        $sudahHariIni  = count($sudahIsiHariIni);
        $belumHariIni  = max(0, $wajibHariIni - $sudahHariIni);
        $pctHariIni    = $wajibHariIni > 0 ? round($sudahHariIni / $wajibHariIni * 100, 1) : 0.0;

        return [
            'kpi_harian' => [
                'total_asn'     => $totalAsn,
                'wajib_hari_ini'=> $wajibHariIni,
                'sudah_hari_ini'=> $sudahHariIni,
                'belum_hari_ini'=> $belumHariIni,
                'persen'        => $pctHariIni,
                'warna'         => $this->warnaKepatuhan($pctHariIni),
                'tanggal'       => $tanggalStr,
            ],
            'distribusi'     => $distribusi,
            'pembinaan'      => $pembinaan,
            'ranking_unit'   => $rankingUnit,
            'hari_kerja_berlalu' => count($hariKerjaBerlalu),
        ];
    }

    // =========================================================================
    // SKP DISTRIBUSI
    // =========================================================================

    private function buildSkpDistribusi(int $tahun): array
    {
        $rows = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->select('s.status', DB::raw('COUNT(DISTINCT s.user_id) AS total'))
            ->where('s.tahun', $tahun)
            ->whereIn('u.unit_kerja_id', self::UNIT_IDS)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('s.status')
            ->pluck('total', 's.status')
            ->toArray();

        $totalAsn   = DB::table('users')
            ->whereIn('unit_kerja_id', self::UNIT_IDS)
            ->where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->count();

        $sudahBuat  = array_sum(array_values($rows));
        $belumBuat  = max(0, $totalAsn - $sudahBuat);
        $disetujui  = ($rows['DISETUJUI'] ?? 0) + ($rows['REVISI_DITOLAK'] ?? 0);
        $menunggu   = ($rows['DIAJUKAN'] ?? 0) + ($rows['REVISI_DIAJUKAN'] ?? 0);
        $perluTindak = ($rows['DRAFT'] ?? 0) + ($rows['DITOLAK'] ?? 0);

        return [
            'total_asn'       => $totalAsn,
            'sudah_buat'      => $sudahBuat,
            'belum_buat'      => $belumBuat,
            'disetujui'       => $disetujui,
            'menunggu'        => $menunggu,
            'perlu_tindak'    => $perluTindak,
            'persen_disetujui'=> $totalAsn > 0 ? round($disetujui / $totalAsn * 100, 1) : 0.0,
            'warna_disetujui' => $this->warnaKepatuhan($totalAsn > 0 ? round($disetujui / $totalAsn * 100, 1) : 0.0),
            'detail'          => array_merge(
                ['NULL' => $belumBuat],
                $rows
            ),
        ];
    }

    // =========================================================================
    // PER UNIT SKP — untuk tabel unit kerja
    // =========================================================================

    private function buildPerUnitSkp(int $tahun): array
    {
        $totalPerUnit = DB::table('users as u')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id', 'uk.nama_unit', DB::raw('COUNT(u.id) AS total_asn'))
            ->whereIn('u.unit_kerja_id', self::UNIT_IDS)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id', 'uk.nama_unit')
            ->get()->keyBy('id');

        $skpPerUnit = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->select(
                'u.unit_kerja_id',
                's.status',
                DB::raw('COUNT(DISTINCT s.user_id) AS total')
            )
            ->where('s.tahun', $tahun)
            ->whereIn('u.unit_kerja_id', self::UNIT_IDS)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('u.unit_kerja_id', 's.status')
            ->get();

        // Susun map: [unit_id][status] => count
        $skpMap = [];
        foreach ($skpPerUnit as $row) {
            $skpMap[$row->unit_kerja_id][$row->status] = (int) $row->total;
        }

        $result = [];
        foreach ($totalPerUnit as $unitId => $unit) {
            $map        = $skpMap[$unitId] ?? [];
            $totalAsn   = (int) $unit->total_asn;
            $sudahBuat  = array_sum(array_values($map));
            $disetujui  = ($map['DISETUJUI'] ?? 0) + ($map['REVISI_DITOLAK'] ?? 0);
            $menunggu   = ($map['DIAJUKAN'] ?? 0) + ($map['REVISI_DIAJUKAN'] ?? 0);
            $perlu      = ($map['DRAFT'] ?? 0) + ($map['DITOLAK'] ?? 0);
            $persen     = $totalAsn > 0 ? round($disetujui / $totalAsn * 100, 1) : 0.0;

            $result[] = [
                'unit_id'    => $unitId,
                'nama_unit'  => $unit->nama_unit,
                'total_asn'  => $totalAsn,
                'sudah_buat' => $sudahBuat,
                'belum_buat' => max(0, $totalAsn - $sudahBuat),
                'disetujui'  => $disetujui,
                'menunggu'   => $menunggu,
                'perlu'      => $perlu,
                'persen'     => $persen,
                'warna'      => $this->warnaKepatuhan($persen),
            ];
        }

        usort($result, function ($a, $b) {
            if ($b['persen'] !== $a['persen']) return $b['persen'] <=> $a['persen'];
            if ($b['total_asn'] !== $a['total_asn']) return $b['total_asn'] <=> $a['total_asn'];
            return strcmp($a['nama_unit'], $b['nama_unit']);
        });

        return $result;
    }

    // =========================================================================
    // RANKING UNIT KERJA (kepatuhan harian bulan berjalan)
    // =========================================================================

    private function buildRankingUnit(array $perUnitHarian): array
    {
        $result = [];
        foreach ($perUnitHarian as $unit) {
            if ($unit['wajib'] === 0) continue;
            $persen   = round($unit['sudah'] / $unit['wajib'] * 100, 1);
            $result[] = [
                'unit_id'   => $unit['unit_id'],
                'nama_unit' => $unit['nama_unit'],
                'wajib'     => $unit['wajib'],
                'sudah'     => $unit['sudah'],
                'persen'    => $persen,
                'warna'     => $this->warnaKepatuhan($persen),
                'wajib_hari_ini'  => $unit['wajib_hari_ini'],
                'sudah_hari_ini'  => $unit['sudah_hari_ini'],
            ];
        }

        usort($result, function ($a, $b) {
            if ($b['persen'] !== $a['persen']) return $b['persen'] <=> $a['persen'];
            if ($b['wajib'] !== $a['wajib'])   return $b['wajib']  <=> $a['wajib'];
            return strcmp($a['nama_unit'], $b['nama_unit']);
        });

        return $result;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Daftar hari kerja bulan ini yang sudah berlalu (inklusif hari ini).
     * Menggunakan HolidayHelper::isWorkingDay() tanpa konteks user — Senin–Jumat baseline.
     * Filter Sabtu untuk SENIN_SABTU ditangani per-ASN di hitungHariKerjaWajibFromArray().
     */
    private function getHariKerjaBerlalu(int $tahun, int $bulan, Carbon $today): array
    {
        $start   = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $end     = $today->copy()->startOfDay();
        $current = $start->copy();
        $hasil   = [];

        while ($current->lte($end)) {
            // Baseline: Minggu + libur nasional = bukan hari kerja untuk semua
            if (!$current->isSunday() && !HolidayHelper::isNationalHoliday($current)) {
                $hasil[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }

        return $hasil;
    }

    /**
     * Hitung hari kerja wajib isi untuk satu ASN pada bulan tertentu.
     * Mempertimbangkan: pola hari kerja (5/6 hari), libur khusus guru, cuti.
     */
    private function hitungHariKerjaWajibFromArray(
        array $hariKerjaBerlalu,
        object $asn,
        array $tanggalLiburGuru
    ): int {
        $isGuru = $this->isGuruStdClass($asn);
        $pola   = $this->getPolaHariKerja($asn);
        $count  = 0;

        foreach ($hariKerjaBerlalu as $tglStr) {
            $carbon = Carbon::parse($tglStr);

            // Sabtu hanya untuk pola SENIN_SABTU
            if ($carbon->isSaturday() && $pola === 'SENIN_JUMAT') continue;

            // Guru: skip hari libur khusus
            if ($isGuru && isset($tanggalLiburGuru[$tglStr])) continue;

            $count++;
        }

        return $count;
    }

    /**
     * Hitung hari kerja wajib isi untuk drill-down (satu ASN, query fresh).
     */
    private function hitungHariKerjaWajib(int $tahun, int $bulan, object $asn): int
    {
        $today  = now()->setTimezone('Asia/Makassar')->startOfDay();
        $hkArr  = $this->getHariKerjaBerlalu($tahun, $bulan, $today);

        $liburService     = new LiburKhususService();
        $tanggalLiburGuru = $liburService->getTanggalLiburGuruBulanan(
            [intval($asn->unit_kerja_id)],
            $bulan,
            $tahun
        );

        return $this->hitungHariKerjaWajibFromArray($hkArr, $asn, $tanggalLiburGuru);
    }

    /**
     * Hitung max gap in-memory dari batch map yang sudah di-preload.
     *
     * @param array $tglIsiSet   ['YYYY-MM-DD' => true] untuk ASN ini (subset dari tglIsiPerUser)
     * @param array $hariKerjaBerlalu  Daftar hari kerja baseline bulan ini
     */
    private function hitungMaxGapFromMap(array $tglIsiSet, array $hariKerjaBerlalu): int
    {
        $total = count($hariKerjaBerlalu);
        if ($total === 0) return 0;
        if (empty($tglIsiSet)) return $total;

        $maxGap = 0;
        $gap    = 0;
        foreach ($hariKerjaBerlalu as $tgl) {
            if (!isset($tglIsiSet[$tgl])) {
                $gap++;
                if ($gap > $maxGap) $maxGap = $gap;
            } else {
                $gap = 0;
            }
        }

        return $maxGap;
    }

    /**
     * Cek apakah ASN (stdClass dari DB query) adalah Guru.
     * isGuru() di LiburKhususService menuntut User model — kita hindari N+1 dengan
     * mengecek langsung property yang sudah ada di result set.
     */
    private function isGuruStdClass(object $asn): bool
    {
        return ($asn->role ?? 'ASN') === 'ASN'
            && str_contains(strtolower($asn->jabatan ?? ''), 'guru');
    }

    /**
     * Ambil pola hari kerja dari stdClass (tidak pakai HolidayHelper::getHariKerjaUser
     * karena itu membutuhkan Eloquent model untuk relationLoaded/unitKerja).
     * Hasil query sudah memuat u.hari_kerja — cukup baca properti itu.
     * Fallback ke SENIN_JUMAT jika null.
     */
    private function getPolaHariKerja(object $asn): string
    {
        $pola = $asn->hari_kerja ?? null;
        return in_array($pola, ['SENIN_JUMAT', 'SENIN_SABTU'], true) ? $pola : 'SENIN_JUMAT';
    }

    /**
     * Cek apakah tanggal adalah hari kerja untuk ASN stdClass.
     * Menggantikan HolidayHelper::isWorkingDay($date, $user) agar tidak
     * memanggil relationLoaded/unitKerja yang hanya ada di Eloquent model.
     */
    private function isWorkingDayForAsn(Carbon $date, object $asn): bool
    {
        if ($date->isSunday()) return false;
        if (HolidayHelper::isNationalHoliday($date)) return false;
        if ($date->isSaturday()) {
            return $this->getPolaHariKerja($asn) === 'SENIN_SABTU';
        }
        return true;
    }

    private function warnaKepatuhan(float $persen): string
    {
        if ($persen >= 80) return 'hijau';
        if ($persen >= 50) return 'kuning';
        return 'merah';
    }
}
