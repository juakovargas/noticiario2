import { cn } from '@/lib/utils';
import { PropsWithChildren } from 'react';

interface SectionCardProps extends PropsWithChildren {
    className?: string;
}

export default function SectionCard({ children, className }: SectionCardProps): JSX.Element {
    return (
        <section
            className={cn(
                'rounded-xl border border-slate-200/80 bg-white/90 p-5 shadow-sm shadow-slate-200/50 backdrop-blur dark:border-slate-800 dark:bg-slate-900/70 dark:shadow-none',
                className,
            )}
        >
            {children}
        </section>
    );
}
