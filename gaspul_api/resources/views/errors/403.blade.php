<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - Sistem Kinerja ASN</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <!-- Icon -->
                <div class="mb-6">
                    <div class="mx-auto w-20 h-20 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-2xl font-bold text-gray-800 mb-3">Akses Ditolak</h1>

                <!-- Description -->
                <p class="text-gray-600 mb-6">
                    @if(isset($exception) && $exception->getMessage())
                        {{ $exception->getMessage() }}
                    @else
                        Anda tidak memiliki izin untuk mengakses halaman ini.
                    @endif
                </p>

                <!-- Button -->
                <a href="{{ url()->previous() }}"
                   class="inline-block w-full bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-green-600 hover:to-green-700 transition duration-200 shadow-md hover:shadow-lg mb-3">
                    Kembali
                </a>

                <a href="{{ route('asn.skp-tahunan.index') }}"
                   class="inline-block w-full bg-gray-100 text-gray-700 font-semibold py-3 px-6 rounded-lg hover:bg-gray-200 transition duration-200">
                    Ke Halaman SKP Tahunan
                </a>

                <!-- Additional Info -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-sm text-gray-500">
                        <strong>Catatan:</strong> Anda hanya dapat mengedit atau menghapus SKP Tahunan milik Anda sendiri yang berstatus DRAFT atau DITOLAK.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6">
                <p class="text-sm text-gray-500">
                    Sistem Informasi Kinerja ASN<br>
                    Kanwil Kementerian Agama Provinsi Sulawesi Barat
                </p>
            </div>
        </div>
    </div>
</body>
</html>
