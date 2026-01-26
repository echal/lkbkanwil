'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { getSasaranKegiatanList, toggleSasaranKegiatanStatus, deleteSasaranKegiatan, SasaranKegiatan } from '@/app/lib/master-kinerja-api';

export default function SasaranKegiatanPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const [sasaranList, setSasaranList] = useState<SasaranKegiatan[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // RBAC Guard
  useEffect(() => {
    if (authLoading) return;

    if (!isAuthenticated) {
      router.push('/login');
      return;
    }

    if (user?.role !== 'ADMIN') {
      router.push('/unauthorized');
      return;
    }

    loadSasaranList();
  }, [user, isAuthenticated, authLoading, router]);

  const loadSasaranList = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getSasaranKegiatanList();
      setSasaranList(data);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat data Sasaran Kegiatan');
      console.error('Failed to load Sasaran Kegiatan:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleToggleStatus = async (id: number, currentStatus: string) => {
    const confirmMsg = currentStatus === 'AKTIF'
      ? 'Nonaktifkan Sasaran Kegiatan ini? Data yang nonaktif tidak akan muncul di form ASN.'
      : 'Aktifkan kembali Sasaran Kegiatan ini?';

    if (!confirm(confirmMsg)) return;

    try {
      await toggleSasaranKegiatanStatus(id);
      await loadSasaranList();
    } catch (err: any) {
      alert(err.message || 'Gagal mengubah status');
    }
  };

  const handleDelete = async (id: number, digunakanAsn: boolean) => {
    if (digunakanAsn) {
      alert('Sasaran Kegiatan ini tidak dapat dihapus karena sedang digunakan oleh ASN');
      return;
    }

    if (!confirm('Hapus Sasaran Kegiatan ini? Semua Indikator Kinerja terkait akan ikut terhapus!')) {
      return;
    }

    try {
      await deleteSasaranKegiatan(id);
      await loadSasaranList();
      alert('Sasaran Kegiatan berhasil dihapus');
    } catch (err: any) {
      alert(err.message || 'Gagal menghapus Sasaran Kegiatan');
    }
  };

  const handleEdit = (id: number) => {
    router.push(`/admin/sasaran-kegiatan/edit/${id}`);
  };

  const handleAdd = () => {
    router.push('/admin/sasaran-kegiatan/tambah');
  };

  const handleViewIndikator = (id: number) => {
    router.push(`/admin/sasaran-kegiatan/${id}/indikator`);
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

  if (!isAuthenticated || user?.role !== 'ADMIN') {
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
                onClick={() => router.push('/admin/dashboard')}
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
                Sasaran Kegiatan
              </h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-7xl mx-auto">
          {/* Header dengan tombol tambah */}
          <div className="mb-6 flex justify-between items-center">
            <div>
              <h2 className="text-2xl font-bold text-gray-800">Master Sasaran Kegiatan</h2>
              <p className="text-gray-600 mt-1">
                Kelola sasaran strategis organisasi/unit kerja
              </p>
            </div>
            <button
              onClick={handleAdd}
              className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
              <svg
                className="h-5 w-5 mr-2"
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
              Tambah Sasaran
            </button>
          </div>

          {/* Error message */}
          {error && (
            <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
              {error}
            </div>
          )}

          {/* Table */}
          <div className="bg-white rounded-lg shadow overflow-hidden">
            {sasaranList.length === 0 ? (
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
                <p className="mt-4 text-lg">Belum ada Sasaran Kegiatan yang dibuat</p>
                <p className="text-sm text-gray-400 mt-1">
                  Klik tombol "Tambah Sasaran" untuk membuat yang baru
                </p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Unit Kerja
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Sasaran Kegiatan
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Indikator
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Digunakan ASN
                      </th>
                      <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Aksi
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {sasaranList.map((sasaran) => (
                      <tr key={sasaran.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 text-sm text-gray-900">
                          {sasaran.unit_kerja}
                        </td>
                        <td className="px-6 py-4 text-sm text-gray-900">
                          <div className="max-w-2xl">
                            {sasaran.sasaran_kegiatan}
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                          <button
                            onClick={() => handleViewIndikator(sasaran.id)}
                            className="text-blue-600 hover:text-blue-900 font-medium"
                          >
                            {sasaran.jumlah_indikator} indikator
                          </button>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span
                            className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                              sasaran.status === 'AKTIF'
                                ? 'bg-green-100 text-green-800'
                                : 'bg-gray-100 text-gray-800'
                            }`}
                          >
                            {sasaran.status}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {sasaran.digunakan_asn ? (
                            <span className="text-blue-600 font-medium">Ya</span>
                          ) : (
                            <span className="text-gray-400">Tidak</span>
                          )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                          {/* View Indikator Button */}
                          <button
                            onClick={() => handleViewIndikator(sasaran.id)}
                            className="text-purple-600 hover:text-purple-900"
                            title="Kelola Indikator"
                          >
                            <svg
                              className="h-5 w-5 inline"
                              fill="none"
                              stroke="currentColor"
                              viewBox="0 0 24 24"
                            >
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                              />
                            </svg>
                          </button>

                          {/* Edit Button */}
                          <button
                            onClick={() => handleEdit(sasaran.id)}
                            className="text-blue-600 hover:text-blue-900"
                            title="Edit"
                          >
                            <svg
                              className="h-5 w-5 inline"
                              fill="none"
                              stroke="currentColor"
                              viewBox="0 0 24 24"
                            >
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                              />
                            </svg>
                          </button>

                          {/* Toggle Status Button */}
                          <button
                            onClick={() => handleToggleStatus(sasaran.id, sasaran.status)}
                            className={`${
                              sasaran.status === 'AKTIF'
                                ? 'text-yellow-600 hover:text-yellow-900'
                                : 'text-green-600 hover:text-green-900'
                            }`}
                            title={sasaran.status === 'AKTIF' ? 'Nonaktifkan' : 'Aktifkan'}
                          >
                            {sasaran.status === 'AKTIF' ? (
                              <svg
                                className="h-5 w-5 inline"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                              >
                                <path
                                  strokeLinecap="round"
                                  strokeLinejoin="round"
                                  strokeWidth={2}
                                  d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                                />
                              </svg>
                            ) : (
                              <svg
                                className="h-5 w-5 inline"
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
                            )}
                          </button>

                          {/* Delete Button */}
                          <button
                            onClick={() => handleDelete(sasaran.id, sasaran.digunakan_asn)}
                            className="text-red-600 hover:text-red-900"
                            title="Hapus"
                            disabled={sasaran.digunakan_asn}
                          >
                            <svg
                              className="h-5 w-5 inline"
                              fill="none"
                              stroke="currentColor"
                              viewBox="0 0 24 24"
                            >
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                              />
                            </svg>
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>

          {/* Info Box */}
          <div className="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
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
                  Catatan Penting
                </h3>
                <div className="mt-2 text-sm text-blue-700">
                  <ul className="list-disc list-inside space-y-1">
                    <li>Sasaran Kegiatan adalah master data strategis organisasi</li>
                    <li>Setiap sasaran bisa memiliki beberapa Indikator Kinerja</li>
                    <li>ASN akan memilih Sasaran & Indikator saat membuat SKP</li>
                    <li>Data yang nonaktif tidak akan muncul di form ASN</li>
                    <li>Data yang sudah digunakan ASN tidak dapat dihapus</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
