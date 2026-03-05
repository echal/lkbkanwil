@extends('layouts.app')

@section('title', 'Import ASN')

@section('content')
<div class="p-6 max-w-2xl mx-auto">

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Import ASN dari Excel</h1>
            <p class="text-gray-500 text-sm mt-1">Upload file Excel untuk menambahkan data ASN secara massal.</p>
        </div>
        <a href="{{ route('admin.import-asn.template') }}"
            class="flex-shrink-0 inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-4 rounded-lg transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Download Template (CSV)
        </a>
    </div>

    {{-- Alert sukses --}}
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Alert error --}}
    @if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm">
        @foreach($errors->all() as $err)
        <p class="text-red-800">{{ $err }}</p>
        @endforeach
    </div>
    @endif

    {{-- Form Upload --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form action="{{ route('admin.import-asn.preview') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">File Excel <span class="text-red-500">*</span></label>
                <input type="file" name="file" accept=".csv"
                    class="block w-full text-sm text-gray-600 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 p-2">
                <p class="text-xs text-gray-400 mt-1">Format: .csv &nbsp;|&nbsp; Maks. 5 MB &nbsp;|&nbsp; Maks. 1000 baris</p>
            </div>

            <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-4 rounded-lg transition">
                Upload &amp; Preview
            </button>
        </form>
    </div>

    {{-- Panduan Format --}}
    <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Format Kolom Excel (Baris 1 = Header)</h2>
        <div class="overflow-x-auto">
            <table class="text-xs w-full border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border border-gray-200 px-3 py-2 text-left font-semibold">#</th>
                        <th class="border border-gray-200 px-3 py-2 text-left font-semibold">Kolom</th>
                        <th class="border border-gray-200 px-3 py-2 text-left font-semibold">Wajib</th>
                        <th class="border border-gray-200 px-3 py-2 text-left font-semibold">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td class="border border-gray-200 px-3 py-1.5">A</td><td class="border border-gray-200 px-3 py-1.5 font-mono">name</td><td class="border border-gray-200 px-3 py-1.5 text-red-500">Ya</td><td class="border border-gray-200 px-3 py-1.5">Nama lengkap</td></tr>
                    <tr><td class="border border-gray-200 px-3 py-1.5">B</td><td class="border border-gray-200 px-3 py-1.5 font-mono">email</td><td class="border border-gray-200 px-3 py-1.5 text-red-500">Ya</td><td class="border border-gray-200 px-3 py-1.5">Email unik, format email valid</td></tr>
                    <tr><td class="border border-gray-200 px-3 py-1.5">C</td><td class="border border-gray-200 px-3 py-1.5 font-mono">nip</td><td class="border border-gray-200 px-3 py-1.5 text-red-500">Ya</td><td class="border border-gray-200 px-3 py-1.5">18 digit angka, unik</td></tr>
                    <tr><td class="border border-gray-200 px-3 py-1.5">D</td><td class="border border-gray-200 px-3 py-1.5 font-mono">password</td><td class="border border-gray-200 px-3 py-1.5 text-red-500">Ya</td><td class="border border-gray-200 px-3 py-1.5">Min. 8 karakter (akan otomatis di-hash)</td></tr>
                    <tr><td class="border border-gray-200 px-3 py-1.5">E</td><td class="border border-gray-200 px-3 py-1.5 font-mono">role</td><td class="border border-gray-200 px-3 py-1.5 text-red-500">Ya</td><td class="border border-gray-200 px-3 py-1.5"><span class="font-mono bg-gray-100 px-1 rounded">ASN</span> atau <span class="font-mono bg-gray-100 px-1 rounded">ATASAN</span></td></tr>
                    <tr><td class="border border-gray-200 px-3 py-1.5">F</td><td class="border border-gray-200 px-3 py-1.5 font-mono">unit_kerja_id</td><td class="border border-gray-200 px-3 py-1.5 text-red-500">Ya</td><td class="border border-gray-200 px-3 py-1.5">ID unit kerja (angka)</td></tr>
                    <tr><td class="border border-gray-200 px-3 py-1.5">G</td><td class="border border-gray-200 px-3 py-1.5 font-mono">jabatan</td><td class="border border-gray-200 px-3 py-1.5 text-gray-400">Tidak</td><td class="border border-gray-200 px-3 py-1.5">Jabatan (boleh kosong)</td></tr>
                    <tr><td class="border border-gray-200 px-3 py-1.5">H</td><td class="border border-gray-200 px-3 py-1.5 font-mono">atasan_id</td><td class="border border-gray-200 px-3 py-1.5 text-gray-400">Tidak</td><td class="border border-gray-200 px-3 py-1.5">ID atasan langsung (angka, boleh kosong)</td></tr>
                    <tr><td class="border border-gray-200 px-3 py-1.5">I</td><td class="border border-gray-200 px-3 py-1.5 font-mono">status_pegawai</td><td class="border border-gray-200 px-3 py-1.5 text-red-500">Ya</td><td class="border border-gray-200 px-3 py-1.5"><span class="font-mono bg-gray-100 px-1 rounded">AKTIF</span> atau <span class="font-mono bg-gray-100 px-1 rounded">NONAKTIF</span></td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
