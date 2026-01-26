'use client';

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { getHarianList, deleteHarian, Harian } from '@/app/lib/harian-api';
import { getAvailableBulanan, Bulanan } from '@/app/lib/bulanan-api';

export default function HarianPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const searchParams = useSearchParams();
  const bulananIdParam = searchParams?.get('bulanan_id');

  const [harianList, setHarianList] = useState<Harian[]>([]);
  const [bulananList, setBulananList] = useState<Bulanan[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Filters
  const [filterBulananId, setFilterBulananId] = useState<number | undefined>(
    bulananIdParam ? parseInt(bulananIdParam) : undefined
  );
  const [filterBulan, setFilterBulan] = useState<number | undefined>();
  const [filterTahun, setFilterTahun] = useState<number | undefined>(
    new Date().getFullYear()
  );

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
  }, [user, isAuthenticated, authLoading, router, filterBulananId, filterBulan, filterTahun]);

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Load available Bulanan for filter
      const bulananData = await getAvailableBulanan();
      setBulananList(bulananData);

      // Load Harian list
      const harianData = await getHarianList({
        bulanan_id: filterBulananId,
        bulan: filterBulan,
        tahun: filterTahun,
      });
      setHarianList(harianData);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat data Harian');
      console.error('Failed to load Harian:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Hapus kegiatan Harian ini?')) return;

    try {
      await deleteHarian(id);
      await loadData();
      alert('Harian berhasil dihapus');
    } catch (err: any) {
      alert(err.message || 'Gagal menghapus Harian');
    }
  };

  const handleAdd = () => {
    router.push('/asn/harian/tambah');
  };

  const handleEdit = (id: number) => {
    router.push(`/asn/harian/edit/${id}`);
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
                Kegiatan Harian
              </h1>
            </div>
            <button
              onClick={handleAdd}
              className="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
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
                  d="M12 4v16m8-8H4"
                />
              </svg>
              Tambah Kegiatan
            </button>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-7xl mx-auto">
          {/* Header */}
          <div className="mb-6">
            <h2 className="text-2xl font-bold text-gray-800">Daftar Kegiatan Harian</h2>
            <p className="text-gray-600 mt-1">
              Input kegiatan harian dengan bukti mandatory
            </p>
          </div>

          {/* Info Box */}
          <div className="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg
                  className="h-5 w-5 text-yellow-400"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fillRule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clipRule="evenodd"
                  />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-yellow-800">
                  PENTING: Bukti Wajib Dilampirkan
                </h3>
                <div className="mt-2 text-sm text-yellow-700">
                  <p>
                    Setiap kegiatan Harian wajib dilampiri bukti (file atau link).
                    Tanpa bukti, kegiatan tidak dapat disimpan.
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Filters */}
          <div className="mb-6 bg-white rounded-lg shadow p-4">
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Bulanan
                </label>
                <select
                  value={filterBulananId || ''}
                  onChange={(e) =>
                    setFilterBulananId(
                      e.target.value ? parseInt(e.target.value) : undefined
                    )
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900"
                >
                  <option value="">Semua Bulanan</option>
                  {bulananList.map((bulanan) => (
                    <option key={bulanan.id} value={bulanan.id}>
                      {bulanan.bulan_nama} {bulanan.tahun} -{' '}
                      {bulanan.rencana_kerja_asn?.sasaran_kegiatan?.sasaran_kegiatan}
                    </option>
                  ))}
                </select>
              </div>

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
                  Bulan
                </label>
                <select
                  value={filterBulan || ''}
                  onChange={(e) =>
                    setFilterBulan(e.target.value ? parseInt(e.target.value) : undefined)
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900"
                >
                  <option value="">Semua Bulan</option>
                  {[
                    'Januari',
                    'Februari',
                    'Maret',
                    'April',
                    'Mei',
                    'Juni',
                    'Juli',
                    'Agustus',
                    'September',
                    'Oktober',
                    'November',
                    'Desember',
                  ].map((bulan, idx) => (
                    <option key={idx + 1} value={idx + 1}>
                      {bulan}
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex items-end">
                <button
                  onClick={() => {
                    setFilterBulananId(undefined);
                    setFilterBulan(undefined);
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
            {harianList.length === 0 ? (
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
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                  />
                </svg>
                <p className="mt-4 text-lg">Belum ada kegiatan Harian</p>
                <p className="text-sm text-gray-400 mt-1">
                  Klik tombol "Tambah Kegiatan" untuk mulai input
                </p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Tanggal
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Kegiatan
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Progres
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Bukti
                      </th>
                      <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Aksi
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {harianList.map((harian) => (
                      <tr key={harian.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm font-medium text-gray-900">
                            {new Date(harian.tanggal).toLocaleDateString('id-ID', {
                              day: '2-digit',
                              month: 'short',
                              year: 'numeric',
                            })}
                          </div>
                          <div className="text-xs text-gray-500">
                            {harian.bulanan?.bulan_nama}
                          </div>
                        </td>
                        <td className="px-6 py-4">
                          <div className="text-sm text-gray-900">
                            {harian.kegiatan_harian.substring(0, 100)}
                            {harian.kegiatan_harian.length > 100 && '...'}
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {harian.progres} {harian.satuan}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          {harian.bukti_type === 'file' ? (
                            <a
                              href={harian.bukti_url || '#'}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="text-blue-600 hover:text-blue-900 text-sm"
                            >
                              📎 {harian.bukti_display}
                            </a>
                          ) : (
                            <a
                              href={harian.bukti_link || '#'}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="text-blue-600 hover:text-blue-900 text-sm"
                            >
                              🔗 Link
                            </a>
                          )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                          <button
                            onClick={() => handleEdit(harian.id)}
                            className="text-blue-600 hover:text-blue-900"
                          >
                            Edit
                          </button>
                          <span className="text-gray-300">|</span>
                          <button
                            onClick={() => handleDelete(harian.id)}
                            className="text-red-600 hover:text-red-900"
                          >
                            Hapus
                          </button>
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
