interface StatCardProps {
  title: string;
  value: string | number;
  bgColor?: string;
  textColor?: string;
  icon?: React.ReactNode;
}

export default function StatCard({
  title,
  value,
  bgColor = 'bg-blue-500',
  textColor = 'text-white',
  icon,
}: StatCardProps) {
  return (
    <div className={`${bgColor} ${textColor} rounded-lg shadow-lg p-6`}>
      <div className="flex items-center justify-between mb-2">
        <h3 className="text-lg font-semibold">{title}</h3>
        {icon && <div className="text-2xl opacity-75">{icon}</div>}
      </div>
      <p className="text-4xl font-bold">{value}</p>
    </div>
  );
}
