"use client";

import { useState, useEffect } from "react";
import {
  RencanaKerjaBulanan,
  RencanaKerjaHarian,
  SatuanPengukuran,
  StatusPekerjaan,
} from "../lib/types";
import { validateHarianTarget, ValidationResult } from "../lib/validation-helpers";

interface FormRencanaHarianProps {
  rencanaBulanan: RencanaKerjaBulanan;
  allHarian: RencanaKerjaHarian[];
  onSubmit: (data: Partial<RencanaKerjaHarian>) => void;
  onCancel: () => void;
  editData?: RencanaKerjaHarian;
}

export function FormRencanaHarian({
  rencanaBulanan,
  allHarian,
  onSubmit,
  onCancel,
  editData,
}: FormRencanaHarianProps) {
  const [formData, setFormData] = useState<Partial<RencanaKerjaHarian>>({
    tanggal: editData?.tanggal || new Date(),
    uraianTugas: editData?.uraianTugas || "",
    kuantitasOutput: editData?.kuantitasOutput || 0,
    satuanOutput: editData?.satuanOutput || rencanaBulanan.satuanPengukuran,
    waktuMulai: editData?.waktuMulai || "08:00",
    waktuSelesai: editData?.waktuSelesai || "16:00",
    durasiMenit: editData?.durasiMenit || 0,
    statusPelaksanaan: editData?.statusPelaksanaan || StatusPekerjaan.BELUM_DIMULAI,
    hasilKerja: editData?.hasilKerja || "",
    buktiDokumen: editData?.buktiDokumen || "",
    keterangan: editData?.keterangan || "",
  });

  const [validation, setValidation] = useState<ValidationResult>({
    isValid: true,
    message: "",
  });

  // Real-time validation saat kuantitas berubah
  useEffect(() => {
    if (formData.kuantitasOutput && formData.kuantitasOutput > 0) {
      const result = validateHarianTarget(
        rencanaBulanan,
        allHarian,
        formData.kuantitasOutput,
        editData?.id
      );
      setValidation(result);
    }
  }, [formData.kuantitasOutput, rencanaBulanan, allHarian, editData?.id]);

  // Auto-calculate durasi
  useEffect(() => {
    if (formData.waktuMulai && formData.waktuSelesai) {
      const [startHour, startMin] = formData.waktuMulai.split(":").map(Number);
      const [endHour, endMin] = formData.waktuSelesai.split(":").map(Number);

      const startTotalMin = startHour * 60 + startMin;
      const endTotalMin = endHour * 60 + endMin;

      let durasi = endTotalMin - startTotalMin;
      if (durasi < 0) durasi += 24 * 60; // Handle overnight

      setFormData((prev) => ({ ...prev, durasiMenit: durasi }));
    }
  }, [formData.waktuMulai, formData.waktuSelesai]);

  // Hitung sisa target real-time
  const totalKuantitasLain = allHarian
    .filter((hrn) => hrn.rencanaKerjaBulananId === rencanaBulanan.id)
    .filter((hrn) => hrn.id !== editData?.id)
    .reduce((sum, hrn) => sum + hrn.kuantitasOutput, 0);

  const sisaTarget = rencanaBulanan.targetBulanan - totalKuantitasLain;
  const persentaseProgress = Math.round(
    ((totalKuantitasLain + (formData.kuantitasOutput || 0)) /
      rencanaBulanan.targetBulanan) *
      100
  );

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // Validasi final sebelum submit
    const finalValidation = validateHarianTarget(
      rencanaBulanan,
      allHarian,
      formData.kuantitasOutput || 0,
      editData?.id
    );

    if (!finalValidation.isValid) {
      setValidation(finalValidation);
      return;
    }

    onSubmit(formData);
  };

  const formatDurasi = (menit: number) => {
    const jam = Math.floor(menit / 60);
    const sisaMenit = menit % 60;
    return `${jam}j ${sisaMenit}m`;
  };

  return (
    <div className="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <form onSubmit={handleSubmit}>
          {/* Header */}
          <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 className="text-xl font-semibold text-gray-900">
              {editData ? "Edit" : "Tambah"} Rencana Kerja Harian
            </h2>
            <p className="text-sm text-gray-600 mt-1">
              {rencanaBulanan.namaKegiatan} - {rencanaBulanan.bulan}{" "}
              {rencanaBulanan.tahun}
            </p>
          </div>

          {/* Target Info & Progress */}
          <div className="px-6 py-4 bg-green-50 border-b border-green-200">
            <div className="flex justify-between items-center mb-2">
              <span className="text-sm font-medium text-green-900">
                Target Bulanan
              </span>
              <span className="text-lg font-bold text-green-900">
                {rencanaBulanan.targetBulanan} {rencanaBulanan.satuanPengukuran}
              </span>
            </div>
            <div className="flex justify-between items-center mb-2">
              <span className="text-sm text-green-700">Sudah Terisi</span>
              <span className="text-sm font-semibold text-green-700">
                {totalKuantitasLain} {rencanaBulanan.satuanPengukuran}
              </span>
            </div>
            <div className="flex justify-between items-center mb-3">
              <span className="text-sm font-medium text-green-900">
                Sisa Target Tersedia
              </span>
              <span
                className={`text-lg font-bold ${
                  sisaTarget > 0 ? "text-green-600" : "text-red-600"
                }`}
              >
                {sisaTarget} {rencanaBulanan.satuanPengukuran}
              </span>
            </div>

            {/* Progress Bar */}
            <div className="relative pt-1">
              <div className="flex mb-2 items-center justify-between">
                <div>
                  <span className="text-xs font-semibold inline-block text-green-600">
                    Progress Total
                  </span>
                </div>
                <div className="text-right">
                  <span className="text-xs font-semibold inline-block text-green-600">
                    {persentaseProgress}%
                  </span>
                </div>
              </div>
              <div className="overflow-hidden h-2 text-xs flex rounded bg-green-200">
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
            {/* Tanggal */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Tanggal <span className="text-red-500">*</span>
              </label>
              <input
                type="date"
                required
                value={
                  formData.tanggal instanceof Date
                    ? formData.tanggal.toISOString().split("T")[0]
                    : new Date(formData.tanggal || "").toISOString().split("T")[0]
                }
                onChange={(e) =>
                  setFormData({ ...formData, tanggal: new Date(e.target.value) })
                }
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            {/* Uraian Tugas */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Uraian Tugas <span className="text-red-500">*</span>
              </label>
              <textarea
                required
                rows={3}
                value={formData.uraianTugas}
                onChange={(e) =>
                  setFormData({ ...formData, uraianTugas: e.target.value })
                }
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Jelaskan tugas yang dikerjakan hari ini..."
              />
            </div>

            {/* Kuantitas Output */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Kuantitas Output <span className="text-red-500">*</span>
              </label>
              <div className="flex gap-2">
                <input
                  type="number"
                  required
                  min="0"
                  max={sisaTarget}
                  value={formData.kuantitasOutput}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      kuantitasOutput: parseInt(e.target.value) || 0,
                    })
                  }
                  className={`flex-1 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 ${
                    !validation.isValid
                      ? "border-red-500 focus:ring-red-500"
                      : "border-gray-300 focus:ring-blue-500"
                  }`}
                />
                <span className="flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg border border-gray-300">
                  {rencanaBulanan.satuanPengukuran}
                </span>
              </div>
              {!validation.isValid && (
                <div className="mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                  <p className="text-sm text-red-700 font-medium">
                    {validation.message}
                  </p>
                </div>
              )}
            </div>

            {/* Waktu Mulai & Selesai */}
            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Waktu Mulai <span className="text-red-500">*</span>
                </label>
                <input
                  type="time"
                  required
                  value={formData.waktuMulai}
                  onChange={(e) =>
                    setFormData({ ...formData, waktuMulai: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Waktu Selesai <span className="text-red-500">*</span>
                </label>
                <input
                  type="time"
                  required
                  value={formData.waktuSelesai}
                  onChange={(e) =>
                    setFormData({ ...formData, waktuSelesai: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Durasi
                </label>
                <div className="flex items-center h-10 px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700">
                  {formatDurasi(formData.durasiMenit || 0)}
                </div>
              </div>
            </div>

            {/* Status Pelaksanaan */}
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

            {/* Hasil Kerja */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Hasil Kerja
              </label>
              <textarea
                rows={2}
                value={formData.hasilKerja}
                onChange={(e) =>
                  setFormData({ ...formData, hasilKerja: e.target.value })
                }
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Jelaskan hasil kerja yang dicapai..."
              />
            </div>

            {/* Bukti Dokumen & Keterangan */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Bukti Dokumen (Path/URL)
                </label>
                <input
                  type="text"
                  value={formData.buktiDokumen}
                  onChange={(e) =>
                    setFormData({ ...formData, buktiDokumen: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="/uploads/dokumen.pdf"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Keterangan
                </label>
                <input
                  type="text"
                  value={formData.keterangan}
                  onChange={(e) =>
                    setFormData({ ...formData, keterangan: e.target.value })
                  }
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Catatan tambahan..."
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
