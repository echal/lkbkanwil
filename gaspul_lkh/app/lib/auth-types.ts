/**
 * Enum untuk role pengguna dalam sistem
 */
export enum UserRole {
  ADMIN = "ADMIN",
  ASN = "ASN",
}

/**
 * Interface untuk data pengguna
 */
export interface User {
  id: string;
  nip?: string; // NIP hanya untuk ASN
  nama: string;
  email: string;
  role: UserRole;
  jabatan?: string;
  unitKerja?: string;
}

/**
 * Interface untuk konteks autentikasi
 */
export interface AuthContext {
  user: User | null;
  isAuthenticated: boolean;
}
