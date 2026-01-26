import Link from "next/link";
import { RoleGuard } from "../../components/RoleGuard";
import { UserRole } from "../../lib/auth-types";
import { getCurrentUser } from "../../lib/mock-users";
import { mockIndikatorTahunan } from "../../lib/mock-data";
import { IndikatorTahunan } from "../../lib/types";

export default function IndikatorTahunanASNPage() {
  // Simulasi: Ambil current user ASN
  const currentUser = getCurrentUser(UserRole.ASN);

  // Filter indikator tahunan sesuai NIP user yang login
  const myIndikator = mockIndikatorTahunan.filter(
    (item) => item.nipASN === currentUser?.nip
  );

  return (
    <RoleGuard
      allowedRoles={[UserRole.ASN]}
      currentUserRole={currentUser?.role || null}
      fallbackMessage="Halaman ini hanya dapat diakses oleh ASN."
    >
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-7xl mx-auto">
          {/* Header */}
          <div className="mb-8">
            <nav className="mb-4 text-sm">
              <Link href="/dashboard" className="text-blue-600 hover:underline">
                Dashboard
              </Link>
              <span className="mx-2 text-gray-500">/</span>
              <span className="text-gray-700">Indikator Tahunan Saya</span>
            </nav>
            <div className="flex justify-between items-center">
              <div>
                <h1 className="text-3xl font-bold text-gray-900">
                  Indikator Kinerja Tahunan Saya
                </h1>
                <p className="mt-2 text-gray-600">
                  Kelola target dan realisasi kinerja tahunan Anda
                </p>
              </div>
              <div className="text-right">
                <p className="text-sm text-gray-500">Login sebagai:</p>
                <p className="font-semibold text-gray-900">{currentUser?.nama}</p>
                <p className="text-xs text-gray-500">{currentUser?.nip}</p>
                <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                  {currentUser?.role}
                </span>
              </div>
            </div>
          </div>

          {/* Tabel Indikator Tahunan */}
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <h2 className="text-lg font-semibold text-gray-900">
                Daftar Indikator Tahunan ({myIndikator.length})
              </h2>
            </div>
            {myIndikator.length > 0 ? (
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Tahun Anggaran
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
                  {myIndikator.map((item: IndikatorTahunan) => {
                    const persentase = Math.round(
                      (item.realisasiTahunan / item.targetTahunan) * 100
                    );
                    return (
                      <tr key={item.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                          {item.tahunAnggaran}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {item.targetTahunan} {item.satuanPengukuran}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {item.realisasiTahunan} {item.satuanPengukuran}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span
                            className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                              persentase >= 90
                                ? "bg-green-100 text-green-800"
                                : persentase >= 70
                                ? "bg-yellow-100 text-yellow-800"
                                : "bg-red-100 text-red-800"
                            }`}
                          >
                            {persentase}%
                          </span>
                        </td>
                        <td className="px-6 py-4 text-sm text-gray-500">
                          {item.keterangan}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                          <Link
                            href={`/asn/indikator-tahunan/${item.id}`}
                            className="text-blue-600 hover:text-blue-900 font-medium"
                          >
                            Kelola →
                          </Link>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            ) : (
              <div className="px-6 py-8 text-center text-gray-500">
                Belum ada indikator tahunan untuk Anda
              </div>
            )}
          </div>

          {/* Info Box */}
          <div className="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg
                  className="h-5 w-5 text-green-400"
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
                <p className="text-sm text-green-700">
                  <span className="font-semibold">Info:</span> Sebagai ASN, Anda
                  dapat mengelola Indikator Tahunan, Triwulan, Bulanan, dan Harian
                  sesuai dengan target kinerja yang telah ditetapkan.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </RoleGuard>
  );
}
