"use client";

import { useState } from "react";
import Link from "next/link";
import {
  RencanaKerjaBulanan,
  StatusPersetujuan,
  SatuanPengukuran,
  StatusPekerjaan,
  Bulan,
} from "../../lib/types";
import { User, UserRole } from "../../lib/auth-types";
import { getCurrentUser } from "../../lib/mock-users";
import { ApprovalActions } from "../../components/ApprovalActions";
import { AuditLogViewer } from "../../components/AuditLogViewer";
import { canEdit } from "../../lib/approval-helpers";

export default function DemoApprovalPage() {
  // Simulasi role switcher
  const [selectedRole, setSelectedRole] = useState<UserRole>(UserRole.ASN);
  const currentUser = getCurrentUser(selectedRole) as User;

  // Mock data rencana kerja dengan berbagai status
  const [rencanaList, setRencanaList] = useState<RencanaKerjaBulanan[]>([
    {
      id: "rkb-demo-1",
      indikatorTriwulanId: "iktr-003",
      bulan: Bulan.SEPTEMBER,
      tahun: 2024,
      namaKegiatan: "Pengembangan Sistem E-Kinerja (DRAFT)",
      deskripsiKegiatan: "Membuat sistem pelaporan kinerja berbasis web",
      targetBulanan: 4,
      realisasiBulanan: 0,
      satuanPengukuran: SatuanPengukuran.KEGIATAN,
      anggaranKegiatan: 25000000,
      statusPelaksanaan: StatusPekerjaan.BELUM_DIMULAI,
      hambatan: "",
      solusi: "",
      statusPersetujuan: StatusPersetujuan.DRAFT,
      approvalLogs: [
        {
          timestamp: new Date("2024-09-01T08:00:00"),
          action: StatusPersetujuan.DRAFT,
          userId: "asn-001",
          userName: "Budi Santoso, S.Kom",
          userRole: UserRole.ASN,
          catatan: "Draft awal disimpan",
        },
      ],
      createdAt: new Date("2024-09-01T08:00:00"),
      updatedAt: new Date("2024-09-01T08:00:00"),
    },
    {
      id: "rkb-demo-2",
      indikatorTriwulanId: "iktr-003",
      bulan: Bulan.SEPTEMBER,
      tahun: 2024,
      namaKegiatan: "Training Pegawai (DIAJUKAN)",
      deskripsiKegiatan: "Pelatihan aplikasi kepegawaian untuk staff",
      targetBulanan: 3,
      realisasiBulanan: 3,
      satuanPengukuran: SatuanPengukuran.KEGIATAN,
      anggaranKegiatan: 15000000,
      statusPelaksanaan: StatusPekerjaan.SELESAI,
      hambatan: "Jadwal bentrok dengan kegiatan lain",
      solusi: "Reschedule ke minggu berikutnya",
      statusPersetujuan: StatusPersetujuan.DIAJUKAN,
      approvalLogs: [
        {
          timestamp: new Date("2024-09-05T09:00:00"),
          action: StatusPersetujuan.DRAFT,
          userId: "asn-001",
          userName: "Budi Santoso, S.Kom",
          userRole: UserRole.ASN,
          catatan: "Draft disimpan",
        },
        {
          timestamp: new Date("2024-09-10T14:30:00"),
          action: StatusPersetujuan.DIAJUKAN,
          userId: "asn-001",
          userName: "Budi Santoso, S.Kom",
          userRole: UserRole.ASN,
          catatan: "Diajukan untuk persetujuan atasan",
        },
      ],
      createdAt: new Date("2024-09-05T09:00:00"),
      updatedAt: new Date("2024-09-10T14:30:00"),
      submittedAt: new Date("2024-09-10T14:30:00"),
    },
    {
      id: "rkb-demo-3",
      indikatorTriwulanId: "iktr-003",
      bulan: Bulan.AGUSTUS,
      tahun: 2024,
      namaKegiatan: "Maintenance Server (DISETUJUI)",
      deskripsiKegiatan: "Pemeliharaan rutin server aplikasi",
      targetBulanan: 2,
      realisasiBulanan: 2,
      satuanPengukuran: SatuanPengukuran.KEGIATAN,
      anggaranKegiatan: 10000000,
      statusPelaksanaan: StatusPekerjaan.SELESAI,
      hambatan: "Tidak ada",
      solusi: "-",
      statusPersetujuan: StatusPersetujuan.DISETUJUI,
      approvalLogs: [
        {
          timestamp: new Date("2024-08-01T08:00:00"),
          action: StatusPersetujuan.DRAFT,
          userId: "asn-001",
          userName: "Budi Santoso, S.Kom",
          userRole: UserRole.ASN,
          catatan: "Draft disimpan",
        },
        {
          timestamp: new Date("2024-08-05T10:00:00"),
          action: StatusPersetujuan.DIAJUKAN,
          userId: "asn-001",
          userName: "Budi Santoso, S.Kom",
          userRole: UserRole.ASN,
          catatan: "Diajukan untuk persetujuan",
        },
        {
          timestamp: new Date("2024-08-06T15:00:00"),
          action: StatusPersetujuan.DISETUJUI,
          userId: "admin-001",
          userName: "Dr. Ahmad Hidayat, M.Si",
          userRole: UserRole.ADMIN,
          catatan: "Rencana kerja sudah sesuai dan disetujui. Silakan dilaksanakan.",
        },
      ],
      createdAt: new Date("2024-08-01T08:00:00"),
      updatedAt: new Date("2024-08-06T15:00:00"),
      submittedAt: new Date("2024-08-05T10:00:00"),
      approvedAt: new Date("2024-08-06T15:00:00"),
    },
    {
      id: "rkb-demo-4",
      indikatorTriwulanId: "iktr-003",
      bulan: Bulan.JULI,
      tahun: 2024,
      namaKegiatan: "Update Dokumentasi Sistem (DIKEMBALIKAN)",
      deskripsiKegiatan: "Pembaruan dokumentasi teknis aplikasi",
      targetBulanan: 1,
      realisasiBulanan: 1,
      satuanPengukuran: SatuanPengukuran.DOKUMEN,
      anggaranKegiatan: 5000000,
      statusPelaksanaan: StatusPekerjaan.SELESAI,
      hambatan: "",
      solusi: "",
      statusPersetujuan: StatusPersetujuan.DIKEMBALIKAN,
      approvalLogs: [
        {
          timestamp: new Date("2024-07-10T09:00:00"),
          action: StatusPersetujuan.DRAFT,
          userId: "asn-001",
          userName: "Budi Santoso, S.Kom",
          userRole: UserRole.ASN,
          catatan: "Draft disimpan",
        },
        {
          timestamp: new Date("2024-07-15T11:00:00"),
          action: StatusPersetujuan.DIAJUKAN,
          userId: "asn-001",
          userName: "Budi Santoso, S.Kom",
          userRole: UserRole.ASN,
          catatan: "Diajukan untuk persetujuan",
        },
        {
          timestamp: new Date("2024-07-16T14:30:00"),
          action: StatusPersetujuan.DIKEMBALIKAN,
          userId: "admin-001",
          userName: "Dr. Ahmad Hidayat, M.Si",
          userRole: UserRole.ADMIN,
          catatan:
            "Dokumentasi belum lengkap. Mohon tambahkan:\n1. Diagram arsitektur sistem\n2. User manual yang lebih detail\n3. Troubleshooting guide\nSilakan lengkapi dan ajukan kembali.",
        },
      ],
      createdAt: new Date("2024-07-10T09:00:00"),
      updatedAt: new Date("2024-07-16T14:30:00"),
      submittedAt: new Date("2024-07-15T11:00:00"),
    },
  ]);

  const [selectedRencana, setSelectedRencana] = useState<RencanaKerjaBulanan | null>(
    rencanaList[0]
  );

  const handleApprovalAction = (updatedData: RencanaKerjaBulanan | RencanaKerjaHarian) => {
    const updated = updatedData as RencanaKerjaBulanan;
    setRencanaList((prev) =>
      prev.map((item) => (item.id === updated.id ? updated : item))
    );
    setSelectedRencana(updated);
  };

  // Filter berdasarkan role
  const getFilteredList = () => {
    if (selectedRole === UserRole.ASN) {
      return rencanaList;
    }
    // Atasan hanya lihat yang diajukan
    return rencanaList.filter(
      (r) => r.statusPersetujuan === StatusPersetujuan.DIAJUKAN
    );
  };

  const filteredList = getFilteredList();

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <nav className="mb-4 text-sm">
            <Link href="/dashboard" className="text-blue-600 hover:underline">
              Dashboard
            </Link>
            <span className="mx-2 text-gray-500">/</span>
            <span className="text-gray-700">Demo Flow Persetujuan</span>
          </nav>
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">
                Demo Flow Persetujuan Atasan
              </h1>
              <p className="mt-2 text-gray-600">
                Status flow: DRAFT → DIAJUKAN → DISETUJUI / DIKEMBALIKAN
              </p>
            </div>
            {/* Role Switcher */}
            <div className="text-right">
              <p className="text-sm text-gray-500 mb-2">Demo: Pilih Role</p>
              <div className="flex gap-2">
                <button
                  onClick={() => setSelectedRole(UserRole.ASN)}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition ${
                    selectedRole === UserRole.ASN
                      ? "bg-green-600 text-white"
                      : "bg-white text-gray-700 border border-gray-300 hover:bg-gray-50"
                  }`}
                >
                  ASN (Budi)
                </button>
                <button
                  onClick={() => setSelectedRole(UserRole.ADMIN)}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition ${
                    selectedRole === UserRole.ADMIN
                      ? "bg-purple-600 text-white"
                      : "bg-white text-gray-700 border border-gray-300 hover:bg-gray-50"
                  }`}
                >
                  ATASAN (Ahmad)
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* User Info */}
        <div className="mb-6 bg-white rounded-lg shadow p-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="h-12 w-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-lg font-bold">
                {currentUser.nama.charAt(0)}
              </div>
              <div>
                <p className="font-semibold text-gray-900">{currentUser.nama}</p>
                <p className="text-sm text-gray-500">{currentUser.jabatan}</p>
              </div>
            </div>
            <span
              className={`px-3 py-1 text-sm font-semibold rounded-full ${
                selectedRole === UserRole.ASN
                  ? "bg-green-100 text-green-800"
                  : "bg-purple-100 text-purple-800"
              }`}
            >
              {currentUser.role}
            </span>
          </div>
        </div>

        <div className="grid grid-cols-3 gap-6">
          {/* Panel Kiri: List */}
          <div className="col-span-1 bg-white rounded-lg shadow overflow-hidden">
            <div className="px-4 py-3 bg-gray-50 border-b">
              <h3 className="font-semibold text-gray-900">
                {selectedRole === UserRole.ASN
                  ? "Daftar Rencana Kerja"
                  : "Perlu Persetujuan"}
              </h3>
              <p className="text-xs text-gray-500 mt-1">
                {filteredList.length} rencana kerja
              </p>
            </div>
            <div className="divide-y divide-gray-200">
              {filteredList.map((rencana) => (
                <button
                  key={rencana.id}
                  onClick={() => setSelectedRencana(rencana)}
                  className={`w-full px-4 py-3 text-left hover:bg-gray-50 transition ${
                    selectedRencana?.id === rencana.id ? "bg-blue-50" : ""
                  }`}
                >
                  <p className="font-medium text-sm text-gray-900 line-clamp-1">
                    {rencana.namaKegiatan}
                  </p>
                  <p className="text-xs text-gray-500 mt-1">
                    {rencana.bulan} {rencana.tahun}
                  </p>
                  <div className="mt-2">
                    <span
                      className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded ${
                        rencana.statusPersetujuan === StatusPersetujuan.DRAFT
                          ? "bg-gray-100 text-gray-700"
                          : rencana.statusPersetujuan === StatusPersetujuan.DIAJUKAN
                          ? "bg-blue-100 text-blue-700"
                          : rencana.statusPersetujuan === StatusPersetujuan.DISETUJUI
                          ? "bg-green-100 text-green-700"
                          : "bg-red-100 text-red-700"
                      }`}
                    >
                      {rencana.statusPersetujuan}
                    </span>
                  </div>
                </button>
              ))}
            </div>
          </div>

          {/* Panel Tengah: Detail & Actions */}
          <div className="col-span-2 space-y-6">
            {selectedRencana ? (
              <>
                {/* Detail Rencana */}
                <div className="bg-white rounded-lg shadow p-6">
                  <h2 className="text-xl font-bold text-gray-900 mb-4">
                    {selectedRencana.namaKegiatan}
                  </h2>
                  <div className="grid grid-cols-2 gap-4 mb-6">
                    <div>
                      <p className="text-sm text-gray-500">Periode</p>
                      <p className="font-medium text-gray-900">
                        {selectedRencana.bulan} {selectedRencana.tahun}
                      </p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Anggaran</p>
                      <p className="font-medium text-gray-900">
                        Rp {selectedRencana.anggaranKegiatan.toLocaleString("id-ID")}
                      </p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Target</p>
                      <p className="font-medium text-gray-900">
                        {selectedRencana.targetBulanan}{" "}
                        {selectedRencana.satuanPengukuran}
                      </p>
                    </div>
                    <div>
                      <p className="text-sm text-gray-500">Realisasi</p>
                      <p className="font-medium text-gray-900">
                        {selectedRencana.realisasiBulanan}{" "}
                        {selectedRencana.satuanPengukuran}
                      </p>
                    </div>
                  </div>

                  <div className="mb-6">
                    <p className="text-sm text-gray-500 mb-1">Deskripsi Kegiatan</p>
                    <p className="text-gray-900">{selectedRencana.deskripsiKegiatan}</p>
                  </div>

                  {/* Guard untuk Edit */}
                  {selectedRole === UserRole.ASN && (
                    <div className="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                      <div className="flex items-center justify-between">
                        <div>
                          <p className="text-sm font-semibold text-gray-900">
                            Status Edit
                          </p>
                          <p className="text-xs text-gray-600 mt-1">
                            {canEdit(selectedRencana.statusPersetujuan)
                              ? "✅ Dapat diedit"
                              : "🔒 Tidak dapat diedit"}
                          </p>
                        </div>
                        <button
                          disabled={!canEdit(selectedRencana.statusPersetujuan)}
                          className={`px-4 py-2 rounded-lg text-sm font-medium transition ${
                            canEdit(selectedRencana.statusPersetujuan)
                              ? "bg-blue-600 text-white hover:bg-blue-700"
                              : "bg-gray-200 text-gray-400 cursor-not-allowed"
                          }`}
                        >
                          ✏️ Edit
                        </button>
                      </div>
                    </div>
                  )}

                  {/* Approval Actions */}
                  <div className="pt-6 border-t border-gray-200">
                    <h3 className="text-sm font-semibold text-gray-900 mb-4">
                      Aksi Persetujuan
                    </h3>
                    <ApprovalActions
                      data={selectedRencana}
                      currentUser={currentUser}
                      onAction={handleApprovalAction}
                    />
                  </div>
                </div>

                {/* Audit Log */}
                <AuditLogViewer logs={selectedRencana.approvalLogs} />
              </>
            ) : (
              <div className="bg-white rounded-lg shadow p-12 text-center">
                <p className="text-gray-500">Pilih rencana kerja untuk melihat detail</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
