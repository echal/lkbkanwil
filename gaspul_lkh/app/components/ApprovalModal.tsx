'use client';

import { useState } from 'react';
import { LaporanKinerja } from '@/app/types/dashboard';

interface ApprovalModalProps {
  laporan: LaporanKinerja | null;
  action: 'approve' | 'reject' | null;
  isOpen: boolean;
  isProcessing: boolean;
  onClose: () => void;
  onSubmit: (catatan: string) => void;
}

export default function ApprovalModal({
  laporan,
  action,
  isOpen,
  isProcessing,
  onClose,
  onSubmit,
}: ApprovalModalProps) {
  const [catatan, setCatatan] = useState('');

  if (!isOpen || !laporan || !action) return null;

  const handleSubmit = () => {
    if (action === 'reject' && !catatan.trim()) {
      alert('Catatan wajib diisi saat menolak laporan');
      return;
    }
    onSubmit(catatan);
    setCatatan('');
  };

  const handleClose = () => {
    if (!isProcessing) {
      setCatatan('');
      onClose();
    }
  };

  const isApprove = action === 'approve';
  const title = isApprove ? 'Approve Laporan' : 'Reject Laporan';
  const confirmText = isApprove ? 'Approve' : 'Reject';
  const bgColor = isApprove ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700';

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-md">
        {/* Header */}
        <div className="px-6 py-4 border-b border-gray-200">
          <h3 className="text-xl font-semibold text-gray-900">{title}</h3>
        </div>

        {/* Body */}
        <div className="px-6 py-4 space-y-4">
          {/* Laporan Details */}
          <div className="bg-gray-50 rounded-lg p-4 space-y-2">
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">ASN:</span>
              <span className="text-sm font-medium text-gray-900">{laporan.asn_name}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">NIP:</span>
              <span className="text-sm font-medium text-gray-900">{laporan.asn_nip}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Periode:</span>
              <span className="text-sm font-medium text-gray-900">{laporan.periode}</span>
            </div>
          </div>

          {/* Ringkasan */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Ringkasan Kinerja
            </label>
            <p className="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
              {laporan.ringkasan_kinerja}
            </p>
          </div>

          {/* Catatan Input */}
          <div>
            <label htmlFor="catatan" className="block text-sm font-medium text-gray-700 mb-2">
              Catatan Atasan {action === 'reject' && <span className="text-red-500">*</span>}
            </label>
            <textarea
              id="catatan"
              value={catatan}
              onChange={(e) => setCatatan(e.target.value)}
              disabled={isProcessing}
              className="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed resize-none"
              rows={4}
              placeholder={
                action === 'reject'
                  ? 'Jelaskan alasan penolakan...'
                  : 'Tambahkan catatan (opsional)...'
              }
            />
            {action === 'reject' && (
              <p className="mt-1 text-xs text-red-600">Catatan wajib diisi saat reject</p>
            )}
          </div>
        </div>

        {/* Footer */}
        <div className="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
          <button
            onClick={handleClose}
            disabled={isProcessing}
            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:bg-gray-100 disabled:cursor-not-allowed transition"
          >
            Batal
          </button>
          <button
            onClick={handleSubmit}
            disabled={isProcessing}
            className={`px-4 py-2 text-sm font-medium text-white rounded-lg disabled:bg-gray-400 disabled:cursor-not-allowed transition ${bgColor}`}
          >
            {isProcessing ? 'Processing...' : confirmText}
          </button>
        </div>
      </div>
    </div>
  );
}
