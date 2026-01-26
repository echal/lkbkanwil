'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import { getRencanaKerjaList, submitRencanaKerja, type RencanaKerjaAsn } from '@/app/lib/rencana-kerja-api';

export default function RencanaKerjaListPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();

  const [rencanaList, setRencanaList] = useState<RencanaKerjaAsn[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filterStatus, setFilterStatus] = useState<string>('');
  const [filterTriwulan, setFilterTriwulan] = useState<string>('');
  const [filterTahun, setFilterTahun] = useState<string>('');

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
  }, [user, isAuthenticated, authLoading, router, filterStatus, filterTriwulan, filterTahun]);

  const loadRencanaKerja = async () => {
    try {
      setLoading(true);
      const params: any = {};
      if (filterStatus) params.status = filterStatus;
      if (filterTriwulan) params.triwulan = filterTriwulan;
      if (filterTahun) params.tahun = filterTahun;

      const data = await getRencanaKerjaList(params);
      setRencanaList(data);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat Rencana Kerja');
      console.error('Failed to load Rencana Kerja:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (id: number) => {
    if (!confirm('Apakah Anda yakin ingin mengajukan SKP Triwulan ini ke Atasan?')) {
      return;
    }

    try {
      await submitRencanaKerja(id);
      alert('SKP Triwulan berhasil diajukan ke Atasan!');
      loadRencanaKerja();
    } catch (err: any) {
      alert(err.message || 'Gagal mengajukan SKP Triwulan');
      console.error('Failed to submit:', err);
    }
  };

  const getStatusBadge = (status: string) => {
    const badges: { [key: string]: string } = {
      'DRAFT': 'bg-gray-100 text-gray-800',
      'DIAJUKAN': 'bg-yellow-100 text-yellow-800',
      'DISETUJUI': 'bg-green-100 text-green-800',
      'DITOLAK': 'bg-red-100 text-red-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
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

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => router.back()}
                className="text-gray-600 hover:text-gray-900 transition"
                title="Kembali"
              >
                <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <h1 className="text-2xl font-semibold text-gray-900">
                SKP Triwulan
              </h1>
            </div>
            <button
              onClick={() => router.push('/asn/rencana-kerja/tambah')}
              className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              Tambah SKP Triwulan
            </button>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                <strong>SKP Triwulan</strong> adalah rencana kerja triwulanan yang merupakan turunan dari SKP Tahunan yang telah disetujui.
              </p>
            </div>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow p-4 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
              >
                <option value="">Semua Status</option>
                <option value="DRAFT">Draft</option>
                <option value="DIAJUKAN">Diajukan</option>
                <option value="DISETUJUI">Disetujui</option>
                <option value="DITOLAK">Ditolak</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Triwulan</label>
              <select
                value={filterTriwulan}
                onChange={(e) => setFilterTriwulan(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
              >
                <option value="">Semua Triwulan</option>
                <option value="I">Triwulan I</option>
                <option value="II">Triwulan II</option>
                <option value="III">Triwulan III</option>
                <option value="IV">Triwulan IV</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
              <input
                type="number"
                value={filterTahun}
                onChange={(e) => setFilterTahun(e.target.value)}
                placeholder="Contoh: 2026"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 bg-white"
              />
            </div>
          </div>
        </div>

        {/* Error Message */}
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
            {error}
          </div>
        )}

        {/* Table */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tahun / Triwulan
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Butir Kinerja
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Target
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Realisasi
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Capaian
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {rencanaList.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-6 py-12 text-center text-gray-500">
                    <div className="flex flex-col items-center">
                      <svg className="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      <p className="text-lg font-medium">Belum ada SKP Triwulan</p>
                      <p className="text-sm mt-1">Klik tombol "Tambah SKP Triwulan" untuk membuat rencana kerja triwulan</p>
                    </div>
                  </td>
                </tr>
              ) : (
                rencanaList.map((rencana) => (
                  <tr key={rencana.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">{rencana.tahun}</div>
                      <div className="text-sm text-gray-500">Triwulan {rencana.triwulan}</div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="text-sm font-medium text-gray-900">
                        {rencana.skp_tahunan_detail?.sasaran_kegiatan?.sasaran_kegiatan || rencana.sasaran_kegiatan?.sasaran_kegiatan || '-'}
                      </div>
                      <div className="text-sm text-gray-500">
                        {rencana.skp_tahunan_detail?.indikator_kinerja?.indikator_kinerja || rencana.indikator_kinerja?.indikator_kinerja || '-'}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {rencana.target} {rencana.satuan}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {rencana.realisasi || 0} {rencana.satuan}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">
                        {rencana.capaian_persen || 0}%
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusBadge(rencana.status)}`}>
                        {rencana.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                      {rencana.status === 'DRAFT' && (
                        <>
                          <button
                            onClick={() => handleSubmit(rencana.id)}
                            className="text-blue-600 hover:text-blue-900"
                          >
                            Ajukan
                          </button>
                          <button
                            onClick={() => router.push(`/asn/rencana-kerja/${rencana.id}/edit`)}
                            className="text-yellow-600 hover:text-yellow-900"
                          >
                            Edit
                          </button>
                        </>
                      )}
                      {rencana.status === 'DISETUJUI' && (
                        <button
                          onClick={() => router.push(`/asn/bulanan?rencana_kerja_id=${rencana.id}`)}
                          className="text-green-600 hover:text-green-900"
                        >
                          Lihat Bulanan
                        </button>
                      )}
                      <button
                        onClick={() => router.push(`/asn/rencana-kerja/${rencana.id}`)}
                        className="text-gray-600 hover:text-gray-900"
                      >
                        Detail
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
