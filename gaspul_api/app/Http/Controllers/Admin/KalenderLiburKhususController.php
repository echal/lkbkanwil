<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KalenderLiburKhusus;
use App\Models\UnitKerja;
use App\Services\LiburKhususService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KalenderLiburKhususController extends Controller
{
    private LiburKhususService $liburKhususService;

    public function __construct()
    {
        $this->liburKhususService = new LiburKhususService();
    }

    public function index(Request $request)
    {
        $query = KalenderLiburKhusus::with(['unitKerja', 'createdBy:id,name'])
            ->orderByDesc('tanggal_mulai');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('target_khusus')) {
            $query->where('target_khusus', $request->target_khusus);
        }

        $kalenders = $query->paginate(20)->withQueryString();

        return view('admin.kalender-libur-khusus.index', compact('kalenders'));
    }

    public function create()
    {
        $unitKerjaOptions = UnitKerja::toDropdownFlat();
        $targetOptions    = [
            KalenderLiburKhusus::TARGET_GURU     => 'Guru (Madrasah)',
            KalenderLiburKhusus::TARGET_PENYULUH => 'Penyuluh Agama',
            KalenderLiburKhusus::TARGET_PENGHULU => 'Penghulu',
        ];

        return view('admin.kalender-libur-khusus.tambah', compact('unitKerjaOptions', 'targetOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_kerja_id'   => 'required|exists:unit_kerja,id',
            'berlaku_ke_anak' => 'nullable|boolean',
            'target_khusus'   => 'required|in:GURU,PENYULUH,PENGHULU',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan'      => 'required|string|max:255',
            'status'          => 'required|in:DRAFT,AKTIF',
        ]);

        $validated['berlaku_ke_anak'] = $request->boolean('berlaku_ke_anak', true);
        $validated['created_by']      = Auth::id();

        $kalender = KalenderLiburKhusus::create($validated);

        if ($kalender->status === KalenderLiburKhusus::STATUS_AKTIF) {
            $this->liburKhususService->clearCacheForUnit(
                $kalender->unit_kerja_id,
                Carbon::parse($kalender->tanggal_mulai),
                Carbon::parse($kalender->tanggal_selesai)
            );
        }

        return redirect()->route('admin.kalender-libur-khusus.index')
            ->with('success', 'Kalender libur khusus berhasil disimpan.');
    }

    public function edit(KalenderLiburKhusus $kalender)
    {
        $unitKerjaOptions     = UnitKerja::toDropdownFlat();
        $kalenderLiburKhusus  = $kalender;
        $targetOptions        = [
            KalenderLiburKhusus::TARGET_GURU     => 'Guru (Madrasah)',
            KalenderLiburKhusus::TARGET_PENYULUH => 'Penyuluh Agama',
            KalenderLiburKhusus::TARGET_PENGHULU => 'Penghulu',
        ];

        return view('admin.kalender-libur-khusus.edit', compact('kalenderLiburKhusus', 'unitKerjaOptions', 'targetOptions'));
    }

    public function update(Request $request, KalenderLiburKhusus $kalender)
    {
        $validated = $request->validate([
            'unit_kerja_id'   => 'required|exists:unit_kerja,id',
            'berlaku_ke_anak' => 'nullable|boolean',
            'target_khusus'   => 'required|in:GURU,PENYULUH,PENGHULU',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan'      => 'required|string|max:255',
            'status'          => 'required|in:DRAFT,AKTIF',
        ]);

        $validated['berlaku_ke_anak'] = $request->boolean('berlaku_ke_anak', true);

        // Hapus cache tanggal lama
        $this->liburKhususService->clearCacheForUnit(
            $kalender->unit_kerja_id,
            $kalender->tanggal_mulai,
            $kalender->tanggal_selesai
        );

        $kalender->update($validated);

        // Hapus cache tanggal baru jika AKTIF
        if ($kalender->status === KalenderLiburKhusus::STATUS_AKTIF) {
            $this->liburKhususService->clearCacheForUnit(
                $kalender->unit_kerja_id,
                Carbon::parse($validated['tanggal_mulai']),
                Carbon::parse($validated['tanggal_selesai'])
            );
        }

        return redirect()->route('admin.kalender-libur-khusus.index')
            ->with('success', 'Kalender libur khusus berhasil diperbarui.');
    }

    public function destroy(KalenderLiburKhusus $kalender)
    {
        $this->liburKhususService->clearCacheForUnit(
            $kalender->unit_kerja_id,
            $kalender->tanggal_mulai,
            $kalender->tanggal_selesai
        );

        $kalender->delete();

        return redirect()->route('admin.kalender-libur-khusus.index')
            ->with('success', 'Kalender libur khusus berhasil dihapus.');
    }

    /**
     * Toggle status DRAFT ↔ AKTIF secara cepat dari halaman index.
     */
    public function toggleStatus(KalenderLiburKhusus $kalender)
    {
        $newStatus = $kalender->status === KalenderLiburKhusus::STATUS_AKTIF
            ? KalenderLiburKhusus::STATUS_DRAFT
            : KalenderLiburKhusus::STATUS_AKTIF;

        $kalender->update(['status' => $newStatus]);

        $this->liburKhususService->clearCacheForUnit(
            $kalender->unit_kerja_id,
            $kalender->tanggal_mulai,
            $kalender->tanggal_selesai
        );

        $label = $newStatus === KalenderLiburKhusus::STATUS_AKTIF ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.kalender-libur-khusus.index')
            ->with('success', "Kalender berhasil {$label}.");
    }
}
