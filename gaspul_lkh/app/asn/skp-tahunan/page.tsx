'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';

/**
 * SKP Tahunan - Redirect to V2
 *
 * Halaman lama di-redirect ke SKP Tahunan V2 yang baru
 * V2 menggunakan struktur baru: RHK Pimpinan (bukan Sasaran Kegiatan)
 */
export default function SkpTahunanRedirect() {
  const router = useRouter();

  useEffect(() => {
    // Redirect to V2
    router.replace('/asn/skp-tahunan-v2');
  }, [router]);

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center">
      <div className="text-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
        <p className="text-gray-600">Mengalihkan ke SKP Tahunan V2...</p>
      </div>
    </div>
  );
}
