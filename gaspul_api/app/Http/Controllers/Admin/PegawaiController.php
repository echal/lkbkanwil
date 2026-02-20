<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UnitKerja;
use App\Models\ProgresHarian;
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

        // Filter berdasarkan Status Pengisian KH & TLA
        $query->when($request->filled('status_pengisian'), function ($q) use ($request) {
            if ($request->status_pengisian == 'no_kh') {
                // ASN tidak pernah isi Kinerja Harian
                $q->whereDoesntHave('kinerjaHarian');
            } elseif ($request->status_pengisian == 'no_tla') {
                // ASN tidak pernah isi Tugas Atasan Langsung
                $q->whereDoesntHave('tugasAtasanLangsung');
            } elseif ($request->status_pengisian == 'no_all') {
                // ASN tidak pernah isi KH & TLA
                $q->whereDoesntHave('kinerjaHarian')
                  ->whereDoesntHave('tugasAtasanLangsung');
            }
        });

        // Sorting & Pagination
        $pegawai = $query->latest()->paginate(15)->withQueryString();

        // Get dropdown data untuk filter
        $unitKerjaList = UnitKerja::aktif()
            ->orderBy('nama_unit')
            ->get(['id', 'nama_unit']);

        // ========================================================================
        // STATISTIK MONITORING PENGISIAN KH & TLA BULAN BERJALAN
        // ========================================================================

        // Total seluruh ASN (role ASN saja)
        $totalAsn = User::where('role', 'ASN')->where('status_pegawai', 'AKTIF')->count();

        // ASN belum isi KH bulan ini
        $belumIsiKhBulanIni = User::where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->whereDoesntHave('kinerjaHarian', function ($q) {
                $q->whereMonth('tanggal', now()->month)
                  ->whereYear('tanggal', now()->year);
            })
            ->count();

        // ASN belum isi TLA bulan ini
        $belumIsiTlaBulanIni = User::where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->whereDoesntHave('tugasAtasanLangsung', function ($q) {
                $q->whereMonth('tanggal', now()->month)
                  ->whereYear('tanggal', now()->year);
            })
            ->count();

        // ASN belum isi KH & TLA bulan ini
        $belumIsiKeduanyaBulanIni = User::where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->whereDoesntHave('kinerjaHarian', function ($q) {
                $q->whereMonth('tanggal', now()->month)
                  ->whereYear('tanggal', now()->year);
            })
            ->whereDoesntHave('tugasAtasanLangsung', function ($q) {
                $q->whereMonth('tanggal', now()->month)
                  ->whereYear('tanggal', now()->year);
            })
            ->count();

        return view('admin.pegawai.index', compact(
            'pegawai',
            'unitKerjaList',
            'totalAsn',
            'belumIsiKhBulanIni',
            'belumIsiTlaBulanIni',
            'belumIsiKeduanyaBulanIni'
        ));
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
            'unit_kerja_id' => 'nullable|exists:unit_kerja,id',
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
        $atasanList = User::whereIn('role', ['ATASAN'])
            ->where('status_pegawai', 'AKTIF')
            ->where('id', '!=', $id)
            ->orderBy('name')
            ->get(['id', 'name', 'jabatan']);
        return view('admin.pegawai.edit', compact('pegawai', 'units', 'atasanList'));
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
            'unit_kerja_id' => 'nullable|exists:unit_kerja,id',
            'atasan_id' => 'nullable|exists:users,id',
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
