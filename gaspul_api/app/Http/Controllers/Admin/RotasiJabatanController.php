<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RotasiJabatanController extends Controller
{
    /**
     * Halaman utama Rotasi Jabatan
     * Menampilkan dua form:
     * 1. Ganti Kepala Unit — ganti atasan semua staf suatu unit sekaligus
     * 2. Pindah Staf — pindah 1 atau beberapa staf ke atasan lain
     */
    public function index()
    {
        // Semua unit kerja untuk dropdown (kecuali level 1 = Kanwil)
        $unitList = UnitKerja::where('status', 'AKTIF')
            ->where('level', '>', 1)
            ->orderBy('level')
            ->orderBy('nama_unit')
            ->get(['id', 'nama_unit', 'level']);

        // Semua ATASAN aktif untuk dropdown
        $atasanList = User::where('role', 'ATASAN')
            ->where('status_pegawai', 'AKTIF')
            ->orderBy('name')
            ->get(['id', 'name', 'jabatan', 'unit_kerja_id']);

        return view('admin.rotasi-jabatan.index', compact('unitList', 'atasanList'));
    }

    /**
     * AJAX: Ambil info kepala unit saat ini + daftar staf unit tsb
     */
    public function infoUnit(Request $request)
    {
        $unitId = $request->input('unit_kerja_id');

        $unit = UnitKerja::findOrFail($unitId);

        // Kepala unit sekarang = ATASAN yang unit_kerja_id-nya sama
        $kepalaSekarang = User::where('role', 'ATASAN')
            ->where('status_pegawai', 'AKTIF')
            ->where('unit_kerja_id', $unitId)
            ->get(['id', 'name', 'jabatan']);

        // Semua staf (ASN + ATASAN bawahan) yang atasan_id-nya ke salah satu kepala di unit ini
        $kepalaIds = $kepalaSekarang->pluck('id');
        $stafCount = User::whereIn('atasan_id', $kepalaIds)->count();

        return response()->json([
            'unit'            => $unit->only(['id', 'nama_unit']),
            'kepala_sekarang' => $kepalaSekarang,
            'staf_count'      => $stafCount,
        ]);
    }

    /**
     * AJAX: Ambil daftar bawahan dari atasan tertentu
     */
    public function bawahanAtasan(Request $request)
    {
        $atasanId = $request->input('atasan_id');

        $bawahan = User::where('atasan_id', $atasanId)
            ->where('status_pegawai', 'AKTIF')
            ->orderBy('name')
            ->get(['id', 'name', 'jabatan', 'role']);

        return response()->json([
            'bawahan' => $bawahan,
            'total'   => $bawahan->count(),
        ]);
    }

    /**
     * Proses Ganti Kepala Unit
     *
     * Aksi:
     * 1. Update unit_kerja_id kepala lama → unit baru kepala baru (opsional, jika kepala lama ada)
     * 2. Update unit_kerja_id kepala baru → unit ini
     * 3. Update atasan_id semua staf unit ini → kepala baru
     */
    public function gantiKepala(Request $request)
    {
        $request->validate([
            'unit_kerja_id'   => 'required|exists:unit_kerja,id',
            'kepala_baru_id'  => 'required|exists:users,id',
            'jabatan_baru'    => 'required|string|max:255',
        ]);

        $unitId      = $request->unit_kerja_id;
        $kepalaBaru  = User::findOrFail($request->kepala_baru_id);
        $jabatanBaru = $request->jabatan_baru;

        DB::transaction(function () use ($unitId, $kepalaBaru, $jabatanBaru) {
            // Kepala lama di unit ini (bisa lebih dari satu jika ada Plt.)
            $kepalaLama = User::where('role', 'ATASAN')
                ->where('status_pegawai', 'AKTIF')
                ->where('unit_kerja_id', $unitId)
                ->where('id', '!=', $kepalaBaru->id)
                ->get();

            // Kumpulkan semua staf yang atasannya kepala lama
            $kepalaLamaIds = $kepalaLama->pluck('id');

            // Pindahkan semua staf kepala lama → ke kepala baru
            if ($kepalaLamaIds->isNotEmpty()) {
                User::whereIn('atasan_id', $kepalaLamaIds)
                    ->update(['atasan_id' => $kepalaBaru->id]);
            }

            // Update unit_kerja_id kepala baru → unit ini + update jabatan
            $kepalaBaru->update([
                'unit_kerja_id' => $unitId,
                'jabatan'       => $jabatanBaru,
            ]);
        });

        $unit = UnitKerja::find($unitId);
        $stafCount = User::where('atasan_id', $kepalaBaru->id)->count();

        return response()->json([
            'success' => true,
            'message' => "Kepala {$unit->nama_unit} berhasil diganti ke {$kepalaBaru->name}. {$stafCount} staf dipindahkan.",
        ]);
    }

    /**
     * Proses Pindah Staf ke Atasan Lain
     *
     * Memindahkan satu atau beberapa staf ke atasan baru
     */
    public function pindahStaf(Request $request)
    {
        $request->validate([
            'staf_ids'       => 'required|array|min:1',
            'staf_ids.*'     => 'exists:users,id',
            'atasan_baru_id' => 'required|exists:users,id',
        ]);

        $stafIds    = $request->staf_ids;
        $atasanBaru = User::findOrFail($request->atasan_baru_id);

        $updated = User::whereIn('id', $stafIds)
            ->update(['atasan_id' => $atasanBaru->id]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} staf berhasil dipindahkan ke {$atasanBaru->name}.",
        ]);
    }
}
