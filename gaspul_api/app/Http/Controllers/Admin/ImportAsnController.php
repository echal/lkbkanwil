<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportLog;
use App\Models\User;
use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportAsnController extends Controller
{
    private const MAX_ROWS = 1000;

    // =========================================================================
    // TAHAP 1 — Tampilkan form upload
    // =========================================================================

    public function index()
    {
        return view('admin.import-asn.index');
    }

    // =========================================================================
    // TAHAP 2 — Parse, validasi, simpan ke session, tampilkan preview
    // =========================================================================

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ], [
            'file.required'  => 'File wajib diunggah.',
            'file.mimes'     => 'Format file harus CSV (.csv).',
            'file.max'       => 'Ukuran file maksimal 5 MB.',
        ]);

        $path = $request->file('file')->getRealPath();

        // Deteksi BOM UTF-8 dan strip jika ada
        $raw = file_get_contents($path, false, null, 0, 3);
        $offset = ($raw === "\xEF\xBB\xBF") ? 3 : 0;

        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->withErrors(['file' => 'File tidak dapat dibaca.']);
        }

        // Skip BOM jika ada
        if ($offset > 0) fseek($handle, $offset);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        // Validasi header: ambil baris pertama SEBELUM filter kosong
        $headerRow      = array_shift($rows);
        $expectedHeader = ['name', 'email', 'nip', 'password', 'role', 'unit_kerja_id', 'jabatan', 'atasan_id', 'status_pegawai'];
        $actualHeader   = array_map(fn($h) => strtolower(trim((string) ($h ?? ''))), $headerRow ?? []);

        if ($actualHeader !== $expectedHeader) {
            return back()->withErrors([
                'file' => 'Header tidak sesuai. '
                    . 'Diharapkan (' . count($expectedHeader) . ' kolom): ' . implode(', ', $expectedHeader) . '. '
                    . 'Ditemukan (' . count($actualHeader) . ' kolom): ' . implode(', ', $actualHeader) . '.',
            ]);
        }

        // Hapus baris kosong total
        $rows = array_values(array_filter($rows, fn($r) => !empty(array_filter($r, fn($v) => $v !== null && $v !== ''))));

        if (count($rows) === 0) {
            return back()->withErrors(['file' => 'File tidak memiliki data (hanya header atau kosong).']);
        }

        if (count($rows) > self::MAX_ROWS) {
            return back()->withErrors(['file' => 'Maksimal ' . self::MAX_ROWS . ' baris per upload. File Anda memiliki ' . count($rows) . ' baris.']);
        }

        // Normalisasi ke keyed array
        $mapped = [];
        foreach ($rows as $i => $row) {
            $mapped[] = [
                'name'           => trim($row[0] ?? ''),
                'email'          => trim($row[1] ?? ''),
                'nip'            => trim((string)($row[2] ?? '')),
                'password'       => trim($row[3] ?? ''),
                'role'           => strtoupper(trim($row[4] ?? '')),
                'unit_kerja_id'  => $row[5] !== null && $row[5] !== '' ? (int)$row[5] : null,
                'jabatan'        => trim($row[6] ?? '') ?: null,
                'atasan_id'      => $row[7] !== null && $row[7] !== '' ? (int)$row[7] : null,
                'status_pegawai' => strtoupper(trim($row[8] ?? '')),
                '_row'           => $i + 2, // nomor baris di Excel (header = 1)
            ];
        }

        // Validasi duplikat dalam file
        $emailsInFile = array_column($mapped, 'email');
        $nipsInFile   = array_column($mapped, 'nip');

        $dupEmails = $this->findDuplicates($emailsInFile);
        $dupNips   = $this->findDuplicates($nipsInFile);

        $fileDupErrors = [];

        if (!empty($dupEmails)) {
            foreach ($dupEmails as $email => $rowNums) {
                $fileDupErrors[] = 'Duplikat email "' . $email . '" pada baris: ' . implode(', ', $rowNums);
            }
        }

        if (!empty($dupNips)) {
            foreach ($dupNips as $nip => $rowNums) {
                $fileDupErrors[] = 'Duplikat NIP "' . $nip . '" pada baris: ' . implode(', ', $rowNums);
            }
        }

        if (!empty($fileDupErrors)) {
            return back()->withErrors(['file' => implode(' | ', $fileDupErrors)]);
        }

        // Validasi per baris
        $validRows   = [];
        $invalidRows = [];
        $hasError    = false;

        $validUnitIds   = UnitKerja::pluck('id')->toArray();
        $validAtasanIds = User::whereIn('role', ['ATASAN', 'ADMIN'])->pluck('id')->toArray();
        $existingEmails = User::pluck('email')->toArray();
        $existingNips   = User::whereNotNull('nip')->pluck('nip')->toArray();

        foreach ($mapped as $row) {
            $errors = [];

            $validator = Validator::make($row, [
                'name'           => 'required|string|max:255',
                'email'          => 'required|email|max:255',
                'nip'            => 'required|digits:18',
                'password'       => 'required|min:8',
                'role'           => 'required|in:ASN,ATASAN',
                'unit_kerja_id'  => 'required|integer',
                'jabatan'        => 'nullable|string|max:255',
                'atasan_id'      => 'nullable|integer',
                'status_pegawai' => 'required|in:AKTIF,NONAKTIF',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $msg) {
                    $errors[] = $msg;
                }
            }

            // Cek email sudah ada di DB
            if (!empty($row['email']) && in_array($row['email'], $existingEmails)) {
                $errors[] = 'Email "' . $row['email'] . '" sudah terdaftar di sistem.';
            }

            // Cek NIP sudah ada di DB
            if (!empty($row['nip']) && in_array($row['nip'], $existingNips)) {
                $errors[] = 'NIP "' . $row['nip'] . '" sudah terdaftar di sistem.';
            }

            // Cek unit_kerja_id valid
            if (!empty($row['unit_kerja_id']) && !in_array($row['unit_kerja_id'], $validUnitIds)) {
                $errors[] = 'unit_kerja_id "' . $row['unit_kerja_id'] . '" tidak ditemukan.';
            }

            // Cek atasan_id valid jika diisi
            if (!empty($row['atasan_id']) && !in_array($row['atasan_id'], $validAtasanIds)) {
                $errors[] = 'atasan_id "' . $row['atasan_id'] . '" tidak valid atau bukan ATASAN/ADMIN.';
            }

            $rowResult = array_merge($row, ['errors' => $errors]);

            if (!empty($errors)) {
                $hasError      = true;
                $invalidRows[] = $rowResult;
            } else {
                $validRows[]   = $rowResult;
            }
        }

        // Simpan data valid ke session (tanpa password plaintext di sesi, kita simpan semua tapi flush setelah konfirmasi)
        session(['import_asn_rows' => $mapped, 'import_asn_file_name' => $request->file('file')->getClientOriginalName()]);

        return view('admin.import-asn.preview', compact('mapped', 'validRows', 'invalidRows', 'hasError'));
    }

    // =========================================================================
    // TAHAP 3 — Konfirmasi: atomic transaction insert
    // =========================================================================

    public function confirm(Request $request)
    {
        $rows     = session('import_asn_rows');
        $fileName = session('import_asn_file_name', 'unknown.xlsx');

        if (empty($rows)) {
            return redirect()->route('admin.import-asn.index')
                ->with('error', 'Sesi preview telah kedaluwarsa. Silakan upload ulang.');
        }

        // Re-validasi ketat sebelum insert (defend against stale session)
        $existingEmails = User::pluck('email')->toArray();
        $existingNips   = User::whereNotNull('nip')->pluck('nip')->toArray();
        $validUnitIds   = UnitKerja::pluck('id')->toArray();
        $validAtasanIds = User::whereIn('role', ['ATASAN', 'ADMIN'])->pluck('id')->toArray();

        foreach ($rows as $row) {
            if (in_array($row['email'], $existingEmails)) {
                return redirect()->route('admin.import-asn.index')
                    ->with('error', 'Data berubah sejak preview: email "' . $row['email'] . '" sudah ada. Silakan upload ulang.');
            }
            if (in_array($row['nip'], $existingNips)) {
                return redirect()->route('admin.import-asn.index')
                    ->with('error', 'Data berubah sejak preview: NIP "' . $row['nip'] . '" sudah ada. Silakan upload ulang.');
            }
            if (!in_array($row['unit_kerja_id'], $validUnitIds)) {
                return redirect()->route('admin.import-asn.index')
                    ->with('error', 'Data tidak valid: unit_kerja_id "' . $row['unit_kerja_id'] . '" tidak ditemukan.');
            }
        }

        try {
            DB::transaction(function () use ($rows, $fileName) {
                foreach ($rows as $row) {
                    User::create([
                        'name'           => $row['name'],
                        'email'          => $row['email'],
                        'password'       => $row['password'],
                        'role'           => strtoupper($row['role']),
                        'nip'            => $row['nip'],
                        'unit_kerja_id'  => $row['unit_kerja_id'],
                        'jabatan'        => $row['jabatan'] ?? null,
                        'atasan_id'      => $row['atasan_id'] ?? null,
                        'status_pegawai' => $row['status_pegawai'],
                    ]);
                }

                ImportLog::create([
                    'admin_id'      => auth()->id(),
                    'file_name'     => $fileName,
                    'total_rows'    => count($rows),
                    'imported_rows' => count($rows),
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()->route('admin.import-asn.index')
                ->with('error', 'Import gagal dan di-rollback: ' . $e->getMessage());
        }

        session()->forget(['import_asn_rows', 'import_asn_file_name']);

        return redirect()->route('admin.import-asn.index')
            ->with('success', count($rows) . ' data ASN berhasil diimport pada ' . now()->format('d M Y, H:i:s') . ' oleh ' . auth()->user()->name . '.');
    }

    // =========================================================================
    // DOWNLOAD TEMPLATE EXCEL
    // =========================================================================

    public function downloadTemplate(): StreamedResponse
    {
        $rows = [
            // Header
            ['name', 'email', 'nip', 'password', 'role', 'unit_kerja_id', 'jabatan', 'atasan_id', 'status_pegawai'],
            // Contoh data
            ['Ahmad Fauzi', 'ahmad.fauzi@kemenag.go.id', '199001012020011001', 'Password123', 'ASN', '1', 'Penyuluh Agama Islam', '293', 'AKTIF'],
            ['Siti Rahma', 'siti.rahma@kemenag.go.id', '199505152021012002', 'Password123', 'ASN', '1', 'Guru Madrasah', '293', 'AKTIF'],
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM agar Excel baca UTF-8 dengan benar
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_asn.csv"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function findDuplicates(array $values): array
    {
        $seen   = [];
        $dupMap = [];

        foreach ($values as $i => $val) {
            if ($val === '' || $val === null) continue;
            $rowNum = $i + 2;
            if (isset($seen[$val])) {
                if (!isset($dupMap[$val])) {
                    $dupMap[$val] = [$seen[$val]];
                }
                $dupMap[$val][] = $rowNum;
            } else {
                $seen[$val] = $rowNum;
            }
        }

        return $dupMap;
    }
}
