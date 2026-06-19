<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndikatorKinerja;
use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndikatorUnitKerjaController extends Controller
{
    public function index(Request $request)
    {
        $units = UnitKerja::aktif()->orderBy('nama_unit')->get();
        $selectedUnit = null;
        $indikatorList = collect();
        $mappedCount = 0;

        if ($request->filled('unit_kerja_id')) {
            $selectedUnit = UnitKerja::findOrFail($request->unit_kerja_id);

            $mappedIds = DB::table('indikator_unit_kerja')
                ->where('unit_kerja_id', $selectedUnit->id)
                ->pluck('indikator_kinerja_id')
                ->toArray();

            $mappedCount = count($mappedIds);

            $indikatorList = IndikatorKinerja::aktif()
                ->with('sasaranKegiatan')
                ->orderBy('kode_indikator')
                ->get()
                ->map(fn($ik) => [
                    'id'      => $ik->id,
                    'kode'    => $ik->kode_indikator,
                    'nama'    => $ik->nama_indikator,
                    'sasaran' => $ik->sasaranKegiatan?->nama_sasaran ?? '-',
                    'checked' => in_array($ik->id, $mappedIds),
                ]);
        }

        return view('admin.indikator-unit-kerja.index',
            compact('units', 'selectedUnit', 'indikatorList', 'mappedCount'));
    }

    public function update(Request $request, UnitKerja $unitKerja)
    {
        $validated = $request->validate([
            'indikator_ids'   => 'nullable|array',
            'indikator_ids.*' => 'exists:indikator_kinerja,id',
        ]);

        $unitKerja->indikatorKinerjas()->sync($validated['indikator_ids'] ?? []);

        return redirect()
            ->route('admin.indikator-unit-kerja.index', ['unit_kerja_id' => $unitKerja->id])
            ->with('success', 'Pemetaan indikator untuk ' . $unitKerja->nama_unit . ' berhasil disimpan.');
    }
}
