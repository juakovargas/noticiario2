import AdminPageHeader from '@/Components/AdminPageHeader';
import {
    ActionButtonGroup,
    DashboardMetricCard,
    DashboardPanel,
    DashboardSectionHeader,
    EmptyStateCard,
    PipelineStepBar,
    ProviderBadge,
    StatusBadge,
    TodayTimeline,
} from '@/Components/EditorDashboard';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarClock,
    CheckCircle2,
    Clock3,
    FileText,
    Gauge,
    RadioTower,
    ShieldCheck,
} from 'lucide-react';
import { useMemo } from 'react';

const pipelineSteps = [
    'dashboard.pipeline.prompt',
    'dashboard.pipeline.ai',
    'dashboard.pipeline.script',
    'dashboard.pipeline.sources',
    'dashboard.pipeline.review',
    'dashboard.pipeline.production',
];

export default function Dashboard({
    headerActions = [],
    summaryCards = [],
    nextScheduledRuns = [],
    scheduledBulletins = [],
    actionableQueue = [],
    latestExecutions = [],
    scriptsNeedingReview = [],
}: any): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime, formatTime } = useDateFormatter();
    const incidents = actionableQueue.filter((item: any) => item.type !== 'source');
    const sourceTasks = actionableQueue.filter((item: any) => item.type === 'source');
    const timelineItems = useMemo(
        () => buildTimelineItems(nextScheduledRuns, latestExecutions, t),
        [latestExecutions, nextScheduledRuns, t],
    );

    return (
        <EditorLayout>
            <Head title={t('editor.dashboard.title')} />
            <AdminPageHeader
                helpKey="editor.dashboard"
                title={t('editor.dashboard.title')}
                description={t('editor.dashboard.description')}
            />

            <DashboardPanel accent="cyan" className="mb-6 bg-gradient-to-br from-white via-white to-cyan-50 dark:from-slate-950 dark:via-slate-950 dark:to-cyan-950/40">
                <div className="flex flex-col gap-5 p-6 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-cyan-700 dark:text-cyan-300">{t('dashboard.hero.eyebrow')}</p>
                        <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{t('dashboard.hero.title')}</h1>
                        <p className="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">{t('dashboard.hero.subtitle')}</p>
                    </div>
                    <ActionButtonGroup
                        actions={headerActions.map((action: any) => ({
                            key: action.key,
                            label: t(action.label),
                            href: action.href,
                            variant: action.key === 'createBulletin' ? 'default' : 'outline',
                        }))}
                    />
                </div>
            </DashboardPanel>

            <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                {summaryCards.map((card: any, index: number) => (
                    <DashboardMetricCard
                        key={card.key}
                        label={t(card.label)}
                        value={card.value ?? 0}
                        icon={summaryIcon(index)}
                        tone={summaryTone(card.key)}
                    />
                ))}
            </section>

            <DashboardPanel accent="violet" className="mt-6">
                <DashboardSectionHeader
                    icon={<Clock3 className="h-4 w-4" />}
                    title={t('dashboard.timeline.title')}
                    description={t('dashboard.timeline.description')}
                />
                <div className="p-5">
                    <TodayTimeline
                        items={timelineItems}
                        emptyLabel={t('dashboard.timeline.empty')}
                        currentTimeLabel={t('dashboard.timeline.now')}
                        pastLabel={t('dashboard.timeline.past')}
                        upcomingLabel={t('dashboard.timeline.upcoming')}
                        manualApprovalLabel={t('dashboard.ai.manualApproval')}
                        viewLabel={t('dashboard.action.viewRun')}
                        runNowLabel={t('dashboard.action.runNow')}
                        formatTime={formatTime}
                    />
                </div>
            </DashboardPanel>

            <DashboardPanel accent="cyan" className="mt-6">
                <DashboardSectionHeader
                    icon={<RadioTower className="h-4 w-4" />}
                    title={t('dashboard.scheduledBulletins.title')}
                    description={t('dashboard.scheduledBulletins.description')}
                />
                <div className="grid gap-3 p-5">
                    {scheduledBulletins.length === 0 && <EmptyStateCard title={t('dashboard.scheduledBulletins.empty')} />}
                    {scheduledBulletins.map((row: any) => (
                        <article
                            key={row.row_id}
                            className="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,0.9fr)_minmax(0,1.1fr)_minmax(0,1.35fr)_minmax(0,1.4fr)_auto]"
                        >
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <StatusBadge tone={row.is_on ? 'success' : 'neutral'}>{row.is_on ? t('dashboard.state.on') : t('dashboard.state.off')}</StatusBadge>
                                    {row.is_incomplete && <StatusBadge tone="warning">{t('dashboard.status.requiresAttention')}</StatusBadge>}
                                </div>
                                <Link className="mt-2 block truncate font-semibold text-slate-950 hover:text-cyan-700 dark:text-white dark:hover:text-cyan-300" href={row.bulletin_url}>
                                    {row.bulletin || '-'}
                                </Link>
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{[row.location, row.category].filter(Boolean).join(' · ') || '-'}</p>
                            </div>

                            <div>
                                <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{t('dashboard.table.schedule')}</p>
                                <p className="mt-1 font-medium text-slate-900 dark:text-slate-100">
                                    {[t(`frequency.${row.frequency ?? 'daily'}`), row.time?.slice(0, 5), row.timezone].filter(Boolean).join(' · ') || '-'}
                                </p>
                            </div>

                            <div>
                                <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{t('dashboard.table.nextRun')}</p>
                                <p className="mt-1 font-medium text-slate-900 dark:text-slate-100">{formatDateTime(row.next_run)}</p>
                                {row.ai_manual_approval_required && <div className="mt-2"><StatusBadge tone="warning">{t('dashboard.ai.manualApproval')}</StatusBadge></div>}
                            </div>

                            <ProviderBadge
                                provider={row.provider ? { name: row.provider, model: row.model, supports_grounding: row.grounded } : null}
                                missingLabel={t('bulletinTypes.provider.missing')}
                                groundedLabel={t('bulletinTypes.provider.grounded')}
                            />

                            <PipelineStepBar steps={pipelineFor(row.pipeline, t)} />

                            <ActionButtonGroup actions={scheduleActions(row, t)} />
                        </article>
                    ))}
                </div>
            </DashboardPanel>

            <section className="mt-6 grid gap-5 xl:grid-cols-2">
                <DashboardPanel accent="amber">
                    <DashboardSectionHeader
                        icon={<AlertTriangle className="h-4 w-4" />}
                        title={t('dashboard.incidents.title')}
                        description={t('dashboard.incidents.description')}
                    />
                    <div className="space-y-3 p-5">
                        {incidents.length === 0 && <EmptyStateCard title={t('dashboard.incidents.empty')} />}
                        {incidents.slice(0, 8).map((item: any) => (
                            <CompactWorkItem
                                key={item.id}
                                item={item}
                                t={t}
                                formatDateTime={formatDateTime}
                            />
                        ))}
                    </div>
                </DashboardPanel>

                <DashboardPanel accent="violet">
                    <DashboardSectionHeader
                        icon={<ShieldCheck className="h-4 w-4" />}
                        title={t('dashboard.review.title')}
                        description={t('dashboard.review.description')}
                    />
                    <div className="space-y-3 p-5">
                        {scriptsNeedingReview.length === 0 && sourceTasks.length === 0 && <EmptyStateCard title={t('dashboard.review.empty')} />}
                        {scriptsNeedingReview.slice(0, 5).map((script: any) => (
                            <article key={`script-${script.id}`} className="rounded-xl border border-slate-200 bg-white p-4 text-sm dark:border-slate-800 dark:bg-slate-950">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="font-semibold text-slate-950 dark:text-white">{script.title}</p>
                                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{script.bulletin || '-'} · {formatDateTime(script.updated_at)}</p>
                                    </div>
                                    <StatusBadge tone="warning">{t('status.scriptPendingReview')}</StatusBadge>
                                </div>
                                <div className="mt-3">
                                    <ActionButtonGroup actions={[{ key: 'review', label: t('dashboard.action.reviewScript'), href: script.url, variant: 'default' }]} align="start" />
                                </div>
                            </article>
                        ))}
                        {sourceTasks.slice(0, 5).map((item: any) => (
                            <CompactWorkItem key={item.id} item={item} t={t} formatDateTime={formatDateTime} />
                        ))}
                    </div>
                </DashboardPanel>
            </section>

            <DashboardPanel accent="cyan" className="mt-6">
                <DashboardSectionHeader
                    icon={<FileText className="h-4 w-4" />}
                    title={t('dashboard.latest.title')}
                    description={t('dashboard.latest.description')}
                />
                <div className="grid gap-3 p-5">
                    {latestExecutions.length === 0 && <EmptyStateCard title={t('dashboard.latest.empty')} />}
                    {latestExecutions.map((run: any) => {
                        const source = sourceStatus(run, t);

                        return (
                            <article key={run.id} className="rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-950">
                                <div className="grid gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)_minmax(0,1.4fr)_auto]">
                                    <div className="min-w-0">
                                        <Link href={run.run_url ?? '#'} className="font-semibold text-slate-950 hover:text-cyan-700 dark:text-white dark:hover:text-cyan-300">
                                            {run.bulletin || '-'}
                                        </Link>
                                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{formatDateTime(run.scheduled_for)}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{t('dashboard.table.provider')}</p>
                                        <p className="mt-1 text-slate-700 dark:text-slate-300">{[run.provider, run.model].filter(Boolean).join(' · ') || t('bulletinTypes.provider.missing')}</p>
                                    </div>
                                    <div className="min-w-0">
                                        <div className="mb-2 flex flex-wrap gap-1.5">
                                            <StatusBadge tone={statusTone(run.status)}>{t(statusKey(run.status))}</StatusBadge>
                                            <StatusBadge tone={source.tone}>{source.label}</StatusBadge>
                                            <StatusBadge tone="info">{t(run.next_action)}</StatusBadge>
                                        </div>
                                        <PipelineStepBar steps={pipelineFor(run.pipeline, t)} />
                                    </div>
                                    <ActionButtonGroup actions={latestActions(run, t)} />
                                </div>
                            </article>
                        );
                    })}
                </div>
            </DashboardPanel>
        </EditorLayout>
    );
}

function CompactWorkItem({ item, t, formatDateTime }: { item: any; t: (key: string) => string; formatDateTime: (value?: string | null) => string }): JSX.Element {
    return (
        <article className="rounded-xl border border-slate-200 bg-white p-4 text-sm dark:border-slate-800 dark:bg-slate-950">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="font-semibold text-slate-950 dark:text-white">{item.bulletin || t(item.type_label)}</p>
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {t(item.type_label)} · {formatDateTime(item.scheduled_for)}
                    </p>
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{[item.provider, item.model].filter(Boolean).join(' · ') || t('bulletinTypes.provider.missing')}</p>
                </div>
                <StatusBadge tone={statusTone(item.status)}>{t(statusKey(item.status))}</StatusBadge>
            </div>
            <div className="mt-3">
                <ActionButtonGroup
                    align="start"
                    actions={[
                        { key: 'next', label: t(item.next_action), href: item.action_url, method: item.action_method === 'post' ? 'post' : 'get', variant: 'default' },
                        { key: 'view', label: t('dashboard.action.view'), href: item.view_url, variant: 'outline' },
                    ]}
                />
            </div>
        </article>
    );
}

function summaryIcon(index: number): JSX.Element {
    const icons = [
        <CalendarClock className="h-4 w-4" />,
        <Clock3 className="h-4 w-4" />,
        <AlertTriangle className="h-4 w-4" />,
        <ShieldCheck className="h-4 w-4" />,
        <CheckCircle2 className="h-4 w-4" />,
        <Gauge className="h-4 w-4" />,
    ];

    return icons[index] ?? <Gauge className="h-4 w-4" />;
}

function summaryTone(key: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet' {
    if (key === 'overdue') return 'warning';
    if (key === 'failed') return 'danger';
    if (key === 'readyProduction') return 'success';
    if (key === 'sourcesPending') return 'violet';
    return 'info';
}

function buildTimelineItems(nextScheduledRuns: any[], latestExecutions: any[], t: (key: string) => string): any[] {
    const executionItems = latestExecutions.map((run) => {
        const source = sourceStatus(run, t);

        return {
            id: `run-${run.id}`,
            type: 'execution',
            bulletin: run.bulletin,
            provider: run.provider,
            model: run.model,
            scheduled_for: run.scheduled_for,
            status: run.status,
            status_label: t(statusKey(run.status)),
            status_tone: statusTone(run.status),
            source_label: source.label,
            source_tone: source.tone,
            view_url: run.run_url,
            action_url: run.action_url || run.script_url || run.run_url,
            action_method: run.action_method,
            action_label: t(run.next_action),
        };
    });

    const executionTimes = new Set(executionItems.map((item) => `${item.bulletin}|${item.scheduled_for}`));
    const scheduledItems = nextScheduledRuns
        .filter((run) => !executionTimes.has(`${run.bulletin}|${run.scheduled_for}`))
        .map((run) => ({
            id: `schedule-${run.id}`,
            type: 'schedule',
            bulletin: run.bulletin,
            provider: run.provider,
            model: run.model,
            scheduled_for: run.scheduled_for,
            status: 'scheduled',
            status_label: t('status.scheduled'),
            status_tone: 'info',
            ai_manual_approval_required: run.ai_manual_approval_required,
            view_url: run.view_url,
            run_now_url: run.run_now_url,
        }));

    return [...executionItems, ...scheduledItems];
}

function scheduleActions(row: any, t: (key: string) => string): any[] {
    return [
        { key: 'run', label: t('dashboard.action.runNow'), href: row.run_now_url, method: 'post', variant: 'default' },
        { key: 'open', label: t('dashboard.action.view'), href: row.view_url, variant: 'outline' },
        { key: 'runs', label: t('bulletinTypes.action.executions'), href: row.runs_url, variant: 'outline' },
        { key: 'schedule', label: t('dashboard.action.viewSchedule'), href: row.schedule_url, variant: 'outline' },
    ];
}

function latestActions(run: any, t: (key: string) => string): any[] {
    return [
        { key: 'next', label: t(run.next_action), href: run.action_url || run.script_url || run.run_url, method: run.action_method === 'post' ? 'post' : 'get', variant: 'default' },
        { key: 'run', label: t('dashboard.action.viewRun'), href: run.run_url, variant: 'outline' },
        { key: 'script', label: t('dashboard.action.viewScript'), href: run.script_url, variant: 'outline' },
        { key: 'sources', label: t('dashboard.action.reviewSources'), href: run.sources_url, variant: 'outline' },
        { key: 'prompt', label: t('dashboard.action.viewPromptRun'), href: run.prompt_run_url, variant: 'outline' },
    ];
}

function pipelineFor(rawSteps: string[] = [], t: (key: string) => string): Array<{ key: string; label: string; complete: boolean; tone: 'neutral' | 'warning' | 'danger' }> {
    const normalized = rawSteps.map(statusKey);
    return pipelineSteps.map((step) => {
        const complete = normalized.includes(step) || normalized.includes(statusKeyToPipeline(step));
        return { key: step, label: t(step), complete, tone: step === 'dashboard.pipeline.ai' ? 'warning' : 'neutral' };
    });
}

function statusKeyToPipeline(step: string): string {
    const map: Record<string, string> = {
        'dashboard.pipeline.prompt': 'status.promptGenerated',
        'dashboard.pipeline.ai': 'status.responseReceived',
        'dashboard.pipeline.script': 'status.scriptCreated',
        'dashboard.pipeline.sources': 'status.sourcesVerified',
        'dashboard.pipeline.production': 'status.readyForProduction',
    };
    return map[step] ?? step;
}

function sourceStatus(run: any, t: (key: string) => string): { label: string; tone: 'neutral' | 'success' | 'warning' | 'danger' | 'violet' } {
    const total = Number(run.sources_total ?? 0);
    const pending = Number(run.sources_pending ?? 0);
    const verified = Number(run.sources_verified ?? 0);
    const rejected = Number(run.sources_rejected ?? 0);

    if (total === 0) return { label: t('sourceStatus.none'), tone: 'neutral' };
    if (rejected > 0) return { label: t('sourceStatus.rejected'), tone: 'danger' };
    if (pending > 0 && verified > 0) return { label: t('sourceStatus.mixed'), tone: 'warning' };
    if (pending > 0) return { label: t('sourceStatus.pending'), tone: 'warning' };
    if (verified === total) return { label: t('sourceStatus.verified'), tone: 'success' };

    return { label: t('sourceStatus.mixed'), tone: 'violet' };
}

function statusTone(status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet' {
    if (['completed', 'script_created', 'prompt_generated', 'ready_for_production', 'sources_verified', 'scheduled'].includes(status)) return 'success';
    if (['failed', 'failed_today', 'missing_ai_provider', 'missing_schedule', 'missing_bulletin_type'].includes(status)) return 'danger';
    if (['schedule_overdue', 'waiting_ai_response', 'sources_pending_verification'].includes(status)) return 'warning';
    return 'neutral';
}

function statusKey(status: string): string {
    const map: Record<string, string> = {
        created: 'status.created',
        prompt_run_created: 'status.promptRunCreated',
        prompt_ready: 'status.promptGenerated',
        prompt_generated: 'status.promptGenerated',
        waiting_ai_response: 'status.waitingAiResponse',
        response_received: 'status.responseReceived',
        script_created: 'status.scriptCreated',
        completed: 'status.completed',
        failed: 'status.failed',
        failed_today: 'status.failedToday',
        schedule_overdue: 'status.scheduleOverdue',
        scheduled: 'status.scheduled',
        missing_ai_provider: 'status.missingAiProvider',
        missing_schedule: 'status.missingSchedule',
        missing_bulletin_type: 'status.missingBulletinType',
        script_pending_review: 'status.scriptPendingReview',
        sources_pending_verification: 'status.sourcesPendingVerification',
        sources_verified: 'status.sourcesVerified',
        ready_for_production: 'status.readyForProduction',
        'dashboard.pipeline.missingProvider': 'dashboard.pipeline.missingProvider',
        'dashboard.pipeline.missingSchedule': 'dashboard.pipeline.missingSchedule',
        'dashboard.pipeline.missingBulletin': 'dashboard.pipeline.missingBulletin',
        'dashboard.pipeline.manualAiApproval': 'dashboard.pipeline.manualAiApproval',
        'dashboard.pipeline.scheduled': 'dashboard.pipeline.scheduled',
        'dashboard.pipeline.incomplete': 'dashboard.pipeline.incomplete',
        'dashboard.pipeline.noPromptRun': 'dashboard.pipeline.noPromptRun',
        'dashboard.pipeline.noScript': 'dashboard.pipeline.noScript',
    };

    return map[status] ?? status;
}
