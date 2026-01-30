@extends('layouts.app')

@section('title', 'Pusat Persetujuan')
@section('page-title', 'Pusat Persetujuan')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Pusat Persetujuan</h2>
            <p class="text-sm text-gray-600 mt-1">Monitoring dan persetujuan semua pengajuan dari bawahan</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">SKP Menunggu</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $approvalData['skp_pending_count'] }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">SKP Disetujui</p>
                    <p class="text-3xl font-bold text-green-600">{{ $approvalData['skp_approved_count'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">SKP Ditolak</p>
                    <p class="text-3xl font-bold text-red-600">{{ $approvalData['skp_rejected_count'] }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Bukti Dukung</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $approvalData['bukti_count'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" action="{{ route('atasan.approval.index') }}" class="flex items-center space-x-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="all" {{ $filterType === 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="skp" {{ $filterType === 'skp' ? 'selected' : '' }}>SKP Tahunan</option>
                    <option value="bukti" {{ $filterType === 'bukti' ? 'selected' : '' }}>Bukti Dukung</option>
                </select>
            </div>

            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="pending" {{ $filterStatus === 'pending' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                    <option value="approved" {{ $filterStatus === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ $filterStatus === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    Filter
                </button>
            </div>
        </form>
    </div>

    {{-- SKP Tahunan List --}}
    @if($filterType === 'all' || $filterType === 'skp')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">SKP Tahunan</h3>
                    <p class="text-sm text-gray-600 mt-1">Daftar pengajuan SKP Tahunan dari bawahan</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                    {{ $skpData->count() }} pengajuan
                </span>
            </div>

            @if($skpData->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach($skpData as $skp)
                        <div class="px-6 py-4 hover:bg-gray-50 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                            {{ substr($skp['asn_nama'], 0, 1) }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-gray-900">{{ $skp['asn_nama'] }}</h4>
                                            <p class="text-xs text-gray-500">{{ $skp['asn_nip'] }} • {{ $skp['asn_jabatan'] }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-600">Unit Kerja:</span>
                                            <span class="font-medium text-gray-900">{{ $skp['unit_kerja'] }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600">Tahun:</span>
                                            <span class="font-medium text-gray-900">{{ $skp['tahun'] }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600">Total RHK:</span>
                                            <span class="font-medium text-gray-900">{{ $skp['total_rhk'] }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600">Diajukan:</span>
                                            <span class="font-medium text-gray-900">{{ $skp['tanggal_pengajuan']->format('d/m/Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-6 flex items-center space-x-3">
                                    @if($skp['status'] === 'DIAJUKAN')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                            Menunggu Persetujuan
                                        </span>
                                    @elseif($skp['status'] === 'DISETUJUI')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                            Disetujui
                                        </span>
                                    @elseif($skp['status'] === 'DITOLAK')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                            Ditolak
                                        </span>
                                    @endif
                                    <a href="{{ $skp['url_detail'] }}"
                                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Detail & Approve
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-500">Tidak ada SKP Tahunan untuk status ini</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Bukti Dukung List --}}
    @if($filterType === 'all' || $filterType === 'bukti')
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Bukti Dukung Kinerja Harian</h3>
                    <p class="text-sm text-gray-600 mt-1">Bukti dukung yang telah diupload ASN (50 terbaru)</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                    {{ $buktiData->count() }} bukti
                </span>
            </div>

            @if($buktiData->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">No</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">ASN</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Kegiatan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">RHK</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($buktiData as $bukti)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $bukti['asn_nama'] }}</div>
                                        <div class="text-sm text-gray-500">{{ $bukti['asn_nip'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($bukti['tanggal'])->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 max-w-md truncate">
                                        {{ $bukti['kegiatan'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                                        {{ $bukti['indikator_kinerja'] ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <a href="{{ $bukti['bukti_dukung'] }}" target="_blank"
                                           class="text-blue-600 hover:text-blue-900 font-medium text-sm">
                                            Lihat Bukti
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-gray-500">Belum ada bukti dukung yang diupload</p>
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
