@extends('layouts.app')

@section('title', 'Monitoring Harian Bawahan')
@section('page-title', 'Monitoring Harian Bawahan')

@section('content')
<div class="space-y-6">

    <!-- Header & Biodata Atasan -->
    <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-2">Monitoring Harian Bawahan</h2>
                <div class="space-y-1 text-green-100">
                    <p><span class="font-semibold">Atasan:</span> {{ $atasan->name }}</p>
                    <p><span class="font-semibold">NIP:</span> {{ $atasan->nip }}</p>
                    <p><span class="font-semibold">Unit Kerja:</span> {{ $atasan->unitKerja->nama_unit ?? '-' }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-green-100 text-sm">Tanggal Aktif</p>
                <p class="text-2xl font-bold">{{ \Carbon\Carbon::parse($tanggal)->locale('id')->isoFormat('D MMM Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="GET" action="{{ route('atasan.harian-bawahan.index') }}" class="flex items-end gap-4">
            <!-- Mode -->
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Mode</label>
                <select name="mode" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    <option value="harian" {{ $mode === 'harian' ? 'selected' : '' }}>Harian</option>
                    <option value="mingguan" {{ $mode === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                    <option value="bulanan" {{ $mode === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                </select>
            </div>

            <!-- Tanggal -->
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal</label>
                <input type="date" name="tanggal" value="{{ $tanggal }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            </div>

            <!-- Submit -->
            <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                Filter
            </button>
        </form>
    </div>

    <!-- Tabel Monitoring ASN -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Daftar Monitoring Per ASN</h3>
            <p class="text-sm text-gray-600 mt-1">Mode: {{ ucfirst($mode) }} | Total: {{ $bawahan_list->count() }} ASN</p>
        </div>

        @if($bawahan_list->isEmpty())
        <div class="p-12 text-center">
            <p class="text-gray-500">Belum ada ASN di unit kerja Anda</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ASN</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Jam</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">KH</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">TLA</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($bawahan_list as $index => $asn)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $index + 1 }}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $asn->name }}</div>
                            <div class="text-sm text-gray-500">NIP: {{ $asn->nip }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $asn->total_jam }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $asn->total_kh }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $asn->total_tla }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{!! $asn->status_badge !!}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('atasan.harian-bawahan.detail', ['user_id' => $asn->id, 'tanggal' => $tanggal, 'mode' => $mode]) }}"
                                class="text-blue-600 hover:text-blue-900 font-medium">
                                Detail
                            </a>
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
