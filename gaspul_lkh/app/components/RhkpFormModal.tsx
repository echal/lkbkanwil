'use client';

import { useState, useEffect } from 'react';
import { RhkPimpinan } from '@/app/types/dashboard';

interface RhkpFormModalProps {
  isOpen: boolean;
  mode: 'create' | 'edit';
  rhkp: RhkPimpinan | null;
  isProcessing: boolean;
  onClose: () => void;
  onSubmit: (data: Omit<RhkPimpinan, 'id' | 'created_by' | 'created_at' | 'updated_at' | 'usage_count'>) => void;
}

export default function RhkpFormModal({
  isOpen,
  mode,
  rhkp,
  isProcessing,
  onClose,
  onSubmit,
}: RhkpFormModalProps) {
  const [formData, setFormData] = useState({
    rencana_hasil_kerja: '',
    unit_kerja: '',
    status: 'AKTIF' as 'AKTIF' | 'NONAKTIF',
  });

  useEffect(() => {
    if (mode === 'edit' && rhkp) {
      setFormData({
        rencana_hasil_kerja: rhkp.rencana_hasil_kerja,
        unit_kerja: rhkp.unit_kerja || '',
        status: rhkp.status,
      });
    } else {
      setFormData({
        rencana_hasil_kerja: '',
        unit_kerja: '',
        status: 'AKTIF',
      });
    }
  }, [mode, rhkp]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.rencana_hasil_kerja.trim()) {
      alert('Rencana Hasil Kerja Pimpinan wajib diisi');
      return;
    }

    if (!confirm(`Yakin ingin ${mode === 'create' ? 'menambah' : 'mengubah'} RHK Pimpinan ini?`)) {
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
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl">
        {/* Header */}
        <div className="px-6 py-4 border-b border-gray-200">
          <h3 className="text-xl font-semibold text-gray-900">
            {mode === 'create' ? 'Tambah RHK Pimpinan' : 'Edit RHK Pimpinan'}
          </h3>
        </div>

        {/* Body */}
        <form onSubmit={handleSubmit}>
          <div className="px-6 py-4 space-y-4 max-h-[calc(100vh-300px)] overflow-y-auto">
            {/* Rencana Hasil Kerja Pimpinan */}
            <div>
              <label htmlFor="rencana_hasil_kerja" className="block text-sm font-medium text-gray-700 mb-2">
                Rencana Hasil Kerja Pimpinan <span className="text-red-500">*</span>
              </label>
              <textarea
                id="rencana_hasil_kerja"
                value={formData.rencana_hasil_kerja}
                onChange={(e) => setFormData({ ...formData, rencana_hasil_kerja: e.target.value })}
                disabled={isProcessing}
                required
                rows={6}
                className="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed resize-none"
                placeholder="Contoh: Meningkatkan pelayanan publik melalui digitalisasi sistem administrasi"
              />
            </div>

            {/* Unit Kerja */}
            <div>
              <label htmlFor="unit_kerja" className="block text-sm font-medium text-gray-700 mb-2">
                Unit Kerja
              </label>
              <input
                id="unit_kerja"
                type="text"
                value={formData.unit_kerja}
                onChange={(e) => setFormData({ ...formData, unit_kerja: e.target.value })}
                disabled={isProcessing}
                className="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                placeholder="Contoh: Bagian Umum dan Kepegawaian"
              />
            </div>

            {/* Status */}
            <div>
              <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-2">
                Status <span className="text-red-500">*</span>
              </label>
              <select
                id="status"
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value as 'AKTIF' | 'NONAKTIF' })}
                disabled={isProcessing}
                required
                className="w-full border border-gray-300 rounded-lg p-3 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
              >
                <option value="AKTIF">Aktif</option>
                <option value="NONAKTIF">Non-Aktif</option>
              </select>
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
