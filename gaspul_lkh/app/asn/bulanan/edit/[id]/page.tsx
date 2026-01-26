'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { getBulananDetail, updateBulanan, Bulanan } from '@/app/lib/bulanan-api';

export default function EditBulananPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const id = params?.id as string;

  const [bulanan, setBulanan] = useState<Bulanan | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Form state
  const [formData, setFormData] = useState({
    target_bulanan: 0,
    rencana_kerja_bulanan: '',
  });

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

    loadBulananDetail();
  }, [user, isAuthenticated, authLoading, router, id]);

  const loadBulananDetail = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getBulananDetail(parseInt(id));
      setBulanan(data);

      // Pre-fill form if target already set
      setFormData({
        target_bulanan: data.target_bulanan || 0,
        rencana_kerja_bulanan: data.rencana_kerja_bulanan || '',
      });
    } catch (err: any) {
      setError(err.message || 'Gagal memuat detail Bulanan');
      console.error('Failed to load Bulanan:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.target_bulanan || formData.target_bulanan <= 0) {
      alert('Target Bulanan harus diisi dan lebih dari 0');
      return;
    }

    if (!formData.rencana_kerja_bulanan.trim()) {
      alert('Rencana Kerja Bulanan harus diisi');
      return;
    }

    try {
      setSaving(true);
      await updateBulanan(parseInt(id), formData);
      alert('Target dan Rencana Kerja Bulanan berhasil disimpan!');
      router.push('/asn/bulanan');
    } catch (err: any) {
      alert(err.message || 'Gagal menyimpan Bulanan');
    } finally {
      setSaving(false);
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

  if (!isAuthenticated || user?.role !== 'ASN') {
    return null;
  }

  if (error || !bulanan) {
    return (
      <div className="p-8">
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {error || 'Bulanan tidak ditemukan'}
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top Navigation Bar */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => router.push('/asn/bulanan')}
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
                {bulanan.has_target_filled ? 'Edit' : 'Isi'} Target Bulanan
              </h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-3xl mx-auto">
          {/* Header Card */}
          <div className="bg-white rounded-lg shadow p-6 mb-6">
            <h2 className="text-2xl font-bold text-gray-800">
              {bulanan.bulan_nama} {bulanan.tahun}
            </h2>
            <div className="mt-4 space-y-2">
              <div className="text-sm text-gray-600">
                <span className="font-medium">SKP Triwulan:</span> Triwulan{' '}
                {bulanan.rencana_kerja_asn?.triwulan} / {bulanan.tahun}
              </div>
              <div className="text-sm text-gray-600">
                <span className="font-medium">Sasaran:</span>{' '}
                {bulanan.rencana_kerja_asn?.sasaran_kegiatan?.sasaran_kegiatan}
              </div>
              <div className="text-sm text-gray-600">
                <span className="font-medium">Indikator:</span>{' '}
                {bulanan.rencana_kerja_asn?.indikator_kinerja?.indikator_kinerja}
              </div>
              <div className="text-sm text-gray-600">
                <span className="font-medium">Target SKP Triwulan:</span>{' '}
                {bulanan.rencana_kerja_asn?.target}{' '}
                {bulanan.rencana_kerja_asn?.satuan}
              </div>
            </div>
          </div>

          {/* Info Box */}
          <div className="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
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
                <p className="text-sm text-blue-700">
                  Setelah target bulanan diisi, Anda bisa mulai input kegiatan Harian.
                  Target bulanan sebaiknya merupakan pembagian dari target triwulan.
                </p>
              </div>
            </div>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6">
            <div className="space-y-6">
              <div>
                <label
                  htmlFor="target_bulanan"
                  className="block text-sm font-medium text-gray-700 mb-2"
                >
                  Target Bulanan <span className="text-red-500">*</span>
                </label>
                <div className="flex items-center space-x-2">
                  <input
                    type="number"
                    id="target_bulanan"
                    min="0"
                    step="1"
                    value={formData.target_bulanan}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        target_bulanan: parseInt(e.target.value) || 0,
                      })
                    }
                    className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900"
                    required
                  />
                  <span className="text-gray-700 font-medium">
                    {bulanan.rencana_kerja_asn?.satuan}
                  </span>
                </div>
                <p className="mt-1 text-xs text-gray-500">
                  Masukkan target yang ingin dicapai pada bulan ini
                </p>
              </div>

              <div>
                <label
                  htmlFor="rencana_kerja_bulanan"
                  className="block text-sm font-medium text-gray-700 mb-2"
                >
                  Rencana Kerja Bulanan <span className="text-red-500">*</span>
                </label>
                <textarea
                  id="rencana_kerja_bulanan"
                  rows={6}
                  value={formData.rencana_kerja_bulanan}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      rencana_kerja_bulanan: e.target.value,
                    })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 placeholder:text-gray-400"
                  placeholder="Jelaskan rencana kerja yang akan dilakukan pada bulan ini..."
                  required
                />
                <p className="mt-1 text-xs text-gray-500">
                  Uraikan kegiatan atau langkah-langkah yang akan dilakukan
                </p>
              </div>

              {/* Current Realisasi Info */}
              {bulanan.has_target_filled && (
                <div className="bg-gray-50 rounded-lg p-4">
                  <h4 className="text-sm font-medium text-gray-700 mb-2">
                    Realisasi Saat Ini
                  </h4>
                  <div className="flex items-center justify-between">
                    <span className="text-2xl font-bold text-gray-900">
                      {bulanan.realisasi_bulanan}{' '}
                      <span className="text-base text-gray-600">
                        {bulanan.rencana_kerja_asn?.satuan}
                      </span>
                    </span>
                    <span
                      className={`px-3 py-1 text-sm font-semibold rounded-full ${
                        (bulanan.capaian_persen || 0) >= 90
                          ? 'bg-green-100 text-green-800'
                          : (bulanan.capaian_persen || 0) >= 70
                          ? 'bg-yellow-100 text-yellow-800'
                          : 'bg-red-100 text-red-800'
                      }`}
                    >
                      {bulanan.capaian_persen || 0}%
                    </span>
                  </div>
                  <p className="mt-2 text-xs text-gray-500">
                    Realisasi otomatis terhitung dari kegiatan Harian
                  </p>
                </div>
              )}
            </div>

            {/* Buttons */}
            <div className="mt-6 flex space-x-3">
              <button
                type="button"
                onClick={() => router.push('/asn/bulanan')}
                className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
              >
                Batal
              </button>
              <button
                type="submit"
                disabled={saving}
                className="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed font-medium"
              >
                {saving ? 'Menyimpan...' : 'Simpan Target'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
