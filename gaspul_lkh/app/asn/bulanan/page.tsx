'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { getBulananList, Bulanan } from '@/app/lib/bulanan-api';
import { getRencanaKerjaList, RencanaKerjaAsn } from '@/app/lib/rencana-kerja-api';

export default function BulananPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const [bulananList, setBulananList] = useState<Bulanan[]>([]);
  const [skpList, setSkpList] = useState<RencanaKerjaAsn[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Filters
  const [filterSkpId, setFilterSkpId] = useState<number | undefined>();
  const [filterTahun, setFilterTahun] = useState<number | undefined>(new Date().getFullYear());

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

    loadData();
  }, [user, isAuthenticated, authLoading, router, filterSkpId, filterTahun]);

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Load approved SKP list for filter
      const skpData = await getRencanaKerjaList({
        status: 'DISETUJUI',
        tahun: filterTahun,
      });
      setSkpList(skpData);

      // Load Bulanan list
      const bulananData = await getBulananList({
        skp_id: filterSkpId,
        tahun: filterTahun,
      });
      setBulananList(bulananData);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat data Bulanan');
      console.error('Failed to load Bulanan:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleEditTarget = (id: number) => {
    router.push(`/asn/bulanan/edit/${id}`);
  };

  const handleViewHarian = (bulananId: number) => {
    router.push(`/asn/harian?bulanan_id=${bulananId}`);
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

  if (!isAuthenticated || user?.role !== 'ASN') {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top Navigation Bar */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => router.push('/asn/dashboard')}
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
                Rencana Kerja Bulanan
              </h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-7xl mx-auto">
          {/* Header */}
          <div className="mb-6">
            <h2 className="text-2xl font-bold text-gray-800">Target dan Realisasi Bulanan</h2>
            <p className="text-gray-600 mt-1">
              Kelola target bulanan dari SKP yang telah disetujui
            </p>
          </div>

          {/* Info Box */}
          <div className="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg
                  className="h-5 w-5 text-blue-400"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fillRule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                    clipRule="evenodd"
                  />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-blue-800">
                  Cara Kerja Bulanan
                </h3>
                <div className="mt-2 text-sm text-blue-700">
                  <ul className="list-disc list-inside space-y-1">
                    <li>Bulanan otomatis dibuat ketika SKP Triwulan disetujui Atasan</li>
                    <li>Anda harus mengisi Target Bulanan sebelum bisa input Harian</li>
                    <li>Realisasi Bulanan otomatis terhitung dari sum Harian</li>
                    <li>Setelah target diisi, Anda bisa mulai input kegiatan Harian</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          {/* Filters */}
          <div className="mb-6 bg-white rounded-lg shadow p-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Tahun
                </label>
                <select
                  value={filterTahun || ''}
                  onChange={(e) =>
                    setFilterTahun(e.target.value ? parseInt(e.target.value) : undefined)
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900"
                >
                  <option value="">Semua Tahun</option>
                  {[2024, 2025, 2026].map((year) => (
                    <option key={year} value={year}>
                      {year}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  SKP Triwulan
                </label>
                <select
                  value={filterSkpId || ''}
                  onChange={(e) =>
                    setFilterSkpId(e.target.value ? parseInt(e.target.value) : undefined)
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900"
                >
                  <option value="">Semua SKP</option>
                  {skpList.map((skp) => (
                    <option key={skp.id} value={skp.id}>
                      Triwulan {skp.triwulan} / {skp.tahun} -{' '}
                      {skp.sasaran_kegiatan?.sasaran_kegiatan}
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex items-end">
                <button
                  onClick={() => {
                    setFilterSkpId(undefined);
                    setFilterTahun(new Date().getFullYear());
                  }}
                  className="w-full px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                >
                  Reset Filter
                </button>
              </div>
            </div>
          </div>

          {/* Error message */}
          {error && (
            <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
              {error}
            </div>
          )}

          {/* Table */}
          <div className="bg-white rounded-lg shadow overflow-hidden">
            {bulananList.length === 0 ? (
              <div className="text-center py-12 text-gray-500">
                <svg
                  className="mx-auto h-12 w-12 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  />
                </svg>
                <p className="mt-4 text-lg">Tidak ada Bulanan</p>
                <p className="text-sm text-gray-400 mt-1">
                  Bulanan akan otomatis dibuat ketika SKP Triwulan disetujui
                </p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        SKP Triwulan
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Bulan
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Target
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Realisasi
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Capaian
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Aksi
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {bulananList.map((bulanan) => (
                      <tr key={bulanan.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4">
                          <div className="text-sm font-medium text-gray-900">
                            Triwulan {bulanan.rencana_kerja_asn?.triwulan} /{' '}
                            {bulanan.tahun}
                          </div>
                          <div className="text-xs text-gray-500">
                            {bulanan.rencana_kerja_asn?.sasaran_kegiatan?.sasaran_kegiatan}
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {bulanan.bulan_nama}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                          {bulanan.has_target_filled ? (
                            <span className="text-gray-900 font-medium">
                              {bulanan.target_bulanan}{' '}
                              {bulanan.rencana_kerja_asn?.satuan}
                            </span>
                          ) : (
                            <span className="text-red-600 font-medium">Belum diisi</span>
                          )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {bulanan.realisasi_bulanan}{' '}
                          {bulanan.rencana_kerja_asn?.satuan}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                          <span
                            className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                              (bulanan.capaian_persen || 0) >= 90
                                ? 'bg-green-100 text-green-800'
                                : (bulanan.capaian_persen || 0) >= 70
                                ? 'bg-yellow-100 text-yellow-800'
                                : 'bg-red-100 text-red-800'
                            }`}
                          >
                            {bulanan.capaian_persen || 0}%
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span
                            className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                              bulanan.status === 'AKTIF'
                                ? 'bg-blue-100 text-blue-800'
                                : 'bg-gray-100 text-gray-800'
                            }`}
                          >
                            {bulanan.status}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                          {!bulanan.has_target_filled ? (
                            <button
                              onClick={() => handleEditTarget(bulanan.id)}
                              className="text-green-600 hover:text-green-900"
                            >
                              Isi Target →
                            </button>
                          ) : (
                            <>
                              <button
                                onClick={() => handleEditTarget(bulanan.id)}
                                className="text-blue-600 hover:text-blue-900"
                              >
                                Edit Target
                              </button>
                              <span className="text-gray-300">|</span>
                              <button
                                onClick={() => handleViewHarian(bulanan.id)}
                                className="text-purple-600 hover:text-purple-900"
                              >
                                Lihat Harian →
                              </button>
                            </>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
