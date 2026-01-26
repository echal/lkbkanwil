'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { getUnitList, deleteUnit, toggleUnitStatus, type Unit } from '@/app/lib/unit-api';

export default function MasterUnitPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const [units, setUnits] = useState<Unit[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');

  useEffect(() => {
    if (authLoading) return;
    if (!isAuthenticated || user?.role !== 'ADMIN') {
      router.push('/login');
      return;
    }
    loadUnits();
  }, [authLoading, isAuthenticated, user]);

  const loadUnits = async () => {
    try {
      setLoading(true);
      const data = await getUnitList();
      setUnits(data);
    } catch (error: any) {
      alert(error.message || 'Gagal memuat data Unit Kerja');
    } finally {
      setLoading(false);
    }
  };

  const handleToggleStatus = async (id: number) => {
    if (!confirm('Apakah Anda yakin ingin mengubah status Unit Kerja ini?')) return;

    try {
      await toggleUnitStatus(id);
      alert('Status Unit Kerja berhasil diubah');
      loadUnits();
    } catch (error: any) {
      alert(error.message || 'Gagal mengubah status Unit Kerja');
    }
  };

  const handleDelete = async (id: number, namaUnit: string) => {
    if (!confirm(`Apakah Anda yakin ingin menghapus Unit Kerja "${namaUnit}"?\n\nPeringatan: Unit yang sedang digunakan oleh pegawai tidak dapat dihapus.`)) return;

    try {
      await deleteUnit(id);
      alert('Unit Kerja berhasil dihapus');
      loadUnits();
    } catch (error: any) {
      alert(error.message || 'Gagal menghapus Unit Kerja');
    }
  };

  const filteredUnits = units.filter(unit =>
    unit.nama_unit.toLowerCase().includes(searchTerm.toLowerCase()) ||
    unit.kode_unit.toLowerCase().includes(searchTerm.toLowerCase())
  );

  if (authLoading || loading) {
    return (
      <div className="p-8">
        <div className="animate-pulse">
          <div className="h-8 bg-gray-200 rounded w-1/4 mb-4"></div>
          <div className="h-64 bg-gray-200 rounded"></div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h1 className="text-3xl font-bold text-gray-800">Master Unit Kerja</h1>
              <p className="text-gray-600 mt-1">Kelola data unit kerja organisasi</p>
            </div>
            <button
              onClick={() => router.push('/admin/units/tambah')}
              className="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"
            >
              + Tambah Unit Kerja
            </button>
          </div>

          <button
            onClick={() => router.push('/admin/dashboard')}
            className="text-blue-600 hover:text-blue-700 flex items-center gap-2"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Dashboard
          </button>
        </div>

        {/* Search */}
        <div className="bg-white rounded-lg shadow p-4 mb-6">
          <input
            type="text"
            placeholder="Cari unit kerja atau kode unit..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
          />
        </div>

        {/* Table */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          {filteredUnits.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-gray-500 text-lg">
                {searchTerm ? 'Tidak ada unit kerja yang sesuai dengan pencarian' : 'Belum ada data Unit Kerja'}
              </p>
              {!searchTerm && (
                <button
                  onClick={() => router.push('/admin/units/tambah')}
                  className="mt-4 text-blue-600 hover:text-blue-700"
                >
                  + Tambah Unit Kerja Pertama
                </button>
              )}
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Kode Unit
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Nama Unit Kerja
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Jumlah Pegawai
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
                  {filteredUnits.map((unit) => (
                    <tr key={unit.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className="text-sm font-mono font-semibold text-gray-900">{unit.kode_unit}</span>
                      </td>
                      <td className="px-6 py-4">
                        <div className="text-sm font-medium text-gray-900">{unit.nama_unit}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <span className="text-sm text-gray-900">{unit.jumlah_pegawai} pegawai</span>
                          {unit.digunakan_pegawai && (
                            <span className="ml-2 text-xs text-orange-600">● Digunakan</span>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span
                          className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            unit.status === 'AKTIF'
                              ? 'bg-green-100 text-green-800'
                              : 'bg-gray-100 text-gray-800'
                          }`}
                        >
                          {unit.status}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm">
                        <div className="flex gap-2">
                          <button
                            onClick={() => router.push(`/admin/units/edit/${unit.id}`)}
                            className="text-blue-600 hover:text-blue-900"
                          >
                            Edit
                          </button>
                          <button
                            onClick={() => handleToggleStatus(unit.id)}
                            className="text-yellow-600 hover:text-yellow-900"
                          >
                            {unit.status === 'AKTIF' ? 'Nonaktifkan' : 'Aktifkan'}
                          </button>
                          {!unit.digunakan_pegawai && (
                            <button
                              onClick={() => handleDelete(unit.id, unit.nama_unit)}
                              className="text-red-600 hover:text-red-900"
                            >
                              Hapus
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>

        {/* Info */}
        <div className="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
          <p className="text-sm text-blue-700">
            <strong>💡 Informasi:</strong> Unit Kerja yang sedang digunakan oleh pegawai tidak dapat dihapus.
            Anda hanya dapat mengubah statusnya atau mengedit datanya.
          </p>
        </div>
      </div>
    </div>
  );
}
