@extends('layouts.app')

@section('title', 'SKP Tahunan')
@section('page-title', 'SKP Tahunan')

@section('content')
<div class="space-y-6" x-data="{ deleteId: null }">

    <!-- Header & Filter Tahun -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">SKP Tahunan</h2>
                <p class="text-blue-100">{{ $asn->name }} - NIP: {{ $asn->nip }}</p>
            </div>
            <div class="text-right">
                <form method="GET" action="{{ route('asn.skp-tahunan.index') }}">
                    <label class="text-sm text-blue-100 block mb-2">Tahun</label>
                    <select name="tahun" onchange="this.form.submit()"
                        class="px-4 py-2 bg-white text-gray-800 rounded-lg border border-blue-300 focus:ring-2 focus:ring-blue-300">
                        @for($y = 2024; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- SKP Status Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Status SKP Tahunan {{ $tahun }}</h3>
                <p class="text-sm text-gray-600 mt-1">Total Butir Kinerja: {{ $skpTahunan->total_butir_kinerja }}</p>
            </div>
            <div>
                @if($skpTahunan->status === 'DRAFT')
                    <span class="px-4 py-2 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">Draft</span>
                @elseif($skpTahunan->status === 'DIAJUKAN')
                    <span class="px-4 py-2 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">Diajukan</span>
                @elseif($skpTahunan->status === 'DISETUJUI')
                    <span class="px-4 py-2 text-sm font-semibold rounded-full bg-green-100 text-green-800">Disetujui</span>
                @elseif($skpTahunan->status === 'DITOLAK')
                    <span class="px-4 py-2 text-sm font-semibold rounded-full bg-red-100 text-red-800">Ditolak</span>
                @endif
            </div>
        </div>

        @if($skpTahunan->status === 'DITOLAK' && $skpTahunan->catatan_atasan)
        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-red-800 mb-1">Catatan Atasan:</h4>
                    <p class="text-sm text-red-700">{{ $skpTahunan->catatan_atasan }}</p>
                </div>
            </div>
        </div>
        @endif

        @if($skpTahunan->canAddDetails())
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-600">Anda dapat menambah atau mengedit butir kinerja</p>
            <a href="{{ route('asn.skp-tahunan.create', ['skp_tahunan_id' => $skpTahunan->id]) }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                + Tambah Butir Kinerja
            </a>
        </div>
        @endif

        @if($skpTahunan->canBeSubmitted())
        <div class="flex items-center justify-end pt-4 border-t border-gray-200 mt-4">
            <form method="POST" action="{{ route('asn.skp-tahunan.submit', $skpTahunan->id) }}"
                  onsubmit="return confirm('Apakah Anda yakin ingin mengajukan SKP Tahunan ini ke atasan? SKP tidak dapat diubah setelah diajukan.')">
                @csrf
                <button type="submit"
                    class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-semibold">
                    Ajukan ke Atasan
                </button>
            </form>
        </div>
        @endif
    </div>

    <!-- Daftar Butir Kinerja -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Daftar Butir Kinerja</h3>
            @if($skpTahunan->canAddDetails())
            <a href="{{ route('asn.skp-tahunan.create', ['skp_tahunan_id' => $skpTahunan->id]) }}"
               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Butir Kinerja
                </span>
            </a>
            @endif
        </div>

        @if($skpTahunan->details->isEmpty())
        <div class="p-12 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-600 mb-2">Belum Ada Butir Kinerja</h3>
            <p class="text-sm text-gray-500 mb-4">Silakan tambah butir kinerja untuk SKP Tahunan Anda</p>
            @if($skpTahunan->canAddDetails())
            <a href="{{ route('asn.skp-tahunan.create', ['skp_tahunan_id' => $skpTahunan->id]) }}"
               class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                + Tambah Butir Kinerja
            </a>
            @endif
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">RHK Pimpinan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Rencana Aksi</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Target</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Realisasi</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Capaian</th>
                        @if($skpTahunan->canEditDetails())
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($skpTahunan->details as $index => $detail)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="font-medium">{{ $detail->indikatorKinerja->nama_indikator ?? '-' }}</div>
                            <div class="text-xs text-gray-500 mt-1">Kode: {{ $detail->indikatorKinerja->kode_indikator ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="max-w-md">{{ $detail->rencana_aksi }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="font-semibold">{{ $detail->target_tahunan }}</span> {{ $detail->satuan }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="font-semibold">{{ $detail->realisasi_tahunan }}</span> {{ $detail->satuan }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @php
                                $persen = $detail->capaian_persen;
                            @endphp
                            <div class="flex items-center space-x-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2 w-20">
                                    <div class="h-2 rounded-full transition-all duration-300
                                        {{ $persen >= 100 ? 'bg-green-500' :
                                           ($persen >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                         style="width: {{ min($persen, 100) }}%">
                                    </div>
                                </div>
                                <span class="font-semibold text-gray-900">{{ $persen }}%</span>
                            </div>
                        </td>
                        @if($skpTahunan->canEditDetails())
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('asn.skp-tahunan.edit', $detail->id) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                    Edit
                                </a>
                                <span class="text-gray-300">|</span>
                                <button @click="deleteId = {{ $detail->id }}"
                                    class="text-red-600 hover:text-red-800 font-medium">
                                    Hapus
                                </button>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($skpTahunan->canAddDetails())
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50">
            <p class="text-sm text-gray-600">Total: {{ $skpTahunan->total_butir_kinerja }} Butir Kinerja</p>
            <a href="{{ route('asn.skp-tahunan.create', ['skp_tahunan_id' => $skpTahunan->id]) }}"
               class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Butir Kinerja
                </span>
            </a>
        </div>
        @endif
        @endif
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteId !== null"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="deleteId = null">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
            <div class="flex items-start mb-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Konfirmasi Hapus</h3>
                    <p class="text-sm text-gray-600">
                        Apakah Anda yakin ingin menghapus butir kinerja ini?
                        Semua rencana aksi bulanan yang terkait juga akan terhapus.
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-end space-x-3 mt-6">
                <button @click="deleteId = null"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Batal
                </button>
                <form :action="'/asn/skp-tahunan/destroy/' + deleteId" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
    {{ session('error') }}
</div>
@endif
@endsection
