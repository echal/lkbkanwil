'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';

/**
 * DEPRECATED: This page has been moved to /asn/skp-tahunan
 * Redirecting to the new location
 */
export default function SkpTahunanListPageRedirect() {
  const router = useRouter();

  useEffect(() => {
    router.replace('/asn/skp-tahunan');
  }, [router]);

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center">
      <div className="text-center">
        <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p className="text-gray-600">Redirecting to SKP Tahunan...</p>
      </div>
    </div>
  );
}
