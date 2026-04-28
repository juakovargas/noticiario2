import { cn } from '@/lib/utils';
import { PropsWithChildren, ReactNode } from 'react';

interface PageHeaderProps extends PropsWithChildren {
    title: string;
    description?: string | null;
    actions?: ReactNode;
    className?: string;
}

export default function PageHeader({ title, description, actions, className, children }: PageHeaderProps): JSX.Element {
    return (
        <div className={cn('mb-6 flex flex-wrap items-start justify-between gap-4', className)}>
            <div>
                <h1 className="text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{title}</h1>
                {description ? <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{description}</p> : null}
                {children}
            </div>
            {actions ? <div className="flex items-center gap-2">{actions}</div> : null}
        </div>
    );
}
