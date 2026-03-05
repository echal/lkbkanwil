@extends('layouts.app')

@section('title', 'Preview Import ASN')

@section('content')
<div class="p-6">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Preview Import ASN</h1>
            <p class="text-gray-500 text-sm mt-1">Periksa data sebelum konfirmasi import.</p>
        </div>
        <a href="{{ route('admin.import-asn.index') }}"
           class="text-sm text-gray-500 hover:text-gray-700 underline">
            &larr; Upload Ulang
        </a>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm">
            <p class="text-3xl font-bold text-gray-800">{{ count($mapped) }}</p>
            <p class="text-xs text-gray-500 mt-1">Total Baris</p>
        </div>
        <div class="bg-white border border-green-200 rounded-xl p-4 text-center shadow-sm">
            <p class="text-3xl font-bold text-green-600">{{ count($validRows) }}</p>
            <p class="text-xs text-gray-500 mt-1">Valid</p>
        </div>
        <div class="bg-white border border-red-200 rounded-xl p-4 text-center shadow-sm">
            <p class="text-3xl font-bold text-red-600">{{ count($invalidRows) }}</p>
            <p class="text-xs text-gray-500 mt-1">Error</p>
        </div>
    </div>

    {{-- Banner error jika ada baris invalid --}}
    @if($hasError)
    <div class="mb-4 p-4 bg-red-50 border border-red-300 rounded-lg">
        <p class="text-red-700 font-semibold text-sm">Terdapat {{ count($invalidRows) }} baris dengan error. Perbaiki file dan upload ulang sebelum dapat melakukan import.</p>
    </div>
    @else
    <div class="mb-4 p-4 bg-green-50 border border-green-300 rounded-lg">
        <p class="text-green-700 font-semibold text-sm">Semua {{ count($validRows) }} baris valid. Klik "Konfirmasi Import" untuk melanjutkan.</p>
    </div>
    @endif

    {{-- Tabel Preview --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="text-xs w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600">Baris</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600">Nama</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600">Email</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600">NIP</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600">Role</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600">Unit ID</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600">Jabatan</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600">Atasan ID</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600">Status Pegawai</th>
                        <th class="px-3 py-3 text-left font-semibold text-gray-600">Keterangan Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($mapped as $row)
                    @php $rowErrors = $row['errors'] ?? []; @endphp
                    <tr class="{{ !empty($rowErrors) ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                        <td class="px-3 py-2 text-gray-500">{{ $row['_row'] }}</td>
                        <td class="px-3 py-2">
                            @if(empty($rowErrors))
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Valid</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Error</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 font-medium text-gray-800">{{ $row['name'] }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $row['email'] }}</td>
                        <td class="px-3 py-2 text-gray-600 font-mono">{{ $row['nip'] }}</td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $row['role'] === 'ASN' ? 'bg-blue-100 text-blue-700' : ($row['role'] === 'ATASAN' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ $row['role'] ?: '-' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-gray-600">{{ $row['unit_kerja_id'] ?? '-' }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $row['jabatan'] ?? '-' }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $row['atasan_id'] ?? '-' }}</td>
                        <td class="px-3 py-2">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $row['status_pegawai'] === 'AKTIF' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $row['status_pegawai'] ?: '-' }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            @if(!empty($rowErrors))
                                <ul class="list-disc list-inside text-red-600 space-y-0.5">
                                    @foreach($rowErrors as $err)
                                    <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tombol Konfirmasi --}}
    <div class="flex items-center gap-4">
        @if(!$hasError)
        <form action="{{ route('admin.import-asn.confirm') }}" method="POST">
            @csrf
            <button type="submit"
                onclick="return confirm('Anda akan mengimport {{ count($validRows) }} data ASN. Proses ini tidak dapat dibatalkan. Lanjutkan?')"
                class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-6 rounded-lg transition">
                Konfirmasi Import ({{ count($validRows) }} Data)
            </button>
        </form>
        @else
        <button disabled
            class="bg-gray-300 text-gray-500 font-semibold py-2.5 px-6 rounded-lg cursor-not-allowed">
            Konfirmasi Import (Ada Error)
        </button>
        @endif

        <a href="{{ route('admin.import-asn.index') }}"
           class="text-sm text-gray-500 hover:text-gray-700 underline">
            Upload Ulang
        </a>
    </div>

</div>
@endsection
