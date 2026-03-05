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
     *
     * SECURITY: Menggunakan Laravel Policy + Route Model Binding
     */
    public function edit(SkpTahunanDetail $detail)
    {
        $asn = auth()->user();

        // CRITICAL: Load relation BEFORE authorization check
        // Policy needs skpTahunan relation to check ownership
        if (!$detail->relationLoaded('skpTahunan')) {
            $detail->load('skpTahunan');
        }

        // AUTHORIZATION menggunakan Policy (Laravel Best Practice)
        // Policy akan handle: ownership check + status check
        $this->authorize('update', $detail);

        // Load additional relations for form
        $detail->load(['indikatorKinerja.sasaranKegiatan']);

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
     *
     * SECURITY: Menggunakan Laravel Policy + Route Model Binding
     */
    public function update(Request $request, SkpTahunanDetail $detail)
    {
        // CRITICAL: Load relation BEFORE authorization check
        if (!$detail->relationLoaded('skpTahunan')) {
            $detail->load('skpTahunan');
        }

        // AUTHORIZATION menggunakan Policy
        $this->authorize('update', $detail);

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
     *
     * SECURITY: Menggunakan Laravel Policy + Route Model Binding
     */
    public function destroy(SkpTahunanDetail $detail)
    {
        // CRITICAL: Load relation BEFORE authorization check
        if (!$detail->relationLoaded('skpTahunan')) {
            $detail->load('skpTahunan');
        }

        // AUTHORIZATION menggunakan Policy
        $this->authorize('delete', $detail);

        $tahun = $detail->skpTahunan->tahun;
        $detail->delete();

        return redirect()
            ->route('asn.skp-tahunan.index', ['tahun' => $tahun])
            ->with('success', 'Butir Kinerja berhasil dihapus');
    }

    /**
     * Submit SKP Tahunan untuk persetujuan atasan
     *
     * APPROVAL LOGIC BERBASIS HIERARKI (atasan_id):
     * 1. Jika user punya atasan_id → set approved_by = atasan_id, status = DIAJUKAN
     * 2. Jika user TIDAK punya atasan_id (puncak hierarki) → auto final approve
     * 3. Backward compatible: approved_by bisa null untuk data lama
     */
    public function submit($id)
    {
        $user = Auth::user();

        $skpTahunan = SkpTahunan::where('user_id', $user->id)->findOrFail($id);

        if (!$skpTahunan->canBeSubmitted()) {
            return redirect()
                ->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
                ->with('error', 'SKP Tahunan tidak dapat diajukan (belum ada butir kinerja atau sudah disetujui)');
        }

        // ====================================================================
        // APPROVAL LOGIC BERBASIS HIERARKI
        // ====================================================================

        // Cek apakah user punya atasan (load relasi atasan)
        $user->load('atasan');

        if ($user->atasan_id && $user->atasan) {
            // CASE 1: User punya atasan → submit untuk approval
            $skpTahunan->update([
                'status' => 'DIAJUKAN',
                'approved_by' => $user->atasan_id, // Set atasan sebagai approver
                'approved_at' => null, // Clear previous approval date
                'catatan_atasan' => null, // Clear previous catatan
            ]);

            $message = 'SKP Tahunan berhasil diajukan ke ' . $user->atasan->name . ' untuk persetujuan';
        } elseif ($user->role === 'ATASAN' && is_null($user->atasan_id)) {
            // CASE 2: Murni puncak hierarki (role ATASAN, atasan_id = NULL) → auto approve
            $skpTahunan->update([
                'status' => 'DISETUJUI',
                'approved_by' => null,
                'approved_at' => now(),
                'catatan_atasan' => 'Otomatis disetujui (Puncak Hierarki)',
            ]);

            $message = 'SKP Tahunan berhasil disetujui otomatis (Anda adalah puncak hierarki)';
        } else {
            // CASE 3: ASN atau role lain tanpa atasan_id → BLOKIR, konfigurasi belum benar
            return redirect()
                ->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
                ->with('error', 'Struktur atasan belum dikonfigurasi. Hubungi admin untuk mengatur atasan langsung Anda sebelum mengajukan SKP.');
        }

        return redirect()
            ->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
            ->with('success', $message);
    }

    /**
     * Ajukan permintaan revisi SKP Tahunan yang sudah DISETUJUI
     *
     * BUSINESS RULES:
     * - Hanya bisa diajukan jika status = 'DISETUJUI'
     * - Menggunakan Policy authorization
     * - RHK dan Kinerja Harian TIDAK terpengaruh
     */
    public function ajukanRevisi(Request $request, SkpTahunan $skpTahunan)
    {
        // AUTHORIZATION menggunakan Policy
        $this->authorize('requestRevision', $skpTahunan);

        $validated = $request->validate([
            'alasan_revisi' => 'required|string|min:10|max:1000',
        ], [
            'alasan_revisi.required' => 'Alasan revisi wajib diisi',
            'alasan_revisi.min' => 'Alasan revisi minimal 10 karakter',
            'alasan_revisi.max' => 'Alasan revisi maksimal 1000 karakter',
        ]);

        // Update status dan catat alasan revisi
        $skpTahunan->update([
            'status' => 'REVISI_DIAJUKAN',
            'alasan_revisi' => $validated['alasan_revisi'],
            'revisi_diajukan_at' => now(),
        ]);

        return redirect()
            ->route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun])
            ->with('success', 'Permintaan revisi SKP Tahunan berhasil diajukan. Menunggu persetujuan atasan.');
    }
}

