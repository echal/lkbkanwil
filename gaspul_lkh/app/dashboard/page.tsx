"use client";

import Link from "next/link";
import { useState } from "react";
import { getCurrentUser } from "../lib/mock-users";
import { UserRole } from "../lib/auth-types";

export default function DashboardPage() {
  // Simulasi role switcher untuk demo
  const [selectedRole, setSelectedRole] = useState<UserRole>(UserRole.ASN);
  const currentUser = getCurrentUser(selectedRole);

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-7xl mx-auto">
        {/* Header dengan Role Switcher */}
        <div className="mb-8">
          <div className="flex justify-between items-center mb-4">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">
                Dashboard Laporan Kinerja ASN
              </h1>
              <p className="mt-2 text-gray-600">
                Sistem manajemen kinerja berbasis role
              </p>
            </div>
            {/* Role Switcher untuk Demo */}
            <div className="text-right">
              <p className="text-sm text-gray-500 mb-2">Demo: Pilih Role</p>
              <div className="flex gap-2">
                <button
                  onClick={() => setSelectedRole(UserRole.ADMIN)}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition ${
                    selectedRole === UserRole.ADMIN
                      ? "bg-purple-600 text-white"
                      : "bg-white text-gray-700 border border-gray-300 hover:bg-gray-50"
                  }`}
                >
                  ADMIN
                </button>
                <button
                  onClick={() => setSelectedRole(UserRole.ASN)}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition ${
                    selectedRole === UserRole.ASN
                      ? "bg-green-600 text-white"
                      : "bg-white text-gray-700 border border-gray-300 hover:bg-gray-50"
                  }`}
                >
                  ASN
                </button>
              </div>
            </div>
          </div>

          {/* User Info */}
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="h-16 w-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold">
                  {currentUser?.nama.charAt(0)}
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-gray-900">
                    {currentUser?.nama}
                  </h2>
                  <p className="text-sm text-gray-500">{currentUser?.jabatan}</p>
                  {currentUser?.nip && (
                    <p className="text-xs text-gray-400">NIP: {currentUser.nip}</p>
                  )}
                </div>
              </div>
              <span
                className={`inline-flex px-4 py-2 text-sm font-semibold rounded-full ${
                  selectedRole === UserRole.ADMIN
                    ? "bg-purple-100 text-purple-800"
                    : "bg-green-100 text-green-800"
                }`}
              >
                {currentUser?.role}
              </span>
            </div>
          </div>
        </div>

        {/* Menu Berdasarkan Role */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {selectedRole === UserRole.ADMIN && (
            <>
              {/* Menu Admin */}
              <Link href="/admin/indikator-organisasi">
                <div className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-purple-600">
                  <div className="flex items-start justify-between">
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-2">
                        Manajemen Indikator Organisasi
                      </h3>
                      <p className="text-sm text-gray-600">
                        Kelola indikator kinerja organisasi yang menjadi acuan ASN
                      </p>
                      <div className="mt-4">
                        <span className="inline-flex items-center text-purple-600 text-sm font-medium">
                          Kelola Indikator →
                        </span>
                      </div>
                    </div>
                    <svg
                      className="w-8 h-8 text-purple-600"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                      />
                    </svg>
                  </div>
                </div>
              </Link>

              {/* Info untuk Admin */}
              <div className="bg-purple-50 rounded-lg shadow p-6 border border-purple-200">
                <h3 className="text-lg font-semibold text-purple-900 mb-2">
                  Role: Administrator
                </h3>
                <p className="text-sm text-purple-700 mb-4">
                  Sebagai Admin, Anda memiliki akses untuk:
                </p>
                <ul className="space-y-2 text-sm text-purple-700">
                  <li className="flex items-start gap-2">
                    <span className="text-purple-600">✓</span>
                    <span>Membuat dan mengelola Indikator Organisasi</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-purple-600">✓</span>
                    <span>Melihat daftar ASN yang menggunakan indikator</span>
                  </li>
                  <li className="flex items-start gap-2">
                    <span className="text-purple-600">✓</span>
                    <span>Monitoring capaian kinerja organisasi</span>
                  </li>
                </ul>
              </div>
            </>
          )}

          {selectedRole === UserRole.ASN && (
            <>
              {/* Menu ASN - Indikator Tahunan */}
              <Link href="/asn/indikator-tahunan">
                <div className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-green-600">
                  <div className="flex items-start justify-between">
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-2">
                        Indikator Tahunan
                      </h3>
                      <p className="text-sm text-gray-600">
                        Kelola target dan realisasi kinerja tahunan Anda
                      </p>
                      <div className="mt-4">
                        <span className="inline-flex items-center text-green-600 text-sm font-medium">
                          Kelola →
                        </span>
                      </div>
                    </div>
                    <svg
                      className="w-8 h-8 text-green-600"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                      />
                    </svg>
                  </div>
                </div>
              </Link>

              {/* Menu ASN - Indikator Triwulan */}
              <Link href="/asn/indikator-triwulan">
                <div className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-blue-600">
                  <div className="flex items-start justify-between">
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-2">
                        Indikator Triwulan
                      </h3>
                      <p className="text-sm text-gray-600">
                        Breakdown target tahunan per triwulan (3 bulan)
                      </p>
                      <div className="mt-4">
                        <span className="inline-flex items-center text-blue-600 text-sm font-medium">
                          Kelola →
                        </span>
                      </div>
                    </div>
                    <svg
                      className="w-8 h-8 text-blue-600"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                      />
                    </svg>
                  </div>
                </div>
              </Link>

              {/* Menu ASN - Rencana Bulanan */}
              <Link href="/asn/rencana-bulanan">
                <div className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-yellow-600">
                  <div className="flex items-start justify-between">
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-2">
                        Rencana Bulanan
                      </h3>
                      <p className="text-sm text-gray-600">
                        Rencana kerja dan kegiatan bulanan Anda
                      </p>
                      <div className="mt-4">
                        <span className="inline-flex items-center text-yellow-600 text-sm font-medium">
                          Kelola →
                        </span>
                      </div>
                    </div>
                    <svg
                      className="w-8 h-8 text-yellow-600"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                      />
                    </svg>
                  </div>
                </div>
              </Link>

              {/* Menu ASN - Rencana Harian */}
              <Link href="/asn/rencana-harian">
                <div className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer border-l-4 border-red-600">
                  <div className="flex items-start justify-between">
                    <div>
                      <h3 className="text-lg font-semibold text-gray-900 mb-2">
                        Rencana Harian
                      </h3>
                      <p className="text-sm text-gray-600">
                        Detail aktivitas dan laporan kinerja harian
                      </p>
                      <div className="mt-4">
                        <span className="inline-flex items-center text-red-600 text-sm font-medium">
                          Kelola →
                        </span>
                      </div>
                    </div>
                    <svg
                      className="w-8 h-8 text-red-600"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                  </div>
                </div>
              </Link>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
