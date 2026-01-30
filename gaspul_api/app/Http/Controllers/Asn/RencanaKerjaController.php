<?php

namespace App\Http\Controllers\Asn;

use App\Http\Controllers\Controller;
use App\Models\SkpTahunan;
use App\Models\SkpTahunanDetail;
use App\Models\RencanaAksiBulanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RencanaKerjaController extends Controller
{
    public function index(Request $request)
    {
        $asn = Auth::user();
        $tahun = $request->input('tahun', now()->year);

        // Get SKP Tahunan untuk tahun ini
        $skpTahunan = SkpTahunan::where('user_id', $asn->id)
            ->where('tahun', $tahun)
            ->first();

        if (!$skpTahunan) {
            return view('asn.rencana-kerja.index', [
                'skpTahunan' => null,
                'rhkList' => collect([]),
                'tahun' => $tahun,
            ]);
        }

        // Get semua RHK Tahunan (SKP Tahunan Detail) beserta Rencana Aksi Bulanannya
        $rhkList = SkpTahunanDetail::where('skp_tahunan_id', $skpTahunan->id)
            ->with([
                'indikatorKinerja.sasaranKegiatan',
                'rencanaAksiBulanan' => function($query) use ($tahun) {
                    $query->where('tahun', $tahun)
                        ->orderBy('bulan');
                }
            ])
            ->get()
            ->map(function($detail) use ($tahun) {
                // Buat array 12 bulan dengan status
                $bulanData = [];
                for ($bulan = 1; $bulan <= 12; $bulan++) {
                    $rencana = $detail->rencanaAksiBulanan->firstWhere('bulan', $bulan);
                    $bulanData[$bulan] = [
                        'exists' => $rencana !== null,
                        'rencana' => $rencana,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                    ];
                }

                return [
                    'id' => $detail->id,
                    'indikator_kinerja' => $detail->indikatorKinerja->nama_indikator ?? '-',
                    'kode_indikator' => $detail->indikatorKinerja->kode_indikator ?? '-',
                    'sasaran_kegiatan' => $detail->indikatorKinerja->sasaranKegiatan->nama_sasaran ?? '-',
                    'rencana_aksi' => $detail->rencana_aksi,
                    'target_tahunan' => $detail->target_tahunan,
                    'satuan' => $detail->satuan,
                    'bulan_data' => $bulanData,
                    'total_terisi' => $detail->rencanaAksiBulanan->count(),
                ];
            });

        return view('asn.rencana-kerja.index', [
            'skpTahunan' => $skpTahunan,
            'rhkList' => $rhkList,
            'tahun' => $tahun,
        ]);
    }

    public function create(Request $request)
    {
        $skpTahunanDetailId = $request->input('skp_tahunan_detail_id');
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun', now()->year);

        $skpDetail = SkpTahunanDetail::with('indikatorKinerja')->findOrFail($skpTahunanDetailId);

        return view('asn.rencana-kerja.tambah', [
            'skpDetail' => $skpDetail,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'skp_tahunan_detail_id' => 'required|exists:skp_tahunan_detail,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2100',
            'rencana_aksi_bulanan' => 'required|string',
            'target_bulanan' => 'required|integer|min:0',
            'satuan_target' => 'required|string|max:100',
        ]);

        // Check duplicate
        $exists = RencanaAksiBulanan::where('skp_tahunan_detail_id', $validated['skp_tahunan_detail_id'])
            ->where('bulan', $validated['bulan'])
            ->where('tahun', $validated['tahun'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Rencana aksi bulanan untuk bulan ini sudah ada')->withInput();
        }

        RencanaAksiBulanan::create([
            'skp_tahunan_detail_id' => $validated['skp_tahunan_detail_id'],
            'bulan' => $validated['bulan'],
            'tahun' => $validated['tahun'],
            'rencana_aksi_bulanan' => $validated['rencana_aksi_bulanan'],
            'target_bulanan' => $validated['target_bulanan'],
            'satuan_target' => $validated['satuan_target'],
            'realisasi_bulanan' => 0,
            'status' => 'AKTIF',
        ]);

        return redirect()->route('asn.rencana-kerja.index', ['tahun' => $validated['tahun']])
            ->with('success', 'Rencana aksi bulanan berhasil ditambahkan');
    }

    public function edit($id)
    {
        $rencana = RencanaAksiBulanan::with('skpTahunanDetail.indikatorKinerja')->findOrFail($id);

        return view('asn.rencana-kerja.edit', [
            'rencana' => $rencana,
        ]);
    }

    public function update(Request $request, $id)
    {
        $rencana = RencanaAksiBulanan::findOrFail($id);

        $validated = $request->validate([
            'rencana_aksi_bulanan' => 'required|string',
            'target_bulanan' => 'required|integer|min:0',
            'satuan_target' => 'required|string|max:100',
        ]);

        $rencana->update([
            'rencana_aksi_bulanan' => $validated['rencana_aksi_bulanan'],
            'target_bulanan' => $validated['target_bulanan'],
            'satuan_target' => $validated['satuan_target'],
            'status' => 'AKTIF', // Update status when filled
        ]);

        return redirect()->route('asn.rencana-kerja.index', ['tahun' => $rencana->tahun])
            ->with('success', 'Rencana aksi berhasil diperbarui');
    }

    public function destroy($id)
    {
        $rencana = RencanaAksiBulanan::findOrFail($id);
        $tahun = $rencana->tahun;
        $rencana->delete();

        return redirect()->route('asn.rencana-kerja.index', ['tahun' => $tahun])
            ->with('success', 'Rencana aksi bulanan berhasil dihapus');
    }
}
