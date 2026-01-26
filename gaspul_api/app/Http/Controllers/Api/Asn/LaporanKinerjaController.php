<?php

namespace App\Http\Controllers\Api\Asn;

use App\Http\Controllers\Controller;
use App\Models\ProgresHarian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * ✅ LAPORAN KINERJA ASN (PERSONAL) - Self Monitoring Dashboard
 *
 * Fitur Utama:
 * 1. ASN melihat laporan kinerjanya sendiri (LKH + TLA)
 * 2. Filter harian, mingguan, bulanan
 * 3. Cetak laporan KH dan TLA milik sendiri
 * 4. STRICT ACCESS CONTROL - hanya data ASN yang login
 *
 * Security: User ID dari auth()->id(), TIDAK dari request parameter
 * Performance: Optimized dengan composite indexes
 */
class LaporanKinerjaController extends Controller
{
    /**
     * Get biodata ASN yang login
     */
    public function getBiodata(Request $request)
    {
        try {
            $asn = $request->user();

            return response()->json([
                'biodata' => [
                    'id' => $asn->id,
                    'nama' => $asn->name,
                    'nip' => $asn->nip,
                    'jabatan' => $asn->jabatan,
                    'unit_kerja' => $asn->unit_kerja,
                    'email' => $asn->email,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get biodata',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ENDPOINT UTAMA: Get laporan kinerja ASN dengan filter
     *
     * Filter:
     * - tanggal: Single date (format: Y-m-d)
     * - tanggal_mulai & tanggal_akhir: Date range
     * - bulan & tahun: Monthly filter
     * - mode: 'harian', 'mingguan', 'bulanan'
     *
     * Output: List progres ASN (LKH + TLA)
     *
     * SECURITY: User ID OTOMATIS dari auth()->id()
     */
    public function getLaporanKinerja(Request $request)
    {
        try {
            $asn = $request->user();
            $userId = $asn->id; // ✅ STRICT: User ID dari token, bukan dari request

            // ✅ STEP 1: Parse filter parameters
            $mode = $request->input('mode', 'harian'); // harian, mingguan, bulanan
            $bulan = $request->input('bulan', date('n'));
            $tahun = $request->input('tahun', date('Y'));
            $tanggal = $request->input('tanggal'); // Single date
            $tanggalMulai = $request->input('tanggal_mulai');
            $tanggalAkhir = $request->input('tanggal_akhir');

            // Determine date range based on mode
            if ($tanggal) {
                // Single date mode
                $startDate = $tanggal;
                $endDate = $tanggal;
            } elseif ($tanggalMulai && $tanggalAkhir) {
                // Custom range mode
                $startDate = $tanggalMulai;
                $endDate = $tanggalAkhir;
            } elseif ($mode === 'bulanan') {
                // Monthly mode
                $startDate = Carbon::create($tahun, $bulan, 1)->format('Y-m-d');
                $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth()->format('Y-m-d');
            } elseif ($mode === 'mingguan') {
                // Weekly mode (current week)
                $startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
                $endDate = Carbon::now()->endOfWeek()->format('Y-m-d');
            } else {
                // Default: today
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d');
            }

            // ✅ STEP 2: Get progres harian ASN (OPTIMIZED with indexes)
            // Using idx_tipe_user_tanggal composite index
            $progresData = ProgresHarian::select([
                    'id',
                    'user_id',
                    'tipe_progres',
                    'tanggal',
                    'jam_mulai',
                    'jam_selesai',
                    'durasi_menit',
                    'rencana_kegiatan_harian',
                    'tugas_atasan',
                    'progres',
                    'satuan',
                    'bukti_dukung',
                    'status_bukti',
                    'keterangan',
                ])
                ->where('user_id', $userId) // ✅ STRICT: Hanya data ASN yang login
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderBy('tanggal')
                ->orderBy('jam_mulai')
                ->get();

            // ✅ STEP 3: Format kegiatan list
            $kegiatanList = $progresData->map(function ($item) {
                return [
                    'id' => $item->id,
                    'tipe_progres' => $item->tipe_progres,
                    'tanggal' => $item->tanggal,
                    'jam_mulai' => $item->jam_mulai,
                    'jam_selesai' => $item->jam_selesai,
                    'durasi_menit' => $item->durasi_menit,
                    'durasi_jam' => round($item->durasi_menit / 60, 2),
                    'kegiatan' => $item->tipe_progres === 'KINERJA_HARIAN'
                        ? $item->rencana_kegiatan_harian
                        : $item->tugas_atasan,
                    'realisasi' => $item->progres ?? '-',
                    'satuan' => $item->satuan ?? '-',
                    'keterangan' => $item->tipe_progres === 'KINERJA_HARIAN' ? 'LKH' : 'TLA',
                    'status_bukti' => $item->status_bukti,
                    'bukti_dukung' => $item->bukti_dukung,
                ];
            })->toArray();

            $totalDurasi = $progresData->sum('durasi_menit');

            // ✅ STEP 4: Calculate summary statistics
            $totalKH = $progresData->where('tipe_progres', 'KINERJA_HARIAN')->count();
            $totalTLA = $progresData->where('tipe_progres', 'TUGAS_ATASAN')->count();

            return response()->json([
                'message' => 'Data laporan kinerja berhasil diambil',
                'filter' => [
                    'mode' => $mode,
                    'tanggal_mulai' => $startDate,
                    'tanggal_akhir' => $endDate,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                ],
                'data' => [
                    'asn' => [
                        'user_id' => $userId,
                        'nama' => $asn->name,
                        'nip' => $asn->nip,
                        'jabatan' => $asn->jabatan,
                        'unit_kerja' => $asn->unit_kerja,
                    ],
                    'total_kegiatan' => $progresData->count(),
                    'total_durasi_menit' => $totalDurasi,
                    'total_durasi_jam' => round($totalDurasi / 60, 2),
                    'kegiatan_list' => $kegiatanList,
                ],
                'summary' => [
                    'total_kegiatan' => $progresData->count(),
                    'total_kh' => $totalKH,
                    'total_tla' => $totalTLA,
                    'total_durasi_jam' => round($totalDurasi / 60, 2),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting laporan kinerja ASN:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to get laporan kinerja',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ CETAK LAPORAN KH (Kinerja Harian) - ASN Sendiri
     *
     * SECURITY: User ID dari auth()->id()
     */
    public function cetakLaporanKH(Request $request)
    {
        try {
            $asn = $request->user();
            $userId = $asn->id; // ✅ STRICT: User ID dari token

            $tanggalMulai = $request->input('tanggal_mulai', date('Y-m-d'));
            $tanggalAkhir = $request->input('tanggal_akhir', date('Y-m-d'));

            // Get Kinerja Harian only
            $progres = ProgresHarian::where('user_id', $userId)
                ->where('tipe_progres', 'KINERJA_HARIAN')
                ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                ->orderBy('tanggal')
                ->orderBy('jam_mulai')
                ->get();

            return response()->json([
                'message' => 'Data laporan KH',
                'data' => [
                    'asn' => [
                        'nama' => $asn->name,
                        'nip' => $asn->nip,
                        'jabatan' => $asn->jabatan,
                        'unit_kerja' => $asn->unit_kerja,
                    ],
                    'periode' => [
                        'tanggal_mulai' => $tanggalMulai,
                        'tanggal_akhir' => $tanggalAkhir,
                    ],
                    'progres' => $progres->map(function ($item) {
                        return [
                            'tanggal' => $item->tanggal,
                            'jam_mulai' => $item->jam_mulai,
                            'jam_selesai' => $item->jam_selesai,
                            'durasi_menit' => $item->durasi_menit,
                            'kegiatan' => $item->rencana_kegiatan_harian,
                            'realisasi' => $item->progres,
                            'satuan' => $item->satuan,
                            'bukti_dukung' => $item->bukti_dukung,
                        ];
                    }),
                    'total_durasi_jam' => round($progres->sum('durasi_menit') / 60, 2),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate laporan KH',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ CETAK LAPORAN TLA (Tugas Langsung Atasan) - ASN Sendiri
     *
     * SECURITY: User ID dari auth()->id()
     */
    public function cetakLaporanTLA(Request $request)
    {
        try {
            $asn = $request->user();
            $userId = $asn->id; // ✅ STRICT: User ID dari token

            $tanggalMulai = $request->input('tanggal_mulai', date('Y-m-d'));
            $tanggalAkhir = $request->input('tanggal_akhir', date('Y-m-d'));

            // Get Tugas Langsung Atasan only
            $progres = ProgresHarian::where('user_id', $userId)
                ->where('tipe_progres', 'TUGAS_ATASAN')
                ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                ->orderBy('tanggal')
                ->orderBy('jam_mulai')
                ->get();

            return response()->json([
                'message' => 'Data laporan TLA',
                'data' => [
                    'asn' => [
                        'nama' => $asn->name,
                        'nip' => $asn->nip,
                        'jabatan' => $asn->jabatan,
                        'unit_kerja' => $asn->unit_kerja,
                    ],
                    'periode' => [
                        'tanggal_mulai' => $tanggalMulai,
                        'tanggal_akhir' => $tanggalAkhir,
                    ],
                    'progres' => $progres->map(function ($item) {
                        return [
                            'tanggal' => $item->tanggal,
                            'jam_mulai' => $item->jam_mulai,
                            'jam_selesai' => $item->jam_selesai,
                            'durasi_menit' => $item->durasi_menit,
                            'tugas' => $item->tugas_atasan,
                            'bukti_dukung' => $item->bukti_dukung,
                            'keterangan' => $item->keterangan,
                        ];
                    }),
                    'total_durasi_jam' => round($progres->sum('durasi_menit') / 60, 2),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate laporan TLA',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
