'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { createUser } from '@/app/lib/user-management-api';
import { getUnitList, type Unit } from '@/app/lib/unit-api';

export default function TambahPegawaiPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [units, setUnits] = useState<Unit[]>([]);
  const [formData, setFormData] = useState({
    name: '',
    nip: '',
    email: '',
    password: '',
    role: 'ASN' as 'ASN' | 'ATASAN' | 'ADMIN',
    unit_id: 0,
    jabatan: '',
    status: 'AKTIF' as 'AKTIF' | 'NONAKTIF',
  });

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
      const data = await getUnitList();
      setUnits(data.filter(u => u.status === 'AKTIF'));
    } catch (error: any) {
      alert(error.message || 'Gagal memuat data unit kerja');
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.name.trim()) {
      alert('Nama harus diisi');
      return;
    }

    if (!formData.nip.trim()) {
      alert('NIP harus diisi');
      return;
    }

    if (!formData.email.trim()) {
      alert('Email harus diisi');
      return;
    }

    if (formData.password.length < 8) {
      alert('Password minimal 8 karakter');
      return;
    }

    if (!formData.unit_id) {
      alert('Unit Kerja harus dipilih');
      return;
    }

    try {
      setLoading(true);
      await createUser(formData);
      alert('Pegawai berhasil ditambahkan');
      router.push('/admin/users');
    } catch (error: any) {
      alert(error.message || 'Gagal menambahkan pegawai');
    } finally {
      setLoading(false);
    }
  };

  if (authLoading) {
    return <div className="p-8">Loading...</div>;
  }

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-3xl mx-auto">
        {/* Header */}
        <div className="mb-6">
          <h1 className="text-3xl font-bold text-gray-800 mb-2">Tambah Pegawai</h1>
          <button
            onClick={() => router.push('/admin/users')}
            className="text-blue-600 hover:text-blue-700 flex items-center gap-2"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Daftar Pegawai
          </button>
        </div>

        {/* Form */}
        <div className="bg-white rounded-lg shadow p-6">
          <form onSubmit={handleSubmit}>
            {/* Nama */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Nama Lengkap <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                placeholder="Contoh: Ahmad Hidayat, S.Kom"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
                required
              />
            </div>

            {/* NIP */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                NIP <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.nip}
                onChange={(e) => setFormData({ ...formData, nip: e.target.value })}
                placeholder="Contoh: 198501012010011001"
                maxLength={18}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white font-mono"
                required
              />
            </div>

            {/* Email */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Email <span className="text-red-500">*</span>
              </label>
              <input
                type="email"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                placeholder="Contoh: ahmad.hidayat@pemda.go.id"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
                required
              />
            </div>

            {/* Password */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Password <span className="text-red-500">*</span>
              </label>
              <input
                type="password"
                value={formData.password}
                onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                placeholder="Minimal 8 karakter"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
                required
                minLength={8}
              />
              <p className="text-xs text-gray-500 mt-1">Password dapat direset oleh Admin kapan saja.</p>
            </div>

            {/* Role */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Role <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.role}
                onChange={(e) => setFormData({ ...formData, role: e.target.value as any })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
                required
              >
                <option value="ASN" className="text-gray-900">ASN</option>
                <option value="ATASAN" className="text-gray-900">Atasan</option>
                <option value="ADMIN" className="text-gray-900">Admin</option>
              </select>
              <p className="text-xs text-gray-500 mt-1">
                ASN = Pegawai biasa | Atasan = Pejabat penilai | Admin = Administrator sistem
              </p>
            </div>

            {/* Unit Kerja */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Unit Kerja <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.unit_id}
                onChange={(e) => setFormData({ ...formData, unit_id: parseInt(e.target.value) })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
                required
              >
                <option value="" className="text-gray-500">-- Pilih Unit Kerja --</option>
                {units.map((unit) => (
                  <option key={unit.id} value={unit.id} className="text-gray-900">
                    {unit.nama_unit} ({unit.kode_unit})
                  </option>
                ))}
              </select>
              {units.length === 0 && (
                <p className="text-xs text-red-500 mt-1">
                  Belum ada unit kerja aktif. Silakan buat unit kerja terlebih dahulu.
                </p>
              )}
            </div>

            {/* Jabatan */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Jabatan
              </label>
              <input
                type="text"
                value={formData.jabatan}
                onChange={(e) => setFormData({ ...formData, jabatan: e.target.value })}
                placeholder="Contoh: Kepala Seksi Pengembangan Aplikasi"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
              />
            </div>

            {/* Status */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Status <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value as 'AKTIF' | 'NONAKTIF' })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
                required
              >
                <option value="AKTIF" className="text-gray-900">AKTIF</option>
                <option value="NONAKTIF" className="text-gray-900">NONAKTIF</option>
              </select>
            </div>

            {/* Actions */}
            <div className="flex gap-4">
              <button
                type="submit"
                disabled={loading || units.length === 0}
                className="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400"
              >
                {loading ? 'Menyimpan...' : 'Simpan Pegawai'}
              </button>
              <button
                type="button"
                onClick={() => router.back()}
                className="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
              >
                Batal
              </button>
            </div>
          </form>
        </div>

        {/* Info */}
        <div className="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
          <p className="text-sm text-blue-700">
            <strong>💡 Informasi:</strong> Profil pegawai hanya dapat dikelola oleh Administrator.
            Pegawai tidak dapat mengubah data profil mereka sendiri (READ ONLY).
          </p>
        </div>
      </div>
    </div>
  );
}
