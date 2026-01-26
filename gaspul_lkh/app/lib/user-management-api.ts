import { apiFetch } from './api';

// ===== TYPES =====

export interface UserManagement {
  id: number;
  name: string;
  nip: string;
  email: string;
  role: 'ASN' | 'ATASAN' | 'ADMIN';
  unit_id: number | null;
  unit_name: string | null;
  jabatan: string | null;
  status: 'AKTIF' | 'NONAKTIF';
  created_at: string;
  updated_at: string;
}

export interface CreateUserData {
  name: string;
  nip: string;
  email: string;
  password: string;
  role: 'ASN' | 'ATASAN' | 'ADMIN';
  unit_id: number;
  jabatan?: string;
  status: 'AKTIF' | 'NONAKTIF';
}

export interface UpdateUserData {
  name: string;
  nip: string;
  email: string;
  role: 'ASN' | 'ATASAN' | 'ADMIN';
  unit_id: number;
  jabatan?: string;
  status: 'AKTIF' | 'NONAKTIF';
}

export interface ResetPasswordData {
  password: string;
}

// ===== USER MANAGEMENT API (ADMIN ONLY) =====

/**
 * Get list of all Users (Master Pegawai)
 * GET /api/admin/users
 * Optional query params: role, unit_id, status
 */
export async function getUserList(filters?: {
  role?: string;
  unit_id?: number;
  status?: string;
}): Promise<UserManagement[]> {
  try {
    let url = '/admin/users';
    const params = new URLSearchParams();

    if (filters?.role) params.append('role', filters.role);
    if (filters?.unit_id) params.append('unit_id', filters.unit_id.toString());
    if (filters?.status) params.append('status', filters.status);

    if (params.toString()) {
      url += '?' + params.toString();
    }

    const response = await apiFetch<{ data: UserManagement[] }>(url);
    return response.data;
  } catch (error) {
    console.error('Error fetching User list:', error);
    throw error;
  }
}

/**
 * Get single User by ID
 * GET /api/admin/users/{id}
 */
export async function getUserById(id: number): Promise<UserManagement> {
  try {
    const response = await apiFetch<{ data: UserManagement }>(`/admin/users/${id}`);
    return response.data;
  } catch (error) {
    console.error('Error fetching User:', error);
    throw error;
  }
}

/**
 * Create new User (Pegawai)
 * POST /api/admin/users
 */
export async function createUser(data: CreateUserData): Promise<UserManagement> {
  try {
    const response = await apiFetch<{ data: UserManagement }>('/admin/users', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data;
  } catch (error) {
    console.error('Error creating User:', error);
    throw error;
  }
}

/**
 * Update existing User
 * PUT /api/admin/users/{id}
 */
export async function updateUser(id: number, data: UpdateUserData): Promise<UserManagement> {
  try {
    const response = await apiFetch<{ data: UserManagement }>(`/admin/users/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
    return response.data;
  } catch (error) {
    console.error('Error updating User:', error);
    throw error;
  }
}

/**
 * Reset password for User
 * PATCH /api/admin/users/{id}/reset-password
 */
export async function resetUserPassword(id: number, data: ResetPasswordData): Promise<void> {
  try {
    await apiFetch(`/admin/users/${id}/reset-password`, {
      method: 'PATCH',
      body: JSON.stringify(data),
    });
  } catch (error) {
    console.error('Error resetting User password:', error);
    throw error;
  }
}

/**
 * Toggle User status (AKTIF <-> NONAKTIF)
 * PATCH /api/admin/users/{id}/toggle-status
 */
export async function toggleUserStatus(id: number): Promise<{ id: number; status: string }> {
  try {
    const response = await apiFetch<{ data: { id: number; status: string } }>(
      `/admin/users/${id}/toggle-status`,
      { method: 'PATCH' }
    );
    return response.data;
  } catch (error) {
    console.error('Error toggling User status:', error);
    throw error;
  }
}

/**
 * Delete User
 * DELETE /api/admin/users/{id}
 */
export async function deleteUser(id: number): Promise<void> {
  try {
    await apiFetch(`/admin/users/${id}`, {
      method: 'DELETE',
    });
  } catch (error) {
    console.error('Error deleting User:', error);
    throw error;
  }
}
