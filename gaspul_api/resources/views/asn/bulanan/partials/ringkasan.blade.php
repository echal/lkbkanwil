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
</div>
