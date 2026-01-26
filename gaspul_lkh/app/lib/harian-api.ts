/**
 * API Functions untuk Harian Management
 *
 * ASN dapat input kegiatan harian dengan bukti mandatory
 */

import { apiFetch } from './api';
import type { Harian } from './bulanan-api';

// Re-export Harian type
export type { Harian } from './bulanan-api';

// ============================================================================
// TYPES & INTERFACES
// ============================================================================

export interface CreateHarianData {
  bulanan_id: number;
  tanggal: string;
  kegiatan_harian: string;
  progres: number;
  satuan: string;
  waktu_kerja?: number;
  bukti_type: 'file' | 'link';
  bukti_file?: File;
  bukti_link?: string;
  keterangan?: string;
}

export interface UpdateHarianData {
  tanggal: string;
  kegiatan_harian: string;
  progres: number;
  satuan: string;
  waktu_kerja?: number;
  bukti_type: 'file' | 'link';
  bukti_file?: File;
  bukti_link?: string;
  keterangan?: string;
}

// ============================================================================
// API FUNCTIONS - HARIAN
// ============================================================================

/**
 * Get list of Harian for authenticated ASN
 */
export async function getHarianList(filters?: {
  bulanan_id?: number;
  start_date?: string;
  end_date?: string;
  bulan?: number;
  tahun?: number;
}): Promise<Harian[]> {
  const queryParams = new URLSearchParams();

  if (filters?.bulanan_id) {
    queryParams.append('bulanan_id', filters.bulanan_id.toString());
  }
  if (filters?.start_date) {
    queryParams.append('start_date', filters.start_date);
  }
  if (filters?.end_date) {
    queryParams.append('end_date', filters.end_date);
  }
  if (filters?.bulan) {
    queryParams.append('bulan', filters.bulan.toString());
  }
  if (filters?.tahun) {
    queryParams.append('tahun', filters.tahun.toString());
  }

  const queryString = queryParams.toString();
  const url = `/asn/harian${queryString ? `?${queryString}` : ''}`;

  const response = await apiFetch<{ success: boolean; data: Harian[] }>(url, {
    method: 'GET',
  });

  if (!response.success) {
    throw new Error('Gagal memuat Harian');
  }

  return response.data;
}

/**
 * Get detail of specific Harian
 */
export async function getHarianDetail(id: number): Promise<Harian> {
  const response = await apiFetch<{ success: boolean; data: Harian }>(
    `/asn/harian/${id}`,
    {
      method: 'GET',
    }
  );

  if (!response.success) {
    throw new Error('Gagal memuat detail Harian');
  }

  return response.data;
}

/**
 * Create new Harian entry
 * IMPORTANT: Bukti is MANDATORY (file or link)
 */
export async function createHarian(data: CreateHarianData): Promise<Harian> {
  const formData = new FormData();

  formData.append('bulanan_id', data.bulanan_id.toString());
  formData.append('tanggal', data.tanggal);
  formData.append('kegiatan_harian', data.kegiatan_harian);
  formData.append('progres', data.progres.toString());
  formData.append('satuan', data.satuan);
  formData.append('bukti_type', data.bukti_type);

  if (data.waktu_kerja !== undefined) {
    formData.append('waktu_kerja', data.waktu_kerja.toString());
  }

  if (data.bukti_type === 'file' && data.bukti_file) {
    formData.append('bukti_file', data.bukti_file);
  }

  if (data.bukti_type === 'link' && data.bukti_link) {
    formData.append('bukti_link', data.bukti_link);
  }

  if (data.keterangan) {
    formData.append('keterangan', data.keterangan);
  }

  const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/asn/harian`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
    },
    body: formData,
  });

  const result = await response.json();

  if (!result.success) {
    throw new Error(result.message || 'Gagal menambahkan Harian');
  }

  return result.data;
}

/**
 * Update Harian entry
 */
export async function updateHarian(
  id: number,
  data: UpdateHarianData
): Promise<Harian> {
  const formData = new FormData();

  formData.append('tanggal', data.tanggal);
  formData.append('kegiatan_harian', data.kegiatan_harian);
  formData.append('progres', data.progres.toString());
  formData.append('satuan', data.satuan);
  formData.append('bukti_type', data.bukti_type);
  formData.append('_method', 'PUT');

  if (data.waktu_kerja !== undefined) {
    formData.append('waktu_kerja', data.waktu_kerja.toString());
  }

  if (data.bukti_type === 'file' && data.bukti_file) {
    formData.append('bukti_file', data.bukti_file);
  }

  if (data.bukti_type === 'link' && data.bukti_link) {
    formData.append('bukti_link', data.bukti_link);
  }

  if (data.keterangan) {
    formData.append('keterangan', data.keterangan);
  }

  const response = await fetch(
    `${process.env.NEXT_PUBLIC_API_URL}/asn/harian/${id}`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('access_token')}`,
      },
      body: formData,
    }
  );

  const result = await response.json();

  if (!result.success) {
    throw new Error(result.message || 'Gagal mengupdate Harian');
  }

  return result.data;
}

/**
 * Delete Harian entry
 */
export async function deleteHarian(id: number): Promise<void> {
  const response = await apiFetch<{ success: boolean; message: string }>(
    `/asn/harian/${id}`,
    {
      method: 'DELETE',
    }
  );

  if (!response.success) {
    throw new Error(response.message || 'Gagal menghapus Harian');
  }
}
