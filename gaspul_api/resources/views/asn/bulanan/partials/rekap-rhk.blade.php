{{-- Rekap RHK Bulanan --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">Rekap Rencana Hasil Kerja (RHK) Bulanan</h2>

    @if(empty($rekapRhkBulanan))
        <p class="text-gray-500 text-center py-8">Belum ada RHK yang dikerjakan bulan ini</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">RHK Pimpinan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rencana Aksi Bulanan</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Target</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Realisasi</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Capaian</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($rekapRhkBulanan as $index => $rhk)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $index + 1 }}</td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $rhk['indikator_kinerja'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">Kode: {{ $rhk['kode_indikator'] }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ Str::limit($rhk['rencana_aksi_bulanan'] ?? '-', 80) }}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-900">
                            <span class="font-semibold">{{ $rhk['target_bulanan'] }}</span>
                            <span class="text-gray-500">{{ $rhk['satuan'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-900">
                            <span class="font-semibold text-blue-600">{{ $rhk['realisasi_bulanan'] }}</span>
                            <span class="text-gray-500">{{ $rhk['satuan'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $rhk['persentase_capaian'] >= 100 ? 'bg-green-100 text-green-800' : 
                                   ($rhk['persentase_capaian'] >= 80 ? 'bg-blue-100 text-blue-800' : 
                                   ($rhk['persentase_capaian'] >= 50 ? 'bg-yellow-100 text-yellow-800' : 
                                   'bg-red-100 text-red-800')) }}">
                                {{ $rhk['persentase_capaian'] }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-600">
                            {{ $rhk['status'] }}
                            <br><span class="text-gray-400">({{ $rhk['jumlah_hari_dikerjakan'] }} hari)</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
