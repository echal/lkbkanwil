{{-- Rekap Kerja Harian (Detail) --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 -m-6 mb-6 p-4 rounded-t-xl">
        <h2 class="text-xl font-bold text-white">Rekap Kerja Harian (Detail)</h2>
        <p class="text-sm text-blue-100 mt-1">
            Detail kegiatan harian {{ $asn->name }} -
            @php
                $namaBulanArray = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                echo $namaBulan ?? $namaBulanArray[$bulan] ?? 'Bulan ' . $bulan;
            @endphp
            {{ $tahun }}
        </p>
    </div>

    @if(isset($rekapKerjaHarianDetail) && count($rekapKerjaHarianDetail) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pegawai</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam Kerja</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uraian Kegiatan</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Volume</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($rekapKerjaHarianDetail as $index => $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $index + 1 }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            @php
                                $tanggal = \Carbon\Carbon::parse($item['tanggal']);
                                echo $tanggal->translatedFormat('d M Y');
                            @endphp
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-gray-900">{{ $item['nama_pegawai'] }}</p>
                            <p class="text-xs text-gray-500">NIP: {{ $item['nip'] }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <div>{{ $item['jam_kerja'] }}</div>
                            <div class="text-xs text-gray-500">({{ $item['durasi_formatted'] }})</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <div class="max-w-md">
                                @php
                                    $uraian = $item['uraian_kegiatan'];
                                    echo strlen($uraian) > 100 ? substr($uraian, 0, 100) . '...' : $uraian;
                                @endphp
                            </div>
                            @if($item['status_bukti'] === 'SUDAH_ADA')
                                <span class="inline-flex items-center text-xs text-blue-600 mt-1">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Ada Bukti
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-gray-900">
                            <span class="font-semibold">{{ $item['volume'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($item['jenis_kegiatan'] === 'LKH')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    LKH
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                    TLA
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($item['jenis_kegiatan'] === 'LKH')
                                <a href="{{ route('asn.harian.cetak', ['id' => $item['id']]) }}"
                                   target="_blank"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Cetak KH
                                </a>
                            @else
                                <a href="{{ route('asn.harian.cetak-tla', ['id' => $item['id']]) }}"
                                   target="_blank"
                                   class="inline-flex items-center px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded transition">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Cetak TLA
                                </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Summary Footer --}}
        <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Total Kegiatan:</span>
                    <span class="font-semibold text-gray-900 ml-2">{{ count($rekapKerjaHarianDetail) }} kegiatan</span>
                </div>
                <div>
                    <span class="text-gray-600">LKH:</span>
                    <span class="font-semibold text-green-600 ml-2">
                        {{ collect($rekapKerjaHarianDetail)->where('jenis_kegiatan', 'LKH')->count() }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-600">TLA:</span>
                    <span class="font-semibold text-purple-600 ml-2">
                        {{ collect($rekapKerjaHarianDetail)->where('jenis_kegiatan', 'TLA')->count() }}
                    </span>
                </div>
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Belum Ada Data Detail</h3>
            <p class="text-gray-600">Belum ada kinerja harian yang tercatat untuk periode ini.</p>
        </div>
    @endif
</div>

{{-- PDF-Friendly Version (Hidden by default, shown when printing) --}}
<div class="pdf-only hidden print:block" style="page-break-before: always;">
    <h3 style="font-size: 14px; font-weight: bold; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 5px;">
        REKAP KERJA HARIAN (DETAIL)
    </h3>

    @if(isset($rekapKerjaHarianDetail) && count($rekapKerjaHarianDetail) > 0)
        <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
            <thead>
                <tr style="background-color: #f3f4f6;">
                    <th style="border: 1px solid #000; padding: 6px; text-align: left;">No</th>
                    <th style="border: 1px solid #000; padding: 6px; text-align: left;">Tanggal</th>
                    <th style="border: 1px solid #000; padding: 6px; text-align: left;">Jam</th>
                    <th style="border: 1px solid #000; padding: 6px; text-align: left;">Uraian</th>
                    <th style="border: 1px solid #000; padding: 6px; text-align: center;">Volume</th>
                    <th style="border: 1px solid #000; padding: 6px; text-align: center;">Jenis</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rekapKerjaHarianDetail as $index => $item)
                <tr>
                    <td style="border: 1px solid #000; padding: 6px;">{{ $index + 1 }}</td>
                    <td style="border: 1px solid #000; padding: 6px;">
                        @php
                            $tanggal = \Carbon\Carbon::parse($item['tanggal']);
                            echo $tanggal->translatedFormat('d/m/Y');
                        @endphp
                    </td>
                    <td style="border: 1px solid #000; padding: 6px;">{{ $item['jam_kerja'] }}</td>
                    <td style="border: 1px solid #000; padding: 6px;">{{ $item['uraian_kegiatan'] }}</td>
                    <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ $item['volume'] }}</td>
                    <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ $item['jenis_kegiatan'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
