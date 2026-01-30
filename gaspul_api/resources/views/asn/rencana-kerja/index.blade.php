@extends('layouts.app')

@section('title', 'Rencana Kerja - Laporan Harian')
@section('page-title', 'Rencana Kerja')

@section('content')
@php
    $namaBulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    $currentYear = now()->year;
    $currentMonth = now()->month;
@endphp

<div class="space-y-6">

    <!-- Header with Year Filter -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rencana Kerja Tahun {{ $tahun }}</h2>
            <p class="text-sm text-gray-600 mt-1">Kelola rencana aksi bulanan berdasarkan RHK Tahunan Anda</p>
        </div>

        <!-- Year Selector -->
        <form method="GET" action="{{ route('asn.rencana-kerja.index') }}" class="flex items-center space-x-3">
            <label for="tahun" class="text-sm font-medium text-gray-700">Tahun:</label>
            <select name="tahun" id="tahun"
                    onchange="this.form.submit()"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @for($y = $currentYear - 2; $y <= $currentYear + 2; $y++)
                    <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg" x-data="{show: true}" x-show="show" x-transition>
        <div class="flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button @click="show = false" class="text-green-700 hover:text-green-900">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg" x-data="{show: true}" x-show="show" x-transition>
        <div class="flex items-center justify-between">
            <span>{{ session('error') }}</span>
            <button @click="show = false" class="text-red-700 hover:text-red-900">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    </div>
    @endif

    <!-- Info Card -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-semibold mb-1">Cara Menggunakan:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Setiap butir RHK Tahunan memiliki 12 card bulan (Jan-Des)</li>
                    <li>Klik <strong class="bg-green-100 px-1 rounded">Tambah</strong> pada bulan yang belum diisi</li>
                    <li>Klik <strong class="bg-blue-100 px-1 rounded">Edit</strong> untuk mengubah rencana yang sudah ada</li>
                </ul>
            </div>
        </div>
    </div>

    @if(!$skpTahunan)
        <!-- No SKP Tahunan -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Belum Ada SKP Tahunan</h3>
            <p class="text-gray-600 mb-4">Anda belum memiliki SKP Tahunan untuk tahun {{ $tahun }}.</p>
            <a href="{{ route('asn.skp-tahunan-v2.index') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat SKP Tahunan
            </a>
        </div>
    @elseif($rhkList->isEmpty())
        <!-- No RHK -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Belum Ada RHK Tahunan</h3>
            <p class="text-gray-600">SKP Tahunan Anda belum memiliki butir RHK. Silakan tambahkan RHK terlebih dahulu.</p>
        </div>
    @else
        <!-- RHK List with Monthly Calendar -->
        @foreach($rhkList as $rhk)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

                <!-- RHK Header -->
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-200 p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 mr-2">
                                    Butir RHK #{{ $loop->iteration }}
                                </span>
                                <span class="text-xs text-gray-600">{{ $rhk['total_terisi'] }}/12 bulan terisi</span>
                            </div>

                            <h3 class="text-lg font-bold text-gray-900 mb-2">
                                {{ $rhk['indikator_kinerja'] }}
                            </h3>

                            <div class="text-sm text-gray-600 space-y-1">
                                <div class="flex items-start">
                                    <svg class="w-4 h-4 mt-0.5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <span><strong>Rencana Aksi:</strong> {{ Str::limit($rhk['rencana_aksi'], 100) }}</span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <span><strong>Target Tahunan:</strong> {{ $rhk['target_tahunan'] }} {{ $rhk['satuan'] }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Circle -->
                        <div class="ml-6 text-center">
                            <div class="relative inline-flex items-center justify-center">
                                <svg class="w-20 h-20 transform -rotate-90">
                                    <circle cx="40" cy="40" r="30" stroke="#E5E7EB" stroke-width="6" fill="none"/>
                                    <circle cx="40" cy="40" r="30"
                                            stroke="#4F46E5"
                                            stroke-width="6"
                                            fill="none"
                                            stroke-dasharray="{{ 2 * 3.14159 * 30 }}"
                                            stroke-dashoffset="{{ 2 * 3.14159 * 30 * (1 - $rhk['total_terisi'] / 12) }}"
                                            class="transition-all duration-500"/>
                                </svg>
                                <span class="absolute text-sm font-bold text-indigo-600">
                                    {{ round(($rhk['total_terisi'] / 12) * 100) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 12 Month Cards -->
                <div class="p-6 bg-gray-50">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-12 gap-3">
                        @for($bulan = 1; $bulan <= 12; $bulan++)
                            @php
                                $bulanInfo = $rhk['bulan_data'][$bulan];
                                $sudahAda = $bulanInfo['exists'];
                                $rencana = $bulanInfo['rencana'];
                                $isBulanSekarang = ($tahun == $currentYear && $bulan == $currentMonth);
                            @endphp

                            <div class="relative group">
                                <!-- Month Card -->
                                <div class="bg-white rounded-lg border-2 {{ $sudahAda ? 'border-green-400' : 'border-gray-200' }} {{ $isBulanSekarang ? 'ring-2 ring-purple-300' : '' }} p-3 hover:shadow-md transition">

                                    <!-- Month Name -->
                                    <div class="text-center mb-2">
                                        <div class="text-xs font-semibold text-gray-600">{{ $namaBulan[$bulan] }}</div>
                                        @if($isBulanSekarang)
                                            <div class="text-[10px] text-purple-600 font-semibold mt-1">Bulan Ini</div>
                                        @endif
                                    </div>

                                    <!-- Status Icon -->
                                    <div class="flex justify-center mb-2">
                                        @if($sudahAda)
                                            <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <svg class="w-8 h-8 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </div>

                                    <!-- Action Button -->
                                    @if($sudahAda)
                                        <a href="{{ route('asn.rencana-kerja.edit', $rencana->id) }}"
                                           class="block w-full text-center px-2 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition">
                                            Edit
                                        </a>
                                    @else
                                        <a href="{{ route('asn.rencana-kerja.tambah', ['skp_tahunan_detail_id' => $rhk['id'], 'bulan' => $bulan, 'tahun' => $tahun]) }}"
                                           class="block w-full text-center px-2 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition">
                                            + Tambah
                                        </a>
                                    @endif
                                </div>

                                <!-- Tooltip (shows on hover) -->
                                @if($sudahAda)
                                    <div class="hidden group-hover:block absolute z-10 bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-64 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg">
                                        <div class="font-semibold mb-1">{{ $namaBulan[$bulan] }} {{ $tahun }}</div>
                                        <div class="mb-2">{{ Str::limit($rencana->rencana_aksi_bulanan, 100) }}</div>
                                        <div class="text-green-300">Target: {{ $rencana->target_bulanan }} {{ $rencana->satuan_target }}</div>
                                        <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2 rotate-45 w-2 h-2 bg-gray-900"></div>
                                    </div>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>

            </div>
        @endforeach
    @endif

    <!-- Legend -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Keterangan:</h4>
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center">
                <div class="w-4 h-4 rounded bg-green-500 mr-2"></div>
                <span class="text-gray-600">Sudah diisi</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 rounded bg-gray-300 mr-2"></div>
                <span class="text-gray-600">Belum diisi</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 rounded border-2 border-purple-400 mr-2"></div>
                <span class="text-gray-600">Bulan berjalan</span>
            </div>
        </div>
    </div>

</div>
@endsection
