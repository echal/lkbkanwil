import { User, UserRole } from "./auth-types";

/**
 * Mock data pengguna untuk testing RBAC
 * Dalam production, data ini akan diambil dari session/token
 */
export const mockUsers: User[] = [
  {
    id: "admin-001",
    nama: "Dr. Ahmad Hidayat, M.Si",
    email: "admin@diskominfo.go.id",
    role: UserRole.ADMIN,
    jabatan: "Kepala Dinas Komunikasi dan Informatika",
    unitKerja: "Dinas Komunikasi dan Informatika",
  },
  {
    id: "asn-001",
    nip: "199001012015031001",
    nama: "Budi Santoso, S.Kom",
    email: "budi.santoso@diskominfo.go.id",
    role: UserRole.ASN,
    jabatan: "Kepala Seksi Aplikasi",
    unitKerja: "Dinas Komunikasi dan Informatika",
  },
  {
    id: "asn-002",
    nip: "199105152016032002",
    nama: "Siti Aminah, S.T.",
    email: "siti.aminah@diskominfo.go.id",
    role: UserRole.ASN,
    jabatan: "Analis Teknologi Informasi",
    unitKerja: "Dinas Komunikasi dan Informatika",
  },
];

/**
 * Fungsi untuk mendapatkan current user
 * Dalam production, ini akan mengambil dari session/cookie/token
 * Untuk demo, kita return user pertama sesuai role yang diinginkan
 */
export function getCurrentUser(role?: UserRole): User | null {
  if (role === UserRole.ADMIN) {
    return mockUsers.find((user) => user.role === UserRole.ADMIN) || null;
  }
  if (role === UserRole.ASN) {
    return mockUsers.find((user) => user.role === UserRole.ASN) || null;
  }
  // Default: return ASN user untuk demo
  return mockUsers.find((user) => user.role === UserRole.ASN) || null;
}

/**
 * Fungsi untuk cek apakah user memiliki role tertentu
 */
export function hasRole(user: User | null, allowedRoles: UserRole[]): boolean {
  if (!user) return false;
  return allowedRoles.includes(user.role);
}
