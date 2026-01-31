<?php

namespace App\Http\Controllers\Asn;

use App\Http\Controllers\Controller;
use App\Models\SkpTahunan;
use App\Models\SkpTahunanDetail;
use App\Models\RhkPimpinan;
use App\Models\IndikatorKinerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controller SKP Tahunan ASN
 *
 * Mengelola SKP Tahunan dan Butir Kinerja (Header-Detail Pattern)
 */
class SkpTahunanController extends Controller
{
    /**
     * Display SKP Tahunan index
     */
    public function index(Request $request)
    {
        $asn = Auth::user();
        $tahun = $request->input('tahun', now()->year);

        // Get or create SKP Tahunan for current year
        $skpTahunan = SkpTahunan::firstOrCreate(
            [
                'user_id' => $asn->id,
                'tahun' => $tahun,
            ],
            [
                'status' => 'DRAFT',
            ]
        );

        // Load details with relations
        $skpTahunan->load(['details.indikatorKinerja.sasaranKegiatan']);

        return view('asn.skp-tahunan.index', [
            'skpTahunan' => $skpTahunan,
            'tahun' => $tahun,
            'asn' => $asn,
        ]);
    }

    /**
     * Show form to add butir kinerja
     */
    public function create(Request $request)
    {
        $asn = Auth::user();
        $skpTahunanId = $request->input('skp_tahunan_id');

        $skpTahunan = SkpTahunan::where('user_id', $asn->id)
            ->findOrFail($skpTahunanId);

        // Security check: Can add details?
        if (!$skpTahunan->canAddDetails()) {
            return redirect()
                ->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
                ->with('error', 'SKP Tahunan sudah disetujui, tidak dapat menambah butir kinerja');
        }

        // Get active Indikator Kinerja
        // Filter by ASN's unit kerja (if exists, otherwise show all)
        $indikatorList = IndikatorKinerja::aktif()
            ->with('sasaranKegiatan')
            ->when($asn->unit_kerja_id, function($query) use ($asn) {
                $query->where(function($q) use ($asn) {
                    $q->where('unit_kerja_id', $asn->unit_kerja_id)
                      ->orWhereNull('unit_kerja_id'); // Allow global indikator
                });
            })
            ->get();

        return view('asn.skp-tahunan.create', [
            'skpTahunan' => $skpTahunan,
            'indikatorList' => $indikatorList,
            'asn' => $asn,
        ]);
    }

    /**
     * Store butir kinerja
     */
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

        $skpTahunan = SkpTahunan::where('user_id', $asn->id)
            ->findOrFail($validated['skp_tahunan_id']);

        // Security check
        if (!$skpTahunan->canAddDetails()) {
            return redirect()
                ->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
                ->with('error', 'SKP Tahunan sudah disetujui, tidak dapat menambah butir kinerja');
        }

        // Create detail
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

    /**
     * Show form to edit butir kinerja
     */
    public function edit($id)
    {
        $asn = Auth::user();

        // Load detail dengan security check di query level
        $detail = SkpTahunanDetail::with(['skpTahunan', 'indikatorKinerja.sasaranKegiatan'])
            ->whereHas('skpTahunan', function($query) use ($asn) {
                $query->where('user_id', $asn->id);
            })
            ->findOrFail($id);

        // whereHas sudah memastikan hanya SKP milik user yang ter-load
        // Jadi jika sampai sini, berarti user adalah pemilik

        // Check apakah bisa diedit (SKP belum disetujui)
        if (!in_array($detail->skpTahunan->status, ['DRAFT', 'DITOLAK'])) {
            return redirect()
                ->route('asn.skp-tahunan.index', ['tahun' => $detail->skpTahunan->tahun])
                ->with('error', 'Butir Kinerja tidak dapat diedit karena SKP sudah ' . strtolower($detail->skpTahunan->status));
        }

        // Get active Indikator Kinerja
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

    /**
     * Update butir kinerja
     */
    public function update(Request $request, $id)
    {
        $asn = Auth::user();

        // Load detail dengan WHERE HAS untuk security
        $detail = SkpTahunanDetail::with('skpTahunan')
            ->whereHas('skpTahunan', function($query) use ($asn) {
                $query->where('user_id', $asn->id);
            })
            ->findOrFail($id);

        // whereHas sudah memastikan hanya SKP milik user yang ter-load
        // Jadi jika sampai sini, berarti user adalah pemilik

        // Check apakah bisa diedit
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

    /**
     * Delete butir kinerja
     */
    public function destroy($id)
    {
        $asn = Auth::user();

        // Load detail dengan WHERE HAS untuk security
        $detail = SkpTahunanDetail::with('skpTahunan')
            ->whereHas('skpTahunan', function($query) use ($asn) {
                $query->where('user_id', $asn->id);
            })
            ->findOrFail($id);

        // whereHas sudah memastikan hanya SKP milik user yang ter-load
        // Jadi jika sampai sini, berarti user adalah pemilik

        // Check apakah bisa dihapus
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

    /**
     * Submit SKP Tahunan untuk persetujuan atasan
     */
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

