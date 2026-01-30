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
    public function index()
    {
        $pegawai = User::with('unitKerja')->latest()->get();
        return view('admin.pegawai.index', compact('pegawai'));
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
