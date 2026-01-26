'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { createUnit } from '@/app/lib/unit-api';

export default function TambahUnitPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    nama_unit: '',
    kode_unit: '',
    status: 'AKTIF' as 'AKTIF' | 'NONAKTIF',
  });

  useEffect(() => {
    if (authLoading) return;
    if (!isAuthenticated || user?.role !== 'ADMIN') {
      router.push('/login');
      return;
    }
  }, [authLoading, isAuthenticated, user]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.nama_unit.trim()) {
      alert('Nama Unit Kerja harus diisi');
      return;
    }

    if (!formData.kode_unit.trim()) {
      alert('Kode Unit harus diisi');
      return;
    }

    try {
      setLoading(true);
      await createUnit(formData);
      alert('Unit Kerja berhasil ditambahkan');
      router.push('/admin/units');
    } catch (error: any) {
      alert(error.message || 'Gagal menambahkan Unit Kerja');
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
          <h1 className="text-3xl font-bold text-gray-800 mb-2">Tambah Unit Kerja</h1>
          <button
            onClick={() => router.push('/admin/units')}
            className="text-blue-600 hover:text-blue-700 flex items-center gap-2"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Daftar Unit Kerja
          </button>
        </div>

        {/* Form */}
        <div className="bg-white rounded-lg shadow p-6">
          <form onSubmit={handleSubmit}>
            {/* Nama Unit Kerja */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Nama Unit Kerja <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.nama_unit}
                onChange={(e) => setFormData({ ...formData, nama_unit: e.target.value })}
                placeholder="Contoh: Dinas Pendidikan dan Kebudayaan"
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
                required
              />
            </div>

            {/* Kode Unit */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Kode Unit <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                value={formData.kode_unit}
                onChange={(e) => setFormData({ ...formData, kode_unit: e.target.value.toUpperCase() })}
                placeholder="Contoh: DISDIKBUD"
                maxLength={20}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white font-mono"
                required
              />
              <p className="text-xs text-gray-500 mt-1">Maksimal 20 karakter. Kode akan otomatis kapital.</p>
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
                disabled={loading}
                className="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400"
              >
                {loading ? 'Menyimpan...' : 'Simpan Unit Kerja'}
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
            <strong>💡 Informasi:</strong> Unit Kerja yang sudah dibuat dapat digunakan sebagai referensi
            saat menambahkan pegawai (ASN, Atasan, Admin) ke dalam sistem.
          </p>
        </div>
      </div>
    </div>
  );
}
