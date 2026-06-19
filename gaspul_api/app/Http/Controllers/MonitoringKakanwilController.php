<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\LiburKhususService;

class MonitoringKakanwilController extends Controller
{
    private const CACHE_KEY = 'monitoring_kakanwil';
    private const CACHE_TTL = 300; // 5 menit

    public function clearCache(Request $request)
    {
        $key = $request->query('key');
        if ($key !== config('app.kakanwil_monitor_key') || empty($key)) {
            abort(403, 'Akses ditolak.');
        }

        // Hapus semua cache monitoring (semua tahun)
        foreach (range(now()->year - 2, now()->year + 1) as $y) {
            Cache::forget(self::CACHE_KEY . '_' . $y);
        }

        return redirect()->route('monitoring.kakanwil', ['key' => $key])
            ->with('info', 'Cache monitoring berhasil di-refresh.');
    }

    public function index(Request $request)
    {
        // ====================================================================
        // TOKEN GUARD — tidak perlu login, tapi butuh key yang benar
        // ====================================================================
        $key = $request->query('key');
        if ($key !== config('app.kakanwil_monitor_key') || empty($key)) {
            abort(403, 'Akses ditolak.');
        }

        $tahun = (int) $request->query('tahun', now()->year);

        // Cache key per tahun
        $cacheKey = self::CACHE_KEY . '_' . $tahun;

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tahun) {
            return $this->buildMonitoringData($tahun);
        });

        // Data real-time — TIDAK di-cache, selalu fresh per request
        $totalAsn        = $this->getTotalAsn();
        $totalAtasan     = $this->getTotalAtasan();
        $asnAktifHariIni = $this->getAsnAktifHariIni($totalAsn);
        $bulanBerjalan   = (int) now()->month;
        $progressHari    = $this->getProgressHariKerja($bulanBerjalan, $tahun);
        $totalHariKerja  = \App\Helpers\HolidayHelper::countWorkingDaysInMonth($bulanBerjalan, $tahun);

        // Breakdown mingguan — menggantikan breakdown bulanan
        $mingguan = $this->getBreakdownMingguan();

        // Override key real-time di atas data cached
        $data['kpi']['asn_aktif_hari_ini']  = $asnAktifHariIni;
        $data['kpi']['progress_hari']       = $progressHari;
        $data['kpi']['total_hari_kerja']    = $totalHariKerja;
        $data['kpi']['mingguan']            = $mingguan;
        // Pastikan total_asn selalu dari query fresh
        $data['kpi']['total_asn']           = $totalAsn > 0 ? $totalAsn : $data['kpi']['total_asn'];
        $data['kpi']['total_atasan']        = $totalAtasan;
        $data['kpi']['total_pegawai']       = $totalAsn + $totalAtasan;

        Log::info('Monitoring ASN Aktif', [
            'total_asn'          => $totalAsn,
            'asn_aktif_hari_ini' => $asnAktifHariIni['asn_aktif'],
            'persen_hari_ini'    => $asnAktifHariIni['persen'],
        ]);

        // Smart context — bantu pimpinan interpretasi angka ASN aktif hari ini
        $now         = now()->setTimezone('Asia/Makassar');
        $jamSekarang = (int) $now->format('H');
        $isHariKerja = \App\Helpers\HolidayHelper::isWorkingDay($now);
        $isJamKerja  = $jamSekarang >= 7 && $jamSekarang <= 17;

        if (!$isHariKerja) {
            $statusWaktu  = 'libur';
            $messageWaktu = 'Hari libur — aktivitas kerja tidak berlangsung';
        } elseif (!$isJamKerja) {
            $statusWaktu  = 'belum_jam_kerja';
            $messageWaktu = 'Aktivitas belum dimulai — data akan terisi saat jam kerja';
        } else {
            $statusWaktu  = 'jam_kerja';
            $messageWaktu = null;
        }

        // Jam kerja dinamis: Senin–Kamis vs Jumat, null jika Sabtu/Minggu
        $dayOfWeek = $now->dayOfWeek; // 0=Minggu, 1=Senin, ..., 5=Jumat, 6=Sabtu
        if ($dayOfWeek >= 1 && $dayOfWeek <= 4) {
            $jamKerja = '07:30 – 16:00 WITA';
        } elseif ($dayOfWeek === 5) {
            $jamKerja = '07:30 – 16:30 WITA';
        } else {
            $jamKerja = null; // Sabtu / Minggu
        }

        return view('monitoring.kakanwil', [
            'data'         => $data,
            'tahun'        => $tahun,
            'monitorKey'   => config('app.kakanwil_monitor_key'),
            'lastUpdate'   => $now->format('d M Y, H:i:s') . ' WITA',
            'statusWaktu'  => $statusWaktu,
            'messageWaktu' => $messageWaktu,
            'jamSekarang'  => $now->format('H:i') . ' WITA',
            'jamKerja'     => $jamKerja,
        ]);
    }

    /**
     * Export ASN aktif per unit kerja ke file XLSX.
     * Endpoint: GET /monitoring-kakanwil/asn-aktif-export?key=...&tanggal=YYYY-MM-DD
     */
    public function exportExcel(Request $request)
    {
        $key = $request->query('key');
        if ($key !== config('app.kakanwil_monitor_key') || empty($key)) {
            abort(403, 'Akses ditolak.');
        }

        $wita         = now()->setTimezone('Asia/Makassar');
        $tanggalInput = $request->query('tanggal');
        $tanggal      = $tanggalInput
            ? \Carbon\Carbon::createFromFormat('Y-m-d', $tanggalInput)->startOfDay()
            : $wita->copy()->startOfDay();

        if ($tanggal->gt($wita->copy()->startOfDay())) {
            $tanggal = $wita->copy()->startOfDay();
        }

        $tanggalStr  = $tanggal->format('Y-m-d');
        $namaHari    = $tanggal->locale('id')->isoFormat('dddd, D MMMM YYYY');

        // Reuse logika query dari asnAktifDetail
        // tanggal bertipe DATE — where() langsung lebih efisien dari whereDate()
        $khPerUnit = DB::table('progres_harian as ph')
            ->join('users as u', 'ph.user_id', '=', 'u.id')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id as unit_id', DB::raw('COUNT(DISTINCT ph.user_id) as kh'))
            ->where('ph.tanggal', $tanggalStr)
            ->where('ph.tipe_progres', 'KINERJA_HARIAN')
            ->where('u.role', 'ASN')->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id')->pluck('kh', 'unit_id');

        $tlaPerUnit = DB::table('progres_harian as ph')
            ->join('users as u', 'ph.user_id', '=', 'u.id')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id as unit_id', DB::raw('COUNT(DISTINCT ph.user_id) as tla'))
            ->where('ph.tanggal', $tanggalStr)
            ->where('ph.tipe_progres', 'TUGAS_ATASAN')
            ->where('u.role', 'ASN')->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id')->pluck('tla', 'unit_id');

        $aktifPerUnit = DB::table(
            DB::table('progres_harian as ph')
                ->join('users as u', 'ph.user_id', '=', 'u.id')
                ->select('u.unit_kerja_id', 'ph.user_id')
                ->where('ph.tanggal', $tanggalStr)
                ->whereIn('ph.tipe_progres', ['KINERJA_HARIAN', 'TUGAS_ATASAN'])
                ->where('u.role', 'ASN')->where('u.status_pegawai', 'AKTIF'),
            'sub'
        )->select('unit_kerja_id', DB::raw('COUNT(DISTINCT user_id) as aktif'))
         ->groupBy('unit_kerja_id')->pluck('aktif', 'unit_kerja_id');

        $totalPerUnit = DB::table('users as u')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id as unit_id', 'uk.nama_unit', DB::raw('COUNT(u.id) as total'))
            ->where('u.role', 'ASN')->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id', 'uk.nama_unit')->orderBy('uk.nama_unit')->get();

        // Susun rows — semua unit (termasuk 0 aktif) untuk kelengkapan laporan
        $rows = [];
        foreach ($totalPerUnit as $row) {
            $uid    = $row->unit_id;
            $aktif  = (int) ($aktifPerUnit[$uid] ?? 0);
            $total  = (int) $row->total;
            $persen = $total > 0 ? round($aktif / $total * 100, 1) : 0;

            $rows[] = [
                'nama_unit' => $row->nama_unit,
                'total'     => $total,
                'aktif'     => $aktif,
                'persen'    => $persen,
                'kh'        => (int) ($khPerUnit[$uid]  ?? 0),
                'tla'       => (int) ($tlaPerUnit[$uid] ?? 0),
            ];
        }

        // Sort: aktif DESC, persen DESC, nama ASC
        usort($rows, function ($a, $b) {
            if ($b['aktif']  !== $a['aktif'])  return $b['aktif']  <=> $a['aktif'];
            if ($b['persen'] !== $a['persen']) return $b['persen'] <=> $a['persen'];
            return strcmp($a['nama_unit'], $b['nama_unit']);
        });

        $xlsx     = $this->buildXlsx($rows, $namaHari, $tanggalStr);
        $filename = 'ASN_Aktif_Harian_' . $tanggalStr . '.xlsx';

        return response($xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => strlen($xlsx),
        ]);
    }

    /**
     * Build file XLSX murni menggunakan ZipArchive + XML (tanpa library tambahan).
     */
    private function buildXlsx(array $rows, string $namaHari, string $tanggalStr): string
    {
        $escape = fn(string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        // Shared strings (untuk string cells lebih efisien)
        // Kita pakai inline string (t="inlineStr") supaya tidak perlu sharedStrings.xml
        $rowsXml = '';
        $rowNum  = 4; // Mulai dari baris 4 (baris 1–3 untuk judul/header)

        foreach ($rows as $i => $r) {
            $no     = $i + 1;
            $persen = number_format($r['persen'], 1) . '%';

            $rowsXml .= "<row r=\"{$rowNum}\">"
                . "<c r=\"A{$rowNum}\" t=\"n\"><v>{$no}</v></c>"
                . "<c r=\"B{$rowNum}\" t=\"inlineStr\"><is><t>{$escape($r['nama_unit'])}</t></is></c>"
                . "<c r=\"C{$rowNum}\" t=\"n\"><v>{$r['total']}</v></c>"
                . "<c r=\"D{$rowNum}\" t=\"n\"><v>{$r['aktif']}</v></c>"
                . "<c r=\"E{$rowNum}\" t=\"inlineStr\"><is><t>{$persen}</t></is></c>"
                . "<c r=\"F{$rowNum}\" t=\"n\"><v>{$r['kh']}</v></c>"
                . "<c r=\"G{$rowNum}\" t=\"n\"><v>{$r['tla']}</v></c>"
                . "</row>\n";
            $rowNum++;
        }

        $totalAktif = array_sum(array_column($rows, 'aktif'));
        $totalAsn   = array_sum(array_column($rows, 'total'));
        $totalKh    = array_sum(array_column($rows, 'kh'));
        $totalTla   = array_sum(array_column($rows, 'tla'));
        $pctTotal   = $totalAsn > 0 ? number_format(round($totalAktif / $totalAsn * 100, 1), 1) . '%' : '0%';

        // Baris total
        $rowsXml .= "<row r=\"{$rowNum}\">"
            . "<c r=\"A{$rowNum}\" t=\"inlineStr\"><is><t></t></is></c>"
            . "<c r=\"B{$rowNum}\" t=\"inlineStr\"><is><t>TOTAL</t></is></c>"
            . "<c r=\"C{$rowNum}\" t=\"n\"><v>{$totalAsn}</v></c>"
            . "<c r=\"D{$rowNum}\" t=\"n\"><v>{$totalAktif}</v></c>"
            . "<c r=\"E{$rowNum}\" t=\"inlineStr\"><is><t>{$pctTotal}</t></is></c>"
            . "<c r=\"F{$rowNum}\" t=\"n\"><v>{$totalKh}</v></c>"
            . "<c r=\"G{$rowNum}\" t=\"n\"><v>{$totalTla}</v></c>"
            . "</row>\n";

        $sheetXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>REKAP ASN AKTIF HARIAN eSARAku</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>Tanggal: {$escape($namaHari)}</t></is></c>
    </row>
    <row r="3">
      <c r="A3" t="inlineStr"><is><t>No</t></is></c>
      <c r="B3" t="inlineStr"><is><t>Unit Kerja</t></is></c>
      <c r="C3" t="inlineStr"><is><t>Total ASN</t></is></c>
      <c r="D3" t="inlineStr"><is><t>ASN Aktif</t></is></c>
      <c r="E3" t="inlineStr"><is><t>% Aktif</t></is></c>
      <c r="F3" t="inlineStr"><is><t>Kinerja Harian (KH)</t></is></c>
      <c r="G3" t="inlineStr"><is><t>Tugas Atasan (TLA)</t></is></c>
    </row>
    {$rowsXml}
  </sheetData>
</worksheet>
XML;

        $workbookXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="ASN Aktif Harian" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML;

        $workbookRels = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"
                Target="worksheets/sheet1.xml"/>
</Relationships>
XML;

        $contentTypes = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml"
            ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml"
            ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML;

        $packageRels = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"
                Target="xl/workbook.xml"/>
</Relationships>
XML;

        // Tulis ke temp file ZIP lalu baca kembali sebagai string
        $tmpFile = tempnam(sys_get_temp_dir(), 'esaraku_xlsx_');
        $zip     = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',            $contentTypes);
        $zip->addFromString('_rels/.rels',                   $packageRels);
        $zip->addFromString('xl/workbook.xml',               $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels',    $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml',      $sheetXml);
        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $content;
    }

    // =========================================================================
    // BUILD DATA
    // =========================================================================

    private function buildMonitoringData(int $tahun): array
    {
        $totalAsn        = $this->getTotalAsn();
        $totalAtasan     = $this->getTotalAtasan();
        $totalPegawai    = $totalAsn + $totalAtasan;

        $statusDist      = $this->getStatusDistribution($tahun);
        $sudahBuatSkp    = array_sum(array_values($statusDist));
        $sudahDiajukan   = ($statusDist['DIAJUKAN'] ?? 0) + ($statusDist['DISETUJUI'] ?? 0);
        $sudahDisetujui  = $statusDist['DISETUJUI'] ?? 0;

        // Basis belumBuat dan kepatuhan: totalPegawai (ASN+ATASAN aktif)
        // agar konsisten dengan kartu "Total Pegawai" yang ditampilkan di dashboard
        $belumBuat       = max(0, $totalPegawai - $sudahBuatSkp);

        $persenKepatuhan = $totalPegawai > 0
            ? round(($sudahDisetujui / $totalPegawai) * 100, 1)
            : 0;

        // Kepatuhan kinerja aktif — bulan berjalan
        $bulanBerjalan    = (int) now()->month;
        $kinerjaAktif     = $this->getKepatuhanKinerja($bulanBerjalan, $tahun);

        // Gap kepatuhan SKP vs Kinerja
        $gap = round($persenKepatuhan - $kinerjaAktif['persen'], 1);

        return [
            'kpi' => [
                'total_asn'          => $totalAsn,
                'sudah_buat_skp'     => $sudahBuatSkp,
                'sudah_diajukan'     => $sudahDiajukan,
                'sudah_disetujui'    => $sudahDisetujui,
                'belum_buat'         => $belumBuat,
                'persen_kepatuhan'   => $persenKepatuhan,
                'warna_kepatuhan'    => $this->warnaKepatuhan($persenKepatuhan),
                'kinerja_aktif'      => $kinerjaAktif,
                'gap_kepatuhan'      => $gap,
                // progress_hari, total_hari_kerja, asn_aktif_hari_ini
                // di-inject fresh dari index() — tidak di-cache
                'progress_hari'      => 0,
                'total_hari_kerja'   => 0,
                'asn_aktif_hari_ini' => ['asn_aktif' => 0, 'total_asn' => 0, 'persen' => 0],
            ],
            'status_distribution' => $statusDist,
            'per_unit'            => $this->getPerUnit($tahun),
            'ranking_top'         => $this->getRanking($tahun, 'top'),
            'ranking_bottom'      => $this->getRanking($tahun, 'bottom'),
            'tahun'               => $tahun,
        ];
    }

    // =========================================================================
    // QUERIES — hanya agregat, TIDAK ada nama/NIP individu
    // =========================================================================

    private function getTotalAsn(): int
    {
        return DB::table('users')
            ->where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->count();
    }

    private function getTotalAtasan(): int
    {
        return DB::table('users')
            ->where('role', 'ATASAN')
            ->where('status_pegawai', 'AKTIF')
            ->count();
    }

    /**
     * Status distribution SKP tahun ini — hanya pegawai aktif, user unik.
     * Return: ['DRAFT' => n, 'DIAJUKAN' => n, 'DISETUJUI' => n, 'DITOLAK' => n]
     *
     * Perbaikan:
     * - JOIN users agar SKP orphan dan pegawai nonaktif tidak ikut terhitung
     * - COUNT(DISTINCT s.user_id) agar tidak ada double-count per user
     * - Filter status_pegawai = AKTIF agar konsisten dengan basis total_pegawai
     */
    private function getStatusDistribution(int $tahun): array
    {
        $rows = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->where('s.tahun', $tahun)
            ->where('u.status_pegawai', 'AKTIF')
            ->select('s.status', DB::raw('COUNT(DISTINCT s.user_id) as total'))
            ->groupBy('s.status')
            ->pluck('total', 's.status')
            ->toArray();

        return array_merge(
            ['DRAFT' => 0, 'DIAJUKAN' => 0, 'DISETUJUI' => 0, 'DITOLAK' => 0],
            $rows
        );
    }

    /**
     * Data per unit kerja — agregat saja, tanpa nama individu.
     */
    private function getPerUnit(int $tahun): array
    {
        // Total ASN aktif per unit
        $asnPerUnit = DB::table('users as u')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id', 'uk.nama_unit', DB::raw('COUNT(u.id) as total_asn'))
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id', 'uk.nama_unit')
            ->get()
            ->keyBy('id');

        // SKP disetujui per unit
        $disetujuiPerUnit = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id', DB::raw('COUNT(s.id) as total_disetujui'))
            ->where('s.tahun', $tahun)
            ->where('s.status', 'DISETUJUI')
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id')
            ->get()
            ->keyBy('id');

        // SKP sudah buat (semua status) per unit — DISTINCT user_id agar 1 ASN tidak double-count
        $buatPerUnit = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id', DB::raw('COUNT(DISTINCT s.user_id) as total_buat'))
            ->where('s.tahun', $tahun)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id')
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($asnPerUnit as $unitId => $unit) {
            $totalAsn       = (int) $unit->total_asn;
            $totalDisetujui = (int) ($disetujuiPerUnit[$unitId]->total_disetujui ?? 0);
            $totalBuat      = (int) ($buatPerUnit[$unitId]->total_buat ?? 0);
            $persen         = $totalAsn > 0 ? round(($totalDisetujui / $totalAsn) * 100, 1) : 0;

            $result[] = [
                'unit_id'         => $unitId,           // dibutuhkan untuk drill-down AJAX
                'nama_unit'       => $unit->nama_unit,
                'total_asn'       => $totalAsn,
                'total_buat'      => $totalBuat,
                'total_disetujui' => $totalDisetujui,
                'belum_buat'      => max(0, $totalAsn - $totalBuat),
                'persen'          => $persen,
                'warna'           => $this->warnaKepatuhan($persen),
            ];
        }

        // Urutkan: kepatuhan DESC → total_asn DESC → nama_unit ASC
        usort($result, function ($a, $b) {
            if ($b['persen'] !== $a['persen']) {
                return $b['persen'] <=> $a['persen'];
            }
            if ($b['total_asn'] !== $a['total_asn']) {
                return $b['total_asn'] <=> $a['total_asn'];
            }
            return strcmp($a['nama_unit'], $b['nama_unit']);
        });

        return $result;
    }

    /**
     * Ranking top/bottom 5 unit berdasarkan persentase kepatuhan.
     */
    private function getRanking(int $tahun, string $type): array
    {
        $perUnit = $this->getPerUnit($tahun);

        // Hanya unit yang punya minimal 1 ASN
        $filtered = array_filter($perUnit, fn($u) => $u['total_asn'] > 0);

        // Sort: top = DESC, bottom = ASC
        usort($filtered, fn($a, $b) =>
            $type === 'top'
                ? $b['persen'] <=> $a['persen']
                : $a['persen'] <=> $b['persen']
        );

        return array_slice(array_values($filtered), 0, 5);
    }

    /**
     * Hitung kepatuhan kinerja aktif bulan tertentu.
     * ASN dianggap aktif jika input progres harian ≥ 10 hari dalam bulan tsb.
     *
     * Return: [
     *   'asn_aktif'  => int,   // jumlah ASN yang input ≥ 10 hari
     *   'total_asn'  => int,   // total ASN aktif
     *   'persen'     => float, // persentase
     *   'warna'      => string,
     *   'bulan'      => int,
     *   'tahun'      => int,
     * ]
     */
    private function getKepatuhanKinerja(int $bulan, int $tahun): array
    {
        $totalAsn = $this->getTotalAsn();

        // Hitung ASN yang input progres_harian ≥ 10 hari dalam bulan ini
        // Hanya ASN aktif (join ke users)
        $asnAktif = DB::table('progres_harian as ph')
            ->join('users as u', 'ph.user_id', '=', 'u.id')
            ->select('ph.user_id', DB::raw('COUNT(DISTINCT DATE(ph.tanggal)) as hari_input'))
            ->whereMonth('ph.tanggal', $bulan)
            ->whereYear('ph.tanggal', $tahun)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('ph.user_id')
            ->having('hari_input', '>=', 10)
            ->get()
            ->count();

        $persen = $totalAsn > 0 ? round(($asnAktif / $totalAsn) * 100, 1) : 0;

        return [
            'asn_aktif' => $asnAktif,
            'total_asn' => $totalAsn,
            'persen'    => $persen,
            'warna'     => $this->warnaKepatuhan($persen),
            'bulan'     => $bulan,
            'tahun'     => $tahun,
        ];
    }

    /**
     * Hitung jumlah hari kerja yang sudah berjalan di bulan ini.
     * Loop dari tanggal 1 s.d. hari ini, hitung yang isWorkingDay.
     */
    private function getProgressHariKerja(int $bulan, int $tahun): int
    {
        $today   = now()->startOfDay();
        $current = \Carbon\Carbon::create($tahun, $bulan, 1)->startOfDay();
        $count   = 0;

        while ($current->lte($today)) {
            if (\App\Helpers\HolidayHelper::isWorkingDay($current)) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Hitung jumlah ASN yang input progres_harian hari ini.
     * ASN dianggap aktif jika punya entri KH (KINERJA_HARIAN) ATAU TLA (TUGAS_ATASAN).
     * Keduanya ada di tabel progres_harian dengan kolom tipe_progres.
     *
     * Return: [
     *   'asn_aktif' => int,
     *   'total_asn' => int,
     *   'persen'    => float,
     *   'kh'        => int,  // jumlah ASN unik yang input KH
     *   'tla'       => int,  // jumlah ASN unik yang input TLA
     * ]
     */
    private function getAsnAktifHariIni(int $totalAsn): array
    {
        // KH — user unik yang input KINERJA_HARIAN hari ini
        // tanggal bertipe DATE — where() langsung lebih efisien dari whereDate()
        $todayStr = now()->setTimezone('Asia/Makassar')->toDateString();
        $khIds = DB::table('progres_harian')
            ->where('tanggal', $todayStr)
            ->where('tipe_progres', 'KINERJA_HARIAN')
            ->distinct()
            ->pluck('user_id');

        // TLA — user unik yang input TUGAS_ATASAN hari ini
        $tlaIds = DB::table('progres_harian')
            ->where('tanggal', $todayStr)
            ->where('tipe_progres', 'TUGAS_ATASAN')
            ->distinct()
            ->pluck('user_id');

        // Union: ASN aktif = KH ATAU TLA (tidak double-count)
        $jumlahAktif = $khIds->merge($tlaIds)->unique()->count();

        $persen = $totalAsn > 0 ? round(($jumlahAktif / $totalAsn) * 100, 1) : 0;

        return [
            'asn_aktif' => $jumlahAktif,
            'total_asn' => $totalAsn,
            'persen'    => $persen,
            'kh'        => $khIds->count(),
            'tla'       => $tlaIds->count(),
        ];
    }

    /**
     * Breakdown keaktifan ASN bulan berjalan (tanggal 1 s.d. hari ini).
     *
     * Kategori:
     *   🔴 belum_input  — 0 hari input bulan ini
     *   🟡 kurang_aktif — input 1–2 hari
     *   🟢 aktif        — input ≥ 3 hari (tidak masuk card "kurang aktif")
     *
     * Invariant: belum_input + kurang_aktif + aktif = total ASN
     */
    private function getBreakdownMingguan(): array
    {
        $bulan = (int) now()->month;
        $tahun = (int) now()->year;

        // 🔴 Belum input sama sekali bulan ini (left join → NULL)
        $belumInput = DB::table('users as u')
            ->leftJoin('progres_harian as ph', function ($join) use ($bulan, $tahun) {
                $join->on('u.id', '=', 'ph.user_id')
                     ->whereMonth('ph.tanggal', $bulan)
                     ->whereYear('ph.tanggal', $tahun);
            })
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->whereNull('ph.user_id')
            ->count();

        // 🟡 Input 1–2 hari (kurang aktif)
        $kurangAktif = DB::table('progres_harian as ph')
            ->join('users as u', 'ph.user_id', '=', 'u.id')
            ->select('ph.user_id', DB::raw('COUNT(DISTINCT DATE(ph.tanggal)) as hari_input'))
            ->whereMonth('ph.tanggal', $bulan)
            ->whereYear('ph.tanggal', $tahun)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('ph.user_id')
            ->having('hari_input', '<', 3)
            ->get()
            ->count();

        // 🟢 Aktif ≥ 3 hari
        $aktif = DB::table('progres_harian as ph')
            ->join('users as u', 'ph.user_id', '=', 'u.id')
            ->select('ph.user_id', DB::raw('COUNT(DISTINCT DATE(ph.tanggal)) as hari_input'))
            ->whereMonth('ph.tanggal', $bulan)
            ->whereYear('ph.tanggal', $tahun)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('ph.user_id')
            ->having('hari_input', '>=', 3)
            ->get()
            ->count();

        return [
            'belum_input'  => $belumInput,
            'kurang_aktif' => $kurangAktif,
            'aktif'        => $aktif,
            'total_belum'  => $belumInput + $kurangAktif,
            'periode'      => '1 – ' . now()->format('d') . ' ' . now()->locale('id')->isoFormat('MMMM YYYY'),
        ];
    }

    /**
     * AJAX — Detail ASN aktif hari ini per unit kerja.
     * Endpoint: GET /monitoring/asn-aktif-hari-ini-detail?key=...
     */
    public function asnAktifDetail(Request $request)
    {
        $key = $request->query('key');
        if ($key !== config('app.kakanwil_monitor_key') || empty($key)) {
            abort(403, 'Akses ditolak.');
        }

        // Tanggal dari parameter, default hari ini (WITA)
        $wita        = now()->setTimezone('Asia/Makassar');
        $tanggalInput = $request->query('tanggal');
        $tanggal      = $tanggalInput
            ? \Carbon\Carbon::createFromFormat('Y-m-d', $tanggalInput)->startOfDay()
            : $wita->copy()->startOfDay();

        // Batas: tidak boleh lebih dari hari ini WITA, tidak lebih dari 90 hari ke belakang
        $today = $wita->copy()->startOfDay();
        if ($tanggal->gt($today)) {
            $tanggal = $today;
        }

        $tanggalStr = $tanggal->format('Y-m-d');

        // tanggal bertipe DATE — where() langsung lebih efisien dari whereDate()
        // KH per unit — 1 query agregat
        $khPerUnit = DB::table('progres_harian as ph')
            ->join('users as u', 'ph.user_id', '=', 'u.id')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id as unit_id', DB::raw('COUNT(DISTINCT ph.user_id) as kh'))
            ->where('ph.tanggal', $tanggalStr)
            ->where('ph.tipe_progres', 'KINERJA_HARIAN')
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id')
            ->pluck('kh', 'unit_id');

        // TLA per unit — 1 query agregat
        $tlaPerUnit = DB::table('progres_harian as ph')
            ->join('users as u', 'ph.user_id', '=', 'u.id')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id as unit_id', DB::raw('COUNT(DISTINCT ph.user_id) as tla'))
            ->where('ph.tanggal', $tanggalStr)
            ->where('ph.tipe_progres', 'TUGAS_ATASAN')
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id')
            ->pluck('tla', 'unit_id');

        // Union KH+TLA per unit — 1 query dengan UNION untuk hitung distinct tanpa double-count
        $aktifPerUnit = DB::table(
            DB::table('progres_harian as ph')
                ->join('users as u', 'ph.user_id', '=', 'u.id')
                ->select('u.unit_kerja_id', 'ph.user_id')
                ->where('ph.tanggal', $tanggalStr)
                ->whereIn('ph.tipe_progres', ['KINERJA_HARIAN', 'TUGAS_ATASAN'])
                ->where('u.role', 'ASN')
                ->where('u.status_pegawai', 'AKTIF'),
            'sub'
        )
        ->select('unit_kerja_id', DB::raw('COUNT(DISTINCT user_id) as aktif'))
        ->groupBy('unit_kerja_id')
        ->pluck('aktif', 'unit_kerja_id');

        // Total ASN per unit
        $totalPerUnit = DB::table('users as u')
            ->join('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
            ->select('uk.id as unit_id', 'uk.nama_unit', DB::raw('COUNT(u.id) as total'))
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->groupBy('uk.id', 'uk.nama_unit')
            ->get();

        // Gabungkan — hanya unit yang ada aktivitas
        $result = [];
        foreach ($totalPerUnit as $row) {
            $uid   = $row->unit_id;
            $aktif = (int) ($aktifPerUnit[$uid] ?? 0);
            if ($aktif === 0) continue;

            $total  = (int) $row->total;
            $persen = $total > 0 ? round($aktif / $total * 100, 1) : 0;

            $result[] = [
                'nama_unit' => $row->nama_unit,
                'aktif'     => $aktif,
                'total'     => $total,
                'persen'    => $persen,
                'kh'        => (int) ($khPerUnit[$uid]  ?? 0),
                'tla'       => (int) ($tlaPerUnit[$uid] ?? 0),
            ];
        }

        // Sort: persen DESC, aktif DESC, nama ASC
        usort($result, function ($a, $b) {
            if ($b['persen'] !== $a['persen']) return $b['persen'] <=> $a['persen'];
            if ($b['aktif']  !== $a['aktif'])  return $b['aktif']  <=> $a['aktif'];
            return strcmp($a['nama_unit'], $b['nama_unit']);
        });

        $isHariIni   = $tanggal->isSameDay($today);
        $namaHari    = $tanggal->locale('id')->isoFormat('dddd, D MMMM YYYY');

        return response()->json([
            'data'        => $result,
            'total_unit'  => count($result),
            'tanggal'     => $namaHari,
            'tanggal_val' => $tanggalStr,
            'is_hari_ini' => $isHariIni,
        ]);
    }

    /**
     * AJAX — Daftar ASN belum mengisi kinerja harian per unit kerja.
     * ASN dianggap SUDAH mengisi jika punya ≥1 entri KH atau TLA hari ini.
     * Hanya ASN yang hari ini merupakan hari kerja mereka yang dihitung.
     * Endpoint: GET /monitoring-kakanwil/asn-belum-isi?key=...&tanggal=YYYY-MM-DD
     */
    public function asnBelumIsiPerUnit(Request $request)
    {
        $key = $request->query('key');
        if ($key !== config('app.kakanwil_monitor_key') || empty($key)) {
            abort(403, 'Akses ditolak.');
        }

        $wita         = now()->setTimezone('Asia/Makassar');
        $tanggalInput = $request->query('tanggal');
        $tanggal      = $tanggalInput
            ? \Carbon\Carbon::createFromFormat('Y-m-d', $tanggalInput)->startOfDay()
            : $wita->copy()->startOfDay();

        if ($tanggal->gt($wita->copy()->startOfDay())) {
            $tanggal = $wita->copy()->startOfDay();
        }

        $tanggalStr = $tanggal->format('Y-m-d');

        // ── Query 1: Semua ASN aktif + unit kerja (eager load via join) ───────
        // NOTE: Semua role ATASAN saat ini dianggap wajib input harian.
        // Jika ke depan perlu pengecualian (misal Kakanwil),
        // gunakan flag khusus di tabel users (misal: wajib_input_harian).
        $semuaAsn = \App\Models\User::with('unitKerja')
            ->whereIn('role', ['ASN', 'ATASAN'])
            ->where('status_pegawai', 'AKTIF')
            ->whereNotNull('unit_kerja_id')
            ->get(['id', 'name', 'nip', 'jabatan', 'unit_kerja_id', 'hari_kerja']);

        // ── Query 2: user_id yang sudah mengisi KH atau TLA pada tanggal tsb ──
        // tanggal bertipe DATE — where() langsung lebih efisien dari whereDate()
        $sudahIsiIds = DB::table('progres_harian as ph')
            ->join('users as u', 'ph.user_id', '=', 'u.id')
            ->where('ph.tanggal', $tanggalStr)
            ->whereIn('ph.tipe_progres', ['KINERJA_HARIAN', 'TUGAS_ATASAN'])
            ->whereIn('u.role', ['ASN', 'ATASAN'])
            ->where('u.status_pegawai', 'AKTIF')
            ->distinct()
            ->pluck('ph.user_id')
            ->flip(); // jadikan key untuk O(1) lookup

        // Pre-load tanggal libur khusus Guru untuk semua unit yang ada — batch query, no N+1
        $liburKhususService = new LiburKhususService();
        $allUnitIds         = $semuaAsn->pluck('unit_kerja_id')->unique()->filter()->toArray();
        $tanggalLiburGuru   = $liburKhususService->getTanggalLiburGuruBulanan(
            $allUnitIds,
            (int) $tanggal->format('m'),
            (int) $tanggal->format('Y')
        );
        $isLiburKhususHariIni = isset($tanggalLiburGuru[$tanggalStr]);

        // ── PHP: filter & group by unit ──────────────────────────────────────
        $perUnit = [];

        foreach ($semuaAsn as $asn) {
            // Hanya hitung ASN yang hari ini adalah hari kerja mereka
            if (! \App\Helpers\HolidayHelper::isWorkingDay($tanggal, $asn)) {
                continue;
            }

            // Guru pada hari libur khusus tidak diwajibkan input — skip dari hitungan
            if ($isLiburKhususHariIni && $liburKhususService->isGuru($asn)) {
                continue;
            }

            $unitId   = $asn->unit_kerja_id;
            $unitNama = $asn->unitKerja?->nama_unit ?? 'Tanpa Unit';

            if (! isset($perUnit[$unitId])) {
                $perUnit[$unitId] = [
                    'unit_id'   => $unitId,
                    'nama_unit' => $unitNama,
                    'total'     => 0,
                    'belum'     => [],
                ];
            }

            $perUnit[$unitId]['total']++;

            if (! isset($sudahIsiIds[$asn->id])) {
                $perUnit[$unitId]['belum'][] = [
                    'id'     => $asn->id,
                    'nama'   => $asn->name,
                    'nip'    => $asn->nip ?? '-',
                    'jabatan'=> $asn->jabatan ?? '-',
                ];
            }
        }

        // ── Format output ─────────────────────────────────────────────────────
        $result = [];
        foreach ($perUnit as $row) {
            $jumlahBelum = count($row['belum']);
            $result[] = [
                'unit_id'       => $row['unit_id'],
                'nama_unit'     => $row['nama_unit'],
                'total'         => $row['total'],
                'sudah'         => $row['total'] - $jumlahBelum,
                'belum'         => $jumlahBelum,
                'persen_sudah'  => $row['total'] > 0
                                    ? round(($row['total'] - $jumlahBelum) / $row['total'] * 100, 1)
                                    : 0,
                'semua_isi'     => $jumlahBelum === 0,
                'daftar_belum'  => $row['belum'],
            ];
        }

        // Sort: unit bermasalah di atas → jumlah belum DESC → nama A-Z
        usort($result, function ($a, $b) {
            if (($a['belum'] > 0) !== ($b['belum'] > 0)) {
                return ($b['belum'] > 0) <=> ($a['belum'] > 0);
            }
            if ($b['belum'] !== $a['belum']) {
                return $b['belum'] <=> $a['belum'];
            }
            return strcasecmp($a['nama_unit'], $b['nama_unit']);
        });

        $totalBelum = array_sum(array_column($result, 'belum'));
        $totalAsn   = array_sum(array_column($result, 'total'));

        return response()->json([
            'tanggal'     => $tanggal->locale('id')->isoFormat('dddd, D MMMM YYYY'),
            'tanggal_val' => $tanggalStr,
            'total_asn'   => $totalAsn,
            'total_belum' => $totalBelum,
            'total_sudah' => $totalAsn - $totalBelum,
            'data'        => $result,
        ]);
    }

    /**
     * AJAX — Daftar nama ASN per unit untuk kolom "Belum Buat" atau "Disetujui".
     * Endpoint: GET /monitoring-kakanwil/skp-detail?key=...&unit_id=X&tipe=belum_buat|disetujui&tahun=Y
     */
    public function skpDetail(Request $request)
    {
        $key = $request->query('key');
        if ($key !== config('app.kakanwil_monitor_key') || empty($key)) {
            abort(403, 'Akses ditolak.');
        }

        $unitId = (int) $request->query('unit_id');
        $tipe   = $request->query('tipe'); // 'belum_buat' atau 'disetujui'
        $tahun  = (int) $request->query('tahun', now()->year);

        if (! in_array($tipe, ['belum_buat', 'disetujui', 'belum_disetujui'], true) || $unitId <= 0) {
            return response()->json(['error' => 'Parameter tidak valid.'], 422);
        }

        // Nama unit kerja
        $namaUnit = DB::table('unit_kerja')->where('id', $unitId)->value('nama_unit') ?? 'Unit tidak ditemukan';

        if ($tipe === 'belum_buat') {
            // ASN aktif di unit ini yang TIDAK punya baris skp_tahunan tahun ini
            $rows = DB::table('users as u')
                ->leftJoin('skp_tahunan as s', function ($join) use ($tahun) {
                    $join->on('s.user_id', '=', 'u.id')
                         ->where('s.tahun', $tahun);
                })
                ->select('u.name', 'u.nip', 'u.jabatan')
                ->where('u.unit_kerja_id', $unitId)
                ->where('u.role', 'ASN')
                ->where('u.status_pegawai', 'AKTIF')
                ->whereNull('s.id')
                ->orderBy('u.name')
                ->get();

            $label = 'Belum Buat SKP';

            return response()->json([
                'unit_id'   => $unitId,
                'nama_unit' => $namaUnit,
                'tahun'     => $tahun,
                'tipe'      => $tipe,
                'label'     => $label,
                'total'     => $rows->count(),
                'data'      => $rows->map(fn($r) => [
                    'nama'    => $r->name,
                    'nip'     => $r->nip    ?? '-',
                    'jabatan' => $r->jabatan ?? '-',
                ])->values(),
            ]);
        }

        // tipe === 'disetujui' atau 'belum_disetujui'
        // Keduanya dipanggil paralel dari frontend (Promise.all) saat klik kolom Disetujui

        if ($tipe === 'disetujui') {
            $rows = DB::table('skp_tahunan as s')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->select('u.name', 'u.nip', 'u.jabatan')
                ->where('u.unit_kerja_id', $unitId)
                ->where('u.role', 'ASN')
                ->where('u.status_pegawai', 'AKTIF')
                ->where('s.tahun', $tahun)
                ->where('s.status', 'DISETUJUI')
                ->orderBy('u.name')
                ->get();

            $label = 'SKP Disetujui';

            return response()->json([
                'unit_id'   => $unitId,
                'nama_unit' => $namaUnit,
                'tahun'     => $tahun,
                'tipe'      => $tipe,
                'label'     => $label,
                'total'     => $rows->count(),
                'data'      => $rows->map(fn($r) => [
                    'nama'    => $r->name,
                    'nip'     => $r->nip    ?? '-',
                    'jabatan' => $r->jabatan ?? '-',
                ])->values(),
            ]);
        }

        // tipe === 'belum_disetujui'
        // ASN aktif di unit ini yang SUDAH buat SKP tapi statusnya bukan DISETUJUI
        $rows = DB::table('skp_tahunan as s')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->select('u.name', 'u.nip', 'u.jabatan', 's.status')
            ->where('u.unit_kerja_id', $unitId)
            ->where('u.role', 'ASN')
            ->where('u.status_pegawai', 'AKTIF')
            ->where('s.tahun', $tahun)
            ->whereIn('s.status', ['DRAFT', 'DIAJUKAN', 'DITOLAK', 'REVISI_DIAJUKAN'])
            ->orderBy('s.status')
            ->orderBy('u.name')
            ->get();

        $label = 'Belum Disetujui';

        return response()->json([
            'unit_id'   => $unitId,
            'nama_unit' => $namaUnit,
            'tahun'     => $tahun,
            'tipe'      => $tipe,
            'label'     => $label,
            'total'     => $rows->count(),
            'data'      => $rows->map(fn($r) => [
                'nama'    => $r->name,
                'nip'     => $r->nip    ?? '-',
                'jabatan' => $r->jabatan ?? '-',
                'status'  => $r->status,
            ])->values(),
        ]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function warnaKepatuhan(float $persen): string
    {
        if ($persen >= 85) return 'hijau';
        if ($persen >= 70) return 'kuning';
        return 'merah';
    }
}
