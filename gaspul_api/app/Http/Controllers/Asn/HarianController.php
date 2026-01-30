<?php

namespace App\Http\Controllers\Asn;

use App\Http\Controllers\Controller;
use App\Models\RencanaAksiBulanan;
use App\Models\ProgresHarian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HarianController extends Controller
{
    /**
     * Display calendar view with monthly progress
     */
    public function index(Request $request)
    {
        $asn = Auth::user();

        // Get selected month and year
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $selectedDate = $request->input('date', now()->format('Y-m-d'));

        // Build calendar data for the month
        $calendarData = $this->buildCalendarData($asn->id, $month, $year);

        // Get progress for selected date
        $progressData = $this->getProgressForDate($asn->id, $selectedDate);

        return view('asn.harian.index', [
            'calendarData' => $calendarData,
            'progressData' => $progressData,
            'selectedDate' => $selectedDate,
            'month' => $month,
            'year' => $year,
            'asn' => $asn,
        ]);
    }

    /**
     * Build calendar data for entire month
     */
    private function buildCalendarData($userId, $month, $year)
    {
        $data = [];

        // Get all progres harian for this month from database
        $progresHarianList = ProgresHarian::where('user_id', $userId)
            ->whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->get()
            ->groupBy(function($item) {
                return $item->tanggal->format('Y-m-d');
            });

        // Generate dates for the month
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');

            if (isset($progresHarianList[$dateStr])) {
                $entries = $progresHarianList[$dateStr];
                $totalMenit = $entries->sum('durasi_menit');
                $hasEvidence = $entries->where('status_bukti', 'SUDAH_ADA')->count() > 0;
                $countKh = $entries->where('tipe_progres', 'KINERJA_HARIAN')->count();
                $countTla = $entries->where('tipe_progres', 'TUGAS_ATASAN')->count();

                // Status logic: RED (no evidence), YELLOW (< 450 min with evidence), GREEN (>= 450 min + evidence)
                if ($totalMenit > 0) {
                    if (!$hasEvidence) {
                        $status = 'red';
                    } elseif ($totalMenit < 450) {
                        $status = 'yellow';
                    } else {
                        $status = 'green';
                    }
                } else {
                    $status = 'empty';
                }

                $data[$dateStr] = [
                    'status' => $status,
                    'total_menit' => $totalMenit,
                    'has_evidence' => $hasEvidence,
                    'count_kh' => $countKh,
                    'count_tla' => $countTla,
                ];
            } else {
                $data[$dateStr] = [
                    'status' => 'empty',
                    'total_menit' => 0,
                    'has_evidence' => false,
                    'count_kh' => 0,
                    'count_tla' => 0,
                ];
            }
        }

        return $data;
    }

    /**
     * Get progress entries for specific date
     */
    private function getProgressForDate($userId, $date)
    {
        // Get entries from database
        $entries = ProgresHarian::where('user_id', $userId)
            ->whereDate('tanggal', $date)
            ->orderBy('jam_mulai')
            ->get();

        // Calculate totals
        $totalMenit = $entries->sum('durasi_menit');
        $hasEvidence = $entries->where('status_bukti', 'SUDAH_ADA')->count() > 0;
        $sisaMenit = max(0, 450 - $totalMenit);

        // Determine status
        if ($totalMenit > 0) {
            if (!$hasEvidence) {
                $status = 'red';
            } elseif ($totalMenit < 450) {
                $status = 'yellow';
            } else {
                $status = 'green';
            }
        } else {
            $status = 'empty';
        }

        // Transform entries to match expected format for view
        $entriesArray = $entries->map(function($entry) {
            return [
                'id' => $entry->id,
                'tipe_progres' => $entry->tipe_progres,
                'jam_mulai' => Carbon::parse($entry->jam_mulai)->format('H:i'),
                'jam_selesai' => Carbon::parse($entry->jam_selesai)->format('H:i'),
                'durasi_menit' => $entry->durasi_menit,
                'durasi_display' => $entry->durasi_jam,
                'kegiatan' => $entry->tipe_progres === 'KINERJA_HARIAN'
                    ? $entry->rencana_kegiatan_harian
                    : $entry->tugas_atasan,
                'progres' => $entry->progres,
                'satuan' => $entry->satuan,
                'link_bukti' => $entry->bukti_dukung,
                'status_bukti' => $entry->status_bukti,
                'keterangan' => $entry->keterangan,
            ];
        })->toArray();

        return [
            'total_menit' => $totalMenit,
            'total_jam' => $this->formatDuration($totalMenit),
            'sisa_menit' => $sisaMenit,
            'sisa_jam' => $this->formatDuration($sisaMenit),
            'status' => $status,
            'entries' => $entriesArray,
        ];
    }

    /**
     * Show page to choose type (Kinerja Harian or TLA)
     */
    public function pilih()
    {
        return view('asn.harian.pilih');
    }

    /**
     * Show form for Kinerja Harian
     */
    public function formKinerja()
    {
        $asn = Auth::user();
        $tanggal = request('date', now()->format('Y-m-d'));

        // Parse date to get month and year
        $dateObj = Carbon::parse($tanggal);
        $bulan = $dateObj->month;
        $tahun = $dateObj->year;

        // Query Rencana Aksi Bulanan from database
        $rencanaKerja = RencanaAksiBulanan::whereHas('skpTahunanDetail.skpTahunan', function($query) use ($asn, $tahun) {
                $query->where('user_id', $asn->id)
                      ->where('tahun', $tahun)
                      ->where('status', 'DISETUJUI');
            })
            ->with(['skpTahunanDetail.indikatorKinerja'])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotNull('rencana_aksi_bulanan')
            ->where('rencana_aksi_bulanan', '!=', '')
            ->get()
            ->map(function($rencana) {
                return [
                    'id' => $rencana->id,
                    'indikator_kinerja' => $rencana->skpTahunanDetail->indikatorKinerja->nama_indikator ?? '-',
                    'rencana_aksi_bulanan' => $rencana->rencana_aksi_bulanan,
                    'bulan' => $rencana->bulan_nama,
                    'target' => $rencana->target_bulanan . ' ' . ($rencana->satuan_target ?? ''),
                ];
            });

        return view('asn.harian.form-kinerja', [
            'rencanaKerja' => $rencanaKerja,
            'tanggal' => $tanggal,
        ]);
    }

    /**
     * Store Kinerja Harian
     */
    public function storeKinerja(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'tanggal' => 'nullable|date',
            'rencana_kerja_id' => 'nullable|integer|exists:rencana_aksi_bulanan,id',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required|after:jam_mulai',
            'kegiatan_harian' => 'required|string',
            'progres' => 'required|numeric|min:0',
            'satuan' => 'required|string',
            'link_bukti' => 'nullable|url',
            'keterangan' => 'nullable|string',
        ], [
            'jam_selesai.after' => 'Jam selesai harus lebih besar dari jam mulai',
            'rencana_kerja_id.exists' => 'Rencana Aksi Bulanan tidak valid',
        ]);

        $asn = Auth::user();
        $tanggal = $validated['tanggal'] ?? now()->format('Y-m-d');

        // Calculate duration
        $jamMulai = Carbon::parse($tanggal . ' ' . $validated['jam_mulai']);
        $jamSelesai = Carbon::parse($tanggal . ' ' . $validated['jam_selesai']);
        $durasiMenit = $jamSelesai->diffInMinutes($jamMulai);

        // Validate total durasi per hari tidak melebihi 450 menit (7.5 jam)
        $totalDurasiHariIni = ProgresHarian::where('user_id', $asn->id)
            ->whereDate('tanggal', $tanggal)
            ->sum('durasi_menit');

        if (($totalDurasiHariIni + $durasiMenit) > 450) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Total durasi kerja hari ini tidak boleh melebihi 7 jam 30 menit (450 menit).');
        }

        // Determine status_bukti based on link_bukti
        $statusBukti = empty($validated['link_bukti']) ? 'BELUM_ADA' : 'SUDAH_ADA';

        // Insert to database
        ProgresHarian::create([
            'user_id' => $asn->id,
            'rencana_aksi_bulanan_id' => $validated['rencana_kerja_id'] ?? null,
            'tipe_progres' => 'KINERJA_HARIAN',
            'tanggal' => $tanggal,
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'rencana_kegiatan_harian' => $validated['kegiatan_harian'],
            'progres' => $validated['progres'],
            'satuan' => $validated['satuan'],
            'bukti_dukung' => $validated['link_bukti'] ?? null,
            'status_bukti' => $statusBukti,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        // Redirect back to calendar with selected date
        return redirect()->route('asn.harian.index', ['date' => $tanggal])
            ->with('success', 'Kinerja Harian berhasil disimpan!');
    }

    /**
     * Format duration in minutes to "Xj Ym" format
     */
    private function formatDuration($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return "{$hours}j {$mins}m";
    }

    /**
     * Show form for Tugas Langsung Atasan
     */
    public function formTla()
    {
        $asn = Auth::user();
        $tanggal = request('date', now()->format('Y-m-d'));

        return view('asn.harian.form-tla', [
            'tanggal' => $tanggal,
        ]);
    }

    /**
     * Store Tugas Langsung Atasan
     */
    public function storeTla(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'tanggal' => 'nullable|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required|after:jam_mulai',
            'tugas_langsung_atasan' => 'required|string',
            'link_bukti' => 'nullable|url',
            'keterangan' => 'nullable|string',
        ], [
            'jam_selesai.after' => 'Jam selesai harus lebih besar dari jam mulai',
        ]);

        $asn = Auth::user();
        $tanggal = $validated['tanggal'] ?? now()->format('Y-m-d');

        // Calculate duration
        $jamMulai = Carbon::parse($tanggal . ' ' . $validated['jam_mulai']);
        $jamSelesai = Carbon::parse($tanggal . ' ' . $validated['jam_selesai']);
        $durasiMenit = $jamSelesai->diffInMinutes($jamMulai);

        // Validate total durasi per hari tidak melebihi 450 menit (7.5 jam)
        $totalDurasiHariIni = ProgresHarian::where('user_id', $asn->id)
            ->whereDate('tanggal', $tanggal)
            ->sum('durasi_menit');

        if (($totalDurasiHariIni + $durasiMenit) > 450) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Total durasi kerja hari ini tidak boleh melebihi 7 jam 30 menit (450 menit).');
        }

        // Determine status_bukti based on link_bukti
        $statusBukti = empty($validated['link_bukti']) ? 'BELUM_ADA' : 'SUDAH_ADA';

        // Insert to database (TLA doesn't have rencana_aksi_bulanan_id)
        ProgresHarian::create([
            'user_id' => $asn->id,
            'rencana_aksi_bulanan_id' => null, // TLA tidak terkait rencana aksi bulanan
            'tipe_progres' => 'TUGAS_ATASAN',
            'tugas_atasan' => $validated['tugas_langsung_atasan'],
            'tanggal' => $tanggal,
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'progres' => 1, // Default 1 untuk TLA
            'satuan' => 'tugas', // Default satuan untuk TLA
            'bukti_dukung' => $validated['link_bukti'] ?? null,
            'status_bukti' => $statusBukti,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        // Redirect back to calendar with selected date
        return redirect()->route('asn.harian.index', ['date' => $tanggal])
            ->with('success', 'Tugas Langsung Atasan berhasil disimpan!');
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $asn = Auth::user();
        $date = request('date', now()->format('Y-m-d'));

        // Get progres harian from database
        $progresHarian = ProgresHarian::where('id', $id)
            ->where('user_id', $asn->id)
            ->first();

        if (!$progresHarian) {
            return redirect()->route('asn.harian.index', ['date' => $date])
                ->with('error', 'Progres harian tidak ditemukan');
        }

        // Parse date to get month and year
        $dateObj = Carbon::parse($progresHarian->tanggal);
        $bulan = $dateObj->month;
        $tahun = $dateObj->year;

        // Route ke view yang berbeda berdasarkan tipe_progres
        if ($progresHarian->tipe_progres === 'TUGAS_ATASAN') {
            // TLA: Tidak perlu rencana aksi
            return view('asn.harian.edit-tla', [
                'entry' => $progresHarian,
                'date' => $progresHarian->tanggal->format('Y-m-d'),
            ]);
        }

        // KINERJA_HARIAN: Query Rencana Aksi Bulanan for dropdown
        $rencanaKerja = RencanaAksiBulanan::whereHas('skpTahunanDetail.skpTahunan', function($query) use ($asn, $tahun) {
                $query->where('user_id', $asn->id)
                      ->where('tahun', $tahun)
                      ->where('status', 'DISETUJUI');
            })
            ->with(['skpTahunanDetail.indikatorKinerja'])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereNotNull('rencana_aksi_bulanan')
            ->where('rencana_aksi_bulanan', '!=', '')
            ->get()
            ->map(function($rencana) {
                return [
                    'id' => $rencana->id,
                    'indikator_kinerja' => $rencana->skpTahunanDetail->indikatorKinerja->nama_indikator ?? '-',
                    'rencana_aksi_bulanan' => $rencana->rencana_aksi_bulanan,
                    'bulan' => $rencana->bulan_nama,
                ];
            });

        return view('asn.harian.edit', [
            'entry' => $progresHarian,
            'date' => $progresHarian->tanggal->format('Y-m-d'),
            'rencanaKerja' => $rencanaKerja,
        ]);
    }

    /**
     * Update kinerja harian
     */
    public function update(Request $request, $id)
    {
        $asn = Auth::user();

        // Find progres harian
        $progresHarian = ProgresHarian::where('id', $id)
            ->where('user_id', $asn->id)
            ->first();

        if (!$progresHarian) {
            return redirect()->route('asn.harian.index')
                ->with('error', 'Progres harian tidak ditemukan');
        }

        // Validate based on tipe_progres
        if ($progresHarian->tipe_progres === 'KINERJA_HARIAN') {
            $validated = $request->validate([
                'rencana_kerja_id' => 'nullable|integer|exists:rencana_aksi_bulanan,id',
                'jam_mulai' => 'required',
                'jam_selesai' => 'required|after:jam_mulai',
                'kegiatan_harian' => 'required|string',
                'progres' => 'required|numeric|min:0',
                'satuan' => 'required|string',
                'link_bukti' => 'nullable|url',
                'keterangan' => 'nullable|string',
            ], [
                'jam_selesai.after' => 'Jam selesai harus lebih besar dari jam mulai',
            ]);
        } else {
            // TUGAS_ATASAN
            $validated = $request->validate([
                'jam_mulai' => 'required',
                'jam_selesai' => 'required|after:jam_mulai',
                'tugas_langsung_atasan' => 'required|string',
                'link_bukti' => 'nullable|url',
                'keterangan' => 'nullable|string',
            ], [
                'jam_selesai.after' => 'Jam selesai harus lebih besar dari jam mulai',
            ]);
        }

        // Calculate duration
        $tanggal = $progresHarian->tanggal->format('Y-m-d');
        $jamMulai = Carbon::parse($tanggal . ' ' . $validated['jam_mulai']);
        $jamSelesai = Carbon::parse($tanggal . ' ' . $validated['jam_selesai']);
        $durasiMenit = $jamSelesai->diffInMinutes($jamMulai);

        // Validate total durasi per hari (exclude current record)
        $totalDurasiHariIni = ProgresHarian::where('user_id', $asn->id)
            ->whereDate('tanggal', $tanggal)
            ->where('id', '!=', $id)
            ->sum('durasi_menit');

        if (($totalDurasiHariIni + $durasiMenit) > 450) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Total durasi kerja hari ini tidak boleh melebihi 7 jam 30 menit (450 menit).');
        }

        // Determine status_bukti
        $statusBukti = empty($validated['link_bukti']) ? 'BELUM_ADA' : 'SUDAH_ADA';

        // Update data
        if ($progresHarian->tipe_progres === 'KINERJA_HARIAN') {
            $progresHarian->update([
                'rencana_aksi_bulanan_id' => $validated['rencana_kerja_id'] ?? null,
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $validated['jam_selesai'],
                'rencana_kegiatan_harian' => $validated['kegiatan_harian'],
                'progres' => $validated['progres'],
                'satuan' => $validated['satuan'],
                'bukti_dukung' => $validated['link_bukti'] ?? null,
                'status_bukti' => $statusBukti,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        } else {
            $progresHarian->update([
                'jam_mulai' => $validated['jam_mulai'],
                'jam_selesai' => $validated['jam_selesai'],
                'tugas_atasan' => $validated['tugas_langsung_atasan'],
                'bukti_dukung' => $validated['link_bukti'] ?? null,
                'status_bukti' => $statusBukti,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);
        }

        return redirect()->route('asn.harian.index', ['date' => $tanggal])
            ->with('success', 'Progres harian berhasil diupdate!');
    }

    /**
     * Delete kinerja harian entry
     */
    public function destroy($id)
    {
        $asn = Auth::user();
        $date = request('date', now()->format('Y-m-d'));

        // Find and delete from database
        $progresHarian = ProgresHarian::where('id', $id)
            ->where('user_id', $asn->id)
            ->first();

        if ($progresHarian) {
            $progresHarian->delete();

            return redirect()->route('asn.harian.index', ['date' => $date])
                ->with('success', 'Progres harian berhasil dihapus!');
        }

        return redirect()->route('asn.harian.index', ['date' => $date])
            ->with('error', 'Progres harian tidak ditemukan');
    }
}
