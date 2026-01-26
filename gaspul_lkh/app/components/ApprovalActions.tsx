"use client";

import { useState } from "react";
import {
  StatusPersetujuan,
  RencanaKerjaBulanan,
  RencanaKerjaHarian,
} from "../lib/types";
import { User, UserRole } from "../lib/auth-types";
import {
  canEdit,
  canSubmit,
  canApprove,
  canReject,
  saveDraft,
  submitForApproval,
  approveByAtasan,
  rejectByAtasan,
  formatStatus,
  getRejectionNote,
} from "../lib/approval-helpers";

interface ApprovalActionsProps {
  data: RencanaKerjaBulanan | RencanaKerjaHarian;
  currentUser: User;
  onAction: (updatedData: RencanaKerjaBulanan | RencanaKerjaHarian) => void;
  mode?: "inline" | "modal";
}

export function ApprovalActions({
  data,
  currentUser,
  onAction,
  mode = "inline",
}: ApprovalActionsProps) {
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [catatanPengembalian, setCatatanPengembalian] = useState("");
  const [loading, setLoading] = useState(false);

  const statusInfo = formatStatus(data.statusPersetujuan);
  const rejectionNote = getRejectionNote(data);
  const isASN = currentUser.role === UserRole.ASN;
  const isAtasan = currentUser.role === UserRole.ADMIN; // Simulasi: Admin sebagai atasan

  const handleSaveDraft = () => {
    setLoading(true);
    const result = saveDraft(data, currentUser);
    if (result.success && result.updatedData) {
      onAction(result.updatedData);
      alert(result.message);
    }
    setLoading(false);
  };

  const handleSubmit = () => {
    if (!confirm("Ajukan rencana kerja ini ke atasan untuk persetujuan?")) return;

    setLoading(true);
    const result = submitForApproval(data, currentUser);
    if (result.success && result.updatedData) {
      onAction(result.updatedData);
      alert(result.message);
    } else {
      alert(result.message);
    }
    setLoading(false);
  };

  const handleApprove = () => {
    if (!confirm("Setujui rencana kerja ini?")) return;

    setLoading(true);
    const result = approveByAtasan(data, currentUser, "Rencana kerja sudah sesuai dan disetujui");
    if (result.success && result.updatedData) {
      onAction(result.updatedData);
      alert(result.message);
    } else {
      alert(result.message);
    }
    setLoading(false);
  };

  const handleReject = () => {
    if (!catatanPengembalian.trim()) {
      alert("Catatan pengembalian wajib diisi!");
      return;
    }

    setLoading(true);
    const result = rejectByAtasan(data, currentUser, catatanPengembalian);
    if (result.success && result.updatedData) {
      onAction(result.updatedData);
      setShowRejectModal(false);
      setCatatanPengembalian("");
      alert(result.message);
    } else {
      alert(result.message);
    }
    setLoading(false);
  };

  return (
    <div className={mode === "inline" ? "space-y-4" : ""}>
      {/* Status Badge */}
      <div className="flex items-center gap-3">
        <span
          className={`inline-flex px-3 py-1 text-sm font-semibold rounded-full ${statusInfo.bgColor} ${statusInfo.color}`}
        >
          {statusInfo.label}
        </span>
        <span className="text-sm text-gray-600">{statusInfo.description}</span>
      </div>

      {/* Rejection Note (jika dikembalikan) */}
      {rejectionNote && (
        <div className="p-4 bg-red-50 border border-red-200 rounded-lg">
          <div className="flex items-start gap-2">
            <svg
              className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fillRule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                clipRule="evenodd"
              />
            </svg>
            <div>
              <p className="text-sm font-semibold text-red-900">
                Catatan Pengembalian dari Atasan:
              </p>
              <p className="text-sm text-red-700 mt-1">{rejectionNote}</p>
            </div>
          </div>
        </div>
      )}

      {/* Action Buttons untuk ASN */}
      {isASN && (
        <div className="flex gap-3">
          {canEdit(data.statusPersetujuan) && (
            <button
              onClick={handleSaveDraft}
              disabled={loading}
              className="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              💾 Simpan Draft
            </button>
          )}

          {canSubmit(data.statusPersetujuan) && (
            <button
              onClick={handleSubmit}
              disabled={loading}
              className="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              📤 Ajukan ke Atasan
            </button>
          )}

          {data.statusPersetujuan === StatusPersetujuan.DIAJUKAN && (
            <div className="px-4 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
              ⏳ Menunggu persetujuan atasan...
            </div>
          )}

          {data.statusPersetujuan === StatusPersetujuan.DISETUJUI && (
            <div className="px-4 py-2 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
              ✅ Sudah disetujui atasan
            </div>
          )}
        </div>
      )}

      {/* Action Buttons untuk Atasan */}
      {isAtasan && (
        <div className="flex gap-3">
          {data.statusPersetujuan === StatusPersetujuan.DIAJUKAN ? (
            <>
              <button
                onClick={handleApprove}
                disabled={loading}
                className="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                ✅ Setujui
              </button>
              <button
                onClick={() => setShowRejectModal(true)}
                disabled={loading}
                className="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                ❌ Kembalikan
              </button>
            </>
          ) : (
            <div className="px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600">
              Status: {statusInfo.label}
            </div>
          )}
        </div>
      )}

      {/* Guard Info untuk Edit */}
      {isASN && !canEdit(data.statusPersetujuan) && (
        <div className="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
          <div className="flex items-start gap-2">
            <svg
              className="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fillRule="evenodd"
                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                clipRule="evenodd"
              />
            </svg>
            <div>
              <p className="text-sm font-semibold text-yellow-900">
                Tidak Dapat Mengedit
              </p>
              <p className="text-sm text-yellow-700 mt-1">
                Rencana kerja dengan status "{statusInfo.label}" tidak dapat diedit.
                {data.statusPersetujuan === StatusPersetujuan.DIAJUKAN &&
                  " Sedang dalam proses persetujuan atasan."}
                {data.statusPersetujuan === StatusPersetujuan.DISETUJUI &&
                  " Sudah disetujui dan tidak dapat diubah."}
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Modal Reject */}
      {showRejectModal && (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div className="px-6 py-4 border-b border-gray-200">
              <h3 className="text-lg font-semibold text-gray-900">
                Kembalikan Rencana Kerja
              </h3>
              <p className="text-sm text-gray-600 mt-1">
                Catatan pengembalian WAJIB diisi
              </p>
            </div>
            <div className="px-6 py-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Alasan Pengembalian <span className="text-red-500">*</span>
              </label>
              <textarea
                value={catatanPengembalian}
                onChange={(e) => setCatatanPengembalian(e.target.value)}
                rows={4}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                placeholder="Jelaskan alasan pengembalian dan saran perbaikan untuk ASN..."
              />
              <p className="text-xs text-gray-500 mt-2">
                💡 Berikan penjelasan yang jelas agar ASN dapat memperbaiki dengan tepat
              </p>
            </div>
            <div className="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
              <button
                onClick={() => {
                  setShowRejectModal(false);
                  setCatatanPengembalian("");
                }}
                className="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
              >
                Batal
              </button>
              <button
                onClick={handleReject}
                disabled={!catatanPengembalian.trim() || loading}
                className="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Kembalikan
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
