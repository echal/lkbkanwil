import {
  StatusPersetujuan,
  ApprovalLog,
  RencanaKerjaBulanan,
  RencanaKerjaHarian,
} from "./types";
import { User } from "./auth-types";

/**
 * Interface untuk hasil approval action
 */
export interface ApprovalActionResult {
  success: boolean;
  message: string;
  updatedData?: RencanaKerjaBulanan | RencanaKerjaHarian;
}

/**
 * Cek apakah data bisa diedit berdasarkan status
 */
export function canEdit(statusPersetujuan: StatusPersetujuan): boolean {
  return statusPersetujuan === StatusPersetujuan.DRAFT ||
         statusPersetujuan === StatusPersetujuan.DIKEMBALIKAN;
}

/**
 * Cek apakah ASN bisa mengajukan (submit)
 */
export function canSubmit(statusPersetujuan: StatusPersetujuan): boolean {
  return statusPersetujuan === StatusPersetujuan.DRAFT ||
         statusPersetujuan === StatusPersetujuan.DIKEMBALIKAN;
}

/**
 * Cek apakah Atasan bisa menyetujui
 */
export function canApprove(statusPersetujuan: StatusPersetujuan): boolean {
  return statusPersetujuan === StatusPersetujuan.DIAJUKAN;
}

/**
 * Cek apakah Atasan bisa mengembalikan
 */
export function canReject(statusPersetujuan: StatusPersetujuan): boolean {
  return statusPersetujuan === StatusPersetujuan.DIAJUKAN;
}

/**
 * Action: Simpan sebagai draft
 */
export function saveDraft<T extends RencanaKerjaBulanan | RencanaKerjaHarian>(
  data: T,
  currentUser: User
): ApprovalActionResult {
  const now = new Date();

  const log: ApprovalLog = {
    timestamp: now,
    action: StatusPersetujuan.DRAFT,
    userId: currentUser.id,
    userName: currentUser.nama,
    userRole: currentUser.role,
    catatan: "Draft disimpan",
  };

  const updatedData = {
    ...data,
    statusPersetujuan: StatusPersetujuan.DRAFT,
    approvalLogs: [...(data.approvalLogs || []), log],
    updatedAt: now,
  };

  return {
    success: true,
    message: "Draft berhasil disimpan. Anda dapat melanjutkan nanti atau mengajukan untuk persetujuan.",
    updatedData: updatedData as T,
  };
}

/**
 * Action: Ajukan untuk persetujuan atasan
 */
export function submitForApproval<T extends RencanaKerjaBulanan | RencanaKerjaHarian>(
  data: T,
  currentUser: User
): ApprovalActionResult {
  if (!canSubmit(data.statusPersetujuan)) {
    return {
      success: false,
      message: `Tidak dapat mengajukan. Status saat ini: ${data.statusPersetujuan}`,
    };
  }

  const now = new Date();

  const log: ApprovalLog = {
    timestamp: now,
    action: StatusPersetujuan.DIAJUKAN,
    userId: currentUser.id,
    userName: currentUser.nama,
    userRole: currentUser.role,
    catatan: "Diajukan untuk persetujuan atasan",
  };

  const updatedData = {
    ...data,
    statusPersetujuan: StatusPersetujuan.DIAJUKAN,
    approvalLogs: [...(data.approvalLogs || []), log],
    updatedAt: now,
    submittedAt: now,
  };

  return {
    success: true,
    message: "Rencana kerja berhasil diajukan ke atasan. Menunggu persetujuan.",
    updatedData: updatedData as T,
  };
}

/**
 * Action: Setujui oleh atasan
 */
export function approveByAtasan<T extends RencanaKerjaBulanan | RencanaKerjaHarian>(
  data: T,
  currentUser: User,
  catatan?: string
): ApprovalActionResult {
  if (!canApprove(data.statusPersetujuan)) {
    return {
      success: false,
      message: `Tidak dapat menyetujui. Status saat ini: ${data.statusPersetujuan}`,
    };
  }

  const now = new Date();

  const log: ApprovalLog = {
    timestamp: now,
    action: StatusPersetujuan.DISETUJUI,
    userId: currentUser.id,
    userName: currentUser.nama,
    userRole: currentUser.role,
    catatan: catatan || "Disetujui oleh atasan",
  };

  const updatedData = {
    ...data,
    statusPersetujuan: StatusPersetujuan.DISETUJUI,
    approvalLogs: [...(data.approvalLogs || []), log],
    updatedAt: now,
    approvedAt: now,
  };

  return {
    success: true,
    message: "Rencana kerja telah disetujui.",
    updatedData: updatedData as T,
  };
}

/**
 * Action: Kembalikan oleh atasan (WAJIB dengan catatan)
 */
export function rejectByAtasan<T extends RencanaKerjaBulanan | RencanaKerjaHarian>(
  data: T,
  currentUser: User,
  catatan: string
): ApprovalActionResult {
  if (!canReject(data.statusPersetujuan)) {
    return {
      success: false,
      message: `Tidak dapat mengembalikan. Status saat ini: ${data.statusPersetujuan}`,
    };
  }

  if (!catatan || catatan.trim() === "") {
    return {
      success: false,
      message: "Catatan pengembalian wajib diisi. Jelaskan alasan pengembalian untuk ASN.",
    };
  }

  const now = new Date();

  const log: ApprovalLog = {
    timestamp: now,
    action: StatusPersetujuan.DIKEMBALIKAN,
    userId: currentUser.id,
    userName: currentUser.nama,
    userRole: currentUser.role,
    catatan,
  };

  const updatedData = {
    ...data,
    statusPersetujuan: StatusPersetujuan.DIKEMBALIKAN,
    approvalLogs: [...(data.approvalLogs || []), log],
    updatedAt: now,
  };

  return {
    success: true,
    message: "Rencana kerja dikembalikan ke ASN untuk diperbaiki.",
    updatedData: updatedData as T,
  };
}

/**
 * Helper: Format status untuk display
 */
export function formatStatus(status: StatusPersetujuan): {
  label: string;
  color: string;
  bgColor: string;
  description: string;
} {
  switch (status) {
    case StatusPersetujuan.DRAFT:
      return {
        label: "Draft",
        color: "text-gray-700",
        bgColor: "bg-gray-100",
        description: "Masih dalam tahap penyusunan",
      };
    case StatusPersetujuan.DIAJUKAN:
      return {
        label: "Diajukan",
        color: "text-blue-700",
        bgColor: "bg-blue-100",
        description: "Menunggu persetujuan atasan",
      };
    case StatusPersetujuan.DISETUJUI:
      return {
        label: "Disetujui",
        color: "text-green-700",
        bgColor: "bg-green-100",
        description: "Telah disetujui atasan",
      };
    case StatusPersetujuan.DIKEMBALIKAN:
      return {
        label: "Dikembalikan",
        color: "text-red-700",
        bgColor: "bg-red-100",
        description: "Dikembalikan untuk diperbaiki",
      };
    default:
      return {
        label: status,
        color: "text-gray-700",
        bgColor: "bg-gray-100",
        description: "",
      };
  }
}

/**
 * Helper: Get latest approval log
 */
export function getLatestApprovalLog(logs: ApprovalLog[]): ApprovalLog | null {
  if (!logs || logs.length === 0) return null;
  return logs[logs.length - 1];
}

/**
 * Helper: Get rejection note (jika ada)
 */
export function getRejectionNote(data: RencanaKerjaBulanan | RencanaKerjaHarian): string | null {
  if (data.statusPersetujuan !== StatusPersetujuan.DIKEMBALIKAN) return null;

  const rejectionLog = data.approvalLogs
    .filter((log) => log.action === StatusPersetujuan.DIKEMBALIKAN)
    .pop();

  return rejectionLog?.catatan || null;
}

/**
 * Helper: Format timestamp untuk display
 */
export function formatTimestamp(date: Date): string {
  return new Intl.DateTimeFormat("id-ID", {
    dateStyle: "full",
    timeStyle: "short",
  }).format(new Date(date));
}
