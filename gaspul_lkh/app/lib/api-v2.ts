/**
 * API V2 Helper Functions
 *
 * Total Refactor - New System Architecture
 * Base URL: /api/admin/v2, /api/atasan/v2, /api/asn/v2
 */

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

// ============================================================================
// TYPES & INTERFACES
// ============================================================================

export interface MasterAtasan {
  id: number;
  asn_id: number;
  atasan_id: number;
  tahun: number;
  status: 'AKTIF' | 'NONAKTIF';
  created_at: string;
  updated_at: string;
  asn?: User;
  atasan?: User;
}

export interface User {
  id: number;
  name: string;
  nip: string;
  email?: string;
  role: string;
  status: string;
  unit_id?: number;
  unit?: Unit;
}

export interface Unit {
  id: number;
  nama_unit: string;
  kode_unit?: string;
}

export interface IndikatorKinerja {
  id: number;
  sasaran_kegiatan_id: number;
  indikator_kinerja: string;
  status: 'AKTIF' | 'NONAKTIF';
  created_at: string;
  updated_at: string;
  sasaran_kegiatan?: SasaranKegiatan;
}

export interface RhkPimpinan {
  id: number;
  indikator_kinerja_id: number;
  rhk_pimpinan: string;
  status: 'AKTIF' | 'NONAKTIF';
  created_at: string;
  updated_at: string;
  indikator_kinerja?: IndikatorKinerja;
  skp_tahunan_details_count?: number;
}

export interface SasaranKegiatan {
  id: number;
  unit_kerja: string;
  sasaran_kegiatan: string;
  status: 'AKTIF' | 'NONAKTIF';
  created_at: string;
  updated_at: string;
}

export interface SkpTahunan {
  id: number;
  user_id: number;
  tahun: number;
  status: 'DRAFT' | 'DIAJUKAN' | 'DISETUJUI' | 'DITOLAK';
  catatan_atasan?: string;
  approved_by?: number;
  approved_at?: string;
  created_at: string;
  updated_at: string;
  total_butir_kinerja?: number;
  capaian_persen?: number;
  can_add_details?: boolean;
  can_be_submitted?: boolean;
  can_edit_details?: boolean;
  details?: SkpTahunanDetail[];
  approver?: User;
}

export interface SkpTahunanDetail {
  id: number;
  skp_tahunan_id: number;
  rhk_pimpinan_id: number;
  target_tahunan: number;
  satuan: string;
  rencana_aksi: string;
  realisasi_tahunan: number;
  capaian_persen?: number;
  created_at: string;
  updated_at: string;
  rhk_pimpinan?: RhkPimpinan;
  rencana_aksi_bulanan?: RencanaAksiBulanan[];
}

export interface RencanaAksiBulanan {
  id: number;
  skp_tahunan_detail_id: number;
  bulan: number;
  tahun: number;
  rencana_aksi_bulanan?: string;
  target_bulanan: number;
  satuan_target?: string;
  realisasi_bulanan: number;
  status: 'BELUM_DIISI' | 'AKTIF' | 'SELESAI';
  bulan_nama?: string;
  capaian_persen?: number;
  created_at: string;
  updated_at: string;
  skp_tahunan_detail?: SkpTahunanDetail;
  progres_harian?: ProgresHarian[];
}

export interface ProgresHarian {
  id: number;
  rencana_aksi_bulanan_id: number | null; // Nullable untuk TUGAS_ATASAN
  tipe_progres: 'KINERJA_HARIAN' | 'TUGAS_ATASAN';
  tugas_atasan?: string; // Untuk TUGAS_ATASAN
  tanggal: string;
  jam_mulai: string;
  jam_selesai: string;
  durasi_menit?: number;
  durasi_jam?: string;
  rencana_kegiatan_harian?: string; // Optional untuk TUGAS_ATASAN
  progres?: number; // Optional untuk TUGAS_ATASAN
  satuan?: string; // Optional untuk TUGAS_ATASAN
  bukti_dukung?: string;
  status_bukti: 'BELUM_ADA' | 'SUDAH_ADA';
  keterangan?: string;
  created_at: string;
  updated_at: string;
  rencana_aksi_bulanan?: RencanaAksiBulanan;
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function getToken(): string | null {
  if (typeof window === 'undefined') return null;
  // Match the key used in AuthProvider (access_token)
  return localStorage.getItem('access_token');
}

async function fetchApi<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const token = getToken();

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...(token && { Authorization: `Bearer ${token}` }),
    ...options.headers,
  };

  try {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      ...options,
      headers,
    });

    // Try to parse response as JSON
    let responseData;
    const contentType = response.headers.get('content-type');

    if (contentType && contentType.includes('application/json')) {
      try {
        responseData = await response.json();
      } catch (jsonError) {
        // JSON parse failed
        throw new Error('Server returned invalid JSON response');
      }
    } else {
      // Not JSON response - possibly HTML error page
      const textResponse = await response.text();
      console.error('Non-JSON response:', textResponse.substring(0, 200));
      throw new Error(`Server returned non-JSON response (${response.status})`);
    }

    if (!response.ok) {
      // Create a custom error object that preserves validation errors
      const error: any = new Error(responseData?.message || `HTTP ${response.status}`);
      error.errors = responseData?.errors; // Preserve validation errors from Laravel
      error.status = response.status;
      throw error;
    }

    return responseData as T;
  } catch (error: any) {
    // Network error or other fetch errors
    if (error.message && (error.errors || error.status)) {
      // Already formatted error from above
      throw error;
    }

    // Network/fetch error
    const networkError: any = new Error(error.message || 'Network error - unable to connect to server');
    networkError.status = 0;
    throw networkError;
  }
}

// ============================================================================
// ADMIN API - MASTER ATASAN
// ============================================================================

export const masterAtasanApi = {
  // Get all master atasan
  getAll: async (params?: {
    tahun?: number;
    status?: string;
    asn_id?: number;
    atasan_id?: number;
    search?: string;
  }) => {
    const queryString = params
      ? '?' + new URLSearchParams(params as any).toString()
      : '';
    return fetchApi<{ data: MasterAtasan[] }>(`/admin/v2/master-atasan${queryString}`);
  },

  // Get detail
  getById: async (id: number) => {
    return fetchApi<MasterAtasan>(`/admin/v2/master-atasan/${id}`);
  },

  // Create
  create: async (data: {
    asn_id: number;
    atasan_id: number;
    tahun: number;
    status?: 'AKTIF' | 'NONAKTIF';
  }) => {
    return fetchApi<{ message: string; data: MasterAtasan }>('/admin/v2/master-atasan', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  // Update
  update: async (id: number, data: Partial<{
    asn_id: number;
    atasan_id: number;
    tahun: number;
    status: 'AKTIF' | 'NONAKTIF';
  }>) => {
    return fetchApi<{ message: string; data: MasterAtasan }>(`/admin/v2/master-atasan/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  },

  // Delete
  delete: async (id: number) => {
    return fetchApi<{ message: string }>(`/admin/v2/master-atasan/${id}`, {
      method: 'DELETE',
    });
  },

  // Get ASN list for dropdown
  getAsnList: async () => {
    return fetchApi<User[]>('/admin/v2/master-atasan/asn/list');
  },

  // Get Atasan list for dropdown
  getAtasanList: async () => {
    return fetchApi<User[]>('/admin/v2/master-atasan/atasan/list');
  },
};

// ============================================================================
// ATASAN API - INDIKATOR KINERJA (Read Only)
// ============================================================================

export const indikatorKinerjaAtasanApi = {
  // Get all Indikator Kinerja
  getAll: async (params?: {
    sasaran_kegiatan_id?: number;
    status?: string;
  }) => {
    const queryString = params
      ? '?' + new URLSearchParams(params as any).toString()
      : '';
    return fetchApi<{ data: IndikatorKinerja[] }>(`/atasan/v2/indikator-kinerja${queryString}`);
  },

  // Get detail
  getById: async (id: number) => {
    return fetchApi<{ data: IndikatorKinerja }>(`/atasan/v2/indikator-kinerja/${id}`);
  },
};

// ============================================================================
// ATASAN API - RHK PIMPINAN
// ============================================================================

export const rhkPimpinanApi = {
  // Get all RHK Pimpinan
  getAll: async (params?: {
    indikator_kinerja_id?: number;
    status?: string;
    search?: string;
  }) => {
    const queryString = params
      ? '?' + new URLSearchParams(params as any).toString()
      : '';
    return fetchApi<{ data: RhkPimpinan[] }>(`/atasan/v2/rhk-pimpinan${queryString}`);
  },

  // Get active RHK (for dropdown)
  getActive: async () => {
    return fetchApi<RhkPimpinan[]>('/atasan/v2/rhk-pimpinan/active/list');
  },

  // Get by Indikator Kinerja
  getByIndikator: async (indikatorKinerjaId: number) => {
    return fetchApi<RhkPimpinan[]>(`/atasan/v2/rhk-pimpinan/by-indikator/${indikatorKinerjaId}`);
  },

  // Get detail
  getById: async (id: number) => {
    return fetchApi<RhkPimpinan>(`/atasan/v2/rhk-pimpinan/${id}`);
  },

  // Create
  create: async (data: {
    indikator_kinerja_id: number;
    rhk_pimpinan: string;
    status?: 'AKTIF' | 'NONAKTIF';
  }) => {
    return fetchApi<{ message: string; data: RhkPimpinan }>('/atasan/v2/rhk-pimpinan', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  // Update
  update: async (id: number, data: Partial<{
    indikator_kinerja_id: number;
    rhk_pimpinan: string;
    status: 'AKTIF' | 'NONAKTIF';
  }>) => {
    return fetchApi<{ message: string; data: RhkPimpinan }>(`/atasan/v2/rhk-pimpinan/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  },

  // Delete
  delete: async (id: number) => {
    return fetchApi<{ message: string }>(`/atasan/v2/rhk-pimpinan/${id}`, {
      method: 'DELETE',
    });
  },
};

// ============================================================================
// ASN API - SKP TAHUNAN V2
// ============================================================================

export const skpTahunanV2Api = {
  // Get all SKP Tahunan headers
  getAll: async (params?: { tahun?: number; status?: string }) => {
    const queryString = params
      ? '?' + new URLSearchParams(params as any).toString()
      : '';
    return fetchApi<SkpTahunan[]>(`/asn/v2/skp-tahunan${queryString}`);
  },

  // Get detail with all butir kinerja
  getById: async (id: number) => {
    return fetchApi<SkpTahunan>(`/asn/v2/skp-tahunan/${id}`);
  },

  // Create or get header
  createOrGet: async (tahun: number) => {
    return fetchApi<{ message: string; data: SkpTahunan }>('/asn/v2/skp-tahunan/create-or-get', {
      method: 'POST',
      body: JSON.stringify({ tahun }),
    });
  },

  // Submit for approval
  submit: async (id: number) => {
    return fetchApi<{ message: string; data: SkpTahunan }>(`/asn/v2/skp-tahunan/${id}/submit`, {
      method: 'POST',
    });
  },

  // Add detail (butir kinerja)
  addDetail: async (skpTahunanId: number, data: {
    rhk_pimpinan_id: number;
    target_tahunan: number;
    satuan: string;
    rencana_aksi: string;
  }) => {
    return fetchApi<{ message: string; data: SkpTahunanDetail }>(
      `/asn/v2/skp-tahunan/${skpTahunanId}/detail`,
      {
        method: 'POST',
        body: JSON.stringify(data),
      }
    );
  },

  // Update detail
  updateDetail: async (skpTahunanId: number, detailId: number, data: Partial<{
    rhk_pimpinan_id: number;
    target_tahunan: number;
    satuan: string;
    rencana_aksi: string;
  }>) => {
    return fetchApi<{ message: string; data: SkpTahunanDetail }>(
      `/asn/v2/skp-tahunan/${skpTahunanId}/detail/${detailId}`,
      {
        method: 'PUT',
        body: JSON.stringify(data),
      }
    );
  },

  // Delete detail
  deleteDetail: async (skpTahunanId: number, detailId: number) => {
    return fetchApi<{ message: string }>(
      `/asn/v2/skp-tahunan/${skpTahunanId}/detail/${detailId}`,
      {
        method: 'DELETE',
      }
    );
  },

  // Get active RHK Pimpinan for dropdown (ASN access)
  getActiveRhk: async () => {
    return fetchApi<RhkPimpinan[]>('/asn/v2/rhk-pimpinan/active');
  },
};

// ============================================================================
// ASN API - RENCANA AKSI BULANAN
// ============================================================================

export const rencanaAksiBulananApi = {
  // Get all
  getAll: async (params?: {
    bulan?: number;
    tahun?: number;
    status?: string;
    skp_tahunan_detail_id?: number;
  }) => {
    const queryString = params
      ? '?' + new URLSearchParams(params as any).toString()
      : '';
    return fetchApi<RencanaAksiBulanan[]>(`/asn/v2/rencana-aksi-bulanan${queryString}`);
  },

  // Get by SKP Tahunan Detail (12 bulan)
  getByDetail: async (skpTahunanDetailId: number) => {
    return fetchApi<RencanaAksiBulanan[]>(`/asn/v2/rencana-aksi-bulanan/by-detail/${skpTahunanDetailId}`);
  },

  // Get detail
  getById: async (id: number) => {
    return fetchApi<RencanaAksiBulanan>(`/asn/v2/rencana-aksi-bulanan/${id}`);
  },

  // Update (isi rencana aksi)
  update: async (id: number, data: {
    rencana_aksi_bulanan: string;
    target_bulanan: number;
    satuan_target: string;
  }) => {
    return fetchApi<{ message: string; data: RencanaAksiBulanan }>(
      `/asn/v2/rencana-aksi-bulanan/${id}`,
      {
        method: 'PUT',
        body: JSON.stringify(data),
      }
    );
  },

  // Get summary per tahun
  getSummary: async (tahun: number) => {
    return fetchApi<any[]>(`/asn/v2/rencana-aksi-bulanan/summary/year?tahun=${tahun}`);
  },
};

// ============================================================================
// ASN API - PROGRES HARIAN
// ============================================================================

export const progresHarianApi = {
  // Get all
  getAll: async (params?: {
    tanggal?: string;
    tanggal_mulai?: string;
    tanggal_akhir?: string;
    bulan?: number;
    tahun?: number;
    rencana_aksi_bulanan_id?: number;
    status_bukti?: string;
  }) => {
    const queryString = params
      ? '?' + new URLSearchParams(params as any).toString()
      : '';
    return fetchApi<ProgresHarian[]>(`/asn/v2/progres-harian${queryString}`);
  },

  // Get by date
  getByDate: async (tanggal: string) => {
    return fetchApi<{
      tanggal: string;
      progres_list: ProgresHarian[];
      total_durasi_menit: number;
      total_durasi_jam: number;
      sisa_durasi_menit: number;
      is_full: boolean;
    }>('/asn/v2/progres-harian/by-date', {
      method: 'POST',
      body: JSON.stringify({ tanggal }),
    });
  },

  // Get calendar data
  getCalendar: async (bulan: number, tahun: number) => {
    return fetchApi<{
      bulan: number;
      tahun: number;
      calendar_data: any[];
    }>('/asn/v2/progres-harian/calendar', {
      method: 'POST',
      body: JSON.stringify({ bulan, tahun }),
    });
  },

  // Get detail
  getById: async (id: number) => {
    return fetchApi<ProgresHarian>(`/asn/v2/progres-harian/${id}`);
  },

  // Create - Dual mode: KINERJA_HARIAN atau TUGAS_ATASAN
  create: async (data: {
    tipe_progres?: 'KINERJA_HARIAN' | 'TUGAS_ATASAN';
    // KINERJA_HARIAN fields
    rencana_aksi_bulanan_id?: number;
    rencana_kegiatan_harian?: string;
    progres?: number;
    satuan?: string;
    // TUGAS_ATASAN fields
    tugas_atasan?: string;
    // Common fields
    tanggal: string;
    jam_mulai: string;
    jam_selesai: string;
    bukti_dukung: string;
    keterangan?: string;
  }) => {
    return fetchApi<{ message: string; data: ProgresHarian }>('/asn/v2/progres-harian', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  // Update
  update: async (id: number, data: Partial<{
    tanggal: string;
    jam_mulai: string;
    jam_selesai: string;
    rencana_kegiatan_harian: string;
    progres: number;
    satuan: string;
    bukti_dukung: string;
    keterangan: string;
  }>) => {
    return fetchApi<{ message: string; data: ProgresHarian }>(`/asn/v2/progres-harian/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  },

  // ✅ CRITICAL: Update HANYA bukti dukung (FAST & OPTIMIZED)
  // Gunakan endpoint ini untuk update link bukti TANPA mengubah jam/progres
  // Performance: < 50ms (vs 500ms dengan update() biasa)
  // Tidak trigger: validasi jam, hitung durasi, updateRealisasi observer
  updateBuktiDukung: async (id: number, bukti_dukung: string) => {
    return fetchApi<{ message: string; data: { id: number; bukti_dukung: string; status_bukti: string } }>(
      `/asn/v2/progres-harian/${id}/bukti-dukung`,
      {
        method: 'PUT',
        body: JSON.stringify({ bukti_dukung }),
      }
    );
  },

  // Delete
  delete: async (id: number) => {
    return fetchApi<{ message: string }>(`/asn/v2/progres-harian/${id}`, {
      method: 'DELETE',
    });
  },
};

// ============================================================================
// ATASAN - KINERJA BAWAHAN API
// ============================================================================

export interface KinerjaBawahanFilter {
  mode?: 'harian' | 'mingguan' | 'bulanan';
  tanggal?: string;
  tanggal_mulai?: string;
  tanggal_akhir?: string;
  bulan?: number;
  tahun?: number;
}

export interface KegiatanBawahan {
  id: number;
  tipe_progres: 'KINERJA_HARIAN' | 'TUGAS_ATASAN';
  tanggal: string;
  jam_mulai: string;
  jam_selesai: string;
  durasi_menit: number;
  durasi_jam: number;
  kegiatan: string;
  realisasi: string | number;
  satuan: string;
  keterangan: 'LKH' | 'TLA';
  status_bukti: 'BELUM_ADA' | 'SUDAH_ADA';
  bukti_dukung?: string;
}

export interface BawahanKinerja {
  user_id: number;
  nama: string;
  nip: string;
  jabatan: string;
  unit_kerja: string;
  status: 'Sudah Mengisi' | 'Belum Mengisi';
  total_kegiatan: number;
  total_durasi_menit: number;
  total_durasi_jam: number;
  kegiatan_list: KegiatanBawahan[];
}

export const kinerjaBawahanApi = {
  // Get biodata atasan
  getBiodata: async () => {
    return fetchApi<{
      biodata: {
        id: number;
        nama: string;
        nip: string;
        jabatan: string;
        unit_kerja: string;
        email: string;
        jumlah_bawahan: number;
        tahun_aktif: number;
      };
    }>('/atasan/v2/kinerja-bawahan/biodata');
  },

  // Get kinerja bawahan dengan filter
  getKinerjaBawahan: async (filter?: KinerjaBawahanFilter) => {
    const queryString = filter
      ? '?' + new URLSearchParams(filter as any).toString()
      : '';
    return fetchApi<{
      message: string;
      filter: {
        mode: string;
        tanggal_mulai: string;
        tanggal_akhir: string;
        bulan: number;
        tahun: number;
      };
      data: BawahanKinerja[];
      summary: {
        total_bawahan: number;
        sudah_mengisi: number;
        belum_mengisi: number;
        persentase_kepatuhan: number;
      };
    }>(`/atasan/v2/kinerja-bawahan${queryString}`);
  },

  // Cetak laporan KH
  cetakLaporanKH: async (userId: number, tanggalMulai: string, tanggalAkhir: string) => {
    return fetchApi<{
      message: string;
      data: any;
    }>(`/atasan/v2/kinerja-bawahan/cetak-kh/${userId}?tanggal_mulai=${tanggalMulai}&tanggal_akhir=${tanggalAkhir}`);
  },

  // Cetak laporan TLA
  cetakLaporanTLA: async (userId: number, tanggalMulai: string, tanggalAkhir: string) => {
    return fetchApi<{
      message: string;
      data: any;
    }>(`/atasan/v2/kinerja-bawahan/cetak-tla/${userId}?tanggal_mulai=${tanggalMulai}&tanggal_akhir=${tanggalAkhir}`);
  },
};

// ============================================================================
// LAPORAN KINERJA ASN (PERSONAL) - Self Monitoring
// ============================================================================

export interface LaporanKinerjaFilter {
  mode?: 'harian' | 'mingguan' | 'bulanan';
  tanggal?: string;
  tanggal_mulai?: string;
  tanggal_akhir?: string;
  bulan?: number;
  tahun?: number;
}

export interface KegiatanASN {
  id: number;
  tipe_progres: 'KINERJA_HARIAN' | 'TUGAS_ATASAN';
  tanggal: string;
  jam_mulai: string;
  jam_selesai: string;
  durasi_menit: number;
  durasi_jam: number;
  kegiatan: string;
  realisasi: string;
  satuan: string;
  keterangan: 'LKH' | 'TLA';
  status_bukti: string;
  bukti_dukung: string | null;
}

export interface LaporanKinerjaData {
  asn: {
    user_id: number;
    nama: string;
    nip: string;
    jabatan: string;
    unit_kerja: string;
  };
  total_kegiatan: number;
  total_durasi_menit: number;
  total_durasi_jam: number;
  kegiatan_list: KegiatanASN[];
}

export interface LaporanKinerjaSummary {
  total_kegiatan: number;
  total_kh: number;
  total_tla: number;
  total_durasi_jam: number;
}

/**
 * ✅ LAPORAN KINERJA ASN API
 * Self-monitoring dashboard untuk ASN
 * SECURITY: User ID otomatis dari token, tidak bisa diubah
 */
export const laporanKinerjaAsnApi = {
  /**
   * Get biodata ASN yang login
   */
  getBiodata: async () => {
    return fetchApi<{
      biodata: {
        id: number;
        nama: string;
        nip: string;
        jabatan: string;
        unit_kerja: string;
        email: string;
      };
    }>('/asn/v2/laporan-kinerja/biodata');
  },

  /**
   * Get laporan kinerja ASN dengan filter
   * SECURITY: User ID otomatis dari auth token
   */
  getLaporanKinerja: async (filter?: LaporanKinerjaFilter) => {
    const params = new URLSearchParams();

    if (filter?.mode) params.append('mode', filter.mode);
    if (filter?.tanggal) params.append('tanggal', filter.tanggal);
    if (filter?.tanggal_mulai) params.append('tanggal_mulai', filter.tanggal_mulai);
    if (filter?.tanggal_akhir) params.append('tanggal_akhir', filter.tanggal_akhir);
    if (filter?.bulan) params.append('bulan', filter.bulan.toString());
    if (filter?.tahun) params.append('tahun', filter.tahun.toString());

    const queryString = params.toString();
    return fetchApi<{
      message: string;
      filter: {
        mode: string;
        tanggal_mulai: string;
        tanggal_akhir: string;
        bulan: number;
        tahun: number;
      };
      data: LaporanKinerjaData;
      summary: LaporanKinerjaSummary;
    }>(`/asn/v2/laporan-kinerja${queryString ? '?' + queryString : ''}`);
  },

  /**
   * Cetak Laporan Kinerja Harian (KH) - ASN Sendiri
   * SECURITY: User ID otomatis dari auth token
   */
  cetakLaporanKH: async (tanggalMulai: string, tanggalAkhir: string) => {
    return fetchApi<{
      message: string;
      data: {
        asn: {
          nama: string;
          nip: string;
          jabatan: string;
          unit_kerja: string;
        };
        periode: {
          tanggal_mulai: string;
          tanggal_akhir: string;
        };
        progres: Array<{
          tanggal: string;
          jam_mulai: string;
          jam_selesai: string;
          durasi_menit: number;
          kegiatan: string;
          realisasi: number | null;
          satuan: string | null;
          bukti_dukung: string | null;
        }>;
        total_durasi_jam: number;
      };
    }>(`/asn/v2/laporan-kinerja/cetak-kh?tanggal_mulai=${tanggalMulai}&tanggal_akhir=${tanggalAkhir}`);
  },

  /**
   * Cetak Laporan Tugas Langsung Atasan (TLA) - ASN Sendiri
   * SECURITY: User ID otomatis dari auth token
   */
  cetakLaporanTLA: async (tanggalMulai: string, tanggalAkhir: string) => {
    return fetchApi<{
      message: string;
      data: {
        asn: {
          nama: string;
          nip: string;
          jabatan: string;
          unit_kerja: string;
        };
        periode: {
          tanggal_mulai: string;
          tanggal_akhir: string;
        };
        progres: Array<{
          tanggal: string;
          jam_mulai: string;
          jam_selesai: string;
          durasi_menit: number;
          tugas: string;
          bukti_dukung: string | null;
          keterangan: string | null;
        }>;
        total_durasi_jam: number;
      };
    }>(`/asn/v2/laporan-kinerja/cetak-tla?tanggal_mulai=${tanggalMulai}&tanggal_akhir=${tanggalAkhir}`);
  },
};
