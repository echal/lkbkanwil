<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Survei;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SurveiMonitoringController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'ADMIN') {
            abort(403);
        }

        // Semua survei untuk panel toggle (bukan hanya AKTIF)
        $semuaSurvei = Survei::orderByRaw("FIELD(status,'AKTIF','DRAFT','TUTUP')")->latest()->get();
        $survei      = $semuaSurvei->firstWhere('status', 'AKTIF');

        if (!$survei) {
            return view('admin.survei.index', [
                'survei'      => null,
                'semuaSurvei' => $semuaSurvei,
            ]);
        }

        // ── Query 1: semua pegawai aktif (ASN + ATASAN) beserta unit kerja ──
        $semuaPegawai = DB::table('users')
            ->select('id', 'name', 'nip', 'jabatan', 'unit_kerja_id')
            ->whereIn('role', ['ASN', 'ATASAN'])
            ->where('status_pegawai', 'AKTIF')
            ->get();

        $totalPegawai = $semuaPegawai->count();

        // ── Query 2: semua yang sudah isi survei ini ──
        $sudahIsiIds = DB::table('survei_jawaban')
            ->where('survei_id', $survei->id)
            ->pluck('user_id')
            ->flip(); // O(1) lookup

        $totalSudah = $sudahIsiIds->count();
        $totalBelum = $totalPegawai - $totalSudah;
        $persen     = $totalPegawai > 0
            ? round($totalSudah / $totalPegawai * 100, 1)
            : 0;

        // ── Query 3: rata-rata q1–q9 ──
        $avgRow = DB::table('survei_jawaban')
            ->where('survei_id', $survei->id)
            ->selectRaw('
                ROUND(AVG(q1),2) as avg1, ROUND(AVG(q2),2) as avg2,
                ROUND(AVG(q3),2) as avg3, ROUND(AVG(q4),2) as avg4,
                ROUND(AVG(q5),2) as avg5, ROUND(AVG(q6),2) as avg6,
                ROUND(AVG(q7),2) as avg7, ROUND(AVG(q8),2) as avg8,
                ROUND(AVG(q9),2) as avg9
            ')
            ->first();

        // ── Query 4: nama unit kerja (sekali, pakai pluck) ──
        $unitKerjaMap = DB::table('unit_kerja')
            ->pluck('nama_unit', 'id'); // [id => nama_unit]

        // ── Grouping per unit di PHP — tanpa query dalam loop ──
        $perUnit = $semuaPegawai->groupBy('unit_kerja_id');

        $tabelUnit = [];
        foreach ($perUnit as $unitId => $pegawaiUnit) {
            $totalUnit  = $pegawaiUnit->count();
            $belumUnit  = $pegawaiUnit->filter(fn($p) => !isset($sudahIsiIds[$p->id]));
            $sudahUnit  = $totalUnit - $belumUnit->count();
            $persenUnit = $totalUnit > 0 ? round($sudahUnit / $totalUnit * 100, 1) : 0;

            $tabelUnit[] = [
                'unit_kerja_id' => $unitId,
                'nama_unit'     => $unitKerjaMap[$unitId] ?? 'Tanpa Unit Kerja',
                'total'         => $totalUnit,
                'sudah'         => $sudahUnit,
                'belum'         => $belumUnit->count(),
                'persen'        => $persenUnit,
                'list_belum'    => $belumUnit->values()->map(fn($p) => [
                    'name'    => $p->name,
                    'nip'     => $p->nip ?? '-',
                    'jabatan' => $p->jabatan ?? '-',
                ])->toArray(),
            ];
        }

        usort($tabelUnit, fn($a, $b) => $b['belum'] <=> $a['belum']);

        // ── Query 5: 30 saran terbaru — LIMIT di DB-level, bukan PHP ──
        $saranTerbaru = DB::table('survei_jawaban as sj')
            ->select(
                'sj.id',
                'sj.saran',
                'sj.submitted_at',
                'u.name as nama_asn',
                'u.nip',
                'u.jabatan',
                'uk.nama_unit'
            )
            ->join('users as u', 'u.id', '=', 'sj.user_id')
            ->leftJoin('unit_kerja as uk', 'uk.id', '=', 'sj.unit_kerja_id')
            ->where('sj.survei_id', $survei->id)
            ->whereNotNull('sj.saran')
            ->where('sj.saran', '!=', '')
            ->orderBy('sj.id', 'desc')
            ->limit(30)
            ->get();

        return view('admin.survei.index', compact(
            'survei',
            'semuaSurvei',
            'totalPegawai',
            'totalSudah',
            'totalBelum',
            'persen',
            'avgRow',
            'tabelUnit',
            'saranTerbaru'
        ));
    }

    public function aktifkan(int $id)
    {
        if (Auth::user()->role !== 'ADMIN') {
            abort(403);
        }

        $survei = Survei::findOrFail($id);

        if ($survei->status === 'AKTIF') {
            return redirect()->route('admin.survei.index')
                ->with('info', 'Survei "' . $survei->judul . '" sudah berstatus AKTIF.');
        }

        DB::transaction(function () use ($survei) {
            // Tutup semua survei lain yang sedang AKTIF
            Survei::where('status', 'AKTIF')->update(['status' => 'TUTUP']);

            // Aktifkan survei yang dipilih
            $survei->update(['status' => 'AKTIF']);
        });

        return redirect()->route('admin.survei.index')
            ->with('success', 'Survei "' . $survei->judul . '" berhasil diaktifkan. Popup akan tampil ke ASN & ATASAN.');
    }

    public function tutup(int $id)
    {
        if (Auth::user()->role !== 'ADMIN') {
            abort(403);
        }

        $survei = Survei::findOrFail($id);

        if ($survei->status === 'TUTUP') {
            return redirect()->route('admin.survei.index')
                ->with('info', 'Survei "' . $survei->judul . '" sudah berstatus TUTUP.');
        }

        $survei->update(['status' => 'TUTUP']);

        return redirect()->route('admin.survei.index')
            ->with('success', 'Survei "' . $survei->judul . '" berhasil ditutup. Popup tidak akan tampil lagi.');
    }

    public function export()
    {
        if (Auth::user()->role !== 'ADMIN') {
            abort(403);
        }

        $survei = Survei::aktif()->latest()->first();

        if (!$survei) {
            return redirect()->route('admin.survei.index')
                ->with('info', 'Tidak ada survei aktif untuk diekspor.');
        }

        $semuaPegawai = DB::table('users')
            ->select('id', 'name', 'nip', 'jabatan', 'unit_kerja_id')
            ->whereIn('role', ['ASN', 'ATASAN'])
            ->where('status_pegawai', 'AKTIF')
            ->get();

        $sudahIsiIds = DB::table('survei_jawaban')
            ->where('survei_id', $survei->id)
            ->pluck('user_id')
            ->flip();

        $unitKerjaMap = DB::table('unit_kerja')->pluck('nama_unit', 'id');

        $perUnit = $semuaPegawai->groupBy('unit_kerja_id');

        $rows = [];
        foreach ($perUnit as $unitId => $pegawaiUnit) {
            $totalUnit  = $pegawaiUnit->count();
            $belumUnit  = $pegawaiUnit->filter(fn($p) => !isset($sudahIsiIds[$p->id]));
            $sudahUnit  = $totalUnit - $belumUnit->count();
            $persenUnit = $totalUnit > 0 ? round($sudahUnit / $totalUnit * 100, 1) : 0;
            $namaBelum  = $belumUnit->map(fn($p) => $p->name)->join(', ');

            $rows[] = [
                'nama_unit'  => $unitKerjaMap[$unitId] ?? 'Tanpa Unit Kerja',
                'total'      => $totalUnit,
                'sudah'      => $sudahUnit,
                'belum'      => $belumUnit->count(),
                'persen'     => $persenUnit,
                'nama_belum' => $namaBelum,
            ];
        }

        usort($rows, fn($a, $b) => $b['belum'] <=> $a['belum']);

        $namaFile    = 'monitoring-survei-' . now()->format('Ymd') . '.xlsx';
        $xlsxContent = $this->buildXlsx($rows, $survei->judul, $survei->periode);

        return response($xlsxContent)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="' . $namaFile . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    public function exportSaran()
    {
        if (Auth::user()->role !== 'ADMIN') {
            abort(403);
        }

        $survei = Survei::aktif()->latest()->first();

        if (!$survei) {
            return redirect()->route('admin.survei.index')
                ->with('info', 'Tidak ada survei aktif untuk diekspor.');
        }

        // Semua saran tanpa LIMIT — select minimal, ORDER BY id DESC
        $semuaSaran = DB::table('survei_jawaban as sj')
            ->select(
                'sj.id',
                'sj.saran',
                'sj.submitted_at',
                'u.name as nama_asn',
                'u.nip',
                'u.jabatan',
                'uk.nama_unit'
            )
            ->join('users as u', 'u.id', '=', 'sj.user_id')
            ->leftJoin('unit_kerja as uk', 'uk.id', '=', 'sj.unit_kerja_id')
            ->where('sj.survei_id', $survei->id)
            ->whereNotNull('sj.saran')
            ->where('sj.saran', '!=', '')
            ->orderBy('sj.id', 'desc')
            ->get();

        $namaFile    = 'saran-survei-' . now()->format('Ymd') . '.xlsx';
        $xlsxContent = $this->buildXlsxSaran($semuaSaran->all(), $survei->judul, $survei->periode);

        return response($xlsxContent)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="' . $namaFile . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    public function exportDetail()
    {
        if (Auth::user()->role !== 'ADMIN') {
            abort(403);
        }

        $survei = Survei::aktif()->latest()->first();

        if (!$survei) {
            return redirect()->route('admin.survei.index')
                ->with('info', 'Tidak ada survei aktif untuk diekspor.');
        }

        // 1 query JOIN tunggal — tidak ada query dalam loop, tidak ada N+1
        $rows = DB::table('survei_jawaban as sj')
            ->select(
                'sj.id',
                'u.name as nama_asn',
                'u.nip',
                'u.jabatan',
                'uk.nama_unit',
                'sj.q1', 'sj.q2', 'sj.q3', 'sj.q4', 'sj.q5',
                'sj.q6', 'sj.q7', 'sj.q8', 'sj.q9',
                DB::raw('(COALESCE(sj.q1,0)+COALESCE(sj.q2,0)+COALESCE(sj.q3,0)+COALESCE(sj.q4,0)+COALESCE(sj.q5,0)+COALESCE(sj.q6,0)+COALESCE(sj.q7,0)+COALESCE(sj.q8,0)+COALESCE(sj.q9,0)) as total_nilai'),
                'sj.saran',
                'sj.submitted_at'
            )
            ->join('users as u', 'u.id', '=', 'sj.user_id')
            ->leftJoin('unit_kerja as uk', 'uk.id', '=', 'sj.unit_kerja_id')
            ->where('sj.survei_id', $survei->id)
            ->orderBy('uk.nama_unit')
            ->orderBy('u.name')
            ->get()
            ->all();

        $namaFile    = 'detail-jawaban-survei-' . now()->format('Ymd') . '.xlsx';
        $xlsxContent = $this->buildXlsxDetail($rows, $survei->judul, $survei->periode);

        return response($xlsxContent)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="' . $namaFile . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    private function buildXlsxDetail(array $rows, string $judulSurvei, string $periode): string
    {
        $esc = fn(string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $escSaran = function (string $v) use ($esc): string {
            $v = str_replace(["\r\n", "\r", "\n"], '&#10;', $v);
            return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        };

        $rowsXml = '';
        $rowNum  = 4;

        foreach ($rows as $i => $r) {
            $no       = $i + 1;
            $namaAsn  = $esc($r->nama_asn  ?? '-');
            $nip      = $esc($r->nip       ?? '-');
            $jabatan  = $esc($r->jabatan   ?? '-');
            $namaUnit = $esc($r->nama_unit ?? 'Tanpa Unit Kerja');
            $saran    = $escSaran($r->saran ?? '');
            $tgl      = $r->submitted_at
                ? date('d/m/Y H:i', strtotime($r->submitted_at))
                : '-';

            // Hitung rata-rata hanya dari Q yang tidak null
            $qValues  = array_filter(
                [$r->q1, $r->q2, $r->q3, $r->q4, $r->q5, $r->q6, $r->q7, $r->q8, $r->q9],
                fn($v) => $v !== null
            );
            $rataRata = count($qValues) > 0
                ? number_format(round(array_sum($qValues) / count($qValues), 2), 2)
                : '-';

            // Q1–Q9 tampilkan angka atau dash jika null
            $qCell = function (string $col, int $rowN, $val) use ($esc): string {
                if ($val === null) {
                    return "<c r=\"{$col}{$rowN}\" t=\"inlineStr\"><is><t>-</t></is></c>";
                }
                return "<c r=\"{$col}{$rowN}\" t=\"n\"><v>{$val}</v></c>";
            };

            $rowsXml .= "<row r=\"{$rowNum}\">"
                . "<c r=\"A{$rowNum}\" t=\"n\"><v>{$no}</v></c>"
                . "<c r=\"B{$rowNum}\" t=\"inlineStr\"><is><t>{$namaAsn}</t></is></c>"
                . "<c r=\"C{$rowNum}\" t=\"inlineStr\"><is><t>{$nip}</t></is></c>"
                . "<c r=\"D{$rowNum}\" t=\"inlineStr\"><is><t>{$jabatan}</t></is></c>"
                . "<c r=\"E{$rowNum}\" t=\"inlineStr\"><is><t>{$namaUnit}</t></is></c>"
                . $qCell('F', $rowNum, $r->q1)
                . $qCell('G', $rowNum, $r->q2)
                . $qCell('H', $rowNum, $r->q3)
                . $qCell('I', $rowNum, $r->q4)
                . $qCell('J', $rowNum, $r->q5)
                . $qCell('K', $rowNum, $r->q6)
                . $qCell('L', $rowNum, $r->q7)
                . $qCell('M', $rowNum, $r->q8)
                . $qCell('N', $rowNum, $r->q9)
                . "<c r=\"O{$rowNum}\" t=\"n\"><v>{$r->total_nilai}</v></c>"
                . "<c r=\"P{$rowNum}\" t=\"inlineStr\"><is><t>{$esc($rataRata)}</t></is></c>"
                . "<c r=\"Q{$rowNum}\" t=\"inlineStr\"><is><t>{$saran}</t></is></c>"
                . "<c r=\"R{$rowNum}\" t=\"inlineStr\"><is><t>{$esc($tgl)}</t></is></c>"
                . "</row>\n";
            $rowNum++;
        }

        if (empty($rows)) {
            $rowsXml .= "<row r=\"4\"><c r=\"A4\" t=\"inlineStr\"><is><t>Belum ada jawaban yang masuk.</t></is></c></row>\n";
        }

        $judulEsc   = $esc($judulSurvei);
        $periodeEsc = $esc($periode);
        $tglExport  = $esc(now()->format('d/m/Y H:i'));

        $sheetXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>DETAIL JAWABAN SURVEI ESARAKU</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>Survei: {$judulEsc} | Periode: {$periodeEsc} | Diekspor: {$tglExport}</t></is></c>
    </row>
    <row r="3">
      <c r="A3" t="inlineStr"><is><t>No</t></is></c>
      <c r="B3" t="inlineStr"><is><t>Nama ASN</t></is></c>
      <c r="C3" t="inlineStr"><is><t>NIP</t></is></c>
      <c r="D3" t="inlineStr"><is><t>Jabatan</t></is></c>
      <c r="E3" t="inlineStr"><is><t>Unit Kerja</t></is></c>
      <c r="F3" t="inlineStr"><is><t>Q1</t></is></c>
      <c r="G3" t="inlineStr"><is><t>Q2</t></is></c>
      <c r="H3" t="inlineStr"><is><t>Q3</t></is></c>
      <c r="I3" t="inlineStr"><is><t>Q4</t></is></c>
      <c r="J3" t="inlineStr"><is><t>Q5</t></is></c>
      <c r="K3" t="inlineStr"><is><t>Q6</t></is></c>
      <c r="L3" t="inlineStr"><is><t>Q7</t></is></c>
      <c r="M3" t="inlineStr"><is><t>Q8</t></is></c>
      <c r="N3" t="inlineStr"><is><t>Q9</t></is></c>
      <c r="O3" t="inlineStr"><is><t>Total Nilai</t></is></c>
      <c r="P3" t="inlineStr"><is><t>Rata-rata</t></is></c>
      <c r="Q3" t="inlineStr"><is><t>Saran</t></is></c>
      <c r="R3" t="inlineStr"><is><t>Tanggal Submit</t></is></c>
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
    <sheet name="Detail Jawaban ASN" sheetId="1" r:id="rId1"/>
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

        $tmpFile = tempnam(sys_get_temp_dir(), 'esaraku_detail_xlsx_');
        $zip     = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',        $contentTypes);
        $zip->addFromString('_rels/.rels',                $packageRels);
        $zip->addFromString('xl/workbook.xml',            $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml',   $sheetXml);
        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $content;
    }

    private function buildXlsxSaran(array $rows, string $judulSurvei, string $periode): string
    {
        // Escape XML entities + konversi newline → &#10; agar Excel render multiline
        $escSaran = function (string $v): string {
            $v = str_replace(["\r\n", "\r", "\n"], '&#10;', $v);
            return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        };
        $esc = fn(string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $rowsXml = '';
        $rowNum  = 4;

        foreach ($rows as $i => $r) {
            $no        = $i + 1;
            $tgl       = $r->submitted_at ? date('d/m/Y H:i', strtotime($r->submitted_at)) : '-';
            $namaAsn   = $esc($r->nama_asn ?? '-');
            $nip       = $esc($r->nip ?? '-');
            $jabatan   = $esc($r->jabatan ?? '-');
            $namaUnit  = $esc($r->nama_unit ?? 'Tanpa Unit Kerja');
            $saran     = $escSaran($r->saran ?? '');

            $rowsXml .= "<row r=\"{$rowNum}\">"
                . "<c r=\"A{$rowNum}\" t=\"n\"><v>{$no}</v></c>"
                . "<c r=\"B{$rowNum}\" t=\"inlineStr\"><is><t>{$esc($tgl)}</t></is></c>"
                . "<c r=\"C{$rowNum}\" t=\"inlineStr\"><is><t>{$namaAsn}</t></is></c>"
                . "<c r=\"D{$rowNum}\" t=\"inlineStr\"><is><t>{$nip}</t></is></c>"
                . "<c r=\"E{$rowNum}\" t=\"inlineStr\"><is><t>{$namaUnit}</t></is></c>"
                . "<c r=\"F{$rowNum}\" t=\"inlineStr\"><is><t>{$saran}</t></is></c>"
                . "</row>\n";
            $rowNum++;
        }

        if (empty($rows)) {
            $rowsXml .= "<row r=\"4\"><c r=\"A4\" t=\"inlineStr\"><is><t>Belum ada saran yang masuk.</t></is></c></row>\n";
        }

        $judulEsc   = $esc($judulSurvei);
        $periodeEsc = $esc($periode);
        $tglExport  = $esc(now()->format('d/m/Y H:i'));

        $sheetXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>SARAN &amp; MASUKAN SURVEI ESARAKU</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>Survei: {$judulEsc} | Periode: {$periodeEsc} | Diekspor: {$tglExport}</t></is></c>
    </row>
    <row r="3">
      <c r="A3" t="inlineStr"><is><t>No</t></is></c>
      <c r="B3" t="inlineStr"><is><t>Tanggal Submit</t></is></c>
      <c r="C3" t="inlineStr"><is><t>Nama ASN</t></is></c>
      <c r="D3" t="inlineStr"><is><t>NIP</t></is></c>
      <c r="E3" t="inlineStr"><is><t>Unit Kerja</t></is></c>
      <c r="F3" t="inlineStr"><is><t>Saran &amp; Masukan</t></is></c>
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
    <sheet name="Saran Masukan" sheetId="1" r:id="rId1"/>
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

        $tmpFile = tempnam(sys_get_temp_dir(), 'esaraku_saran_xlsx_');
        $zip     = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',        $contentTypes);
        $zip->addFromString('_rels/.rels',                $packageRels);
        $zip->addFromString('xl/workbook.xml',            $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml',   $sheetXml);
        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $content;
    }

    private function buildXlsx(array $rows, string $judulSurvei, string $periode): string
    {
        $escape = fn(string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $rowsXml = '';
        $rowNum  = 4;

        foreach ($rows as $i => $r) {
            $no     = $i + 1;
            $persen = number_format($r['persen'], 1) . '%';

            $rowsXml .= "<row r=\"{$rowNum}\">"
                . "<c r=\"A{$rowNum}\" t=\"n\"><v>{$no}</v></c>"
                . "<c r=\"B{$rowNum}\" t=\"inlineStr\"><is><t>{$escape($r['nama_unit'])}</t></is></c>"
                . "<c r=\"C{$rowNum}\" t=\"n\"><v>{$r['total']}</v></c>"
                . "<c r=\"D{$rowNum}\" t=\"n\"><v>{$r['sudah']}</v></c>"
                . "<c r=\"E{$rowNum}\" t=\"n\"><v>{$r['belum']}</v></c>"
                . "<c r=\"F{$rowNum}\" t=\"inlineStr\"><is><t>{$persen}</t></is></c>"
                . "<c r=\"G{$rowNum}\" t=\"inlineStr\"><is><t>{$escape($r['nama_belum'])}</t></is></c>"
                . "</row>\n";
            $rowNum++;
        }

        $totalSemua = array_sum(array_column($rows, 'total'));
        $totalSudah = array_sum(array_column($rows, 'sudah'));
        $totalBelum = array_sum(array_column($rows, 'belum'));
        $pctTotal   = $totalSemua > 0
            ? number_format(round($totalSudah / $totalSemua * 100, 1), 1) . '%'
            : '0%';

        $rowsXml .= "<row r=\"{$rowNum}\">"
            . "<c r=\"A{$rowNum}\" t=\"inlineStr\"><is><t></t></is></c>"
            . "<c r=\"B{$rowNum}\" t=\"inlineStr\"><is><t>TOTAL</t></is></c>"
            . "<c r=\"C{$rowNum}\" t=\"n\"><v>{$totalSemua}</v></c>"
            . "<c r=\"D{$rowNum}\" t=\"n\"><v>{$totalSudah}</v></c>"
            . "<c r=\"E{$rowNum}\" t=\"n\"><v>{$totalBelum}</v></c>"
            . "<c r=\"F{$rowNum}\" t=\"inlineStr\"><is><t>{$pctTotal}</t></is></c>"
            . "<c r=\"G{$rowNum}\" t=\"inlineStr\"><is><t></t></is></c>"
            . "</row>\n";

        $judulEsc   = $escape($judulSurvei);
        $periodeEsc = $escape($periode);

        $sheetXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    <row r="1">
      <c r="A1" t="inlineStr"><is><t>MONITORING SURVEI ESARAKU</t></is></c>
    </row>
    <row r="2">
      <c r="A2" t="inlineStr"><is><t>Survei: {$judulEsc} | Periode: {$periodeEsc}</t></is></c>
    </row>
    <row r="3">
      <c r="A3" t="inlineStr"><is><t>No</t></is></c>
      <c r="B3" t="inlineStr"><is><t>Unit Kerja</t></is></c>
      <c r="C3" t="inlineStr"><is><t>Total Pegawai</t></is></c>
      <c r="D3" t="inlineStr"><is><t>Sudah Isi</t></is></c>
      <c r="E3" t="inlineStr"><is><t>Belum Isi</t></is></c>
      <c r="F3" t="inlineStr"><is><t>Persentase</t></is></c>
      <c r="G3" t="inlineStr"><is><t>Nama Belum Isi</t></is></c>
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
    <sheet name="Monitoring Survei" sheetId="1" r:id="rId1"/>
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

        $tmpFile = tempnam(sys_get_temp_dir(), 'esaraku_survei_xlsx_');
        $zip     = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',        $contentTypes);
        $zip->addFromString('_rels/.rels',                $packageRels);
        $zip->addFromString('xl/workbook.xml',            $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml',   $sheetXml);
        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $content;
    }
}
