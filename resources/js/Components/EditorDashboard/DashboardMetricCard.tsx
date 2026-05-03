import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface Props {
    label: string;
    value: string | number;
    icon?: ReactNode;
    tone?: 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet';
    helper?: string;
}

const toneClasses: Record<NonNullable<Props['tone']>, string> = {
    neutral: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    info: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-200',
    success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
    warning: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-200',
    danger: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-200',
    violet: 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-200',
};

export default function DashboardMetricCard({ label, value, icon, tone = 'neutral', helper }: Props): JSX.Element {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{label}</p>
                    <p className="mt-2 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white">{value}</p>
                    {helper && <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">{helper}</p>}
                </div>
                {icon && <span className={cn('rounded-lg p-2.5', toneClasses[tone])}>{icon}</span>}
            </div>
        </div>
    );
}
