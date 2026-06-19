@extends('layouts.app')

@section('title', 'Survei Penggunaan ESARAKU')

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-4 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl px-5 py-4 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
            </svg>
            {{ session('info') }}
        </div>
    @endif

    @if(!$survei)
        {{-- Tidak ada survei aktif --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center">
            <svg class="w-14 h-14 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-500 font-medium">Survei belum tersedia.</p>
            <a href="{{ route('dashboard') }}" class="mt-4 inline-block text-sm text-blue-600 hover:underline">Kembali ke Dashboard</a>
        </div>

    @else
        {{-- Header --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-gray-800">{{ $survei->judul }}</h1>
                    <p class="text-xs text-gray-400">Periode: {{ $survei->periode }}</p>
                </div>
            </div>
            <p class="text-sm text-gray-500 leading-relaxed">
                Masukan Anda membantu pengembangan ESARAKU menjadi lebih baik. Semua jawaban bersifat anonim.
            </p>
        </div>

        {{-- Validation errors summary --}}
        @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 text-sm">
                <p class="font-semibold mb-1">Mohon lengkapi semua pertanyaan berikut:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form
            method="POST"
            action="{{ route('survei.store') }}"
            x-data="{ submitting: false }"
            @submit="submitting = true"
        >
            @csrf
            <input type="hidden" name="survei_id" value="{{ $survei->id }}">

            @php
                // Label skala dinamis per urutan pertanyaan (nilai 1 = opsi paling kiri/atas)
                $skalaPerQ = [
                    1  => [1 => 'Sangat Setuju',    2 => 'Setuju',            3 => 'Biasa Saja',       4 => 'Tidak Setuju',     5 => 'Sangat Tidak Setuju'],
                    2  => [1 => 'Sangat Mudah',      2 => 'Mudah',             3 => 'Cukup Mudah',      4 => 'Tidak Mudah',      5 => 'Sangat Tidak Mudah'],
                    3  => [1 => 'Sangat Memahami',   2 => 'Memahami',          3 => 'Cukup Memahami',   4 => 'Tidak Memahami',   5 => 'Sangat Tidak Memahami'],
                    4  => [1 => 'Selalu Sendiri',    2 => 'Lebih Sering Sendiri', 3 => 'Kadang Dibantu', 4 => 'Lebih Sering Dibantu', 5 => 'Selalu Dibantu Orang Lain'],
                    5  => [1 => 'Sangat Bermanfaat', 2 => 'Bermanfaat',        3 => 'Cukup Bermanfaat', 4 => 'Kurang Bermanfaat', 5 => 'Tidak Bermanfaat'],
                    6  => [1 => 'Sangat Memahami',   2 => 'Memahami',          3 => 'Cukup Memahami',   4 => 'Kurang Memahami',  5 => 'Tidak Memahami'],
                    7  => [1 => 'Sangat Mengetahui', 2 => 'Mengetahui',        3 => 'Cukup Mengetahui', 4 => 'Kurang Mengetahui', 5 => 'Tidak Mengetahui'],
                    8  => [1 => 'Sangat Sering',     2 => 'Sering',            3 => 'Kadang-Kadang',    4 => 'Jarang',           5 => 'Tidak Pernah'],
                    9  => [1 => 'Sangat Merata',     2 => 'Merata',            3 => 'Cukup Merata',     4 => 'Kurang Merata',    5 => 'Tidak Merata'],
                ];
            @endphp

            @foreach($survei->pertanyaan as $p)
                <div class="bg-white rounded-xl shadow-sm border {{ $errors->has('q'.$p->urutan) ? 'border-red-300' : 'border-gray-100' }} p-5 mb-4">
                    <p class="text-sm font-semibold text-gray-800 mb-4">
                        {{ $p->urutan }}.&nbsp; {{ $p->pertanyaan }}
                        @if($p->tipe === 'SKALA')
                            <span class="text-red-400 ml-0.5">*</span>
                        @endif
                    </p>

                    @if($p->tipe === 'SKALA')
                        @php
                            $labelQ = $skalaPerQ[$p->urutan] ?? [1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'];
                        @endphp
                        {{-- Skala 1–5: horizontal desktop, stack mobile --}}
                        <div class="flex flex-col sm:flex-row sm:items-start sm:gap-3 gap-2">
                            @for($i = 1; $i <= 5; $i++)
                                <label class="flex sm:flex-col items-center gap-3 sm:gap-1 cursor-pointer group flex-1 sm:text-center">
                                    <input
                                        type="radio"
                                        name="q{{ $p->urutan }}"
                                        value="{{ $i }}"
                                        class="sr-only peer"
                                        {{ old('q'.$p->urutan) == $i ? 'checked' : '' }}
                                    >
                                    <span class="w-10 h-10 flex-shrink-0 flex items-center justify-center rounded-full border-2 border-gray-300
                                        text-sm font-bold text-gray-500
                                        peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-checked:text-white
                                        group-hover:border-blue-400 transition-colors">
                                        {{ $i }}
                                    </span>
                                    <span class="text-xs text-gray-400 leading-tight">{{ $labelQ[$i] }}</span>
                                </label>
                            @endfor
                        </div>
                        @error('q'.$p->urutan)
                            <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
                        @enderror

                    @else
                        {{-- Pertanyaan teks / saran --}}
                        <textarea
                            name="saran"
                            rows="4"
                            maxlength="2000"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"
                            placeholder="Tuliskan saran atau masukan Anda..."
                        >{{ old('saran') }}</textarea>
                        @error('saran')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-1 text-right">Maksimal 2.000 karakter</p>
                    @endif
                </div>
            @endforeach

            {{-- Footer actions --}}
            <div class="flex items-center justify-between pt-2 pb-6">
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600 transition">
                    Lewati untuk sekarang
                </a>
                <button
                    type="submit"
                    :disabled="submitting"
                    :class="submitting ? 'opacity-60 cursor-not-allowed' : 'hover:bg-blue-700'"
                    class="bg-blue-600 text-white px-8 py-2.5 rounded-lg text-sm font-semibold transition flex items-center gap-2"
                >
                    <span x-show="submitting">
                        <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                    </span>
                    <span x-show="!submitting">Kirim Survei</span>
                    <span x-show="submitting">Mengirim...</span>
                </button>
            </div>
        </form>
    @endif

</div>
@endsection
