{{-- Calendar Grid dengan Integrasi Penuh --}}
<div class="grid grid-cols-7 gap-1">
    {{-- Header: Senin - Minggu --}}
    @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dayName)
    <div class="text-center text-xs font-semibold text-gray-700 py-2 {{ in_array($dayName, ['Sab', 'Min']) ? 'bg-gray-50' : 'bg-white' }}">
        {{ $dayName }}
    </div>
    @endforeach

    {{-- Calendar Days --}}
    @foreach($calendarData as $dateStr => $dayData)
    <div class="aspect-square {{ !$dayData['is_current_month'] ? 'opacity-40' : '' }}">
        <button type="button"
                @click="selectDate('{{ $dateStr }}')"
                class="w-full h-full p-1 rounded-lg border-2 transition-all duration-200
                    {{ $dayData['is_today'] ? 'ring-2 ring-blue-400 ring-offset-1' : '' }}
                    {{ $dayData['can_input'] ? 'hover:shadow-md cursor-pointer' : 'cursor-not-allowed opacity-75' }}
                    {{ $selectedDate === $dateStr ? 'ring-2 ring-offset-1 ' . $dayData['badge']['border'] : $dayData['badge']['border'] }}
                    {{ $dayData['badge']['bg'] }}">

            {{-- Tanggal --}}
            <div class="text-center mb-1">
                <span class="text-sm font-bold {{ $dayData['badge']['text'] }}">
                    {{ $dayData['day'] }}
                </span>
            </div>

            {{-- Badge & Info --}}
            <div class="text-center space-y-0.5">
                {{-- Badge LKH --}}
                @if($dayData['has_lkh'])
                    <div class="flex items-center justify-center gap-1">
                        <span class="inline-flex items-center px-1 py-0.5 rounded text-[10px] font-semibold bg-blue-600 text-white">
                            LKH
                        </span>
                        @if($dayData['total_hours'] > 0)
                            <span class="text-[9px] font-medium {{ $dayData['badge']['text'] }}">
                                {{ $dayData['total_hours'] }}j
                            </span>
                        @endif
                    </div>
                {{-- Badge RHK (jika ada RHK tapi belum ada LKH) --}}
                @elseif($dayData['has_rhk'] && $dayData['is_working_day'])
                    <span class="inline-block px-1 py-0.5 rounded text-[10px] font-semibold bg-purple-600 text-white">
                        RHK
                    </span>
                {{-- Badge Weekend --}}
                @elseif($dayData['is_weekend'])
                    <span class="text-[9px] font-medium text-gray-500">Weekend</span>
                {{-- Badge Libur --}}
                @elseif($dayData['is_holiday'])
                    <div class="text-[8px] font-medium text-red-700 leading-tight">
                        {{ \Illuminate\Support\Str::limit($dayData['holiday_name'], 12) }}
                    </div>
                @endif

                {{-- Status Icon (untuk hari dengan LKH) --}}
                @if($dayData['has_lkh'])
                    <div class="flex items-center justify-center">
                        @if($dayData['status'] === 'green')
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @elseif($dayData['status'] === 'yellow')
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        @elseif($dayData['status'] === 'red')
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </div>
                @endif

                {{-- Indikator tidak bisa input --}}
                @if(!$dayData['can_input'] && $dayData['is_current_month'] && !$dayData['is_holiday'] && !$dayData['is_weekend'])
                    <svg class="w-3 h-3 mx-auto text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                @endif
            </div>
        </button>
    </div>
    @endforeach
</div>

{{-- Legend / Keterangan --}}
<div class="mt-6 p-4 bg-gray-50 rounded-lg">
    <h4 class="text-xs font-semibold text-gray-700 mb-3">Keterangan:</h4>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-xs">
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded bg-blue-600"></div>
            <span class="text-gray-700">LKH Terisi</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded bg-purple-600"></div>
            <span class="text-gray-700">RHK Ada (Belum LKH)</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded bg-green-50 border-2 border-green-200"></div>
            <span class="text-gray-700">Hari Kerja Kosong</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded bg-gray-100 border-2 border-gray-300"></div>
            <span class="text-gray-700">Weekend</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 rounded bg-red-100 border-2 border-red-300"></div>
            <span class="text-gray-700">Hari Libur</span>
        </div>
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-gray-700">Hari Ini</span>
        </div>
    </div>

    <div class="mt-3 pt-3 border-t border-gray-200 grid grid-cols-1 md:grid-cols-3 gap-2 text-xs">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-gray-700">≥7.5 jam + Bukti</span>
        </div>
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span class="text-gray-700"><7.5 jam + Bukti</span>
        </div>
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span class="text-gray-700">Belum Ada Bukti</span>
        </div>
    </div>
</div>
