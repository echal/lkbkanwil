@extends('layouts.app')

@section('title', 'Riwayat Cuti / Dinas Luar')
@section('page-title', 'Riwayat Cuti / Dinas Luar')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <a href="{{ route('asn.harian.pilih') }}"
               class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Riwayat Cuti / Dinas Luar</h2>
                <p class="text-sm text-gray-500">Data cuti dan tugas kedinasan yang tercatat</p>
            </div>
        </div>
        <a href="{{ route('asn.cuti.create') }}"
           class="inline-flex items-center px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl text-sm font-semibold transition shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Cuti
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg text-sm text-red-700">
        {{ session('error') }}
    </div>
    @endif

    @if($list->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
        <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <p class="text-gray-500 font-medium">Belum ada data cuti / dinas luar.</p>
        <a href="{{ route('asn.cuti.create') }}" class="mt-3 inline-block text-orange-500 hover:text-orange-600 text-sm font-medium">
            Tambah sekarang →
        </a>
    </div>
    @else
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Periode</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Kategori</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Jenis</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Keterangan</th>
                    <th class="text-left px-5 py-3 font-semibold text-gray-600">Surat</th>
                    <th class="text-center px-5 py-3 font-semibold text-gray-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($list as $item)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-4">
                        <div class="font-medium text-gray-800">
                            {{ $item->tanggal_mulai->format('d M Y') }}
                        </div>
                        @if($item->tanggal_mulai->ne($item->tanggal_selesai))
                        <div class="text-xs text-gray-500">
                            s.d {{ $item->tanggal_selesai->format('d M Y') }}
                            ({{ $item->tanggal_mulai->diffInDays($item->tanggal_selesai) + 1 }} hari)
                        </div>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                            {{ $item->kategori === 'CUTI' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $item->kategori === 'CUTI' ? 'Cuti' : 'Dinas Luar' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-gray-700 max-w-xs">{{ $item->jenis }}</td>
                    <td class="px-5 py-4 text-gray-500 max-w-xs">{{ $item->keterangan ?? '-' }}</td>
                    <td class="px-5 py-4">
                        <a href="{{ $item->bukti_dukung }}" target="_blank"
                           class="inline-flex items-center text-blue-600 hover:text-blue-800 text-xs font-medium">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Lihat
                        </a>
                    </td>
                    <td class="px-5 py-4 text-center">
                        @if($item->tanggal_mulai->gte(now()->startOfDay()))
                        <form action="{{ route('asn.cuti.destroy', $item->id) }}" method="POST"
                              onsubmit="return confirm('Hapus data cuti ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-red-500 hover:text-red-700 text-xs font-medium transition">
                                Hapus
                            </button>
                        </form>
                        @else
                        <span class="text-gray-400 text-xs">Selesai</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($list->hasPages())
    <div class="flex justify-center">
        {{ $list->links() }}
    </div>
    @endif
    @endif

</div>
@endsection
