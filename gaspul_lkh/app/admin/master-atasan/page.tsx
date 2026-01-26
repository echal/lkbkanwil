'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { masterAtasanApi, MasterAtasan, User } from '@/app/lib/api-v2';

export default function MasterAtasanPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();

  const [masterAtasanList, setMasterAtasanList] = useState<MasterAtasan[]>([]);
  const [asnList, setAsnList] = useState<User[]>([]);
  const [atasanList, setAtasanList] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Form state
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [formData, setFormData] = useState({
    asn_id: 0,
    atasan_id: 0,
    tahun: new Date().getFullYear(),
    status: 'AKTIF' as 'AKTIF' | 'NONAKTIF',
  });

  // Filter state
  const [filterTahun, setFilterTahun] = useState<number>(new Date().getFullYear());
  const [filterStatus, setFilterStatus] = useState<string>('');

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
  }, [user, isAuthenticated, authLoading, router, filterTahun, filterStatus]);

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Load master atasan with filters
      const params: any = {};
      if (filterTahun) params.tahun = filterTahun;
      if (filterStatus) params.status = filterStatus;

      const response = await masterAtasanApi.getAll(params);
      setMasterAtasanList(response.data);

      // Load ASN & Atasan lists for dropdowns
      const [asnData, atasanData] = await Promise.all([
        masterAtasanApi.getAsnList(),
        masterAtasanApi.getAtasanList(),
      ]);

      setAsnList(asnData);
      setAtasanList(atasanData);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat data');
      console.error('Failed to load data:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (formData.asn_id === 0) {
      alert('Pilih ASN terlebih dahulu');
      return;
    }

    if (formData.atasan_id === 0) {
      alert('Pilih Atasan terlebih dahulu');
      return;
    }

    if (formData.asn_id === formData.atasan_id) {
      alert('ASN tidak bisa menjadi atasan dirinya sendiri');
      return;
    }

    try {
      setSaving(true);

      if (editingId) {
        await masterAtasanApi.update(editingId, formData);
        alert('Master atasan berhasil diperbarui!');
      } else {
        await masterAtasanApi.create(formData);
        alert('Master atasan berhasil ditambahkan!');
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

  const handleEdit = (item: MasterAtasan) => {
    setEditingId(item.id);
    setFormData({
      asn_id: item.asn_id,
      atasan_id: item.atasan_id,
      tahun: item.tahun,
      status: item.status,
    });
    setShowForm(true);
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Yakin ingin menghapus data ini?')) return;

    try {
      await masterAtasanApi.delete(id);
      alert('Master atasan berhasil dihapus!');
      loadData();
    } catch (err: any) {
      alert(err.message || 'Gagal menghapus data');
    }
  };

  const resetForm = () => {
    setFormData({
      asn_id: 0,
      atasan_id: 0,
      tahun: new Date().getFullYear(),
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

  if (!isAuthenticated || user?.role !== 'ADMIN') {
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
              <h1 className="text-2xl font-semibold text-gray-900">Master Atasan</h1>
            </div>
            <button
              onClick={() => setShowForm(true)}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium"
            >
              + Tambah Master Atasan
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
                    {editingId ? 'Edit Master Atasan' : 'Tambah Master Atasan'}
                  </h2>

                  <form onSubmit={handleSubmit} className="space-y-6">
                    {/* ASN Selection */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        ASN / PPPK <span className="text-red-500">*</span>
                      </label>
                      <select
                        value={formData.asn_id}
                        onChange={(e) => setFormData({ ...formData, asn_id: parseInt(e.target.value) })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
                        required
                      >
                        <option value="0">-- Pilih ASN --</option>
                        {asnList.map((asn) => (
                          <option key={asn.id} value={asn.id}>
                            {asn.name} ({asn.nip}) - {asn.unit?.nama_unit || '-'}
                          </option>
                        ))}
                      </select>
                    </div>

                    {/* Atasan Selection */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Atasan Langsung <span className="text-red-500">*</span>
                      </label>
                      <select
                        value={formData.atasan_id}
                        onChange={(e) => setFormData({ ...formData, atasan_id: parseInt(e.target.value) })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
                        required
                      >
                        <option value="0">-- Pilih Atasan --</option>
                        {atasanList.map((atasan) => (
                          <option key={atasan.id} value={atasan.id}>
                            {atasan.name} ({atasan.nip}) - {atasan.unit?.nama_unit || '-'}
                          </option>
                        ))}
                      </select>
                    </div>

                    {/* Tahun */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Tahun <span className="text-red-500">*</span>
                      </label>
                      <input
                        type="number"
                        min="2020"
                        max="2100"
                        value={formData.tahun}
                        onChange={(e) => setFormData({ ...formData, tahun: parseInt(e.target.value) })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900"
                        required
                      />
                    </div>

                    {/* Status */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Status
                      </label>
                      <select
                        value={formData.status}
                        onChange={(e) => setFormData({ ...formData, status: e.target.value as 'AKTIF' | 'NONAKTIF' })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
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
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Filter Tahun
                </label>
                <input
                  type="number"
                  value={filterTahun}
                  onChange={(e) => setFilterTahun(parseInt(e.target.value))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Filter Status
                </label>
                <select
                  value={filterStatus}
                  onChange={(e) => setFilterStatus(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
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
                    ASN / PPPK
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Atasan Langsung
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Tahun
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Aksi
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {masterAtasanList.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-6 py-8 text-center text-gray-500">
                      Belum ada data master atasan
                    </td>
                  </tr>
                ) : (
                  masterAtasanList.map((item, index) => (
                    <tr key={item.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {index + 1}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        <div className="font-medium">{item.asn?.name || '-'}</div>
                        <div className="text-gray-500">{item.asn?.nip || '-'}</div>
                        <div className="text-gray-400 text-xs">{item.asn?.unit?.nama_unit || '-'}</div>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        <div className="font-medium">{item.atasan?.name || '-'}</div>
                        <div className="text-gray-500">{item.atasan?.nip || '-'}</div>
                        <div className="text-gray-400 text-xs">{item.atasan?.unit?.nama_unit || '-'}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {item.tahun}
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
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <button
                          onClick={() => handleEdit(item)}
                          className="text-blue-600 hover:text-blue-900"
                        >
                          Edit
                        </button>
                        <button
                          onClick={() => handleDelete(item.id)}
                          className="text-red-600 hover:text-red-900"
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
