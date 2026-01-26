<?php

namespace App\Http\Controllers\Api\Asn;

use App\Http\Controllers\Controller;
use App\Models\Harian;
use App\Models\Bulanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class HarianController extends Controller
{
    /**
     * Get list of Harian for the authenticated ASN
     * Can filter by Bulanan ID, date range, etc.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Harian::with([
            'bulanan.rencanaKerjaAsn.sasaranKegiatan',
            'bulanan.rencanaKerjaAsn.indikatorKinerja'
        ])->whereHas('bulanan.rencanaKerjaAsn', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // Filter by Bulanan ID
        if ($request->has('bulanan_id')) {
            $query->where('bulanan_id', $request->bulanan_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        // Filter by month and year
        if ($request->has('bulan') && $request->has('tahun')) {
            $query->whereMonth('tanggal', $request->bulan)
                ->whereYear('tanggal', $request->tahun);
        }

        $harianList = $query->orderBy('tanggal', 'desc')->get();

        // Add calculated fields
        $harianList->each(function ($harian) {
            $harian->bukti_display = $harian->bukti_display;
            $harian->bukti_url = $harian->bukti_url;
        });

        return response()->json([
            'success' => true,
            'data' => $harianList
        ]);
    }

    /**
     * Get detail of specific Harian
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $harian = Harian::with([
            'bulanan.rencanaKerjaAsn.sasaranKegiatan',
            'bulanan.rencanaKerjaAsn.indikatorKinerja'
        ])->find($id);

        if (!$harian) {
            return response()->json([
                'success' => false,
                'message' => 'Harian tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if ($harian->bulanan->rencanaKerjaAsn->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke Harian ini'
            ], 403);
        }

        $harian->bukti_display = $harian->bukti_display;
        $harian->bukti_url = $harian->bukti_url;

        return response()->json([
            'success' => true,
            'data' => $harian
        ]);
    }

    /**
     * Create new Harian entry
     * IMPORTANT: Bukti is MANDATORY (file or link)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'bulanan_id' => 'required|integer|exists:bulanan,id',
            'tanggal' => 'required|date',
            'kegiatan_harian' => 'required|string|max:5000',
            'progres' => 'required|integer|min:0',
            'satuan' => 'required|string|max:50',
            'waktu_kerja' => 'nullable|integer|min:0',
            'bukti_type' => 'required|in:file,link',
            'bukti_file' => 'required_if:bukti_type,file|file|max:10240', // Max 10MB
            'bukti_link' => 'required_if:bukti_type,link|url',
            'keterangan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get Bulanan and check ownership
        $bulanan = Bulanan::with('rencanaKerjaAsn')->find($request->bulanan_id);

        if (!$bulanan) {
            return response()->json([
                'success' => false,
                'message' => 'Bulanan tidak ditemukan'
            ], 404);
        }

        if ($bulanan->rencanaKerjaAsn->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke Bulanan ini'
            ], 403);
        }

        // Check if Harian can be created (target must be filled)
        if (!$bulanan->canCreateHarian()) {
            return response()->json([
                'success' => false,
                'message' => 'Target Bulanan belum diisi atau SKP belum disetujui. Anda harus mengisi target Bulanan terlebih dahulu sebelum membuat Harian.'
            ], 403);
        }

        // Validate date is within Bulanan month
        $tanggal = Carbon::parse($request->tanggal);
        if ($tanggal->year != $bulanan->tahun || $tanggal->month != $bulanan->bulan) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal harus berada dalam bulan ' . $bulanan->bulan_nama . ' ' . $bulanan->tahun
            ], 422);
        }

        try {
            $data = [
                'bulanan_id' => $request->bulanan_id,
                'tanggal' => $request->tanggal,
                'kegiatan_harian' => $request->kegiatan_harian,
                'progres' => $request->progres,
                'satuan' => $request->satuan,
                'waktu_kerja' => $request->waktu_kerja,
                'bukti_type' => $request->bukti_type,
                'keterangan' => $request->keterangan,
            ];

            // Handle bukti file upload
            if ($request->bukti_type === 'file' && $request->hasFile('bukti_file')) {
                $file = $request->file('bukti_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('bukti_harian', $filename, 'public');
                $data['bukti_path'] = $path;
            }

            // Handle bukti link
            if ($request->bukti_type === 'link') {
                $data['bukti_link'] = $request->bukti_link;
            }

            $harian = Harian::create($data);
            $harian->load(['bulanan.rencanaKerjaAsn.sasaranKegiatan', 'bulanan.rencanaKerjaAsn.indikatorKinerja']);

            return response()->json([
                'success' => true,
                'message' => 'Harian berhasil ditambahkan',
                'data' => $harian
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan Harian: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update Harian entry
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $harian = Harian::with('bulanan.rencanaKerjaAsn')->find($id);

        if (!$harian) {
            return response()->json([
                'success' => false,
                'message' => 'Harian tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if ($harian->bulanan->rencanaKerjaAsn->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke Harian ini'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'kegiatan_harian' => 'required|string|max:5000',
            'progres' => 'required|integer|min:0',
            'satuan' => 'required|string|max:50',
            'waktu_kerja' => 'nullable|integer|min:0',
            'bukti_type' => 'required|in:file,link',
            'bukti_file' => 'nullable|file|max:10240',
            'bukti_link' => 'nullable|url',
            'keterangan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate date is within Bulanan month
        $tanggal = Carbon::parse($request->tanggal);
        $bulanan = $harian->bulanan;
        if ($tanggal->year != $bulanan->tahun || $tanggal->month != $bulanan->bulan) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal harus berada dalam bulan ' . $bulanan->bulan_nama . ' ' . $bulanan->tahun
            ], 422);
        }

        try {
            $data = [
                'tanggal' => $request->tanggal,
                'kegiatan_harian' => $request->kegiatan_harian,
                'progres' => $request->progres,
                'satuan' => $request->satuan,
                'waktu_kerja' => $request->waktu_kerja,
                'bukti_type' => $request->bukti_type,
                'keterangan' => $request->keterangan,
            ];

            // Handle bukti file upload (if new file uploaded)
            if ($request->bukti_type === 'file' && $request->hasFile('bukti_file')) {
                // Delete old file if exists
                if ($harian->bukti_path) {
                    Storage::disk('public')->delete($harian->bukti_path);
                }

                $file = $request->file('bukti_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('bukti_harian', $filename, 'public');
                $data['bukti_path'] = $path;
                $data['bukti_link'] = null;
            }

            // Handle bukti link
            if ($request->bukti_type === 'link') {
                $data['bukti_link'] = $request->bukti_link;
                // Don't delete file if switching to link, keep as backup
            }

            $harian->update($data);
            $harian->load(['bulanan.rencanaKerjaAsn.sasaranKegiatan', 'bulanan.rencanaKerjaAsn.indikatorKinerja']);

            return response()->json([
                'success' => true,
                'message' => 'Harian berhasil diupdate',
                'data' => $harian
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate Harian: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Harian entry
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $harian = Harian::with('bulanan.rencanaKerjaAsn')->find($id);

        if (!$harian) {
            return response()->json([
                'success' => false,
                'message' => 'Harian tidak ditemukan'
            ], 404);
        }

        // Check ownership
        if ($harian->bulanan->rencanaKerjaAsn->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke Harian ini'
            ], 403);
        }

        try {
            // Delete bukti file if exists
            if ($harian->bukti_path) {
                Storage::disk('public')->delete($harian->bukti_path);
            }

            $harian->delete();

            return response()->json([
                'success' => true,
                'message' => 'Harian berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus Harian: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available Bulanan for Harian creation
     * Only return Bulanan that:
     * 1. Belongs to user
     * 2. From approved SKP
     * 3. Has target filled
     */
    public function getAvailableBulanan(Request $request)
    {
        $user = $request->user();

        $bulananList = Bulanan::with([
            'rencanaKerjaAsn.sasaranKegiatan',
            'rencanaKerjaAsn.indikatorKinerja'
        ])
            ->whereHas('rencanaKerjaAsn', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('status', 'DISETUJUI');
            })
            ->whereNotNull('target_bulanan')
            ->where('target_bulanan', '>', 0)
            ->where('status', 'AKTIF')
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->get();

        // Add calculated fields
        $bulananList->each(function ($bulanan) {
            $bulanan->bulan_nama = $bulanan->bulan_nama;
            $bulanan->capaian_persen = $bulanan->capaian_persen;
        });

        return response()->json([
            'success' => true,
            'data' => $bulananList
        ]);
    }
}
