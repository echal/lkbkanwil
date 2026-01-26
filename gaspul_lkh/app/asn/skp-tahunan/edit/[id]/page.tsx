'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { getSingleSkpTahunanDetail, updateSkpTahunanDetail, type SkpTahunan, type SkpTahunanDetail } from '@/app/lib/skp-tahunan-api';
import { getSasaranKegiatanByUnitKerja, getIndikatorKinerjaBySasaran, type SasaranKegiatan, type IndikatorKinerja } from '@/app/lib/master-kinerja-api';

export default function EditSkpTahunanDetailPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const detailId = parseInt(params.id as string);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [header, setHeader] = useState<SkpTahunan | null>(null);
  const [detail, setDetail] = useState<SkpTahunanDetail | null>(null);
  const [unitKerja, setUnitKerja] = useState<string>('');
  const [sasaranList, setSasaranList] = useState<SasaranKegiatan[]>([]);
  const [indikatorList, setIndikatorList] = useState<IndikatorKinerja[]>([]);
  const [error, setError] = useState('');

  const [formData, setFormData] = useState({
    sasaran_kegiatan_id: 0,
    indikator_kinerja_id: 0,
    target_tahunan: 0,
    satuan: '',
    rencana_aksi: '',
  });

  useEffect(() => {
    if (authLoading) return;
    if (!isAuthenticated || user?.role !== 'ASN') {
      router.push('/login');
      return;
    }
    loadData();
  }, [authLoading, isAuthenticated, user, detailId]);

  const loadData = async () => {
    try {
      setLoading(true);
      setError('');

      // Load master data first
      const sasaranResponse = await getSasaranKegiatanByUnitKerja();
      setUnitKerja(sasaranResponse.unit_kerja);
      setSasaranList(sasaranResponse.data);

      // Load the specific detail data
      const detailData = await getSingleSkpTahunanDetail(detailId);
      setDetail(detailData);

      // Set form data with loaded values
      setFormData({
        sasaran_kegiatan_id: detailData.sasaran_kegiatan_id,
        indikator_kinerja_id: detailData.indikator_kinerja_id,
        target_tahunan: detailData.target_tahunan,
        satuan: detailData.satuan,
        rencana_aksi: detailData.rencana_aksi || '',
      });

      // Load indikator list for the selected sasaran
      if (detailData.sasaran_kegiatan_id) {
        const indikatorData = await getIndikatorKinerjaBySasaran(detailData.sasaran_kegiatan_id);
        setIndikatorList(indikatorData);
      }

      // Store header reference from the detail's relationship
      if (detailData.skpTahunan) {
        setHeader(detailData.skpTahunan);
      }

    } catch (error: any) {
      console.error('Failed to load data:', error);
      setError(error.message || 'Gagal memuat data');
      setTimeout(() => router.push('/asn/skp-tahunan'), 2000);
    } finally {
      setLoading(false);
    }
  };

  const handleSasaranChange = async (sasaranId: number) => {
    setFormData({ ...formData, sasaran_kegiatan_id: sasaranId, indikator_kinerja_id: 0 });

    if (!sasaranId) {
      setIndikatorList([]);
      return;
    }

    try {
      const data = await getIndikatorKinerjaBySasaran(sasaranId);
      setIndikatorList(data);
    } catch (error: any) {
      console.error('Failed to load indikator:', error);
      alert(error.message || 'Gagal memuat Indikator Kinerja');
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      setSaving(true);
      await updateSkpTahunanDetail(detailId, formData);
      alert('Butir kinerja berhasil diupdate');
      router.push('/asn/skp-tahunan');
    } catch (error: any) {
      alert(error.message || 'Gagal mengupdate butir kinerja');
    } finally {
      setSaving(false);
    }
  };

  if (authLoading || loading) {
    return (
      <div className="min-h-screen bg-gray-50 p-8 flex items-center justify-center">
        <div className="text-center">
          <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
          <p className="text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-4xl mx-auto">
          <div className="bg-red-50 border-l-4 border-red-500 p-4 mb-4">
            <p className="text-red-700">{error}</p>
          </div>
          <button
            onClick={() => router.push('/asn/skp-tahunan')}
            className="px-6 py-2 border border-gray-300 rounded hover:bg-gray-50"
          >
            Kembali ke Daftar SKP
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-4xl mx-auto">
        <div className="mb-6">
          <div className="flex items-center gap-4 mb-4">
            <button
              onClick={() => router.push('/asn/skp-tahunan')}
              className="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
            >
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
              </svg>
              <span className="font-medium">Kembali ke Daftar SKP</span>
            </button>
          </div>

          <h1 className="text-3xl font-bold text-gray-800 mb-2">Edit Butir Kinerja</h1>

          {/* Info Box */}
          <div className="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
            <p className="text-sm text-blue-700">
              <strong>Note:</strong> Anda dapat mengedit butir kinerja jika SKP Tahunan masih berstatus DRAFT atau DITOLAK.
            </p>
          </div>

          {/* Unit Kerja Info */}
          {unitKerja && (
            <div className="bg-green-50 border-l-4 border-green-500 p-4 mb-4">
              <p className="text-sm text-green-700">
                <strong>Unit Kerja Anda:</strong> {unitKerja}
              </p>
            </div>
          )}
        </div>

        <div className="bg-white rounded-lg shadow p-6">
          <form onSubmit={handleSubmit}>
            {/* Sasaran Kegiatan */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Sasaran Kegiatan <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.sasaran_kegiatan_id}
                onChange={(e) => handleSasaranChange(parseInt(e.target.value))}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
                required
              >
                <option value="" className="text-gray-500">-- Pilih Sasaran Kegiatan --</option>
                {sasaranList.map((sasaran) => (
                  <option key={sasaran.id} value={sasaran.id} className="text-gray-900">
                    {sasaran.sasaran_kegiatan}
                  </option>
                ))}
              </select>
            </div>

            {/* Indikator Kinerja */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Indikator Kinerja <span className="text-red-500">*</span>
              </label>
              <select
                value={formData.indikator_kinerja_id}
                onChange={(e) => setFormData({ ...formData, indikator_kinerja_id: parseInt(e.target.value) })}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white disabled:bg-gray-100 disabled:text-gray-500"
                required
                disabled={!formData.sasaran_kegiatan_id}
              >
                <option value="" className="text-gray-500">-- Pilih Indikator Kinerja --</option>
                {indikatorList.map((indikator) => (
                  <option key={indikator.id} value={indikator.id} className="text-gray-900">
                    {indikator.indikator_kinerja}
                  </option>
                ))}
              </select>
              {!formData.sasaran_kegiatan_id && (
                <p className="text-xs text-gray-500 mt-1">Pilih Sasaran Kegiatan terlebih dahulu</p>
              )}
            </div>

            {/* Target Tahunan & Satuan */}
            <div className="grid grid-cols-2 gap-6 mb-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Target Tahunan <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  min="0"
                  value={formData.target_tahunan}
                  onChange={(e) => setFormData({ ...formData, target_tahunan: parseInt(e.target.value) })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Satuan <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  placeholder="Contoh: %, Dokumen, Laporan"
                  value={formData.satuan}
                  onChange={(e) => setFormData({ ...formData, satuan: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white placeholder:text-gray-400"
                  required
                />
              </div>
            </div>

            {/* Rencana Aksi */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Rencana Aksi Tahunan
              </label>
              <textarea
                value={formData.rencana_aksi}
                onChange={(e) => setFormData({ ...formData, rencana_aksi: e.target.value })}
                rows={5}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white placeholder:text-gray-400"
                placeholder="Deskripsikan rencana aksi untuk mencapai target tahunan..."
              />
            </div>

            {/* Actions */}
            <div className="flex gap-4">
              <button
                type="submit"
                disabled={saving}
                className="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400"
              >
                {saving ? 'Menyimpan...' : 'Update Butir Kinerja'}
              </button>
              <button
                type="button"
                onClick={() => router.push('/asn/skp-tahunan')}
                className="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-gray-700"
              >
                Batal
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
