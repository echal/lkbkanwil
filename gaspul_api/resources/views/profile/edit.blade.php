@extends('layouts.app')

@section('title', 'Edit Profil - GASPUL')
@section('page-title', 'Edit Profil')

@section('content')
<div class="space-y-6">

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Profile Header --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-sm border border-blue-200 p-6">
        <div class="flex items-center space-x-4">
            <div class="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-3xl">
                {{ substr($user->name, 0, 1) }}
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
                <p class="text-sm text-gray-600">{{ $user->email }}</p>
                <div class="mt-2 flex items-center space-x-4">
                    @if($user->role === 'ASN')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                            ASN
                        </span>
                    @elseif($user->role === 'ATASAN')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                            Atasan/Pimpinan
                        </span>
                    @elseif($user->role === 'ADMIN')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            Administrator
                        </span>
                    @endif

                    @if($user->status === 'AKTIF')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <circle cx="10" cy="10" r="3"/>
                            </svg>
                            Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                            Nonaktif
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column: Data ASN (Read-only) --}}
        <div class="lg:col-span-1 space-y-6">

            {{-- Data Pegawai --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-4">
                    <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900">Data Pegawai</h3>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">NIP</label>
                        <p class="text-sm text-gray-900 font-medium">{{ $user->nip ?? '-' }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Jabatan</label>
                        <p class="text-sm text-gray-900 font-medium">{{ $user->jabatan ?? '-' }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Unit Kerja</label>
                        <p class="text-sm text-gray-900 font-medium">
                            {{ $user->unitKerja->nama_unit ?? $user->unit_kerja ?? '-' }}
                        </p>
                    </div>

                    <div class="pt-3 border-t border-gray-200">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <div class="flex items-start">
                                <svg class="w-4 h-4 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-xs text-blue-700">
                                    Data pegawai (NIP, Jabatan, Unit Kerja) dikelola oleh Administrator dan tidak dapat diubah sendiri.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Account Info --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center mb-4">
                    <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900">Info Akun</h3>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Akun Dibuat</label>
                        <p class="text-sm text-gray-900">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Terakhir Diupdate</label>
                        <p class="text-sm text-gray-900">{{ $user->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- Right Column: Editable Fields --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Update Email --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" x-data="{ showEmailForm: false }">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <h3 class="text-lg font-bold text-gray-900">Email</h3>
                    </div>
                    <button @click="showEmailForm = !showEmailForm" type="button" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                        <span x-show="!showEmailForm">Ubah Email</span>
                        <span x-show="showEmailForm">Batal</span>
                    </button>
                </div>

                <div x-show="!showEmailForm">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Email Saat Ini</label>
                        <p class="text-base text-gray-900 font-medium">{{ $user->email }}</p>
                    </div>
                </div>

                <div x-show="showEmailForm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="mt-4">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email Baru</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="email@example.com">
                            @error('email')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end space-x-3">
                            <button type="button" @click="showEmailForm = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-semibold">
                                Simpan Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Update Password --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" x-data="{ showPasswordForm: false }">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <h3 class="text-lg font-bold text-gray-900">Keamanan</h3>
                    </div>
                    <button @click="showPasswordForm = !showPasswordForm" type="button" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                        <span x-show="!showPasswordForm">Ubah Password</span>
                        <span x-show="showPasswordForm">Batal</span>
                    </button>
                </div>

                <div x-show="!showPasswordForm">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Password</label>
                        <p class="text-base text-gray-900 font-medium">••••••••</p>
                        <p class="text-xs text-gray-500 mt-1">Terakhir diubah: {{ $user->updated_at->diffForHumans() }}</p>
                    </div>
                </div>

                <div x-show="showPasswordForm"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="mt-4">
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Password Lama</label>
                                <input type="password" name="current_password" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Masukkan password lama">
                                @error('current_password')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Password Baru</label>
                                <input type="password" name="password" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Minimal 8 karakter">
                                @error('password')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">Gunakan minimal 8 karakter dengan kombinasi huruf dan angka</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Konfirmasi Password Baru</label>
                                <input type="password" name="password_confirmation" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Ulangi password baru">
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-3 mt-6">
                            <button type="button" @click="showPasswordForm = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-semibold">
                                Simpan Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
