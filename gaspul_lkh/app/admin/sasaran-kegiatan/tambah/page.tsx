'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { createSasaranKegiatan } from '@/app/lib/master-kinerja-api';
import { getUnitList, type Unit } from '@/app/lib/unit-api';

export default function TambahSasaranKegiatanPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const [formData, setFormData] = useState({
    unit_kerja: '',
    sasaran_kegiatan: '',
    status: 'AKTIF' as 'AKTIF' | 'NONAKTIF',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [units, setUnits] = useState<Unit[]>([]);
  const [loadingUnits, setLoadingUnits] = useState(true);

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

    loadUnits();
  }, [user, isAuthenticated, authLoading, router]);

  const loadUnits = async () => {
    try {
      setLoadingUnits(true);
      const data = await getUnitList();
      setUnits(data.filter(u => u.status === 'AKTIF'));
    } catch (error: any) {
      console.error('Failed to load units:', error);
      setError('Gagal memuat data unit kerja');
    } finally {
      setLoadingUnits(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    // Validasi
    if (!formData.unit_kerja.trim()) {
      setError('Unit Kerja harus diisi');
      return;
    }

    if (!formData.sasaran_kegiatan.trim()) {
      setError('Sasaran Kegiatan harus diisi');
      return;
    }

    try {
      setLoading(true);
      setError(null);

      await createSasaranKegiatan(formData);

      alert('Sasaran Kegiatan berhasil ditambahkan!');
      router.push('/admin/sasaran-kegiatan');
    } catch (err: any) {
      setError(err.message || 'Gagal menambahkan Sasaran Kegiatan');
      console.error('Failed to create Sasaran Kegiatan:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    if (confirm('Batalkan dan kembali? Data yang diisi akan hilang.')) {
      router.push('/admin/sasaran-kegiatan');
    }
  };

  // Loading auth
  if (authLoading) {
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
                Tambah Sasaran Kegiatan
              </h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-3xl mx-auto">
          {/* Form Card */}
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-2xl font-bold text-gray-800 mb-6">
              Form Tambah Sasaran Kegiatan
            </h2>

            {/* Error message */}
            {error && (
              <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Unit Kerja */}
              <div>
                <label
                  htmlFor="unit_kerja"
                  className="block text-sm font-medium text-gray-700 mb-2"
                >
                  Unit Kerja <span className="text-red-500">*</span>
                </label>
                {loadingUnits ? (
                  <div className="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                    Memuat data unit kerja...
                  </div>
                ) : (
                  <select
                    id="unit_kerja"
                    name="unit_kerja"
                    value={formData.unit_kerja}
                    onChange={(e) =>
                      setFormData({ ...formData, unit_kerja: e.target.value })
                    }
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
                    required
                  >
                    <option value="" className="text-gray-500">-- Pilih Unit Kerja --</option>
                    {units.map((unit) => (
                      <option key={unit.id} value={unit.nama_unit} className="text-gray-900">
                        {unit.nama_unit} ({unit.kode_unit})
                      </option>
                    ))}
                  </select>
                )}
                <p className="mt-2 text-sm text-gray-500">
                  {units.length === 0 && !loadingUnits
                    ? 'Belum ada unit kerja aktif. Silakan buat unit kerja terlebih dahulu di menu Master Unit Kerja.'
                    : 'Pilih unit kerja dari daftar yang tersedia'}
                </p>
              </div>

              {/* Sasaran Kegiatan */}
              <div>
                <label
                  htmlFor="sasaran_kegiatan"
                  className="block text-sm font-medium text-gray-700 mb-2"
                >
                  Sasaran Kegiatan <span className="text-red-500">*</span>
                </label>
                <textarea
                  id="sasaran_kegiatan"
                  name="sasaran_kegiatan"
                  rows={5}
                  value={formData.sasaran_kegiatan}
                  onChange={(e) =>
                    setFormData({ ...formData, sasaran_kegiatan: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 placeholder:text-gray-400"
                  placeholder="Contoh: Meningkatnya jaminan beragama, toleransi, dan cinta kemanusiaan umat beragama"
                  required
                />
                <p className="mt-2 text-sm text-gray-500">
                  Sasaran strategis organisasi sesuai PERKIN
                </p>
              </div>

              {/* Status */}
              <div>
                <label
                  htmlFor="status"
                  className="block text-sm font-medium text-gray-700 mb-2"
                >
                  Status <span className="text-red-500">*</span>
                </label>
                <select
                  id="status"
                  name="status"
                  value={formData.status}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      status: e.target.value as 'AKTIF' | 'NONAKTIF',
                    })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900"
                >
                  <option value="AKTIF">Aktif</option>
                  <option value="NONAKTIF">Nonaktif</option>
                </select>
                <p className="mt-2 text-sm text-gray-500">
                  Sasaran dengan status Aktif akan muncul di form ASN
                </p>
              </div>

              {/* Info Box */}
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
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
                      Catatan
                    </h3>
                    <div className="mt-2 text-sm text-blue-700">
                      <ul className="list-disc list-inside space-y-1">
                        <li>Sasaran Kegiatan adalah master data level organisasi</li>
                        <li>Setelah membuat sasaran, tambahkan Indikator Kinerja terkait</li>
                        <li>ASN akan memilih sasaran ini saat membuat SKP</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="flex justify-end space-x-3 pt-4">
                <button
                  type="button"
                  onClick={handleCancel}
                  className="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
                  disabled={loading}
                >
                  Batal
                </button>
                <button
                  type="submit"
                  className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                  disabled={loading}
                >
                  {loading ? 'Menyimpan...' : 'Simpan'}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}
