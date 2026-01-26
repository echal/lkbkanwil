'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/app/providers/AuthProvider';
import IndikatorTable from '@/app/components/IndikatorTable';
import IndikatorFormModal from '@/app/components/IndikatorFormModal';
import { IndikatorKinerja, TriwulanType } from '@/app/types/dashboard';
import {
  getIndikatorList,
  createIndikator,
  updateIndikator,
  deleteIndikator,
} from '@/app/lib/dashboard-api';

export default function AdminIndikatorPage() {
  const { user, isAuthenticated, loading: authLoading, logout } = useAuth();
  const router = useRouter();
  const [indikatorList, setIndikatorList] = useState<IndikatorKinerja[]>([]);
  const [filteredList, setFilteredList] = useState<IndikatorKinerja[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Modal state
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalMode, setModalMode] = useState<'create' | 'edit'>('create');
  const [selectedIndikator, setSelectedIndikator] = useState<IndikatorKinerja | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);

  // Filter state
  const currentYear = new Date().getFullYear();
  const [filterTahun, setFilterTahun] = useState<number>(currentYear);
  const [filterTriwulan, setFilterTriwulan] = useState<string>('ALL');

  // RBAC Guard: Only ADMIN can access
  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.push('/login');
      return;
    }

    if (!authLoading && user && user.role !== 'ADMIN') {
      if (user.role === 'ASN') {
        router.push('/asn/dashboard');
      } else if (user.role === 'ATASAN') {
        router.push('/atasan/dashboard');
      } else {
        router.push('/unauthorized');
      }
      return;
    }
  }, [authLoading, isAuthenticated, user, router]);

  // Load indikator list
  useEffect(() => {
    if (user && user.role === 'ADMIN') {
      loadIndikatorList();
    }
  }, [user]);

  // Apply filters
  useEffect(() => {
    let filtered = indikatorList;

    if (filterTahun) {
      filtered = filtered.filter((item) => item.tahun === filterTahun);
    }

    if (filterTriwulan !== 'ALL') {
      filtered = filtered.filter((item) => item.triwulan === filterTriwulan);
    }

    setFilteredList(filtered);
  }, [indikatorList, filterTahun, filterTriwulan]);

  const loadIndikatorList = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await getIndikatorList();
      setIndikatorList(data);
    } catch (err: any) {
      setError(err.message || 'Gagal memuat data indikator');
      console.error('Failed to load indikator:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleCreate = () => {
    setModalMode('create');
    setSelectedIndikator(null);
    setIsModalOpen(true);
  };

  const handleEdit = (indikator: IndikatorKinerja) => {
    setModalMode('edit');
    setSelectedIndikator(indikator);
    setIsModalOpen(true);
  };

  const handleModalClose = () => {
    setIsModalOpen(false);
    setSelectedIndikator(null);
  };

  const handleModalSubmit = async (
    data: Omit<IndikatorKinerja, 'id' | 'created_at' | 'updated_at'>
  ) => {
    try {
      setIsProcessing(true);

      if (modalMode === 'create') {
        await createIndikator(data);
        alert('Indikator berhasil ditambahkan');
      } else if (modalMode === 'edit' && selectedIndikator) {
        await updateIndikator(selectedIndikator.id, data);
        alert('Indikator berhasil diperbarui');
      }

      handleModalClose();
      loadIndikatorList();
    } catch (err: any) {
      alert(err.message || 'Gagal menyimpan indikator');
    } finally {
      setIsProcessing(false);
    }
  };

  const handleDelete = async (id: number) => {
    try {
      await deleteIndikator(id);
      alert('Indikator berhasil dihapus');
      loadIndikatorList();
    } catch (err: any) {
      alert(err.message || 'Gagal menghapus indikator');
    }
  };

  // Loading state
  if (authLoading || loading) {
    return (
      <div className="min-h-screen bg-gray-50 p-8">
        <div className="max-w-7xl mx-auto">
          <div className="animate-pulse space-y-6">
            <div className="h-8 bg-gray-200 rounded w-1/4"></div>
            <div className="h-64 bg-gray-200 rounded"></div>
          </div>
        </div>
      </div>
    );
  }

  // RBAC Check
  if (!user || user.role !== 'ADMIN') {
    return null;
  }

  const yearOptions = Array.from({ length: 10 }, (_, i) => currentYear - 5 + i);

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top Navigation Bar */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            {/* User Info */}
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center">
                  <span className="text-white font-semibold text-sm">
                    {user?.name.charAt(0).toUpperCase()}
                  </span>
                </div>
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-gray-900">{user?.name}</p>
                <p className="text-xs text-gray-500">{user?.role}</p>
              </div>
            </div>

            {/* Logout Button */}
            <button
              onClick={logout}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition"
            >
              <svg
                className="-ml-1 mr-2 h-5 w-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                />
              </svg>
              Logout
            </button>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Manajemen Indikator Kinerja</h1>
              <p className="mt-2 text-sm text-gray-600">
                Kelola standar indikator kinerja per triwulan
              </p>
            </div>
            <button
              onClick={handleCreate}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
            >
              <svg
                className="-ml-1 mr-2 h-5 w-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 4v16m8-8H4"
                />
              </svg>
              Tambah Indikator
            </button>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow p-6 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Filter Tahun
              </label>
              <select
                value={filterTahun}
                onChange={(e) => setFilterTahun(parseInt(e.target.value))}
                className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                {yearOptions.map((year) => (
                  <option key={year} value={year}>
                    {year}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Filter Triwulan
              </label>
              <select
                value={filterTriwulan}
                onChange={(e) => setFilterTriwulan(e.target.value)}
                className="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="ALL">Semua Triwulan</option>
                <option value="TW1">Triwulan 1</option>
                <option value="TW2">Triwulan 2</option>
                <option value="TW3">Triwulan 3</option>
                <option value="TW4">Triwulan 4</option>
              </select>
            </div>

            <div className="flex items-end">
              <button
                onClick={loadIndikatorList}
                className="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
              >
                <svg
                  className="-ml-1 mr-2 h-5 w-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                  />
                </svg>
                Refresh
              </button>
            </div>
          </div>
        </div>

        {/* Error Alert */}
        {error && (
          <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <p className="text-sm">{error}</p>
          </div>
        )}

        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-blue-100 rounded-md p-3">
                <svg
                  className="h-6 w-6 text-blue-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                  />
                </svg>
              </div>
              <div className="ml-5">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">Total Indikator</dt>
                  <dd className="mt-1 text-3xl font-semibold text-gray-900">{filteredList.length}</dd>
                </dl>
              </div>
            </div>
          </div>

          {['TW1', 'TW2', 'TW3', 'TW4'].map((tw) => (
            <div key={tw} className="bg-white rounded-lg shadow p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-green-100 rounded-md p-3">
                  <svg
                    className="h-6 w-6 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </div>
                <div className="ml-5">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">{tw}</dt>
                    <dd className="mt-1 text-3xl font-semibold text-gray-900">
                      {filteredList.filter((item) => item.triwulan === tw).length}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Indikator Table */}
        <IndikatorTable
          indikatorList={filteredList}
          isAdmin={true}
          onEdit={handleEdit}
          onDelete={handleDelete}
        />
      </div>

      {/* Form Modal */}
      <IndikatorFormModal
        isOpen={isModalOpen}
        mode={modalMode}
        indikator={selectedIndikator}
        isProcessing={isProcessing}
        onClose={handleModalClose}
        onSubmit={handleModalSubmit}
      />
    </div>
  );
}
