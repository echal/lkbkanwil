/**
 * API Functions untuk Bulanan Management
 *
 * ASN dapat mengelola target bulanan setelah SKP disetujui
 */

import { apiFetch } from './api';

// ============================================================================
// TYPES & INTERFACES
// ============================================================================

export interface Bulanan {
  id: number;
  rencana_kerja_asn_id: number;
  bulan: number;
  tahun: number;
  target_bulanan: number | null;
  rencana_kerja_bulanan: string | null;
  realisasi_bulanan: number;
  status: 'AKTIF' | 'SELESAI';
  created_at: string;
  updated_at: string;

  // Relationships
  rencana_kerja_asn?: {
    id: number;
    tahun: number;
    triwulan: string;
    target: number;
    satuan: string;
    status: string;
    sasaran_kegiatan?: {
      id: number;
      sasaran_kegiatan: string;
    };
    indikator_kinerja?: {
      id: number;
      indikator_kinerja: string;
    };
  };
  harian?: Harian[];

  // Calculated fields
  capaian_persen?: number;
  bulan_nama?: string;
  has_target_filled?: boolean;
  can_create_harian?: boolean;
}

export interface Harian {
  id: number;
  bulanan_id: number;
  tanggal: string;
  kegiatan_harian: string;
  progres: number;
  satuan: string;
  waktu_kerja: number | null;
  bukti_type: 'file' | 'link';
  bukti_path: string | null;
  bukti_link: string | null;
  keterangan: string | null;
  created_at: string;
  updated_at: string;

  // Relationships
  bulanan?: Bulanan;

  // Calculated fields
  bukti_display?: string;
  bukti_url?: string | null;
}

export interface UpdateBulananData {
  target_bulanan: number;
  rencana_kerja_bulanan: string;
}

// ============================================================================
// API FUNCTIONS - BULANAN
// ============================================================================

/**
 * Get list of Bulanan for authenticated ASN
 */
export async function getBulananList(filters?: {
  skp_id?: number;
  tahun?: number;
  bulan?: number;
  status?: 'AKTIF' | 'SELESAI';
}): Promise<Bulanan[]> {
  const queryParams = new URLSearchParams();

  if (filters?.skp_id) {
    queryParams.append('skp_id', filters.skp_id.toString());
  }
  if (filters?.tahun) {
    queryParams.append('tahun', filters.tahun.toString());
  }
  if (filters?.bulan) {
    queryParams.append('bulan', filters.bulan.toString());
  }
  if (filters?.status) {
    queryParams.append('status', filters.status);
  }

  const queryString = queryParams.toString();
  const url = `/asn/bulanan${queryString ? `?${queryString}` : ''}`;

  const response = await apiFetch<{ success: boolean; data: Bulanan[] }>(url, {
    method: 'GET',
  });

  if (!response.success) {
    throw new Error('Gagal memuat Bulanan');
  }

  return response.data;
}

/**
 * Get detail of specific Bulanan
 */
export async function getBulananDetail(id: number): Promise<Bulanan> {
  const response = await apiFetch<{ success: boolean; data: Bulanan }>(
    `/asn/bulanan/${id}`,
    {
      method: 'GET',
    }
  );

  if (!response.success) {
    throw new Error('Gagal memuat detail Bulanan');
  }

  return response.data;
}

/**
 * Get Bulanan by SKP ID
 */
export async function getBulananBySkp(skpId: number): Promise<Bulanan[]> {
  const response = await apiFetch<{ success: boolean; data: Bulanan[] }>(
    `/asn/bulanan/skp/${skpId}`,
    {
      method: 'GET',
    }
  );

  if (!response.success) {
    throw new Error('Gagal memuat Bulanan untuk SKP ini');
  }

  return response.data;
}

/**
 * Update Bulanan target and rencana kerja
 */
export async function updateBulanan(
  id: number,
  data: UpdateBulananData
): Promise<Bulanan> {
  const response = await apiFetch<{
    success: boolean;
    message: string;
    data: Bulanan;
  }>(`/asn/bulanan/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  });

  if (!response.success) {
    throw new Error(response.message || 'Gagal mengupdate Bulanan');
  }

  return response.data;
}

/**
 * Get available Bulanan for Harian creation
 * Only returns Bulanan from approved SKP with target filled
 */
export async function getAvailableBulanan(): Promise<Bulanan[]> {
  const response = await apiFetch<{ success: boolean; data: Bulanan[] }>(
    '/asn/bulanan/available',
    {
      method: 'GET',
    }
  );

  if (!response.success) {
    throw new Error('Gagal memuat Bulanan yang tersedia');
  }

  return response.data;
}
