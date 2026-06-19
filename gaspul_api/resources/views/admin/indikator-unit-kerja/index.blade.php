@extends('layouts.app')

@section('title', 'Pemetaan Indikator per Unit Kerja - Admin')
@section('page-title', 'Pemetaan Indikator')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Pemetaan Indikator per Unit Kerja</h2>
        <p class="text-sm text-gray-600 mt-1">Pilih unit kerja lalu centang indikator yang berlaku untuk unit tersebut.</p>
    </div>

    {{-- Alert success --}}
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

    {{-- Form pilih unit kerja --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="GET" action="{{ route('admin.indikator-unit-kerja.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[260px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Unit Kerja</label>
                <select name="unit_kerja_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">— Pilih Unit Kerja —</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ ($selectedUnit && $selectedUnit->id == $unit->id) ? 'selected' : '' }}>
                            {{ $unit->nama_unit }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                Tampilkan
            </button>
        </form>
    </div>

    {{-- Tabel pemetaan (muncul setelah unit dipilih) --}}
    @if($selectedUnit)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200"
         x-data="{
             allChecked: false,
             indikatorChecked: {{ $indikatorList->where('checked', true)->count() }},
             total: {{ $indikatorList->count() }},
             toggleAll(val) {
                 document.querySelectorAll('.cb-indikator').forEach(cb => cb.checked = val);
                 this.indikatorChecked = val ? this.total : 0;
             },
             updateCount() {
                 this.indikatorChecked = document.querySelectorAll('.cb-indikator:checked').length;
                 this.allChecked = (this.indikatorChecked === this.total);
             }
         }">

        {{-- Header tabel --}}
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">{{ $selectedUnit->nama_unit }}</h3>
                <p class="text-sm text-gray-500 mt-0.5">
                    <span class="font-medium text-blue-600" x-text="indikatorChecked"></span>
                    dari <span class="font-medium">{{ $indikatorList->count() }}</span> indikator dipetakan
                </p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                {{ $mappedCount }} dipetakan
            </span>
        </div>

        <form method="POST" action="{{ route('admin.indikator-unit-kerja.update', $selectedUnit->id) }}">
            @csrf

            {{-- Pilih semua --}}
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                <input type="checkbox" id="select-all"
                    class="w-4 h-4 text-blue-600 rounded border-gray-300"
                    x-model="allChecked"
                    @change="toggleAll($event.target.checked)">
                <label for="select-all" class="text-sm font-medium text-gray-700 cursor-pointer select-none">
                    Pilih / Hapus Semua
                </label>
            </div>

            {{-- Tabel indikator --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">
                                <svg class="w-4 h-4 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </th>
                            <th class="px-4 py-3 text-left w-28">Kode</th>
                            <th class="px-4 py-3 text-left">Nama Indikator</th>
                            <th class="px-4 py-3 text-left">Sasaran Kegiatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($indikatorList as $item)
                        <tr class="hover:bg-blue-50 transition {{ $item['checked'] ? 'bg-blue-50/40' : '' }}">
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox"
                                    name="indikator_ids[]"
                                    value="{{ $item['id'] }}"
                                    class="cb-indikator w-4 h-4 text-blue-600 rounded border-gray-300"
                                    {{ $item['checked'] ? 'checked' : '' }}
                                    @change="updateCount()">
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded">
                                    {{ $item['kode'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-800">{{ $item['nama'] }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $item['sasaran'] }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                Tidak ada indikator aktif.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Tombol simpan --}}
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50 rounded-b-xl">
                <p class="text-xs text-gray-500">Perubahan akan langsung tersimpan ke database.</p>
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                    Simpan Pemetaan
                </button>
            </div>
        </form>
    </div>
    @endif

</div>
@endsection
