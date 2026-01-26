'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { rencanaAksiBulananApi, RencanaAksiBulanan, skpTahunanV2Api, SkpTahunan } from '@/app/lib/api-v2';

export default function RencanaAksiBulananPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(true);
  const [skpList, setSkpList] = useState<SkpTahunan[]>([]);
  const [selectedSkp, setSelectedSkp] = useState<SkpTahunan | null>(null);
  const [selectedDetailId, setSelectedDetailId] = useState<number | null>(null);
  const [rencanaList, setRencanaList] = useState<RencanaAksiBulanan[]>([]);
  const [selectedRencana, setSelectedRencana] = useState<RencanaAksiBulanan | null>(null);
  const [showModal, setShowModal] = useState(false);
  const [formData, setFormData] = useState({
    rencana_aksi_bulanan: '',
    target_bulanan: 0,
    satuan_target: 'Dokumen'
  });
  const [error, setError] = useState<string | null>(null);

  const satuan_options = ['Dokumen', 'Data', 'Laporan', 'Kegiatan', 'Persentase', 'Berkas', 'Dokumentasi'];

  useEffect(() => {
    loadSkpTahunan();
  }, []);

  const loadSkpTahunan = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await skpTahunanV2Api.getAll();

      // Filter hanya SKP yang sudah DISETUJUI
      const approvedSkp = data.filter(skp => skp.status === 'DISETUJUI');
      setSkpList(approvedSkp);

      if (approvedSkp.length === 0) {
        setError('Anda belum memiliki SKP Tahunan yang disetujui. Silakan ajukan SKP Tahunan terlebih dahulu.');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to load SKP Tahunan');
    } finally {
      setLoading(false);
    }
  };

  const loadRencanaAksi = async (detailId: number) => {
    try {
      setLoading(true);
      setError(null);
      const data = await rencanaAksiBulananApi.getByDetail(detailId);
      setRencanaList(data);
      setSelectedDetailId(detailId);
    } catch (err: any) {
      setError(err.message || 'Failed to load rencana aksi bulanan');
    } finally {
      setLoading(false);
    }
  };

  const handleSelectSkp = (skp: SkpTahunan) => {
    setSelectedSkp(skp);
    setRencanaList([]);
    setSelectedDetailId(null);
  };

  const handleSelectDetail = (detailId: number) => {
    loadRencanaAksi(detailId);
  };

  const handleEdit = (rencana: RencanaAksiBulanan) => {
    setSelectedRencana(rencana);
    setFormData({
      rencana_aksi_bulanan: rencana.rencana_aksi_bulanan || '',
      target_bulanan: rencana.target_bulanan || 0,
      satuan_target: rencana.satuan_target || 'Dokumen'
    });
    setShowModal(true);
  };

  const handleCloseModal = () => {
    setShowModal(false);
    setSelectedRencana(null);
    setFormData({
      rencana_aksi_bulanan: '',
      target_bulanan: 0,
      satuan_target: 'Dokumen'
    });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedRencana) return;

    try {
      setLoading(true);
      await rencanaAksiBulananApi.update(selectedRencana.id, formData);

      // Reload data
      if (selectedDetailId) {
        await loadRencanaAksi(selectedDetailId);
      }

      handleCloseModal();
      alert('Rencana aksi bulanan berhasil diperbarui!');
    } catch (err: any) {
      console.error('Error updating rencana aksi:', err);
      alert(err.message || 'Failed to update rencana aksi bulanan');
    } finally {
      setLoading(false);
    }
  };

  const getStatusBadge = (status: string) => {
    const statusConfig: { [key: string]: { bg: string; text: string; label: string } } = {
      'BELUM_DIISI': { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Belum Diisi' },
      'AKTIF': { bg: 'bg-blue-100', text: 'text-blue-800', label: 'Aktif' },
      'SELESAI': { bg: 'bg-green-100', text: 'text-green-800', label: 'Selesai' }
    };
    const config = statusConfig[status] || statusConfig['BELUM_DIISI'];
    return (
      <span className={`px-2 py-1 rounded text-xs font-semibold ${config.bg} ${config.text}`}>
        {config.label}
      </span>
    );
  };

  if (loading && skpList.length === 0) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <button
            onClick={() => router.push('/asn/dashboard')}
            className="mb-4 text-blue-600 hover:text-blue-800 flex items-center"
          >
            <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Dashboard
          </button>
          <h1 className="text-3xl font-bold text-gray-900">Rencana Aksi Bulanan</h1>
          <p className="mt-2 text-gray-600">
            Kelola rencana aksi bulanan dari SKP Tahunan yang telah disetujui
          </p>
        </div>

        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <p className="text-red-800">{error}</p>
          </div>
        )}

        {/* Content Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left: SKP Tahunan List */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow">
              <div className="p-4 border-b border-gray-200">
                <h2 className="font-semibold text-gray-900">SKP Tahunan</h2>
                <p className="text-sm text-gray-600">Pilih SKP yang sudah disetujui</p>
              </div>
              <div className="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                {skpList.map((skp) => (
                  <button
                    key={skp.id}
                    onClick={() => handleSelectSkp(skp)}
                    className={`w-full text-left p-4 hover:bg-gray-50 transition-colors ${
                      selectedSkp?.id === skp.id ? 'bg-blue-50 border-l-4 border-blue-600' : ''
                    }`}
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <p className="font-medium text-gray-900">Tahun {skp.tahun}</p>
                        <p className="text-sm text-gray-600">{skp.details?.length || 0} butir kinerja</p>
                      </div>
                      <span className="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                        Disetujui
                      </span>
                    </div>
                  </button>
                ))}
                {skpList.length === 0 && !error && (
                  <div className="p-4 text-center text-gray-500">
                    <p>Tidak ada SKP Tahunan yang disetujui</p>
                  </div>
                )}
              </div>
            </div>

            {/* Butir Kinerja List */}
            {selectedSkp && selectedSkp.details && (
              <div className="bg-white rounded-lg shadow mt-6">
                <div className="p-4 border-b border-gray-200">
                  <h2 className="font-semibold text-gray-900">Butir Kinerja</h2>
                  <p className="text-sm text-gray-600">Pilih butir kinerja</p>
                </div>
                <div className="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                  {selectedSkp.details.map((detail, index) => (
                    <button
                      key={detail.id}
                      onClick={() => handleSelectDetail(detail.id)}
                      className={`w-full text-left p-4 hover:bg-gray-50 transition-colors ${
                        selectedDetailId === detail.id ? 'bg-blue-50 border-l-4 border-blue-600' : ''
                      }`}
                    >
                      <p className="font-medium text-gray-900 text-sm mb-1">
                        #{index + 1} - {detail.rhk_pimpinan?.rhk_pimpinan?.substring(0, 60)}...
                      </p>
                      <p className="text-xs text-gray-600">
                        Target: {detail.target_tahunan} {detail.satuan}
                      </p>
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Right: Rencana Aksi Bulanan List */}
          <div className="lg:col-span-2">
            {selectedDetailId ? (
              <div className="bg-white rounded-lg shadow">
                <div className="p-6 border-b border-gray-200">
                  <h2 className="text-xl font-semibold text-gray-900">Rencana Aksi Bulanan</h2>
                  <p className="text-sm text-gray-600 mt-1">12 periode bulanan (Januari - Desember)</p>
                </div>
                <div className="p-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {rencanaList.map((rencana) => (
                      <div
                        key={rencana.id}
                        className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                      >
                        <div className="flex justify-between items-start mb-3">
                          <div>
                            <h3 className="font-semibold text-gray-900">{rencana.bulan_nama}</h3>
                            <p className="text-sm text-gray-600">{rencana.tahun}</p>
                          </div>
                          {getStatusBadge(rencana.status)}
                        </div>

                        {rencana.status === 'BELUM_DIISI' ? (
                          <div className="text-center py-6">
                            <button
                              onClick={() => handleEdit(rencana)}
                              className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                            >
                              Isi Rencana Aksi
                            </button>
                          </div>
                        ) : (
                          <>
                            <div className="mb-3">
                              <p className="text-sm text-gray-700 line-clamp-2">
                                {rencana.rencana_aksi_bulanan}
                              </p>
                            </div>
                            <div className="flex justify-between items-center text-sm mb-3">
                              <span className="text-gray-600">Target:</span>
                              <span className="font-medium">{rencana.target_bulanan} {rencana.satuan_target}</span>
                            </div>
                            <div className="flex justify-between items-center text-sm mb-3">
                              <span className="text-gray-600">Realisasi:</span>
                              <span className="font-medium">{rencana.realisasi_bulanan} {rencana.satuan_target}</span>
                            </div>
                            <div className="mb-3">
                              <div className="flex justify-between text-xs mb-1">
                                <span>Capaian</span>
                                <span>{rencana.capaian_persen}%</span>
                              </div>
                              <div className="w-full bg-gray-200 rounded-full h-2">
                                <div
                                  className="bg-blue-600 h-2 rounded-full"
                                  style={{ width: `${Math.min(rencana.capaian_persen || 0, 100)}%` }}
                                ></div>
                              </div>
                            </div>
                            <button
                              onClick={() => handleEdit(rencana)}
                              className="w-full mt-2 px-3 py-1.5 text-sm text-blue-600 border border-blue-600 rounded hover:bg-blue-50"
                            >
                              Edit Rencana Aksi
                            </button>
                          </>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            ) : (
              <div className="bg-white rounded-lg shadow p-12 text-center">
                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3 className="mt-2 text-sm font-medium text-gray-900">Pilih Butir Kinerja</h3>
                <p className="mt-1 text-sm text-gray-500">
                  Pilih SKP Tahunan dan butir kinerja untuk melihat rencana aksi bulanan
                </p>
              </div>
            )}
          </div>
        </div>

        {/* Modal Form */}
        {showModal && selectedRencana && (
          <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div className="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
              <div className="mb-4">
                <h3 className="text-lg font-semibold text-gray-900">
                  Isi Rencana Aksi - {selectedRencana.bulan_nama} {selectedRencana.tahun}
                </h3>
              </div>
              <form onSubmit={handleSubmit}>
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Rencana Aksi Bulanan <span className="text-red-500">*</span>
                    </label>
                    <textarea
                      value={formData.rencana_aksi_bulanan}
                      onChange={(e) => setFormData({ ...formData, rencana_aksi_bulanan: e.target.value })}
                      rows={4}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                      placeholder="Deskripsi rencana aksi untuk bulan ini..."
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Target Bulanan <span className="text-red-500">*</span>
                    </label>
                    <input
                      type="number"
                      value={formData.target_bulanan}
                      onChange={(e) => setFormData({ ...formData, target_bulanan: parseInt(e.target.value) || 0 })}
                      min="1"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Satuan Target <span className="text-red-500">*</span>
                    </label>
                    <select
                      value={formData.satuan_target}
                      onChange={(e) => setFormData({ ...formData, satuan_target: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 text-base font-normal"
                      required
                    >
                      {satuan_options.map((satuan) => (
                        <option key={satuan} value={satuan}>{satuan}</option>
                      ))}
                    </select>
                  </div>
                </div>
                <div className="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    onClick={handleCloseModal}
                    className="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                    disabled={loading}
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:bg-gray-400"
                    disabled={loading}
                  >
                    {loading ? 'Menyimpan...' : 'Simpan'}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
