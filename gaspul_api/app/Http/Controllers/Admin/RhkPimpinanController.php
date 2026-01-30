<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RhkPimpinan;
use App\Models\IndikatorKinerja;
use App\Models\UnitKerja;
use Illuminate\Http\Request;

class RhkPimpinanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = RhkPimpinan::with(['indikatorKinerja', 'unitKerja']);

        // Filter by unit kerja
        if ($request->has('unit_kerja_id') && $request->unit_kerja_id != '') {
            $query->where('unit_kerja_id', $request->unit_kerja_id);
        }

        // Filter by indikator
        if ($request->has('indikator_kinerja_id') && $request->indikator_kinerja_id != '') {
            $query->where('indikator_kinerja_id', $request->indikator_kinerja_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $query->where('rhk_pimpinan', 'like', '%' . $request->search . '%');
        }

        $rhkList = $query->latest()->paginate(20);
        $unitKerjaList = UnitKerja::aktif()->get();
        $indikatorList = IndikatorKinerja::aktif()->get();

        return view('admin.rhk-pimpinan.index', compact('rhkList', 'unitKerjaList', 'indikatorList'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $indikatorList = IndikatorKinerja::aktif()->get();
        $unitKerjaList = UnitKerja::aktif()->get();

        return view('admin.rhk-pimpinan.tambah', compact('indikatorList', 'unitKerjaList'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'indikator_kinerja_id' => 'required|exists:indikator_kinerja,id',
            'unit_kerja_id' => 'required|exists:unit_kerja,id',
            'rhk_pimpinan' => 'required|string|max:1000',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ]);

        // Check duplicate
        $exists = RhkPimpinan::where('indikator_kinerja_id', $validated['indikator_kinerja_id'])
            ->where('unit_kerja_id', $validated['unit_kerja_id'])
            ->where('rhk_pimpinan', $validated['rhk_pimpinan'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'RHK Pimpinan dengan nama yang sama sudah ada untuk indikator dan unit kerja ini')->withInput();
        }

        RhkPimpinan::create($validated);

        return redirect()->route('admin.rhk-pimpinan.index')->with('success', 'RHK Pimpinan berhasil ditambahkan');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $rhk = RhkPimpinan::with(['indikatorKinerja', 'unitKerja'])->findOrFail($id);
        $indikatorList = IndikatorKinerja::aktif()->get();
        $unitKerjaList = UnitKerja::aktif()->get();

        return view('admin.rhk-pimpinan.edit', compact('rhk', 'indikatorList', 'unitKerjaList'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $rhk = RhkPimpinan::findOrFail($id);

        $validated = $request->validate([
            'indikator_kinerja_id' => 'required|exists:indikator_kinerja,id',
            'unit_kerja_id' => 'required|exists:unit_kerja,id',
            'rhk_pimpinan' => 'required|string|max:1000',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ]);

        // Check duplicate (exclude current)
        $exists = RhkPimpinan::where('indikator_kinerja_id', $validated['indikator_kinerja_id'])
            ->where('unit_kerja_id', $validated['unit_kerja_id'])
            ->where('rhk_pimpinan', $validated['rhk_pimpinan'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'RHK Pimpinan dengan nama yang sama sudah ada untuk indikator dan unit kerja ini')->withInput();
        }

        $rhk->update($validated);

        return redirect()->route('admin.rhk-pimpinan.index')->with('success', 'RHK Pimpinan berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $rhk = RhkPimpinan::findOrFail($id);

        // Check if used in SKP
        $usageCount = $rhk->skpTahunanDetails()->count();
        if ($usageCount > 0) {
            return back()->with('error', "RHK Pimpinan tidak dapat dihapus karena sudah digunakan di {$usageCount} SKP Tahunan");
        }

        $rhk->delete();

        return redirect()->route('admin.rhk-pimpinan.index')->with('success', 'RHK Pimpinan berhasil dihapus');
    }
}
