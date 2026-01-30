@extends('layouts.app')

@section('title', 'Detail SKP Tahunan')
@section('page-title', 'Detail SKP Tahunan')

@section('content')
<div class="space-y-6">

    {{-- Back Button --}}
    <div>
        <a href="{{ route('atasan.skp-tahunan.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Daftar SKP
        </a>
    </div>

    {{-- Header: ASN Identity --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-sm border border-blue-200 p-6">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                        {{ substr($asn->name, 0, 1) }}
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $asn->name }}</h2>
                        <p class="text-sm text-gray-600">SKP Tahunan {{ $skp->tahun }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">NIP</p>
                        <p class="text-sm font-medium text-gray-900">{{ $asn->nip ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Jabatan</p>
                        <p class="text-sm font-medium text-gray-900">{{ $asn->jabatan ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Unit Kerja</p>
                        <p class="text-sm font-medium text-gray-900">{{ $asn->unitKerja->nama_unit ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="ml-6">
                @if($skp->status === 'DRAFT')
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-gray-100 text-gray-800">
                        Draft
                    </span>
                @elseif($skp->status === 'DIAJUKAN')
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                        Menunggu Persetujuan
                    </span>
                @elseif($skp->status === 'DISETUJUI')
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                        Disetujui
                    </span>
                @elseif($skp->status === 'DITOLAK')
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                        Ditolak
                    </span>
                @endif
            </div>
        </div>

        @if($skp->catatan_atasan)
            <div class="mt-4 p-4 bg-white rounded-lg border border-blue-200">
                <p class="text-xs font-semibold text-gray-700 mb-1">Catatan Atasan:</p>
                <p class="text-sm text-gray-800">{{ $skp->catatan_atasan }}</p>
                @if($skp->approver)
                    <p class="text-xs text-gray-500 mt-2">
                        Oleh: {{ $skp->approver->name }} • {{ $skp->approved_at->format('d/m/Y H:i') }}
                    </p>
                @endif
            </div>
        @endif
    </div>

    {{-- Ringkasan SKP --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Butir Kinerja</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $rhkList->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Rencana Bulanan</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $rhkList->sum('total_rencana_bulanan') }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Tahun SKP</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $skp->tahun }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail RHK List --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Daftar Rencana Hasil Kerja (RHK)</h3>
            <p class="text-sm text-gray-600 mt-1">Detail butir kinerja yang telah ditetapkan ASN</p>
        </div>

        <div class="p-6 space-y-4">
            @forelse($rhkList as $rhk)
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    {{-- RHK Header --}}
                    <div class="bg-gradient-to-r from-gray-50 to-blue-50 p-4 border-b border-gray-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 mb-2">
                                    Butir #{{ $loop->iteration }}
                                </span>
                                <h4 class="text-base font-bold text-gray-900 mb-2">{{ $rhk['indikator_kinerja'] }}</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <span class="text-gray-600">Sasaran Kegiatan:</span>
                                        <span class="font-medium text-gray-900">{{ $rhk['sasaran_kegiatan'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Indikator Kinerja:</span>
                                        <span class="font-medium text-gray-900">{{ $rhk['indikator_kinerja'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- RHK Detail --}}
                    <div class="p-4 bg-white">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Rencana Aksi Tahunan</p>
                                <p class="text-sm font-medium text-gray-900">{{ $rhk['rencana_aksi'] }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Target Tahunan</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $rhk['target_tahunan'] }} {{ $rhk['satuan'] }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Rencana Bulanan</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $rhk['total_rencana_bulanan'] }} bulan terisi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">
                    <p>Belum ada RHK yang ditambahkan</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Approval Section (hanya tampil jika status DIAJUKAN) --}}
    @if($canApprove)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" x-data="{ showApproveForm: false, showRejectForm: false }">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Persetujuan SKP Tahunan</h3>
            <p class="text-sm text-gray-600 mb-6">Silakan setujui atau tolak SKP Tahunan ini dengan memberikan catatan kepada ASN.</p>

            <div class="flex space-x-3">
                {{-- Button Setujui --}}
                <button @click="showApproveForm = !showApproveForm; showRejectForm = false"
                        class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-medium">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Setujui SKP
                </button>

                {{-- Button Tolak --}}
                <button @click="showRejectForm = !showRejectForm; showApproveForm = false"
                        class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-medium">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Tolak SKP
                </button>
            </div>

            {{-- Form Setujui --}}
            <div x-show="showApproveForm" x-transition class="mt-6 p-4 bg-green-50 rounded-lg border border-green-200">
                <form method="POST" action="{{ route('atasan.skp-tahunan.approve', $skp->id) }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Persetujuan (Opsional)</label>
                        <textarea name="catatan_atasan" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                                  placeholder="Berikan catatan atau apresiasi untuk ASN..."></textarea>
                    </div>
                    <div class="flex space-x-3">
                        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-medium">
                            Konfirmasi Setujui
                        </button>
                        <button type="button" @click="showApproveForm = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">
                            Batal
                        </button>
                    </div>
                </form>
            </div>

            {{-- Form Tolak --}}
            <div x-show="showRejectForm" x-transition class="mt-6 p-4 bg-red-50 rounded-lg border border-red-200">
                <form method="POST" action="{{ route('atasan.skp-tahunan.reject', $skp->id) }}">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Penolakan (Wajib) <span class="text-red-600">*</span></label>
                        <textarea name="catatan_atasan" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500"
                                  placeholder="Jelaskan alasan penolakan dan apa yang perlu diperbaiki..."></textarea>
                    </div>
                    <div class="flex space-x-3">
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-medium">
                            Konfirmasi Tolak
                        </button>
                        <button type="button" @click="showRejectForm = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
