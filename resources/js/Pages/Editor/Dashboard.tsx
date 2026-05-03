import AdminPageHeader from '@/Components/AdminPageHeader';
import WorldBulletinMap from '@/Components/Maps/WorldBulletinMap';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
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
    PlayCircle,
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
    'dashboard.pipeline.audioFuture',
    'dashboard.pipeline.videoFuture',
    'dashboard.pipeline.publishingFuture',
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

            <div className="mb-6 flex flex-wrap items-center gap-2">
                {headerActions.map((action: any) => (
                    <Button key={action.key} asChild variant={action.key === 'createBulletin' ? 'default' : 'outline'} size="sm">
                        <Link href={action.href}>{t(action.label)}</Link>
                    </Button>
                ))}
            </div>

            <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                {summaryCards.map((card: any, index: number) => (
                    <Card key={card.key} className="rounded-lg">
                        <CardContent className="p-5">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs font-medium uppercase text-slate-500">{t(card.label)}</p>
                                    <p className="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">{card.value ?? 0}</p>
                                </div>
                                <span className="rounded-lg bg-slate-100 p-2 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {summaryIcon(index)}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </section>

            <section className="mt-6 grid gap-4 xl:grid-cols-3">
                <Card className="rounded-lg xl:col-span-2">
                    <CardHeader className="flex flex-row items-center justify-between gap-3">
                        <CardTitle className="flex items-center gap-2"><MapPinned className="h-4 w-4" />{t('dashboard.map.title')}</CardTitle>
                        <Button asChild variant="outline" size="sm"><Link href={mapOverview.mapRoute}>{t('dashboard.action.viewFullMap')}</Link></Button>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <WorldBulletinMap panel="editor" markers={mapOverview.markers || []} />
                        <div className="grid gap-3 md:grid-cols-3">
                            {(mapOverview.locations || []).slice(0, 6).map((location: any) => (
                                <div key={location.location} className="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-800">
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="font-semibold">{t(location.location)}</p>
                                        <Badge variant={location.issues ? 'danger' : 'success'}>{location.issues}</Badge>
                                    </div>
                                    <p className="mt-1 text-slate-500">{t('dashboard.map.activeBulletins')}: {location.active_bulletins}</p>
                                    <p className="text-slate-500">{t('dashboard.map.nextRun')}: {formatDateTime(location.next_run)}</p>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-lg">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><CalendarClock className="h-4 w-4" />{t('dashboard.nextRuns.title')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {nextScheduledRuns.length === 0 && <EmptyText text={t('dashboard.nextRuns.empty')} />}
                        {nextScheduledRuns.map((run: any) => (
                            <div key={run.id} className="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-800">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-semibold">{run.bulletin}</p>
                                        <p className="text-xs text-slate-500">{formatDateTime(run.scheduled_for)}</p>
                                    </div>
                                    {run.ai_manual_approval_required && <Badge variant="outline">{t('dashboard.ai.manualApproval')}</Badge>}
                                </div>
                                <p className="mt-2 text-xs text-slate-500">{providerLabel(run, t)}</p>
                                <div className="mt-3 flex gap-2">
                                    <Button asChild size="sm" variant="outline"><Link href={run.view_url}>{t('dashboard.action.configure')}</Link></Button>
                                    {isRealUrl(run.run_now_url) && (
                                        <Button asChild size="sm"><Link href={run.run_now_url} method="post" as="button"><PlayCircle className="h-4 w-4" />{t('dashboard.action.runNow')}</Link></Button>
                                    )}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </section>

            <section className="mt-6 grid gap-4 xl:grid-cols-3">
                <Card className="rounded-lg xl:col-span-2">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><Table2 className="h-4 w-4" />{t('dashboard.scheduledBulletins.title')}</CardTitle>
                    </CardHeader>
                    <CardContent className="overflow-x-auto">
                        <table className="w-full min-w-[1180px] text-sm">
                            <thead>
                                <tr className="border-b border-slate-200 text-left text-xs uppercase text-slate-500 dark:border-slate-800">
                                    <th className="py-3 pr-3">{t('dashboard.table.onOff')}</th>
                                    <th className="py-3 pr-3">{t('dashboard.table.bulletin')}</th>
                                    <th className="py-3 pr-3">{t('dashboard.table.location')}</th>
                                    <th className="py-3 pr-3">{t('dashboard.table.provider')}</th>
                                    <th className="py-3 pr-3">{t('dashboard.table.nextRun')}</th>
                                    <th className="py-3 pr-3">{t('dashboard.table.pipeline')}</th>
                                    <th className="py-3 pr-3 text-right">{t('dashboard.table.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {scheduledBulletins.map((row: any) => (
                                    <tr key={row.row_id} className="border-b border-slate-100 align-top dark:border-slate-800">
                                        <td className="py-3 pr-3"><Badge variant={row.is_on ? 'success' : 'outline'}>{row.is_on ? 'ON' : 'OFF'}</Badge></td>
                                        <td className="py-3 pr-3">
                                            <Link className="font-semibold hover:underline" href={row.bulletin_url}>{row.bulletin || '-'}</Link>
                                            <p className="text-xs text-slate-500">{row.frequency || t('dashboard.schedule.noFrequency')} · {row.time?.slice(0, 5) || t('dashboard.schedule.noTime')}</p>
                                        </td>
                                        <td className="py-3 pr-3">{[row.location, row.category].filter(Boolean).join(' · ') || '-'}</td>
                                        <td className="py-3 pr-3">{row.provider ? <ProviderBlock item={row} /> : <Badge variant="danger">{t('bulletinTypes.provider.missing')}</Badge>}</td>
                                        <td className="py-3 pr-3">
                                            <p>{formatDateTime(row.next_run)}</p>
                                            {row.ai_manual_approval_required && <p className="mt-1 text-xs text-amber-600 dark:text-amber-300">{t('dashboard.ai.manualApproval')}</p>}
                                        </td>
                                        <td className="py-3 pr-3"><PipelineChips steps={row.pipeline} t={t} /></td>
                                        <td className="py-3 pr-3">
                                            <div className="flex justify-end gap-2">
                                                <Button asChild size="sm" variant="outline"><Link href={row.view_url}>{t('dashboard.action.configure')}</Link></Button>
                                                {isRealUrl(row.run_now_url) && <Button asChild size="sm"><Link href={row.run_now_url} method="post" as="button">{t('dashboard.action.runNow')}</Link></Button>}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card className="rounded-lg">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><AlertTriangle className="h-4 w-4" />{t('dashboard.queue.title')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {actionableQueue.length === 0 && <EmptyText text={t('dashboard.queue.empty')} />}
                        {actionableQueue.slice(0, 10).map((item: any) => (
                            <div key={item.id} className="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-800">
                                <div className="mb-1 flex items-center justify-between gap-2">
                                    <p className="font-semibold">{t(item.type_label)}</p>
                                    <Badge variant={statusVariant(item.status)}>{t(statusKey(item.status))}</Badge>
                                </div>
                                <p>{item.bulletin || '-'}</p>
                                <p className="text-xs text-slate-500">{providerLabel(item, t)}</p>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {item.view_url && <Button asChild size="sm" variant="outline"><Link href={item.view_url}>{t('dashboard.action.view')}</Link></Button>}
                                    {isRealUrl(item.action_url) && item.action_method === 'post' && <Button asChild size="sm"><Link href={item.action_url} method="post" as="button">{t(item.next_action)}</Link></Button>}
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </section>

            <section className="mt-6 grid gap-4 xl:grid-cols-2">
                <Card className="rounded-lg">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><Bot className="h-4 w-4" />{t('dashboard.aiEngines.title')}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 md:grid-cols-2">
                        {aiEngines.map((engine: any) => (
                            <div key={engine.id} className="rounded-lg border border-slate-200 p-4 text-sm dark:border-slate-800">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-semibold">{engine.name}</p>
                                        <p className="text-xs text-slate-500">{engine.model || '-'}</p>
                                    </div>
                                    <Badge variant={engine.availability === 'available' ? 'success' : 'danger'}>{t(engine.availability_label)}</Badge>
                                </div>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    <Badge variant={engine.grounded ? 'success' : 'outline'}>{engine.grounded ? t('bulletinTypes.provider.grounded') : t('bulletinTypes.provider.notGrounded')}</Badge>
                                    <Badge variant={engine.env_key_configured ? 'success' : 'danger'}>{engine.env_key_configured ? t('dashboard.ai.keyReady') : t('dashboard.ai.keyMissing')}</Badge>
                                </div>
                                <p className="mt-3 text-xs text-slate-500">{t('dashboard.ai.usageToday')}: {engine.usage_today}</p>
                                <p className="mt-1 text-xs text-slate-500">{t('dashboard.ai.bulletinsUsing')}: {engine.bulletins.join(', ') || '-'}</p>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Card className="rounded-lg">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><Layers3 className="h-4 w-4" />{t('dashboard.coverage.title')}</CardTitle>
                        <p className="text-sm text-slate-500">{t(coverageByLocationTopic.explanation)}</p>
                    </CardHeader>
                    <CardContent className="grid gap-3 md:grid-cols-2">
                        {coverageByLocationTopic.groups.map((group: any, index: number) => (
                            <div key={`${group.location}-${group.category}-${index}`} className="rounded-lg border border-slate-200 p-3 text-sm dark:border-slate-800">
                                <p className="font-semibold">{t(group.location)} · {t(group.category)}</p>
                                <div className="mt-2 flex flex-wrap gap-1">
                                    <Badge variant="success">{t('dashboard.coverage.active')}: {group.active}</Badge>
                                    <Badge variant="outline">{t('dashboard.coverage.paused')}: {group.paused}</Badge>
                                    <Badge variant="danger">{t('dashboard.coverage.missingProvider')}: {group.missing_provider}</Badge>
                                    <Badge variant="outline">{t('dashboard.coverage.missingSchedule')}: {group.missing_schedule}</Badge>
                                    <Badge variant="danger">{t('dashboard.coverage.failed')}: {group.failed}</Badge>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </section>

            <Card className="mt-6 rounded-lg">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2"><RadioTower className="h-4 w-4" />{t('dashboard.latest.title')}</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    {latestExecutions.length === 0 && <EmptyText text={t('dashboard.latest.empty')} />}
                    {latestExecutions.map((run: any) => (
                        <div key={run.id} className="rounded-lg border border-slate-200 p-4 text-sm dark:border-slate-800">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="font-semibold">{run.bulletin || '-'}</p>
                                    <p className="text-xs text-slate-500">{formatDateTime(run.scheduled_for)} · {providerLabel(run, t)}</p>
                                </div>
                                <Badge variant={statusVariant(run.status)}>{t(statusKey(run.status))}</Badge>
                            </div>
                            <div className="mt-3 grid gap-2 md:grid-cols-3">
                                <p>{t('dashboard.latest.sources')}: {run.sources_pending}/{run.sources_total}</p>
                                <p>{t('dashboard.latest.nextAction')}: {t(run.next_action)}</p>
                                <p>{t('dashboard.latest.script')}: {run.script?.title || '-'}</p>
                            </div>
                            <div className="mt-3 flex flex-wrap gap-1">
                                {pipelineSteps.map((step, index) => (
                                    <span key={`${run.id}-${step}`} className={`rounded-full border px-2 py-0.5 text-xs ${index < run.pipeline.length ? 'border-emerald-300 text-emerald-700 dark:border-emerald-700 dark:text-emerald-300' : 'border-slate-200 text-slate-400 dark:border-slate-800'}`}>{t(step)}</span>
                                ))}
                            </div>
                            <div className="mt-3 flex flex-wrap gap-2">
                                <Button asChild size="sm" variant="outline"><Link href={run.run_url}>{t('dashboard.action.viewRun')}</Link></Button>
                                {run.prompt_run_url && <Button asChild size="sm" variant="outline"><Link href={run.prompt_run_url}>{t('dashboard.action.viewPromptRun')}</Link></Button>}
                                {run.script_url && <Button asChild size="sm" variant="outline"><Link href={run.script_url}>{t('dashboard.action.viewScript')}</Link></Button>}
                                {run.sources_url && <Button asChild size="sm" variant="outline"><Link href={run.sources_url}>{t('dashboard.action.reviewSources')}</Link></Button>}
                                {isRealUrl(run.action_url) && run.action_method === 'post' && <Button asChild size="sm"><Link href={run.action_url} method="post" as="button">{t('dashboard.action.retry')}</Link></Button>}
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>
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

function ProviderBlock({ item }: { item: any }): JSX.Element {
    return (
        <div>
            <p className="font-medium">{item.provider}</p>
            <p className="text-xs text-slate-500">{item.model || '-'}</p>
        </div>
    );
}

function PipelineChips({ steps, t }: { steps: string[]; t: (key: string) => string }): JSX.Element {
    return (
        <div className="flex flex-wrap gap-1">
            {(steps || []).map((step) => <span key={step} className="rounded-full border border-slate-200 px-2 py-0.5 text-xs dark:border-slate-800">{t(statusKey(step))}</span>)}
        </div>
    );
}

function EmptyText({ text }: { text: string }): JSX.Element {
    return <p className="rounded-lg border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500 dark:border-slate-700">{text}</p>;
}

function providerLabel(item: any, t: (key: string) => string): string {
    return [item.provider, item.model].filter(Boolean).join(' · ') || t('bulletinTypes.provider.missing');
}

function statusVariant(status: string): 'default' | 'success' | 'danger' | 'outline' {
    if (['completed', 'script_created', 'prompt_generated', 'ready_for_production', 'sources_verified', 'scheduled'].includes(status)) {
        return 'success';
    }

    if (['failed', 'failed_today', 'schedule_overdue', 'missing_ai_provider', 'missing_schedule', 'missing_bulletin_type'].includes(status)) {
        return 'danger';
    }

    return 'outline';
}

function statusKey(status: string): string {
    const map: Record<string, string> = {
        created: 'status.created',
        prompt_run_created: 'status.promptRunCreated',
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
