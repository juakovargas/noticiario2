import { ReactNode } from 'react';

export default function EmptyStateCard({ title, icon }: { title: string; icon?: ReactNode }): JSX.Element {
    return (
        <div className="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
            {icon && <div className="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-900">{icon}</div>}
            {title}
        </div>
    );
}
