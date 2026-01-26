import { apiFetch } from './api';

// ===== TYPES =====

export interface SasaranKegiatan {
  id: number;
  unit_kerja: string;
  sasaran_kegiatan: string;
  status: 'AKTIF' | 'NONAKTIF';
  jumlah_indikator: number;
  digunakan_asn: boolean;
  created_at: string;
  updated_at: string;
}

export interface IndikatorKinerja {
  id: number;
  sasaran_kegiatan_id: number;
  sasaran_kegiatan?: {
    id: number;
    unit_kerja: string;
    sasaran_kegiatan: string;
  };
  indikator_kinerja: string;
  status: 'AKTIF' | 'NONAKTIF';
  digunakan_asn: boolean;
  created_at: string;
  updated_at: string;
}

export interface CreateSasaranKegiatanData {
  unit_kerja: string;
  sasaran_kegiatan: string;
  status: 'AKTIF' | 'NONAKTIF';
}

export interface UpdateSasaranKegiatanData {
  unit_kerja: string;
  sasaran_kegiatan: string;
  status: 'AKTIF' | 'NONAKTIF';
}

export interface CreateIndikatorKinerjaData {
  sasaran_kegiatan_id: number;
  indikator_kinerja: string;
  status: 'AKTIF' | 'NONAKTIF';
}

export interface UpdateIndikatorKinerjaData {
  sasaran_kegiatan_id: number;
  indikator_kinerja: string;
  status: 'AKTIF' | 'NONAKTIF';
}

// ===== SASARAN KEGIATAN API =====

/**
 * Get list of all Sasaran Kegiatan (Admin only)
 */
export async function getSasaranKegiatanList(): Promise<SasaranKegiatan[]> {
  try {
    const response = await apiFetch<{ data: SasaranKegiatan[] }>('/admin/sasaran-kegiatan');
    return response.data;
  } catch (error) {
    console.error('Error fetching Sasaran Kegiatan list:', error);
    throw error;
  }
}

/**
 * Get single Sasaran Kegiatan by ID (Admin only)
 */
export async function getSasaranKegiatanById(id: number): Promise<SasaranKegiatan> {
  try {
    const response = await apiFetch<{ data: SasaranKegiatan }>(`/admin/sasaran-kegiatan/${id}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching Sasaran Kegiatan:', error);
    throw error;
  }
}

/**
 * Create new Sasaran Kegiatan (Admin only)
 */
export async function createSasaranKegiatan(data: CreateSasaranKegiatanData): Promise<SasaranKegiatan> {
  try {
    const response = await apiFetch<{ data: SasaranKegiatan }>('/admin/sasaran-kegiatan', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data;
  } catch (error) {
    console.error('Error creating Sasaran Kegiatan:', error);
    throw error;
  }
}

/**
 * Update existing Sasaran Kegiatan (Admin only)
 */
export async function updateSasaranKegiatan(id: number, data: UpdateSasaranKegiatanData): Promise<SasaranKegiatan> {
  try {
    const response = await apiFetch<{ data: SasaranKegiatan }>(`/admin/sasaran-kegiatan/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
    return response.data;
  } catch (error) {
    console.error('Error updating Sasaran Kegiatan:', error);
    throw error;
  }
}

/**
 * Toggle Sasaran Kegiatan status (Admin only)
 */
export async function toggleSasaranKegiatanStatus(id: number): Promise<SasaranKegiatan> {
  try {
    const response = await apiFetch<{ data: SasaranKegiatan }>(`/admin/sasaran-kegiatan/${id}/toggle-status`, {
      method: 'PATCH',
    });
    return response.data;
  } catch (error) {
    console.error('Error toggling Sasaran Kegiatan status:', error);
    throw error;
  }
}

/**
 * Delete Sasaran Kegiatan (Admin only)
 */
export async function deleteSasaranKegiatan(id: number): Promise<void> {
  try {
    await apiFetch(`/admin/sasaran-kegiatan/${id}`, {
      method: 'DELETE',
    });
  } catch (error) {
    console.error('Error deleting Sasaran Kegiatan:', error);
    throw error;
  }
}

// ===== INDIKATOR KINERJA API =====

/**
 * Get list of Indikator Kinerja (Admin only)
 * Optionally filter by sasaran_kegiatan_id
 */
export async function getIndikatorKinerjaList(sasaranKegiatanId?: number): Promise<IndikatorKinerja[]> {
  try {
    const url = sasaranKegiatanId
      ? `/admin/indikator-kinerja?sasaran_kegiatan_id=${sasaranKegiatanId}`
      : '/admin/indikator-kinerja';

    const response = await apiFetch<{ data: IndikatorKinerja[] }>(url);
    return response.data;
  } catch (error) {
    console.error('Error fetching Indikator Kinerja list:', error);
    throw error;
  }
}

/**
 * Get single Indikator Kinerja by ID (Admin only)
 */
export async function getIndikatorKinerjaById(id: number): Promise<IndikatorKinerja> {
  try {
    const response = await apiFetch<{ data: IndikatorKinerja }>(`/admin/indikator-kinerja/${id}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching Indikator Kinerja:', error);
    throw error;
  }
}

/**
 * Create new Indikator Kinerja (Admin only)
 */
export async function createIndikatorKinerja(data: CreateIndikatorKinerjaData): Promise<IndikatorKinerja> {
  try {
    const response = await apiFetch<{ data: IndikatorKinerja }>('/admin/indikator-kinerja', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data;
  } catch (error) {
    console.error('Error creating Indikator Kinerja:', error);
    throw error;
  }
}

/**
 * Update existing Indikator Kinerja (Admin only)
 */
export async function updateIndikatorKinerja(id: number, data: UpdateIndikatorKinerjaData): Promise<IndikatorKinerja> {
  try {
    const response = await apiFetch<{ data: IndikatorKinerja }>(`/admin/indikator-kinerja/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
    return response.data;
  } catch (error) {
    console.error('Error updating Indikator Kinerja:', error);
    throw error;
  }
}

/**
 * Toggle Indikator Kinerja status (Admin only)
 */
export async function toggleIndikatorKinerjaStatus(id: number): Promise<IndikatorKinerja> {
  try {
    const response = await apiFetch<{ data: IndikatorKinerja }>(`/admin/indikator-kinerja/${id}/toggle-status`, {
      method: 'PATCH',
    });
    return response.data;
  } catch (error) {
    console.error('Error toggling Indikator Kinerja status:', error);
    throw error;
  }
}

/**
 * Delete Indikator Kinerja (Admin only)
 */
export async function deleteIndikatorKinerja(id: number): Promise<void> {
  try {
    await apiFetch(`/admin/indikator-kinerja/${id}`, {
      method: 'DELETE',
    });
  } catch (error) {
    console.error('Error deleting Indikator Kinerja:', error);
    throw error;
  }
}

// ===== ASN API (untuk memilih Sasaran & Indikator) =====

/**
 * Get list of Sasaran Kegiatan berdasarkan unit kerja ASN (NEW - UNIT KERJA FILTERED)
 * Hanya menampilkan sasaran yang AKTIF dan sesuai unit kerja ASN yang login
 */
export async function getSasaranKegiatanByUnitKerja(): Promise<{ unit_kerja: string; data: SasaranKegiatan[] }> {
  try {
    const response = await apiFetch<{ unit_kerja: string; data: SasaranKegiatan[] }>('/asn/sasaran-kegiatan');
    return response;
  } catch (error) {
    console.error('Error fetching Sasaran Kegiatan by unit kerja:', error);
    throw error;
  }
}

/**
 * Get list of Indikator Kinerja for a specific Sasaran Kegiatan (NEW - WITH UNIT KERJA VALIDATION)
 */
export async function getIndikatorKinerjaBySasaran(sasaranKegiatanId: number): Promise<IndikatorKinerja[]> {
  try {
    const response = await apiFetch<{ data: IndikatorKinerja[] }>(
      `/asn/indikator-kinerja?sasaran_kegiatan_id=${sasaranKegiatanId}`
    );
    return response.data;
  } catch (error) {
    console.error('Error fetching Indikator Kinerja:', error);
    throw error;
  }
}

/**
 * Get list of active Sasaran Kegiatan for ASN selection (DEPRECATED - use getSasaranKegiatanByUnitKerja)
 */
export async function getActiveSasaranKegiatanList(): Promise<SasaranKegiatan[]> {
  try {
    const response = await apiFetch<{ data: SasaranKegiatan[] }>('/sasaran-kegiatan/active');
    return response.data;
  } catch (error) {
    console.error('Error fetching active Sasaran Kegiatan list:', error);
    throw error;
  }
}

/**
 * Get list of active Indikator Kinerja for a specific Sasaran Kegiatan (DEPRECATED)
 */
export async function getActiveIndikatorKinerjaList(sasaranKegiatanId: number): Promise<IndikatorKinerja[]> {
  try {
    const response = await apiFetch<{ data: IndikatorKinerja[] }>(
      `/sasaran-kegiatan/${sasaranKegiatanId}/indikator-kinerja/active`
    );
    return response.data;
  } catch (error) {
    console.error('Error fetching active Indikator Kinerja list:', error);
    throw error;
  }
}
