import { apiFetch } from './api';

// ===== TYPES =====

export interface UserProfile {
  id: number;
  name: string;
  nip: string;
  email: string;
  role: 'ASN' | 'ATASAN' | 'ADMIN';
  unit_id: number | null;
  unit_name: string | null;
  unit_kode: string | null;
  jabatan: string | null;
  status: 'AKTIF' | 'NONAKTIF';
  created_at: string;
  updated_at: string;
}

// ===== PROFILE API (ASN - READ ONLY) =====

/**
 * Get authenticated user's profile (READ ONLY)
 * GET /api/asn/profile
 */
export async function getProfile(): Promise<{ data: UserProfile; message: string }> {
  try {
    const response = await apiFetch<{ data: UserProfile; message: string }>('/asn/profile');
    return response;
  } catch (error) {
    console.error('Error fetching profile:', error);
    throw error;
  }
}
