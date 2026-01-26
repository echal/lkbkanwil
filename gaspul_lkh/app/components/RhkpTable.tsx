'use client';

import { RhkPimpinan } from '@/app/types/dashboard';
import RhkpStatusBadge from './RhkpStatusBadge';

interface RhkpTableProps {
  rhkpList: RhkPimpinan[];
  onEdit: (rhkp: RhkPimpinan) => void;
  onDelete: (id: number) => void;
  onToggleStatus: (id: number, currentStatus: 'AKTIF' | 'NONAKTIF') => void;
}

export default function RhkpTable({
  rhkpList,
  onEdit,
  onDelete,
  onToggleStatus,
}: RhkpTableProps) {
  const handleDelete = (rhkp: RhkPimpinan) => {
    if (rhkp.usage_count && rhkp.usage_count > 0) {
      alert(
        `Tidak dapat menghapus RHK Pimpinan ini karena sudah digunakan oleh ${rhkp.usage_count} ASN.\n\nUbah status menjadi "Non-Aktif" jika ingin menonaktifkan.`
      );
      return;
    }

    if (confirm(`Yakin ingin menghapus RHK Pimpinan ini?\n\n"${rhkp.rencana_hasil_kerja}"`)) {
      onDelete(rhkp.id);
    }
  };

  const handleToggleStatus = (rhkp: RhkPimpinan) => {
    const newStatus = rhkp.status === 'AKTIF' ? 'NONAKTIF' : 'AKTIF';
    const action = newStatus === 'AKTIF' ? 'mengaktifkan' : 'menonaktifkan';

    if (confirm(`Yakin ingin ${action} RHK Pimpinan ini?`)) {
      onToggleStatus(rhkp.id, rhkp.status);
    }
  };

  if (rhkpList.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow p-8">
        <div className="text-center py-12">
          <svg
            className="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          <h3 className="mt-2 text-sm font-medium text-gray-900">Tidak ada RHK Pimpinan</h3>
          <p className="mt-1 text-sm text-gray-500">
            Mulai dengan menambahkan Rencana Hasil Kerja Pimpinan baru
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg shadow overflow-hidden">
      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Rencana Hasil Kerja Pimpinan
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Unit Kerja
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Digunakan
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tanggal Dibuat
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {rhkpList.map((rhkp) => (
              <tr key={rhkp.id} className="hover:bg-gray-50 transition">
                <td className="px-6 py-4">
                  <div className="text-sm text-gray-900 max-w-md line-clamp-2">
                    {rhkp.rencana_hasil_kerja}
                  </div>
                </td>
                <td className="px-6 py-4">
                  <div className="text-sm text-gray-900">
                    {rhkp.unit_kerja || '-'}
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <RhkpStatusBadge status={rhkp.status} />
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm text-gray-900">
                    {rhkp.usage_count || 0} ASN
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm text-gray-500">
                    {new Date(rhkp.created_at).toLocaleDateString('id-ID', {
                      year: 'numeric',
                      month: 'short',
                      day: 'numeric'
                    })}
                  </div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm">
                  <div className="flex flex-col space-y-2">
                    <div className="flex space-x-2">
                      <button
                        onClick={() => onEdit(rhkp)}
                        className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                      >
                        <svg
                          className="-ml-0.5 mr-1 h-4 w-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                          />
                        </svg>
                        Edit
                      </button>
                      <button
                        onClick={() => handleToggleStatus(rhkp)}
                        className={`inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white ${
                          rhkp.status === 'AKTIF'
                            ? 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500'
                            : 'bg-green-600 hover:bg-green-700 focus:ring-green-500'
                        } focus:outline-none focus:ring-2 focus:ring-offset-2 transition`}
                      >
                        <svg
                          className="-ml-0.5 mr-1 h-4 w-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          {rhkp.status === 'AKTIF' ? (
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
                            />
                          ) : (
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                          )}
                        </svg>
                        {rhkp.status === 'AKTIF' ? 'Non-Aktifkan' : 'Aktifkan'}
                      </button>
                    </div>
                    <button
                      onClick={() => handleDelete(rhkp)}
                      disabled={rhkp.usage_count ? rhkp.usage_count > 0 : false}
                      className="inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
                      title={rhkp.usage_count && rhkp.usage_count > 0 ? 'Tidak dapat dihapus karena sudah digunakan' : 'Hapus RHK Pimpinan'}
                    >
                      <svg
                        className="-ml-0.5 mr-1 h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          strokeWidth={2}
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
