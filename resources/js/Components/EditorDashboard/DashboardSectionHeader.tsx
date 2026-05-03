import { ReactNode } from 'react';

interface Props {
    title: string;
    description?: string;
    icon?: ReactNode;
    action?: ReactNode;
}

export default function DashboardSectionHeader({ title, description, icon, action }: Props): JSX.Element {
    return (
        <div className="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
            <div className="flex min-w-0 items-start gap-3">
                {icon && <span className="mt-0.5 rounded-lg bg-slate-100 p-2 text-slate-600 dark:bg-slate-900 dark:text-slate-300">{icon}</span>}
                <div className="min-w-0">
                    <h2 className="text-base font-semibold text-slate-950 dark:text-white">{title}</h2>
                    {description && <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{description}</p>}
                </div>
            </div>
            {action}
        </div>
    );
}
