/**
 * API Functions untuk Rencana Kerja ASN
 *
 * Rencana Kerja ASN adalah SKP Triwulan yang dibuat oleh ASN
 * dengan memilih Sasaran Kegiatan & Indikator Kinerja dari master ADMIN,
 * lalu menambahkan Tahun, Triwulan, Target, dan Realisasi mereka sendiri.
 */

import { apiFetch } from './api';

// ============================================================================
// TYPES & INTERFACES
// ============================================================================

export interface SasaranKegiatanOption {
  id: number;
  unit_kerja: string;
  sasaran_kegiatan: string;
  status: 'AKTIF' | 'NONAKTIF';
  indikator_kinerja_aktif?: IndikatorKinerjaOption[];
}

export interface IndikatorKinerjaOption {
  id: number;
  sasaran_kegiatan_id: number;
  indikator_kinerja: string;
  status: 'AKTIF' | 'NONAKTIF';
}

export interface RencanaKerjaAsn {
  id: number;
  user_id: number;
  skp_tahunan_id: number;
  skp_tahunan_detail_id: number;
  sasaran_kegiatan_id: number;
  indikator_kinerja_id: number;
  tahun: number;
  triwulan: 'I' | 'II' | 'III' | 'IV';
  target: number;
  satuan: string;
  realisasi: number;
  catatan_asn: string | null;
  status: 'DRAFT' | 'DIAJUKAN' | 'DISETUJUI' | 'DITOLAK';
  catatan_atasan: string | null;
  approved_by: number | null;
  approved_at: string | null;
  created_at: string;
  updated_at: string;

  // Relasi
  sasaran_kegiatan?: SasaranKegiatanOption;
  indikator_kinerja?: IndikatorKinerjaOption;
  skp_tahunan_detail?: {
    id: number;
    sasaran_kegiatan_id: number;
    indikator_kinerja_id: number;
    target_tahunan: number;
    satuan: string;
    sasaran_kegiatan?: SasaranKegiatanOption;
    indikator_kinerja?: IndikatorKinerjaOption;
  };
  approved_by_user?: {
    id: number;
    name: string;
  };

  // Calculated field
  capaian_persen?: number;
}

export interface CreateRencanaKerjaData {
  skp_tahunan_id: number;
  skp_tahunan_detail_id: number;
  triwulan: 'I' | 'II' | 'III' | 'IV';
  target: number;
  satuan: string;
  catatan_asn?: string;
}

export interface UpdateRencanaKerjaData {
  sasaran_kegiatan_id: number;
  indikator_kinerja_id: number;
  tahun: number;
  triwulan: 'I' | 'II' | 'III' | 'IV';
  target: number;
  satuan: string;
  realisasi?: number;
  catatan_asn?: string;
}

export interface UpdateRealisasiData {
  realisasi: number;
}

// ============================================================================
// API FUNCTIONS - MASTER DATA
// ============================================================================

/**
 * Get active Sasaran Kegiatan with their active Indikator Kinerja
 */
export async function getActiveSasaranKegiatan(): Promise<SasaranKegiatanOption[]> {
  const response = await apiFetch<{ success: boolean; data: SasaranKegiatanOption[] }>(
    '/asn/sasaran-kegiatan/active',
    {
      method: 'GET',
    }
  );

  if (!response.success) {
    throw new Error('Gagal memuat Sasaran Kegiatan');
  }

  return response.data;
}

/**
 * Get active Indikator Kinerja by Sasaran Kegiatan ID
 */
export async function getIndikatorBySasaran(sasaranId: number): Promise<IndikatorKinerjaOption[]> {
  const response = await apiFetch<{ success: boolean; data: IndikatorKinerjaOption[] }>(
    `/asn/sasaran-kegiatan/${sasaranId}/indikator`,
    {
      method: 'GET',
    }
  );

  if (!response.success) {
    throw new Error('Gagal memuat Indikator Kinerja');
  }

  return response.data;
}

// ============================================================================
// API FUNCTIONS - RENCANA KERJA CRUD
// ============================================================================

/**
 * Get all Rencana Kerja for authenticated ASN
 */
export async function getRencanaKerjaList(filters?: {
  tahun?: number;
  triwulan?: 'I' | 'II' | 'III' | 'IV';
  status?: 'DRAFT' | 'DIAJUKAN' | 'DISETUJUI' | 'DITOLAK';
}): Promise<RencanaKerjaAsn[]> {
  const queryParams = new URLSearchParams();

  if (filters?.tahun) {
    queryParams.append('tahun', filters.tahun.toString());
  }
  if (filters?.triwulan) {
    queryParams.append('triwulan', filters.triwulan);
  }
  if (filters?.status) {
    queryParams.append('status', filters.status);
  }

  const queryString = queryParams.toString();
  const url = `/asn/rencana-kerja${queryString ? `?${queryString}` : ''}`;

  const response = await apiFetch<{ success: boolean; data: RencanaKerjaAsn[] }>(url, {
    method: 'GET',
  });

  if (!response.success) {
    throw new Error('Gagal memuat Rencana Kerja');
  }

  return response.data;
}

/**
 * Get single Rencana Kerja by ID
 */
export async function getRencanaKerjaById(id: number): Promise<RencanaKerjaAsn> {
  const response = await apiFetch<{ success: boolean; data: RencanaKerjaAsn }>(
    `/asn/rencana-kerja/${id}`,
    {
      method: 'GET',
    }
  );

  if (!response.success) {
    throw new Error('Gagal memuat Rencana Kerja');
  }

  return response.data;
}

/**
 * Create new Rencana Kerja
 */
export async function createRencanaKerja(data: CreateRencanaKerjaData): Promise<RencanaKerjaAsn> {
  const response = await apiFetch<{ success: boolean; message: string; data: RencanaKerjaAsn }>(
    '/asn/rencana-kerja',
    {
      method: 'POST',
      body: JSON.stringify(data),
    }
  );

  if (!response.success) {
    throw new Error(response.message || 'Gagal menambahkan Rencana Kerja');
  }

  return response.data;
}

/**
 * Update existing Rencana Kerja
 */
export async function updateRencanaKerja(id: number, data: UpdateRencanaKerjaData): Promise<RencanaKerjaAsn> {
  const response = await apiFetch<{ success: boolean; message: string; data: RencanaKerjaAsn }>(
    `/asn/rencana-kerja/${id}`,
    {
      method: 'PUT',
      body: JSON.stringify(data),
    }
  );

  if (!response.success) {
    throw new Error(response.message || 'Gagal memperbarui Rencana Kerja');
  }

  return response.data;
}

/**
 * Delete Rencana Kerja (only DRAFT can be deleted)
 */
export async function deleteRencanaKerja(id: number): Promise<void> {
  const response = await apiFetch<{ success: boolean; message: string }>(
    `/asn/rencana-kerja/${id}`,
    {
      method: 'DELETE',
    }
  );

  if (!response.success) {
    throw new Error(response.message || 'Gagal menghapus Rencana Kerja');
  }
}

/**
 * Submit Rencana Kerja for approval (DRAFT -> DIAJUKAN)
 */
export async function submitRencanaKerja(id: number): Promise<RencanaKerjaAsn> {
  const response = await apiFetch<{ success: boolean; message: string; data: RencanaKerjaAsn }>(
    `/asn/rencana-kerja/${id}/submit`,
    {
      method: 'POST',
    }
  );

  if (!response.success) {
    throw new Error(response.message || 'Gagal mengajukan Rencana Kerja');
  }

  return response.data;
}

/**
 * Update realisasi only (for tracking progress)
 */
export async function updateRealisasi(id: number, data: UpdateRealisasiData): Promise<RencanaKerjaAsn> {
  const response = await apiFetch<{ success: boolean; message: string; data: RencanaKerjaAsn }>(
    `/asn/rencana-kerja/${id}/realisasi`,
    {
      method: 'PATCH',
      body: JSON.stringify(data),
    }
  );

  if (!response.success) {
    throw new Error(response.message || 'Gagal memperbarui realisasi');
  }

  return response.data;
}
