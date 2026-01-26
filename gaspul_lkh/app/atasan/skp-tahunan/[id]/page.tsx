'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import {
  getSkpTahunanDetailForApproval,
  approveSkpTahunan,
  rejectSkpTahunan,
  type SkpTahunan,
} from '@/app/lib/skp-tahunan-api';

export default function DetailSkpTahunanApprovalPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const id = parseInt(params.id as string);

  const [skp, setSkp] = useState<SkpTahunan | null>(null);
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showApproveModal, setShowApproveModal] = useState(false);
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [catatan, setCatatan] = useState('');

  // RBAC Guard
  useEffect(() => {
    if (authLoading) return;

    if (!isAuthenticated) {
      router.push('/login');
      return;
    }

    if (user?.role !== 'ATASAN') {
      router.push('/unauthorized');
      return;
    }

    loadSkpDetail();
  }, [user, isAuthenticated, authLoading, router, id]);

  const loadSkpDetail = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getSkpTahunanDetailForApproval(id);
      setSkp(data);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat detail SKP Tahunan');
      console.error('Failed to load SKP detail:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async () => {
    if (!skp) return;

    try {
      setProcessing(true);
      await approveSkpTahunan(skp.id, catatan);
      alert('SKP Tahunan berhasil disetujui!');
      router.push('/atasan/skp-tahunan');
    } catch (err: any) {
      alert(err.message || 'Gagal menyetujui SKP Tahunan');
    } finally {
      setProcessing(false);
      setShowApproveModal(false);
    }
  };

  const handleReject = async () => {
    if (!skp) return;

    if (!catatan.trim()) {
      alert('Catatan wajib diisi saat menolak SKP Tahunan');
      return;
    }

    try {
      setProcessing(true);
      await rejectSkpTahunan(skp.id, catatan);
      alert('SKP Tahunan ditolak');
      router.push('/atasan/skp-tahunan');
    } catch (err: any) {
      alert(err.message || 'Gagal menolak SKP Tahunan');
    } finally {
      setProcessing(false);
      setShowRejectModal(false);
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

  if (error || !skp) {
    return (
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-3xl mx-auto">
          <div className="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg">
            <p className="font-semibold">Error</p>
            <p className="mt-2">{error || 'SKP Tahunan tidak ditemukan'}</p>
            <button
              onClick={() => router.push('/atasan/skp-tahunan')}
              className="mt-4 text-red-800 hover:text-red-900 underline"
            >
              ← Kembali ke Daftar SKP Tahunan
            </button>
          </div>
        </div>
      </div>
    );
  }

  const canApprove = skp.status === 'DIAJUKAN';

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top Navigation Bar */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => router.push('/atasan/skp-tahunan')}
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
                Detail SKP Tahunan
              </h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-4xl mx-auto">
          {/* Status Badge */}
          <div className="mb-6 flex items-center justify-between">
            <span
              className={`inline-flex px-4 py-2 text-sm font-semibold rounded-full ${
                skp.status === 'DRAFT'
                  ? 'bg-gray-100 text-gray-800'
                  : skp.status === 'DIAJUKAN'
                  ? 'bg-yellow-100 text-yellow-800'
                  : skp.status === 'DISETUJUI'
                  ? 'bg-green-100 text-green-800'
                  : 'bg-red-100 text-red-800'
              }`}
            >
              Status: {skp.status}
            </span>

            {canApprove && (
              <div className="flex gap-3">
                <button
                  onClick={() => setShowRejectModal(true)}
                  className="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                >
                  Tolak
                </button>
                <button
                  onClick={() => setShowApproveModal(true)}
                  className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                >
                  Setujui
                </button>
              </div>
            )}
          </div>

          {/* Main Content */}
          <div className="bg-white rounded-lg shadow p-6 space-y-6">
            {/* ASN Information */}
            <div>
              <h2 className="text-lg font-semibold text-gray-800 mb-4">Informasi ASN</h2>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-gray-600">Nama</p>
                  <p className="text-base font-medium text-gray-900">{skp.user?.name || '-'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">NIP</p>
                  <p className="text-base font-medium text-gray-900">{skp.user?.nip || '-'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Email</p>
                  <p className="text-base font-medium text-gray-900">{skp.user?.email || '-'}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600">Tahun</p>
                  <p className="text-base font-medium text-gray-900">{skp.tahun}</p>
                </div>
              </div>
            </div>

            <hr />

            {/* SKP Summary */}
            <div>
              <h2 className="text-lg font-semibold text-gray-800 mb-4">Ringkasan SKP Tahunan</h2>

              <div className="grid grid-cols-2 gap-4 mb-6">
                <div>
                  <p className="text-sm font-medium text-gray-600 mb-1">Total Butir Kinerja</p>
                  <p className="text-2xl font-bold text-gray-900">
                    {skp.total_butir_kinerja || 0}
                  </p>
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-600 mb-1">Capaian Keseluruhan</p>
                  <div className="flex items-center gap-2">
                    <div className="flex-1 bg-gray-200 rounded-full h-3">
                      <div
                        className={`h-3 rounded-full ${
                          (skp.capaian_persen || 0) >= 90
                            ? 'bg-green-500'
                            : (skp.capaian_persen || 0) >= 70
                            ? 'bg-yellow-500'
                            : 'bg-red-500'
                        }`}
                        style={{ width: `${Math.min(skp.capaian_persen || 0, 100)}%` }}
                      ></div>
                    </div>
                    <span className="text-lg font-bold text-gray-900">
                      {skp.capaian_persen || 0}%
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <hr />

            {/* List of Butir Kinerja (Details) */}
            <div>
              <h2 className="text-lg font-semibold text-gray-800 mb-4">
                Daftar Butir Kinerja ({skp.details?.length || 0})
              </h2>

              {(!skp.details || skp.details.length === 0) ? (
                <div className="text-center py-8 text-gray-500">
                  <p>Belum ada butir kinerja</p>
                </div>
              ) : (
                <div className="space-y-6">
                  {skp.details.map((detail, index) => (
                    <div key={detail.id} className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                      <div className="flex items-start justify-between mb-3">
                        <h3 className="text-md font-semibold text-gray-800">
                          Butir Kinerja #{index + 1}
                        </h3>
                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                          (detail.capaian_persen || 0) >= 90
                            ? 'bg-green-100 text-green-800'
                            : (detail.capaian_persen || 0) >= 70
                            ? 'bg-yellow-100 text-yellow-800'
                            : 'bg-red-100 text-red-800'
                        }`}>
                          {detail.capaian_persen || 0}%
                        </span>
                      </div>

                      <div className="space-y-3">
                        <div>
                          <p className="text-xs font-medium text-gray-600 mb-1">Sasaran Kegiatan</p>
                          <p className="text-sm text-gray-900">
                            {detail.sasaran_kegiatan?.sasaran_kegiatan || '-'}
                          </p>
                        </div>

                        <div>
                          <p className="text-xs font-medium text-gray-600 mb-1">Indikator Kinerja</p>
                          <p className="text-sm text-gray-900">
                            {detail.indikator_kinerja?.indikator_kinerja || '-'}
                          </p>
                        </div>

                        <div className="grid grid-cols-3 gap-3">
                          <div>
                            <p className="text-xs font-medium text-gray-600 mb-1">Target</p>
                            <p className="text-sm text-gray-900">
                              {detail.target_tahunan} {detail.satuan}
                            </p>
                          </div>
                          <div>
                            <p className="text-xs font-medium text-gray-600 mb-1">Realisasi</p>
                            <p className="text-sm text-gray-900">
                              {detail.realisasi_tahunan} {detail.satuan}
                            </p>
                          </div>
                          <div>
                            <p className="text-xs font-medium text-gray-600 mb-1">Capaian</p>
                            <p className="text-sm font-bold text-gray-900">
                              {detail.capaian_persen || 0}%
                            </p>
                          </div>
                        </div>

                        {detail.rencana_aksi && (
                          <div>
                            <p className="text-xs font-medium text-gray-600 mb-1">Rencana Aksi</p>
                            <p className="text-sm text-gray-700 whitespace-pre-wrap">
                              {detail.rencana_aksi}
                            </p>
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Catatan Atasan (if exists) */}
            {skp.catatan_atasan && (
              <>
                <hr />
                <div>
                  <h2 className="text-lg font-semibold text-gray-800 mb-4">Catatan Atasan</h2>
                  <div className="bg-gray-50 rounded-lg p-4">
                    <p className="text-base text-gray-900 whitespace-pre-wrap">
                      {skp.catatan_atasan}
                    </p>
                  </div>
                </div>
              </>
            )}
          </div>

          {/* Info Box */}
          <div className="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
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
                <h3 className="text-sm font-medium text-blue-800">Penting</h3>
                <div className="mt-2 text-sm text-blue-700">
                  <p>
                    SKP Tahunan harus disetujui terlebih dahulu sebelum ASN dapat membuat
                    SKP Triwulan. Pastikan Anda meninjau dengan teliti sebelum menyetujui.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Approve Modal */}
      {showApproveModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-md w-full p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Setujui SKP Tahunan
            </h3>
            <p className="text-sm text-gray-600 mb-4">
              Anda yakin ingin menyetujui SKP Tahunan dari <strong>{skp.user?.name}</strong>?
            </p>
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Catatan (Opsional)
              </label>
              <textarea
                value={catatan}
                onChange={(e) => setCatatan(e.target.value)}
                rows={3}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 bg-white"
                placeholder="Tambahkan catatan jika diperlukan..."
              />
            </div>
            <div className="flex gap-3">
              <button
                onClick={() => setShowApproveModal(false)}
                disabled={processing}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition disabled:opacity-50"
              >
                Batal
              </button>
              <button
                onClick={handleApprove}
                disabled={processing}
                className="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50"
              >
                {processing ? 'Memproses...' : 'Setujui'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Reject Modal */}
      {showRejectModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-md w-full p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Tolak SKP Tahunan
            </h3>
            <p className="text-sm text-gray-600 mb-4">
              Anda yakin ingin menolak SKP Tahunan dari <strong>{skp.user?.name}</strong>?
            </p>
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Catatan <span className="text-red-500">*</span>
              </label>
              <textarea
                value={catatan}
                onChange={(e) => setCatatan(e.target.value)}
                rows={3}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-gray-900 bg-white"
                placeholder="Jelaskan alasan penolakan..."
                required
              />
              <p className="text-xs text-gray-500 mt-1">Catatan wajib diisi saat menolak</p>
            </div>
            <div className="flex gap-3">
              <button
                onClick={() => setShowRejectModal(false)}
                disabled={processing}
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition disabled:opacity-50"
              >
                Batal
              </button>
              <button
                onClick={handleReject}
                disabled={processing}
                className="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:opacity-50"
              >
                {processing ? 'Memproses...' : 'Tolak'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
