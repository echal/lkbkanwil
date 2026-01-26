'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import {
  getRencanaKerjaDetail,
  approveRencanaKerja,
  rejectRencanaKerja,
} from '@/app/lib/approval-api';
import { RencanaKerjaAsn } from '@/app/lib/rencana-kerja-api';

export default function DetailApprovalPage() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const router = useRouter();
  const params = useParams();
  const id = params?.id as string;

  const [rencana, setRencana] = useState<RencanaKerjaAsn | null>(null);
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Form state for approval/rejection
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

    loadRencanaDetail();
  }, [user, isAuthenticated, authLoading, router, id]);

  const loadRencanaDetail = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getRencanaKerjaDetail(parseInt(id));
      setRencana(data);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat detail Rencana Kerja');
      console.error('Failed to load Rencana Kerja:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async () => {
    if (!confirm('Yakin ingin menyetujui rencana kerja ini?')) return;

    try {
      setProcessing(true);
      await approveRencanaKerja(parseInt(id), { catatan_atasan: catatan || undefined });
      alert('Rencana Kerja berhasil disetujui!');
      router.push('/atasan/persetujuan');
    } catch (err: any) {
      alert(err.message || 'Gagal menyetujui Rencana Kerja');
    } finally {
      setProcessing(false);
    }
  };

  const handleReject = async () => {
    if (!catatan.trim()) {
      alert('Catatan penolakan wajib diisi');
      return;
    }

    if (!confirm('Yakin ingin menolak rencana kerja ini?')) return;

    try {
      setProcessing(true);
      await rejectRencanaKerja(parseInt(id), { catatan_atasan: catatan });
      alert('Rencana Kerja ditolak');
      router.push('/atasan/persetujuan');
    } catch (err: any) {
      alert(err.message || 'Gagal menolak Rencana Kerja');
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

  if (!isAuthenticated || user?.role !== 'ATASAN') {
    return null;
  }

  if (error || !rencana) {
    return (
      <div className="p-8">
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {error || 'Rencana Kerja tidak ditemukan'}
        </div>
      </div>
    );
  }

  const canApprove = rencana.status === 'DIAJUKAN';

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top Navigation Bar */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => router.push('/atasan/persetujuan')}
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
                Detail Rencana Kerja
              </h1>
            </div>
          </div>
        </div>
      </div>

      <div className="p-8">
        <div className="max-w-4xl mx-auto">
          {/* Header Card */}
          <div className="bg-white rounded-lg shadow p-6 mb-6">
            <div className="flex justify-between items-start">
              <div>
                <h2 className="text-2xl font-bold text-gray-800">
                  Rencana Kerja ASN
                </h2>
                <p className="text-gray-600 mt-1">
                  Detail dan informasi rencana kerja triwulan
                </p>
              </div>
              <span className={`inline-flex px-3 py-1 text-sm font-semibold rounded-full ${
                rencana.status === 'DRAFT' ? 'bg-gray-100 text-gray-800' :
                rencana.status === 'DIAJUKAN' ? 'bg-yellow-100 text-yellow-800' :
                rencana.status === 'DISETUJUI' ? 'bg-green-100 text-green-800' :
                'bg-red-100 text-red-800'
              }`}>
                {rencana.status}
              </span>
            </div>
          </div>

          {/* ASN Info */}
          <div className="bg-white rounded-lg shadow p-6 mb-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Informasi ASN</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-500 mb-1">
                  Nama ASN
                </label>
                <p className="text-gray-900">{rencana.user?.name || '-'}</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-500 mb-1">
                  NIP
                </label>
                <p className="text-gray-900">{rencana.user?.nip || '-'}</p>
              </div>
            </div>
          </div>

          {/* Rencana Kerja Detail */}
          <div className="bg-white rounded-lg shadow p-6 mb-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Detail Rencana Kerja</h3>

            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-500 mb-1">
                  Sasaran Kegiatan
                </label>
                <p className="text-gray-900">
                  {rencana.sasaran_kegiatan?.sasaran_kegiatan || '-'}
                </p>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-500 mb-1">
                  Indikator Kinerja
                </label>
                <p className="text-gray-900">
                  {rencana.indikator_kinerja?.indikator_kinerja || '-'}
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Periode
                  </label>
                  <p className="text-gray-900">
                    Triwulan {rencana.triwulan} / {rencana.tahun}
                  </p>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Satuan
                  </label>
                  <p className="text-gray-900">{rencana.satuan}</p>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Target
                  </label>
                  <p className="text-lg font-semibold text-gray-900">
                    {rencana.target} {rencana.satuan}
                  </p>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Realisasi
                  </label>
                  <p className="text-lg font-semibold text-gray-900">
                    {rencana.realisasi} {rencana.satuan}
                  </p>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Capaian
                  </label>
                  <span className={`inline-flex px-3 py-1 text-lg font-semibold rounded-full ${
                    (rencana.capaian_persen || 0) >= 90
                      ? 'bg-green-100 text-green-800'
                      : (rencana.capaian_persen || 0) >= 70
                      ? 'bg-yellow-100 text-yellow-800'
                      : 'bg-red-100 text-red-800'
                  }`}>
                    {rencana.capaian_persen || 0}%
                  </span>
                </div>
              </div>

              {rencana.catatan_asn && (
                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Catatan ASN
                  </label>
                  <p className="text-gray-900 bg-gray-50 p-3 rounded">
                    {rencana.catatan_asn}
                  </p>
                </div>
              )}
            </div>
          </div>

          {/* Approval History */}
          {(rencana.status === 'DISETUJUI' || rencana.status === 'DITOLAK') && (
            <div className="bg-white rounded-lg shadow p-6 mb-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">Riwayat Persetujuan</h3>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Disetujui/Ditolak Oleh
                  </label>
                  <p className="text-gray-900">
                    {rencana.approved_by_user?.name || '-'}
                  </p>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-500 mb-1">
                    Tanggal
                  </label>
                  <p className="text-gray-900">
                    {rencana.approved_at ? new Date(rencana.approved_at).toLocaleString('id-ID') : '-'}
                  </p>
                </div>
                {rencana.catatan_atasan && (
                  <div>
                    <label className="block text-sm font-medium text-gray-500 mb-1">
                      Catatan Atasan
                    </label>
                    <p className="text-gray-900 bg-gray-50 p-3 rounded">
                      {rencana.catatan_atasan}
                    </p>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Approval Actions */}
          {canApprove && (
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="text-lg font-semibold text-gray-800 mb-4">
                Tindakan Persetujuan
              </h3>

              <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Catatan (Opsional untuk Approve, Wajib untuk Reject)
                </label>
                <textarea
                  rows={4}
                  value={catatan}
                  onChange={(e) => setCatatan(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-gray-900 placeholder:text-gray-400"
                  placeholder="Berikan catatan atau feedback untuk ASN..."
                />
              </div>

              <div className="flex space-x-3">
                <button
                  onClick={handleApprove}
                  disabled={processing}
                  className="flex-1 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed font-medium"
                >
                  {processing ? 'Memproses...' : '✓ Setujui'}
                </button>
                <button
                  onClick={() => setShowRejectModal(true)}
                  disabled={processing}
                  className="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed font-medium"
                >
                  ✗ Tolak
                </button>
              </div>
            </div>
          )}

          {!canApprove && rencana.status !== 'DISETUJUI' && rencana.status !== 'DITOLAK' && (
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <p className="text-yellow-800">
                Rencana kerja ini belum diajukan untuk persetujuan.
              </p>
            </div>
          )}
        </div>
      </div>

      {/* Reject Modal */}
      {showRejectModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">
              Konfirmasi Penolakan
            </h3>
            <p className="text-gray-600 mb-4">
              Yakin ingin menolak rencana kerja ini? Pastikan Anda telah mengisi catatan penolakan.
            </p>
            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Catatan Penolakan <span className="text-red-500">*</span>
              </label>
              <textarea
                rows={3}
                value={catatan}
                onChange={(e) => setCatatan(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-gray-900 placeholder:text-gray-400"
                placeholder="Jelaskan alasan penolakan..."
              />
            </div>
            <div className="flex space-x-3">
              <button
                onClick={() => {
                  setShowRejectModal(false);
                  setCatatan('');
                }}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
              >
                Batal
              </button>
              <button
                onClick={handleReject}
                disabled={!catatan.trim() || processing}
                className="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
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
