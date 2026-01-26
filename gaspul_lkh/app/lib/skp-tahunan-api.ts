import { apiFetch } from './api';

// ============================================================================
// HEADER-DETAIL PATTERN INTERFACES
// ============================================================================

/**
 * SKP Tahunan Detail (Butir Kinerja)
 * Setiap detail = 1 butir kinerja
 * Multiple details dapat memiliki sasaran/indikator yang sama
 */
export interface SkpTahunanDetail {
  id: number;
  skp_tahunan_id: number;
  sasaran_kegiatan_id: number;
  indikator_kinerja_id: number;
  target_tahunan: number;
  satuan: string;
  rencana_aksi?: string;
  realisasi_tahunan: number;
  sasaran_kegiatan?: { id: number; sasaran_kegiatan: string };
  indikator_kinerja?: { id: number; indikator_kinerja: string };
  skpTahunan?: SkpTahunan;
  can_edit?: boolean;
  capaian_persen?: number;
  display_name?: string;
  created_at?: string;
  updated_at?: string;
}

/**
 * SKP Tahunan Header
 * 1 Header per user per tahun
 * Contains multiple details (butir kinerja)
 */
export interface SkpTahunan {
  id: number;
  user_id: number;
  tahun: number;
  status: 'DRAFT' | 'DIAJUKAN' | 'DISETUJUI' | 'DITOLAK';
  catatan_atasan?: string;
  approved_by?: number;
  approved_at?: string;
  user?: {
    id: number;
    name: string;
    nip: string;
    email: string;
    unit?: { id: number; name: string };
  };
  approver?: { id: number; name: string; nip: string };
  details?: SkpTahunanDetail[];
  details_count?: number;
  total_butir_kinerja?: number;
  total_target?: number;
  total_realisasi?: number;
  capaian_persen?: number;
  can_add_details?: boolean;
  can_be_submitted?: boolean;
  can_edit_details?: boolean;
  display_name?: string;
  created_at?: string;
  updated_at?: string;
}

/**
 * Data untuk menambahkan butir kinerja baru
 * Setiap submit = tambah 1 butir kinerja ke header
 */
export interface CreateSkpTahunanDetailData {
  tahun: number;
  sasaran_kegiatan_id: number;
  indikator_kinerja_id: number;
  target_tahunan: number;
  satuan: string;
  rencana_aksi?: string;
}

/**
 * Data untuk mengupdate butir kinerja yang sudah ada
 */
export interface UpdateSkpTahunanDetailData {
  sasaran_kegiatan_id: number;
  indikator_kinerja_id: number;
  target_tahunan: number;
  satuan: string;
  rencana_aksi?: string;
}

// ============================================================================
// SKP TAHUNAN HEADER FUNCTIONS (ASN)
// ============================================================================

/**
 * Get list of SKP Tahunan Headers untuk ASN
 * Setiap header = 1 SKP per tahun dengan multiple butir kinerja
 */
export async function getSkpTahunanList(filters?: {
  tahun?: number;
  status?: string;
}): Promise<SkpTahunan[]> {
  const params = new URLSearchParams();
  if (filters?.tahun) params.append('tahun', filters.tahun.toString());
  if (filters?.status) params.append('status', filters.status);

  const url = '/asn/skp-tahunan' + (params.toString() ? '?' + params.toString() : '');
  const response = await apiFetch<{ success: boolean; data: SkpTahunan[] }>(url);

  if (!response.success) throw new Error('Gagal memuat SKP Tahunan');
  return response.data;
}

/**
 * Get detail of specific SKP Tahunan Header (with all details)
 */
export async function getSkpTahunanDetail(id: number): Promise<SkpTahunan> {
  const response = await apiFetch<{ success: boolean; data: SkpTahunan }>(
    '/asn/skp-tahunan/' + id
  );

  if (!response.success) throw new Error('Gagal memuat detail SKP Tahunan');
  return response.data;
}

/**
 * Submit SKP Tahunan Header for approval
 * Header must have at least 1 butir kinerja
 */
export async function submitSkpTahunan(id: number): Promise<SkpTahunan> {
  const response = await apiFetch<{ success: boolean; data: SkpTahunan; message: string }>(
    '/asn/skp-tahunan/' + id + '/submit',
    { method: 'POST' }
  );

  if (!response.success) throw new Error(response.message);
  return response.data;
}

/**
 * Get list of approved SKP Tahunan (for dropdown selection in SKP Triwulan)
 */
export async function getApprovedSkpTahunanList(): Promise<SkpTahunan[]> {
  const response = await apiFetch<{ success: boolean; data: SkpTahunan[] }>(
    '/asn/skp-tahunan/approved'
  );

  if (!response.success) throw new Error('Gagal memuat SKP Tahunan');
  return response.data;
}

// ============================================================================
// SKP TAHUNAN DETAIL FUNCTIONS (Butir Kinerja)
// ============================================================================

/**
 * Get single butir kinerja (detail) by ID for editing
 */
export async function getSingleSkpTahunanDetail(detailId: number): Promise<SkpTahunanDetail> {
  const response = await apiFetch<{ success: boolean; data: SkpTahunanDetail; message?: string }>(
    '/asn/skp-tahunan/detail/' + detailId
  );

  if (!response.success) throw new Error(response.message || 'Gagal memuat detail butir kinerja');
  return response.data;
}

/**
 * Add new butir kinerja (detail) to SKP Tahunan
 * Auto-creates header if doesn't exist
 * Can be called multiple times for the same sasaran/indikator
 */
export async function addSkpTahunanDetail(
  data: CreateSkpTahunanDetailData
): Promise<{ header: SkpTahunan; detail: SkpTahunanDetail; message: string }> {
  const response = await apiFetch<{
    success: boolean;
    data: { header: SkpTahunan; detail: SkpTahunanDetail };
    message: string;
  }>('/asn/skp-tahunan', { method: 'POST', body: JSON.stringify(data) });

  if (!response.success) throw new Error(response.message);
  return { ...response.data, message: response.message };
}

/**
 * Update specific butir kinerja (detail)
 * Only works for DRAFT or DITOLAK header status
 */
export async function updateSkpTahunanDetail(
  detailId: number,
  data: UpdateSkpTahunanDetailData
): Promise<SkpTahunanDetail> {
  const response = await apiFetch<{ success: boolean; data: SkpTahunanDetail; message: string }>(
    '/asn/skp-tahunan/detail/' + detailId,
    { method: 'PUT', body: JSON.stringify(data) }
  );

  if (!response.success) throw new Error(response.message);
  return response.data;
}

/**
 * Delete specific butir kinerja (detail)
 * Auto-deletes header if no details left
 * Only works for DRAFT or DITOLAK header status
 */
export async function deleteSkpTahunanDetail(detailId: number): Promise<void> {
  const response = await apiFetch<{ success: boolean; message: string }>(
    '/asn/skp-tahunan/detail/' + detailId,
    { method: 'DELETE' }
  );

  if (!response.success) throw new Error(response.message);
}

// ============================================================================
// APPROVAL FUNCTIONS (FOR ATASAN)
// ============================================================================

export async function getSkpTahunanForApproval(filters?: {
  status?: string;
  tahun?: number;
}): Promise<SkpTahunan[]> {
  const params = new URLSearchParams();
  if (filters?.status) params.append('status', filters.status);
  if (filters?.tahun) params.append('tahun', filters.tahun.toString());

  const url = '/atasan/skp-tahunan' + (params.toString() ? '?' + params.toString() : '');
  const response = await apiFetch<{ success: boolean; data: SkpTahunan[] }>(url);

  if (!response.success) throw new Error('Gagal memuat SKP Tahunan untuk approval');
  return response.data;
}

export async function approveSkpTahunan(id: number, catatan?: string): Promise<SkpTahunan> {
  const response = await apiFetch<{ success: boolean; data: SkpTahunan; message: string }>(
    `/atasan/skp-tahunan/${id}/approve`,
    {
      method: 'POST',
      body: JSON.stringify({ catatan_atasan: catatan })
    }
  );

  if (!response.success) throw new Error(response.message);
  return response.data;
}

export async function rejectSkpTahunan(id: number, catatan: string): Promise<SkpTahunan> {
  const response = await apiFetch<{ success: boolean; data: SkpTahunan; message: string }>(
    `/atasan/skp-tahunan/${id}/reject`,
    {
      method: 'POST',
      body: JSON.stringify({ catatan_atasan: catatan })
    }
  );

  if (!response.success) throw new Error(response.message);
  return response.data;
}

export async function getSkpTahunanDetailForApproval(id: number): Promise<SkpTahunan> {
  const response = await apiFetch<{ success: boolean; data: SkpTahunan }>(
    `/atasan/skp-tahunan/${id}`
  );

  if (!response.success) throw new Error('Gagal memuat detail SKP Tahunan');
  return response.data;
}
