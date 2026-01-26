"use client";

import { ReactNode } from "react";
import { UserRole } from "../lib/auth-types";
import Link from "next/link";

interface RoleGuardProps {
  children: ReactNode;
  allowedRoles: UserRole[];
  currentUserRole: UserRole | null;
  fallbackMessage?: string;
}

/**
 * Component untuk proteksi konten berdasarkan role user
 * Jika user tidak memiliki akses, tampilkan pesan error
 */
export function RoleGuard({
  children,
  allowedRoles,
  currentUserRole,
  fallbackMessage = "Anda tidak memiliki akses ke halaman ini.",
}: RoleGuardProps) {
  // Cek apakah user memiliki role yang diizinkan
  const hasAccess = currentUserRole && allowedRoles.includes(currentUserRole);

  if (!hasAccess) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-8">
        <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
          <div className="mb-4">
            <svg
              className="mx-auto h-12 w-12 text-red-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Akses Ditolak</h2>
          <p className="text-gray-600 mb-6">{fallbackMessage}</p>
          <div className="space-y-2">
            <p className="text-sm text-gray-500">
              Role Anda: <span className="font-semibold">{currentUserRole || "Tidak diketahui"}</span>
            </p>
            <p className="text-sm text-gray-500">
              Role yang diperlukan:{" "}
              <span className="font-semibold">{allowedRoles.join(", ")}</span>
            </p>
          </div>
          <div className="mt-6">
            <Link
              href="/dashboard"
              className="inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
            >
              Kembali ke Dashboard
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return <>{children}</>;
}
