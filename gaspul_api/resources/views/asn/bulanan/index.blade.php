@extends('layouts.app')

@section('title', 'Laporan Bulanan Kinerja')
@section('page-title', 'Laporan Bulanan')

@section('content')
<div class="space-y-6" id="laporan-bulanan">

    {{-- Filter Tahun & Bulan --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" action="{{ route('asn.bulanan.index') }}" class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 mr-2">Tahun:</label>
                    <select name="tahun" class="px-3 py-2 border border-gray-300 rounded-lg">
                        @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 mr-2">Bulan:</label>
                    <select name="bulan" class="px-3 py-2 border border-gray-300 rounded-lg">
                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                            @endphp
                            <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
                        @endfor
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    Tampilkan
                </button>
            </div>

            @if($hasData ?? false)
            <div class="flex space-x-2">
                <a href="{{ route('asn.bulanan.export-pdf', ['tahun' => $tahun, 'bulan' => $bulan]) }}"
                   class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Cetak PDF
                </a>
                @if($statusLaporan === 'DRAFT')
                <form method="POST" action="{{ route('asn.bulanan.kirim-atasan') }}" class="inline">
                    @csrf
                    <input type="hidden" name="tahun" value="{{ $tahun }}">
                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition"
                            onclick="return confirm('Kirim laporan ke atasan?')">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Kirim ke Atasan
                    </button>
                </form>
                @endif
            </div>
            @endif
        </form>
    </div>

    @if(!($hasData ?? false))
        {{-- No Data State --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Belum Ada Data Laporan</h3>
            <p class="text-gray-600">Silakan pilih tahun dan bulan, atau mulai input kinerja harian.</p>
        </div>
    @else
        {{-- Laporan Content --}}
        @include('asn.bulanan.partials.header')
        @include('asn.bulanan.partials.ringkasan')
        @include('asn.bulanan.partials.rekap-rhk')
        @include('asn.bulanan.partials.rekap-detail-harian')
        @include('asn.bulanan.partials.rekap-harian')
        @include('asn.bulanan.partials.kesimpulan')
    @endif

</div>
@endsection
