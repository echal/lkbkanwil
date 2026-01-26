"use client";

import { useState } from "react";
import Link from "next/link";
import {
  mockIndikatorTriwulan,
  mockRencanaKerjaBulanan,
  mockRencanaKerjaHarian,
} from "../../lib/mock-data";
import {
  IndikatorTriwulan,
  RencanaKerjaBulanan,
  RencanaKerjaHarian,
} from "../../lib/types";
import { FormRencanaBulanan } from "../../components/FormRencanaBulanan";
import { FormRencanaHarian } from "../../components/FormRencanaHarian";

export default function DemoValidasiPage() {
  // State untuk mock data yang bisa diubah
  const [dataTriwulan] = useState<IndikatorTriwulan[]>(mockIndikatorTriwulan);
  const [dataBulanan, setDataBulanan] = useState<RencanaKerjaBulanan[]>(
    mockRencanaKerjaBulanan
  );
  const [dataHarian, setDataHarian] = useState<RencanaKerjaHarian[]>(
    mockRencanaKerjaHarian
  );

  // State untuk form modal
  const [showFormBulanan, setShowFormBulanan] = useState(false);
  const [showFormHarian, setShowFormHarian] = useState(false);
  const [selectedTriwulan, setSelectedTriwulan] = useState<IndikatorTriwulan | null>(
    dataTriwulan[2] // Default: Triwulan III yang masih berjalan
  );
  const [selectedBulanan, setSelectedBulanan] = useState<RencanaKerjaBulanan | null>(
    dataBulanan[2] // Default: September yang masih berjalan
  );

  // Handler submit form
  const handleSubmitBulanan = (data: Partial<RencanaKerjaBulanan>) => {
    const newBulanan: RencanaKerjaBulanan = {
      id: `rkb-${Date.now()}`,
      indikatorTriwulanId: selectedTriwulan!.id,
      ...data,
      createdAt: new Date(),
      updatedAt: new Date(),
    } as RencanaKerjaBulanan;

    setDataBulanan([...dataBulanan, newBulanan]);
    setShowFormBulanan(false);
    alert("✅ Rencana Bulanan berhasil ditambahkan!");
  };

  const handleSubmitHarian = (data: Partial<RencanaKerjaHarian>) => {
    const newHarian: RencanaKerjaHarian = {
      id: `rkh-${Date.now()}`,
      rencanaKerjaBulananId: selectedBulanan!.id,
      ...data,
      createdAt: new Date(),
      updatedAt: new Date(),
    } as RencanaKerjaHarian;

    setDataHarian([...dataHarian, newHarian]);
    setShowFormHarian(false);
    alert("✅ Rencana Harian berhasil ditambahkan!");
  };

  // Hitung statistik untuk selected triwulan
  const bulananInTriwulan = dataBulanan.filter(
    (b) => b.indikatorTriwulanId === selectedTriwulan?.id
  );
  const totalRealisasiBulanan = bulananInTriwulan.reduce(
    (sum, b) => sum + b.realisasiBulanan,
    0
  );
  const sisaTargetTriwulan = (selectedTriwulan?.targetTriwulan || 0) - totalRealisasiBulanan;

  // Hitung statistik untuk selected bulanan
  const harianInBulanan = dataHarian.filter(
    (h) => h.rencanaKerjaBulananId === selectedBulanan?.id
  );
  const totalKuantitasHarian = harianInBulanan.reduce(
    (sum, h) => sum + h.kuantitasOutput,
    0
  );
  const sisaTargetBulanan = (selectedBulanan?.targetBulanan || 0) - totalKuantitasHarian;

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <nav className="mb-4 text-sm">
            <Link href="/dashboard" className="text-blue-600 hover:underline">
              Dashboard
            </Link>
            <span className="mx-2 text-gray-500">/</span>
            <span className="text-gray-700">Demo Validasi Target</span>
          </nav>
          <h1 className="text-3xl font-bold text-gray-900">
            Demo Validasi Target vs Realisasi
          </h1>
          <p className="mt-2 text-gray-600">
            Testing sistem validasi hierarki: Triwulan → Bulanan → Harian
          </p>
        </div>

        {/* Info Box */}
        <div className="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
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
              <p className="text-sm text-blue-700">
                <span className="font-semibold">Aturan Validasi:</span>
              </p>
              <ul className="mt-2 text-sm text-blue-700 space-y-1">
                <li>• Total Realisasi Bulanan ≤ Target Triwulan</li>
                <li>• Total Kuantitas Output Harian ≤ Target Bulanan</li>
                <li>• Form akan diblok jika melebihi target tersedia</li>
              </ul>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-2 gap-6">
          {/* Panel Kiri: Validasi Bulanan */}
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <div className="px-6 py-4 bg-blue-600 text-white">
              <h2 className="text-lg font-semibold">
                Validasi Rencana Bulanan
              </h2>
              <p className="text-sm text-blue-100 mt-1">
                Test validasi: Total Bulanan ≤ Target Triwulan
              </p>
            </div>

            {/* Pilih Triwulan */}
            <div className="p-6 border-b">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Pilih Triwulan untuk Testing
              </label>
              <select
                value={selectedTriwulan?.id}
                onChange={(e) =>
                  setSelectedTriwulan(
                    dataTriwulan.find((t) => t.id === e.target.value) || null
                  )
                }
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                {dataTriwulan.map((tw) => (
                  <option key={tw.id} value={tw.id}>
                    {tw.triwulan.replace("TRIWULAN_", "Triwulan ")} - Target:{" "}
                    {tw.targetTriwulan}
                  </option>
                ))}
              </select>
            </div>

            {/* Info Target Triwulan */}
            {selectedTriwulan && (
              <div className="p-6 bg-blue-50 border-b">
                <div className="space-y-2">
                  <div className="flex justify-between">
                    <span className="text-sm text-gray-600">Target Triwulan:</span>
                    <span className="font-semibold text-gray-900">
                      {selectedTriwulan.targetTriwulan}{" "}
                      {selectedTriwulan.satuanPengukuran}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-gray-600">
                      Total Realisasi Bulanan:
                    </span>
                    <span className="font-semibold text-gray-900">
                      {totalRealisasiBulanan} {selectedTriwulan.satuanPengukuran}
                    </span>
                  </div>
                  <div className="flex justify-between pt-2 border-t">
                    <span className="text-sm font-medium text-gray-900">
                      Sisa Target:
                    </span>
                    <span
                      className={`font-bold text-lg ${
                        sisaTargetTriwulan > 0 ? "text-green-600" : "text-red-600"
                      }`}
                    >
                      {sisaTargetTriwulan} {selectedTriwulan.satuanPengukuran}
                    </span>
                  </div>
                </div>
              </div>
            )}

            {/* List Bulanan */}
            <div className="p-6">
              <h3 className="text-sm font-semibold text-gray-900 mb-3">
                Daftar Rencana Bulanan ({bulananInTriwulan.length})
              </h3>
              <div className="space-y-2 mb-4">
                {bulananInTriwulan.map((bln) => (
                  <div
                    key={bln.id}
                    className="p-3 bg-gray-50 rounded border border-gray-200"
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <p className="font-medium text-sm text-gray-900">
                          {bln.bulan}
                        </p>
                        <p className="text-xs text-gray-500">{bln.namaKegiatan}</p>
                      </div>
                      <span className="text-sm font-semibold text-blue-600">
                        {bln.realisasiBulanan} {bln.satuanPengukuran}
                      </span>
                    </div>
                  </div>
                ))}
              </div>

              <button
                onClick={() => setShowFormBulanan(true)}
                disabled={!selectedTriwulan}
                className="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:bg-gray-300 disabled:cursor-not-allowed"
              >
                + Tambah Rencana Bulanan
              </button>
            </div>
          </div>

          {/* Panel Kanan: Validasi Harian */}
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <div className="px-6 py-4 bg-green-600 text-white">
              <h2 className="text-lg font-semibold">
                Validasi Rencana Harian
              </h2>
              <p className="text-sm text-green-100 mt-1">
                Test validasi: Total Harian ≤ Target Bulanan
              </p>
            </div>

            {/* Pilih Bulanan */}
            <div className="p-6 border-b">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Pilih Rencana Bulanan untuk Testing
              </label>
              <select
                value={selectedBulanan?.id}
                onChange={(e) =>
                  setSelectedBulanan(
                    dataBulanan.find((b) => b.id === e.target.value) || null
                  )
                }
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
              >
                {dataBulanan.map((bln) => (
                  <option key={bln.id} value={bln.id}>
                    {bln.bulan} - {bln.namaKegiatan}
                  </option>
                ))}
              </select>
            </div>

            {/* Info Target Bulanan */}
            {selectedBulanan && (
              <div className="p-6 bg-green-50 border-b">
                <div className="space-y-2">
                  <div className="flex justify-between">
                    <span className="text-sm text-gray-600">Target Bulanan:</span>
                    <span className="font-semibold text-gray-900">
                      {selectedBulanan.targetBulanan}{" "}
                      {selectedBulanan.satuanPengukuran}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-sm text-gray-600">
                      Total Kuantitas Harian:
                    </span>
                    <span className="font-semibold text-gray-900">
                      {totalKuantitasHarian} {selectedBulanan.satuanPengukuran}
                    </span>
                  </div>
                  <div className="flex justify-between pt-2 border-t">
                    <span className="text-sm font-medium text-gray-900">
                      Sisa Target:
                    </span>
                    <span
                      className={`font-bold text-lg ${
                        sisaTargetBulanan > 0 ? "text-green-600" : "text-red-600"
                      }`}
                    >
                      {sisaTargetBulanan} {selectedBulanan.satuanPengukuran}
                    </span>
                  </div>
                </div>
              </div>
            )}

            {/* List Harian */}
            <div className="p-6">
              <h3 className="text-sm font-semibold text-gray-900 mb-3">
                Daftar Rencana Harian ({harianInBulanan.length})
              </h3>
              <div className="space-y-2 mb-4">
                {harianInBulanan.map((hrn) => (
                  <div
                    key={hrn.id}
                    className="p-3 bg-gray-50 rounded border border-gray-200"
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <p className="font-medium text-sm text-gray-900">
                          {new Date(hrn.tanggal).toLocaleDateString("id-ID")}
                        </p>
                        <p className="text-xs text-gray-500">{hrn.uraianTugas}</p>
                      </div>
                      <span className="text-sm font-semibold text-green-600">
                        {hrn.kuantitasOutput} {hrn.satuanOutput}
                      </span>
                    </div>
                  </div>
                ))}
              </div>

              <button
                onClick={() => setShowFormHarian(true)}
                disabled={!selectedBulanan}
                className="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:bg-gray-300 disabled:cursor-not-allowed"
              >
                + Tambah Rencana Harian
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Modal Form Bulanan */}
      {showFormBulanan && selectedTriwulan && (
        <FormRencanaBulanan
          indikatorTriwulan={selectedTriwulan}
          allBulanan={dataBulanan}
          onSubmit={handleSubmitBulanan}
          onCancel={() => setShowFormBulanan(false)}
        />
      )}

      {/* Modal Form Harian */}
      {showFormHarian && selectedBulanan && (
        <FormRencanaHarian
          rencanaBulanan={selectedBulanan}
          allHarian={dataHarian}
          onSubmit={handleSubmitHarian}
          onCancel={() => setShowFormHarian(false)}
        />
      )}
    </div>
  );
}
