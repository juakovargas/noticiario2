import { useTranslations } from '@/i18n/useTranslations';
import { statusTranslationKey } from '@/i18n/statusLabels';
import { cn } from '@/lib/utils';

interface Props {
    status: string | null | undefined;
    className?: string;
}

const classes: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700',
    prompt_ready: 'bg-indigo-100 text-indigo-700',
    waiting_ai_response: 'bg-amber-100 text-amber-800',
    response_received: 'bg-cyan-100 text-cyan-700',
    script_created: 'bg-emerald-100 text-emerald-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-rose-100 text-rose-700',
    failed: 'bg-red-100 text-red-700',
    archived: 'bg-zinc-200 text-zinc-700',
    approved: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-rose-100 text-rose-700',
    pending: 'bg-amber-100 text-amber-800',
    in_review: 'bg-purple-100 text-purple-700',
    verified: 'bg-emerald-100 text-emerald-700',
    weak: 'bg-orange-100 text-orange-700',
    missing: 'bg-yellow-100 text-yellow-700',
    broken: 'bg-red-100 text-red-700',
    not_required: 'bg-slate-100 text-slate-700',
    active: 'bg-green-100 text-green-700',
    inactive: 'bg-zinc-200 text-zinc-700',
    collected: 'bg-blue-100 text-blue-700',
    selected: 'bg-teal-100 text-teal-700',
};

export default function StatusBadge({ status, className }: Props): JSX.Element {
    const { t } = useTranslations();
    const label = status ? t(statusTranslationKey(status)) : '-';

    return (
        <span className={cn('inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize', classes[status ?? ''] ?? 'bg-slate-100 text-slate-700', className)}>
            {label}
        </span>
    );
}
