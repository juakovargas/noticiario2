import { useTranslations } from '@/i18n/useTranslations';
import { statusTranslationKey } from '@/i18n/statusLabels';
import { cn } from '@/lib/utils';

interface Props {
    status: string | null | undefined;
    className?: string;
}

const classes: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    prompt_ready: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200',
    waiting_ai_response: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    response_received: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-200',
    script_created: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
    completed: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200',
    cancelled: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200',
    failed: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200',
    archived: 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
    rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200',
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    in_review: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-200',
    verified: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
    weak: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-200',
    missing: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-200',
    broken: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200',
    not_required: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    active: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-200',
    inactive: 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100',
    collected: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
    selected: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-200',
};

export default function StatusBadge({ status, className }: Props): JSX.Element {
    const { t } = useTranslations();
    const label = status ? t(statusTranslationKey(status)) : '-';

    return (
        <span
            className={cn(
                'inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize',
                classes[status ?? ''] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
                className,
            )}
        >
            {label}
        </span>
    );
}
