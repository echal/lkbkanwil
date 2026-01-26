import { RhkpStatus } from '@/app/types/dashboard';

interface RhkpStatusBadgeProps {
  status: RhkpStatus;
}

const STATUS_CONFIG: Record<RhkpStatus, { label: string; className: string }> = {
  AKTIF: {
    label: 'Aktif',
    className: 'bg-green-100 text-green-800 border-green-200',
  },
  NONAKTIF: {
    label: 'Non-Aktif',
    className: 'bg-gray-100 text-gray-800 border-gray-200',
  },
};

export default function RhkpStatusBadge({ status }: RhkpStatusBadgeProps) {
  const config = STATUS_CONFIG[status];

  return (
    <span
      className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border ${config.className}`}
    >
      {config.label}
    </span>
  );
}
