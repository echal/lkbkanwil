'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { getUserList, deleteUser, toggleUserStatus, resetUserPassword, type UserManagement } from '@/app/lib/user-management-api';
import { getUnitList, type Unit } from '@/app/lib/unit-api';

export default function MasterPegawaiPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const [users, setUsers] = useState<UserManagement[]>([]);
  const [units, setUnits] = useState<Unit[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterRole, setFilterRole] = useState('');
  const [filterUnit, setFilterUnit] = useState('');
  const [filterStatus, setFilterStatus] = useState('');

  useEffect(() => {
    if (authLoading) return;
    if (!isAuthenticated || user?.role !== 'ADMIN') {
      router.push('/login');
      return;
    }
    loadData();
  }, [authLoading, isAuthenticated, user]);

  const loadData = async () => {
    try {
      setLoading(true);
      const [usersData, unitsData] = await Promise.all([
        getUserList(),
        getUnitList(),
      ]);
      setUsers(usersData);
      setUnits(unitsData);
    } catch (error: any) {
      alert(error.message || 'Gagal memuat data');
    } finally {
      setLoading(false);
    }
  };

  const handleToggleStatus = async (id: number) => {
    if (!confirm('Apakah Anda yakin ingin mengubah status pegawai ini?')) return;

    try {
      await toggleUserStatus(id);
      alert('Status pegawai berhasil diubah');
      loadData();
    } catch (error: any) {
      alert(error.message || 'Gagal mengubah status pegawai');
    }
  };

  const handleResetPassword = async (id: number, name: string) => {
    const password = prompt(`Masukkan password baru untuk ${name}:\n(Minimal 8 karakter)`);
    if (!password) return;

    if (password.length < 8) {
      alert('Password minimal 8 karakter');
      return;
    }

    try {
      await resetUserPassword(id, { password });
      alert('Password berhasil direset');
    } catch (error: any) {
      alert(error.message || 'Gagal mereset password');
    }
  };

  const handleDelete = async (id: number, name: string) => {
    if (!confirm(`Apakah Anda yakin ingin menghapus pegawai "${name}"?\n\nPeringatan: Data yang sudah dihapus tidak dapat dikembalikan.`)) return;

    try {
      await deleteUser(id);
      alert('Pegawai berhasil dihapus');
      loadData();
    } catch (error: any) {
      alert(error.message || 'Gagal menghapus pegawai');
    }
  };

  const filteredUsers = users.filter(u => {
    const matchSearch = u.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        u.nip.toLowerCase().includes(searchTerm.toLowerCase()) ||
                        u.email.toLowerCase().includes(searchTerm.toLowerCase());
    const matchRole = !filterRole || u.role === filterRole;
    const matchUnit = !filterUnit || u.unit_id?.toString() === filterUnit;
    const matchStatus = !filterStatus || u.status === filterStatus;

    return matchSearch && matchRole && matchUnit && matchStatus;
  });

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
              <h1 className="text-3xl font-bold text-gray-800">Master Pegawai</h1>
              <p className="text-gray-600 mt-1">Kelola data pegawai (ASN, Atasan, Admin)</p>
            </div>
            <button
              onClick={() => router.push('/admin/users/tambah')}
              className="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition"
            >
              + Tambah Pegawai
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

        {/* Filters */}
        <div className="bg-white rounded-lg shadow p-4 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            {/* Search */}
            <input
              type="text"
              placeholder="Cari nama, NIP, atau email..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
            />

            {/* Filter Role */}
            <select
              value={filterRole}
              onChange={(e) => setFilterRole(e.target.value)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
            >
              <option value="" className="text-gray-500">Semua Role</option>
              <option value="ASN" className="text-gray-900">ASN</option>
              <option value="ATASAN" className="text-gray-900">Atasan</option>
              <option value="ADMIN" className="text-gray-900">Admin</option>
            </select>

            {/* Filter Unit */}
            <select
              value={filterUnit}
              onChange={(e) => setFilterUnit(e.target.value)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
            >
              <option value="" className="text-gray-500">Semua Unit</option>
              {units.map((unit) => (
                <option key={unit.id} value={unit.id} className="text-gray-900">{unit.nama_unit}</option>
              ))}
            </select>

            {/* Filter Status */}
            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
              className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
            >
              <option value="" className="text-gray-500">Semua Status</option>
              <option value="AKTIF" className="text-gray-900">AKTIF</option>
              <option value="NONAKTIF" className="text-gray-900">NONAKTIF</option>
            </select>
          </div>
        </div>

        {/* Table */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          {filteredUsers.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-gray-500 text-lg">
                {searchTerm || filterRole || filterUnit || filterStatus
                  ? 'Tidak ada pegawai yang sesuai dengan filter'
                  : 'Belum ada data Pegawai'}
              </p>
              {!searchTerm && !filterRole && !filterUnit && !filterStatus && (
                <button
                  onClick={() => router.push('/admin/users/tambah')}
                  className="mt-4 text-blue-600 hover:text-blue-700"
                >
                  + Tambah Pegawai Pertama
                </button>
              )}
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Pegawai
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      NIP
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Unit Kerja
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Role
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
                  {filteredUsers.map((pegawai) => (
                    <tr key={pegawai.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4">
                        <div className="text-sm font-medium text-gray-900">{pegawai.name}</div>
                        <div className="text-sm text-gray-500">{pegawai.email}</div>
                        {pegawai.jabatan && (
                          <div className="text-xs text-gray-500 mt-1">{pegawai.jabatan}</div>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className="text-sm font-mono text-gray-900">{pegawai.nip}</span>
                      </td>
                      <td className="px-6 py-4">
                        <div className="text-sm text-gray-900">{pegawai.unit_name || '-'}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span
                          className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            pegawai.role === 'ADMIN'
                              ? 'bg-purple-100 text-purple-800'
                              : pegawai.role === 'ATASAN'
                              ? 'bg-orange-100 text-orange-800'
                              : 'bg-blue-100 text-blue-800'
                          }`}
                        >
                          {pegawai.role}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span
                          className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                            pegawai.status === 'AKTIF'
                              ? 'bg-green-100 text-green-800'
                              : 'bg-gray-100 text-gray-800'
                          }`}
                        >
                          {pegawai.status}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm">
                        <div className="flex flex-col gap-1">
                          <div className="flex gap-2">
                            <button
                              onClick={() => router.push(`/admin/users/edit/${pegawai.id}`)}
                              className="text-blue-600 hover:text-blue-900"
                            >
                              Edit
                            </button>
                            <button
                              onClick={() => handleResetPassword(pegawai.id, pegawai.name)}
                              className="text-green-600 hover:text-green-900"
                            >
                              Reset PW
                            </button>
                          </div>
                          <div className="flex gap-2">
                            <button
                              onClick={() => handleToggleStatus(pegawai.id)}
                              className="text-yellow-600 hover:text-yellow-900"
                            >
                              {pegawai.status === 'AKTIF' ? 'Nonaktifkan' : 'Aktifkan'}
                            </button>
                            {pegawai.id !== user?.id && (
                              <button
                                onClick={() => handleDelete(pegawai.id, pegawai.name)}
                                className="text-red-600 hover:text-red-900"
                              >
                                Hapus
                              </button>
                            )}
                          </div>
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
            <strong>💡 Informasi:</strong> Anda tidak dapat menghapus akun Anda sendiri.
            Data pegawai yang dihapus tidak dapat dikembalikan.
          </p>
        </div>
      </div>
    </div>
  );
}
