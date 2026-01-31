<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Berakhir - Sistem Kinerja ASN</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full">
            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <!-- Icon -->
                <div class="mb-6">
                    <div class="mx-auto w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-2xl font-bold text-gray-800 mb-3">Sesi Anda Telah Berakhir</h1>

                <!-- Description -->
                <p class="text-gray-600 mb-2">
                    Sesi login Anda telah habis karena tidak ada aktivitas dalam waktu yang lama.
                </p>
                <p class="text-gray-600 mb-6">
                    Silakan login kembali untuk melanjutkan.
                </p>

                <!-- Button -->
                <a href="{{ route('login') }}"
                   class="inline-block w-full bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-green-600 hover:to-green-700 transition duration-200 shadow-md hover:shadow-lg">
                    Login Kembali
                </a>

                <!-- Additional Info -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-sm text-gray-500">
                        <strong>Tips:</strong> Untuk menghindari sesi berakhir, pastikan Anda tetap aktif saat menggunakan aplikasi.
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

    <!-- Auto redirect after 3 seconds -->
    <script>
        // Auto redirect ke login setelah 3 detik
        setTimeout(function() {
            window.location.href = '{{ route("login") }}';
        }, 3000);
    </script>
</body>
</html>
