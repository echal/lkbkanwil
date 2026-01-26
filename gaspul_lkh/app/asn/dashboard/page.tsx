'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';

export default function AsnDashboardPage() {
  const { user, isAuthenticated, loading: authLoading, logout } = useAuth();
  const router = useRouter();
  const [loading, setLoading] = useState(false);

  // RBAC Guard
  useEffect(() => {
    if (authLoading) return;

    if (!isAuthenticated) {
      router.push('/login');
      return;
    }

    if (user?.role !== 'ASN') {
      // Redirect to appropriate dashboard based on role
      if (user?.role === 'ADMIN') {
        router.push('/admin/dashboard');
      } else if (user?.role === 'ATASAN') {
        router.push('/atasan/dashboard');
      } else {
        router.push('/unauthorized');
      }
      return;
    }
  }, [user, isAuthenticated, authLoading, router]);

  // Loading state
  if (authLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Memuat...</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated || user?.role !== 'ASN') {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top Navigation Bar */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            {/* User Info */}
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center">
                  <span className="text-white font-semibold text-sm">
                    {user?.name.charAt(0).toUpperCase()}
                  </span>
                </div>
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-gray-900">{user?.name}</p>
                <p className="text-xs text-gray-500">{user?.role}</p>
              </div>
            </div>

            {/* Logout Button */}
            <button
              onClick={logout}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition"
            >
              <svg
                className="-ml-1 mr-2 h-5 w-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                />
              </svg>
              Logout
            </button>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-800">Dashboard ASN</h1>
          <p className="text-gray-600 mt-1">Selamat datang, {user.name}</p>
        </div>

      {/* Welcome Banner */}
      <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-8 mb-8 text-white">
        <h2 className="text-2xl font-bold mb-2">Selamat Datang, {user.name}!</h2>
        <p className="text-blue-100">Kelola SKP Tahunan dan pantau kinerja Anda</p>
      </div>

      {/* Main Menu Grid */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {/* SKP Tahunan V2 - Primary */}
        <button
          onClick={() => router.push('/asn/skp-tahunan-v2')}
          className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow text-left border-2 border-blue-500"
        >
          <div className="flex items-center mb-4">
            <div className="flex-shrink-0 bg-blue-100 rounded-lg p-3">
              <svg className="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            SKP Tahunan
          </h3>
          <p className="text-sm text-gray-600">
            Kelola Sasaran Kinerja Pegawai Tahunan berdasarkan RHK Pimpinan
          </p>
          <div className="mt-4">
            <span className="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded font-semibold">
              PRIORITAS UTAMA
            </span>
          </div>
        </button>

        {/* Rencana Aksi Bulanan */}
        <button
          onClick={() => router.push('/asn/rencana-aksi-bulanan')}
          className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow text-left border-2 border-gray-200"
        >
          <div className="flex items-center mb-4">
            <div className="flex-shrink-0 bg-purple-100 rounded-lg p-3">
              <svg className="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
            </div>
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Rencana Aksi Bulanan
          </h3>
          <p className="text-sm text-gray-600">
            Target dan rencana kerja per bulan (12 periode)
          </p>
          <div className="mt-4">
            <span className="inline-block bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded font-semibold">
              LANGKAH 2
            </span>
          </div>
        </button>

        {/* Progres Harian */}
        <button
          onClick={() => router.push('/asn/progres-harian')}
          className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow text-left border-2 border-gray-200"
        >
          <div className="flex items-center mb-4">
            <div className="flex-shrink-0 bg-orange-100 rounded-lg p-3">
              <svg className="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Progres Harian
          </h3>
          <p className="text-sm text-gray-600">
            Laporan kegiatan harian dengan kalender visual
          </p>
          <div className="mt-4">
            <span className="inline-block bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded font-semibold">
              LANGKAH 3
            </span>
          </div>
        </button>

        {/* Laporan Kinerja Saya */}
        <button
          onClick={() => router.push('/asn/laporan-kinerja')}
          className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow text-left border-2 border-green-500"
        >
          <div className="flex items-center mb-4">
            <div className="flex-shrink-0 bg-green-100 rounded-lg p-3">
              <svg className="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Laporan Kinerja Saya
          </h3>
          <p className="text-sm text-gray-600">
            Monitor dan cetak laporan kinerja pribadi (LKH & TLA)
          </p>
          <div className="mt-4">
            <span className="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded font-semibold">
              MONITORING
            </span>
          </div>
        </button>
      </div>

      {/* Info Box */}
      <div className="bg-blue-50 border-l-4 border-blue-500 p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <p className="text-sm text-blue-700">
              <strong>Alur Kerja:</strong> Mulai dengan membuat SKP Tahunan berdasarkan RHK Pimpinan yang di Intervensi.
              Setelah SKP Tahunan disetujui, lanjutkan dengan Rencana Aksi Bulanan, kemudian input Progres Harian.
            </p>
          </div>
        </div>
      </div>

      </div>
    </div>
  );
}
