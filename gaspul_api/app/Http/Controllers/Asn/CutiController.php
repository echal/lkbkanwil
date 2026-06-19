<?php

namespace App\Http\Controllers\Asn;

use App\Http\Controllers\Controller;
use App\Models\CutiAsn;
use App\Helpers\HolidayHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CutiController extends Controller
{
    private const JENIS_CUTI = [
        'Cuti Tahunan',
        'Cuti Bersalin (Anak Pertama s.d Ketiga)',
        'Cuti Bersalin (Anak Keempat dst) Bulan Pertama',
        'Cuti Bersalin (Anak Keempat dst) Bulan Kedua',
        'Cuti Bersalin (Anak Keempat dst) Bulan Ketiga',
        'Cuti Besar Bulan Pertama',
        'Cuti Besar Bulan Kedua',
        'Cuti Besar Bulan Ketiga',
        'Cuti Alasan Penting (1 s.d 2 Hari)',
        'Cuti Alasan Penting (Hari Ketiga dst)',
        'Cuti Sakit (1 Hari Sampai Dengan 14 Hari)',
        'Cuti Sakit (Lebih Dari 14 Hari Sampai Dengan 12 Bulan)',
        'Cuti Sakit (Lebih Dari 12 Bulan Sampai Dengan 18 Bulan)',
    ];

    public function create()
    {
        return view('asn.cuti.create', [
            'jenisCuti' => self::JENIS_CUTI,
            'tanggal'   => request('date', now()->format('Y-m-d')),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis'           => 'required|string|max:100',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan'      => 'nullable|string|max:1000',
            'bukti_dukung'    => 'required|url|max:500',
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
            'bukti_dukung.required'          => 'Link surat resmi wajib diisi.',
            'bukti_dukung.url'               => 'Link surat resmi harus berupa URL yang valid.',
        ]);

        $asn     = Auth::user();
        $mulai   = $validated['tanggal_mulai'];
        $selesai = $validated['tanggal_selesai'];

        // Validasi: maksimal 30 hari
        $jumlahHari = Carbon::parse($mulai)->diffInDays(Carbon::parse($selesai)) + 1;
        if ($jumlahHari > 30) {
            return back()->withInput()->withErrors([
                'tanggal_selesai' => 'Maksimal input cuti adalah 30 hari sekaligus.',
            ]);
        }

        // Validasi: tidak boleh overlap dengan cuti yang sudah ada
        if (CutiAsn::hasOverlap($asn->id, $mulai, $selesai)) {
            return back()->withInput()->withErrors([
                'tanggal_mulai' => 'Tanggal tersebut sudah tercatat sebagai cuti. Periksa kembali riwayat cuti Anda.',
            ]);
        }

        // Validasi: jenis harus termasuk daftar resmi
        if (! in_array($validated['jenis'], self::JENIS_CUTI)) {
            return back()->withInput()->withErrors([
                'jenis' => 'Jenis cuti tidak valid.',
            ]);
        }

        CutiAsn::create([
            'user_id'         => $asn->id,
            'kategori'        => 'CUTI',
            'jenis'           => $validated['jenis'],
            'tanggal_mulai'   => $mulai,
            'tanggal_selesai' => $selesai,
            'keterangan'      => $validated['keterangan'] ?? null,
            'bukti_dukung'    => $validated['bukti_dukung'],
        ]);

        return redirect()->route('asn.cuti.index')
            ->with('success', 'Cuti/Dinas Luar berhasil disimpan.');
    }

    public function index()
    {
        $asn  = Auth::user();
        $list = CutiAsn::where('user_id', $asn->id)
            ->orderByDesc('tanggal_mulai')
            ->paginate(15);

        return view('asn.cuti.index', compact('list'));
    }

    public function destroy($id)
    {
        $asn  = Auth::user();
        $cuti = CutiAsn::where('id', $id)->where('user_id', $asn->id)->firstOrFail();

        // Hanya boleh hapus jika belum lewat (tanggal_mulai >= hari ini)
        if ($cuti->tanggal_mulai->lt(now()->startOfDay())) {
            return back()->with('error', 'Cuti yang sudah berjalan tidak dapat dihapus. Hubungi administrator.');
        }

        $cuti->delete();

        return back()->with('success', 'Data cuti berhasil dihapus.');
    }
}
