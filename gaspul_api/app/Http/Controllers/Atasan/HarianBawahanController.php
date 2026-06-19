<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use App\Models\ProgresHarian;
use App\Services\SubordinateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Dashboard Atasan - Monitoring Harian Bawahan
 *
 * Performance Target: < 200ms untuk 250 ASN
 *
 * Features:
 * - Monitoring progres harian bawahan (KH + TLA)
 * - Filter: Harian, Mingguan, Bulanan
 * - Status 3 warna: Merah/Kuning/Hijau
 * - Detail drill-down per ASN
 * - Cetak LKH dan TLA
 */
class HarianBawahanController extends Controller
{
    /**
     * Dashboard monitoring - Tabel per ASN
     */
    public function index(Request $request)
    {
        $atasan = Auth::user();

        // Validasi role
        if ($atasan->role !== 'ATASAN') {
            abort(403, 'Unauthorized');
        }

        // Filter parameters
        $mode = $request->input('mode', 'harian'); // harian|mingguan|bulanan
        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));

        $service = new SubordinateService();
        $isKepalaKab = $service->isKepalaKankemenagKab($atasan);
        $subordinateIds = $service->getMonitorableIds($atasan);

        $bawahan_list = $this->getBawahanWithStatus($subordinateIds, $tanggal, $mode);

        // Tab Monitoring Verifikasi — default bulan berjalan
        $vBulan = (int) $request->input('v_bulan', now()->month);
        $vTahun = (int) $request->input('v_tahun', now()->year);
        $verifikasiData = $this->getVerifikasiData($subordinateIds, $vBulan, $vTahun);

        $activeTab = $request->input('tab', 'aktivitas');

        $namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
                      'Juli','Agustus','September','Oktober','November','Desember'];
        $vPeriode = ($namaBulan[$vBulan] ?? $vBulan) . ' ' . $vTahun;

        return view('atasan.harian-bawahan.index', compact(
            'atasan', 'bawahan_list', 'mode', 'tanggal', 'isKepalaKab',
            'verifikasiData', 'vBulan', 'vTahun', 'vPeriode', 'activeTab'
        ));
    }

    /**
     * Detail ASN - Drill down
     */
    public function detail(Request $request, $user_id)
    {
        $atasan = Auth::user();

        // Security check: pastikan ASN adalah bawahan yang dapat dimonitor
        $service = new SubordinateService();
        $allowedIds = $service->getMonitorableIds($atasan);

        if (!$allowedIds->contains($user_id)) {
            abort(404, 'ASN tidak ditemukan atau bukan bawahan Anda');
        }

        $asn = DB::table('users')->where('id', $user_id)->first();

        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));
        $mode = $request->input('mode', 'harian');

        // Get progres detail
        $progres_list = $this->getProgresDetail($user_id, $tanggal, $mode);

        return view('atasan.harian-bawahan.detail', compact('asn', 'progres_list', 'tanggal', 'mode'));
    }

    /**
     * Monitoring Verifikasi Eviden — ringkasan per ASN bawahan.
     * Filter: bulan + tahun (default bulan berjalan).
     * Security: hanya bawahan yang dapat dimonitor atasan ini.
     */
    public function monitoringVerifikasi(Request $request)
    {
        $atasan = Auth::user();

        if ($atasan->role !== 'ATASAN') {
            abort(403, 'Unauthorized');
        }

        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $service    = new SubordinateService();
        $allowedIds = $service->getMonitorableIds($atasan);

        // Subquery: agregat verifikasi per user untuk bulan+tahun dipilih
        $verifikasiSub = DB::table('progres_harian as ph')
            ->select(
                'ph.user_id',
                DB::raw("COUNT(CASE WHEN ph.status_bukti = 'SUDAH_ADA' AND ph.bukti_dukung IS NOT NULL AND ph.bukti_dukung != '' THEN 1 END) as ada_bukti"),
                DB::raw("COUNT(CASE WHEN ph.verified_at IS NOT NULL THEN 1 END) as sudah_verif"),
                DB::raw("COUNT(CASE WHEN ph.status_bukti = 'SUDAH_ADA' AND ph.bukti_dukung IS NOT NULL AND ph.bukti_dukung != '' AND ph.verified_at IS NULL THEN 1 END) as belum_verif"),
                DB::raw("COUNT(CASE WHEN ph.status_bukti = 'BELUM_ADA' OR ph.bukti_dukung IS NULL OR ph.bukti_dukung = '' THEN 1 END) as link_kosong")
            )
            ->whereIn('ph.user_id', $allowedIds)
            ->whereYear('ph.tanggal', $tahun)
            ->whereMonth('ph.tanggal', $bulan)
            ->groupBy('ph.user_id');

        // Join ke users untuk nama ASN, join ke subquery agregat
        $list = DB::table('users as u')
            ->leftJoinSub($verifikasiSub, 'vs', 'u.id', '=', 'vs.user_id')
            ->whereIn('u.id', $allowedIds)
            ->select(
                'u.id',
                'u.name',
                'u.nip',
                'u.jabatan',
                DB::raw('COALESCE(vs.ada_bukti,  0) as ada_bukti'),
                DB::raw('COALESCE(vs.sudah_verif, 0) as sudah_verif'),
                DB::raw('COALESCE(vs.belum_verif, 0) as belum_verif'),
                DB::raw('COALESCE(vs.link_kosong, 0) as link_kosong'),
                DB::raw("ROUND(CASE WHEN COALESCE(vs.ada_bukti,0) > 0
                    THEN COALESCE(vs.sudah_verif,0) / COALESCE(vs.ada_bukti,0) * 100
                    ELSE 0 END, 1) as persen")
            )
            ->orderByRaw('COALESCE(vs.belum_verif,0) DESC')
            ->orderBy('u.name')
            ->get();

        // KPI ringkasan keseluruhan
        $totalAdaBukti  = $list->sum('ada_bukti');
        $totalSudah     = $list->sum('sudah_verif');
        $totalBelum     = $list->sum('belum_verif');
        $totalLinkKosong= $list->sum('link_kosong');
        $persenTotal    = $totalAdaBukti > 0 ? round($totalSudah / $totalAdaBukti * 100, 1) : 0;

        $kpi = compact(
            'totalAdaBukti', 'totalSudah', 'totalBelum', 'totalLinkKosong', 'persenTotal'
        );

        $namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
                      'Juli','Agustus','September','Oktober','November','Desember'];
        $periode = ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun;

        return view('atasan.monitoring-verifikasi.index', compact(
            'list', 'kpi', 'bulan', 'tahun', 'periode', 'atasan'
        ));
    }

    /**
     * Cetak LKH (Laporan Kinerja Harian)
     */
    public function cetakLKH($user_id, $tanggal)
    {
        $atasan = Auth::user();

        $service = new SubordinateService();
        $allowedIds = $service->getMonitorableIds($atasan);

        if (!$allowedIds->contains($user_id)) {
            abort(404);
        }

        $asn = DB::table('users')->where('id', $user_id)->first();

        // Get LKH data
        $data_lkh = DB::table('progres_harian')
            ->where('user_id', $user_id)
            ->where('tipe_progres', 'KINERJA_HARIAN')
            ->whereDate('tanggal', $tanggal)
            ->get();

        return view('atasan.harian-bawahan.cetak-lkh', compact('asn', 'data_lkh', 'tanggal', 'atasan'));
    }

    /**
     * Cetak TLA (Tugas Langsung Atasan)
     */
    public function cetakTLA($user_id, $tanggal)
    {
        $atasan = Auth::user();

        $service = new SubordinateService();
        $allowedIds = $service->getMonitorableIds($atasan);

        if (!$allowedIds->contains($user_id)) {
            abort(404);
        }

        $asn = DB::table('users')->where('id', $user_id)->first();

        // Get TLA data
        $data_tla = DB::table('progres_harian')
            ->where('user_id', $user_id)
            ->where('tipe_progres', 'TUGAS_ATASAN')
            ->whereDate('tanggal', $tanggal)
            ->get();

        return view('atasan.harian-bawahan.cetak-tla', compact('asn', 'data_tla', 'tanggal', 'atasan'));
    }

    // ========================================================================
    // PRIVATE METHODS - OPTIMIZED QUERIES
    // ========================================================================

    /**
     * Get bawahan with status (OPTIMIZED - Single Query)
     *
     * Uses: JOIN + GROUP BY + SUM + COUNT(CASE)
     * NO whereHas nesting!
     */
    private function getBawahanWithStatus($subordinateIds, $tanggal, $mode)
    {
        // Build date range
        list($start_date, $end_date) = $this->getDateRange($tanggal, $mode);

        // ⚡ OPTIMIZED QUERY - Single query untuk semua ASN
        $result = DB::table('users as u')
            ->leftJoin('progres_harian as ph', function($join) use ($start_date, $end_date) {
                $join->on('u.id', '=', 'ph.user_id')
                     ->whereBetween('ph.tanggal', [$start_date, $end_date]);
            })
            ->whereIn('u.id', $subordinateIds)
            ->select(
                'u.id',
                'u.name',
                'u.nip',
                DB::raw('SUM(ph.durasi_menit) as total_menit'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "KINERJA_HARIAN" THEN 1 END) as total_kh'),
                DB::raw('COUNT(CASE WHEN ph.tipe_progres = "TUGAS_ATASAN" THEN 1 END) as total_tla'),
                DB::raw('COUNT(ph.id) as total_progres'),
                DB::raw('SUM(CASE WHEN ph.status_bukti = "BELUM_ADA" OR ph.status_bukti IS NULL THEN 1 ELSE 0 END) as total_tanpa_bukti')
            )
            ->groupBy('u.id', 'u.name', 'u.nip')
            ->orderBy('u.name')
            ->get();

        // Calculate status for each ASN
        return $result->map(function($asn) {
            $asn->total_jam = $this->formatDurasi($asn->total_menit);
            $asn->status = $this->calculateStatus($asn->total_menit, $asn->total_progres, $asn->total_tanpa_bukti);
            $asn->status_badge = $this->getStatusBadge($asn->status);
            return $asn;
        });
    }

    /**
     * Verifikasi eviden per aktivitas oleh atasan.
     * Security: hanya bawahan yang berada dalam pengawasan atasan yang dapat diverifikasi.
     */
    public function verifikasi(Request $request, int $progresId)
    {
        $atasan = Auth::user();

        if ($atasan->role !== 'ATASAN') {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'verifikasi_eviden' => ['required', 'in:SESUAI,KURANG,TIDAK_SESUAI'],
            'catatan_verifikasi' => [
                'nullable', 'string', 'max:2000',
                function ($attribute, $value, $fail) use ($request) {
                    if (in_array($request->verifikasi_eviden, ['KURANG', 'TIDAK_SESUAI']) && empty($value)) {
                        $fail('Catatan wajib diisi jika status Kurang atau Tidak Sesuai.');
                    }
                },
            ],
        ], [
            'verifikasi_eviden.required' => 'Pilih salah satu status verifikasi.',
            'verifikasi_eviden.in'       => 'Status verifikasi tidak valid.',
        ]);

        // Security: pastikan progres milik bawahan yang dimonitor atasan ini
        $service = new SubordinateService();
        $allowedIds = $service->getMonitorableIds($atasan);

        $progres = ProgresHarian::where('id', $progresId)
            ->whereIn('user_id', $allowedIds)
            ->firstOrFail();

        $progres->update([
            'verifikasi_eviden'  => $request->verifikasi_eviden,
            'catatan_verifikasi' => $request->catatan_verifikasi ?? null,
            'verified_by'        => $atasan->id,
            'verified_at'        => now(),
        ]);

        return response()->json([
            'success'           => true,
            'verifikasi_eviden' => $progres->verifikasi_eviden,
            'catatan_verifikasi'=> $progres->catatan_verifikasi,
            'verified_by_name'  => $atasan->name,
            'verified_at'       => $progres->verified_at?->locale('id')->isoFormat('D MMM Y, HH:mm'),
        ]);
    }

    /**
     * Get progres detail for ASN
     */
    private function getProgresDetail($user_id, $tanggal, $mode)
    {
        list($start_date, $end_date) = $this->getDateRange($tanggal, $mode);

        return DB::table('progres_harian')
            ->where('user_id', $user_id)
            ->whereBetween('tanggal', [$start_date, $end_date])
            ->select(
                'id',
                'tanggal',
                'jam_mulai',
                'jam_selesai',
                'durasi_menit',
                'tipe_progres',
                'rencana_kegiatan_harian',
                'tugas_atasan',
                'progres',
                'satuan',
                'bukti_dukung',
                'status_bukti',
                'verifikasi_eviden',
                'catatan_verifikasi',
                'verified_by',
                'verified_at'
            )
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->get();
    }

    /**
     * Calculate date range based on mode
     */
    private function getDateRange($tanggal, $mode)
    {
        $date = Carbon::parse($tanggal);

        switch ($mode) {
            case 'mingguan':
                return [$date->copy()->startOfWeek()->toDateString(), $date->copy()->endOfWeek()->toDateString()];
            case 'bulanan':
                return [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()];
            default: // harian
                return [$tanggal, $tanggal];
        }
    }

    /**
     * Calculate status based on rules:
     * 🔴 MERAH = Tidak ada progres sama sekali
     * 🟡 KUNING = Ada progres tapi < 450 menit ATAU ada yang tanpa bukti
     * 🟢 HIJAU = >= 450 menit DAN semua ada bukti
     */
    private function calculateStatus($total_menit, $total_progres, $total_tanpa_bukti)
    {
        // MERAH: Tidak ada progres
        if ($total_progres == 0) {
            return 'MERAH';
        }

        // HIJAU: >= 450 menit DAN semua ada bukti
        if ($total_menit >= 450 && $total_tanpa_bukti == 0) {
            return 'HIJAU';
        }

        // KUNING: Sisanya
        return 'KUNING';
    }

    /**
     * Get status badge HTML
     */
    private function getStatusBadge($status)
    {
        switch ($status) {
            case 'HIJAU':
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">🟢 HIJAU</span>';
            case 'KUNING':
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">🟡 KUNING</span>';
            default: // MERAH
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">🔴 MERAH</span>';
        }
    }

    /**
     * Format durasi to hours and minutes
     */
    private function formatDurasi($menit)
    {
        if (!$menit) return '0j 0m';

        $jam = floor($menit / 60);
        $sisa_menit = $menit % 60;

        return "{$jam}j {$sisa_menit}m";
    }

    /**
     * Export Excel monitoring verifikasi eviden per bawahan.
     * Menggunakan ZipArchive + XML — zero dependency, sama dengan MonitoringKakanwilController.
     */
    public function exportVerifikasi(Request $request)
    {
        $atasan = Auth::user();
        if ($atasan->role !== 'ATASAN') {
            abort(403, 'Unauthorized');
        }

        $bulan  = (int) $request->input('v_bulan', now()->month);
        $tahun  = (int) $request->input('v_tahun', now()->year);

        $service        = new SubordinateService();
        $subordinateIds = $service->getMonitorableIds($atasan);
        $data           = $this->getVerifikasiData($subordinateIds, $bulan, $tahun);

        // Query catatan verifikasi per ASN — 1 query GROUP_CONCAT, zero N+1
        $catatanPerUser = DB::table('progres_harian')
            ->whereIn('user_id', $subordinateIds)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->whereNotNull('catatan_verifikasi')
            ->where('catatan_verifikasi', '!=', '')
            ->whereNotNull('verifikasi_eviden')
            ->select(
                'user_id',
                DB::raw("GROUP_CONCAT(DISTINCT catatan_verifikasi ORDER BY verified_at SEPARATOR '; ') as semua_catatan"),
                // Temuan: hitung kemunculan setiap catatan untuk ringkasan
                DB::raw("GROUP_CONCAT(catatan_verifikasi ORDER BY verified_at SEPARATOR '|||') as raw_catatan")
            )
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
                      'Juli','Agustus','September','Oktober','November','Desember'];
        $periode   = ($namaBulan[$bulan] ?? $bulan) . ' ' . $tahun;

        $xlsx     = $this->buildVerifikasiXlsx($data['list'], $periode, $atasan->name, $catatanPerUser);
        $filename = 'Verifikasi_Eviden_' . str_replace(' ', '_', $periode) . '.xlsx';

        return response($xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => strlen($xlsx),
        ]);
    }

    /**
     * Buat ringkasan temuan dari kumpulan catatan mentah.
     * Contoh input: "Link terkunci; Link terkunci; Dokumen belum lengkap"
     * Output: "Link terkunci (2); Dokumen belum lengkap (1)"
     */
    private function buildTemuanRingkasan(string $rawCatatan): string
    {
        $items = array_filter(array_map('trim', explode('|||', $rawCatatan)));
        if (empty($items)) return '-';

        // Hitung frekuensi setiap catatan unik
        $freq = [];
        foreach ($items as $item) {
            // Normalisasi: potong terlalu panjang, ambil 60 karakter pertama
            $key = mb_substr(trim($item), 0, 60);
            if ($key === '') continue;
            $freq[$key] = ($freq[$key] ?? 0) + 1;
        }

        arsort($freq); // urut dari yang paling sering
        $parts = [];
        foreach ($freq as $catatan => $count) {
            $parts[] = $count > 1 ? "{$catatan} ({$count})" : $catatan;
        }

        $result = implode('; ', $parts);
        return mb_strlen($result) > 300 ? mb_substr($result, 0, 297) . '...' : $result;
    }

    /**
     * Build XLSX verifikasi eviden — ZipArchive + inline XML, tanpa library eksternal.
     */
    private function buildVerifikasiXlsx($list, string $periode, string $atasanNama, $catatanPerUser = null): string
    {
        $escape = fn(string $v): string => htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $rowsXml = '';
        $rowNum  = 5; // baris 1–4 untuk judul/header

        foreach ($list as $i => $r) {
            // Status
            if ($r->ada_bukti == 0)       $status = 'TANPA EVIDEN';
            elseif ($r->belum_verif == 0) $status = 'TUNTAS';
            else                          $status = 'BELUM TUNTAS';

            // Kolom L: Temuan Verifikasi (ringkasan frekuensi catatan)
            // Kolom M: Catatan Verifikasi (gabungan semua catatan unik, max 500 karakter)
            $temuan  = '-';
            $catatan = '-';
            if ($catatanPerUser && isset($catatanPerUser[$r->id])) {
                $cRow    = $catatanPerUser[$r->id];
                $temuan  = $this->buildTemuanRingkasan($cRow->raw_catatan ?? '');
                $catatan = $cRow->semua_catatan ?? '-';
                if (mb_strlen($catatan) > 500) {
                    $catatan = mb_substr($catatan, 0, 497) . '...';
                }
            }

            $rowsXml .= "<row r=\"{$rowNum}\">"
                . "<c r=\"A{$rowNum}\" t=\"n\"><v>" . ($i+1) . "</v></c>"
                . "<c r=\"B{$rowNum}\" t=\"inlineStr\"><is><t>{$escape($r->name)}</t></is></c>"
                . "<c r=\"C{$rowNum}\" t=\"inlineStr\"><is><t>{$escape($r->nip ?? '-')}</t></is></c>"
                . "<c r=\"D{$rowNum}\" t=\"inlineStr\"><is><t>{$escape($r->nama_unit ?? '-')}</t></is></c>"
                . "<c r=\"E{$rowNum}\" t=\"n\"><v>{$r->ada_bukti}</v></c>"
                . "<c r=\"F{$rowNum}\" t=\"n\"><v>{$r->jml_sesuai}</v></c>"
                . "<c r=\"G{$rowNum}\" t=\"n\"><v>{$r->jml_kurang}</v></c>"
                . "<c r=\"H{$rowNum}\" t=\"n\"><v>{$r->jml_tidak_sesuai}</v></c>"
                . "<c r=\"I{$rowNum}\" t=\"n\"><v>{$r->belum_verif}</v></c>"
                . "<c r=\"J{$rowNum}\" t=\"inlineStr\"><is><t>{$r->persen}%</t></is></c>"
                . "<c r=\"K{$rowNum}\" t=\"inlineStr\"><is><t>{$escape($status)}</t></is></c>"
                . "<c r=\"L{$rowNum}\" t=\"inlineStr\"><is><t>{$escape($temuan)}</t></is></c>"
                . "<c r=\"M{$rowNum}\" t=\"inlineStr\"><is><t>{$escape($catatan)}</t></is></c>"
                . "</row>\n";
            $rowNum++;
        }

        // Baris total
        $tAdaBukti    = $list->sum('ada_bukti');
        $tSesuai      = $list->sum('jml_sesuai');
        $tKurang      = $list->sum('jml_kurang');
        $tTidakSesuai = $list->sum('jml_tidak_sesuai');
        $tBelum       = $list->sum('belum_verif');
        $tPersen      = $tAdaBukti > 0 ? round(($tAdaBukti - $tBelum) / $tAdaBukti * 100, 1) : 0;

        $rowsXml .= "<row r=\"{$rowNum}\">"
            . "<c r=\"A{$rowNum}\" t=\"inlineStr\"><is><t></t></is></c>"
            . "<c r=\"B{$rowNum}\" t=\"inlineStr\"><is><t>TOTAL</t></is></c>"
            . "<c r=\"C{$rowNum}\" t=\"inlineStr\"><is><t></t></is></c>"
            . "<c r=\"D{$rowNum}\" t=\"inlineStr\"><is><t></t></is></c>"
            . "<c r=\"E{$rowNum}\" t=\"n\"><v>{$tAdaBukti}</v></c>"
            . "<c r=\"F{$rowNum}\" t=\"n\"><v>{$tSesuai}</v></c>"
            . "<c r=\"G{$rowNum}\" t=\"n\"><v>{$tKurang}</v></c>"
            . "<c r=\"H{$rowNum}\" t=\"n\"><v>{$tTidakSesuai}</v></c>"
            . "<c r=\"I{$rowNum}\" t=\"n\"><v>{$tBelum}</v></c>"
            . "<c r=\"J{$rowNum}\" t=\"inlineStr\"><is><t>{$tPersen}%</t></is></c>"
            . "<c r=\"K{$rowNum}\" t=\"inlineStr\"><is><t></t></is></c>"
            . "<c r=\"L{$rowNum}\" t=\"inlineStr\"><is><t></t></is></c>"
            . "<c r=\"M{$rowNum}\" t=\"inlineStr\"><is><t></t></is></c>"
            . "</row>\n";

        $sheetXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1"><c r="A1" t="inlineStr"><is><t>MONITORING VERIFIKASI EVIDEN e_SARAku</t></is></c></row>
    <row r="2"><c r="A2" t="inlineStr"><is><t>Periode: {$escape($periode)}</t></is></c></row>
    <row r="3"><c r="A3" t="inlineStr"><is><t>Atasan: {$escape($atasanNama)}</t></is></c></row>
    <row r="4">
      <c r="A4" t="inlineStr"><is><t>No</t></is></c>
      <c r="B4" t="inlineStr"><is><t>Nama ASN</t></is></c>
      <c r="C4" t="inlineStr"><is><t>NIP</t></is></c>
      <c r="D4" t="inlineStr"><is><t>Unit Kerja</t></is></c>
      <c r="E4" t="inlineStr"><is><t>Jumlah Eviden</t></is></c>
      <c r="F4" t="inlineStr"><is><t>Sesuai</t></is></c>
      <c r="G4" t="inlineStr"><is><t>Kurang</t></is></c>
      <c r="H4" t="inlineStr"><is><t>Tidak Sesuai</t></is></c>
      <c r="I4" t="inlineStr"><is><t>Belum Diverifikasi</t></is></c>
      <c r="J4" t="inlineStr"><is><t>Persentase Verifikasi</t></is></c>
      <c r="K4" t="inlineStr"><is><t>Status</t></is></c>
      <c r="L4" t="inlineStr"><is><t>Temuan Verifikasi</t></is></c>
      <c r="M4" t="inlineStr"><is><t>Catatan Verifikasi</t></is></c>
    </row>
    {$rowsXml}
  </sheetData>
</worksheet>
XML;

        $workbookXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="Verifikasi Eviden" sheetId="1" r:id="rId1"/></sheets>
</workbook>
XML;
        $workbookRels = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML;
        $contentTypes = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML;
        $packageRels = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;

        $tmpFile = tempnam(sys_get_temp_dir(), 'esaraku_vrf_');
        $zip = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',         $contentTypes);
        $zip->addFromString('_rels/.rels',                 $packageRels);
        $zip->addFromString('xl/workbook.xml',             $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels',  $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml',    $sheetXml);
        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $content;
    }

    /**
     * Agregat data verifikasi eviden per bawahan untuk bulan+tahun tertentu.
     * Digunakan oleh Tab "Monitoring Verifikasi" di halaman Harian Bawahan.
     */
    private function getVerifikasiData($subordinateIds, int $bulan, int $tahun): array
    {
        $verifikasiSub = DB::table('progres_harian as ph')
            ->select(
                'ph.user_id',
                DB::raw("COUNT(CASE WHEN ph.status_bukti='SUDAH_ADA' AND ph.bukti_dukung IS NOT NULL AND ph.bukti_dukung!='' THEN 1 END) as ada_bukti"),
                DB::raw("COUNT(CASE WHEN ph.verified_at IS NOT NULL THEN 1 END) as sudah_verif"),
                DB::raw("COUNT(CASE WHEN ph.status_bukti='SUDAH_ADA' AND ph.bukti_dukung IS NOT NULL AND ph.bukti_dukung!='' AND ph.verified_at IS NULL THEN 1 END) as belum_verif"),
                DB::raw("COUNT(CASE WHEN ph.status_bukti='BELUM_ADA' OR ph.bukti_dukung IS NULL OR ph.bukti_dukung='' THEN 1 END) as link_kosong"),
                // Breakdown hasil verifikasi
                DB::raw("COUNT(CASE WHEN ph.verifikasi_eviden='SESUAI' THEN 1 END) as jml_sesuai"),
                DB::raw("COUNT(CASE WHEN ph.verifikasi_eviden='KURANG' THEN 1 END) as jml_kurang"),
                DB::raw("COUNT(CASE WHEN ph.verifikasi_eviden='TIDAK_SESUAI' THEN 1 END) as jml_tidak_sesuai")
            )
            ->whereIn('ph.user_id', $subordinateIds)
            ->whereYear('ph.tanggal', $tahun)
            ->whereMonth('ph.tanggal', $bulan)
            ->groupBy('ph.user_id');

        $list = DB::table('users as u')
            ->leftJoin('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->leftJoinSub($verifikasiSub, 'vs', 'u.id', '=', 'vs.user_id')
            ->whereIn('u.id', $subordinateIds)
            ->select(
                'u.id', 'u.name', 'u.nip', 'u.jabatan',
                'uk.nama_unit',
                DB::raw('COALESCE(vs.ada_bukti,      0) as ada_bukti'),
                DB::raw('COALESCE(vs.sudah_verif,    0) as sudah_verif'),
                DB::raw('COALESCE(vs.belum_verif,    0) as belum_verif'),
                DB::raw('COALESCE(vs.link_kosong,    0) as link_kosong'),
                DB::raw('COALESCE(vs.jml_sesuai,     0) as jml_sesuai'),
                DB::raw('COALESCE(vs.jml_kurang,     0) as jml_kurang'),
                DB::raw('COALESCE(vs.jml_tidak_sesuai,0) as jml_tidak_sesuai'),
                DB::raw("ROUND(CASE WHEN COALESCE(vs.ada_bukti,0)>0
                    THEN COALESCE(vs.sudah_verif,0)/COALESCE(vs.ada_bukti,0)*100
                    ELSE 0 END, 1) as persen")
            )
            ->orderByRaw('COALESCE(vs.belum_verif,0) DESC')
            ->orderBy('u.name')
            ->get();

        $totalAdaBukti = $list->sum('ada_bukti');
        $totalSudah    = $list->sum('sudah_verif');
        $totalBelum    = $list->sum('belum_verif');
        $persenTotal   = $totalAdaBukti > 0 ? round($totalSudah / $totalAdaBukti * 100, 1) : 0;

        // KPI berbasis ASN — dihitung dari collection, zero query tambahan
        // ASN Tuntas: punya eviden DAN belum_verif = 0
        $asnTuntas      = $list->filter(fn($r) => $r->ada_bukti > 0 && $r->belum_verif === 0)->count();
        // ASN Belum Tuntas: punya eviden DAN masih ada belum_verif > 0
        $asnBelumTuntas = $list->filter(fn($r) => $r->ada_bukti > 0 && $r->belum_verif > 0)->count();
        // ASN tanpa eviden: tidak masuk antrian verifikasi
        $asnTanpaEviden = $list->filter(fn($r) => $r->ada_bukti === 0)->count();

        return compact(
            'list',
            'totalAdaBukti', 'totalSudah', 'totalBelum', 'persenTotal',
            'asnTuntas', 'asnBelumTuntas', 'asnTanpaEviden'
        );
    }
}
