@extends('layouts.app')

@section('title', 'Data Pegawai - Admin')
@section('page-title', 'Data Pegawai')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Data Pegawai</h2>
            <p class="text-sm text-gray-600 mt-1">Kelola Data Pegawai</p>
        </div>
        <a href="{{ route('admin.pegawai.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Pegawai
        </a>
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

    {{-- Filter Form --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="GET" action="{{ route('admin.pegawai.index') }}" class="space-y-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Filter & Pencarian</h3>
                @if(request()->hasAny(['nip', 'unit_kerja_id', 'role', 'status']))
                    <a href="{{ route('admin.pegawai.index') }}"
                       class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Reset Filter
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Filter NIP --}}
                <div>
                    <label for="nip" class="block text-sm font-medium text-gray-700 mb-2">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Cari NIP
                    </label>
                    <input type="text"
                           name="nip"
                           id="nip"
                           value="{{ request('nip') }}"
                           placeholder="Contoh: 198901012010011001"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- Filter Unit Kerja --}}
                <div>
                    <label for="unit_kerja_id" class="block text-sm font-medium text-gray-700 mb-2">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Unit Kerja
                    </label>
                    <select name="unit_kerja_id"
                            id="unit_kerja_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Semua Unit Kerja</option>
                        @foreach($unitKerjaList as $unit)
                            <option value="{{ $unit->id }}" {{ request('unit_kerja_id') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->nama_unit }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Role --}}
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Role
                    </label>
                    <select name="role"
                            id="role"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Semua Role</option>
                        <option value="ADMIN" {{ request('role') == 'ADMIN' ? 'selected' : '' }}>ADMIN</option>
                        <option value="ATASAN" {{ request('role') == 'ATASAN' ? 'selected' : '' }}>ATASAN</option>
                        <option value="ASN" {{ request('role') == 'ASN' ? 'selected' : '' }}>ASN</option>
                    </select>
                </div>

                {{-- Filter Status --}}
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Status
                    </label>
                    <select name="status"
                            id="status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Semua Status</option>
                        <option value="AKTIF" {{ request('status') == 'AKTIF' ? 'selected' : '' }}>AKTIF</option>
                        <option value="NONAKTIF" {{ request('status') == 'NONAKTIF' ? 'selected' : '' }}>NONAKTIF</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <div class="text-sm text-gray-600">
                    @if($pegawai->total() > 0)
                        Menampilkan {{ $pegawai->firstItem() }} - {{ $pegawai->lastItem() }} dari {{ $pegawai->total() }} pegawai
                    @else
                        Tidak ada data ditemukan
                    @endif
                </div>
                <button type="submit"
                        class="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 019 17v-5.586L4.293 6.707A1 1 0 014 6V4z"/>
                    </svg>
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Kerja</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jabatan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($pegawai as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->nip }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $item->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $item->email }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $item->unitKerja->nama_unit ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $item->jabatan ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $item->role === 'ADMIN' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $item->role === 'ATASAN' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $item->role === 'ASN' ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ $item->role }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $item->status_pegawai === 'AKTIF' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $item->status_pegawai }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('admin.pegawai.edit', $item->id) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <form action="{{ route('admin.pegawai.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus pegawai ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center py-8">
                                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-gray-600 font-medium">
                                    @if(request()->hasAny(['nip', 'unit_kerja_id', 'role', 'status']))
                                        Tidak ada pegawai yang sesuai dengan filter
                                    @else
                                        Belum ada data pegawai
                                    @endif
                                </p>
                                @if(request()->hasAny(['nip', 'unit_kerja_id', 'role', 'status']))
                                    <a href="{{ route('admin.pegawai.index') }}"
                                       class="mt-3 text-blue-600 hover:text-blue-800 text-sm">
                                        Reset Filter
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($pegawai->hasPages())
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Menampilkan
                        <span class="font-medium">{{ $pegawai->firstItem() }}</span>
                        sampai
                        <span class="font-medium">{{ $pegawai->lastItem() }}</span>
                        dari
                        <span class="font-medium">{{ $pegawai->total() }}</span>
                        hasil
                    </div>
                    <div>
                        {{ $pegawai->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
