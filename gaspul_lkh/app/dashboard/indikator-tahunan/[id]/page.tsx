import Link from "next/link";
import { mockIndikatorTahunan, mockIndikatorTriwulan } from "../../../lib/mock-data";
import { IndikatorTriwulan } from "../../../lib/types";

interface PageProps {
  params: Promise<{ id: string }>;
}

export default async function IndikatorTahunanDetailPage({ params }: PageProps) {
  const { id } = await params;
  const indikator = mockIndikatorTahunan.find((item) => item.id === id);

  if (!indikator) {
    return (
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-7xl mx-auto">
          <p className="text-red-600">Indikator tidak ditemukan</p>
        </div>
      </div>
    );
  }

  const listTriwulan = mockIndikatorTriwulan.filter(
    (item) => item.indikatorTahunanId === id
  );

  const persentase = Math.round(
    (indikator.realisasiTahunan / indikator.targetTahunan) * 100
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
          <span className="text-gray-700">Detail Indikator Tahunan</span>
        </nav>

        {/* Header Info */}
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">
            Indikator Kinerja Tahunan
          </h1>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm text-gray-500">NIP</p>
              <p className="font-medium text-gray-900">{indikator.nipASN}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Nama ASN</p>
              <p className="font-medium text-gray-900">{indikator.namaASN}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Jabatan</p>
              <p className="font-medium text-gray-900">{indikator.jabatan}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Unit Kerja</p>
              <p className="font-medium text-gray-900">{indikator.unitKerja}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Tahun Anggaran</p>
              <p className="font-medium text-gray-900">{indikator.tahunAnggaran}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Capaian Tahunan</p>
              <p className="font-medium">
                <span
                  className={`inline-flex px-3 py-1 text-sm font-semibold rounded-full ${
                    persentase >= 90
                      ? "bg-green-100 text-green-800"
                      : persentase >= 70
                      ? "bg-yellow-100 text-yellow-800"
                      : "bg-red-100 text-red-800"
                  }`}
                >
                  {indikator.realisasiTahunan} / {indikator.targetTahunan}{" "}
                  {indikator.satuanPengukuran} ({persentase}%)
                </span>
              </p>
            </div>
          </div>
          {indikator.keterangan && (
            <div className="mt-4 pt-4 border-t">
              <p className="text-sm text-gray-500">Keterangan</p>
              <p className="text-gray-900">{indikator.keterangan}</p>
            </div>
          )}
        </div>

        {/* Daftar Triwulan */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="px-6 py-4 border-b">
            <h2 className="text-lg font-semibold text-gray-900">
              Daftar Indikator Triwulan
            </h2>
          </div>
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Triwulan
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Target
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Realisasi
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Capaian
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Keterangan
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {listTriwulan.map((item: IndikatorTriwulan) => (
                <tr key={item.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {item.triwulan.replace("TRIWULAN_", "Triwulan ")}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {item.targetTriwulan} {item.satuanPengukuran}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {item.realisasiTriwulan} {item.satuanPengukuran}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span
                      className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        item.persentaseCapaian >= 90
                          ? "bg-green-100 text-green-800"
                          : item.persentaseCapaian >= 70
                          ? "bg-yellow-100 text-yellow-800"
                          : "bg-red-100 text-red-800"
                      }`}
                    >
                      {item.persentaseCapaian.toFixed(2)}%
                    </span>
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-500">
                    {item.keterangan}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm">
                    <Link
                      href={`/dashboard/indikator-tahunan/${id}/indikator-triwulan/${item.id}`}
                      className="text-blue-600 hover:text-blue-900 font-medium"
                    >
                      Lihat Detail →
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
