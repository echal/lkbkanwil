'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { getRencanaKerjaById, updateRencanaKerja, type RencanaKerjaAsn } from '@/app/lib/rencana-kerja-api';

export default function EditRencanaKerjaPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const id = params?.id as string;

  const [rencana, setRencana] = useState<RencanaKerjaAsn | null>(null);
  const [formData, setFormData] = useState({
    target: 0,
    satuan: '',
    catatan_asn: '',
  });

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

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

    loadRencanaKerja();
  }, [user, isAuthenticated, authLoading, router, id]);

  const loadRencanaKerja = async () => {
    try {
      setLoading(true);
      const data = await getRencanaKerjaById(parseInt(id));

      // Check if can be edited (DRAFT or DITOLAK)
      if (data.status !== 'DRAFT' && data.status !== 'DITOLAK') {
        setError('Hanya SKP Triwulan dengan status DRAFT atau DITOLAK yang dapat diedit');
        setTimeout(() => router.push('/asn/rencana-kerja'), 2000);
        return;
      }

      setRencana(data);
      setFormData({
        target: data.target,
        satuan: data.satuan,
        catatan_asn: data.catatan_asn || '',
      });
    } catch (err: any) {
      setError(err.message || 'Gagal memuat Rencana Kerja');
      console.error('Failed to load Rencana Kerja:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (formData.target <= 0) {
      setError('Target harus lebih besar dari 0');
      return;
    }

    if (!formData.satuan || !formData.satuan.trim()) {
      setError('Satuan harus diisi');
      return;
    }

    try {
      setSaving(true);
      setError(null);

      await updateRencanaKerja(parseInt(id), {
        sasaran_kegiatan_id: rencana!.sasaran_kegiatan_id,
        indikator_kinerja_id: rencana!.indikator_kinerja_id,
        tahun: rencana!.tahun,
        triwulan: rencana!.triwulan,
        target: formData.target,
        satuan: formData.satuan,
        catatan_asn: formData.catatan_asn,
      });

      alert('SKP Triwulan berhasil diperbarui!');
      router.push('/asn/rencana-kerja');
    } catch (err: any) {
      setError(err.message || 'Gagal memperbarui SKP Triwulan');
      console.error('Failed to update SKP Triwulan:', err);
    } finally {
      setSaving(false);
    }
  };

  if (authLoading || loading) {
    return (
      <div className="min-h-screen bg-gray-50 p-8">
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

  if (error && !rencana) {
    return (
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-3xl mx-auto">
          <div className="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
            <p className="text-red-700">{error}</p>
          </div>
          <button
            onClick={() => router.push('/asn/rencana-kerja')}
            className="px-6 py-2 border border-gray-300 rounded hover:bg-gray-50"
          >
            Kembali ke Daftar SKP Triwulan
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => router.push('/asn/rencana-kerja')}
                className="text-gray-600 hover:text-gray-900"
              >
                <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <h1 className="text-xl font-semibold text-gray-900">
                Edit SKP Triwulan
              </h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-3xl mx-auto">
          {/* Info Box */}
          <div className="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <p className="text-sm text-blue-700">
                  Anda hanya dapat mengedit <strong>Target, Satuan, dan Catatan</strong>. Butir Kinerja ditentukan dari SKP Tahunan yang dipilih.
                </p>
              </div>
            </div>
          </div>

          {/* Informasi SKP yang tidak bisa diubah */}
          {rencana && (
            <div className="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
              <h3 className="font-semibold text-gray-900 mb-3">Informasi SKP Triwulan:</h3>
              <div className="space-y-2 text-sm">
                <div className="flex">
                  <span className="w-32 text-gray-600">Tahun:</span>
                  <span className="flex-1 font-medium text-gray-900">{rencana.tahun}</span>
                </div>
                <div className="flex">
                  <span className="w-32 text-gray-600">Triwulan:</span>
                  <span className="flex-1 font-medium text-gray-900">Triwulan {rencana.triwulan}</span>
                </div>
                <div className="flex">
                  <span className="w-32 text-gray-600">Sasaran:</span>
                  <span className="flex-1 font-medium text-gray-900">
                    {rencana.skp_tahunan_detail?.sasaran_kegiatan?.sasaran_kegiatan || rencana.sasaran_kegiatan?.sasaran_kegiatan || '-'}
                  </span>
                </div>
                <div className="flex">
                  <span className="w-32 text-gray-600">Indikator:</span>
                  <span className="flex-1 font-medium text-gray-900">
                    {rencana.skp_tahunan_detail?.indikator_kinerja?.indikator_kinerja || rencana.indikator_kinerja?.indikator_kinerja || '-'}
                  </span>
                </div>
                <div className="flex">
                  <span className="w-32 text-gray-600">Status:</span>
                  <span className="flex-1">
                    <span className={`px-2 py-1 text-xs rounded-full ${
                      rencana.status === 'DRAFT' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800'
                    }`}>
                      {rencana.status}
                    </span>
                  </span>
                </div>
              </div>
            </div>
          )}

          {/* Form Card */}
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-2xl font-bold text-gray-800 mb-6">
              Form Edit SKP Triwulan
            </h2>

            {error && (
              <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Target & Satuan */}
              <div className="grid grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Target Triwulan <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    value={formData.target}
                    onChange={(e) => setFormData({ ...formData, target: parseFloat(e.target.value) })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
                    required
                  />
                  {rencana?.skp_tahunan_detail && (
                    <p className="mt-1 text-xs text-gray-500">
                      Target tahunan: {rencana.skp_tahunan_detail.target_tahunan} {rencana.skp_tahunan_detail.satuan}
                    </p>
                  )}
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Satuan <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={formData.satuan || ''}
                    onChange={(e) => setFormData({ ...formData, satuan: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white placeholder:text-gray-400"
                    placeholder="Contoh: %, Dokumen"
                    required
                  />
                </div>
              </div>

              {/* Catatan */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Catatan ASN
                </label>
                <textarea
                  value={formData.catatan_asn || ''}
                  onChange={(e) => setFormData({ ...formData, catatan_asn: e.target.value })}
                  rows={4}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white placeholder:text-gray-400"
                  placeholder="Catatan tambahan (opsional)"
                />
              </div>

              {/* Actions */}
              <div className="flex gap-4 pt-4">
                <button
                  type="submit"
                  disabled={saving}
                  className="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400"
                >
                  {saving ? 'Menyimpan...' : 'Simpan Perubahan'}
                </button>
                <button
                  type="button"
                  onClick={() => router.push('/asn/rencana-kerja')}
                  className="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                >
                  Batal
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
}
