{{-- Rekap Kinerja Harian --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">Rekap Kinerja Harian</h2>

    @if(empty($rekapKinerjaHarian))
        <p class="text-gray-500 text-center py-8">Belum ada kinerja harian yang tercatat</p>
    @else
        <div class="space-y-4">
            @foreach($rekapKinerjaHarian as $hari)
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $hari['tanggal_formatted'] }}</h3>
                                <p class="text-sm text-gray-600">
                                    {{ $hari['jumlah_kegiatan'] }} kegiatan •
                                    Durasi: {{ $hari['total_durasi_formatted'] }}
                                </p>
                            </div>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                {{ $hari['hari_nama'] }}
                            </span>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-200">
                        @foreach($hari['kegiatan_list'] as $kegiatan)
                            <div class="px-4 py-3 hover:bg-gray-50">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center text-sm text-gray-500 mb-1">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ $kegiatan['jam_mulai'] }} - {{ $kegiatan['jam_selesai'] }}
                                            <span class="ml-2 px-2 py-0.5 bg-gray-100 rounded text-xs">
                                                {{ $kegiatan['durasi_menit'] }} menit
                                            </span>
                                        </div>

                                        <p class="text-xs text-gray-500 mb-1">
                                            <strong>Indikator Kinerja:</strong> {{ Str::limit($kegiatan['indikator_kinerja'], 60) }}
                                        </p>

                                        <p class="text-sm font-medium text-gray-900">{{ $kegiatan['kegiatan'] }}</p>

                                        <div class="flex items-center space-x-3 mt-2 text-sm">
                                            <span class="inline-flex items-center text-green-600">
                                                Progres: <strong class="ml-1">{{ $kegiatan['progres'] }} {{ $kegiatan['satuan'] }}</strong>
                                            </span>
                                            @if($kegiatan['status_bukti'] === 'SUDAH_ADA')
                                                <span class="text-blue-600 text-xs">✓ Ada Bukti</span>
                                            @endif
                                        </div>
                                    </div>

                                    <span class="ml-4 px-2 py-1 text-xs font-semibold rounded
                                        {{ $kegiatan['tipe'] === 'KINERJA_HARIAN' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $kegiatan['tipe'] === 'KINERJA_HARIAN' ? 'Kinerja' : 'Tugas Atasan' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
