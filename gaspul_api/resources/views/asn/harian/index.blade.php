@extends('layouts.app')

@section('title', 'Progres Harian')
@section('page-title', 'Progres Harian')

@section('content')
<div class="space-y-6" x-data="{
    selectedDate: '{{ $selectedDate }}',
    month: {{ $month }},
    year: {{ $year }},
    deleteEntry: null,
    selectDate(date) {
        this.selectedDate = date;
        window.location.href = '{{ route('asn.harian.index') }}?month=' + this.month + '&year=' + this.year + '&date=' + date;
    },
    prevMonth() {
        const newDate = new Date(this.year, this.month - 2, 1);
        this.month = newDate.getMonth() + 1;
        this.year = newDate.getFullYear();
        window.location.href = '{{ route('asn.harian.index') }}?month=' + this.month + '&year=' + this.year;
    },
    nextMonth() {
        const newDate = new Date(this.year, this.month, 1);
        this.month = newDate.getMonth() + 1;
        this.year = newDate.getFullYear();
        window.location.href = '{{ route('asn.harian.index') }}?month=' + this.month + '&year=' + this.year;
    }
}">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Progres Harian</h2>
            <p class="text-sm text-gray-600 mt-1">Input dan kelola progres kegiatan harian dengan kalender visual</p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            ← Kembali ke Dashboard
        </a>
    </div>

    <!-- Keterangan Warna -->
    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Keterangan Warna:</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-red-100 rounded flex items-center justify-center">
                    <span class="text-red-600 text-xs font-bold">X</span>
                </div>
                <span class="text-sm text-gray-700">Belum ada bukti dukung</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-yellow-100 rounded flex items-center justify-center">
                    <span class="text-yellow-600 text-xs font-bold">!</span>
                </div>
                <span class="text-sm text-gray-700">Ada progres, belum 7.5 jam</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-green-100 rounded flex items-center justify-center">
                    <span class="text-green-600 text-xs font-bold">✓</span>
                </div>
                <span class="text-sm text-gray-700">Sudah 7.5 jam & ada bukti</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kalender (Main View) - 2 kolom di large screen -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <!-- Calendar Header -->
                <div class="flex items-center justify-between mb-6">
                    <button @click="prevMonth()" class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <h3 class="text-xl font-bold text-gray-800">
                        @php
                            $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        @endphp
                        {{ $monthNames[$month] }} {{ $year }}
                    </h3>
                    <button @click="nextMonth()" class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-2">
                    <!-- Day Headers -->
                    @foreach(['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $day)
                    <div class="text-center text-sm font-semibold text-gray-600 py-2">{{ $day }}</div>
                    @endforeach

                    <!-- Calendar Days -->
                    @php
                        $startDate = \Carbon\Carbon::create($year, $month, 1);
                        $endDate = $startDate->copy()->endOfMonth();
                        $startDayOfWeek = $startDate->dayOfWeek;

                        // Adjust for Sunday (Carbon uses 0=Sunday, we want 0=Monday)
                        $startDayOfWeek = $startDayOfWeek == 0 ? 6 : $startDayOfWeek - 1;
                    @endphp

                    <!-- Empty cells before first day -->
                    @for($i = 0; $i < $startDayOfWeek; $i++)
                        <div></div>
                    @endfor

                    <!-- Days of month -->
                    @for($day = 1; $day <= $endDate->day; $day++)
                        @php
                            $currentDate = \Carbon\Carbon::create($year, $month, $day);
                            $dateStr = $currentDate->format('Y-m-d');
                            $dayData = $calendarData[$dateStr] ?? ['status' => 'empty'];
                            $status = $dayData['status'];
                            $isToday = $dateStr === now()->format('Y-m-d');
                            $isSelected = $dateStr === $selectedDate;

                            // Determine background color
                            if ($status === 'red') {
                                $bgClass = 'bg-red-50 border-red-300';
                                $textClass = 'text-red-800';
                            } elseif ($status === 'yellow') {
                                $bgClass = 'bg-yellow-50 border-yellow-300';
                                $textClass = 'text-yellow-800';
                            } elseif ($status === 'green') {
                                $bgClass = 'bg-green-50 border-green-300';
                                $textClass = 'text-green-800';
                            } else {
                                $bgClass = 'bg-white border-gray-200';
                                $textClass = 'text-gray-700';
                            }

                            if ($isSelected) {
                                $bgClass = 'bg-blue-100 border-blue-500 ring-2 ring-blue-500';
                                $textClass = 'text-blue-900';
                            }
                        @endphp
                        <button
                            @click="selectDate('{{ $dateStr }}')"
                            class="relative aspect-square border-2 rounded-lg transition hover:shadow-md {{ $bgClass }} {{ $isToday ? 'ring-2 ring-blue-400' : '' }}"
                        >
                            <div class="flex flex-col items-center justify-center h-full p-1">
                                <span class="text-lg font-bold {{ $textClass }}">{{ $day }}</span>
                                @if($dayData['status'] !== 'empty')
                                    <div class="text-xs mt-1 {{ $textClass }}">
                                        {{ floor($dayData['total_menit'] / 60) }}j {{ $dayData['total_menit'] % 60 }}m
                                    </div>
                                @endif
                            </div>
                            @if($dayData['status'] !== 'empty')
                                <div class="absolute bottom-1 right-1">
                                    @if($dayData['count_kh'] > 0)
                                        <span class="inline-block w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                    @endif
                                    @if($dayData['count_tla'] > 0)
                                        <span class="inline-block w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                    @endif
                                </div>
                            @endif
                        </button>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Panel Detail Kanan - 1 kolom -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                <!-- Progres Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Progres {{ \Carbon\Carbon::parse($selectedDate)->format('d-m-Y') }}
                    </h3>
                </div>

                <!-- Summary Stats -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-600">Total {{ $progressData['total_jam'] }}</span>
                        <span class="text-sm font-semibold
                            {{ $progressData['status'] === 'green' ? 'text-green-600' : ($progressData['status'] === 'yellow' ? 'text-yellow-600' : 'text-red-600') }}">
                            @if($progressData['status'] === 'green') 🟢
                            @elseif($progressData['status'] === 'yellow') 🟡
                            @elseif($progressData['status'] === 'red') 🔴
                            @else ⚪
                            @endif
                            @if($progressData['status'] === 'empty') No Bukti @else {{ strtoupper($progressData['status']) }} @endif
                        </span>
                    </div>
                    <div class="text-sm text-gray-600">Sisa {{ $progressData['sisa_jam'] }}</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
                        <div class="h-2 rounded-full transition-all duration-300
                            {{ $progressData['status'] === 'green' ? 'bg-green-500' : ($progressData['status'] === 'yellow' ? 'bg-yellow-500' : 'bg-red-500') }}"
                            style="width: {{ min(($progressData['total_menit'] / 450) * 100, 100) }}%">
                        </div>
                    </div>
                </div>

                <!-- Tambah Progres Button -->
                <a href="{{ route('asn.harian.pilih') }}?date={{ $selectedDate }}"
                   class="block w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition text-center font-semibold mb-4">
                    + Tambah Progres Harian
                </a>

                <!-- List Kegiatan -->
                <div class="space-y-3">
                    <h4 class="text-sm font-semibold text-gray-700">Daftar Kegiatan</h4>
                    @if(count($progressData['entries']) > 0)
                        @foreach($progressData['entries'] as $entry)
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <div class="flex items-start justify-between mb-2">
                                <span class="text-xs font-semibold px-2 py-1 rounded
                                    {{ $entry['tipe_progres'] === 'KINERJA_HARIAN' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $entry['tipe_progres'] === 'KINERJA_HARIAN' ? 'KH' : 'TLA' }}
                                </span>
                                <div class="flex items-center space-x-1">
                                    <a href="{{ route('asn.harian.edit', $entry['id']) }}?date={{ $selectedDate }}"
                                       class="p-1 hover:bg-gray-200 rounded transition text-blue-600" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button @click="deleteEntry = { id: {{ $entry['id'] }}, date: '{{ $selectedDate }}' }"
                                        class="p-1 hover:bg-gray-200 rounded transition text-red-600" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="text-sm text-gray-700 mb-1">{{ $entry['jam_mulai'] }} - {{ $entry['jam_selesai'] }}</div>
                            <div class="text-sm font-medium text-gray-800 mb-1">{{ $entry['kegiatan'] }}</div>
                            @if($entry['tipe_progres'] === 'KINERJA_HARIAN')
                                <div class="text-xs text-gray-600">Progres: {{ $entry['progres'] }} {{ $entry['satuan'] }}</div>
                            @endif
                            @if($entry['link_bukti'])
                                <div class="text-xs text-green-600 mt-2 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Ada bukti
                                </div>
                            @else
                                <div class="text-xs text-red-600 mt-2 flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    No Bukti
                                </div>
                            @endif
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-sm text-gray-500">Belum ada progres</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="deleteEntry !== null"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="deleteEntry = null">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
            <div class="flex items-start mb-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Konfirmasi Hapus</h3>
                    <p class="text-sm text-gray-600">
                        Apakah Anda yakin ingin menghapus progres harian ini?
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-end space-x-3 mt-6">
                <button @click="deleteEntry = null"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Batal
                </button>
                <form x-bind:action="deleteEntry ? '{{ url('/asn/harian/destroy') }}/' + deleteEntry.id + '?date=' + deleteEntry.date : ''"
                      method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
     class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
    {{ session('error') }}
</div>
@endif
@endsection
