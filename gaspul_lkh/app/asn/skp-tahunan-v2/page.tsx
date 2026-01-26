'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { skpTahunanV2Api, SkpTahunan, SkpTahunanDetail, RhkPimpinan } from '@/app/lib/api-v2';

export default function SkpTahunanV2Page() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();

  const [skpList, setSkpList] = useState<SkpTahunan[]>([]);
  const [rhkList, setRhkList] = useState<RhkPimpinan[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Form state
  const [showForm, setShowForm] = useState(false);
  const [selectedSkpId, setSelectedSkpId] = useState<number | null>(null);
  const [formData, setFormData] = useState({
    rhk_pimpinan_id: 0,
    target_tahunan: 0,
    satuan: '',
    rencana_aksi: '',
  });

  // Filter state
  const [filterTahun, setFilterTahun] = useState<number>(new Date().getFullYear());
  const [filterStatus, setFilterStatus] = useState<string>('');

  // Expand state
  const [expandedHeaders, setExpandedHeaders] = useState<Set<number>>(new Set());

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

    loadData();
  }, [user, isAuthenticated, authLoading, router, filterTahun, filterStatus]);

  const loadData = async () => {
    try {
      setLoading(true);
      setError(null);

      // Load SKP Tahunan with filters
      const params: any = {};
      if (filterTahun) params.tahun = filterTahun;
      if (filterStatus) params.status = filterStatus;

      const skpData = await skpTahunanV2Api.getAll(params);
      setSkpList(skpData);

      // Load active RHK Pimpinan for dropdown (ASN access)
      const rhkData = await skpTahunanV2Api.getActiveRhk();
      setRhkList(rhkData);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat data');
      console.error('Failed to load data:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateOrGetHeader = async (tahun: number) => {
    try {
      setSaving(true);
      const response = await skpTahunanV2Api.createOrGet(tahun);

      // Set selected SKP and show form
      setSelectedSkpId(response.data.id);
      setShowForm(true);

      // Reload data to show the header
      loadData();
    } catch (err: any) {
      alert(err.message || 'Gagal membuat SKP Tahunan');
    } finally {
      setSaving(false);
    }
  };

  const handleAddDetail = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!selectedSkpId) {
      alert('Pilih SKP Tahunan terlebih dahulu');
      return;
    }

    if (formData.rhk_pimpinan_id === 0) {
      alert('Pilih RHK Pimpinan terlebih dahulu');
      return;
    }

    if (!formData.rencana_aksi.trim()) {
      alert('Rencana Aksi tidak boleh kosong');
      return;
    }

    try {
      setSaving(true);
      const response = await skpTahunanV2Api.addDetail(selectedSkpId, formData);
      alert(response.message || 'Butir kinerja berhasil ditambahkan! 12 periode bulanan telah dibuat.');

      setShowForm(false);
      setSelectedSkpId(null);
      resetForm();
      loadData();
    } catch (err: any) {
      alert(err.message || 'Gagal menambahkan butir kinerja');
    } finally {
      setSaving(false);
    }
  };

  const handleDeleteDetail = async (skpId: number, detailId: number) => {
    if (!confirm('Yakin ingin menghapus butir kinerja ini? 12 periode bulanan juga akan dihapus.')) return;

    try {
      await skpTahunanV2Api.deleteDetail(skpId, detailId);
      alert('Butir kinerja berhasil dihapus!');
      loadData();
    } catch (err: any) {
      alert(err.message || 'Gagal menghapus butir kinerja');
    }
  };

  const handleSubmit = async (skpId: number) => {
    const skp = skpList.find(s => s.id === skpId);

    if (!skp?.can_be_submitted) {
      alert('SKP tidak dapat diajukan. Pastikan sudah ada minimal 1 butir kinerja.');
      return;
    }

    if (!confirm('Yakin ingin mengajukan SKP Tahunan ini untuk persetujuan?')) return;

    try {
      await skpTahunanV2Api.submit(skpId);
      alert('SKP Tahunan berhasil diajukan untuk persetujuan!');
      loadData();
    } catch (err: any) {
      alert(err.message || 'Gagal mengajukan SKP Tahunan');
    }
  };

  const resetForm = () => {
    setFormData({
      rhk_pimpinan_id: 0,
      target_tahunan: 0,
      satuan: '',
      rencana_aksi: '',
    });
  };

  const handleCancel = () => {
    setShowForm(false);
    setSelectedSkpId(null);
    resetForm();
  };

  const toggleExpand = (headerId: number) => {
    const newExpanded = new Set(expandedHeaders);
    if (newExpanded.has(headerId)) {
      newExpanded.delete(headerId);
    } else {
      newExpanded.add(headerId);
    }
    setExpandedHeaders(newExpanded);
  };

  const getStatusBadgeColor = (status: string) => {
    switch (status) {
      case 'DRAFT':
        return 'bg-gray-500 text-white';
      case 'DIAJUKAN':
        return 'bg-blue-500 text-white';
      case 'DISETUJUI':
        return 'bg-green-500 text-white';
      case 'DITOLAK':
        return 'bg-red-500 text-white';
      default:
        return 'bg-gray-400 text-white';
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

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => router.push('/asn/dashboard')}
                className="text-gray-600 hover:text-gray-900 transition"
                title="Kembali"
              >
                <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <h1 className="text-2xl font-semibold text-gray-900">SKP Tahunan V2</h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-7xl mx-auto">
          {/* Info Box */}
          <div className="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <p className="text-sm text-blue-700">
                  <strong>Sistem Baru:</strong> Pilih RHK Pimpinan yang sudah ditetapkan oleh atasan. Anda dapat memilih RHK yang sama berkali-kali dengan Rencana Aksi yang berbeda. Setiap butir kinerja akan otomatis dibuatkan 12 periode bulanan.
                </p>
              </div>
            </div>
          </div>

          {/* Error message */}
          {error && (
            <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
              {error}
            </div>
          )}

          {/* Form Modal */}
          {showForm && (
            <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
              <div className="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div className="p-6">
                  <h2 className="text-xl font-semibold text-gray-900 mb-6">
                    Tambah Butir Kinerja
                  </h2>

                  <form onSubmit={handleAddDetail} className="space-y-6">
                    {/* RHK Pimpinan Selection */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        RHK Pimpinan <span className="text-red-500">*</span>
                      </label>
                      <select
                        value={formData.rhk_pimpinan_id}
                        onChange={(e) => setFormData({ ...formData, rhk_pimpinan_id: parseInt(e.target.value) })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal bg-white"
                        required
                      >
                        <option value="0">-- Pilih RHK Pimpinan --</option>
                        {rhkList.map((rhk) => (
                          <option key={rhk.id} value={rhk.id}>
                            {rhk.rhk_pimpinan}
                            {rhk.sasaran_kegiatan && ` - ${rhk.sasaran_kegiatan.unit_kerja}`}
                          </option>
                        ))}
                      </select>
                      <p className="mt-1 text-sm text-gray-500">
                        RHK (Rencana Hasil Kerja) yang sudah ditetapkan oleh atasan
                      </p>
                    </div>

                    {/* Rencana Aksi */}
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Rencana Aksi <span className="text-red-500">*</span>
                      </label>
                      <textarea
                        value={formData.rencana_aksi}
                        onChange={(e) => setFormData({ ...formData, rencana_aksi: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                        rows={3}
                        placeholder="Contoh: Melakukan maintenance jaringan internet di 5 kantor cabang"
                        required
                      />
                      <p className="mt-1 text-sm text-gray-500">
                        Rencana aksi spesifik Anda untuk mencapai RHK Pimpinan tersebut. Field ini membedakan jika Anda memilih RHK yang sama.
                      </p>
                    </div>

                    {/* Target Tahunan */}
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Target Tahunan <span className="text-red-500">*</span>
                        </label>
                        <input
                          type="number"
                          min="0"
                          step="0.01"
                          value={formData.target_tahunan}
                          onChange={(e) => setFormData({ ...formData, target_tahunan: parseFloat(e.target.value) || 0 })}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                          required
                        />
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                          Satuan <span className="text-red-500">*</span>
                        </label>
                        <input
                          type="text"
                          value={formData.satuan}
                          onChange={(e) => setFormData({ ...formData, satuan: e.target.value })}
                          className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                          placeholder="Contoh: Laporan, Kegiatan, Unit"
                          required
                        />
                      </div>
                    </div>

                    {/* Buttons */}
                    <div className="flex space-x-3 pt-4">
                      <button
                        type="button"
                        onClick={handleCancel}
                        className="flex-1 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition font-medium"
                      >
                        Batal
                      </button>
                      <button
                        type="submit"
                        disabled={saving}
                        className="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed font-medium"
                      >
                        {saving ? 'Menyimpan...' : 'Simpan'}
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          )}

          {/* Filters and Actions */}
          <div className="bg-white rounded-lg shadow p-6 mb-6">
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
              {/* Filters */}
              <div className="flex flex-col sm:flex-row gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Filter Tahun
                  </label>
                  <input
                    type="number"
                    value={filterTahun}
                    onChange={(e) => setFilterTahun(parseInt(e.target.value))}
                    className="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Filter Status
                  </label>
                  <select
                    value={filterStatus}
                    onChange={(e) => setFilterStatus(e.target.value)}
                    className="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
                  >
                    <option value="">Semua Status</option>
                    <option value="DRAFT">DRAFT</option>
                    <option value="DIAJUKAN">DIAJUKAN</option>
                    <option value="DISETUJUI">DISETUJUI</option>
                    <option value="DITOLAK">DITOLAK</option>
                  </select>
                </div>
              </div>

              {/* Action Button */}
              <button
                onClick={() => handleCreateOrGetHeader(new Date().getFullYear())}
                disabled={saving}
                className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium disabled:bg-gray-400"
              >
                + Buat SKP Tahun {new Date().getFullYear()}
              </button>
            </div>
          </div>

          {/* SKP List */}
          <div className="space-y-4">
            {skpList.length === 0 ? (
              <div className="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                Belum ada SKP Tahunan. Klik tombol "Buat SKP" untuk memulai.
              </div>
            ) : (
              skpList.map((skp) => (
                <div key={skp.id} className="bg-white rounded-lg shadow overflow-hidden">
                  {/* Header */}
                  <div className="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <div className="flex justify-between items-start">
                      <div className="flex-1">
                        <div className="flex items-center gap-3">
                          <h3 className="text-lg font-semibold text-gray-900">
                            SKP Tahunan {skp.tahun}
                          </h3>
                          <span className={`px-3 py-1 rounded-full text-xs font-semibold ${getStatusBadgeColor(skp.status)}`}>
                            {skp.status}
                          </span>
                        </div>
                        <div className="mt-2 text-sm text-gray-600 space-y-1">
                          <p>Total Butir Kinerja: <strong>{skp.total_butir_kinerja || 0}</strong></p>
                          {skp.capaian_persen !== undefined && (
                            <p>Capaian: <strong>{skp.capaian_persen.toFixed(2)}%</strong></p>
                          )}
                          {skp.approver && (
                            <p>Disetujui oleh: <strong>{skp.approver.name}</strong></p>
                          )}
                          {skp.catatan_atasan && (
                            <p className="text-red-600">Catatan: {skp.catatan_atasan}</p>
                          )}
                        </div>
                      </div>

                      <div className="flex gap-2">
                        {skp.can_add_details && (
                          <button
                            onClick={() => {
                              setSelectedSkpId(skp.id);
                              setShowForm(true);
                            }}
                            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium"
                          >
                            + Tambah Butir Kinerja
                          </button>
                        )}
                        {skp.can_be_submitted && (
                          <button
                            onClick={() => handleSubmit(skp.id)}
                            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium"
                          >
                            Ajukan
                          </button>
                        )}
                        <button
                          onClick={() => toggleExpand(skp.id)}
                          className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm font-medium"
                        >
                          {expandedHeaders.has(skp.id) ? 'Tutup' : 'Lihat Detail'}
                        </button>
                      </div>
                    </div>
                  </div>

                  {/* Details */}
                  {expandedHeaders.has(skp.id) && skp.details && (
                    <div className="p-6">
                      <h4 className="text-md font-semibold text-gray-900 mb-4">Butir Kinerja</h4>

                      {skp.details.length === 0 ? (
                        <p className="text-gray-500 text-center py-4">Belum ada butir kinerja</p>
                      ) : (
                        <div className="space-y-4">
                          {skp.details.map((detail, index) => (
                            <div key={detail.id} className="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                              <div className="flex justify-between items-start">
                                <div className="flex-1">
                                  <div className="flex items-start gap-3">
                                    <span className="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-semibold text-sm">
                                      {index + 1}
                                    </span>
                                    <div className="flex-1">
                                      <p className="font-medium text-gray-900 mb-1">
                                        {detail.rhk_pimpinan?.rhk_pimpinan || '-'}
                                      </p>
                                      <p className="text-sm text-gray-600 mb-2">
                                        <strong>Rencana Aksi:</strong> {detail.rencana_aksi}
                                      </p>
                                      <div className="grid grid-cols-3 gap-4 text-sm">
                                        <div>
                                          <span className="text-gray-500">Target:</span>
                                          <p className="font-medium">{detail.target_tahunan} {detail.satuan}</p>
                                        </div>
                                        <div>
                                          <span className="text-gray-500">Realisasi:</span>
                                          <p className="font-medium">{detail.realisasi_tahunan} {detail.satuan}</p>
                                        </div>
                                        <div>
                                          <span className="text-gray-500">Capaian:</span>
                                          <p className="font-medium">{detail.capaian_persen?.toFixed(2) || 0}%</p>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>

                                {skp.can_edit_details && (
                                  <div className="flex gap-2 ml-4">
                                    <button
                                      onClick={() => handleDeleteDetail(skp.id, detail.id)}
                                      className="text-red-600 hover:text-red-900 text-sm font-medium"
                                    >
                                      Hapus
                                    </button>
                                  </div>
                                )}
                              </div>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>
                  )}
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
