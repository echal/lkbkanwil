<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LaporanBulananKinerja;
use App\Services\LaporanBulananService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KoreksiLaporanController extends Controller
{
    public function __construct(
        protected LaporanBulananService $laporanService
    ) {}

    /**
     * GET /admin/koreksi-laporan
     * Daftar laporan bulan berjalan status DIKIRIM/DISETUJUI yang berpotensi salah bulan.
     */
    public function index()
    {
        $bulan  = (int) now()->setTimezone('Asia/Makassar')->month;
        $tahun  = (int) now()->setTimezone('Asia/Makassar')->year;

        $laporan = LaporanBulananKinerja::with(['user.unitKerja'])
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->whereIn('status', [
                LaporanBulananKinerja::STATUS_DIKIRIM,
                LaporanBulananKinerja::STATUS_DISETUJUI,
            ])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.koreksi-laporan.index', compact('laporan', 'bulan', 'tahun'));
    }

    /**
     * POST /admin/laporan-bulanan/{id}/koreksi
     *
     * Koreksi laporan salah bulan: DELETE lama + generateBulanan() bulan baru.
     * Audit log disimpan ke laporan_koreksi_log sebelum DELETE.
     * Satu DB::transaction per record — tidak bulk.
     */
    public function koreksi(Request $request, int $id)
    {
        $request->validate([
            'bulan_baru' => ['required', 'integer', 'min:1', 'max:12'],
            'tahun_baru' => ['required', 'integer', 'min:2024', 'max:2030'],
            'alasan'     => ['required', 'string', 'max:500'],
        ]);

        $bulanBaru = (int) $request->bulan_baru;
        $tahunBaru = (int) $request->tahun_baru;
        $admin     = Auth::user();

        // ── 1. Ambil laporan lama ─────────────────────────────────────────────
        $laporan = LaporanBulananKinerja::findOrFail($id);

        // ── 2. Validasi: bulan_baru harus berbeda ─────────────────────────────
        if ($laporan->bulan === $bulanBaru && $laporan->tahun === $tahunBaru) {
            throw ValidationException::withMessages([
                'bulan_baru' => 'Bulan baru sama dengan bulan laporan saat ini. Tidak ada yang dikoreksi.',
            ]);
        }

        // ── 3. Transaksi per-record ───────────────────────────────────────────
        DB::transaction(function () use ($laporan, $bulanBaru, $tahunBaru, $admin, $request) {

            // a. Hapus laporan di bulan_baru jika sudah ada (hindari UNIQUE constraint conflict)
            LaporanBulananKinerja::where('user_id', $laporan->user_id)
                ->where('bulan', $bulanBaru)
                ->where('tahun', $tahunBaru)
                ->delete();

            // b. Simpan audit log laporan lama sebelum dihapus
            DB::table('laporan_koreksi_log')->insert([
                'user_id'             => $laporan->user_id,
                'bulan_lama'          => $laporan->bulan,
                'tahun_lama'          => $laporan->tahun,
                'total_hari_lama'     => $laporan->total_hari,
                'total_jam_lama'      => $laporan->total_jam,
                'target_jam_lama'     => $laporan->target_jam,
                'capaian_persen_lama' => $laporan->capaian_persen,
                'status_lama'         => $laporan->status,
                'approved_by_lama'    => $laporan->approved_by,
                'approved_at_lama'    => $laporan->approved_at,
                'bulan_baru'          => $bulanBaru,
                'tahun_baru'          => $tahunBaru,
                'admin_id'            => $admin->id,
                'alasan'              => $request->alasan,
                'created_at'          => now(),
            ]);

            // c. Hapus laporan lama (salah bulan)
            $laporan->delete();

            // d. Generate laporan baru di bulan yang benar (status = DRAFT)
            $this->laporanService->generateBulanan($laporan->user_id, $bulanBaru, $tahunBaru);
        });

        return redirect()
            ->back()
            ->with('success', "Laporan berhasil dikoreksi dari {$laporan->bulan}/{$laporan->tahun} ke {$bulanBaru}/{$tahunBaru}. Status diset ke DRAFT.");
    }
}
