<?php

namespace App\Http\Controllers\Asn;

use App\Http\Controllers\Controller;
use App\Models\SkpTahunan;
use App\Models\SkpTahunanDetail;
use App\Models\IndikatorKinerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller SKP Tahunan ASN - PRODUCTION STABLE VERSION
 */
class SkpTahunanController extends Controller
{
    public function index(Request $request)
    {
        $asn = Auth::user();
        $tahun = $request->input('tahun', now()->year);

        $skpTahunan = SkpTahunan::firstOrCreate(
            ['user_id' => $asn->id, 'tahun' => $tahun],
            ['status' => 'DRAFT']
        );

        $skpTahunan->load(['details.indikatorKinerja.sasaranKegiatan']);

        return view('asn.skp-tahunan.index', [
            'skpTahunan' => $skpTahunan,
            'tahun' => $tahun,
            'asn' => $asn,
        ]);
    }

    public function create(Request $request)
    {
        $asn = Auth::user();
        $skpTahunanId = $request->input('skp_tahunan_id');

        $skpTahunan = SkpTahunan::where('user_id', $asn->id)->findOrFail($skpTahunanId);

        if (!$skpTahunan->canAddDetails()) {
            return redirect()
                ->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
                ->with('error', 'SKP Tahunan sudah disetujui, tidak dapat menambah butir kinerja');
        }

        $indikatorList = IndikatorKinerja::aktif()
            ->with('sasaranKegiatan')
            ->when($asn->unit_kerja_id, function($query) use ($asn) {
                $query->where(function($q) use ($asn) {
                    $q->where('unit_kerja_id', $asn->unit_kerja_id)
                      ->orWhereNull('unit_kerja_id');
                });
            })
            ->get();

        return view('asn.skp-tahunan.create', [
            'skpTahunan' => $skpTahunan,
            'indikatorList' => $indikatorList,
            'asn' => $asn,
        ]);
    }

    public function store(Request $request)
    {
        $asn = Auth::user();

        $validated = $request->validate([
            'skp_tahunan_id' => 'required|exists:skp_tahunan,id',
            'indikator_kinerja_id' => 'required|exists:indikator_kinerja,id',
            'rencana_aksi' => 'required|string',
            'target_tahunan' => 'required|integer|min:1',
            'satuan' => 'required|string|max:50',
        ]);

        $skpTahunan = SkpTahunan::where('user_id', $asn->id)->findOrFail($validated['skp_tahunan_id']);

        if (!$skpTahunan->canAddDetails()) {
            return redirect()
                ->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
                ->with('error', 'SKP Tahunan sudah disetujui, tidak dapat menambah butir kinerja');
        }

        SkpTahunanDetail::create([
            'skp_tahunan_id' => $validated['skp_tahunan_id'],
            'indikator_kinerja_id' => $validated['indikator_kinerja_id'],
            'rencana_aksi' => $validated['rencana_aksi'],
            'target_tahunan' => $validated['target_tahunan'],
            'satuan' => $validated['satuan'],
            'realisasi_tahunan' => 0,
        ]);

        return redirect()
            ->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
            ->with('success', 'Butir Kinerja berhasil ditambahkan. Rencana Aksi Bulanan otomatis terbuat!');
    }

    public function edit($id)
    {
        $asn = auth()->user();

        $detail = SkpTahunanDetail::with(['skpTahunan', 'indikatorKinerja.sasaranKegiatan'])
            ->findOrFail($id);

        // Authorization check
        if ($detail->skpTahunan->user_id !== $asn->id) {
            abort(403, 'Akses ditolak. SKP ini bukan milik Anda.');
        }

        // Status check
        if (!in_array($detail->skpTahunan->status, ['DRAFT', 'DITOLAK'])) {
            return redirect()
                ->route('asn.skp-tahunan.index', ['tahun' => $detail->skpTahunan->tahun])
                ->with('error', 'Butir Kinerja tidak dapat diedit karena SKP sudah ' . strtolower($detail->skpTahunan->status));
        }

        $indikatorList = IndikatorKinerja::aktif()
            ->with('sasaranKegiatan')
            ->when($asn->unit_kerja_id, function($query) use ($asn) {
                $query->where(function($q) use ($asn) {
                    $q->where('unit_kerja_id', $asn->unit_kerja_id)
                      ->orWhereNull('unit_kerja_id');
                });
            })
            ->get();

        return view('asn.skp-tahunan.edit', [
            'detail' => $detail,
            'indikatorList' => $indikatorList,
            'asn' => $asn,
        ]);
    }

    public function update(Request $request, $id)
    {
        $asn = auth()->user();

        $detail = SkpTahunanDetail::with('skpTahunan')->findOrFail($id);

        // Authorization check
        if ($detail->skpTahunan->user_id !== $asn->id) {
            abort(403, 'Akses ditolak. SKP ini bukan milik Anda.');
        }

        // Status check
        if (!in_array($detail->skpTahunan->status, ['DRAFT', 'DITOLAK'])) {
            return redirect()
                ->route('asn.skp-tahunan.index', ['tahun' => $detail->skpTahunan->tahun])
                ->with('error', 'Butir Kinerja tidak dapat diedit karena SKP sudah ' . strtolower($detail->skpTahunan->status));
        }

        $validated = $request->validate([
            'indikator_kinerja_id' => 'required|exists:indikator_kinerja,id',
            'rencana_aksi' => 'required|string',
            'target_tahunan' => 'required|integer|min:1',
            'satuan' => 'required|string|max:50',
        ]);

        $detail->update($validated);

        return redirect()
            ->route('asn.skp-tahunan.index', ['tahun' => $detail->skpTahunan->tahun])
            ->with('success', 'Butir Kinerja berhasil diperbarui');
    }

    public function destroy($id)
    {
        $asn = auth()->user();

        $detail = SkpTahunanDetail::with('skpTahunan')->findOrFail($id);

        // Authorization check
        if ($detail->skpTahunan->user_id !== $asn->id) {
            abort(403, 'Akses ditolak. SKP ini bukan milik Anda.');
        }

        // Status check
        if (!in_array($detail->skpTahunan->status, ['DRAFT', 'DITOLAK'])) {
            return redirect()
                ->route('asn.skp-tahunan.index', ['tahun' => $detail->skpTahunan->tahun])
                ->with('error', 'Butir Kinerja tidak dapat dihapus karena SKP sudah ' . strtolower($detail->skpTahunan->status));
        }

        $tahun = $detail->skpTahunan->tahun;
        $detail->delete();

        return redirect()
            ->route('asn.skp-tahunan.index', ['tahun' => $tahun])
            ->with('success', 'Butir Kinerja berhasil dihapus');
    }

    public function submit($id)
    {
        $asn = Auth::user();

        $skpTahunan = SkpTahunan::where('user_id', $asn->id)->findOrFail($id);

        if (!$skpTahunan->canBeSubmitted()) {
            return redirect()
                ->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
                ->with('error', 'SKP Tahunan tidak dapat diajukan (belum ada butir kinerja atau sudah disetujui)');
        }

        $skpTahunan->update(['status' => 'DIAJUKAN']);

        return redirect()
            ->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
            ->with('success', 'SKP Tahunan berhasil diajukan untuk persetujuan atasan');
    }
}
