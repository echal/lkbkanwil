{{-- Header Laporan: Identitas ASN --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 print:border-0">
    <div class="border-b border-gray-300 pb-4 mb-4">
        <h1 class="text-2xl font-bold text-center text-gray-900">LAPORAN KINERJA BULANAN</h1>
        <p class="text-center text-gray-600 mt-1">Periode: {{ $namaBulan }} {{ $tahun }}</p>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <div class="space-y-2">
            <div class="flex">
                <span class="font-semibold w-32 text-gray-700">Nama</span>
                <span class="text-gray-900">: {{ $asn->name }}</span>
            </div>
            <div class="flex">
                <span class="font-semibold w-32 text-gray-700">NIP</span>
                <span class="text-gray-900">: {{ $asn->nip ?? '-' }}</span>
            </div>
            <div class="flex">
                <span class="font-semibold w-32 text-gray-700">Jabatan</span>
                <span class="text-gray-900">: {{ $asn->jabatan ?? '-' }}</span>
            </div>
        </div>
        <div class="space-y-2">
            <div class="flex">
                <span class="font-semibold w-32 text-gray-700">Unit Kerja</span>
                <span class="text-gray-900">: {{ $asn->unitKerja->nama_unit ?? $asn->unit_kerja ?? '-' }}</span>
            </div>
            <div class="flex">
                <span class="font-semibold w-32 text-gray-700">Status</span>
                <span>: 
                    <span class="px-2 py-1 rounded text-xs font-semibold 
                        {{ $statusLaporan === 'DISETUJUI' ? 'bg-green-100 text-green-800' : 
                           ($statusLaporan === 'DIKIRIM' ? 'bg-blue-100 text-blue-800' : 
                           'bg-gray-100 text-gray-800') }}">
                        {{ $statusLaporan }}
                    </span>
                </span>
            </div>
        </div>
    </div>
</div>
