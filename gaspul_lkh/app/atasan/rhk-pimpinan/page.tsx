'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { rhkPimpinanApi, RhkPimpinan, indikatorKinerjaAtasanApi, IndikatorKinerja } from '@/app/lib/api-v2';

export default function RhkPimpinanPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();

  const [rhkList, setRhkList] = useState<RhkPimpinan[]>([]);
  const [indikatorList, setIndikatorList] = useState<IndikatorKinerja[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Form state
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [formData, setFormData] = useState({
    indikator_kinerja_id: 0,
    rhk_pimpinan: '',
    status: 'AKTIF' as 'AKTIF' | 'NONAKTIF',
  });

  // Filter state
  const [filterIndikator, setFilterIndikator] = useState<number>(0);
  const [filterStatus, setFilterStatus] = useState<string>('');

  // RBAC Guard
  useEffect(() => {
    if (authLoading) return;

    if (!isAuthenticated) {
      router.push('/login');
      return;
    }

    if (user?.role !== 'ATASAN') {
      router.push('/unauthorized');
      return;
    }

    loadData();
  }, [user, isAuthenticated, authLoading, router, filterIndikator, filterStatus]);

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Load RHK Pimpinan with filters
      const params: any = {};
      if (filterIndikator) params.indikator_kinerja_id = filterIndikator;
      if (filterStatus) params.status = filterStatus;

      const response = await rhkPimpinanApi.getAll(params);
      setRhkList(response.data);

      // Load Indikator Kinerja list for dropdown (only once)
      if (indikatorList.length === 0) {
        const indikatorResponse = await indikatorKinerjaAtasanApi.getAll({ status: 'AKTIF' });
        setIndikatorList(indikatorResponse.data);
      }
    } catch (err: any) {
      setError(err.message || 'Gagal memuat data');
      console.error('Failed to load data:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (formData.indikator_kinerja_id === 0) {
      alert('Pilih Indikator Kinerja terlebih dahulu');
      return;
    }

    if (!formData.rhk_pimpinan.trim()) {
      alert('RHK Pimpinan tidak boleh kosong');
      return;
    }

    try {
      setSaving(true);

      if (editingId) {
        await rhkPimpinanApi.update(editingId, formData);
        alert('RHK Pimpinan berhasil diperbarui!');
      } else {
        await rhkPimpinanApi.create(formData);
        alert('RHK Pimpinan berhasil ditambahkan!');
      }

      setShowForm(false);
      setEditingId(null);
      resetForm();
      loadData();
    } catch (err: any) {
      alert(err.message || 'Gagal menyimpan data');
    } finally {
      setSaving(false);
    }
  };

  const handleEdit = (item: RhkPimpinan) => {
    setEditingId(item.id);
    setFormData({
      indikator_kinerja_id: item.indikator_kinerja_id,
      rhk_pimpinan: item.rhk_pimpinan,
      status: item.status,
    });
    setShowForm(true);
  };

  const handleDelete = async (id: number, usageCount?: number) => {
    if (usageCount && usageCount > 0) {
      alert(`Tidak dapat menghapus RHK Pimpinan ini karena sudah digunakan dalam ${usageCount} SKP Tahunan`);
      return;
    }

    if (!confirm('Yakin ingin menghapus data ini?')) return;

    try {
      await rhkPimpinanApi.delete(id);
      alert('RHK Pimpinan berhasil dihapus!');
      loadData();
    } catch (err: any) {
      alert(err.message || 'Gagal menghapus data');
    }
  };

  const resetForm = () => {
    setFormData({
      indikator_kinerja_id: 0,
      rhk_pimpinan: '',
      status: 'AKTIF',
    });
  };

  const handleCancel = () => {
    setShowForm(false);
    setEditingId(null);
    resetForm();
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

  if (!isAuthenticated || user?.role !== 'ATASAN') {
    return null;
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => router.back()}
                className="text-gray-600 hover:text-gray-900 transition"
                title="Kembali"
              >
                <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <h1 className="text-2xl font-semibold text-gray-900">RHK Pimpinan</h1>
            </div>
            <button
              onClick={() => setShowForm(true)}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
            >
              + Tambah RHK Pimpinan
            </button>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-7xl mx-auto">
          {/* Error message */}
          {error && (
            <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
              {error}
            </div>
          )}

          {/* Form Modal */}
          {showForm && (
            <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
              <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div className="p-6">
                  <h2 className="text-xl font-semibold text-gray-900 mb-6">
                    {editingId ? 'Edit RHK Pimpinan' : 'Tambah RHK Pimpinan'}
                  </h2>

                  <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Indikator Kinerja Selection */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Indikator Kinerja <span className="text-red-500">*</span>
                      </label>
                      <select
                        value={formData.indikator_kinerja_id}
                        onChange={(e) => setFormData({ ...formData, indikator_kinerja_id: parseInt(e.target.value) })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal bg-white"
                        required
                      >
                        <option value="0">-- Pilih Indikator Kinerja --</option>
                        {indikatorList.map((indikator) => (
                          <option key={indikator.id} value={indikator.id}>
                            {indikator.sasaran_kegiatan?.unit_kerja || ''} - {indikator.indikator_kinerja}
                          </option>
                        ))}
                      </select>
                    </div>

                    {/* RHK Pimpinan */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        RHK Pimpinan yang di Intervensi <span className="text-red-500">*</span>
                      </label>
                      <textarea
                        value={formData.rhk_pimpinan}
                        onChange={(e) => setFormData({ ...formData, rhk_pimpinan: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                        rows={4}
                        placeholder="Contoh: Terlaksananya Layanan Keagamaan Berbasis IT"
                        required
                      />
                      <p className="mt-1 text-sm text-gray-500">
                        Masukkan Rencana Hasil Kerja Pimpinan yang akan menjadi acuan butir kinerja ASN
                      </p>
                    </div>

                    {/* Status */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Status
                      </label>
                      <select
                        value={formData.status}
                        onChange={(e) => setFormData({ ...formData, status: e.target.value as 'AKTIF' | 'NONAKTIF' })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal bg-white"
                      >
                        <option value="AKTIF">AKTIF</option>
                        <option value="NONAKTIF">NONAKTIF</option>
                      </select>
                    </div>

                    {/* Buttons */}
                    <div className="flex space-x-3 pt-4">
                      <button
                        type="button"
                        onClick={handleCancel}
                        className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
                      >
                        Batal
                      </button>
                      <button
                        type="submit"
                        disabled={saving}
                        className="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed font-medium"
                      >
                        {saving ? 'Menyimpan...' : 'Simpan'}
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          )}

          {/* Filters */}
          <div className="bg-white rounded-lg shadow p-4 mb-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Filter Indikator Kinerja
                </label>
                <select
                  value={filterIndikator}
                  onChange={(e) => setFilterIndikator(parseInt(e.target.value))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal bg-white"
                >
                  <option value="0">Semua Indikator Kinerja</option>
                  {indikatorList.map((indikator) => (
                    <option key={indikator.id} value={indikator.id}>
                      {indikator.sasaran_kegiatan?.unit_kerja || ''} - {indikator.indikator_kinerja}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Filter Status
                </label>
                <select
                  value={filterStatus}
                  onChange={(e) => setFilterStatus(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal bg-white"
                >
                  <option value="">Semua Status</option>
                  <option value="AKTIF">AKTIF</option>
                  <option value="NONAKTIF">NONAKTIF</option>
                </select>
              </div>
            </div>
          </div>

          {/* Table */}
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    No
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Indikator Kinerja
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    RHK Pimpinan
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Digunakan
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Aksi
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {rhkList.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-6 py-8 text-center text-gray-500">
                      Belum ada data RHK Pimpinan
                    </td>
                  </tr>
                ) : (
                  rhkList.map((item, index) => (
                    <tr key={item.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {index + 1}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        <div className="font-medium">{item.indikator_kinerja?.indikator_kinerja || '-'}</div>
                        <div className="text-gray-500 text-xs">
                          {item.indikator_kinerja?.sasaran_kegiatan?.unit_kerja || ''} - {item.indikator_kinerja?.sasaran_kegiatan?.sasaran_kegiatan || ''}
                        </div>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        {item.rhk_pimpinan}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span
                          className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            item.status === 'AKTIF'
                              ? 'bg-green-100 text-green-800'
                              : 'bg-gray-100 text-gray-800'
                          }`}
                        >
                          {item.status}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <span className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${
                          (item.skp_tahunan_details_count || 0) > 0
                            ? 'bg-blue-100 text-blue-800'
                            : 'bg-gray-100 text-gray-500'
                        }`}>
                          {item.skp_tahunan_details_count || 0} SKP
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <button
                          onClick={() => handleEdit(item)}
                          className="text-blue-600 hover:text-blue-900"
                        >
                          Edit
                        </button>
                        <button
                          onClick={() => handleDelete(item.id, item.skp_tahunan_details_count)}
                          className={`${
                            (item.skp_tahunan_details_count || 0) > 0
                              ? 'text-gray-400 cursor-not-allowed'
                              : 'text-red-600 hover:text-red-900'
                          }`}
                          disabled={(item.skp_tahunan_details_count || 0) > 0}
                        >
                          Hapus
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
