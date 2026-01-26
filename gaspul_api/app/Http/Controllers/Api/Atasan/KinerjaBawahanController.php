<?php

namespace App\Http\Controllers\Api\Atasan;

use App\Http\Controllers\Controller;
use App\Models\ProgresHarian;
use App\Models\MasterAtasan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ✅ KINERJA HARIAN BAWAHAN - Dashboard Pengawasan Atasan
 *
 * Fitur Utama:
 * 1. Monitoring progres harian ASN bawahan per unit kerja
 * 2. Deteksi ASN yang belum mengisi progres
 * 3. Rekap harian, mingguan, bulanan
 * 4. Cetak laporan KH (Kinerja Harian) dan TLA (Tugas Langsung Atasan)
 *
 * Performance: Optimized untuk 50+ ASN bawahan
 * - No nested whereHas (3 level)
 * - Direct JOIN with indexes
 * - Grouped by ASN untuk efisiensi
 */
class KinerjaBawahanController extends Controller
{
    /**
     * Get biodata atasan dan informasi unit kerja
     */
    public function getBiodata(Request $request)
    {
        try {
            $atasan = $request->user();

            // Get jumlah bawahan aktif
            $jumlahBawahan = MasterAtasan::where('atasan_id', $atasan->id)
                ->where('status', 'AKTIF')
                ->where('tahun', date('Y'))
                ->count();

            return response()->json([
                'biodata' => [
                    'id' => $atasan->id,
                    'nama' => $atasan->name,
                    'nip' => $atasan->nip,
                    'jabatan' => $atasan->jabatan,
                    'unit_kerja' => $atasan->unit_kerja,
                    'email' => $atasan->email,
                    'jumlah_bawahan' => $jumlahBawahan,
                    'tahun_aktif' => date('Y'),
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
     * ✅ ENDPOINT UTAMA: Get kinerja bawahan dengan filter
     *
     * Filter:
     * - tanggal: Single date (format: Y-m-d)
     * - tanggal_mulai & tanggal_akhir: Date range
     * - bulan & tahun: Monthly filter
     * - mode: 'harian', 'mingguan', 'bulanan'
     *
     * Output: List ASN dengan progres harian (atau status "Belum Mengisi")
     */
    public function getKinerjaBawahan(Request $request)
    {
        try {
            $atasan = $request->user();
            $tahunAktif = $request->input('tahun', date('Y'));

            // ✅ STEP 1: Get list bawahan aktif (OPTIMIZED - single query)
            $bawahanIds = MasterAtasan::where('atasan_id', $atasan->id)
                ->where('status', 'AKTIF')
                ->where('tahun', $tahunAktif)
                ->pluck('asn_id')
                ->toArray();

            if (empty($bawahanIds)) {
                return response()->json([
                    'message' => 'Tidak ada bawahan aktif',
                    'data' => [],
                    'summary' => [
                        'total_bawahan' => 0,
                        'sudah_mengisi' => 0,
                        'belum_mengisi' => 0,
                    ]
                ]);
            }

            // ✅ STEP 2: Parse filter parameters
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

            // ✅ STEP 3: Get progres harian bawahan (OPTIMIZED with indexes)
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
                ->whereIn('user_id', $bawahanIds)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderBy('tanggal')
                ->orderBy('jam_mulai')
                ->get();

            // ✅ STEP 4: Get bawahan data with unit info
            $bawahanData = User::select('id', 'name', 'nip', 'jabatan', 'unit_kerja')
                ->whereIn('id', $bawahanIds)
                ->where('status', 'AKTIF')
                ->orderBy('name')
                ->get()
                ->keyBy('id');

            // ✅ STEP 5: Group progres by user_id
            $progresGrouped = $progresData->groupBy('user_id');

            // ✅ STEP 6: Build response data
            $result = [];
            $sudahMengisi = 0;
            $belumMengisi = 0;

            foreach ($bawahanData as $userId => $bawahan) {
                $userProgres = $progresGrouped->get($userId, collect());

                $hasProgres = $userProgres->isNotEmpty();

                if ($hasProgres) {
                    $sudahMengisi++;
                } else {
                    $belumMengisi++;
                }

                // Format kegiatan list
                $kegiatanList = $userProgres->map(function ($item) {
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

                $totalDurasi = $userProgres->sum('durasi_menit');

                $result[] = [
                    'user_id' => $userId,
                    'nama' => $bawahan->name,
                    'nip' => $bawahan->nip,
                    'jabatan' => $bawahan->jabatan,
                    'unit_kerja' => $bawahan->unit_kerja,
                    'status' => $hasProgres ? 'Sudah Mengisi' : 'Belum Mengisi',
                    'total_kegiatan' => $userProgres->count(),
                    'total_durasi_menit' => $totalDurasi,
                    'total_durasi_jam' => round($totalDurasi / 60, 2),
                    'kegiatan_list' => $kegiatanList,
                ];
            }

            return response()->json([
                'message' => 'Data kinerja bawahan berhasil diambil',
                'filter' => [
                    'mode' => $mode,
                    'tanggal_mulai' => $startDate,
                    'tanggal_akhir' => $endDate,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                ],
                'data' => $result,
                'summary' => [
                    'total_bawahan' => count($bawahanData),
                    'sudah_mengisi' => $sudahMengisi,
                    'belum_mengisi' => $belumMengisi,
                    'persentase_kepatuhan' => count($bawahanData) > 0
                        ? round(($sudahMengisi / count($bawahanData)) * 100, 2)
                        : 0,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting kinerja bawahan:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Failed to get kinerja bawahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ CETAK LAPORAN KH (Kinerja Harian) - Single ASN
     */
    public function cetakLaporanKH(Request $request, $userId)
    {
        try {
            $atasan = $request->user();
            $tanggalMulai = $request->input('tanggal_mulai', date('Y-m-d'));
            $tanggalAkhir = $request->input('tanggal_akhir', date('Y-m-d'));

            // Verify bawahan
            $isBawahan = MasterAtasan::where('atasan_id', $atasan->id)
                ->where('asn_id', $userId)
                ->where('status', 'AKTIF')
                ->exists();

            if (!$isBawahan) {
                return response()->json([
                    'message' => 'ASN bukan bawahan Anda'
                ], 403);
            }

            // Get ASN data
            $asn = User::find($userId);

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
                    'atasan' => [
                        'nama' => $atasan->name,
                        'nip' => $atasan->nip,
                        'jabatan' => $atasan->jabatan,
                    ],
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
     * ✅ CETAK LAPORAN TLA (Tugas Langsung Atasan) - Single ASN
     */
    public function cetakLaporanTLA(Request $request, $userId)
    {
        try {
            $atasan = $request->user();
            $tanggalMulai = $request->input('tanggal_mulai', date('Y-m-d'));
            $tanggalAkhir = $request->input('tanggal_akhir', date('Y-m-d'));

            // Verify bawahan
            $isBawahan = MasterAtasan::where('atasan_id', $atasan->id)
                ->where('asn_id', $userId)
                ->where('status', 'AKTIF')
                ->exists();

            if (!$isBawahan) {
                return response()->json([
                    'message' => 'ASN bukan bawahan Anda'
                ], 403);
            }

            // Get ASN data
            $asn = User::find($userId);

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
                    'atasan' => [
                        'nama' => $atasan->name,
                        'nip' => $atasan->nip,
                        'jabatan' => $atasan->jabatan,
                    ],
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
