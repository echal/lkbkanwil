@php
    $user = auth()->user();
    $surveiAktif = null;
    $sudahIsi    = false;

    if ($user) {
        $surveiAktif = \App\Models\Survei::aktif()->latest()->first();
        if ($surveiAktif) {
            $sudahIsi = \App\Models\SurveiJawaban::where('survei_id', $surveiAktif->id)
                ->where('user_id', $user->id)
                ->exists();
        }
    }
@endphp

@if($surveiAktif && !$sudahIsi)
<div
    x-data="{
        show: false,
        key: 'esaraku_survei_dismissed_{{ $surveiAktif->id }}',
        init() {
            if (!sessionStorage.getItem(this.key)) {
                // Tunda 1.5 detik agar halaman sudah render
                setTimeout(() => { this.show = true; }, 1500);
            }
        },
        dismiss() {
            sessionStorage.setItem(this.key, '1');
            this.show = false;
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed bottom-6 right-6 z-50 w-80 bg-white rounded-xl shadow-2xl border border-blue-100 p-5"
    style="display: none;"
>
    <div class="flex items-start justify-between mb-3">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <span class="text-sm font-semibold text-gray-800">Survei ESARAKU</span>
        </div>
        <button @click="dismiss()" class="text-gray-400 hover:text-gray-600 transition ml-2 flex-shrink-0" aria-label="Tutup">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <p class="text-xs text-gray-500 mb-4 leading-relaxed">
        Bantu kami meningkatkan ESARAKU dengan mengisi survei singkat. Hanya butuh 2–3 menit!
    </p>

    <div class="flex gap-2">
        <a href="{{ route('survei.show') }}"
            class="flex-1 bg-blue-600 text-white text-xs font-semibold text-center py-2 px-3 rounded-lg hover:bg-blue-700 transition">
            Isi Sekarang
        </a>
        <button @click="dismiss()"
            class="flex-1 bg-gray-100 text-gray-600 text-xs font-semibold py-2 px-3 rounded-lg hover:bg-gray-200 transition">
            Nanti Saja
        </button>
    </div>
</div>
@endif
