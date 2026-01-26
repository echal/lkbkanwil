import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Login - Laporan Harian Kemenag Sulawesi Barat",
  description: "Sistem Laporan Harian - Kanwil Kementerian Agama Provinsi Sulawesi Barat",
};

/**
 * Layout khusus untuk halaman autentikasi (login)
 * Layout ini STERIL tanpa komponen tambahan seperti navbar, sidebar, atau widget global
 * Hanya untuk halaman login - tidak mewarisi komponen dashboard
 */
export default function AuthLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <div className="auth-layout">
      {/* Hanya children tanpa komponen tambahan */}
      {children}
    </div>
  );
}
