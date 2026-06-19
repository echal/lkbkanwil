@extends('layouts.app')

@section('title', 'Koreksi Laporan Bulanan')

@section('content')
@php
    $namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
                  'Juli','Agustus','September','Oktober','November','Desember'];
@endphp

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Koreksi Laporan Bulanan</h1>
        <p class="text-sm text-gray-500 mt-1">
            Daftar laporan bulan <strong>{{ $namaBulan[$bulan] }} {{ $tahun }}</strong>
            yang berpotensi salah kirim (status DIKIRIM / DISETUJUI).
            Koreksi akan memindahkan laporan ke bulan sebelumnya.
        </p>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm flex items-start gap-2">
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
        {{ $errors->first() }}
    </div>
    @endif

    {{-- Info --}}
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-4 py-3 flex items-start gap-2">
        <svg class="w-4 h-4 text-yellow-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <p class="text-sm text-yellow-800">
            Tombol <strong>"Koreksi ke Bulan Sebelumnya"</strong> akan menghapus laporan ini
            dan membuat laporan baru di bulan sebelumnya dengan status <strong>DRAFT</strong>.
            ASN perlu mengirim ulang ke atasan setelah dikoreksi.
        </p>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-700">
                Laporan {{ $namaBulan[$bulan] }} {{ $tahun }}
                <span class="ml-2 text-xs font-normal text-gray-400">(DIKIRIM &amp; DISETUJUI)</span>
            </h3>
            <span class="text-xs text-gray-400">{{ $laporan->count() }} laporan</span>
        </div>

        @if($laporan->isEmpty())
        <div class="px-5 py-12 text-center text-sm text-gray-400">
            Tidak ada laporan bulan {{ $namaBulan[$bulan] }} {{ $tahun }} dengan status DIKIRIM atau DISETUJUI.
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-center w-10">#</th>
                        <th class="px-4 py-3 text-left">Nama ASN</th>
                        <th class="px-4 py-3 text-left">NIP</th>
                        <th class="px-4 py-3 text-left">Unit Kerja</th>
                        <th class="px-4 py-3 text-center">Bulan</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Tgl Kirim</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($laporan as $i => $lap)
                    @php
                        $bulanBaru = $lap->bulan == 1 ? 12 : $lap->bulan - 1;
                        $tahunBaru = $lap->bulan == 1 ? $lap->tahun - 1 : $lap->tahun;
                        $badgeClass = $lap->status === 'DISETUJUI'
                            ? 'bg-green-100 text-green-700'
                            : 'bg-yellow-100 text-yellow-700';
                    @endphp
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-center text-xs text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800 leading-tight">{{ $lap->user->name ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs">
                            {{ $lap->user->nip ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs">
                            {{ $lap->user->unitKerja->nama_unit ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 font-medium">
                            {{ $namaBulan[$lap->bulan] }} {{ $lap->tahun }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold {{ $badgeClass }}">
                                {{ $lap->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">
                            {{ $lap->created_at?->setTimezone('Asia/Makassar')->format('d M Y') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST"
                                  action="{{ route('admin.laporan-bulanan.koreksi', $lap->id) }}"
                                  onsubmit="return confirm('Koreksi laporan {{ $lap->user->name ?? '' }} dari {{ $namaBulan[$lap->bulan] }} ke {{ $namaBulan[$bulanBaru] }} {{ $tahunBaru }}?\n\nLaporan akan diset ke DRAFT dan ASN perlu mengirim ulang.')">
                                @csrf
                                <input type="hidden" name="bulan_baru" value="{{ $bulanBaru }}">
                                <input type="hidden" name="tahun_baru" value="{{ $tahunBaru }}">
                                <input type="hidden" name="alasan"     value="Koreksi salah kirim bulan — dari {{ $namaBulan[$lap->bulan] }} {{ $lap->tahun }} ke {{ $namaBulan[$bulanBaru] }} {{ $tahunBaru }}">
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    → {{ $namaBulan[$bulanBaru] }} {{ $tahunBaru }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</div>
@endsection
