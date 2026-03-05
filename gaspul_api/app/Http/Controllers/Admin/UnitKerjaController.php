<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitKerja;
use Illuminate\Http\Request;

class UnitKerjaController extends Controller
{
    /**
     * Tampilkan halaman Unit Kerja dengan tree view.
     */
    public function index()
    {
        // Root units dengan eager load children (2 level) + users_count
        $units = UnitKerja::with([
                'children'          => fn($q) => $q->withCount('users'),
                'children.children' => fn($q) => $q->withCount('users'),
            ])
            ->withCount('users')
            ->whereNull('parent_id')
            ->orderBy('nama_unit')
            ->get();

        return view('admin.unit-kerja.index', compact('units'));
    }

    public function create(Request $request)
    {
        $parentOptions   = UnitKerja::toDropdownFlat();
        $defaultParentId = $request->input('parent_id'); // pre-fill dari tombol "+ Sub-unit"
        return view('admin.unit-kerja.tambah', compact('parentOptions', 'defaultParentId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_unit' => 'required|unique:unit_kerja|max:20',
            'nama_unit' => 'required',
            'eselon'    => 'nullable|max:20',
            'parent_id' => 'nullable|exists:unit_kerja,id',
            'status'    => 'required|in:AKTIF,NONAKTIF',
        ]);

        // Hitung level otomatis dari parent
        if (! empty($validated['parent_id'])) {
            $parent             = UnitKerja::findOrFail($validated['parent_id']);
            $validated['level'] = $parent->level + 1;
        } else {
            $validated['parent_id'] = null;
            $validated['level']     = 1;
        }

        UnitKerja::create($validated);
        return redirect()->route('admin.unit-kerja.index')
                         ->with('success', 'Unit Kerja berhasil ditambahkan');
    }

    public function edit($id)
    {
        $unit          = UnitKerja::findOrFail($id);
        $parentOptions = UnitKerja::toDropdownFlat(excludeId: $unit->id); // exclude diri sendiri
        return view('admin.unit-kerja.edit', compact('unit', 'parentOptions'));
    }

    public function update(Request $request, $id)
    {
        $unit = UnitKerja::findOrFail($id);

        $validated = $request->validate([
            'kode_unit' => 'required|max:20|unique:unit_kerja,kode_unit,' . $id,
            'nama_unit' => 'required',
            'eselon'    => 'nullable|max:20',
            'parent_id' => 'nullable|exists:unit_kerja,id',
            'status'    => 'required|in:AKTIF,NONAKTIF',
        ]);

        // Cegah circular reference
        if (! empty($validated['parent_id']) && (int) $validated['parent_id'] === $unit->id) {
            return back()->withErrors(['parent_id' => 'Unit tidak dapat menjadi induknya sendiri.'])->withInput();
        }

        // Hitung level otomatis
        if (! empty($validated['parent_id'])) {
            $parent             = UnitKerja::findOrFail($validated['parent_id']);
            $validated['level'] = $parent->level + 1;
        } else {
            $validated['parent_id'] = null;
            $validated['level']     = 1;
        }

        $unit->update($validated);
        return redirect()->route('admin.unit-kerja.index')
                         ->with('success', 'Unit Kerja berhasil diupdate');
    }

    public function destroy($id)
    {
        $unit = UnitKerja::findOrFail($id);

        if ($unit->children()->count() > 0) {
            return redirect()->route('admin.unit-kerja.index')
                             ->with('error', 'Unit tidak dapat dihapus karena memiliki sub-unit. Hapus atau pindahkan sub-unit terlebih dahulu.');
        }

        if ($unit->users()->count() > 0) {
            return redirect()->route('admin.unit-kerja.index')
                             ->with('error', 'Unit tidak dapat dihapus karena masih memiliki pegawai.');
        }

        $unit->delete();
        return redirect()->route('admin.unit-kerja.index')
                         ->with('success', 'Unit Kerja berhasil dihapus');
    }
}
