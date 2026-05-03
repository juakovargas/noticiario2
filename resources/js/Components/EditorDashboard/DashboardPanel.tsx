import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface Props {
    children: ReactNode;
    className?: string;
    accent?: 'none' | 'cyan' | 'emerald' | 'amber' | 'rose' | 'violet';
}

const accents: Record<NonNullable<Props['accent']>, string> = {
    none: '',
    cyan: 'before:bg-cyan-500',
    emerald: 'before:bg-emerald-500',
    amber: 'before:bg-amber-500',
    rose: 'before:bg-rose-500',
    violet: 'before:bg-violet-500',
};

export default function DashboardPanel({ children, className, accent = 'none' }: Props): JSX.Element {
    return (
        <section
            className={cn(
                'relative overflow-visible rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-950 dark:shadow-black/20',
                accent !== 'none' && 'before:absolute before:inset-x-0 before:top-0 before:h-1',
                accents[accent],
                className,
            )}
        >
            {children}
        </section>
    );
}
