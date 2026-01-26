'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { getRencanaKerjaById, type RencanaKerjaAsn } from '@/app/lib/rencana-kerja-api';

export default function DetailRencanaKerjaPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const id = parseInt(params.id as string);

  const [rencana, setRencana] = useState<RencanaKerjaAsn | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // RBAC Guard
  useEffect(() => {
    if (authLoading) return;

    if (!isAuthenticated) {
      router.push('/login');
      return;
    }

    if (user?.role !== 'ASN') {
      router.push('/unauthorized');
      return;
    }

    loadRencanaDetail();
  }, [user, isAuthenticated, authLoading, router, id]);

  const loadRencanaDetail = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getRencanaKerjaById(id);
      setRencana(data);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat detail Rencana Kerja');
      console.error('Failed to load Rencana Kerja detail:', err);
    } finally {
      setLoading(false);
    }
  };

  // Loading state
  if (authLoading || loading) {
    return (
      <div className="p-8">
        <div className="animate-pulse space-y-6">
          <div className="h-8 bg-gray-200 rounded w-1/3"></div>
          <div className="h-64 bg-gray-200 rounded"></div>
        </div>
      </div>
    );
  }

  if (error || !rencana) {
    return (
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-3xl mx-auto">
          <div className="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg">
            <p className="font-semibold">Error</p>
            <p className="mt-2">{error || 'Rencana Kerja tidak ditemukan'}</p>
            <button
              onClick={() => router.push('/asn/dashboard')}
              className="mt-4 text-red-800 hover:text-red-900 underline"
            >
              ← Kembali ke Dashboard
            </button>
          </div>
        </div>
      </div>
    );
  }

  const capaianPersen = rencana.target > 0
    ? Math.round((rencana.realisasi / rencana.target) * 100)
    : 0;

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top Navigation Bar */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => router.back()}
                className="text-gray-600 hover:text-gray-900"
              >
                <svg
                  className="h-6 w-6"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M15 19l-7-7 7-7"
                  />
                </svg>
              </button>
              <h1 className="text-xl font-semibold text-gray-900">
                Detail SKP Triwulan
              </h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-4xl mx-auto">
          {/* Status Badge */}
          <div className="mb-6 flex items-center justify-between">
            <span
              className={`inline-flex px-4 py-2 text-sm font-semibold rounded-full ${
                rencana.status === 'DRAFT'
                  ? 'bg-gray-100 text-gray-800'
                  : rencana.status === 'DIAJUKAN'
                  ? 'bg-yellow-100 text-yellow-800'
                  : rencana.status === 'DISETUJUI'
                  ? 'bg-green-100 text-green-800'
                  : 'bg-red-100 text-red-800'
              }`}
            >
              Status: {rencana.status}
            </span>

            <div className="flex gap-3">
              {(rencana.status === 'DRAFT' || rencana.status === 'DITOLAK') && (
                <button
                  onClick={() => router.push(`/asn/rencana-kerja/edit/${rencana.id}`)}
                  className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                  Edit
                </button>
              )}
            </div>
          </div>

          {/* Main Content */}
          <div className="bg-white rounded-lg shadow p-6 space-y-6">
            {/* Sasaran & Indikator */}
            <div>
              <h2 className="text-lg font-semibold text-gray-800 mb-4">Sasaran & Indikator Kinerja</h2>
              <div className="space-y-3">
                <div>
                  <p className="text-sm font-medium text-gray-600 mb-1">Sasaran Kegiatan</p>
                  <p className="text-base text-gray-900">
                    {rencana.sasaran_kegiatan?.sasaran_kegiatan || '-'}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-600 mb-1">Indikator Kinerja</p>
                  <p className="text-base text-gray-900">
                    {rencana.indikator_kinerja?.indikator_kinerja || '-'}
                  </p>
                </div>
              </div>
            </div>

            <hr />

            {/* Periode */}
            <div>
              <h2 className="text-lg font-semibold text-gray-800 mb-4">Periode</h2>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm font-medium text-gray-600 mb-1">Tahun</p>
                  <p className="text-base text-gray-900">{rencana.tahun}</p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-600 mb-1">Triwulan</p>
                  <p className="text-base text-gray-900">Triwulan {rencana.triwulan}</p>
                </div>
              </div>
            </div>

            <hr />

            {/* Target & Realisasi */}
            <div>
              <h2 className="text-lg font-semibold text-gray-800 mb-4">Target & Realisasi</h2>
              <div className="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <p className="text-sm font-medium text-gray-600 mb-1">Target</p>
                  <p className="text-base text-gray-900">
                    {rencana.target} {rencana.satuan}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-600 mb-1">Realisasi</p>
                  <p className="text-base text-gray-900">
                    {rencana.realisasi} {rencana.satuan}
                  </p>
                </div>
              </div>

              <div>
                <p className="text-sm font-medium text-gray-600 mb-2">Capaian</p>
                <div className="flex items-center gap-4">
                  <div className="flex-1 bg-gray-200 rounded-full h-4">
                    <div
                      className={`h-4 rounded-full ${
                        capaianPersen >= 90
                          ? 'bg-green-500'
                          : capaianPersen >= 70
                          ? 'bg-yellow-500'
                          : 'bg-red-500'
                      }`}
                      style={{ width: `${Math.min(capaianPersen, 100)}%` }}
                    ></div>
                  </div>
                  <span className="text-lg font-bold text-gray-900">
                    {capaianPersen}%
                  </span>
                </div>
              </div>
            </div>

            {/* Catatan ASN */}
            {rencana.catatan_asn && (
              <>
                <hr />
                <div>
                  <h2 className="text-lg font-semibold text-gray-800 mb-4">Catatan ASN</h2>
                  <div className="bg-gray-50 rounded-lg p-4">
                    <p className="text-base text-gray-900 whitespace-pre-wrap">
                      {rencana.catatan_asn}
                    </p>
                  </div>
                </div>
              </>
            )}

            {/* Catatan Atasan (if exists) */}
            {rencana.catatan_atasan && (
              <>
                <hr />
                <div>
                  <h2 className="text-lg font-semibold text-gray-800 mb-4">Catatan Atasan</h2>
                  <div className="bg-blue-50 rounded-lg p-4">
                    <p className="text-base text-gray-900 whitespace-pre-wrap">
                      {rencana.catatan_atasan}
                    </p>
                  </div>
                  {rencana.approved_by_user && (
                    <p className="text-sm text-gray-600 mt-2">
                      Oleh: {rencana.approved_by_user.name}
                    </p>
                  )}
                </div>
              </>
            )}
          </div>

          {/* Action Buttons */}
          <div className="mt-6 flex gap-4">
            <button
              onClick={() => router.back()}
              className="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            >
              ← Kembali
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
