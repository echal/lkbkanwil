'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import {
  getSasaranKegiatanById,
  getIndikatorKinerjaList,
  createIndikatorKinerja,
  updateIndikatorKinerja,
  toggleIndikatorKinerjaStatus,
  deleteIndikatorKinerja,
  SasaranKegiatan,
  IndikatorKinerja,
} from '@/app/lib/master-kinerja-api';

export default function KelolaIndikatorKinerjaPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const sasaranId = params.id as string;

  const [sasaran, setSasaran] = useState<SasaranKegiatan | null>(null);
  const [indikatorList, setIndikatorList] = useState<IndikatorKinerja[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Form state untuk tambah/edit
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [formData, setFormData] = useState({
    indikator_kinerja: '',
    status: 'AKTIF' as 'AKTIF' | 'NONAKTIF',
  });
  const [saving, setSaving] = useState(false);

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

    loadData();
  }, [user, isAuthenticated, authLoading, router, sasaranId]);

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);

      const [sasaranData, indikatorData] = await Promise.all([
        getSasaranKegiatanById(Number(sasaranId)),
        getIndikatorKinerjaList(Number(sasaranId)),
      ]);

      setSasaran(sasaranData);
      setIndikatorList(indikatorData);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat data');
      console.error('Failed to load data:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleAdd = () => {
    setEditingId(null);
    setFormData({
      indikator_kinerja: '',
      status: 'AKTIF',
    });
    setShowForm(true);
  };

  const handleEdit = (indikator: IndikatorKinerja) => {
    setEditingId(indikator.id);
    setFormData({
      indikator_kinerja: indikator.indikator_kinerja,
      status: indikator.status,
    });
    setShowForm(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.indikator_kinerja.trim()) {
      alert('Indikator Kinerja harus diisi');
      return;
    }

    try {
      setSaving(true);

      if (editingId) {
        // Update
        await updateIndikatorKinerja(editingId, {
          sasaran_kegiatan_id: Number(sasaranId),
          indikator_kinerja: formData.indikator_kinerja,
          status: formData.status,
        });
        alert('Indikator Kinerja berhasil diperbarui!');
      } else {
        // Create
        await createIndikatorKinerja({
          sasaran_kegiatan_id: Number(sasaranId),
          indikator_kinerja: formData.indikator_kinerja,
          status: formData.status,
        });
        alert('Indikator Kinerja berhasil ditambahkan!');
      }

      setShowForm(false);
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Gagal menyimpan Indikator Kinerja');
    } finally {
      setSaving(false);
    }
  };

  const handleToggleStatus = async (id: number, currentStatus: string) => {
    const confirmMsg =
      currentStatus === 'AKTIF'
        ? 'Nonaktifkan Indikator Kinerja ini?'
        : 'Aktifkan kembali Indikator Kinerja ini?';

    if (!confirm(confirmMsg)) return;

    try {
      await toggleIndikatorKinerjaStatus(id);
      await loadData();
    } catch (err: any) {
      alert(err.message || 'Gagal mengubah status');
    }
  };

  const handleDelete = async (id: number, digunakanAsn: boolean) => {
    if (digunakanAsn) {
      alert('Indikator Kinerja ini tidak dapat dihapus karena sedang digunakan oleh ASN');
      return;
    }

    if (!confirm('Hapus Indikator Kinerja ini?')) return;

    try {
      await deleteIndikatorKinerja(id);
      await loadData();
      alert('Indikator Kinerja berhasil dihapus');
    } catch (err: any) {
      alert(err.message || 'Gagal menghapus Indikator Kinerja');
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
                onClick={() => router.push('/admin/sasaran-kegiatan')}
                className="text-gray-600 hover:text-gray-900"
              >
                <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <h1 className="text-xl font-semibold text-gray-900">Kelola Indikator Kinerja</h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-7xl mx-auto">
          {/* Header dengan info sasaran */}
          <div className="mb-6 bg-white rounded-lg shadow p-6">
            <h2 className="text-xl font-bold text-gray-800">Sasaran Kegiatan</h2>
            <p className="text-sm text-gray-600 mt-1">Unit Kerja: {sasaran?.unit_kerja}</p>
            <p className="text-gray-900 mt-2">{sasaran?.sasaran_kegiatan}</p>
          </div>

          {/* Header dengan tombol tambah */}
          <div className="mb-6 flex justify-between items-center">
            <div>
              <h2 className="text-2xl font-bold text-gray-800">Indikator Kinerja</h2>
              <p className="text-gray-600 mt-1">Kelola indikator untuk sasaran ini</p>
            </div>
            <button
              onClick={handleAdd}
              className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
              <svg className="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              Tambah Indikator
            </button>
          </div>

          {/* Error message */}
          {error && (
            <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
              {error}
            </div>
          )}

          {/* Form Tambah/Edit */}
          {showForm && (
            <div className="mb-6 bg-white rounded-lg shadow p-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">
                {editingId ? 'Edit Indikator Kinerja' : 'Tambah Indikator Kinerja'}
              </h3>
              <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Indikator Kinerja <span className="text-red-500">*</span>
                  </label>
                  <textarea
                    rows={4}
                    value={formData.indikator_kinerja}
                    onChange={(e) =>
                      setFormData({ ...formData, indikator_kinerja: e.target.value })
                    }
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 placeholder:text-gray-400"
                    placeholder="Contoh: Nilai Indeks Kerukunan Umat Beragama"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                  <select
                    value={formData.status}
                    onChange={(e) =>
                      setFormData({ ...formData, status: e.target.value as 'AKTIF' | 'NONAKTIF' })
                    }
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900"
                  >
                    <option value="AKTIF">Aktif</option>
                    <option value="NONAKTIF">Nonaktif</option>
                  </select>
                </div>

                <div className="flex justify-end space-x-3">
                  <button
                    type="button"
                    onClick={() => setShowForm(false)}
                    className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                    disabled={saving}
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400"
                    disabled={saving}
                  >
                    {saving ? 'Menyimpan...' : 'Simpan'}
                  </button>
                </div>
              </form>
            </div>
          )}

          {/* Table */}
          <div className="bg-white rounded-lg shadow overflow-hidden">
            {indikatorList.length === 0 ? (
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
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                  />
                </svg>
                <p className="mt-4 text-lg">Belum ada Indikator Kinerja</p>
                <p className="text-sm text-gray-400 mt-1">
                  Klik tombol "Tambah Indikator" untuk membuat yang baru
                </p>
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Indikator Kinerja
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
                    {indikatorList.map((indikator) => (
                      <tr key={indikator.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 text-sm text-gray-900">
                          <div className="max-w-2xl">{indikator.indikator_kinerja}</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span
                            className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                              indikator.status === 'AKTIF'
                                ? 'bg-green-100 text-green-800'
                                : 'bg-gray-100 text-gray-800'
                            }`}
                          >
                            {indikator.status}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {indikator.digunakan_asn ? (
                            <span className="text-blue-600 font-medium">Ya</span>
                          ) : (
                            <span className="text-gray-400">Tidak</span>
                          )}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                          <button
                            onClick={() => handleEdit(indikator)}
                            className="text-blue-600 hover:text-blue-900"
                            title="Edit"
                          >
                            <svg className="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                              />
                            </svg>
                          </button>

                          <button
                            onClick={() => handleToggleStatus(indikator.id, indikator.status)}
                            className={`${
                              indikator.status === 'AKTIF'
                                ? 'text-yellow-600 hover:text-yellow-900'
                                : 'text-green-600 hover:text-green-900'
                            }`}
                            title={indikator.status === 'AKTIF' ? 'Nonaktifkan' : 'Aktifkan'}
                          >
                            <svg className="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d={
                                  indikator.status === 'AKTIF'
                                    ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'
                                    : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                                }
                              />
                            </svg>
                          </button>

                          <button
                            onClick={() => handleDelete(indikator.id, indikator.digunakan_asn)}
                            className="text-red-600 hover:text-red-900"
                            title="Hapus"
                            disabled={indikator.digunakan_asn}
                          >
                            <svg className="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <svg className="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fillRule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                    clipRule="evenodd"
                  />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-blue-800">Catatan Penting</h3>
                <div className="mt-2 text-sm text-blue-700">
                  <ul className="list-disc list-inside space-y-1">
                    <li>Indikator Kinerja merupakan turunan dari Sasaran Kegiatan</li>
                    <li>ASN akan memilih indikator ini saat membuat SKP</li>
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
