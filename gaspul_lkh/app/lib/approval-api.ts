/**
 * API Functions untuk Atasan Approval
 *
 * Atasan dapat melihat dan meng-approve/menolak Rencana Kerja ASN
 */

import { apiFetch } from './api';
import { RencanaKerjaAsn } from './rencana-kerja-api';

// ============================================================================
// TYPES & INTERFACES
// ============================================================================

export interface ApprovalStats {
  total_pending: number;
  total_approved: number;
  total_rejected: number;
  total_all: number;
}

export interface ApproveData {
  catatan_atasan?: string;
}

export interface RejectData {
  catatan_atasan: string;
}

// ============================================================================
// API FUNCTIONS
// ============================================================================

/**
 * Get statistics for Atasan dashboard
 */
export async function getApprovalStats(): Promise<ApprovalStats> {
  const response = await apiFetch<{ success: boolean; data: ApprovalStats }>(
    '/atasan/stats',
    {
      method: 'GET',
    }
  );

  if (!response.success) {
    throw new Error('Gagal memuat statistik');
  }

  return response.data;
}

/**
 * Get list of Rencana Kerja for approval
 */
export async function getRencanaKerjaForApproval(filters?: {
  status?: 'DRAFT' | 'DIAJUKAN' | 'DISETUJUI' | 'DITOLAK';
  tahun?: number;
  triwulan?: 'I' | 'II' | 'III' | 'IV';
  user_id?: number;
}): Promise<RencanaKerjaAsn[]> {
  const queryParams = new URLSearchParams();

  if (filters?.status) {
    queryParams.append('status', filters.status);
  }
  if (filters?.tahun) {
    queryParams.append('tahun', filters.tahun.toString());
  }
  if (filters?.triwulan) {
    queryParams.append('triwulan', filters.triwulan);
  }
  if (filters?.user_id) {
    queryParams.append('user_id', filters.user_id.toString());
  }

  const queryString = queryParams.toString();
  const url = `/atasan/rencana-kerja${queryString ? `?${queryString}` : ''}`;

  const response = await apiFetch<{ success: boolean; data: RencanaKerjaAsn[] }>(url, {
    method: 'GET',
  });

  if (!response.success) {
    throw new Error('Gagal memuat Rencana Kerja');
  }

  return response.data;
}

/**
 * Get detail of specific Rencana Kerja
 */
export async function getRencanaKerjaDetail(id: number): Promise<RencanaKerjaAsn> {
  const response = await apiFetch<{ success: boolean; data: RencanaKerjaAsn }>(
    `/atasan/rencana-kerja/${id}`,
    {
      method: 'GET',
    }
  );

  if (!response.success) {
    throw new Error('Gagal memuat detail Rencana Kerja');
  }

  return response.data;
}

/**
 * Approve Rencana Kerja
 */
export async function approveRencanaKerja(id: number, data: ApproveData): Promise<RencanaKerjaAsn> {
  const response = await apiFetch<{ success: boolean; message: string; data: RencanaKerjaAsn }>(
    `/atasan/rencana-kerja/${id}/approve`,
    {
      method: 'POST',
      body: JSON.stringify(data),
    }
  );

  if (!response.success) {
    throw new Error(response.message || 'Gagal menyetujui Rencana Kerja');
  }

  return response.data;
}

/**
 * Reject Rencana Kerja
 */
export async function rejectRencanaKerja(id: number, data: RejectData): Promise<RencanaKerjaAsn> {
  const response = await apiFetch<{ success: boolean; message: string; data: RencanaKerjaAsn }>(
    `/atasan/rencana-kerja/${id}/reject`,
    {
      method: 'POST',
      body: JSON.stringify(data),
    }
  );

  if (!response.success) {
    throw new Error(response.message || 'Gagal menolak Rencana Kerja');
  }

  return response.data;
}
