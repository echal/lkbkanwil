{{-- Ringkasan Bulanan --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">Ringkasan Bulanan</h2>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <p class="text-sm text-blue-600 font-semibold mb-1">Total Hari Kerja</p>
            <p class="text-3xl font-bold text-blue-900">{{ $totalHariKerja }}</p>
            <p class="text-xs text-blue-600 mt-1">hari efektif</p>
        </div>

        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <p class="text-sm text-green-600 font-semibold mb-1">Total Jam Kerja</p>
            <p class="text-3xl font-bold text-green-900">{{ $totalJamKerja }}</p>
            <p class="text-xs text-green-600 mt-1">jam {{ $sisaMenit }} menit</p>
        </div>

        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
            <p class="text-sm text-purple-600 font-semibold mb-1">Capaian Jam</p>
            <p class="text-3xl font-bold text-purple-900">{{ $persentaseJamKerja }}%</p>
            <p class="text-xs text-purple-600 mt-1">dari target {{ $targetJamKerjaBulanan }} jam</p>
        </div>

        <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
            <p class="text-sm text-orange-600 font-semibold mb-1">Status Capaian</p>
            <p class="text-2xl font-bold text-orange-900">{{ $statusCapaian }}</p>
            <p class="text-xs text-orange-600 mt-1">evaluasi bulan ini</p>
        </div>
    </div>

    {{-- Ringkasan Validasi Eviden — informasional, tidak mempengaruhi capaian resmi --}}
    <div class="mt-4 pt-4 border-t border-gray-100">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Ringkasan Validasi Eviden</p>
        <div class="flex flex-wrap gap-4">
            <div class="flex items-center gap-2">
                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <div>
                    <p class="text-xs text-gray-500">Jam Kerja Valid</p>
                    <p class="text-sm font-semibold text-gray-800">
                        {{ $jamValid }} jam {{ $sisaMenutValid }} menit
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="flex-shrink-0 w-5 h-5 rounded-full {{ $menitTidakSesuai > 0 ? 'bg-red-100' : 'bg-gray-100' }} flex items-center justify-center">
                    <svg class="w-3 h-3 {{ $menitTidakSesuai > 0 ? 'text-red-600' : 'text-gray-400' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </span>
                <div>
                    <p class="text-xs text-gray-500">Jam Tidak Sesuai</p>
                    <p class="text-sm font-semibold {{ $menitTidakSesuai > 0 ? 'text-red-700' : 'text-gray-400' }}">
                        {{ $jamTidakSesuai }} jam {{ $sisaMenitTidakSesuai }} menit
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </span>
                <div>
                    <p class="text-xs text-gray-500">Persentase Validasi</p>
                    <p class="text-sm font-semibold {{ $persentaseValidasi >= 100 ? 'text-green-700' : ($persentaseValidasi >= 80 ? 'text-yellow-700' : 'text-red-700') }}">
                        {{ $persentaseValidasi }}%
                    </p>
                </div>
            </div>

            @if($menitTidakSesuai === 0)
            <div class="flex items-center gap-1.5 ml-auto">
                <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs text-green-600 font-medium">Semua eviden valid</span>
            </div>
            @endif
        </div>
        @php
            $totalJamKerjaText = $totalJamKerja . ' jam' . ($sisaMenit > 0 ? ' ' . $sisaMenit . ' menit' : '');
            $jamValidText      = $jamValid . ' jam' . ($sisaMenutValid > 0 ? ' ' . $sisaMenutValid . ' menit' : '');
            $adaJamKerja       = ($totalJamKerja * 60 + $sisaMenit) > 0;
        @endphp
        <p class="text-xs text-gray-600 mt-3 font-medium">
            @if($adaJamKerja)
                {{ $jamValidText }} dari total {{ $totalJamKerjaText }} telah tervalidasi.
            @else
                Belum ada jam kerja pada periode ini.
            @endif
        </p>
        <p class="text-xs text-gray-400 mt-1 italic">* Informasi ini hanya untuk monitoring. Capaian resmi tidak berubah.</p>
    </div>
</div>
