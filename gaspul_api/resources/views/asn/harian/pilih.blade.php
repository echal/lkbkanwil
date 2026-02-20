@extends('layouts.app')

@section('title', 'Pilih Jenis Progres - Laporan Harian')
@section('page-title', 'Pilih Jenis Progres')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="text-center">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Pilih Jenis Progres</h2>
        <p class="text-gray-600">Pilih jenis progres yang ingin Anda inputkan hari ini</p>
    </div>

    @if(!$hasApprovedSkp)
    <!-- Warning: SKP belum disetujui -->
    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-amber-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h3 class="text-amber-800 font-semibold">SKP Tahunan Belum Disetujui</h3>
                <p class="text-amber-700 text-sm mt-1">{{ $skpMessage }}</p>
                <a href="{{ route('asn.skp-tahunan.index') }}" class="inline-flex items-center text-sm text-amber-600 hover:text-amber-800 font-medium mt-2">
                    Lihat SKP Tahunan
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">

        <!-- Card: Kinerja Harian -->
        @if($hasApprovedSkp)
        <a href="{{ route('asn.harian.form-kinerja') }}"
           class="group block bg-white rounded-2xl border-2 border-gray-200 hover:border-green-400 hover:shadow-2xl transition-all duration-300 overflow-hidden">
        @else
        <div class="group block bg-gray-100 rounded-2xl border-2 border-gray-300 overflow-hidden cursor-not-allowed opacity-75">
        @endif
            <div class="p-8">
                <!-- Icon with Lock Overlay -->
                <div class="relative">
                    <div class="w-20 h-20 bg-gradient-to-br {{ $hasApprovedSkp ? 'from-green-400 to-green-600' : 'from-gray-400 to-gray-500' }} rounded-2xl flex items-center justify-center mb-6 {{ $hasApprovedSkp ? 'group-hover:scale-110' : '' }} transition-transform duration-300 shadow-lg">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    @if(!$hasApprovedSkp)
                    <!-- Lock Icon Badge -->
                    <div class="absolute -top-2 -right-2 w-10 h-10 bg-red-500 rounded-full flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    @endif
                </div>

                <!-- Title -->
                <h3 class="text-2xl font-bold {{ $hasApprovedSkp ? 'text-gray-800 group-hover:text-green-600' : 'text-gray-500' }} mb-3 transition">
                    Kinerja Harian
                    @if(!$hasApprovedSkp)
                    <span class="text-sm font-normal text-red-500 ml-2">(Terkunci)</span>
                    @endif
                </h3>

                <!-- Description -->
                <p class="{{ $hasApprovedSkp ? 'text-gray-600' : 'text-gray-500' }} mb-4 leading-relaxed">
                    Input kegiatan harian sesuai dengan rencana kerja atau SKP Tahunan Anda.
                </p>

                <!-- Features -->
                <ul class="space-y-2 text-sm {{ $hasApprovedSkp ? 'text-gray-600' : 'text-gray-500' }}">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 {{ $hasApprovedSkp ? 'text-green-500' : 'text-gray-400' }} mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Kegiatan rutin sesuai rencana kerja</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 {{ $hasApprovedSkp ? 'text-green-500' : 'text-gray-400' }} mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Tercatat dalam progres & satuan</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 {{ $hasApprovedSkp ? 'text-green-500' : 'text-gray-400' }} mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Dapat di-upload link bukti</span>
                    </li>
                </ul>

                <!-- Arrow / Lock Message -->
                @if($hasApprovedSkp)
                <div class="mt-6 flex items-center text-green-600 font-semibold group-hover:translate-x-2 transition-transform">
                    <span>Pilih Kinerja Harian</span>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </div>
                @else
                <div class="mt-6 flex items-center text-red-500 font-medium">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    <span>SKP harus disetujui terlebih dahulu</span>
                </div>
                @endif
            </div>
        @if($hasApprovedSkp)
        </a>
        @else
        </div>
        @endif

        <!-- Card: Tugas Langsung Atasan -->
        <a href="{{ route('asn.harian.form-tla') }}"
           class="group block bg-white rounded-2xl border-2 border-gray-200 hover:border-blue-400 hover:shadow-2xl transition-all duration-300 overflow-hidden">
            <div class="p-8">
                <!-- Icon -->
                <div class="w-20 h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>

                <!-- Title -->
                <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-blue-600 transition">
                    Tugas Langsung Atasan
                </h3>

                <!-- Description -->
                <p class="text-gray-600 mb-4 leading-relaxed">
                    Input tugas tambahan atau insidental yang diberikan langsung oleh atasan Anda.
                </p>

                <!-- Features -->
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Tugas mendadak dari atasan</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Tidak terikat dengan rencana kerja</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Dapat di-upload link bukti</span>
                    </li>
                </ul>

                <!-- Arrow -->
                <div class="mt-6 flex items-center text-blue-600 font-semibold group-hover:translate-x-2 transition-transform">
                    <span>Pilih Tugas Langsung</span>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </div>
            </div>
        </a>

    </div>

    <!-- Back Button -->
    <div class="text-center mt-8">
        <a href="{{ route('asn.harian.index') }}"
           class="inline-flex items-center px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>
    </div>

</div>
@endsection
