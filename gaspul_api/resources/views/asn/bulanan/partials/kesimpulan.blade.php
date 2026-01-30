{{-- Kesimpulan & Status --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4 border-b pb-2">Kesimpulan</h2>

    <div class="space-y-3">
        @foreach($kesimpulanOtomatis as $kesimpulan)
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="text-gray-700">{{ $kesimpulan }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
        <p class="text-sm text-blue-800">
            <strong>Catatan:</strong> Kesimpulan ini dibuat otomatis berdasarkan data kinerja harian yang telah diinput.
            Pastikan semua kegiatan telah tercatat dengan benar sebelum mengirim laporan ke atasan.
        </p>
    </div>
</div>
