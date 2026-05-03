import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/react';
import StatusBadge from './StatusBadge';

interface Props {
    items: any[];
    emptyLabel: string;
    runNowLabel: string;
    configureLabel: string;
    manualApprovalLabel: string;
    formatDateTime: (value?: string | null) => string;
}

export default function ScheduleTimeline({ items, emptyLabel, runNowLabel, configureLabel, manualApprovalLabel, formatDateTime }: Props): JSX.Element {
    if (!items.length) {
        return <p className="rounded-lg border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">{emptyLabel}</p>;
    }

    return (
        <div className="space-y-3">
            {items.map((item) => (
                <div key={item.id} className="relative rounded-lg border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="min-w-0">
                            <p className="font-semibold text-slate-950 dark:text-white">{item.bulletin}</p>
                            <p className="text-sm text-slate-500 dark:text-slate-400">{formatDateTime(item.scheduled_for)}</p>
                            <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{[item.provider, item.model].filter(Boolean).join(' · ')}</p>
                        </div>
                        {item.ai_manual_approval_required && <StatusBadge tone="warning">{manualApprovalLabel}</StatusBadge>}
                    </div>
                    <div className="mt-4 flex flex-wrap gap-2">
                        {item.view_url && (
                            <Button asChild size="sm" variant="outline">
                                <Link href={item.view_url}>{configureLabel}</Link>
                            </Button>
                        )}
                        {item.run_now_url && item.run_now_url !== '#' && (
                            <Button asChild size="sm">
                                <Link href={item.run_now_url} method="post" as="button">{runNowLabel}</Link>
                            </Button>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
}
