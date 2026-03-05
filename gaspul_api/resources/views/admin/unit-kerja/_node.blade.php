@php
    $hasChildren = $unit->children->isNotEmpty();
    $paddingLeft = $depth * 24; // 24px per level
    $isRoot      = $depth === 0;
    $bgRow       = $isRoot ? 'bg-white' : 'bg-gray-50';
    $borderLeft  = $depth > 0 ? 'border-l-2 border-indigo-200' : '';
@endphp

<div class="rounded-xl shadow-sm border border-gray-200 overflow-hidden {{ $isRoot ? '' : 'mt-1' }}"
     x-data="{ open: {{ $hasChildren ? 'true' : 'false' }} }">

    {{-- Row unit --}}
    <div class="flex items-center gap-3 px-4 py-3 {{ $bgRow }} hover:bg-gray-50 transition"
         style="padding-left: {{ 16 + $paddingLeft }}px">

        {{-- Toggle expand/collapse --}}
        @if($hasChildren)
            <button @click="open = !open"
                    class="flex-shrink-0 w-5 h-5 text-gray-400 hover:text-gray-600 transition"
                    :title="open ? 'Tutup' : 'Buka'">
                <svg class="w-5 h-5 transition-transform" :class="open ? '' : '-rotate-90'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        @else
            <span class="flex-shrink-0 w-5 h-5 text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </span>
        @endif

        {{-- Icon unit --}}
        <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center
                    {{ $isRoot ? 'bg-indigo-100' : 'bg-blue-50' }}">
            @if($isRoot)
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            @else
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            @endif
        </div>

        {{-- Info unit --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-semibold text-gray-800 text-sm truncate">{{ $unit->nama_unit }}</span>
                <span class="text-xs text-gray-400">{{ $unit->kode_unit }}</span>
                @if($unit->eselon)
                    <span class="text-xs px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded">Eselon {{ $unit->eselon }}</span>
                @endif
                @if($unit->status === 'NONAKTIF')
                    <span class="text-xs px-1.5 py-0.5 bg-red-100 text-red-600 rounded">Nonaktif</span>
                @endif
            </div>
        </div>

        {{-- Badge pegawai --}}
        <div class="flex-shrink-0 flex items-center gap-1 text-xs text-gray-500 bg-gray-100 rounded-full px-2.5 py-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>{{ $unit->users_count ?? 0 }}</span>
        </div>

        {{-- Aksi --}}
        <div class="flex-shrink-0 flex items-center gap-1">
            {{-- Tambah Sub-unit --}}
            <a href="{{ route('admin.unit-kerja.create', ['parent_id' => $unit->id]) }}"
               title="Tambah Sub-unit"
               class="inline-flex items-center px-2 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 text-xs font-medium rounded-lg transition">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Sub-unit
            </a>

            {{-- Edit --}}
            <a href="{{ route('admin.unit-kerja.edit', $unit->id) }}"
               class="inline-flex items-center px-2 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium rounded-lg transition">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>

            {{-- Hapus --}}
            @if($unit->children->isEmpty() && ($unit->users_count ?? 0) === 0)
                <form action="{{ route('admin.unit-kerja.destroy', $unit->id) }}" method="POST"
                      onsubmit="return confirm('Yakin ingin menghapus unit \'{{ addslashes($unit->nama_unit) }}\'?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center px-2 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-medium rounded-lg transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus
                    </button>
                </form>
            @else
                <span class="inline-flex items-center px-2 py-1.5 bg-gray-50 text-gray-300 text-xs font-medium rounded-lg cursor-not-allowed"
                      title="{{ $unit->children->isNotEmpty() ? 'Masih memiliki sub-unit' : 'Masih memiliki pegawai' }}">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Hapus
                </span>
            @endif
        </div>
    </div>

    {{-- Children (recursive) --}}
    @if($hasChildren)
        <div x-show="open" x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             class="border-t border-gray-100 pl-6">
            @foreach($unit->children as $child)
                @include('admin.unit-kerja._node', ['unit' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif

</div>
