<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PegawaiController extends Controller
{
    /**
     * Display a listing of pegawai with filter & pagination
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Query builder dengan eager loading
        $query = User::with('unitKerja');

        // Filter berdasarkan NIP (pencarian partial)
        $query->when($request->filled('nip'), function ($q) use ($request) {
            $q->where('nip', 'like', '%' . $request->nip . '%');
        });

        // Filter berdasarkan Unit Kerja
        $query->when($request->filled('unit_kerja_id'), function ($q) use ($request) {
            $q->where('unit_kerja_id', $request->unit_kerja_id);
        });

        // Filter berdasarkan Role (opsional)
        $query->when($request->filled('role'), function ($q) use ($request) {
            $q->where('role', $request->role);
        });

        // Filter berdasarkan Status (opsional)
        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status_pegawai', $request->status);
        });

        // Sorting & Pagination
        $pegawai = $query->latest()->paginate(15)->withQueryString();

        // Get dropdown data untuk filter
        $unitKerjaList = UnitKerja::aktif()
            ->orderBy('nama_unit')
            ->get(['id', 'nama_unit']);

        return view('admin.pegawai.index', compact('pegawai', 'unitKerjaList'));
    }

    public function create()
    {
        $units = UnitKerja::aktif()->get();
        return view('admin.pegawai.tambah', compact('units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'required|unique:users|max:18',
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:ADMIN,ATASAN,ASN',
            'unit_kerja_id' => 'required|exists:unit_kerja,id',
            'jabatan' => 'nullable',
            'status_pegawai' => 'required|in:AKTIF,NONAKTIF',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);
        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil ditambahkan');
    }

    public function edit($id)
    {
        $pegawai = User::findOrFail($id);
        $units = UnitKerja::aktif()->get();
        return view('admin.pegawai.edit', compact('pegawai', 'units'));
    }

    public function update(Request $request, $id)
    {
        $pegawai = User::findOrFail($id);

        $validated = $request->validate([
            'nip' => ['required', 'max:18', Rule::unique('users')->ignore($pegawai)],
            'name' => 'required',
            'email' => ['required', 'email', Rule::unique('users')->ignore($pegawai)],
            'password' => 'nullable|min:6',
            'role' => 'required|in:ADMIN,ATASAN,ASN',
            'unit_kerja_id' => 'required|exists:unit_kerja,id',
            'jabatan' => 'nullable',
            'status_pegawai' => 'required|in:AKTIF,NONAKTIF',
        ]);

        // Only hash password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $pegawai->update($validated);
        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil diupdate');
    }

    public function destroy($id)
    {
        $pegawai = User::findOrFail($id);
        $pegawai->delete();
        return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai berhasil dihapus');
    }
}
