/**
 * Enum untuk periode triwulan dalam satu tahun
 */
export enum Triwulan {
  I = "TRIWULAN_I",
  II = "TRIWULAN_II",
  III = "TRIWULAN_III",
  IV = "TRIWULAN_IV",
}

/**
 * Enum untuk bulan dalam satu tahun
 */
export enum Bulan {
  JANUARI = "JANUARI",
  FEBRUARI = "FEBRUARI",
  MARET = "MARET",
  APRIL = "APRIL",
  MEI = "MEI",
  JUNI = "JUNI",
  JULI = "JULI",
  AGUSTUS = "AGUSTUS",
  SEPTEMBER = "SEPTEMBER",
  OKTOBER = "OKTOBER",
  NOVEMBER = "NOVEMBER",
  DESEMBER = "DESEMBER",
}

/**
 * Enum untuk status pelaksanaan pekerjaan
 */
export enum StatusPekerjaan {
  BELUM_DIMULAI = "BELUM_DIMULAI",
  SEDANG_BERJALAN = "SEDANG_BERJALAN",
  SELESAI = "SELESAI",
  TERLAMBAT = "TERLAMBAT",
  DIBATALKAN = "DIBATALKAN",
}

/**
 * Enum untuk satuan pengukuran kinerja
 */
export enum SatuanPengukuran {
  DOKUMEN = "DOKUMEN",
  KEGIATAN = "KEGIATAN",
  LAPORAN = "LAPORAN",
  PERSENTASE = "PERSENTASE",
  ORANG = "ORANG",
  UNIT = "UNIT",
  PAKET = "PAKET",
}

/**
 * Enum untuk status persetujuan
 */
export enum StatusPersetujuan {
  DRAFT = "DRAFT",
  DIAJUKAN = "DIAJUKAN",
  DISETUJUI = "DISETUJUI",
  DIKEMBALIKAN = "DIKEMBALIKAN",
}

/**
 * Interface untuk audit log persetujuan
 */
export interface ApprovalLog {
  timestamp: Date;
  action: StatusPersetujuan;
  userId: string;
  userName: string;
  userRole: string;
  catatan?: string;
}

/**
 * Interface untuk Indikator Kinerja Organisasi
 * Dibuat oleh Admin, menjadi acuan kinerja tahunan seluruh ASN
 */
export interface IndikatorOrganisasi {
  id: string;
  kodeIndikator: string; // Contoh: IKO-2024-001
  namaIndikator: string;
  deskripsi: string;
  tahunAnggaran: number;
  targetOrganisasi: number;
  satuanPengukuran: SatuanPengukuran;
  unitPenanggungJawab: string; // Nama unit organisasi/dinas
  createdAt: Date;
  updatedAt: Date;
}

/**
 * Interface untuk Indikator Kinerja Tahunan ASN
 * Turunan dari Indikator Organisasi, berlaku untuk 1 tahun anggaran
 */
export interface IndikatorTahunan {
  id: string;
  indikatorOrganisasiId: string; // Relasi ke IndikatorOrganisasi
  nipASN: string; // Nomor Induk Pegawai
  namaASN: string;
  jabatan: string;
  unitKerja: string;
  tahunAnggaran: number;
  targetTahunan: number;
  realisasiTahunan: number;
  satuanPengukuran: SatuanPengukuran;
  keterangan: string;
  createdAt: Date;
  updatedAt: Date;
}

/**
 * Interface untuk Indikator Kinerja Triwulanan ASN
 * Breakdown dari target tahunan per triwulan (3 bulan)
 */
export interface IndikatorTriwulan {
  id: string;
  indikatorTahunanId: string; // Relasi ke IndikatorTahunan
  triwulan: Triwulan;
  tahunAnggaran: number;
  targetTriwulan: number;
  realisasiTriwulan: number;
  satuanPengukuran: SatuanPengukuran;
  persentaseCapaian: number;
  keterangan: string;
  createdAt: Date;
  updatedAt: Date;
}

/**
 * Interface untuk Rencana Kerja Bulanan ASN
 * Breakdown dari target triwulan per bulan
 */
export interface RencanaKerjaBulanan {
  id: string;
  indikatorTriwulanId: string; // Relasi ke IndikatorTriwulan
  bulan: Bulan;
  tahun: number;
  namaKegiatan: string;
  deskripsiKegiatan: string;
  targetBulanan: number;
  realisasiBulanan: number;
  satuanPengukuran: SatuanPengukuran;
  anggaranKegiatan: number; // Dalam rupiah
  statusPelaksanaan: StatusPekerjaan;
  hambatan: string;
  solusi: string;
  statusPersetujuan: StatusPersetujuan;
  approvalLogs: ApprovalLog[];
  createdAt: Date;
  updatedAt: Date;
  submittedAt?: Date;
  approvedAt?: Date;
}

/**
 * Interface untuk Rencana Kerja Harian ASN
 * Detail aktivitas harian untuk mencapai target bulanan
 */
export interface RencanaKerjaHarian {
  id: string;
  rencanaKerjaBulananId: string; // Relasi ke RencanaKerjaBulanan
  tanggal: Date;
  uraianTugas: string;
  kuantitasOutput: number;
  satuanOutput: SatuanPengukuran;
  waktuMulai: string; // Format: "HH:mm"
  waktuSelesai: string; // Format: "HH:mm"
  durasiMenit: number;
  statusPelaksanaan: StatusPekerjaan;
  hasilKerja: string;
  buktiDokumen: string; // Path/URL ke file bukti
  keterangan: string;
  statusPersetujuan: StatusPersetujuan;
  approvalLogs: ApprovalLog[];
  createdAt: Date;
  updatedAt: Date;
  submittedAt?: Date;
  approvedAt?: Date;
}

/**
 * Interface helper untuk laporan agregat kinerja
 * Digunakan untuk dashboard dan pelaporan
 */
export interface LaporanKinerjaASN {
  nipASN: string;
  namaASN: string;
  periode: {
    tahun: number;
    triwulan?: Triwulan;
    bulan?: Bulan;
  };
  totalTarget: number;
  totalRealisasi: number;
  persentaseCapaian: number;
  jumlahKegiatanSelesai: number;
  jumlahKegiatanBerjalan: number;
  jumlahKegiatanTerlambat: number;
}
