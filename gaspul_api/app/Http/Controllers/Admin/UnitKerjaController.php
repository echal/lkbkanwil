<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitKerja;
use Illuminate\Http\Request;

class UnitKerjaController extends Controller
{
    public function index()
    {
        $units = UnitKerja::withCount('users')->latest()->get();
        return view('admin.unit-kerja.index', compact('units'));
    }

    public function create()
    {
        return view('admin.unit-kerja.tambah');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_unit' => 'required|unique:unit_kerja|max:20',
            'nama_unit' => 'required',
            'eselon' => 'nullable|max:20',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ]);

        UnitKerja::create($validated);
        return redirect()->route('admin.unit-kerja.index')->with('success', 'Unit Kerja berhasil ditambahkan');
    }

    public function edit($id)
    {
        $unit = UnitKerja::findOrFail($id);
        return view('admin.unit-kerja.edit', compact('unit'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'kode_unit' => 'required|max:20|unique:unit_kerja,kode_unit,' . $id,
            'nama_unit' => 'required',
            'eselon' => 'nullable|max:20',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ]);

        $unit = UnitKerja::findOrFail($id);
        $unit->update($validated);
        return redirect()->route('admin.unit-kerja.index')->with('success', 'Unit Kerja berhasil diupdate');
    }

    public function destroy($id)
    {
        $unit = UnitKerja::findOrFail($id);

        if ($unit->users()->count() > 0) {
            return redirect()->route('admin.unit-kerja.index')->with('error', 'Unit tidak dapat dihapus karena masih memiliki pegawai');
        }

        $unit->delete();
        return redirect()->route('admin.unit-kerja.index')->with('success', 'Unit Kerja berhasil dihapus');
    }
}
