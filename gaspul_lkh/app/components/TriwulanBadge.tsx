import { TriwulanType } from '@/app/types/dashboard';

interface TriwulanBadgeProps {
  triwulan: TriwulanType;
}

const TRIWULAN_CONFIG: Record<TriwulanType, { label: string; className: string }> = {
  TW1: {
    label: 'Triwulan 1',
    className: 'bg-blue-100 text-blue-800 border-blue-200',
  },
  TW2: {
    label: 'Triwulan 2',
    className: 'bg-green-100 text-green-800 border-green-200',
  },
  TW3: {
    label: 'Triwulan 3',
    className: 'bg-yellow-100 text-yellow-800 border-yellow-200',
  },
  TW4: {
    label: 'Triwulan 4',
    className: 'bg-purple-100 text-purple-800 border-purple-200',
  },
};

export default function TriwulanBadge({ triwulan }: TriwulanBadgeProps) {
  const config = TRIWULAN_CONFIG[triwulan];

  return (
    <span
      className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border ${config.className}`}
    >
      {config.label}
    </span>
  );
}
