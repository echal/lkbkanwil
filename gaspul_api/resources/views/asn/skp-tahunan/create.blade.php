@extends('layouts.app')

@section('title', 'Tambah Butir Kinerja')
@section('page-title', 'Tambah Butir Kinerja')

@section('content')
<div class="space-y-6">

    <!-- Breadcrumb -->
    <div class="flex items-center text-sm text-gray-600">
        <a href="{{ route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun]) }}" class="hover:text-blue-600">
            SKP Tahunan {{ $skpTahunan->tahun }}
        </a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Tambah Butir Kinerja</span>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-blue-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="text-lg font-semibold text-blue-800 mb-1">Tambah Butir Kinerja ke SKP Tahunan {{ $skpTahunan->tahun }}</h3>
                <p class="text-sm text-blue-700 mb-2">
                    Pilih RHK Pimpinan yang akan Anda intervensi, kemudian isi rencana aksi dan target tahunan Anda.
                </p>
                <p class="text-sm text-blue-700">
                    <strong>Catatan:</strong> Setelah butir kinerja ditambahkan, sistem akan otomatis membuat 12 Rencana Aksi Bulanan (Januari-Desember).
                </p>
            </div>
        </div>
    </div>

    <!-- Form Tambah Butir Kinerja -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Form Butir Kinerja</h3>
        </div>

        <form method="POST" action="{{ route('asn.skp-tahunan.store') }}" class="p-6 space-y-6">
            @csrf
            <input type="hidden" name="skp_tahunan_id" value="{{ $skpTahunan->id }}">

            <!-- Indikator Kinerja (RHK Pimpinan) Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Pilih Indikator Kinerja (RHK Pimpinan) <span class="text-red-500">*</span>
                </label>
                <select name="indikator_kinerja_id" id="indikator_kinerja_id" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('indikator_kinerja_id') border-red-500 @enderror">
                    <option value="">-- Pilih Indikator Kinerja --</option>
                    @foreach($indikatorList as $indikator)
                        <option value="{{ $indikator->id }}"
                                data-satuan="{{ $indikator->satuan }}"
                                {{ old('indikator_kinerja_id') == $indikator->id ? 'selected' : '' }}>
                            {{ $indikator->kode_indikator }} - {{ $indikator->nama_indikator }}
                            @if($indikator->unitKerja) ({{ $indikator->unitKerja->nama_unit }}) @endif
                        </option>
                    @endforeach
                </select>
                @error('indikator_kinerja_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-sm text-gray-500">
                    Pilih Indikator Kinerja yang sesuai dengan tugas dan tanggung jawab Anda.
                </p>
            </div>

            <!-- Rencana Aksi -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Rencana Aksi <span class="text-red-500">*</span>
                </label>
                <textarea name="rencana_aksi" rows="4" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('rencana_aksi') border-red-500 @enderror"
                    placeholder="Jelaskan rencana aksi yang akan Anda lakukan untuk mencapai RHK Pimpinan...">{{ old('rencana_aksi') }}</textarea>
                @error('rencana_aksi')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-sm text-gray-500">
                    Contoh: Melakukan monitoring dan evaluasi pelaksanaan program pendidikan setiap bulan
                </p>
            </div>

            <!-- Target Tahunan & Satuan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Target Tahunan <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="target_tahunan" min="1" required
                        value="{{ old('target_tahunan') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('target_tahunan') border-red-500 @enderror"
                        placeholder="Contoh: 12">
                    @error('target_tahunan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">Target yang ingin dicapai dalam 1 tahun</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Satuan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="satuan" id="satuan" required
                        value="{{ old('satuan') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('satuan') border-red-500 @enderror"
                        placeholder="Contoh: Laporan / Dokumen / Kegiatan">
                    @error('satuan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-sm text-gray-500">Satuan untuk mengukur target (auto-fill dari Indikator Kinerja)</p>
                </div>
            </div>

            <script>
                // Auto-fill satuan dari Indikator Kinerja yang dipilih
                document.getElementById('indikator_kinerja_id').addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const satuan = selectedOption.getAttribute('data-satuan');
                    if(satuan) {
                        document.getElementById('satuan').value = satuan;
                    }
                });
            </script>

            <!-- Info Auto-Generate -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-green-800 mb-1">Rencana Aksi Bulanan Otomatis</h4>
                        <p class="text-sm text-green-700">
                            Setelah menyimpan butir kinerja ini, sistem akan otomatis membuat 12 Rencana Aksi Bulanan
                            (Januari s.d Desember {{ $skpTahunan->tahun }}). Anda dapat mengedit rencana aksi bulanan
                            di menu <strong>Rencana Aksi Bulanan</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('asn.skp-tahunan.index', ['tahun' => $skpTahunan->tahun]) }}"
                   class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-semibold">
                    Simpan Butir Kinerja
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
