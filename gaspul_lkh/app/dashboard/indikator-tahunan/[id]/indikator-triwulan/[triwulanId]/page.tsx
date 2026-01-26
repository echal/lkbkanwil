import Link from "next/link";
import {
  mockIndikatorTahunan,
  mockIndikatorTriwulan,
  mockRencanaKerjaBulanan,
} from "../../../../../lib/mock-data";
import { RencanaKerjaBulanan } from "../../../../../lib/types";

interface PageProps {
  params: Promise<{ id: string; triwulanId: string }>;
}

export default async function IndikatorTriwulanDetailPage({ params }: PageProps) {
  const { id, triwulanId } = await params;
  const indikatorTahunan = mockIndikatorTahunan.find((item) => item.id === id);
  const indikatorTriwulan = mockIndikatorTriwulan.find(
    (item) => item.id === triwulanId
  );

  if (!indikatorTahunan || !indikatorTriwulan) {
    return (
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-7xl mx-auto">
          <p className="text-red-600">Data tidak ditemukan</p>
        </div>
      </div>
    );
  }

  const listBulanan = mockRencanaKerjaBulanan.filter(
    (item) => item.indikatorTriwulanId === triwulanId
  );

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-7xl mx-auto">
        {/* Breadcrumb */}
        <nav className="mb-4 text-sm">
          <Link href="/dashboard" className="text-blue-600 hover:underline">
            Dashboard
          </Link>
          <span className="mx-2 text-gray-500">/</span>
          <Link
            href={`/dashboard/indikator-tahunan/${id}`}
            className="text-blue-600 hover:underline"
          >
            Indikator Tahunan
          </Link>
          <span className="mx-2 text-gray-500">/</span>
          <span className="text-gray-700">Indikator Triwulan</span>
        </nav>

        {/* Header Info Tahunan */}
        <div className="bg-white rounded-lg shadow p-6 mb-4">
          <h2 className="text-lg font-semibold text-gray-900 mb-3">
            Informasi ASN
          </h2>
          <div className="grid grid-cols-3 gap-4 text-sm">
            <div>
              <p className="text-gray-500">Nama</p>
              <p className="font-medium text-gray-900">
                {indikatorTahunan.namaASN}
              </p>
            </div>
            <div>
              <p className="text-gray-500">Jabatan</p>
              <p className="font-medium text-gray-900">
                {indikatorTahunan.jabatan}
              </p>
            </div>
            <div>
              <p className="text-gray-500">Tahun Anggaran</p>
              <p className="font-medium text-gray-900">
                {indikatorTahunan.tahunAnggaran}
              </p>
            </div>
          </div>
        </div>

        {/* Header Info Triwulan */}
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">
            {indikatorTriwulan.triwulan.replace("TRIWULAN_", "Indikator Triwulan ")}
          </h1>
          <div className="grid grid-cols-3 gap-4">
            <div>
              <p className="text-sm text-gray-500">Target Triwulan</p>
              <p className="text-lg font-medium text-gray-900">
                {indikatorTriwulan.targetTriwulan}{" "}
                {indikatorTriwulan.satuanPengukuran}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Realisasi Triwulan</p>
              <p className="text-lg font-medium text-gray-900">
                {indikatorTriwulan.realisasiTriwulan}{" "}
                {indikatorTriwulan.satuanPengukuran}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Persentase Capaian</p>
              <p>
                <span
                  className={`inline-flex px-3 py-1 text-sm font-semibold rounded-full ${
                    indikatorTriwulan.persentaseCapaian >= 90
                      ? "bg-green-100 text-green-800"
                      : indikatorTriwulan.persentaseCapaian >= 70
                      ? "bg-yellow-100 text-yellow-800"
                      : "bg-red-100 text-red-800"
                  }`}
                >
                  {indikatorTriwulan.persentaseCapaian.toFixed(2)}%
                </span>
              </p>
            </div>
          </div>
          {indikatorTriwulan.keterangan && (
            <div className="mt-4 pt-4 border-t">
              <p className="text-sm text-gray-500">Keterangan</p>
              <p className="text-gray-900">{indikatorTriwulan.keterangan}</p>
            </div>
          )}
        </div>

        {/* Daftar Rencana Kerja Bulanan */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="px-6 py-4 border-b">
            <h2 className="text-lg font-semibold text-gray-900">
              Daftar Rencana Kerja Bulanan
            </h2>
          </div>
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Bulan
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Nama Kegiatan
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Target
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Realisasi
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
              {listBulanan.map((item: RencanaKerjaBulanan) => {
                const persentase = Math.round(
                  (item.realisasiBulanan / item.targetBulanan) * 100
                );
                return (
                  <tr key={item.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {item.bulan}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      {item.namaKegiatan}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {item.targetBulanan} {item.satuanPengukuran}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {item.realisasiBulanan} {item.satuanPengukuran}
                      <span className="ml-2 text-xs text-gray-500">
                        ({persentase}%)
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span
                        className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                          item.statusPelaksanaan === "SELESAI"
                            ? "bg-green-100 text-green-800"
                            : item.statusPelaksanaan === "SEDANG_BERJALAN"
                            ? "bg-blue-100 text-blue-800"
                            : item.statusPelaksanaan === "TERLAMBAT"
                            ? "bg-red-100 text-red-800"
                            : "bg-gray-100 text-gray-800"
                        }`}
                      >
                        {item.statusPelaksanaan.replace("_", " ")}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                      <Link
                        href={`/dashboard/indikator-tahunan/${id}/indikator-triwulan/${triwulanId}/rencana-bulanan/${item.id}`}
                        className="text-blue-600 hover:text-blue-900 font-medium"
                      >
                        Lihat Detail →
                      </Link>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
