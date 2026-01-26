export type WorkflowStatus = 'DRAFT' | 'DIAJUKAN' | 'DISETUJUI' | 'DIKEMBALIKAN';

export interface RencanaKerja {
  id: number;
  nama: string;
  bulan: string;
  tahun: number;
  status: WorkflowStatus;
  target?: number;
  realisasi?: number;
  catatan?: string;
  created_at: string;
  updated_at: string;
}

export interface DashboardStats {
  total_indikator?: number;
  total_asn?: number;
  capaian_persen?: number;
  total_rencana?: number;
  pending_approval?: number;
  approved?: number;
  rejected?: number;
}

export interface ApprovalAction {
  id: number;
  action: 'approve' | 'reject';
  catatan?: string;
}

// Approval System Types
export type ApprovalStatus = 'PENDING' | 'APPROVED' | 'REJECTED';

export interface LaporanKinerja {
  id: number;
  user_id: number;
  asn_name: string;
  asn_nip: string;
  periode: string;
  bulan: string;
  tahun: number;
  ringkasan_kinerja: string;
  status: ApprovalStatus;
  catatan_atasan?: string;
  approved_by?: number;
  approved_at?: string;
  created_at: string;
  updated_at: string;
}

// Indikator Kinerja Types
export type TriwulanType = 'TW1' | 'TW2' | 'TW3' | 'TW4';

export interface IndikatorKinerja {
  id: number;
  tahun: number;
  triwulan: TriwulanType;
  indikator: string;
  target: string;
  created_at: string;
  updated_at: string;
}

// RHKP (Rencana Hasil Kerja Pimpinan) Types
export type RhkpStatus = 'AKTIF' | 'NONAKTIF';
export type RhkAsnStatus = 'DRAFT' | 'DIAJUKAN' | 'DISETUJUI' | 'DIKEMBALIKAN';

export interface RhkPimpinan {
  id: number;
  rencana_hasil_kerja: string;
  unit_kerja?: string;
  status: RhkpStatus;
  usage_count?: number;
  created_by: number;
  created_at: string;
  updated_at: string;
}

export interface RhkAsn {
  id: number;
  user_id: number;
  rhk_pimpinan_id: number;
  tahun: number;
  triwulan: TriwulanType;
  rhk_pimpinan?: RhkPimpinan;
  rencana_hasil_kerja_asn: string;
  indikator_kinerja: string;
  target: string;
  realisasi?: string;
  satuan?: string;
  status: RhkAsnStatus;
  catatan_atasan?: string;
  created_at: string;
  updated_at: string;
}
