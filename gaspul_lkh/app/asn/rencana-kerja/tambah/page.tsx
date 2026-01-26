'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { createRencanaKerja } from '@/app/lib/rencana-kerja-api';
import { getApprovedSkpTahunanList, type SkpTahunan } from '@/app/lib/skp-tahunan-api';

export default function TambahRencanaKerjaPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();

  const [formData, setFormData] = useState({
    skp_tahunan_id: 0,
    skp_tahunan_detail_id: 0,
    triwulan: 'I' as 'I' | 'II' | 'III' | 'IV',
    target: 0,
    satuan: '',
    catatan_asn: '',
  });

  const [skpTahunanList, setSkpTahunanList] = useState<SkpTahunan[]>([]);
  const [selectedSkp, setSelectedSkp] = useState<SkpTahunan | null>(null);
  const [loadingMaster, setLoadingMaster] = useState(true);
  const [loading, setLoading] = useState(false);
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

    loadMasterData();
  }, [user, isAuthenticated, authLoading, router]);

  const loadMasterData = async () => {
    try {
      setLoadingMaster(true);
      const data = await getApprovedSkpTahunanList();
      setSkpTahunanList(data);

      if (data.length === 0) {
        setError('Anda belum memiliki SKP Tahunan yang disetujui. Silakan buat dan ajukan SKP Tahunan terlebih dahulu.');
      }
    } catch (err: any) {
      setError(err.message || 'Gagal memuat SKP Tahunan');
      console.error('Failed to load SKP Tahunan:', err);
    } finally {
      setLoadingMaster(false);
    }
  };

  useEffect(() => {
    if (formData.skp_tahunan_id > 0) {
      const skp = skpTahunanList.find(s => s.id === formData.skp_tahunan_id);
      setSelectedSkp(skp || null);

      // Reset detail selection when SKP changes
      setFormData(prev => ({
        ...prev,
        skp_tahunan_detail_id: 0,
        satuan: ''
      }));
    } else {
      setSelectedSkp(null);
      setFormData(prev => ({
        ...prev,
        skp_tahunan_detail_id: 0,
        satuan: ''
      }));
    }
  }, [formData.skp_tahunan_id, skpTahunanList]);

  // Update satuan when detail is selected
  useEffect(() => {
    if (formData.skp_tahunan_detail_id > 0 && selectedSkp?.details) {
      const detail = selectedSkp.details.find(d => d.id === formData.skp_tahunan_detail_id);
      if (detail) {
        setFormData(prev => ({
          ...prev,
          satuan: detail.satuan || ''
        }));
      }
    }
  }, [formData.skp_tahunan_detail_id, selectedSkp]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (formData.skp_tahunan_id === 0) {
      setError('SKP Tahunan harus dipilih');
      return;
    }

    if (formData.skp_tahunan_detail_id === 0) {
      setError('Butir Kinerja harus dipilih');
      return;
    }

    if (formData.target <= 0) {
      setError('Target harus lebih besar dari 0');
      return;
    }

    if (!formData.satuan || !formData.satuan.trim()) {
      setError('Satuan harus diisi');
      return;
    }

    try {
      setLoading(true);
      setError(null);

      await createRencanaKerja(formData);

      alert('SKP Triwulan berhasil ditambahkan!');
      router.push('/asn/rencana-kerja');
    } catch (err: any) {
      setError(err.message || 'Gagal menambahkan SKP Triwulan');
      console.error('Failed to create SKP Triwulan:', err);
    } finally {
      setLoading(false);
    }
  };

  if (authLoading || loadingMaster) {
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

  return (
    <div className="min-h-screen bg-gray-50">
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
                Tambah SKP Triwulan
              </h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-3xl mx-auto">
          {/* PESAN PENTING */}
          <div className="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <p className="text-sm text-blue-700">
                  <strong>💡 SKP Triwulan merupakan turunan dari SKP Tahunan.</strong><br/>
                  Pilih SKP Tahunan yang sudah disetujui, lalu tentukan target untuk triwulan yang dipilih.
                  Sasaran, Indikator, dan Tahun akan otomatis mengikuti SKP Tahunan parent.
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-2xl font-bold text-gray-800 mb-6">
              Form Tambah SKP Triwulan
            </h2>

            {error && (
              <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                {error}
                {skpTahunanList.length === 0 && (
                  <div className="mt-2">
                    <button
                      onClick={() => router.push('/asn/skp-tahunan/tambah')}
                      className="text-sm underline hover:no-underline"
                    >
                      → Buat SKP Tahunan Sekarang
                    </button>
                  </div>
                )}
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-6">
              {/* SKP TAHUNAN DROPDOWN */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Pilih SKP Tahunan Anda <span className="text-red-500">*</span>
                </label>
                <select
                  value={formData.skp_tahunan_id}
                  onChange={(e) => setFormData({ ...formData, skp_tahunan_id: parseInt(e.target.value) })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
                  required
                >
                  <option value="0" className="text-gray-500">-- Pilih SKP Tahunan --</option>
                  {skpTahunanList.map((skp) => (
                    <option key={skp.id} value={skp.id} className="text-gray-900">
                      {skp.display_name || `${skp.sasaran_kegiatan?.sasaran_kegiatan} (${skp.tahun})`}
                    </option>
                  ))}
                </select>
                <p className="mt-2 text-sm text-gray-500">
                  Hanya menampilkan SKP Tahunan yang sudah DISETUJUI oleh Atasan
                </p>
              </div>

              {/* BUTIR KINERJA DROPDOWN */}
              {selectedSkp && selectedSkp.details && selectedSkp.details.length > 0 && (
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Pilih Butir Kinerja <span className="text-red-500">*</span>
                  </label>
                  <select
                    value={formData.skp_tahunan_detail_id}
                    onChange={(e) => setFormData({ ...formData, skp_tahunan_detail_id: parseInt(e.target.value) })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
                    required
                  >
                    <option value="0" className="text-gray-500">-- Pilih Butir Kinerja --</option>
                    {selectedSkp.details.map((detail, index) => (
                      <option key={detail.id} value={detail.id} className="text-gray-900">
                        #{index + 1}: {detail.sasaran_kegiatan?.sasaran_kegiatan || '-'} → {detail.indikator_kinerja?.indikator_kinerja || '-'} ({detail.target_tahunan} {detail.satuan})
                      </option>
                    ))}
                  </select>
                  <p className="mt-2 text-sm text-gray-500">
                    Pilih butir kinerja yang ingin Anda buat rencana triwulannya
                  </p>
                </div>
              )}

              {/* INFO BUTIR KINERJA TERPILIH */}
              {selectedSkp && formData.skp_tahunan_detail_id > 0 && (() => {
                const selectedDetail = selectedSkp.details?.find(d => d.id === formData.skp_tahunan_detail_id);
                if (!selectedDetail) return null;

                return (
                  <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 className="font-semibold text-gray-900 mb-3">Butir Kinerja Terpilih:</h3>
                    <div className="space-y-2 text-sm">
                      <div className="flex">
                        <span className="w-40 text-gray-700 font-medium">Tahun:</span>
                        <span className="flex-1 font-medium text-gray-900">{selectedSkp.tahun}</span>
                      </div>
                      <div className="flex">
                        <span className="w-40 text-gray-700 font-medium">Sasaran Kegiatan:</span>
                        <span className="flex-1 font-medium text-gray-900">{selectedDetail.sasaran_kegiatan?.sasaran_kegiatan || '-'}</span>
                      </div>
                      <div className="flex">
                        <span className="w-40 text-gray-700 font-medium">Indikator Kinerja:</span>
                        <span className="flex-1 font-medium text-gray-900">{selectedDetail.indikator_kinerja?.indikator_kinerja || '-'}</span>
                      </div>
                      <div className="flex">
                        <span className="w-40 text-gray-700 font-medium">Target Tahunan:</span>
                        <span className="flex-1 font-medium text-gray-900">{selectedDetail.target_tahunan} {selectedDetail.satuan}</span>
                      </div>
                    </div>
                  </div>
                );
              })()}

              {/* TRIWULAN */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Triwulan <span className="text-red-500">*</span>
                </label>
                <select
                  value={formData.triwulan}
                  onChange={(e) => setFormData({ ...formData, triwulan: e.target.value as any })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
                  required
                >
                  <option value="I" className="text-gray-900">Triwulan I (Jan-Mar)</option>
                  <option value="II" className="text-gray-900">Triwulan II (Apr-Jun)</option>
                  <option value="III" className="text-gray-900">Triwulan III (Jul-Sep)</option>
                  <option value="IV" className="text-gray-900">Triwulan IV (Okt-Des)</option>
                </select>
              </div>

              {/* TARGET & SATUAN */}
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
                  {selectedSkp && formData.skp_tahunan_detail_id > 0 && (() => {
                    const selectedDetail = selectedSkp.details?.find(d => d.id === formData.skp_tahunan_detail_id);
                    if (!selectedDetail) return null;
                    return (
                      <p className="mt-1 text-xs text-gray-500">
                        Target tahunan: {selectedDetail.target_tahunan} {selectedDetail.satuan}
                      </p>
                    );
                  })()}
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Satuan <span className="text-red-500">*</span>
                  </label>
                  <input
                    type="text"
                    value={formData.satuan || ''}
                    onChange={(e) => setFormData({ ...formData, satuan: e.target.value })}
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 text-gray-900"
                    required
                    readOnly={!!selectedSkp}
                  />
                  <p className="mt-1 text-xs text-gray-500">
                    Otomatis dari SKP Tahunan
                  </p>
                </div>
              </div>

              {/* CATATAN */}
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

              {/* ACTIONS */}
              <div className="flex gap-4 pt-4">
                <button
                  type="submit"
                  disabled={loading || skpTahunanList.length === 0}
                  className="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400"
                >
                  {loading ? 'Menyimpan...' : 'Simpan SKP Triwulan'}
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
        </div>
      </div>
    </div>
  );
}
