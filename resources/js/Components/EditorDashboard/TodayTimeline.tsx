import { Link } from '@inertiajs/react';
import { Clock3 } from 'lucide-react';
import ActionButtonGroup, { DashboardAction } from './ActionButtonGroup';
import StatusBadge from './StatusBadge';

interface TodayTimelineItem {
    id: string | number;
    type?: 'schedule' | 'execution';
    bulletin?: string | null;
    provider?: string | null;
    model?: string | null;
    scheduled_for?: string | null;
    status?: string | null;
    status_label?: string | null;
    status_tone?: 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet';
    source_label?: string | null;
    source_tone?: 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet';
    ai_manual_approval_required?: boolean;
    view_url?: string | null;
    run_now_url?: string | null;
    action_url?: string | null;
    action_method?: 'get' | 'post' | null;
    action_label?: string | null;
}

interface Props {
    items: TodayTimelineItem[];
    emptyLabel: string;
    currentTimeLabel: string;
    pastLabel: string;
    upcomingLabel: string;
    manualApprovalLabel: string;
    viewLabel: string;
    runNowLabel: string;
    formatTime: (value?: string | null) => string;
}

export default function TodayTimeline({
    items,
    emptyLabel,
    currentTimeLabel,
    pastLabel,
    upcomingLabel,
    manualApprovalLabel,
    viewLabel,
    runNowLabel,
    formatTime,
}: Props): JSX.Element {
    const now = new Date();
    const sortedItems = items
        .filter((item) => item.scheduled_for)
        .sort((a, b) => new Date(a.scheduled_for as string).getTime() - new Date(b.scheduled_for as string).getTime());
    const groupedByHour = sortedItems.reduce<Record<number, TodayTimelineItem[]>>((groups, item) => {
        const hour = new Date(item.scheduled_for as string).getHours();
        groups[hour] = groups[hour] ?? [];
        groups[hour].push(item);
        return groups;
    }, {});
    const currentPercent = ((now.getHours() * 60 + now.getMinutes()) / 1439) * 100;

    if (!sortedItems.length) {
        return <p className="rounded-xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">{emptyLabel}</p>;
    }

    return (
        <div className="overflow-x-auto pb-2">
            <div className="relative min-w-[980px] rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                <div className="relative mb-5 h-14">
                    <div className="absolute left-0 right-0 top-7 h-px bg-slate-300 dark:bg-slate-700" />
                    <CurrentTimeMarker label={currentTimeLabel} left={currentPercent} />
                    <div className="grid grid-cols-8 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                        {['00:00', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00', '23:59'].map((label) => (
                            <div key={label} className="relative pt-9">
                                <span className="absolute left-0 top-5 h-3 w-px bg-slate-300 dark:bg-slate-700" />
                                {label}
                            </div>
                        ))}
                    </div>
                </div>
                <div className="grid gap-2" style={{ gridTemplateColumns: 'repeat(24, minmax(8rem, 1fr))' }}>
                    {Array.from({ length: 24 }).map((_, hour) => (
                        <div key={hour} className="min-h-[3rem] border-l border-slate-200 pl-2 dark:border-slate-800">
                            <p className="mb-2 text-[10px] font-semibold text-slate-400 dark:text-slate-500">{String(hour).padStart(2, '0')}</p>
                            <div className="space-y-2">
                                {(groupedByHour[hour] ?? []).map((item) => (
                                    <TimelineCard
                                        key={`${item.type ?? 'item'}-${item.id}`}
                                        item={item}
                                        isPast={new Date(item.scheduled_for as string).getTime() < now.getTime()}
                                        pastLabel={pastLabel}
                                        upcomingLabel={upcomingLabel}
                                        manualApprovalLabel={manualApprovalLabel}
                                        viewLabel={viewLabel}
                                        runNowLabel={runNowLabel}
                                        formatTime={formatTime}
                                    />
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

function CurrentTimeMarker({ label, left }: { label: string; left: number }): JSX.Element {
    return (
        <div className="absolute top-0 z-10 -translate-x-1/2" style={{ left: `${left}%` }}>
            <span className="mx-auto block h-8 w-px bg-cyan-500" />
            <span className="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-cyan-200 bg-cyan-50 px-2.5 py-1 text-xs font-semibold text-cyan-700 shadow-sm dark:border-cyan-900 dark:bg-cyan-950 dark:text-cyan-200">
                <Clock3 className="h-3.5 w-3.5" />
                {label}
            </span>
        </div>
    );
}

function TimelineCard({
    item,
    isPast,
    pastLabel,
    upcomingLabel,
    manualApprovalLabel,
    viewLabel,
    runNowLabel,
    formatTime,
}: {
    item: TodayTimelineItem;
    isPast: boolean;
    pastLabel: string;
    upcomingLabel: string;
    manualApprovalLabel: string;
    viewLabel: string;
    runNowLabel: string;
    formatTime: (value?: string | null) => string;
}): JSX.Element {
    const actions: DashboardAction[] = [
        item.action_url
            ? { key: 'next', label: item.action_label || runNowLabel, href: item.action_url, method: item.action_method === 'post' ? 'post' : 'get', variant: 'default' }
            : { key: 'run', label: runNowLabel, href: item.run_now_url, method: 'post', variant: 'default' },
        { key: 'view', label: viewLabel, href: item.view_url, variant: 'outline' },
    ];

    return (
        <article className="min-w-40 rounded-lg border border-slate-200 bg-white p-2.5 text-xs shadow-sm dark:border-slate-800 dark:bg-slate-950">
            <div className="min-w-0">
                <p className="mb-1 font-bold text-slate-950 dark:text-white">{formatTime(item.scheduled_for)}</p>
                {item.view_url ? (
                    <Link href={item.view_url} className="font-semibold text-slate-950 hover:text-cyan-700 dark:text-white dark:hover:text-cyan-300">
                        {item.bulletin || '-'}
                    </Link>
                ) : (
                    <p className="font-semibold text-slate-950 dark:text-white">{item.bulletin || '-'}</p>
                )}
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {[item.provider, item.model].filter(Boolean).join(' / ') || '-'}
                </p>
                <div className="mt-2 flex flex-wrap gap-1.5">
                    <StatusBadge tone={item.status_tone ?? 'neutral'}>{item.status_label || item.status || '-'}</StatusBadge>
                    <StatusBadge tone={isPast ? 'neutral' : 'info'}>{isPast ? pastLabel : upcomingLabel}</StatusBadge>
                    {item.ai_manual_approval_required && <StatusBadge tone="warning">{manualApprovalLabel}</StatusBadge>}
                    {item.source_label && <StatusBadge tone={item.source_tone ?? 'neutral'}>{item.source_label}</StatusBadge>}
                </div>
            </div>
            <div className="mt-3">
                <ActionButtonGroup actions={actions} />
            </div>
        </article>
    );
}
