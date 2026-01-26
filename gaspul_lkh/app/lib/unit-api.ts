import { apiFetch } from './api';

// ===== TYPES =====

export interface Unit {
  id: number;
  nama_unit: string;
  kode_unit: string;
  status: 'AKTIF' | 'NONAKTIF';
  jumlah_pegawai: number;
  digunakan_pegawai: boolean;
  created_at: string;
  updated_at: string;
}

export interface CreateUnitData {
  nama_unit: string;
  kode_unit: string;
  status: 'AKTIF' | 'NONAKTIF';
}

export interface UpdateUnitData {
  nama_unit: string;
  kode_unit: string;
  status: 'AKTIF' | 'NONAKTIF';
}

// ===== UNIT API (ADMIN ONLY) =====

/**
 * Get list of all Units
 * GET /api/admin/units
 */
export async function getUnitList(): Promise<Unit[]> {
  try {
    const response = await apiFetch<{ data: Unit[] }>('/admin/units');
    return response.data;
  } catch (error) {
    console.error('Error fetching Unit list:', error);
    throw error;
  }
}

/**
 * Get single Unit by ID
 * GET /api/admin/units/{id}
 */
export async function getUnitById(id: number): Promise<Unit> {
  try {
    const response = await apiFetch<{ data: Unit }>(`/admin/units/${id}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching Unit:', error);
    throw error;
  }
}

/**
 * Create new Unit
 * POST /api/admin/units
 */
export async function createUnit(data: CreateUnitData): Promise<Unit> {
  try {
    const response = await apiFetch<{ data: Unit }>('/admin/units', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data;
  } catch (error) {
    console.error('Error creating Unit:', error);
    throw error;
  }
}

/**
 * Update existing Unit
 * PUT /api/admin/units/{id}
 */
export async function updateUnit(id: number, data: UpdateUnitData): Promise<Unit> {
  try {
    const response = await apiFetch<{ data: Unit }>(`/admin/units/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
    return response.data;
  } catch (error) {
    console.error('Error updating Unit:', error);
    throw error;
  }
}

/**
 * Toggle Unit status (AKTIF <-> NONAKTIF)
 * PATCH /api/admin/units/{id}/toggle-status
 */
export async function toggleUnitStatus(id: number): Promise<Unit> {
  try {
    const response = await apiFetch<{ data: Unit }>(`/admin/units/${id}/toggle-status`, {
      method: 'PATCH',
    });
    return response.data;
  } catch (error) {
    console.error('Error toggling Unit status:', error);
    throw error;
  }
}

/**
 * Delete Unit
 * DELETE /api/admin/units/{id}
 */
export async function deleteUnit(id: number): Promise<void> {
  try {
    await apiFetch(`/admin/units/${id}`, {
      method: 'DELETE',
    });
  } catch (error) {
    console.error('Error deleting Unit:', error);
    throw error;
  }
}
