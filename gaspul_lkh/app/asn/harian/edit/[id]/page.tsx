'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { getHarianDetail, updateHarian, Harian } from '@/app/lib/harian-api';

export default function EditHarianPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const id = parseInt(params.id as string);

  const [harian, setHarian] = useState<Harian | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Form state
  const [formData, setFormData] = useState({
    tanggal: '',
    kegiatan_harian: '',
    progres: 0,
    satuan: '',
    waktu_kerja: 0,
    bukti_type: 'link' as 'file' | 'link',
    bukti_file: null as File | null,
    bukti_link: '',
    keterangan: '',
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

    loadHarianDetail();
  }, [user, isAuthenticated, authLoading, router]);

  const loadHarianDetail = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getHarianDetail(id);
      setHarian(data);

      // Populate form with existing data
      setFormData({
        tanggal: data.tanggal,
        kegiatan_harian: data.kegiatan_harian,
        progres: data.progres,
        satuan: data.satuan,
        waktu_kerja: data.waktu_kerja || 0,
        bukti_type: data.bukti_type,
        bukti_file: null, // File will not be pre-populated
        bukti_link: data.bukti_link || '',
        keterangan: data.keterangan || '',
      });
    } catch (err: any) {
      setError(err.message || 'Gagal memuat detail Harian');
      console.error('Failed to load Harian detail:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    // Validation
    if (!formData.kegiatan_harian.trim()) {
      alert('Kegiatan Harian harus diisi');
      return;
    }

    if (formData.progres <= 0) {
      alert('Progres harus lebih dari 0');
      return;
    }

    if (formData.bukti_type === 'file' && !formData.bukti_file && !harian?.bukti_file) {
      alert('File bukti harus diupload');
      return;
    }

    if (formData.bukti_type === 'link' && !formData.bukti_link.trim()) {
      alert('Link bukti harus diisi');
      return;
    }

    try {
      setSaving(true);
      await updateHarian(id, formData);
      alert('Kegiatan Harian berhasil diperbarui!');
      router.push('/asn/harian');
    } catch (err: any) {
      alert(err.message || 'Gagal memperbarui Harian');
    } finally {
      setSaving(false);
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      // Validate file size (max 10MB)
      if (file.size > 10 * 1024 * 1024) {
        alert('Ukuran file maksimal 10MB');
        e.target.value = '';
        return;
      }
      setFormData({ ...formData, bukti_file: file });
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

  if (error) {
    return (
      <div className="p-8">
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {error}
          <div className="mt-2">
            <button
              onClick={() => router.push('/asn/harian')}
              className="text-red-800 underline font-medium"
            >
              Kembali ke Daftar Harian →
            </button>
          </div>
        </div>
      </div>
    );
  }

  if (!harian) {
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
                onClick={() => router.push('/asn/harian')}
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
                Edit Kegiatan Harian
              </h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-3xl mx-auto">
          {/* Info Box - Read-only Bulanan Info */}
          <div className="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 className="text-sm font-medium text-blue-800 mb-2">
              Informasi Bulanan
            </h3>
            <div className="text-sm text-blue-700 space-y-1">
              <p>
                <span className="font-medium">Bulan:</span> {harian.bulanan?.bulan_nama}{' '}
                {harian.bulanan?.tahun}
              </p>
              <p>
                <span className="font-medium">Sasaran Kegiatan:</span>{' '}
                {harian.bulanan?.rencana_kerja_asn?.sasaran_kegiatan?.sasaran_kegiatan || '-'}
              </p>
              <p>
                <span className="font-medium">Indikator Kinerja:</span>{' '}
                {harian.bulanan?.rencana_kerja_asn?.indikator_kinerja?.indikator_kinerja || '-'}
              </p>
              <p>
                <span className="font-medium">Target Bulanan:</span>{' '}
                {harian.bulanan?.target_bulanan} {harian.bulanan?.rencana_kerja_asn?.satuan}
              </p>
            </div>
          </div>

          {/* Warning Box */}
          <div className="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg
                  className="h-5 w-5 text-yellow-400"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fillRule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clipRule="evenodd"
                  />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-yellow-800">
                  Bukti Wajib Dilampirkan
                </h3>
                <div className="mt-2 text-sm text-yellow-700">
                  <p>
                    Setiap kegiatan harian wajib disertai bukti berupa file (foto, dokumen, dll)
                    atau link eksternal. Tanpa bukti, data tidak dapat disimpan.
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Form */}
          <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6">
            <div className="space-y-6">
              {/* Tanggal */}
              <div>
                <label
                  htmlFor="tanggal"
                  className="block text-sm font-medium text-gray-700 mb-2"
                >
                  Tanggal Kegiatan <span className="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  id="tanggal"
                  value={formData.tanggal}
                  onChange={(e) =>
                    setFormData({ ...formData, tanggal: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900"
                  required
                />
                <p className="mt-1 text-xs text-gray-500">
                  Tanggal harus dalam bulan yang dipilih
                </p>
              </div>

              {/* Kegiatan Harian */}
              <div>
                <label
                  htmlFor="kegiatan_harian"
                  className="block text-sm font-medium text-gray-700 mb-2"
                >
                  Uraian Kegiatan <span className="text-red-500">*</span>
                </label>
                <textarea
                  id="kegiatan_harian"
                  rows={5}
                  value={formData.kegiatan_harian}
                  onChange={(e) =>
                    setFormData({ ...formData, kegiatan_harian: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 placeholder:text-gray-400"
                  placeholder="Jelaskan kegiatan yang dilakukan hari ini..."
                  required
                />
              </div>

              {/* Progres dan Satuan */}
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label
                    htmlFor="progres"
                    className="block text-sm font-medium text-gray-700 mb-2"
                  >
                    Progres <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="number"
                    id="progres"
                    min="0"
                    step="1"
                    value={formData.progres}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        progres: parseInt(e.target.value) || 0,
                      })
                    }
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900"
                    required
                  />
                </div>
                <div>
                  <label
                    htmlFor="satuan"
                    className="block text-sm font-medium text-gray-700 mb-2"
                  >
                    Satuan <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    id="satuan"
                    value={formData.satuan}
                    onChange={(e) =>
                      setFormData({ ...formData, satuan: e.target.value })
                    }
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900"
                    placeholder="dokumen, kegiatan, dll"
                    required
                  />
                </div>
              </div>

              {/* Waktu Kerja (Optional) */}
              <div>
                <label
                  htmlFor="waktu_kerja"
                  className="block text-sm font-medium text-gray-700 mb-2"
                >
                  Waktu Kerja (menit) <span className="text-gray-400">(Opsional)</span>
                </label>
                <input
                  type="number"
                  id="waktu_kerja"
                  min="0"
                  value={formData.waktu_kerja}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      waktu_kerja: parseInt(e.target.value) || 0,
                    })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900"
                />
              </div>

              {/* Bukti Type Selection */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Tipe Bukti <span className="text-red-500">*</span>
                </label>
                <div className="flex space-x-4">
                  <label className="flex items-center">
                    <input
                      type="radio"
                      name="bukti_type"
                      value="link"
                      checked={formData.bukti_type === 'link'}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          bukti_type: e.target.value as 'file' | 'link',
                        })
                      }
                      className="mr-2"
                    />
                    <span className="text-gray-700">Link</span>
                  </label>
                  <label className="flex items-center">
                    <input
                      type="radio"
                      name="bukti_type"
                      value="file"
                      checked={formData.bukti_type === 'file'}
                      onChange={(e) =>
                        setFormData({
                          ...formData,
                          bukti_type: e.target.value as 'file' | 'link',
                        })
                      }
                      className="mr-2"
                    />
                    <span className="text-gray-700">Upload File</span>
                  </label>
                </div>
              </div>

              {/* Bukti Input (conditional) */}
              {formData.bukti_type === 'link' ? (
                <div>
                  <label
                    htmlFor="bukti_link"
                    className="block text-sm font-medium text-gray-700 mb-2"
                  >
                    Link Bukti <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="url"
                    id="bukti_link"
                    value={formData.bukti_link}
                    onChange={(e) =>
                      setFormData({ ...formData, bukti_link: e.target.value })
                    }
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900"
                    placeholder="https://..."
                    required={formData.bukti_type === 'link'}
                  />
                  <p className="mt-1 text-xs text-gray-500">
                    Link ke Google Drive, foto online, atau dokumen lainnya
                  </p>
                </div>
              ) : (
                <div>
                  <label
                    htmlFor="bukti_file"
                    className="block text-sm font-medium text-gray-700 mb-2"
                  >
                    Upload File Bukti <span className="text-red-500">*</span>
                  </label>
                  {harian.bukti_file && (
                    <p className="mb-2 text-sm text-gray-600">
                      File saat ini:{' '}
                      <a
                        href={harian.bukti_file}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-blue-600 underline"
                      >
                        Lihat file
                      </a>
                    </p>
                  )}
                  <input
                    type="file"
                    id="bukti_file"
                    onChange={handleFileChange}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900"
                    accept="image/*,.pdf,.doc,.docx"
                  />
                  <p className="mt-1 text-xs text-gray-500">
                    Maksimal 10MB. Format: gambar, PDF, atau dokumen Word
                    {harian.bukti_file && ' (Biarkan kosong jika tidak ingin mengubah file)'}
                  </p>
                  {formData.bukti_file && (
                    <p className="mt-2 text-sm text-green-600">
                      File baru terpilih: {formData.bukti_file.name} (
                      {(formData.bukti_file.size / 1024 / 1024).toFixed(2)} MB)
                    </p>
                  )}
                </div>
              )}

              {/* Keterangan (Optional) */}
              <div>
                <label
                  htmlFor="keterangan"
                  className="block text-sm font-medium text-gray-700 mb-2"
                >
                  Keterangan Tambahan <span className="text-gray-400">(Opsional)</span>
                </label>
                <textarea
                  id="keterangan"
                  rows={3}
                  value={formData.keterangan}
                  onChange={(e) =>
                    setFormData({ ...formData, keterangan: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 placeholder:text-gray-400"
                  placeholder="Catatan atau informasi tambahan..."
                />
              </div>
            </div>

            {/* Buttons */}
            <div className="mt-6 flex space-x-3">
              <button
                type="button"
                onClick={() => router.push('/asn/harian')}
                className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
              >
                Batal
              </button>
              <button
                type="submit"
                disabled={saving}
                className="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed font-medium"
              >
                {saving ? 'Menyimpan...' : 'Simpan Perubahan'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
