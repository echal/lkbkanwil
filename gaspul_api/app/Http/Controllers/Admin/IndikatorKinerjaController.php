<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndikatorKinerja;
use App\Models\SasaranKegiatan;
use App\Models\UnitKerja;
use Illuminate\Http\Request;

class IndikatorKinerjaController extends Controller
{
    public function index()
    {
        $indikator = IndikatorKinerja::with(['sasaranKegiatan', 'unitKerja'])->latest()->get();
        return view('admin.indikator-kinerja.index', compact('indikator'));
    }

    public function create()
    {
        $sasaran = SasaranKegiatan::aktif()->get();
        $unitKerja = UnitKerja::aktif()->get();
        return view('admin.indikator-kinerja.tambah', compact('sasaran', 'unitKerja'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sasaran_kegiatan_id' => 'required|exists:sasaran_kegiatan,id',
            'unit_kerja_id' => 'nullable|exists:unit_kerja,id',
            'kode_indikator' => 'required|unique:indikator_kinerja|max:20',
            'nama_indikator' => 'required',
            'satuan' => 'required|max:50',
            'tipe_target' => 'required|in:ANGKA,DOKUMEN,PERSENTASE',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ]);

        IndikatorKinerja::create($validated);
        return redirect()->route('admin.indikator-kinerja.index')->with('success', 'Indikator Kinerja (RHK Pimpinan) berhasil ditambahkan');
    }

    public function edit($id)
    {
        $indikator = IndikatorKinerja::findOrFail($id);
        $sasaran = SasaranKegiatan::aktif()->get();
        $unitKerja = UnitKerja::aktif()->get();
        return view('admin.indikator-kinerja.edit', compact('indikator', 'sasaran', 'unitKerja'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'sasaran_kegiatan_id' => 'required|exists:sasaran_kegiatan,id',
            'unit_kerja_id' => 'nullable|exists:unit_kerja,id',
            'kode_indikator' => 'required|max:20|unique:indikator_kinerja,kode_indikator,' . $id,
            'nama_indikator' => 'required',
            'satuan' => 'required|max:50',
            'tipe_target' => 'required|in:ANGKA,DOKUMEN,PERSENTASE',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ]);

        $indikator = IndikatorKinerja::findOrFail($id);
        $indikator->update($validated);
        return redirect()->route('admin.indikator-kinerja.index')->with('success', 'Indikator Kinerja (RHK Pimpinan) berhasil diupdate');
    }

    public function destroy($id)
    {
        $indikator = IndikatorKinerja::findOrFail($id);
        $indikator->delete();
        return redirect()->route('admin.indikator-kinerja.index')->with('success', 'Indikator Kinerja berhasil dihapus');
    }
}
