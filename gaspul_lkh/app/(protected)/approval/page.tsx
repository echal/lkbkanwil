'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import ApprovalTable from '@/app/components/ApprovalTable';
import { LaporanKinerja } from '@/app/types/dashboard';
import { getPendingLaporan, approveLaporan, rejectLaporan } from '@/app/lib/dashboard-api';

export default function ApprovalPage() {
  const { user, isAuthenticated, loading: authLoading, logout } = useAuth();
  const router = useRouter();
  const [laporanList, setLaporanList] = useState<LaporanKinerja[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // RBAC Guard: Only ADMIN and ATASAN can access
  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.push('/login');
      return;
    }

    if (!authLoading && user && user.role !== 'ADMIN' && user.role !== 'ATASAN') {
      router.push('/');
      return;
    }
  }, [authLoading, isAuthenticated, user, router]);

  // Load pending laporan
  useEffect(() => {
    if (user && (user.role === 'ADMIN' || user.role === 'ATASAN')) {
      loadPendingLaporan();
    }
  }, [user]);

  const loadPendingLaporan = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getPendingLaporan();
      setLaporanList(data);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat data laporan');
      console.error('Failed to load laporan:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (id: number, catatan: string) => {
    try {
      await approveLaporan(id, catatan);

      // Optimistic update
      setLaporanList((prev) =>
        prev.map((laporan) =>
          laporan.id === id
            ? { ...laporan, status: 'APPROVED' as const, catatan_atasan: catatan }
            : laporan
        )
      );

      alert('Laporan berhasil di-approve');
    } catch (err: any) {
      alert(err.message || 'Gagal approve laporan');
      throw err;
    }
  };

  const handleReject = async (id: number, catatan: string) => {
    try {
      await rejectLaporan(id, catatan);

      // Optimistic update
      setLaporanList((prev) =>
        prev.map((laporan) =>
          laporan.id === id
            ? { ...laporan, status: 'REJECTED' as const, catatan_atasan: catatan }
            : laporan
        )
      );

      alert('Laporan berhasil di-reject');
    } catch (err: any) {
      alert(err.message || 'Gagal reject laporan');
      throw err;
    }
  };

  // Loading state
  if (authLoading || loading) {
    return (
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-7xl mx-auto">
          <div className="animate-pulse space-y-6">
            <div className="h-8 bg-gray-200 rounded w-1/4"></div>
            <div className="h-64 bg-gray-200 rounded"></div>
          </div>
        </div>
      </div>
    );
  }

  // RBAC Check
  if (!user || (user.role !== 'ADMIN' && user.role !== 'ATASAN')) {
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

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Approval Laporan Kinerja</h1>
              <p className="mt-2 text-sm text-gray-600">
                Kelola persetujuan laporan kinerja ASN
              </p>
            </div>
            <button
              onClick={loadPendingLaporan}
              disabled={loading}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
            >
              <svg
                className={`-ml-1 mr-2 h-5 w-5 ${loading ? 'animate-spin' : ''}`}
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                />
              </svg>
              Refresh
            </button>
          </div>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                <svg
                  className="h-6 w-6 text-yellow-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
              <div className="ml-5">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">Pending</dt>
                  <dd className="mt-1 text-3xl font-semibold text-gray-900">
                    {laporanList.filter((l) => l.status === 'PENDING').length}
                  </dd>
                </dl>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-green-100 rounded-md p-3">
                <svg
                  className="h-6 w-6 text-green-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
              <div className="ml-5">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">Approved</dt>
                  <dd className="mt-1 text-3xl font-semibold text-gray-900">
                    {laporanList.filter((l) => l.status === 'APPROVED').length}
                  </dd>
                </dl>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-red-100 rounded-md p-3">
                <svg
                  className="h-6 w-6 text-red-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
              <div className="ml-5">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">Rejected</dt>
                  <dd className="mt-1 text-3xl font-semibold text-gray-900">
                    {laporanList.filter((l) => l.status === 'REJECTED').length}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <p className="text-sm">{error}</p>
          </div>
        )}

        {/* Approval Table */}
        <ApprovalTable
          laporanList={laporanList}
          onApprove={handleApprove}
          onReject={handleReject}
        />
      </div>
    </div>
  );
}
