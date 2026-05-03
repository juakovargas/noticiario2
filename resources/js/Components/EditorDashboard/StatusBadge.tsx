import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface Props {
    children: ReactNode;
    tone?: 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet';
    className?: string;
}

const tones: Record<NonNullable<Props['tone']>, string> = {
    neutral: 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
    info: 'border-cyan-200 bg-cyan-50 text-cyan-700 dark:border-cyan-900 dark:bg-cyan-950 dark:text-cyan-200',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    warning: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200',
    danger: 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200',
    violet: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-200',
};

export default function StatusBadge({ children, tone = 'neutral', className }: Props): JSX.Element {
    return <span className={cn('inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold', tones[tone], className)}>{children}</span>;
}
