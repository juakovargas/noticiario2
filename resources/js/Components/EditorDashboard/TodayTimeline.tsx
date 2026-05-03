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
    const markerIndex = sortedItems.findIndex((item) => new Date(item.scheduled_for as string).getTime() >= now.getTime());
    const insertMarkerAt = markerIndex === -1 ? sortedItems.length : markerIndex;

    if (!sortedItems.length) {
        return <p className="rounded-xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">{emptyLabel}</p>;
    }

    return (
        <div className="relative">
            <div className="absolute bottom-0 left-[4.25rem] top-0 hidden w-px bg-slate-200 dark:bg-slate-800 sm:block" />
            <div className="space-y-3">
                {sortedItems.map((item, index) => (
                    <div key={`${item.type ?? 'item'}-${item.id}`}>
                        {index === insertMarkerAt && <CurrentTimeMarker label={currentTimeLabel} />}
                        <TimelineCard
                            item={item}
                            isPast={new Date(item.scheduled_for as string).getTime() < now.getTime()}
                            pastLabel={pastLabel}
                            upcomingLabel={upcomingLabel}
                            manualApprovalLabel={manualApprovalLabel}
                            viewLabel={viewLabel}
                            runNowLabel={runNowLabel}
                            formatTime={formatTime}
                        />
                    </div>
                ))}
                {insertMarkerAt === sortedItems.length && <CurrentTimeMarker label={currentTimeLabel} />}
            </div>
        </div>
    );
}

function CurrentTimeMarker({ label }: { label: string }): JSX.Element {
    return (
        <div className="relative flex items-center gap-3 py-1 sm:pl-[5.5rem]">
            <span className="absolute left-[3.85rem] hidden h-3 w-3 rounded-full border-2 border-white bg-cyan-500 ring-4 ring-cyan-100 dark:border-slate-950 dark:ring-cyan-950 sm:block" />
            <span className="inline-flex items-center gap-2 rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700 dark:border-cyan-900 dark:bg-cyan-950 dark:text-cyan-200">
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
        <article className="relative grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950 sm:grid-cols-[4.5rem_minmax(0,1fr)_auto] sm:pl-0">
            <div className="flex items-center gap-2 sm:justify-center">
                <span className="hidden h-3 w-3 rounded-full border-2 border-white bg-slate-400 ring-4 ring-slate-100 dark:border-slate-950 dark:ring-slate-800 sm:absolute sm:left-[3.85rem] sm:block" />
                <span className="text-sm font-bold text-slate-950 dark:text-white">{formatTime(item.scheduled_for)}</span>
            </div>
            <div className="min-w-0">
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
            <div className="sm:justify-self-end">
                <ActionButtonGroup actions={actions} />
            </div>
        </article>
    );
}
