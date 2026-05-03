import AdminPageHeader from '@/Components/AdminPageHeader';
import {
    ActionButtonGroup,
    CoverageMatrix,
    DashboardMetricCard,
    DashboardPanel,
    DashboardSectionHeader,
    EmptyStateCard,
    OperationalTable,
    PipelineStepBar,
    ProviderBadge,
    ScheduleTimeline,
    StatusBadge,
} from '@/Components/EditorDashboard';
import WorldBulletinMap from '@/Components/Maps/WorldBulletinMap';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    Bot,
    CalendarClock,
    CheckCircle2,
    Clock3,
    Gauge,
    Layers3,
    MapPinned,
    RadioTower,
    ShieldCheck,
    Table2,
} from 'lucide-react';

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
    mapOverview = {},
    nextScheduledRuns = [],
    scheduledBulletins = [],
    actionableQueue = [],
    aiEngines = [],
    coverageByLocationTopic = { groups: [] },
    latestExecutions = [],
}: any): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();

    return (
        <EditorLayout>
            <Head title={t('editor.dashboard.title')} />
            <AdminPageHeader
                helpKey="editor.dashboard"
                title={t('editor.dashboard.title')}
                description={t('editor.dashboard.description')}
            />

            <DashboardPanel accent="cyan" className="mb-6 bg-gradient-to-br from-white via-white to-cyan-50 dark:from-slate-950 dark:via-slate-950 dark:to-cyan-950/40">
                <div className="grid gap-5 p-6 lg:grid-cols-[1.6fr_1fr]">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-cyan-700 dark:text-cyan-300">{t('dashboard.hero.eyebrow')}</p>
                        <h1 className="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">{t('dashboard.hero.title')}</h1>
                        <p className="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">{t('dashboard.hero.subtitle')}</p>
                    </div>
                    <div className="flex flex-wrap items-start justify-start gap-2 lg:justify-end">
                        <ActionButtonGroup
                            actions={headerActions.map((action: any) => ({
                                key: action.key,
                                label: t(action.label),
                                href: action.href,
                                variant: action.key === 'createBulletin' ? 'default' : 'outline',
                            }))}
                        />
                    </div>
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

            <section className="mt-6 grid gap-5 xl:grid-cols-[1.4fr_0.9fr]">
                <DashboardPanel accent="violet">
                    <DashboardSectionHeader
                        icon={<MapPinned className="h-4 w-4" />}
                        title={t('dashboard.map.title')}
                        description={t('dashboard.map.description')}
                        action={mapOverview.mapRoute ? <Button asChild size="sm" variant="outline"><Link href={mapOverview.mapRoute}>{t('dashboard.action.viewFullMap')}</Link></Button> : null}
                    />
                    <div className="space-y-4 p-5">
                        <div className="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                            <WorldBulletinMap panel="editor" markers={mapOverview.markers || []} />
                        </div>
                        <div className="grid gap-3 md:grid-cols-3">
                            {(mapOverview.locations || []).slice(0, 6).map((location: any) => (
                                <div key={location.location} className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm dark:border-slate-800 dark:bg-slate-900/60">
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="font-semibold text-slate-950 dark:text-white">{t(location.location)}</p>
                                        <StatusBadge tone={location.issues ? 'warning' : 'success'}>{location.issues}</StatusBadge>
                                    </div>
                                    <p className="mt-2 text-slate-500 dark:text-slate-400">{t('dashboard.map.activeBulletins')}: {location.active_bulletins}</p>
                                    <p className="text-slate-500 dark:text-slate-400">{t('dashboard.map.nextRun')}: {formatDateTime(location.next_run)}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </DashboardPanel>

                <DashboardPanel accent="emerald">
                    <DashboardSectionHeader icon={<CalendarClock className="h-4 w-4" />} title={t('dashboard.nextRuns.title')} description={t('dashboard.nextRuns.description')} />
                    <div className="p-5">
                        <ScheduleTimeline
                            items={nextScheduledRuns}
                            emptyLabel={t('dashboard.nextRuns.empty')}
                            runNowLabel={t('dashboard.action.runNow')}
                            configureLabel={t('dashboard.action.configure')}
                            manualApprovalLabel={t('dashboard.ai.manualApproval')}
                            formatDateTime={formatDateTime}
                        />
                    </div>
                </DashboardPanel>
            </section>

            <section className="mt-6 grid gap-5 xl:grid-cols-[1.6fr_0.8fr]">
                <DashboardPanel accent="cyan">
                    <DashboardSectionHeader icon={<Table2 className="h-4 w-4" />} title={t('dashboard.scheduledBulletins.title')} description={t('dashboard.scheduledBulletins.description')} />
                    <OperationalTable minWidth="1180px">
                        <thead>
                            <tr className="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                <th className="px-5 py-3">{t('dashboard.table.onOff')}</th>
                                <th className="px-5 py-3">{t('dashboard.table.bulletin')}</th>
                                <th className="px-5 py-3">{t('dashboard.table.location')}</th>
                                <th className="px-5 py-3">{t('dashboard.table.provider')}</th>
                                <th className="px-5 py-3">{t('dashboard.table.nextRun')}</th>
                                <th className="px-5 py-3">{t('dashboard.table.pipeline')}</th>
                                <th className="px-5 py-3 text-right">{t('dashboard.table.actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {scheduledBulletins.map((row: any) => (
                                <tr key={row.row_id} className="border-b border-slate-100 align-top hover:bg-slate-50/80 dark:border-slate-900 dark:hover:bg-slate-900/60">
                                    <td className="px-5 py-4"><StatusBadge tone={row.is_on ? 'success' : 'neutral'}>{row.is_on ? t('dashboard.state.on') : t('dashboard.state.off')}</StatusBadge></td>
                                    <td className="px-5 py-4">
                                        <Link className="font-semibold text-slate-950 hover:underline dark:text-white" href={row.bulletin_url}>{row.bulletin || '-'}</Link>
                                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{row.frequency || t('dashboard.schedule.noFrequency')} · {row.time?.slice(0, 5) || t('dashboard.schedule.noTime')}</p>
                                    </td>
                                    <td className="px-5 py-4 text-slate-600 dark:text-slate-300">{[row.location, row.category].filter(Boolean).join(' · ') || '-'}</td>
                                    <td className="px-5 py-4"><ProviderBadge provider={row.provider ? { name: row.provider, model: row.model, supports_grounding: row.grounded } : null} missingLabel={t('bulletinTypes.provider.missing')} groundedLabel={t('bulletinTypes.provider.grounded')} /></td>
                                    <td className="px-5 py-4">
                                        <p>{formatDateTime(row.next_run)}</p>
                                        {row.ai_manual_approval_required && <p className="mt-1 text-xs text-amber-600 dark:text-amber-300">{t('dashboard.ai.manualApproval')}</p>}
                                    </td>
                                    <td className="px-5 py-4"><PipelineStepBar steps={pipelineFor(row.pipeline, t)} /></td>
                                    <td className="px-5 py-4">
                                        <div className="flex justify-end gap-2">
                                            {row.view_url && <Button asChild size="sm" variant="outline"><Link href={row.view_url}>{t('dashboard.action.configure')}</Link></Button>}
                                            {isRealUrl(row.run_now_url) && <Button asChild size="sm"><Link href={row.run_now_url} method="post" as="button">{t('dashboard.action.runNow')}</Link></Button>}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </OperationalTable>
                </DashboardPanel>

                <DashboardPanel accent="amber">
                    <DashboardSectionHeader icon={<AlertTriangle className="h-4 w-4" />} title={t('dashboard.queue.title')} description={t('dashboard.queue.description')} />
                    <div className="space-y-3 p-5">
                        {actionableQueue.length === 0 && <EmptyStateCard title={t('dashboard.queue.empty')} />}
                        {actionableQueue.slice(0, 10).map((item: any) => (
                            <div key={item.id} className="rounded-lg border border-slate-200 bg-white p-4 text-sm dark:border-slate-800 dark:bg-slate-950">
                                <div className="flex items-start justify-between gap-2">
                                    <div>
                                        <p className="font-semibold text-slate-950 dark:text-white">{t(item.type_label)}</p>
                                        <p className="text-slate-600 dark:text-slate-300">{item.bulletin || '-'}</p>
                                    </div>
                                    <StatusBadge tone={statusTone(item.status)}>{t(statusKey(item.status))}</StatusBadge>
                                </div>
                                <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">{[item.provider, item.model].filter(Boolean).join(' · ') || t('bulletinTypes.provider.missing')}</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {item.view_url && <Button asChild size="sm" variant="outline"><Link href={item.view_url}>{t('dashboard.action.view')}</Link></Button>}
                                    {isRealUrl(item.action_url) && item.action_method === 'post' && <Button asChild size="sm"><Link href={item.action_url} method="post" as="button">{t(item.next_action)}</Link></Button>}
                                </div>
                            </div>
                        ))}
                    </div>
                </DashboardPanel>
            </section>

            <section className="mt-6 grid gap-5 xl:grid-cols-2">
                <DashboardPanel accent="violet">
                    <DashboardSectionHeader icon={<Bot className="h-4 w-4" />} title={t('dashboard.aiEngines.title')} description={t('dashboard.aiEngines.description')} />
                    <div className="grid gap-3 p-5 md:grid-cols-2">
                        {aiEngines.map((engine: any) => (
                            <div key={engine.id} className="rounded-lg border border-slate-200 bg-white p-4 text-sm dark:border-slate-800 dark:bg-slate-950">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-semibold text-slate-950 dark:text-white">{engine.name}</p>
                                        <p className="text-xs text-slate-500 dark:text-slate-400">{engine.model || '-'}</p>
                                    </div>
                                    <StatusBadge tone={engine.availability === 'available' ? 'success' : engine.availability === 'rate_limited' ? 'warning' : 'danger'}>{t(engine.availability_label)}</StatusBadge>
                                </div>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    <StatusBadge tone={engine.grounded ? 'success' : 'neutral'}>{engine.grounded ? t('bulletinTypes.provider.grounded') : t('bulletinTypes.provider.notGrounded')}</StatusBadge>
                                    <StatusBadge tone={engine.env_key_configured ? 'success' : 'danger'}>{engine.env_key_configured ? t('dashboard.ai.keyReady') : t('dashboard.ai.keyMissing')}</StatusBadge>
                                </div>
                                <p className="mt-3 text-xs text-slate-500 dark:text-slate-400">{t('dashboard.ai.usageToday')}: {engine.usage_today}</p>
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{t('dashboard.ai.bulletinsUsing')}: {engine.bulletins.join(', ') || '-'}</p>
                            </div>
                        ))}
                    </div>
                </DashboardPanel>

                <DashboardPanel accent="emerald">
                    <DashboardSectionHeader icon={<Layers3 className="h-4 w-4" />} title={t('dashboard.coverage.title')} description={t(coverageByLocationTopic.explanation)} />
                    <div className="p-5">
                        <CoverageMatrix
                            groups={coverageByLocationTopic.groups}
                            labels={{
                                active: t('dashboard.coverage.active'),
                                paused: t('dashboard.coverage.paused'),
                                missingProvider: t('dashboard.coverage.missingProvider'),
                                missingSchedule: t('dashboard.coverage.missingSchedule'),
                                failed: t('dashboard.coverage.failed'),
                            }}
                        />
                    </div>
                </DashboardPanel>
            </section>

            <DashboardPanel accent="cyan" className="mt-6">
                <DashboardSectionHeader icon={<RadioTower className="h-4 w-4" />} title={t('dashboard.latest.title')} description={t('dashboard.latest.description')} />
                <div className="space-y-3 p-5">
                    {latestExecutions.length === 0 && <EmptyStateCard title={t('dashboard.latest.empty')} />}
                    {latestExecutions.map((run: any) => (
                        <div key={run.id} className="rounded-lg border border-slate-200 bg-white p-4 text-sm dark:border-slate-800 dark:bg-slate-950">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="font-semibold text-slate-950 dark:text-white">{run.bulletin || '-'}</p>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">{formatDateTime(run.scheduled_for)} · {[run.provider, run.model].filter(Boolean).join(' · ') || t('bulletinTypes.provider.missing')}</p>
                                </div>
                                <StatusBadge tone={statusTone(run.status)}>{t(statusKey(run.status))}</StatusBadge>
                            </div>
                            <div className="mt-3 grid gap-2 text-slate-600 dark:text-slate-300 md:grid-cols-3">
                                <p>{t('dashboard.latest.sources')}: {run.sources_pending}/{run.sources_total}</p>
                                <p>{t('dashboard.latest.nextAction')}: {t(run.next_action)}</p>
                                <p>{t('dashboard.latest.script')}: {run.script?.title || '-'}</p>
                            </div>
                            <div className="mt-4"><PipelineStepBar steps={pipelineFor(run.pipeline, t)} /></div>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {run.run_url && <Button asChild size="sm" variant="outline"><Link href={run.run_url}>{t('dashboard.action.viewRun')}</Link></Button>}
                                {run.prompt_run_url && <Button asChild size="sm" variant="outline"><Link href={run.prompt_run_url}>{t('dashboard.action.viewPromptRun')}</Link></Button>}
                                {run.script_url && <Button asChild size="sm" variant="outline"><Link href={run.script_url}>{t('dashboard.action.viewScript')}</Link></Button>}
                                {run.sources_url && <Button asChild size="sm" variant="outline"><Link href={run.sources_url}>{t('dashboard.action.reviewSources')}</Link></Button>}
                                {isRealUrl(run.action_url) && run.action_method === 'post' && <Button asChild size="sm"><Link href={run.action_url} method="post" as="button">{t(run.next_action)}</Link></Button>}
                            </div>
                        </div>
                    ))}
                </div>
            </DashboardPanel>
        </EditorLayout>
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

function pipelineFor(rawSteps: string[] = [], t: (key: string) => string): Array<{ key: string; label: string; complete: boolean }> {
    const normalized = rawSteps.map(statusKey);
    return pipelineSteps.map((step) => ({ key: step, label: t(step), complete: normalized.includes(step) || normalized.includes(statusKeyToPipeline(step)) }));
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

function statusTone(status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet' {
    if (['completed', 'script_created', 'prompt_generated', 'ready_for_production', 'sources_verified', 'scheduled'].includes(status)) return 'success';
    if (['failed', 'failed_today', 'missing_ai_provider', 'missing_schedule', 'missing_bulletin_type'].includes(status)) return 'danger';
    if (['schedule_overdue', 'waiting_ai_response'].includes(status)) return 'warning';
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

function isRealUrl(url?: string | null): boolean {
    return Boolean(url && url !== '#');
}
