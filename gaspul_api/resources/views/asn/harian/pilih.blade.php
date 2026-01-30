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

    <!-- Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">

        <!-- Card: Kinerja Harian -->
        <a href="{{ route('asn.harian.form-kinerja') }}"
           class="group block bg-white rounded-2xl border-2 border-gray-200 hover:border-green-400 hover:shadow-2xl transition-all duration-300 overflow-hidden">
            <div class="p-8">
                <!-- Icon -->
                <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>

                <!-- Title -->
                <h3 class="text-2xl font-bold text-gray-800 mb-3 group-hover:text-green-600 transition">
                    Kinerja Harian
                </h3>

                <!-- Description -->
                <p class="text-gray-600 mb-4 leading-relaxed">
                    Input kegiatan harian sesuai dengan rencana kerja atau SKP Tahunan Anda.
                </p>

                <!-- Features -->
                <ul class="space-y-2 text-sm text-gray-600">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Kegiatan rutin sesuai rencana kerja</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Tercatat dalam progres & satuan</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Dapat di-upload link bukti</span>
                    </li>
                </ul>

                <!-- Arrow -->
                <div class="mt-6 flex items-center text-green-600 font-semibold group-hover:translate-x-2 transition-transform">
                    <span>Pilih Kinerja Harian</span>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </div>
            </div>
        </a>

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
