<?php

namespace App\Http\Controllers\Api\Asn;

use App\Http\Controllers\Controller;
use App\Models\ProgresHarian;
use App\Models\RencanaAksiBulanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Progres Harian Controller
 *
 * Managed by: ASN / PPPK
 * Purpose: Mengelola progres harian berbasis Rencana Aksi Bulanan
 * Features:
 * - Input jam mulai & jam selesai (validasi max 7 jam 30 menit per hari)
 * - Bukti dukung wajib (Google Drive link)
 * - Status visual (bar merah jika belum ada bukti)
 */
class ProgresHarianController extends Controller
{
    /**
     * Get all progres harian for authenticated ASN
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $query = ProgresHarian::with([
                'rencanaAksiBulanan.skpTahunanDetail.rhkPimpinan',
                'rencanaAksiBulanan.skpTahunanDetail.skpTahunan'
            ])->whereHas('rencanaAksiBulanan.skpTahunanDetail.skpTahunan', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

            // Filter by tanggal (single date)
            if ($request->has('tanggal')) {
                $query->whereDate('tanggal', $request->tanggal);
            }

            // Filter by date range
            if ($request->has('tanggal_mulai') && $request->has('tanggal_akhir')) {
                $query->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_akhir]);
            }

            // Filter by bulan & tahun
            if ($request->has('bulan') && $request->has('tahun')) {
                $query->whereMonth('tanggal', $request->bulan)
                    ->whereYear('tanggal', $request->tahun);
            }

            // Filter by rencana aksi bulanan
            if ($request->has('rencana_aksi_bulanan_id')) {
                $query->where('rencana_aksi_bulanan_id', $request->rencana_aksi_bulanan_id);
            }

            // Filter by status bukti
            if ($request->has('status_bukti')) {
                $query->where('status_bukti', $request->status_bukti);
            }

            $progresList = $query->orderBy('tanggal', 'desc')
                ->orderBy('jam_mulai', 'desc')
                ->get();

            return response()->json($progresList);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch progres harian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get progres harian for specific date (calendar view)
     */
    public function getByDate(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'tanggal' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // ✅ DUAL MODE QUERY: Get both KINERJA_HARIAN and TUGAS_ATASAN
            $progres = ProgresHarian::with([
                'rencanaAksiBulanan.skpTahunanDetail.rhkPimpinan'
            ])->where(function ($query) use ($user) {
                // TUGAS_ATASAN: Filter by user_id directly
                $query->where(function ($q) use ($user) {
                    $q->where('tipe_progres', 'TUGAS_ATASAN')
                      ->where('user_id', $user->id);
                })
                // KINERJA_HARIAN: Filter by rencana aksi ownership
                ->orWhere(function ($q) use ($user) {
                    $q->where('tipe_progres', 'KINERJA_HARIAN')
                      ->whereHas('rencanaAksiBulanan.skpTahunanDetail.skpTahunan', function ($subQ) use ($user) {
                          $subQ->where('user_id', $user->id);
                      });
                });
            })->whereDate('tanggal', $request->tanggal)
                ->orderBy('jam_mulai')
                ->get();

            // Calculate total durasi for the day
            $totalDurasi = $progres->sum('durasi_menit');
            $sisaDurasi = 450 - $totalDurasi; // 7 jam 30 menit = 450 menit

            return response()->json([
                'tanggal' => $request->tanggal,
                'progres_list' => $progres,
                'total_durasi_menit' => $totalDurasi,
                'total_durasi_jam' => round($totalDurasi / 60, 2),
                'sisa_durasi_menit' => $sisaDurasi,
                'is_full' => $totalDurasi >= 450,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch progres harian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detail progres harian
     */
    public function show($id)
    {
        try {
            $progres = ProgresHarian::with([
                'rencanaAksiBulanan.skpTahunanDetail.rhkPimpinan.sasaranKegiatan',
                'rencanaAksiBulanan.skpTahunanDetail.skpTahunan'
            ])->find($id);

            if (!$progres) {
                return response()->json([
                    'message' => 'Progres harian tidak ditemukan'
                ], 404);
            }

            return response()->json($progres);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch progres harian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new progres harian
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            // ✅ DUAL MODE VALIDATION
            $tipeProgres = $request->tipe_progres ?? 'KINERJA_HARIAN';

            if ($tipeProgres === 'TUGAS_ATASAN') {
                // ✅ PERUBAHAN: bukti_dukung OPSIONAL (nullable)
                // ASN boleh simpan progres tanpa bukti, wajib diisi sebelum 23:59
                $validator = Validator::make($request->all(), [
                    'tipe_progres' => 'required|in:TUGAS_ATASAN',
                    'tanggal' => 'required|date',
                    'jam_mulai' => 'required|date_format:H:i',
                    'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
                    'tugas_atasan' => 'required|string|max:1000',
                    'bukti_dukung' => 'nullable|url|max:500', // CHANGED: required → nullable
                    'keterangan' => 'sometimes|nullable|string|max:500',
                ]);
            } else {
                // ✅ PERUBAHAN: bukti_dukung OPSIONAL (nullable)
                // ASN boleh simpan progres tanpa bukti, wajib diisi sebelum 23:59
                $validator = Validator::make($request->all(), [
                    'tipe_progres' => 'sometimes|in:KINERJA_HARIAN',
                    'rencana_aksi_bulanan_id' => 'required|exists:rencana_aksi_bulanan,id',
                    'tanggal' => 'required|date',
                    'jam_mulai' => 'required|date_format:H:i',
                    'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
                    'rencana_kegiatan_harian' => 'required|string|max:1000',
                    'progres' => 'required|integer|min:0',
                    'satuan' => 'required|string|max:50',
                    'bukti_dukung' => 'nullable|url|max:500', // CHANGED: required → nullable
                    'keterangan' => 'sometimes|nullable|string|max:500',
                ]);
            }

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validasi ownership HANYA untuk KINERJA_HARIAN
            if ($tipeProgres === 'KINERJA_HARIAN') {
                $rencanaAksi = RencanaAksiBulanan::with('skpTahunanDetail.skpTahunan')
                    ->find($request->rencana_aksi_bulanan_id);

                if (!$rencanaAksi) {
                    return response()->json([
                        'message' => 'Rencana aksi bulanan tidak ditemukan'
                    ], 404);
                }

                if ($rencanaAksi->skpTahunanDetail->skpTahunan->user_id !== $user->id) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki akses ke rencana aksi ini'
                    ], 403);
                }

                if (!$rencanaAksi->isFilled()) {
                    return response()->json([
                        'message' => 'Rencana aksi bulanan harus diisi terlebih dahulu'
                    ], 422);
                }
            }

            // ✅ VALIDASI AUTO LOCK: Tidak bisa input jika tanggal sudah lewat (H+1)
            $tanggalInput = Carbon::parse($request->tanggal);
            $now = Carbon::now();

            if ($tanggalInput->lt($now->copy()->startOfDay())) {
                return response()->json([
                    'message' => 'Tidak dapat menambah progres untuk tanggal yang sudah lewat. Progres harian hanya bisa diinput pada hari yang sama (sebelum pukul 23:59).'
                ], 422);
            }

            // Hitung durasi
            $start = Carbon::parse($request->tanggal . ' ' . $request->jam_mulai);
            $end = Carbon::parse($request->tanggal . ' ' . $request->jam_selesai);
            $durasiMenit = $start->diffInMinutes($end);

            // ✅ VALIDASI DURASI: Total per hari tidak boleh melebihi 450 menit (per user)
            // Hitung total existing progres untuk USER INI di tanggal tersebut
            $totalExisting = ProgresHarian::where(function ($query) use ($user, $request, $tipeProgres) {
                // Filter by user: TUGAS_ATASAN by user_id, KINERJA_HARIAN by rencana aksi ownership
                $query->where(function ($q) use ($user) {
                    $q->where('tipe_progres', 'TUGAS_ATASAN')
                      ->where('user_id', $user->id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('tipe_progres', 'KINERJA_HARIAN')
                      ->whereHas('rencanaAksiBulanan.skpTahunanDetail.skpTahunan', function ($subQ) use ($user) {
                          $subQ->where('user_id', $user->id);
                      });
                });
            })
            ->whereDate('tanggal', $request->tanggal)
            ->sum('durasi_menit') ?? 0;

            $totalBaru = $totalExisting + $durasiMenit;

            if ($totalBaru > 450) {
                $sisa = 450 - $totalExisting;

                return response()->json([
                    'message' => 'Total waktu kerja harian tidak boleh melebihi 7 jam 30 menit (450 menit)',
                    'total_existing_menit' => $totalExisting,
                    'sisa_menit' => $sisa,
                    'durasi_input_menit' => $durasiMenit,
                ], 422);
            }

            // ✅ CREATE PROGRES - DUAL MODE
            $createData = [
                'user_id' => $user->id, // Track ownership untuk TUGAS_ATASAN
                'tipe_progres' => $tipeProgres,
                'tanggal' => $request->tanggal,
                'jam_mulai' => $request->jam_mulai,
                'jam_selesai' => $request->jam_selesai,
                'bukti_dukung' => $request->bukti_dukung,
                'status_bukti' => $request->bukti_dukung ? 'SUDAH_ADA' : 'BELUM_ADA',
                'keterangan' => $request->keterangan,
            ];

            if ($tipeProgres === 'TUGAS_ATASAN') {
                // Tugas Langsung Atasan - No rencana aksi fields
                $createData['tugas_atasan'] = $request->tugas_atasan;
                $createData['rencana_aksi_bulanan_id'] = null;
                $createData['rencana_kegiatan_harian'] = null;
                $createData['progres'] = null;
                $createData['satuan'] = null;
            } else {
                // Kinerja Harian - No tugas atasan
                $createData['rencana_aksi_bulanan_id'] = $request->rencana_aksi_bulanan_id;
                $createData['rencana_kegiatan_harian'] = $request->rencana_kegiatan_harian;
                $createData['progres'] = $request->progres;
                $createData['satuan'] = $request->satuan;
                $createData['tugas_atasan'] = null;
            }

            $progres = ProgresHarian::create($createData);

            // Trigger update realisasi bulanan (only for KINERJA_HARIAN)
            if ($tipeProgres === 'KINERJA_HARIAN') {
                $progres->load(['rencanaAksiBulanan.skpTahunanDetail.rhkPimpinan']);
            }

            return response()->json([
                'message' => 'Progres harian berhasil ditambahkan',
                'data' => $progres
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating progres harian:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'message' => 'Failed to create progres harian',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
            ], 500);
        }
    }

    /**
     * Update progres harian
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            $progres = ProgresHarian::with([
                'rencanaAksiBulanan.skpTahunanDetail.skpTahunan'
            ])->find($id);

            if (!$progres) {
                return response()->json([
                    'message' => 'Progres harian tidak ditemukan'
                ], 404);
            }

            // Check ownership - DUAL MODE (hanya check untuk KINERJA_HARIAN)
            if ($progres->tipe_progres === 'KINERJA_HARIAN') {
                if ($progres->rencanaAksiBulanan->skpTahunanDetail->skpTahunan->user_id !== $user->id) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki akses ke progres harian ini'
                    ], 403);
                }
            }

            // ✅ VALIDASI AUTO LOCK: Tidak bisa edit jika tanggal sudah lewat (H+1)
            $tanggalProgres = Carbon::parse($progres->tanggal);
            $now = Carbon::now();

            if ($tanggalProgres->lt($now->copy()->startOfDay())) {
                return response()->json([
                    'message' => 'Tidak dapat mengubah progres untuk tanggal yang sudah lewat. Progres harian hanya bisa diedit pada hari yang sama (sebelum pukul 23:59).'
                ], 422);
            }

            // ✅ DUAL MODE VALIDATION
            if ($progres->tipe_progres === 'TUGAS_ATASAN') {
                $validator = Validator::make($request->all(), [
                    'tanggal' => 'sometimes|date',
                    'jam_mulai' => 'sometimes|date_format:H:i',
                    'jam_selesai' => 'sometimes|date_format:H:i',
                    'tugas_atasan' => 'sometimes|string|max:1000',
                    'bukti_dukung' => 'sometimes|url|max:500',
                    'keterangan' => 'sometimes|nullable|string|max:500',
                ]);
            } else {
                $validator = Validator::make($request->all(), [
                    'tanggal' => 'sometimes|date',
                    'jam_mulai' => 'sometimes|date_format:H:i',
                    'jam_selesai' => 'sometimes|date_format:H:i',
                    'rencana_kegiatan_harian' => 'sometimes|string|max:1000',
                    'progres' => 'sometimes|integer|min:0',
                    'satuan' => 'sometimes|string|max:50',
                    'bukti_dukung' => 'sometimes|url|max:500',
                    'keterangan' => 'sometimes|nullable|string|max:500',
                ]);
            }

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validasi durasi jika jam diubah
            if ($request->has('jam_mulai') || $request->has('jam_selesai') || $request->has('tanggal')) {
                $tanggal = $request->tanggal ?? $progres->tanggal;
                $jamMulai = $request->jam_mulai ?? $progres->jam_mulai;
                $jamSelesai = $request->jam_selesai ?? $progres->jam_selesai;

                $start = Carbon::parse($tanggal . ' ' . $jamMulai);
                $end = Carbon::parse($tanggal . ' ' . $jamSelesai);

                if ($end <= $start) {
                    return response()->json([
                        'message' => 'Jam selesai harus setelah jam mulai'
                    ], 422);
                }

                $durasiMenit = $start->diffInMinutes($end);

                // ✅ FIX: Validasi total durasi 450 menit per USER (exclude current record)
                $totalExisting = ProgresHarian::where(function ($query) use ($user) {
                    // Filter by user: TUGAS_ATASAN by user_id, KINERJA_HARIAN by rencana aksi ownership
                    $query->where(function ($q) use ($user) {
                        $q->where('tipe_progres', 'TUGAS_ATASAN')
                          ->where('user_id', $user->id);
                    })
                    ->orWhere(function ($q) use ($user) {
                        $q->where('tipe_progres', 'KINERJA_HARIAN')
                          ->whereHas('rencanaAksiBulanan.skpTahunanDetail.skpTahunan', function ($subQ) use ($user) {
                              $subQ->where('user_id', $user->id);
                          });
                    });
                })
                ->whereDate('tanggal', $tanggal)
                ->where('id', '!=', $id)  // Exclude current record
                ->sum('durasi_menit') ?? 0;

                $totalBaru = $totalExisting + $durasiMenit;

                if ($totalBaru > 450) {
                    $sisa = 450 - $totalExisting;
                    return response()->json([
                        'message' => 'Total waktu kerja harian tidak boleh melebihi 7 jam 30 menit (450 menit)',
                        'total_existing_menit' => $totalExisting,
                        'sisa_menit' => $sisa,
                        'durasi_input_menit' => $durasiMenit,
                    ], 422);
                }
            }

            // ✅ DUAL MODE UPDATE
            if ($progres->tipe_progres === 'TUGAS_ATASAN') {
                $updateData = $request->only([
                    'tanggal',
                    'jam_mulai',
                    'jam_selesai',
                    'tugas_atasan',
                    'bukti_dukung',
                    'keterangan',
                ]);
            } else {
                $updateData = $request->only([
                    'tanggal',
                    'jam_mulai',
                    'jam_selesai',
                    'rencana_kegiatan_harian',
                    'progres',
                    'satuan',
                    'bukti_dukung',
                    'keterangan',
                ]);
            }

            // Update status bukti
            if ($request->has('bukti_dukung')) {
                $updateData['status_bukti'] = $request->bukti_dukung ? 'SUDAH_ADA' : 'BELUM_ADA';
            }

            $progres->update($updateData);

            // Load relationship only for KINERJA_HARIAN
            if ($progres->tipe_progres === 'KINERJA_HARIAN') {
                $progres->load(['rencanaAksiBulanan.skpTahunanDetail.rhkPimpinan']);
            }

            return response()->json([
                'message' => 'Progres harian berhasil diperbarui',
                'data' => $progres
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update progres harian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ CRITICAL FIX: Update HANYA bukti dukung (FAST & SAFE)
     *
     * Endpoint ini khusus untuk update link bukti dukung.
     * ❌ TIDAK validasi jam kerja
     * ❌ TIDAK hitung durasi
     * ❌ TIDAK trigger observer updateRealisasi()
     * ✅ HANYA update bukti_dukung + status_bukti
     *
     * Performance: < 50ms (optimized untuk 250 ASN concurrent)
     */
    public function updateBuktiDukung(Request $request, $id)
    {
        try {
            $user = $request->user();

            // ✅ LIGHTWEIGHT QUERY: No eager loading, minimal overhead
            $progres = ProgresHarian::find($id);

            if (!$progres) {
                return response()->json([
                    'message' => 'Progres harian tidak ditemukan'
                ], 404);
            }

            // ✅ DUAL MODE OWNERSHIP CHECK
            if ($progres->tipe_progres === 'TUGAS_ATASAN') {
                // TUGAS_ATASAN: Check user_id directly
                if ($progres->user_id !== $user->id) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki akses ke progres harian ini'
                    ], 403);
                }
            } else {
                // KINERJA_HARIAN: Check via rencana aksi ownership
                $progres->load('rencanaAksiBulanan.skpTahunanDetail.skpTahunan');

                if (!$progres->rencanaAksiBulanan ||
                    !$progres->rencanaAksiBulanan->skpTahunanDetail ||
                    !$progres->rencanaAksiBulanan->skpTahunanDetail->skpTahunan ||
                    $progres->rencanaAksiBulanan->skpTahunanDetail->skpTahunan->user_id !== $user->id) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki akses ke progres harian ini'
                    ], 403);
                }
            }

            // ✅ VALIDATION: Only bukti_dukung
            $validator = Validator::make($request->all(), [
                'bukti_dukung' => 'required|url|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // ✅ CRITICAL: Update ONLY 2 fields, NO observer trigger
            $progres->bukti_dukung = $request->bukti_dukung;
            $progres->status_bukti = 'SUDAH_ADA';

            // ✅ saveQuietly() = Skip ALL observers (no updateRealisasi trigger)
            $progres->saveQuietly();

            // ✅ LIGHTWEIGHT RESPONSE: No heavy relationships
            return response()->json([
                'message' => 'Bukti dukung berhasil diperbarui',
                'data' => [
                    'id' => $progres->id,
                    'bukti_dukung' => $progres->bukti_dukung,
                    'status_bukti' => $progres->status_bukti,
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error updating bukti dukung:', [
                'progres_id' => $id,
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to update bukti dukung',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete progres harian
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();

            $progres = ProgresHarian::with([
                'rencanaAksiBulanan.skpTahunanDetail.skpTahunan'
            ])->find($id);

            if (!$progres) {
                return response()->json([
                    'message' => 'Progres harian tidak ditemukan'
                ], 404);
            }

            // ✅ DUAL MODE: Check ownership
            if ($progres->tipe_progres === 'TUGAS_ATASAN') {
                // For TUGAS_ATASAN, check user_id directly
                if ($progres->user_id !== $user->id) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki akses ke progres harian ini'
                    ], 403);
                }
            } else {
                // For KINERJA_HARIAN, check via rencana aksi
                if ($progres->rencanaAksiBulanan->skpTahunanDetail->skpTahunan->user_id !== $user->id) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki akses ke progres harian ini'
                    ], 403);
                }
            }

            // ✅ VALIDASI AUTO LOCK: Tidak bisa hapus jika tanggal sudah lewat (H+1)
            $tanggalProgres = Carbon::parse($progres->tanggal);
            $now = Carbon::now();

            if ($tanggalProgres->lt($now->copy()->startOfDay())) {
                return response()->json([
                    'message' => 'Tidak dapat menghapus progres untuk tanggal yang sudah lewat. Progres harian hanya bisa dihapus pada hari yang sama (sebelum pukul 23:59).'
                ], 422);
            }

            $progres->delete();

            // Trigger update realisasi bulanan (handled by model event)

            return response()->json([
                'message' => 'Progres harian berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete progres harian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get calendar data for month
     */
    public function getCalendar(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'bulan' => 'required|integer|min:1|max:12',
                'tahun' => 'required|integer|min:2020',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $bulan = $request->bulan;
            $tahun = $request->tahun;

            // ✅ DUAL MODE QUERY: Get both KINERJA_HARIAN and TUGAS_ATASAN for the month
            $progresList = ProgresHarian::with([
                'rencanaAksiBulanan.skpTahunanDetail.rhkPimpinan'
            ])->where(function ($query) use ($user) {
                // TUGAS_ATASAN: Filter by user_id directly
                $query->where(function ($q) use ($user) {
                    $q->where('tipe_progres', 'TUGAS_ATASAN')
                      ->where('user_id', $user->id);
                })
                // KINERJA_HARIAN: Filter by rencana aksi ownership
                ->orWhere(function ($q) use ($user) {
                    $q->where('tipe_progres', 'KINERJA_HARIAN')
                      ->whereHas('rencanaAksiBulanan.skpTahunanDetail.skpTahunan', function ($subQ) use ($user) {
                          $subQ->where('user_id', $user->id);
                      });
                });
            })->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->get();

            // Group by date
            $now = Carbon::now();
            $calendarData = $progresList->groupBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m-d');
            })->map(function ($items, $date) use ($now) {
                $totalDurasi = $items->sum('durasi_menit');
                $hasAllBukti = $items->every(function ($item) {
                    return $item->hasBukti();
                });

                $tanggalProgres = Carbon::parse($date);
                $isToday = $tanggalProgres->isToday();
                $isPast = $tanggalProgres->lt($now->copy()->startOfDay());

                // ✅ AUTO LOCK LOGIC
                // Locked jika:
                // 1. Tanggal sudah lewat (H+1 atau lebih)
                // 2. DAN (belum upload ATAU belum cukup 7.5 jam)
                $isLocked = $isPast && (!$hasAllBukti || $totalDurasi < 450);

                // ✅ STATUS VISUAL
                // 🔴 merah = Belum upload sama sekali
                // 🟡 kuning = Sudah upload, tapi < 7 jam 30 menit
                // 🟢 hijau = Sudah upload & >= 7 jam 30 menit
                // ⚫ hitam = LOCKED (H+1 & tidak memenuhi syarat)
                $statusVisual = 'merah'; // Default

                if ($isLocked) {
                    $statusVisual = 'hitam'; // LOCKED
                } elseif (!$hasAllBukti) {
                    $statusVisual = 'merah'; // Belum upload
                } elseif ($totalDurasi >= 450) {
                    $statusVisual = 'hijau'; // Cukup jam kerja
                } else {
                    $statusVisual = 'kuning'; // Kurang jam kerja
                }

                return [
                    'tanggal' => $date,
                    'jumlah_progres' => $items->count(),
                    'total_durasi_menit' => $totalDurasi,
                    'total_durasi_jam' => round($totalDurasi / 60, 2),
                    'is_full' => $totalDurasi >= 450,
                    'has_all_bukti' => $hasAllBukti,
                    'status_visual' => $statusVisual,
                    'is_locked' => $isLocked,
                    'is_today' => $isToday,
                    'can_edit' => !$isLocked, // Bisa edit hanya jika tidak locked
                ];
            })->values();

            return response()->json([
                'bulan' => $bulan,
                'tahun' => $tahun,
                'calendar_data' => $calendarData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get calendar data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
