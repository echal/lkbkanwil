'use client';

import { useState } from 'react';
import { LaporanKinerja } from '@/app/types/dashboard';
import StatusBadge from './StatusBadge';
import ApprovalModal from './ApprovalModal';

interface ApprovalTableProps {
  laporanList: LaporanKinerja[];
  onApprove: (id: number, catatan: string) => Promise<void>;
  onReject: (id: number, catatan: string) => Promise<void>;
}

export default function ApprovalTable({
  laporanList,
  onApprove,
  onReject,
}: ApprovalTableProps) {
  const [selectedLaporan, setSelectedLaporan] = useState<LaporanKinerja | null>(null);
  const [modalAction, setModalAction] = useState<'approve' | 'reject' | null>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [processingId, setProcessingId] = useState<number | null>(null);

  const handleOpenModal = (laporan: LaporanKinerja, action: 'approve' | 'reject') => {
    setSelectedLaporan(laporan);
    setModalAction(action);
    setIsModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsModalOpen(false);
    setSelectedLaporan(null);
    setModalAction(null);
  };

  const handleSubmit = async (catatan: string) => {
    if (!selectedLaporan || !modalAction) return;

    setProcessingId(selectedLaporan.id);

    try {
      if (modalAction === 'approve') {
        await onApprove(selectedLaporan.id, catatan);
      } else {
        await onReject(selectedLaporan.id, catatan);
      }
      handleCloseModal();
    } catch (error) {
      console.error('Error processing approval:', error);
    } finally {
      setProcessingId(null);
    }
  };

  if (laporanList.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow p-8">
        <div className="text-center py-12">
          <svg
            className="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          <h3 className="mt-2 text-sm font-medium text-gray-900">Tidak ada laporan</h3>
          <p className="mt-1 text-sm text-gray-500">
            Belum ada laporan kinerja yang perlu di-approve
          </p>
        </div>
      </div>
    );
  }

  return (
    <>
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Nama ASN
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  NIP
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Periode
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Ringkasan
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
              {laporanList.map((laporan) => (
                <tr key={laporan.id} className="hover:bg-gray-50 transition">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm font-medium text-gray-900">{laporan.asn_name}</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-500">{laporan.asn_nip}</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">{laporan.periode}</div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="text-sm text-gray-900 max-w-xs truncate">
                      {laporan.ringkasan_kinerja}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <StatusBadge status={laporan.status} />
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm">
                    {laporan.status === 'PENDING' ? (
                      <div className="flex space-x-2">
                        <button
                          onClick={() => handleOpenModal(laporan, 'approve')}
                          disabled={processingId === laporan.id}
                          className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
                        >
                          {processingId === laporan.id ? 'Processing...' : 'Approve'}
                        </button>
                        <button
                          onClick={() => handleOpenModal(laporan, 'reject')}
                          disabled={processingId === laporan.id}
                          className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
                        >
                          Reject
                        </button>
                      </div>
                    ) : (
                      <span className="text-gray-400 text-xs">No action</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Approval Modal */}
      <ApprovalModal
        laporan={selectedLaporan}
        action={modalAction}
        isOpen={isModalOpen}
        isProcessing={processingId !== null}
        onClose={handleCloseModal}
        onSubmit={handleSubmit}
      />
    </>
  );
}
