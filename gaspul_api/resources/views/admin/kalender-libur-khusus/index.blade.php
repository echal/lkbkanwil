@extends('layouts.app')

@section('title', 'Kalender Libur Khusus - Admin')
@section('page-title', 'Kalender Libur Khusus')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Kalender Libur Khusus</h2>
            <p class="text-sm text-gray-600 mt-1">Kelola hari libur khusus per jabatan (Guru, Penyuluh, Penghulu)</p>
        </div>
        <a href="{{ route('admin.kalender-libur-khusus.create') }}"
           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Kalender
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 flex items-center gap-2">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Info box --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
        <p class="font-semibold mb-1">Cara kerja:</p>
        <ul class="list-disc list-inside space-y-1 text-blue-700">
            <li>Status <strong>DRAFT</strong> — tidak mempengaruhi laporan atau dashboard apapun.</li>
            <li>Status <strong>AKTIF</strong> — guru dalam rentang tanggal tidak diwajibkan input harian, dan tidak muncul di daftar "belum isi" monitoring.</li>
            <li>Fitur hanya berlaku untuk laporan yang belum DISETUJUI atasan.</li>
            <li><strong>berlaku_ke_anak</strong> — libur berlaku untuk seluruh sub-unit di bawah unit yang dipilih.</li>
        </ul>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.kalender-libur-khusus.index') }}" class="flex gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Semua</option>
                <option value="DRAFT"  {{ request('status') === 'DRAFT'  ? 'selected' : '' }}>Draft</option>
                <option value="AKTIF"  {{ request('status') === 'AKTIF'  ? 'selected' : '' }}>Aktif</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Target Jabatan</label>
            <select name="target_khusus" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Semua</option>
                <option value="GURU"     {{ request('target_khusus') === 'GURU'     ? 'selected' : '' }}>Guru</option>
                <option value="PENYULUH" {{ request('target_khusus') === 'PENYULUH' ? 'selected' : '' }}>Penyuluh</option>
                <option value="PENGHULU" {{ request('target_khusus') === 'PENGHULU' ? 'selected' : '' }}>Penghulu</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm transition">Filter</button>
        <a href="{{ route('admin.kalender-libur-khusus.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm hover:bg-gray-50 transition">Reset</a>
    </form>

    {{-- Tabel --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">No</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Unit Kerja</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Target</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Tanggal Mulai</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Tanggal Selesai</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Keterangan</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Ke Anak</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($kalenders as $k)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $kalenders->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ $k->unitKerja?->nama_unit ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $k->target_khusus === 'GURU' ? 'bg-purple-100 text-purple-700' : '' }}
                                {{ $k->target_khusus === 'PENYULUH' ? 'bg-teal-100 text-teal-700' : '' }}
                                {{ $k->target_khusus === 'PENGHULU' ? 'bg-orange-100 text-orange-700' : '' }}
                            ">{{ $k->target_khusus }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($k->tanggal_mulai)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($k->tanggal_selesai)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-600 max-w-xs truncate" title="{{ $k->keterangan }}">{{ $k->keterangan }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($k->berlaku_ke_anak)
                                <span class="text-green-600 font-medium text-xs">Ya</span>
                            @else
                                <span class="text-gray-400 text-xs">Tidak</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $k->status === 'AKTIF' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $k->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Toggle status --}}
                                <form action="{{ route('admin.kalender-libur-khusus.toggle-status', $k) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="text-xs px-2 py-1 rounded border transition
                                            {{ $k->status === 'AKTIF'
                                                ? 'border-yellow-300 text-yellow-700 hover:bg-yellow-50'
                                                : 'border-green-300 text-green-700 hover:bg-green-50' }}"
                                        onclick="return confirm('{{ $k->status === 'AKTIF' ? 'Non-aktifkan kalender ini?' : 'Aktifkan kalender ini? Akan langsung mempengaruhi laporan dan monitoring.' }}')">
                                        {{ $k->status === 'AKTIF' ? 'Non-aktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>

                                <a href="{{ route('admin.kalender-libur-khusus.edit', $k) }}"
                                   class="text-blue-600 hover:text-blue-800 text-xs font-medium">Edit</a>

                                <form action="{{ route('admin.kalender-libur-khusus.destroy', $k) }}" method="POST"
                                      onsubmit="return confirm('Hapus kalender ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                            Belum ada data kalender libur khusus.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($kalenders->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $kalenders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
