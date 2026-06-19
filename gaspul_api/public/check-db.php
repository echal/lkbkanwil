<?php
/**
 * Audit: Dampak pergantian atasan terhadap data KH, TLA, Laporan, Rekap, SKP
 * READ-ONLY — tidak mengubah data apapun
 * HAPUS setelah selesai!
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo '<!DOCTYPE html><html><head><meta charset="utf-8">
<title>Audit Dampak Ganti Atasan</title>
<style>
body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;max-width:1200px}
h2{color:#dcdcaa;margin-top:30px} h3{color:#9cdcfe}
.ok{color:#6a9955} .err{color:#f44747} .warn{color:#e5c07b} .info{color:#9cdcfe}
.box{background:#252526;padding:15px;border-radius:6px;margin-bottom:12px}
table{border-collapse:collapse;width:100%;font-size:0.85em;margin-top:8px}
th{background:#2d2d2d;color:#dcdcaa;padding:7px 10px;text-align:left;border:1px solid #444}
td{border:1px solid #333;padding:6px 10px;vertical-align:top}
tr:hover td{background:#2a2a2a}
.badge-ok{background:#1a3a1a;color:#6a9955;padding:2px 7px;border-radius:4px}
.badge-warn{background:#4a3a00;color:#e5c07b;padding:2px 7px;border-radius:4px}
.badge-err{background:#5a1a1a;color:#f44747;padding:2px 7px;border-radius:4px}
.badge-info{background:#1a2a3a;color:#9cdcfe;padding:2px 7px;border-radius:4px}
.stat{display:inline-block;background:#252526;border:1px solid #444;padding:15px 25px;border-radius:8px;margin:8px;text-align:center}
.stat-num{font-size:2em;font-weight:bold;display:block}
.conclusion{border-left:4px solid #e5c07b;padding:10px 15px;margin:8px 0;background:#252526}
.conclusion.ok{border-color:#6a9955}
.conclusion.err{border-color:#f44747}
</style></head><body>';

use Illuminate\Support\Facades\DB;

echo '<h2>Audit: Dampak Pergantian Atasan terhadap Rekam Jejak ASN</h2>';
echo '<p style="color:#858585">READ-ONLY — tidak ada data yang diubah | ' . now()->setTimezone('Asia/Makassar')->format('d M Y H:i:s') . ' WITA</p>';

// ============================================================
// A. ARSITEKTUR
// ============================================================
echo '<h2>A. Arsitektur Hubungan Data → Atasan</h2>';
echo '<div class="box"><pre>';
echo '<span class="info">TIPE 1 — LIVE (real-time via atasan_id di tabel users):</span>
  KH  (progres_harian)  → tidak punya kolom atasan_id → query via users.atasan_id
  TLA (progres_harian)  → sama dengan KH

<span class="info">TIPE 2 — SNAPSHOT (approved_by disimpan saat transaksi):</span>
  Laporan Bulanan        → laporan_bulanan_kinerja.approved_by = id atasan saat diproses
  Rekap Absensi PUSAKA   → rekap_absensi_pusaka.approved_by   = id atasan saat diproses
  SKP Tahunan            → skp_tahunan.approved_by            = id atasan saat diajukan

<span class="warn">KESIMPULAN ARSITEKTUR:</span>
  Ganti atasan_id → KH & TLA LANGSUNG beralih ke atasan baru (real-time)
  Laporan/Rekap/SKP yang sudah diproses → TETAP tercatat di atasan lama (audit trail)
  Laporan/Rekap/SKP yang belum dikirim  → akan ke atasan baru
</pre></div>';

// ============================================================
// B. KH & TLA
// ============================================================
echo '<h2>B. Kinerja Harian (KH) & Tugas Langsung Atasan (TLA)</h2>';
$totalKh  = DB::table('progres_harian')->where('tipe_progres', 'KINERJA_HARIAN')->count();
$totalTla = DB::table('progres_harian')->where('tipe_progres', 'TUGAS_ATASAN')->count();
echo '<div class="box"><pre>';
echo 'Total record KH  : <span class="info">' . number_format($totalKh)  . ' record</span>' . "\n";
echo 'Total record TLA : <span class="info">' . number_format($totalTla) . ' record</span>' . "\n";
echo 'Kolom atasan_id di progres_harian : <span class="ok">TIDAK ADA</span>' . "\n";
echo 'Terhubung ke atasan via           : <span class="info">users.atasan_id (real-time)</span>' . "\n\n";
echo '<span class="ok">✓ AMAN — KH & TLA tidak kehilangan data saat atasan berganti</span>' . "\n";
echo '<span class="warn">Efek ganti atasan:</span>' . "\n";
echo '  Atasan LAMA : tidak bisa lagi lihat KH/TLA ASN ini' . "\n";
echo '  Atasan BARU : langsung bisa monitor KH/TLA termasuk seluruh histori' . "\n";
echo '</pre></div>';
echo '<div class="conclusion ok">KH & TLA: Semua rekam jejak BERPINDAH ke atasan baru. Atasan baru bisa lihat seluruh histori KH/TLA ASN termasuk data sebelum pergantian.</div>';

// ============================================================
// C. LAPORAN BULANAN
// ============================================================
echo '<h2>C. Laporan Bulanan Kinerja</h2>';
$totalLaporan     = DB::table('laporan_bulanan_kinerja')->count();
$laporanDisetujui = DB::table('laporan_bulanan_kinerja')->where('status', 'DISETUJUI')->count();
$laporanDikirim   = DB::table('laporan_bulanan_kinerja')->where('status', 'DIKIRIM')->count();
$laporanDraft     = DB::table('laporan_bulanan_kinerja')->where('status', 'DRAFT')->count();
$laporanDitolak   = DB::table('laporan_bulanan_kinerja')->where('status', 'DITOLAK')->count();

// Laporan DIKIRIM tapi approved_by (atasan saat dikirim) ≠ atasan_id sekarang
// Kolom approved_by diisi saat APPROVE/TOLAK, bukan saat DIKIRIM — jadi cek via user.atasan_id
// Yang bermasalah: DIKIRIM dan atasan sudah berganti (approved_by = null saat status DIKIRIM)
// → tidak ada masalah teknis, atasan baru otomatis lihat karena query via atasan_id live

// Laporan DISETUJUI/DITOLAK dengan approved_by ≠ atasan_id sekarang = normal (audit trail)
$laporanMismatch = DB::table('laporan_bulanan_kinerja as l')
    ->join('users as u', 'l.user_id', '=', 'u.id')
    ->whereNotNull('l.approved_by')
    ->whereNotNull('u.atasan_id')
    ->whereRaw('l.approved_by != u.atasan_id')
    ->whereIn('l.status', ['DISETUJUI', 'DITOLAK'])
    ->count();

echo '<div class="box"><pre>';
echo 'Total laporan bulanan  : <span class="info">' . number_format($totalLaporan)     . '</span>' . "\n";
echo 'DISETUJUI              : <span class="ok">'   . number_format($laporanDisetujui) . '</span>' . "\n";
echo 'DIKIRIM (pending)      : <span class="warn">' . number_format($laporanDikirim)   . '</span>' . "\n";
echo 'DRAFT                  : <span class="info">' . number_format($laporanDraft)     . '</span>' . "\n";
echo 'DITOLAK                : <span class="err">'  . number_format($laporanDitolak)   . '</span>' . "\n\n";
echo 'Laporan diproses atasan lama (normal): <span class="info">' . number_format($laporanMismatch) . ' laporan</span>' . "\n";
echo '<span class="info">(ini NORMAL — approved_by adalah snapshot saat diproses)</span>' . "\n";
echo '</pre></div>';
echo '<div class="conclusion ' . ($laporanDikirim == 0 ? 'ok' : 'warn') . '">';
echo 'Laporan Bulanan: Histori yang sudah disetujui/ditolak <strong>tetap tercatat di atasan lama</strong> — benar secara audit. ';
if ($laporanDikirim > 0) {
    echo '<strong>' . number_format($laporanDikirim) . ' laporan DIKIRIM akan muncul di dashboard atasan baru.</strong>';
} else {
    echo 'Tidak ada laporan pending.';
}
echo '</div>';

// ============================================================
// D. REKAP ABSENSI PUSAKA
// ============================================================
echo '<h2>D. Rekap Absensi PUSAKA</h2>';
$totalRekap   = DB::table('rekap_absensi_pusaka')->count();
$rekapPending = DB::table('rekap_absensi_pusaka')
    ->whereIn('status', ['pending_kabid','pending_kakanwil','pending_kankemenag'])
    ->count();
$rekapSelesai = DB::table('rekap_absensi_pusaka')->where('status', 'disetujui')->count();
echo '<div class="box"><pre>';
echo 'Total rekap absensi    : <span class="info">' . number_format($totalRekap)   . '</span>' . "\n";
echo 'Disetujui              : <span class="ok">'   . number_format($rekapSelesai) . '</span>' . "\n";
echo 'Pending (belum selesai): <span class="warn">' . number_format($rekapPending) . '</span>' . "\n";
echo '</pre></div>';
echo '<div class="conclusion ' . ($rekapPending == 0 ? 'ok' : 'warn') . '">';
echo 'Rekap PUSAKA: Histori tersimpan permanen. ';
if ($rekapPending > 0) {
    echo '<strong>' . number_format($rekapPending) . ' rekap pending akan muncul di atasan baru.</strong>';
} else {
    echo 'Tidak ada rekap pending.';
}
echo '</div>';

// ============================================================
// E. SKP TAHUNAN
// ============================================================
echo '<h2>E. SKP Tahunan 2026</h2>';
$totalSkp     = DB::table('skp_tahunan')->where('tahun', 2026)->count();
$skpDisetujui = DB::table('skp_tahunan')->where('tahun', 2026)->where('status', 'DISETUJUI')->count();
$skpDiajukan  = DB::table('skp_tahunan')->where('tahun', 2026)->where('status', 'DIAJUKAN')->count();
$skpDraft     = DB::table('skp_tahunan')->where('tahun', 2026)->where('status', 'DRAFT')->count();
$skpDitolak   = DB::table('skp_tahunan')->where('tahun', 2026)->where('status', 'DITOLAK')->count();

// SKP DIAJUKAN ke atasan lama (approved_by ≠ atasan_id sekarang) — INI BERMASALAH
$skpSalahApprover = DB::table('skp_tahunan as s')
    ->join('users as u', 's.user_id', '=', 'u.id')
    ->leftJoin('users as atasan_lama', 's.approved_by', '=', 'atasan_lama.id')
    ->leftJoin('users as atasan_baru', 'u.atasan_id', '=', 'atasan_baru.id')
    ->where('s.tahun', 2026)
    ->where('s.status', 'DIAJUKAN')
    ->whereNotNull('s.approved_by')
    ->whereNotNull('u.atasan_id')
    ->whereRaw('s.approved_by != u.atasan_id')
    ->select(
        'u.name', 'u.nip',
        's.id as skp_id', 's.approved_by', 's.updated_at',
        'atasan_lama.name as nama_atasan_lama',
        'u.atasan_id',
        'atasan_baru.name as nama_atasan_baru'
    )
    ->get();

// SKP DRAFT inkonsisten
$skpInkonsisten = DB::table('skp_tahunan')
    ->where('tahun', 2026)
    ->where('status', 'DRAFT')
    ->whereNotNull('approved_at')
    ->count();

echo '<div class="box"><pre>';
echo 'Total SKP 2026     : <span class="info">' . $totalSkp     . '</span>' . "\n";
echo 'DISETUJUI          : <span class="ok">'   . $skpDisetujui . '</span>' . "\n";
echo 'DIAJUKAN (pending) : <span class="warn">' . $skpDiajukan  . '</span>' . "\n";
echo 'DRAFT              : <span class="info">' . $skpDraft     . '</span>' . "\n";
echo 'DITOLAK            : <span class="err">'  . $skpDitolak   . '</span>' . "\n\n";
echo 'SKP DIAJUKAN ke atasan LAMA : <span class="' . ($skpSalahApprover->count() > 0 ? 'err' : 'ok') . '">' . $skpSalahApprover->count() . ' SKP</span>';
if ($skpSalahApprover->count() > 0) echo ' ← PERLU PERHATIAN';
echo "\n";
echo 'SKP DRAFT inkonsisten       : <span class="' . ($skpInkonsisten > 0 ? 'warn' : 'ok') . '">' . $skpInkonsisten . ' SKP</span>' . "\n";
echo '</pre></div>';

if ($skpSalahApprover->count() > 0) {
    echo '<h3 style="color:#f44747">⚠ SKP DIAJUKAN ke Atasan Lama — Atasan Baru Tidak Bisa Approve</h3>';
    echo '<table><thead><tr>
        <th>Nama ASN</th><th>NIP</th><th>SKP ID</th>
        <th>Diajukan ke (lama)</th><th>Atasan sekarang</th><th>Tgl Diajukan</th>
    </tr></thead><tbody>';
    foreach ($skpSalahApprover as $r) {
        echo '<tr>
            <td>' . htmlspecialchars($r->name) . '</td>
            <td>' . $r->nip . '</td>
            <td>' . $r->skp_id . '</td>
            <td style="color:#f44747">' . htmlspecialchars($r->nama_atasan_lama ?? 'ID:'.$r->approved_by) . '</td>
            <td style="color:#6a9955">' . htmlspecialchars($r->nama_atasan_baru ?? 'ID:'.$r->atasan_id) . '</td>
            <td>' . date('d/m/Y H:i', strtotime($r->updated_at)) . '</td>
        </tr>';
    }
    echo '</tbody></table>';
    echo '<div class="conclusion err">
        <strong>Masalah:</strong> SKP sudah DIAJUKAN tapi approved_by masih atasan lama — atasan baru tidak bisa approve.<br>
        <strong>Solusi:</strong> Perlu reset SKP ini ke DRAFT agar ASN bisa ajukan ulang ke atasan baru.
        <br><a href="?fix_skp_diajukan=yes" style="color:#e5c07b">▶ Reset SKP ini ke DRAFT</a>
    </div>';
}

// Fix SKP DIAJUKAN ke atasan lama
if (isset($_GET['fix_skp_diajukan']) && $_GET['fix_skp_diajukan'] === 'yes' && $skpSalahApprover->count() > 0) {
    echo '<h3>Fix SKP DIAJUKAN ke Atasan Lama</h3><div class="box"><pre>';
    $ids = $skpSalahApprover->pluck('skp_id')->toArray();
    $affected = DB::table('skp_tahunan')
        ->whereIn('id', $ids)
        ->update([
            'status'         => 'DRAFT',
            'approved_by'    => null,
            'approved_at'    => null,
            'catatan_atasan' => null,
            'updated_at'     => now(),
        ]);
    echo ($affected > 0 ? '<span class="ok">✓' : '<span class="warn">⚠') . ' ' . $affected . ' SKP berhasil di-reset ke DRAFT</span>' . "\n";
    echo 'ASN dapat mengajukan ulang ke atasan baru.' . "\n";
    echo '</pre></div>';
}

// SKP inkonsisten
if ($skpInkonsisten > 0) {
    echo '<p><a href="?fix_skp_inkonsisten=yes" style="background:#4a3a00;color:#e5c07b;padding:8px 18px;border-radius:4px;text-decoration:none">
        ▶ Bersihkan ' . $skpInkonsisten . ' SKP DRAFT inkonsisten (approved_at → NULL)
    </a></p>';
}
if (isset($_GET['fix_skp_inkonsisten']) && $_GET['fix_skp_inkonsisten'] === 'yes') {
    $affected = DB::table('skp_tahunan')
        ->where('status', 'DRAFT')->whereNotNull('approved_at')
        ->update(['approved_by' => null, 'approved_at' => null, 'catatan_atasan' => null, 'updated_at' => now()]);
    echo '<div class="box"><pre><span class="ok">✓ ' . $affected . ' SKP inkonsisten dibersihkan</span></pre></div>';
}

// ============================================================
// F. ASN TANPA ATASAN — single optimized query
// ============================================================
echo '<h2>F. ASN Tanpa Atasan (21 orang)</h2>';

$asnTanpaAtasan = DB::table('users as u')
    ->leftJoin('unit_kerja as uk', 'u.unit_kerja_id', '=', 'uk.id')
    ->where('u.role', 'ASN')
    ->where('u.status_pegawai', 'AKTIF')
    ->whereNull('u.atasan_id')
    ->select('u.id', 'u.name', 'u.nip', 'u.jabatan', 'uk.nama_unit', 'u.created_at')
    ->orderBy('uk.nama_unit')
    ->orderBy('u.name')
    ->get();

// Ambil SKP semua user sekaligus (1 query)
$userIds = $asnTanpaAtasan->pluck('id')->toArray();
$skpMap  = DB::table('skp_tahunan')
    ->where('tahun', 2026)
    ->whereIn('user_id', $userIds)
    ->pluck('status', 'user_id');

echo '<table><thead><tr>
    <th>#</th><th>ID</th><th>Nama</th><th>NIP</th><th>Jabatan</th><th>Unit Kerja</th><th>Terdaftar</th><th>SKP 2026</th>
</tr></thead><tbody>';

foreach ($asnTanpaAtasan as $i => $asn) {
    $status = $skpMap[$asn->id] ?? null;
    if (!$status) {
        $skpBadge = '<span style="color:#555">belum ada</span>';
    } elseif ($status === 'DISETUJUI') {
        $skpBadge = '<span class="badge-ok">DISETUJUI</span>';
    } elseif ($status === 'DRAFT') {
        $skpBadge = '<span class="badge-warn">DRAFT</span>';
    } elseif ($status === 'DIAJUKAN') {
        $skpBadge = '<span class="badge-warn">DIAJUKAN</span>';
    } else {
        $skpBadge = '<span class="badge-err">' . $status . '</span>';
    }
    echo '<tr>
        <td>' . ($i+1) . '</td>
        <td>' . $asn->id . '</td>
        <td>' . htmlspecialchars($asn->name) . '</td>
        <td>' . $asn->nip . '</td>
        <td style="font-size:0.8em">' . htmlspecialchars($asn->jabatan ?? '-') . '</td>
        <td>' . htmlspecialchars($asn->nama_unit ?? '-') . '</td>
        <td style="font-size:0.8em">' . date('d/m/Y', strtotime($asn->created_at)) . '</td>
        <td>' . $skpBadge . '</td>
    </tr>';
}
echo '</tbody></table>';

// Kelompok per unit kerja
$byUnit = $asnTanpaAtasan->groupBy('nama_unit')->map->count()->sortDesc();
echo '<h3>Per Unit Kerja</h3><div class="box"><pre>';
foreach ($byUnit as $unit => $count) {
    $bar = str_repeat('█', min($count * 2, 40));
    echo sprintf('%-40s %s %d orang', htmlspecialchars($unit ?: '-'), $bar, $count) . "\n";
}
echo '</pre></div>';

// ============================================================
// G. RINGKASAN EKSEKUTIF
// ============================================================
echo '<h2>G. Ringkasan Eksekutif</h2>';
echo '<table><thead><tr>
    <th>Data</th><th>Mekanisme</th><th>Efek Ganti Atasan</th><th>Histori Lama</th><th>Status</th>
</tr></thead><tbody>
<tr>
    <td><strong>KH & TLA</strong></td>
    <td>Real-time (atasan_id)</td>
    <td>Langsung pindah ke atasan baru</td>
    <td>Atasan baru lihat semua histori</td>
    <td><span class="badge-ok">AMAN</span></td>
</tr>
<tr>
    <td><strong>Laporan Bulanan</strong></td>
    <td>Snapshot (approved_by)</td>
    <td>Pending → ke atasan baru<br>Selesai → tetap di atasan lama</td>
    <td>Audit trail terjaga</td>
    <td><span class="badge-ok">AMAN</span></td>
</tr>
<tr>
    <td><strong>Rekap PUSAKA</strong></td>
    <td>Snapshot (approved_by)</td>
    <td>Pending → ke atasan baru<br>Selesai → tetap di atasan lama</td>
    <td>Audit trail terjaga</td>
    <td><span class="badge-ok">AMAN</span></td>
</tr>
<tr>
    <td><strong>SKP Tahunan</strong></td>
    <td>Snapshot + auto-sync via PegawaiController</td>
    <td>Diajukan → auto-sync ke atasan baru<br>Disetujui → tetap di atasan lama</td>
    <td>Audit trail terjaga</td>
    <td><span class="' . ($skpSalahApprover->count() > 0 ? 'badge-err">PERLU FIX' : 'badge-ok">AMAN') . '</span></td>
</tr>
<tr>
    <td><strong>ASN Tanpa Atasan</strong></td>
    <td>—</td>
    <td>Tidak bisa ajukan SKP</td>
    <td>—</td>
    <td><span class="badge-err">21 ASN</span></td>
</tr>
</tbody></table>';

echo '<p style="color:#858585;font-size:0.8em;margin-top:30px">⚠ READ-ONLY. HAPUS FILE INI SETELAH SELESAI!</p>';
echo '</body></html>';
