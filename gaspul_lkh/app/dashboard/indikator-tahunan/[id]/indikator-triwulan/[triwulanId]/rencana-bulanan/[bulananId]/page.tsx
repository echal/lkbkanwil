import Link from "next/link";
import {
  mockIndikatorTahunan,
  mockIndikatorTriwulan,
  mockRencanaKerjaBulanan,
  mockRencanaKerjaHarian,
} from "../../../../../../../lib/mock-data";
import { RencanaKerjaHarian } from "../../../../../../../lib/types";

interface PageProps {
  params: Promise<{ id: string; triwulanId: string; bulananId: string }>;
}

export default async function RencanaBulananDetailPage({ params }: PageProps) {
  const { id, triwulanId, bulananId } = await params;
  const indikatorTahunan = mockIndikatorTahunan.find((item) => item.id === id);
  const indikatorTriwulan = mockIndikatorTriwulan.find(
    (item) => item.id === triwulanId
  );
  const rencanaBulanan = mockRencanaKerjaBulanan.find(
    (item) => item.id === bulananId
  );

  if (!indikatorTahunan || !indikatorTriwulan || !rencanaBulanan) {
    return (
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-7xl mx-auto">
          <p className="text-red-600">Data tidak ditemukan</p>
        </div>
      </div>
    );
  }

  const listHarian = mockRencanaKerjaHarian.filter(
    (item) => item.rencanaKerjaBulananId === bulananId
  );

  const persentaseBulanan = Math.round(
    (rencanaBulanan.realisasiBulanan / rencanaBulanan.targetBulanan) * 100
  );

  const formatRupiah = (angka: number) => {
    return new Intl.NumberFormat("id-ID", {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0,
    }).format(angka);
  };

  const formatTanggal = (date: Date) => {
    return new Intl.DateTimeFormat("id-ID", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    }).format(date);
  };

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
          <Link
            href={`/dashboard/indikator-tahunan/${id}/indikator-triwulan/${triwulanId}`}
            className="text-blue-600 hover:underline"
          >
            {indikatorTriwulan.triwulan.replace("TRIWULAN_", "Triwulan ")}
          </Link>
          <span className="mx-2 text-gray-500">/</span>
          <span className="text-gray-700">Rencana Bulanan</span>
        </nav>

        {/* Header Info ASN */}
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
              <p className="text-gray-500">Periode</p>
              <p className="font-medium text-gray-900">
                {rencanaBulanan.bulan} {rencanaBulanan.tahun}
              </p>
            </div>
          </div>
        </div>

        {/* Header Info Rencana Bulanan */}
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">
            {rencanaBulanan.namaKegiatan}
          </h1>
          <div className="grid grid-cols-2 gap-6">
            <div>
              <p className="text-sm text-gray-500 mb-1">Deskripsi Kegiatan</p>
              <p className="text-gray-900">{rencanaBulanan.deskripsiKegiatan}</p>
            </div>
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-500">Target Bulanan:</span>
                <span className="font-medium text-gray-900">
                  {rencanaBulanan.targetBulanan} {rencanaBulanan.satuanPengukuran}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-500">Realisasi Bulanan:</span>
                <span className="font-medium text-gray-900">
                  {rencanaBulanan.realisasiBulanan} {rencanaBulanan.satuanPengukuran}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-500">Capaian:</span>
                <span
                  className={`inline-flex px-3 py-1 text-sm font-semibold rounded-full ${
                    persentaseBulanan >= 90
                      ? "bg-green-100 text-green-800"
                      : persentaseBulanan >= 70
                      ? "bg-yellow-100 text-yellow-800"
                      : "bg-red-100 text-red-800"
                  }`}
                >
                  {persentaseBulanan}%
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-500">Anggaran:</span>
                <span className="font-medium text-gray-900">
                  {formatRupiah(rencanaBulanan.anggaranKegiatan)}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-500">Status:</span>
                <span
                  className={`inline-flex px-3 py-1 text-sm font-semibold rounded-full ${
                    rencanaBulanan.statusPelaksanaan === "SELESAI"
                      ? "bg-green-100 text-green-800"
                      : rencanaBulanan.statusPelaksanaan === "SEDANG_BERJALAN"
                      ? "bg-blue-100 text-blue-800"
                      : rencanaBulanan.statusPelaksanaan === "TERLAMBAT"
                      ? "bg-red-100 text-red-800"
                      : "bg-gray-100 text-gray-800"
                  }`}
                >
                  {rencanaBulanan.statusPelaksanaan.replace("_", " ")}
                </span>
              </div>
            </div>
          </div>
          {(rencanaBulanan.hambatan || rencanaBulanan.solusi) && (
            <div className="mt-6 pt-6 border-t grid grid-cols-2 gap-4">
              <div>
                <p className="text-sm text-gray-500 mb-1">Hambatan</p>
                <p className="text-gray-900">{rencanaBulanan.hambatan}</p>
              </div>
              <div>
                <p className="text-sm text-gray-500 mb-1">Solusi</p>
                <p className="text-gray-900">{rencanaBulanan.solusi}</p>
              </div>
            </div>
          )}
        </div>

        {/* Daftar Rencana Kerja Harian */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="px-6 py-4 border-b">
            <h2 className="text-lg font-semibold text-gray-900">
              Daftar Rencana Kerja Harian
            </h2>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Tanggal
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Uraian Tugas
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Waktu
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Durasi
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Hasil Kerja
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {listHarian.map((item: RencanaKerjaHarian) => (
                  <tr key={item.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {formatTanggal(item.tanggal)}
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      <div className="max-w-md">
                        <p className="font-medium">{item.uraianTugas}</p>
                        {item.keterangan && (
                          <p className="text-xs text-gray-500 mt-1">
                            {item.keterangan}
                          </p>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {item.waktuMulai} - {item.waktuSelesai}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {Math.floor(item.durasiMenit / 60)}j {item.durasiMenit % 60}m
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
                    <td className="px-6 py-4 text-sm text-gray-900">
                      <div className="max-w-md">
                        <p>{item.hasilKerja || "-"}</p>
                        {item.buktiDokumen && (
                          <p className="text-xs text-blue-600 mt-1">
                            📎 {item.buktiDokumen.split("/").pop()}
                          </p>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
