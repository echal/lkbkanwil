import { WorkflowStatus } from '@/app/types/dashboard';

interface BadgeStatusProps {
  status: WorkflowStatus;
  className?: string;
}

const STATUS_CONFIG: Record<WorkflowStatus, { label: string; className: string }> = {
  DRAFT: {
    label: 'Draft',
    className: 'bg-gray-100 text-gray-800 border-gray-300',
  },
  DIAJUKAN: {
    label: 'Diajukan',
    className: 'bg-yellow-100 text-yellow-800 border-yellow-300',
  },
  DISETUJUI: {
    label: 'Disetujui',
    className: 'bg-green-100 text-green-800 border-green-300',
  },
  DIKEMBALIKAN: {
    label: 'Dikembalikan',
    className: 'bg-red-100 text-red-800 border-red-300',
  },
};

export default function BadgeStatus({ status, className = '' }: BadgeStatusProps) {
  const config = STATUS_CONFIG[status];

  return (
    <span
      className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border ${config.className} ${className}`}
    >
      {config.label}
    </span>
  );
}
