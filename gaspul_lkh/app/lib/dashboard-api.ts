import { apiFetch } from './api';
import { RencanaKerja, DashboardStats, ApprovalAction, LaporanKinerja, IndikatorKinerja, RhkPimpinan } from '@/app/types/dashboard';

/**
 * ADMIN Dashboard APIs
 */
export async function getAdminStats(): Promise<DashboardStats> {
  return apiFetch<DashboardStats>('/admin/stats');
}

export async function getAdminAuditLog(): Promise<any[]> {
  return apiFetch<any[]>('/admin/audit-log');
}

/**
 * ASN Dashboard APIs
 */
export async function getAsnRencanaKerja(): Promise<RencanaKerja[]> {
  const response = await apiFetch<{ data: RencanaKerja[] }>('/asn/rencana-kerja');
  return response.data || [];
}

export async function getAsnStats(): Promise<DashboardStats> {
  return apiFetch<DashboardStats>('/asn/stats');
}

/**
 * ATASAN Dashboard APIs
 */
export async function getAtasanPendingApproval(): Promise<RencanaKerja[]> {
  const response = await apiFetch<{ data: RencanaKerja[] }>('/atasan/pending-approval');
  return response.data || [];
}

export async function approveRencana(data: ApprovalAction): Promise<{ message: string }> {
  return apiFetch<{ message: string }>(`/atasan/approval/${data.id}`, {
    method: 'POST',
    body: JSON.stringify({
      action: data.action,
      catatan: data.catatan,
    }),
  });
}

/**
 * Approval System APIs
 */
export async function getPendingLaporan(): Promise<LaporanKinerja[]> {
  const response = await apiFetch<{ data: LaporanKinerja[] }>('/laporan/pending');
  return response.data || [];
}

export async function approveLaporan(id: number, catatan: string): Promise<{ message: string }> {
  return apiFetch<{ message: string }>(`/laporan/${id}/approve`, {
    method: 'POST',
    body: JSON.stringify({ catatan }),
  });
}

export async function rejectLaporan(id: number, catatan: string): Promise<{ message: string }> {
  return apiFetch<{ message: string }>(`/laporan/${id}/reject`, {
    method: 'POST',
    body: JSON.stringify({ catatan }),
  });
}

/**
 * Indikator Kinerja APIs (ADMIN)
 */
export async function getIndikatorList(): Promise<IndikatorKinerja[]> {
  const response = await apiFetch<{ data: IndikatorKinerja[] }>('/indikator');
  return response.data || [];
}

export async function createIndikator(
  data: Omit<IndikatorKinerja, 'id' | 'created_at' | 'updated_at'>
): Promise<{ message: string; data: IndikatorKinerja }> {
  return apiFetch<{ message: string; data: IndikatorKinerja }>('/indikator', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function updateIndikator(
  id: number,
  data: Omit<IndikatorKinerja, 'id' | 'created_at' | 'updated_at'>
): Promise<{ message: string; data: IndikatorKinerja }> {
  return apiFetch<{ message: string; data: IndikatorKinerja }>(`/indikator/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  });
}

export async function deleteIndikator(id: number): Promise<{ message: string }> {
  return apiFetch<{ message: string }>(`/indikator/${id}`, {
    method: 'DELETE',
  });
}

/**
 * RHKP (Rencana Hasil Kerja Pimpinan) APIs (ADMIN)
 */
export async function getRhkPimpinanList(): Promise<RhkPimpinan[]> {
  const response = await apiFetch<{ data: RhkPimpinan[] }>('/rhk-pimpinan');
  return response.data || [];
}

export async function createRhkPimpinan(
  data: Omit<RhkPimpinan, 'id' | 'created_by' | 'created_at' | 'updated_at' | 'usage_count'>
): Promise<{ message: string; data: RhkPimpinan }> {
  return apiFetch<{ message: string; data: RhkPimpinan }>('/rhk-pimpinan', {
    method: 'POST',
    body: JSON.stringify(data),
  });
}

export async function updateRhkPimpinan(
  id: number,
  data: Omit<RhkPimpinan, 'id' | 'created_by' | 'created_at' | 'updated_at' | 'usage_count'>
): Promise<{ message: string; data: RhkPimpinan }> {
  return apiFetch<{ message: string; data: RhkPimpinan }>(`/rhk-pimpinan/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  });
}

export async function deleteRhkPimpinan(id: number): Promise<{ message: string }> {
  return apiFetch<{ message: string }>(`/rhk-pimpinan/${id}`, {
    method: 'DELETE',
  });
}

export async function toggleRhkPimpinanStatus(
  id: number,
  currentStatus: 'AKTIF' | 'NONAKTIF'
): Promise<{ message: string; data: RhkPimpinan }> {
  const newStatus = currentStatus === 'AKTIF' ? 'NONAKTIF' : 'AKTIF';
  return apiFetch<{ message: string; data: RhkPimpinan }>(`/rhk-pimpinan/${id}/toggle-status`, {
    method: 'PATCH',
    body: JSON.stringify({ status: newStatus }),
  });
}
