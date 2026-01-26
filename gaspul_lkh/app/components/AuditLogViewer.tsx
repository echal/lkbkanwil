"use client";

import { ApprovalLog, StatusPersetujuan } from "../lib/types";
import { formatTimestamp, formatStatus } from "../lib/approval-helpers";

interface AuditLogViewerProps {
  logs: ApprovalLog[];
  title?: string;
}

export function AuditLogViewer({ logs, title = "Riwayat Persetujuan" }: AuditLogViewerProps) {
  if (!logs || logs.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">{title}</h3>
        <p className="text-sm text-gray-500 text-center py-8">
          Belum ada riwayat persetujuan
        </p>
      </div>
    );
  }

  const getActionIcon = (action: StatusPersetujuan) => {
    switch (action) {
      case StatusPersetujuan.DRAFT:
        return "💾";
      case StatusPersetujuan.DIAJUKAN:
        return "📤";
      case StatusPersetujuan.DISETUJUI:
        return "✅";
      case StatusPersetujuan.DIKEMBALIKAN:
        return "❌";
      default:
        return "📝";
    }
  };

  return (
    <div className="bg-white rounded-lg shadow overflow-hidden">
      <div className="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
        <p className="text-sm text-gray-600 mt-1">
          Semua aktivitas dan perubahan status tercatat untuk audit
        </p>
      </div>

      <div className="p-6">
        <div className="flow-root">
          <ul className="-mb-8">
            {logs.map((log, logIdx) => {
              const statusInfo = formatStatus(log.action);
              const isLast = logIdx === logs.length - 1;

              return (
                <li key={logIdx}>
                  <div className="relative pb-8">
                    {!isLast && (
                      <span
                        className="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200"
                        aria-hidden="true"
                      />
                    )}
                    <div className="relative flex items-start space-x-3">
                      {/* Icon */}
                      <div className="relative">
                        <div
                          className={`h-10 w-10 rounded-full ${statusInfo.bgColor} flex items-center justify-center text-xl`}
                        >
                          {getActionIcon(log.action)}
                        </div>
                      </div>

                      {/* Content */}
                      <div className="min-w-0 flex-1">
                        <div>
                          <div className="flex items-center gap-2">
                            <span
                              className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded ${statusInfo.bgColor} ${statusInfo.color}`}
                            >
                              {statusInfo.label}
                            </span>
                            <span className="text-xs text-gray-500">
                              {formatTimestamp(log.timestamp)}
                            </span>
                          </div>
                          <div className="mt-2 text-sm text-gray-900">
                            <p className="font-medium">{log.userName}</p>
                            <p className="text-xs text-gray-500">
                              {log.userRole} • ID: {log.userId}
                            </p>
                          </div>
                        </div>

                        {/* Catatan */}
                        {log.catatan && (
                          <div className="mt-2">
                            <div
                              className={`p-3 rounded-lg border ${
                                log.action === StatusPersetujuan.DIKEMBALIKAN
                                  ? "bg-red-50 border-red-200"
                                  : log.action === StatusPersetujuan.DISETUJUI
                                  ? "bg-green-50 border-green-200"
                                  : "bg-gray-50 border-gray-200"
                              }`}
                            >
                              <p className="text-xs font-semibold text-gray-700 mb-1">
                                Catatan:
                              </p>
                              <p
                                className={`text-sm ${
                                  log.action === StatusPersetujuan.DIKEMBALIKAN
                                    ? "text-red-700"
                                    : log.action === StatusPersetujuan.DISETUJUI
                                    ? "text-green-700"
                                    : "text-gray-700"
                                }`}
                              >
                                {log.catatan}
                              </p>
                            </div>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                </li>
              );
            })}
          </ul>
        </div>

        {/* Summary Stats */}
        <div className="mt-6 pt-6 border-t border-gray-200">
          <div className="grid grid-cols-4 gap-4 text-center">
            <div className="p-3 bg-gray-50 rounded-lg">
              <p className="text-2xl font-bold text-gray-900">
                {logs.filter((l) => l.action === StatusPersetujuan.DRAFT).length}
              </p>
              <p className="text-xs text-gray-600 mt-1">Draft</p>
            </div>
            <div className="p-3 bg-blue-50 rounded-lg">
              <p className="text-2xl font-bold text-blue-900">
                {logs.filter((l) => l.action === StatusPersetujuan.DIAJUKAN).length}
              </p>
              <p className="text-xs text-blue-600 mt-1">Diajukan</p>
            </div>
            <div className="p-3 bg-green-50 rounded-lg">
              <p className="text-2xl font-bold text-green-900">
                {logs.filter((l) => l.action === StatusPersetujuan.DISETUJUI).length}
              </p>
              <p className="text-xs text-green-600 mt-1">Disetujui</p>
            </div>
            <div className="p-3 bg-red-50 rounded-lg">
              <p className="text-2xl font-bold text-red-900">
                {logs.filter((l) => l.action === StatusPersetujuan.DIKEMBALIKAN).length}
              </p>
              <p className="text-xs text-red-600 mt-1">Dikembalikan</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
