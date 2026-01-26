'use client';

import { useState, useEffect } from 'react';
import { IndikatorKinerja, TriwulanType } from '@/app/types/dashboard';

interface IndikatorFormModalProps {
  isOpen: boolean;
  mode: 'create' | 'edit';
  indikator: IndikatorKinerja | null;
  isProcessing: boolean;
  onClose: () => void;
  onSubmit: (data: Omit<IndikatorKinerja, 'id' | 'created_at' | 'updated_at'>) => void;
}

const TRIWULAN_OPTIONS: { value: TriwulanType; label: string }[] = [
  { value: 'TW1', label: 'Triwulan 1' },
  { value: 'TW2', label: 'Triwulan 2' },
  { value: 'TW3', label: 'Triwulan 3' },
  { value: 'TW4', label: 'Triwulan 4' },
];

export default function IndikatorFormModal({
  isOpen,
  mode,
  indikator,
  isProcessing,
  onClose,
  onSubmit,
}: IndikatorFormModalProps) {
  const currentYear = new Date().getFullYear();
  const [formData, setFormData] = useState({
    tahun: currentYear,
    triwulan: 'TW1' as TriwulanType,
    indikator: '',
    target: '',
  });

  useEffect(() => {
    if (mode === 'edit' && indikator) {
      setFormData({
        tahun: indikator.tahun,
        triwulan: indikator.triwulan,
        indikator: indikator.indikator,
        target: indikator.target,
      });
    } else {
      setFormData({
        tahun: currentYear,
        triwulan: 'TW1',
        indikator: '',
        target: '',
      });
    }
  }, [mode, indikator, currentYear]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.indikator.trim() || !formData.target.trim()) {
      alert('Semua field wajib diisi');
      return;
    }

    if (!confirm(`Yakin ingin ${mode === 'create' ? 'menambah' : 'mengubah'} indikator ini?`)) {
      return;
    }

    onSubmit(formData);
  };

  const handleClose = () => {
    if (!isProcessing) {
      onClose();
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-lg">
        {/* Header */}
        <div className="px-6 py-4 border-b border-gray-200">
          <h3 className="text-xl font-semibold text-gray-900">
            {mode === 'create' ? 'Tambah Indikator Kinerja' : 'Edit Indikator Kinerja'}
          </h3>
        </div>

        {/* Body */}
        <form onSubmit={handleSubmit}>
          <div className="px-6 py-4 space-y-4">
            {/* Tahun */}
            <div>
              <label htmlFor="tahun" className="block text-sm font-medium text-gray-700 mb-2">
                Tahun <span className="text-red-500">*</span>
              </label>
              <input
                id="tahun"
                type="number"
                value={formData.tahun}
                onChange={(e) => setFormData({ ...formData, tahun: parseInt(e.target.value) })}
                disabled={isProcessing}
                min={2020}
                max={2100}
                required
                className="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
              />
            </div>

            {/* Triwulan */}
            <div>
              <label htmlFor="triwulan" className="block text-sm font-medium text-gray-700 mb-2">
                Triwulan <span className="text-red-500">*</span>
              </label>
              <select
                id="triwulan"
                value={formData.triwulan}
                onChange={(e) => setFormData({ ...formData, triwulan: e.target.value as TriwulanType })}
                disabled={isProcessing}
                required
                className="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
              >
                {TRIWULAN_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>

            {/* Indikator Kinerja */}
            <div>
              <label htmlFor="indikator" className="block text-sm font-medium text-gray-700 mb-2">
                Indikator Kinerja <span className="text-red-500">*</span>
              </label>
              <textarea
                id="indikator"
                value={formData.indikator}
                onChange={(e) => setFormData({ ...formData, indikator: e.target.value })}
                disabled={isProcessing}
                required
                rows={4}
                className="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed resize-none"
                placeholder="Contoh: Persentase kehadiran ASN minimal 95%"
              />
            </div>

            {/* Target */}
            <div>
              <label htmlFor="target" className="block text-sm font-medium text-gray-700 mb-2">
                Target <span className="text-red-500">*</span>
              </label>
              <input
                id="target"
                type="text"
                value={formData.target}
                onChange={(e) => setFormData({ ...formData, target: e.target.value })}
                disabled={isProcessing}
                required
                className="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                placeholder="Contoh: 95% atau 100 dokumen"
              />
            </div>
          </div>

          {/* Footer */}
          <div className="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
            <button
              type="button"
              onClick={handleClose}
              disabled={isProcessing}
              className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:bg-gray-100 disabled:cursor-not-allowed transition"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={isProcessing}
              className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
            >
              {isProcessing ? 'Processing...' : mode === 'create' ? 'Tambah' : 'Simpan'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
