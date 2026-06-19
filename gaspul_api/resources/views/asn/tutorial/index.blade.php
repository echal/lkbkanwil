@extends('layouts.app')

@section('title', 'Tutorial ESARAKU')
@section('page-title', 'Tutorial ESARAKU')

@section('content')
<div class="space-y-6" x-data="tutorialPage()">

    {{-- Header --}}
    <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold">Tutorial ESARAKU</h1>
                </div>
                <p class="text-green-100 text-sm leading-relaxed max-w-2xl">
                    Panduan penggunaan sistem ESARAKU untuk membantu ASN memahami alur pengisian
                    SKP, Rencana Hasil Kerja, Kinerja Harian dan Laporan Bulanan.
                </p>
            </div>
            <div class="hidden md:flex items-center gap-2 bg-white bg-opacity-15 rounded-lg px-4 py-2 flex-shrink-0 ml-4">
                <svg class="w-4 h-4 text-green-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <span class="text-green-100 text-sm font-medium">{{ count($videos) }} Video Panduan</span>
            </div>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-3 flex items-start gap-3">
        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-blue-700 text-sm">
            Klik kartu atau tombol <strong>"Putar Tutorial"</strong> untuk menonton video panduan.
            Tonton secara berurutan untuk hasil terbaik — mulai dari SKP Tahunan hingga Laporan Bulanan.
        </p>
    </div>

    {{-- Grid Video Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($videos as $index => $video)
        <div
            class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 cursor-pointer group"
            @click="openModal('{{ $video['youtube_id'] }}', '{{ addslashes($video['judul']) }}')"
        >
            {{-- Thumbnail --}}
            <div class="relative overflow-hidden bg-gray-900 aspect-video">
                <img
                    src="https://img.youtube.com/vi/{{ $video['youtube_id'] }}/hqdefault.jpg"
                    alt="Thumbnail {{ $video['judul'] }}"
                    class="w-full h-full object-cover opacity-90 group-hover:opacity-75 transition-opacity duration-200"
                    loading="lazy"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                >
                {{-- Fallback thumbnail saat YouTube ID placeholder --}}
                <div class="absolute inset-0 bg-gradient-to-br from-green-700 to-green-900 hidden items-center justify-center">
                    <svg class="w-16 h-16 text-white opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </div>

                {{-- Play overlay --}}
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-14 h-14 bg-white bg-opacity-90 rounded-full flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-6 h-6 text-green-700 ml-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </div>
                </div>

                {{-- Durasi badge --}}
                <div class="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white text-xs font-medium px-2 py-0.5 rounded">
                    {{ $video['durasi'] }}
                </div>

                {{-- Nomor urut --}}
                <div class="absolute top-2 left-2 bg-green-600 text-white text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center shadow">
                    {{ $index + 1 }}
                </div>
            </div>

            {{-- Card Body --}}
            <div class="p-4">
                {{-- Kategori badge --}}
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-xs font-semibold px-2.5 py-1 rounded-full border border-green-200">
                        {{-- Icon per kategori --}}
                        @if($video['icon'] === 'clipboard-document')
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        @elseif($video['icon'] === 'calendar-days')
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        @elseif($video['icon'] === 'clock')
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        @elseif($video['icon'] === 'user-group')
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        @else
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        @endif
                        {{ $video['kategori'] }}
                    </span>
                </div>

                <h3 class="text-sm font-semibold text-gray-800 mb-1.5 leading-snug group-hover:text-green-700 transition-colors">
                    {{ $video['judul'] }}
                </h3>
                <p class="text-xs text-gray-500 leading-relaxed mb-3">
                    {{ $video['deskripsi'] }}
                </p>

                <button
                    type="button"
                    class="w-full flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors duration-150"
                    @click.stop="openModal('{{ $video['youtube_id'] }}', '{{ addslashes($video['judul']) }}')"
                >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    Putar Tutorial
                </button>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Modal Video --}}
    <div
        x-show="modalOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
        @click.self="closeModal()"
        @keydown.escape.window="closeModal()"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black bg-opacity-80"></div>

        {{-- Modal Panel --}}
        <div
            class="relative w-full max-w-4xl z-10"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            {{-- Modal Header --}}
            <div class="flex items-center justify-between mb-3 px-1">
                <h2 class="text-white font-semibold text-sm md:text-base truncate pr-4" x-text="activeTitle"></h2>
                <button
                    type="button"
                    @click="closeModal()"
                    class="flex-shrink-0 w-8 h-8 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full flex items-center justify-center transition-colors"
                    aria-label="Tutup video"
                >
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- iframe YouTube — hanya dirender saat modal aktif --}}
            <div class="relative w-full bg-black rounded-xl overflow-hidden shadow-2xl" style="aspect-ratio: 16/9;">
                <template x-if="modalOpen && activeYoutubeId">
                    <iframe
                        :src="'https://www.youtube.com/embed/' + activeYoutubeId + '?autoplay=1&rel=0&modestbranding=1'"
                        class="absolute inset-0 w-full h-full"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                    ></iframe>
                </template>
            </div>

            {{-- Hint tutup --}}
            <p class="text-center text-white text-opacity-60 text-xs mt-3 opacity-60">
                Tekan <kbd class="bg-white bg-opacity-20 px-1.5 py-0.5 rounded text-white text-xs font-mono">ESC</kbd>
                atau klik di luar video untuk menutup
            </p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function tutorialPage() {
    return {
        modalOpen: false,
        activeYoutubeId: null,
        activeTitle: '',

        openModal(youtubeId, title) {
            this.activeYoutubeId = youtubeId;
            this.activeTitle    = title;
            this.modalOpen      = true;
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.modalOpen      = false;
            this.activeYoutubeId = null;
            this.activeTitle    = '';
            document.body.style.overflow = '';
        },
    };
}
</script>
@endpush
