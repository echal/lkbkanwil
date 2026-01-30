<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SasaranKegiatan;
use Illuminate\Http\Request;

class SasaranKegiatanController extends Controller
{
    public function index()
    {
        $sasaran = SasaranKegiatan::withCount('indikatorKinerja')->latest()->get();
        return view('admin.sasaran-kegiatan.index', compact('sasaran'));
    }

    public function create()
    {
        return view('admin.sasaran-kegiatan.tambah');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_sasaran' => 'required|unique:sasaran_kegiatan|max:20',
            'nama_sasaran' => 'required',
            'deskripsi' => 'nullable',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ]);

        SasaranKegiatan::create($validated);
        return redirect()->route('admin.sasaran-kegiatan.index')->with('success', 'Sasaran Kegiatan berhasil ditambahkan');
    }

    public function edit($id)
    {
        $sasaran = SasaranKegiatan::findOrFail($id);
        return view('admin.sasaran-kegiatan.edit', compact('sasaran'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'kode_sasaran' => 'required|max:20|unique:sasaran_kegiatan,kode_sasaran,' . $id,
            'nama_sasaran' => 'required',
            'deskripsi' => 'nullable',
            'status' => 'required|in:AKTIF,NONAKTIF',
        ]);

        $sasaran = SasaranKegiatan::findOrFail($id);
        $sasaran->update($validated);
        return redirect()->route('admin.sasaran-kegiatan.index')->with('success', 'Sasaran Kegiatan berhasil diupdate');
    }

    public function destroy($id)
    {
        $sasaran = SasaranKegiatan::findOrFail($id);
        $sasaran->delete();
        return redirect()->route('admin.sasaran-kegiatan.index')->with('success', 'Sasaran Kegiatan berhasil dihapus');
    }
}
