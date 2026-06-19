<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\LiburKhususService;

class MonitoringBimasIslamController extends Controller
{
    private const CACHE_KEY = 'monitoring_bimas_islam';
    private const CACHE_TTL = 300; // 5 menit

    // =========================================================================
    // UNIT_IDS — Hardcoded, hasil audit 2026-06-05
    // Exclude: ID 48 (Bimas Kristen Mateng), ID 166 (Urusan Agama Kristen Mamasa)
    // =========================================================================

    // Bidang Bimas Islam Kanwil
    private const ID_KANWIL = [4];

    // Seksi Bimbingan Masyarakat Islam per Kabupaten (di bawah Kankemenag)
    private const ID_SEKSI = [
        59,  // Seksi Bimas Islam Kab. Polewali Mandar
        119, // Seksi Bimas Islam Kab. Mamuju
        31,  // Seksi Bimas Islam Kab. Majene
        168, // Seksi Bimas Islam Kab. Mamasa
        47,  // Seksi Bimas Islam Kab. Mamuju Tengah
        126, // Seksi Bimas Islam Kab. Pasangkayu
    ];

    // KUA per Kabupaten
    private const KUA_POLMAN     = [74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89];
    private const KUA_MAMUJU     = [102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112];
    private const KUA_MAJENE     = [91, 92, 93, 94, 95, 96, 97, 98];
    private const KUA_MAMASA     = [141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151];
    private const KUA_MATENG     = [45, 52, 53, 54, 55];
    private const KUA_PASANGKAYU = [33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44];

    // Kankemenag per kabupaten (untuk label kelompok)
    private const KABUPATEN = [
        'Polewali Mandar' => ['seksi' => 59,  'kua' => self::KUA_POLMAN],
        'Mamuju'          => ['seksi' => 119, 'kua' => self::KUA_MAMUJU],
        'Majene'          => ['seksi' => 31,  'kua' => self::KUA_MAJENE],
        'Mamasa'          => ['seksi' => 168, 'kua' => self::KUA_MAMASA],
        'Mamuju Tengah'   => ['seksi' => 47,  'kua' => self::KUA_MATENG],
        'Pasangkayu'      => ['seksi' => 126, 'kua' => self::KUA_PASANGKAYU],
    ];

    private function allUnitIds(): array
    {
        return array_unique(array_merge(
            self::ID_KANWIL,
            self::ID_SEKSI,
            self::KUA_POLMAN,
            self::KUA_MAMUJU,
            self::KUA_MAJENE,
            self::KUA_MAMASA,
            self::KUA_MATENG,
            self::KUA_PASANGKAYU,
        ));
    }

    // =========================================================================
    // ROUTES
    // =========================================================================

    public function index(Request $request)
    {
        $token = $request->query('token');
        if (empty($token) || $token !== config('app.bimas_islam_monitor_token')) {
            abort(403, 'Akses ditolak.');
        }

        $tahun = (int) $request->query('tahun', now()->year);
        $bulan = (int) $request->query('bulan', now()->month);

        $cacheKey = self::CACHE_KEY . '_' . $tahun . '_' . $bulan;

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tahun, $bulan) {
            return $this->buildData($tahun, $bulan);
        });

        return view('monitoring.bimas-islam', [
            'data'       => $data,
            'tahun'      => $tahun,
            'bulan'      => $bulan,
            'token'      => $token,
            'lastUpdate' => now()->format('d M Y, H:i') . ' WIB',
        ]);
    }

    public function clearCache(Request $request)
    {
        $token = $request->query('token');
        if (empty($token) || $token !== config('app.bimas_islam_monitor_token')) {
            abort(403, 'Akses ditolak.');
        }

        foreach (range(now()->year - 1, now()->year + 1) as $y) {
            foreach (range(1, 12) as $m) {
                Cache::forget(self::CACHE_KEY . '_' . $y . '_' . $m);
            }
        }

        return redirect()->route('monitoring.bimas-islam', ['token' => $token])
            ->with('info', 'Cache berhasil di-refresh.');
    }

    // =========================================================================
    // BUILD DATA
    // =========================================================================

    private function buildData(int $tahun, int $bulan): array
    {
        $allIds  = $this->allUnitIds();
        $today   = now()->toDateString();
        $bulanStr = sprintf('%04d-%02d', $tahun, $bulan);

        // --- KPI Global ---
        $totalAsn = DB::table('users')
            ->whereIn('unit_kerja_id', $allIds)
            ->where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->count();

        // ASN sudah isi hari ini = punya entri KH (KINERJA_HARIAN) ATAU TLA (TUGAS_ATASAN)
        // Konsisten dengan logika MonitoringKakanwilController::getAsnAktifHariIni()
        $asnIds = DB::table('users')
            ->whereIn('unit_kerja_id', $allIds)
            ->where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->pluck('id')
            ->toArray();

        $khIds  = DB::table('progres_harian')
            ->whereIn('user_id', $asnIds)
            ->where('tanggal', $today)
            ->where('tipe_progres', 'KINERJA_HARIAN')
            ->distinct()->pluck('user_id');

        $tlaIds = DB::table('progres_harian')
            ->whereIn('user_id', $asnIds)
            ->where('tanggal', $today)
            ->where('tipe_progres', 'TUGAS_ATASAN')
            ->distinct()->pluck('user_id');

        $sudahIsiIds     = $khIds->merge($tlaIds)->unique()->toArray();
        $sudahIsiHariIni = count($sudahIsiIds);

        $belumIsiHariIni    = max(0, $totalAsn - $sudahIsiHariIni);
        $kepatuhanHarian    = $totalAsn > 0 ? round($sudahIsiHariIni / $totalAsn * 100, 1) : 0.0;

        // --- KPI SKP ---
        $dist = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->select('s.status', DB::raw('COUNT(*) as total'))
            ->where('s.tahun', $tahun)
            ->whereIn('u.unit_kerja_id', $allIds)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('s.status')
            ->pluck('total', 'status')
            ->toArray();

        $sudahBuatSkp  = array_sum(array_values($dist));
        $disetujuiSkp  = $dist['DISETUJUI'] ?? 0;
        $belumBuatSkp  = max(0, $totalAsn - $sudahBuatSkp);
        $kepatuhanSkp  = $totalAsn > 0 ? round($disetujuiSkp / $totalAsn * 100, 1) : 0.0;

        // --- KPI KUA ---
        $allKuaIds  = array_merge(
            self::KUA_POLMAN, self::KUA_MAMUJU, self::KUA_MAJENE,
            self::KUA_MAMASA, self::KUA_MATENG, self::KUA_PASANGKAYU,
        );
        $totalKua   = count($allKuaIds);

        return [
            'kpi' => [
                'total_asn'          => $totalAsn,
                'sudah_isi_hari_ini' => $sudahIsiHariIni,
                'belum_isi_hari_ini' => $belumIsiHariIni,
                'kepatuhan_harian'   => $kepatuhanHarian,
                'warna_harian'       => $this->warnaKepatuhan($kepatuhanHarian),
                'sudah_buat_skp'     => $sudahBuatSkp,
                'belum_buat_skp'     => $belumBuatSkp,
                'disetujui_skp'      => $disetujuiSkp,
                'kepatuhan_skp'      => $kepatuhanSkp,
                'warna_skp'          => $this->warnaKepatuhan($kepatuhanSkp),
                'total_kua'          => $totalKua,
            ],
            'kanwil'           => $this->getKanwilData($tahun, $today),
            'per_kabupaten'    => $this->getPerKabupaten($tahun, $today),
            'per_kua'          => $this->getPerKua($tahun, $today),
            'kepatuhan_per_unit' => $this->getKepatuhanPerUnit($today, $allIds),
            'ranking_kab'      => $this->getRankingKabupaten($tahun, $today),
            'ranking_kua_top'  => $this->getRankingKua($tahun, $today, 'top'),
            'ranking_kua_bot'  => $this->getRankingKua($tahun, $today, 'bottom'),
        ];
    }

    // =========================================================================
    // BIDANG BIMAS KANWIL — Tampil TERPISAH
    // =========================================================================

    private function getKanwilData(int $tahun, string $today): array
    {
        $ids = self::ID_KANWIL;

        $totalAsn = DB::table('users')
            ->whereIn('unit_kerja_id', $ids)
            ->where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->count();

        $asnIds = DB::table('users')
            ->whereIn('unit_kerja_id', $ids)
            ->where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->pluck('id')->toArray();

        $khIds  = DB::table('progres_harian')->whereIn('user_id', $asnIds)->where('tanggal', $today)->where('tipe_progres', 'KINERJA_HARIAN')->distinct()->pluck('user_id');
        $tlaIds = DB::table('progres_harian')->whereIn('user_id', $asnIds)->where('tanggal', $today)->where('tipe_progres', 'TUGAS_ATASAN')->distinct()->pluck('user_id');
        $sudahIsi = $khIds->merge($tlaIds)->unique()->count();

        $dist = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->select('s.status', DB::raw('COUNT(*) as total'))
            ->where('s.tahun', $tahun)
            ->whereIn('u.unit_kerja_id', $ids)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('s.status')
            ->pluck('total', 'status')
            ->toArray();

        $disetujui      = $dist['DISETUJUI'] ?? 0;
        $sudahBuat      = array_sum(array_values($dist));
        $kepatuhanHarian = $totalAsn > 0 ? round($sudahIsi / $totalAsn * 100, 1) : 0.0;
        $kepatuhanSkp   = $totalAsn > 0 ? round($disetujui / $totalAsn * 100, 1) : 0.0;

        return [
            'total_asn'       => $totalAsn,
            'sudah_isi'       => $sudahIsi,
            'belum_isi'       => max(0, $totalAsn - $sudahIsi),
            'kepatuhan_harian'=> $kepatuhanHarian,
            'warna_harian'    => $this->warnaKepatuhan($kepatuhanHarian),
            'sudah_buat_skp'  => $sudahBuat,
            'disetujui_skp'   => $disetujui,
            'belum_buat_skp'  => max(0, $totalAsn - $sudahBuat),
            'kepatuhan_skp'   => $kepatuhanSkp,
            'warna_skp'       => $this->warnaKepatuhan($kepatuhanSkp),
        ];
    }

    // =========================================================================
    // PER KABUPATEN — Seksi Bimas + seluruh KUA-nya digabung
    // =========================================================================

    private function getPerKabupaten(int $tahun, string $today): array
    {
        $result = [];

        foreach (self::KABUPATEN as $namaKab => $cfg) {
            $ids = array_merge([$cfg['seksi']], $cfg['kua']);

            $totalAsn = DB::table('users')
                ->whereIn('unit_kerja_id', $ids)
                ->where('role', 'ASN')
                ->where('status_pegawai', 'AKTIF')
                ->count();

            $asnIds = DB::table('users')
                ->whereIn('unit_kerja_id', $ids)
                ->where('role', 'ASN')
                ->where('status_pegawai', 'AKTIF')
                ->pluck('id')->toArray();

            if (empty($asnIds)) {
                $sudahIsi = 0;
            } else {
                $khIds  = DB::table('progres_harian')->whereIn('user_id', $asnIds)->where('tanggal', $today)->where('tipe_progres', 'KINERJA_HARIAN')->distinct()->pluck('user_id');
                $tlaIds = DB::table('progres_harian')->whereIn('user_id', $asnIds)->where('tanggal', $today)->where('tipe_progres', 'TUGAS_ATASAN')->distinct()->pluck('user_id');
                $sudahIsi = $khIds->merge($tlaIds)->unique()->count();
            }

            $dist = DB::table('skp_tahunan as s')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->select('s.status', DB::raw('COUNT(*) as total'))
                ->where('s.tahun', $tahun)
                ->whereIn('u.unit_kerja_id', $ids)
                ->where('u.role', 'ASN')
                ->where('u.status_pegawai', 'AKTIF')
                ->groupBy('s.status')
                ->pluck('total', 'status')
                ->toArray();

            $disetujui       = $dist['DISETUJUI'] ?? 0;
            $sudahBuat       = array_sum(array_values($dist));
            $kepatuhanHarian = $totalAsn > 0 ? round($sudahIsi / $totalAsn * 100, 1) : 0.0;
            $kepatuhanSkp    = $totalAsn > 0 ? round($disetujui / $totalAsn * 100, 1) : 0.0;

            $result[] = [
                'kabupaten'       => $namaKab,
                'total_asn'       => $totalAsn,
                'total_kua'       => count($cfg['kua']),
                'sudah_isi'       => $sudahIsi,
                'belum_isi'       => max(0, $totalAsn - $sudahIsi),
                'kepatuhan_harian'=> $kepatuhanHarian,
                'warna_harian'    => $this->warnaKepatuhan($kepatuhanHarian),
                'sudah_buat_skp'  => $sudahBuat,
                'disetujui_skp'   => $disetujui,
                'belum_buat_skp'  => max(0, $totalAsn - $sudahBuat),
                'kepatuhan_skp'   => $kepatuhanSkp,
                'warna_skp'       => $this->warnaKepatuhan($kepatuhanSkp),
            ];
        }

        // Urutkan kepatuhan SKP DESC
        usort($result, fn($a, $b) => $b['kepatuhan_skp'] <=> $a['kepatuhan_skp']);

        return $result;
    }

    // =========================================================================
    // PER KUA — Seluruh KUA (63 unit)
    // =========================================================================

    private function getPerKua(int $tahun, string $today): array
    {
        $allKuaIds = array_merge(
            self::KUA_POLMAN, self::KUA_MAMUJU, self::KUA_MAJENE,
            self::KUA_MAMASA, self::KUA_MATENG, self::KUA_PASANGKAYU,
        );

        // Peta kabupaten per KUA ID
        $kabMap = [];
        foreach (self::KABUPATEN as $namaKab => $cfg) {
            foreach ($cfg['kua'] as $kuaId) {
                $kabMap[$kuaId] = $namaKab;
            }
        }

        // Total ASN per KUA
        $asnPerKua = DB::table('users as u')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id', 'uk.nama_unit', DB::raw('COUNT(u.id) as total_asn'))
            ->whereIn('uk.id', $allKuaIds)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id', 'uk.nama_unit')
            ->get()->keyBy('id');

        // ASN sudah isi hari ini per KUA — union KH + TLA (konsisten dengan Kakanwil)
        $sudahIsiPerKua = DB::table('progres_harian as ph')
            ->join('users as u', 'ph.user_id', '=', 'u.id')
            ->select('u.unit_kerja_id', DB::raw('COUNT(DISTINCT ph.user_id) as sudah_isi'))
            ->whereIn('u.unit_kerja_id', $allKuaIds)
            ->where('ph.tanggal', $today)
            ->whereIn('ph.tipe_progres', ['KINERJA_HARIAN', 'TUGAS_ATASAN'])
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('u.unit_kerja_id')
            ->get()->keyBy('unit_kerja_id');

        // SKP disetujui per KUA
        $disetujuiPerKua = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->select('u.unit_kerja_id', DB::raw('COUNT(s.id) as total_disetujui'))
            ->where('s.tahun', $tahun)
            ->where('s.status', 'DISETUJUI')
            ->whereIn('u.unit_kerja_id', $allKuaIds)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('u.unit_kerja_id')
            ->get()->keyBy('unit_kerja_id');

        // SKP sudah buat per KUA
        $buatPerKua = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->select('u.unit_kerja_id', DB::raw('COUNT(s.id) as total_buat'))
            ->where('s.tahun', $tahun)
            ->whereIn('u.unit_kerja_id', $allKuaIds)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('u.unit_kerja_id')
            ->get()->keyBy('unit_kerja_id');

        $result = [];
        foreach ($asnPerKua as $kuaId => $kua) {
            $totalAsn        = (int) $kua->total_asn;
            $sudahIsi        = (int) ($sudahIsiPerKua[$kuaId]->sudah_isi ?? 0);
            $disetujui       = (int) ($disetujuiPerKua[$kuaId]->total_disetujui ?? 0);
            $sudahBuat       = (int) ($buatPerKua[$kuaId]->total_buat ?? 0);
            $kepatuhanHarian = $totalAsn > 0 ? round($sudahIsi / $totalAsn * 100, 1) : 0.0;
            $kepatuhanSkp    = $totalAsn > 0 ? round($disetujui / $totalAsn * 100, 1) : 0.0;

            $result[] = [
                'kua_id'          => $kuaId,
                'nama_unit'       => $kua->nama_unit,
                'kabupaten'       => $kabMap[$kuaId] ?? '-',
                'total_asn'       => $totalAsn,
                'sudah_isi'       => $sudahIsi,
                'belum_isi'       => max(0, $totalAsn - $sudahIsi),
                'kepatuhan_harian'=> $kepatuhanHarian,
                'warna_harian'    => $this->warnaKepatuhan($kepatuhanHarian),
                'sudah_buat_skp'  => $sudahBuat,
                'disetujui_skp'   => $disetujui,
                'belum_buat_skp'  => max(0, $totalAsn - $sudahBuat),
                'kepatuhan_skp'   => $kepatuhanSkp,
                'warna_skp'       => $this->warnaKepatuhan($kepatuhanSkp),
            ];
        }

        // Urutkan kepatuhan SKP DESC → nama ASC
        usort($result, function ($a, $b) {
            if ($b['kepatuhan_skp'] !== $a['kepatuhan_skp']) {
                return $b['kepatuhan_skp'] <=> $a['kepatuhan_skp'];
            }
            return strcmp($a['nama_unit'], $b['nama_unit']);
        });

        return $result;
    }

    // =========================================================================
    // ASN BELUM ISI KH HARI INI — Panel pembinaan
    // =========================================================================

    /**
     * Kepatuhan harian per unit kerja — tampilkan sudah+belum+daftar nama.
     * Diurutkan: belum_isi DESC (unit paling banyak belum di atas).
     */
    private function getKepatuhanPerUnit(string $today, array $allIds): array
    {
        $kabMap = [];
        foreach (self::KABUPATEN as $namaKab => $cfg) {
            $kabMap[$cfg['seksi']] = $namaKab;
            foreach ($cfg['kua'] as $kuaId) {
                $kabMap[$kuaId] = $namaKab;
            }
        }
        foreach (self::ID_KANWIL as $id) {
            $kabMap[$id] = 'Kanwil';
        }

        // Semua ASN Bimas beserta unit kerja
        $semua = DB::table('users as u')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('u.id', 'u.name', 'u.nip', 'u.jabatan', 'uk.id as uk_id', 'uk.nama_unit', 'u.unit_kerja_id')
            ->whereIn('u.unit_kerja_id', $allIds)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->orderBy('uk.nama_unit')
            ->orderBy('u.name')
            ->get();

        // Pre-load tanggal libur khusus Guru hari ini (satu batch query)
        $liburKhususService = new LiburKhususService();
        $tanggalLiburGuru   = $liburKhususService->getTanggalLiburGuruBulanan(
            $allIds,
            (int) \Carbon\Carbon::parse($today)->format('m'),
            (int) \Carbon\Carbon::parse($today)->format('Y')
        );
        $isLiburKhususHariIni = isset($tanggalLiburGuru[$today]);

        // ASN sudah isi hari ini (KH atau TLA)
        $khIds       = DB::table('progres_harian')->where('tanggal', $today)->where('tipe_progres', 'KINERJA_HARIAN')->distinct()->pluck('user_id');
        $tlaIds      = DB::table('progres_harian')->where('tanggal', $today)->where('tipe_progres', 'TUGAS_ATASAN')->distinct()->pluck('user_id');
        $sudahIsiSet = $khIds->merge($tlaIds)->unique()->flip(); // flip → key=user_id untuk O(1) lookup

        // Kelompokkan per unit
        $perUnit = [];
        foreach ($semua as $asn) {
            // Guru pada hari libur khusus tidak diwajibkan input — skip dari hitungan
            if ($isLiburKhususHariIni && $liburKhususService->isGuru((object) ['role' => 'ASN', 'jabatan' => $asn->jabatan ?? ''])) {
                continue;
            }

            $ukId = $asn->uk_id;
            if (!isset($perUnit[$ukId])) {
                $perUnit[$ukId] = [
                    'uk_id'      => $ukId,
                    'nama_unit'  => $asn->nama_unit,
                    'kabupaten'  => $kabMap[$asn->unit_kerja_id] ?? '-',
                    'sudah'      => [],
                    'belum'      => [],
                ];
            }
            $row = ['nama' => $asn->name, 'nip' => $asn->nip ?? '-'];
            if ($sudahIsiSet->has($asn->id)) {
                $perUnit[$ukId]['sudah'][] = $row;
            } else {
                $perUnit[$ukId]['belum'][] = $row;
            }
        }

        // Tambahkan agregat dan warna
        $result = [];
        foreach ($perUnit as $unit) {
            $total   = count($unit['sudah']) + count($unit['belum']);
            $persen  = $total > 0 ? round(count($unit['sudah']) / $total * 100) : 0;
            $result[] = [
                'uk_id'      => $unit['uk_id'],
                'nama_unit'  => $unit['nama_unit'],
                'kabupaten'  => $unit['kabupaten'],
                'total'      => $total,
                'sudah'      => $unit['sudah'],
                'belum'      => $unit['belum'],
                'jml_sudah'  => count($unit['sudah']),
                'jml_belum'  => count($unit['belum']),
                'persen'     => $persen,
                'warna'      => $this->warnaKepatuhan((float) $persen),
            ];
        }

        // Urutkan: belum_isi DESC → nama_unit ASC
        usort($result, function ($a, $b) {
            if ($b['jml_belum'] !== $a['jml_belum']) return $b['jml_belum'] <=> $a['jml_belum'];
            return strcmp($a['nama_unit'], $b['nama_unit']);
        });

        return $result;
    }

    // =========================================================================
    // RANKING KABUPATEN (berdasarkan kepatuhan harian)
    // =========================================================================

    private function getRankingKabupaten(int $tahun, string $today): array
    {
        $perKab = $this->getPerKabupaten($tahun, $today);
        usort($perKab, fn($a, $b) => $b['kepatuhan_harian'] <=> $a['kepatuhan_harian']);
        return $perKab; // 6 kabupaten, tampil semua
    }

    // =========================================================================
    // RANKING KUA TOP 10 / BOTTOM 10 (berdasarkan kepatuhan SKP)
    // =========================================================================

    private function getRankingKua(int $tahun, string $today, string $type): array
    {
        $perKua   = $this->getPerKua($tahun, $today);
        $filtered = array_filter($perKua, fn($u) => $u['total_asn'] > 0);

        if ($type === 'bottom') {
            usort($filtered, fn($a, $b) => $a['kepatuhan_skp'] <=> $b['kepatuhan_skp']);
        } else {
            usort($filtered, fn($a, $b) => $b['kepatuhan_skp'] <=> $a['kepatuhan_skp']);
        }

        return array_slice(array_values($filtered), 0, 10);
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    private function warnaKepatuhan(float $persen): string
    {
        if ($persen >= 80) return 'hijau';
        if ($persen >= 50) return 'kuning';
        return 'merah';
    }
}
