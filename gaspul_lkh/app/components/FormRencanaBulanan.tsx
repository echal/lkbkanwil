"use client";

import { useState, useEffect } from "react";
import {
  IndikatorTriwulan,
  RencanaKerjaBulanan,
  SatuanPengukuran,
  StatusPekerjaan,
  Bulan,
} from "../lib/types";
import { validateBulananTarget, ValidationResult } from "../lib/validation-helpers";

interface FormRencanaBulananProps {
  indikatorTriwulan: IndikatorTriwulan;
  allBulanan: RencanaKerjaBulanan[];
  onSubmit: (data: Partial<RencanaKerjaBulanan>) => void;
  onCancel: () => void;
  editData?: RencanaKerjaBulanan;
}

export function FormRencanaBulanan({
  indikatorTriwulan,
  allBulanan,
  onSubmit,
  onCancel,
  editData,
}: FormRencanaBulananProps) {
  const [formData, setFormData] = useState<Partial<RencanaKerjaBulanan>>({
    bulan: editData?.bulan || Bulan.JANUARI,
    tahun: editData?.tahun || new Date().getFullYear(),
    namaKegiatan: editData?.namaKegiatan || "",
    deskripsiKegiatan: editData?.deskripsiKegiatan || "",
    targetBulanan: editData?.targetBulanan || 0,
    realisasiBulanan: editData?.realisasiBulanan || 0,
    satuanPengukuran: editData?.satuanPengukuran || indikatorTriwulan.satuanPengukuran,
    anggaranKegiatan: editData?.anggaranKegiatan || 0,
    statusPelaksanaan: editData?.statusPelaksanaan || StatusPekerjaan.BELUM_DIMULAI,
    hambatan: editData?.hambatan || "",
    solusi: editData?.solusi || "",
  });

  const [validation, setValidation] = useState<ValidationResult>({
    isValid: true,
    message: "",
  });

  // Real-time validation saat realisasi berubah
  useEffect(() => {
    if (formData.realisasiBulanan && formData.realisasiBulanan > 0) {
      const result = validateBulananTarget(
        indikatorTriwulan,
        allBulanan,
        formData.realisasiBulanan,
        editData?.id
      );
      setValidation(result);
    }
  }, [formData.realisasiBulanan, indikatorTriwulan, allBulanan, editData?.id]);

  // Hitung sisa target real-time
  const totalRealisasiLain = allBulanan
    .filter((bln) => bln.indikatorTriwulanId === indikatorTriwulan.id)
    .filter((bln) => bln.id !== editData?.id)
    .reduce((sum, bln) => sum + bln.realisasiBulanan, 0);

  const sisaTarget = indikatorTriwulan.targetTriwulan - totalRealisasiLain;
  const persentaseProgress = Math.round(
    ((totalRealisasiLain + (formData.realisasiBulanan || 0)) /
      indikatorTriwulan.targetTriwulan) *
      100
  );

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Validasi final sebelum submit
    const finalValidation = validateBulananTarget(
      indikatorTriwulan,
      allBulanan,
      formData.realisasiBulanan || 0,
      editData?.id
    );

    if (!finalValidation.isValid) {
      setValidation(finalValidation);
      return;
    }

    onSubmit(formData);
  };

  return (
    <div className="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <form onSubmit={handleSubmit}>
          {/* Header */}
          <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 className="text-xl font-semibold text-gray-900">
              {editData ? "Edit" : "Tambah"} Rencana Kerja Bulanan
            </h2>
            <p className="text-sm text-gray-600 mt-1">
              Triwulan {indikatorTriwulan.triwulan.replace("TRIWULAN_", "")} -{" "}
              {indikatorTriwulan.tahunAnggaran}
            </p>
          </div>

          {/* Target Info & Progress */}
          <div className="px-6 py-4 bg-blue-50 border-b border-blue-200">
            <div className="flex justify-between items-center mb-2">
              <span className="text-sm font-medium text-blue-900">
                Target Triwulan
              </span>
              <span className="text-lg font-bold text-blue-900">
                {indikatorTriwulan.targetTriwulan} {indikatorTriwulan.satuanPengukuran}
              </span>
            </div>
            <div className="flex justify-between items-center mb-2">
              <span className="text-sm text-blue-700">Sudah Terisi</span>
              <span className="text-sm font-semibold text-blue-700">
                {totalRealisasiLain} {indikatorTriwulan.satuanPengukuran}
              </span>
            </div>
            <div className="flex justify-between items-center mb-3">
              <span className="text-sm font-medium text-blue-900">
                Sisa Target Tersedia
              </span>
              <span
                className={`text-lg font-bold ${
                  sisaTarget > 0 ? "text-green-600" : "text-red-600"
                }`}
              >
                {sisaTarget} {indikatorTriwulan.satuanPengukuran}
              </span>
            </div>

            {/* Progress Bar */}
            <div className="relative pt-1">
              <div className="flex mb-2 items-center justify-between">
                <div>
                  <span className="text-xs font-semibold inline-block text-blue-600">
                    Progress Total
                  </span>
                </div>
                <div className="text-right">
                  <span className="text-xs font-semibold inline-block text-blue-600">
                    {persentaseProgress}%
                  </span>
                </div>
              </div>
              <div className="overflow-hidden h-2 text-xs flex rounded bg-blue-200">
                <div
                  style={{ width: `${Math.min(persentaseProgress, 100)}%` }}
                  className={`shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center ${
                    persentaseProgress > 100
                      ? "bg-red-500"
                      : persentaseProgress >= 90
                      ? "bg-green-500"
                      : "bg-blue-500"
                  }`}
                ></div>
              </div>
            </div>
          </div>

          {/* Form Fields */}
          <div className="px-6 py-4 space-y-4">
            {/* Bulan & Tahun */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Bulan <span className="text-red-500">*</span>
                </label>
                <select
                  required
                  value={formData.bulan}
                  onChange={(e) =>
                    setFormData({ ...formData, bulan: e.target.value as Bulan })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  {Object.values(Bulan).map((bulan) => (
                    <option key={bulan} value={bulan}>
                      {bulan}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Tahun <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  required
                  value={formData.tahun}
                  onChange={(e) =>
                    setFormData({ ...formData, tahun: parseInt(e.target.value) })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            {/* Nama Kegiatan */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Nama Kegiatan <span className="text-red-500">*</span>
              </label>
              <input
                type="text"
                required
                value={formData.namaKegiatan}
                onChange={(e) =>
                  setFormData({ ...formData, namaKegiatan: e.target.value })
                }
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Misal: Pengembangan Aplikasi Kepegawaian"
              />
            </div>

            {/* Deskripsi Kegiatan */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Deskripsi Kegiatan <span className="text-red-500">*</span>
              </label>
              <textarea
                required
                rows={3}
                value={formData.deskripsiKegiatan}
                onChange={(e) =>
                  setFormData({ ...formData, deskripsiKegiatan: e.target.value })
                }
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Jelaskan detail kegiatan yang akan dilakukan..."
              />
            </div>

            {/* Target & Realisasi */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Target Bulanan <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  required
                  min="0"
                  value={formData.targetBulanan}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      targetBulanan: parseInt(e.target.value) || 0,
                    })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Realisasi Bulanan <span className="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  required
                  min="0"
                  max={sisaTarget}
                  value={formData.realisasiBulanan}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      realisasiBulanan: parseInt(e.target.value) || 0,
                    })
                  }
                  className={`w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 ${
                    !validation.isValid
                      ? "border-red-500 focus:ring-red-500"
                      : "border-gray-300 focus:ring-blue-500"
                  }`}
                />
                {!validation.isValid && (
                  <p className="mt-1 text-xs text-red-600">{validation.message}</p>
                )}
              </div>
            </div>

            {/* Anggaran & Status */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Anggaran Kegiatan (Rp)
                </label>
                <input
                  type="number"
                  min="0"
                  value={formData.anggaranKegiatan}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      anggaranKegiatan: parseInt(e.target.value) || 0,
                    })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Status Pelaksanaan
                </label>
                <select
                  value={formData.statusPelaksanaan}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      statusPelaksanaan: e.target.value as StatusPekerjaan,
                    })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  {Object.values(StatusPekerjaan).map((status) => (
                    <option key={status} value={status}>
                      {status.replace("_", " ")}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* Hambatan & Solusi */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Hambatan
                </label>
                <textarea
                  rows={2}
                  value={formData.hambatan}
                  onChange={(e) =>
                    setFormData({ ...formData, hambatan: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Jelaskan hambatan yang dihadapi..."
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Solusi
                </label>
                <textarea
                  rows={2}
                  value={formData.solusi}
                  onChange={(e) =>
                    setFormData({ ...formData, solusi: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Jelaskan solusi yang dilakukan..."
                />
              </div>
            </div>
          </div>

          {/* Footer Actions */}
          <div className="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
            <button
              type="button"
              onClick={onCancel}
              className="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            >
              Batal
            </button>
            <button
              type="submit"
              disabled={!validation.isValid}
              className={`px-4 py-2 text-white rounded-lg transition ${
                validation.isValid
                  ? "bg-blue-600 hover:bg-blue-700"
                  : "bg-gray-400 cursor-not-allowed"
              }`}
            >
              {editData ? "Simpan Perubahan" : "Tambah Rencana"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
