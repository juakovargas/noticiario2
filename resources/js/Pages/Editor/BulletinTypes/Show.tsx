import AdminPageHeader from '@/Components/AdminPageHeader';
import {
    ActionButtonGroup,
    DashboardMetricCard,
    DashboardPanel,
    PipelineStepBar,
    ProviderBadge,
    StatusBadge,
} from '@/Components/EditorDashboard';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link } from '@inertiajs/react';
import { Bot, CalendarClock, CheckCircle2, ClipboardList, FileText, PlayCircle, RadioTower } from 'lucide-react';

export default function Show({ bulletinType, primarySchedule = null, recentExecutions = [], recentScripts = [], promptPreview = null, health = null }: any): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const provider = bulletinType.preferred_ai_provider;
    const runNowUrl = primarySchedule ? route('editor.editorial-schedules.run-now', primarySchedule.id) : null;

    return (
        <EditorLayout>
            <Head title={bulletinType.name} />
            <AdminPageHeader helpKey="editor.bulletintypes.show" title={bulletinType.name} description={t('bulletinTypes.show.description')} />

            <DashboardPanel accent={health?.status === 'ready' ? 'emerald' : 'amber'} className="mb-6 p-6">
                <div className="flex flex-wrap items-start justify-between gap-5">
                    <div className="max-w-3xl">
                        <p className="text-sm font-semibold text-cyan-700 dark:text-cyan-300">{t('bulletinTypes.show.flowEyebrow')}</p>
                        <h2 className="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{bulletinType.name}</h2>
                        <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">{bulletinType.description || t('bulletinTypes.empty.noDescription')}</p>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <StatusBadge tone={bulletinType.is_active ? 'success' : 'neutral'}>{bulletinType.is_active ? t('dashboard.state.on') : t('dashboard.state.off')}</StatusBadge>
                            <StatusBadge tone="neutral">{bulletinType.location?.name ?? t('bulletinTypes.empty.notAssigned')}</StatusBadge>
                            <StatusBadge tone="neutral">{bulletinType.news_category?.name ?? t('bulletinTypes.empty.notAssigned')}</StatusBadge>
                            <StatusBadge tone="info">{bulletinType.language?.name ?? t('bulletinTypes.empty.notAssigned')}</StatusBadge>
                        </div>
                    </div>
                    <ActionButtonGroup actions={[
                        { key: 'run', label: t('bulletinTypes.action.runNow'), href: runNowUrl, method: 'post', variant: 'default' },
                        { key: 'edit', label: t('bulletinTypes.action.edit'), href: route('editor.bulletin-types.edit', bulletinType.id), variant: 'outline' },
                        { key: 'executions', label: t('bulletinTypes.action.executions'), href: route('editor.editorial-schedule-runs.index', { schedule_id: primarySchedule?.id }), variant: 'outline' },
                    ]} />
                </div>
            </DashboardPanel>

            <div className="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <DashboardMetricCard label={t('bulletinTypes.show.nextRun')} value={primarySchedule?.next_run_at ? formatDateTime(primarySchedule.next_run_at) : t('bulletinTypes.schedule.missing')} icon={<CalendarClock className="h-5 w-5" />} tone={primarySchedule ? 'info' : 'warning'} />
                <DashboardMetricCard label={t('bulletinTypes.show.provider')} value={provider?.name ?? t('bulletinTypes.provider.missing')} icon={<Bot className="h-5 w-5" />} tone={provider ? 'success' : 'danger'} helper={provider?.default_model} />
                <DashboardMetricCard label={t('bulletinTypes.show.recentExecutions')} value={recentExecutions.length} icon={<RadioTower className="h-5 w-5" />} tone="violet" />
                <DashboardMetricCard label={t('bulletinTypes.show.recentScripts')} value={recentScripts.length} icon={<FileText className="h-5 w-5" />} tone="success" />
            </div>

            <div className="grid gap-6 xl:grid-cols-3">
                <DashboardPanel className="p-5 xl:col-span-2">
                    <SectionTitle icon={<ClipboardList className="h-4 w-4" />} title={t('bulletinTypes.show.summary')} />
                    <div className="grid gap-4 text-sm md:grid-cols-2">
                        <Info label={t('bulletinTypes.table.location')} value={bulletinType.location?.name} fallback={t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('bulletinTypes.table.category')} value={bulletinType.news_category?.name} fallback={t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('bulletinTypes.form.language')} value={bulletinType.language?.name} fallback={t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('bulletinTypes.form.targetDuration')} value={bulletinType.target_duration_seconds ? `${bulletinType.target_duration_seconds}s` : null} fallback={t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('bulletinTypes.form.outputMode')} value={t(`outputMode.${bulletinType.output_mode || 'plain_final_script'}`)} fallback={t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('bulletinTypes.form.coverageMode')} value={t(`coverage.${bulletinType.coverage_mode || 'none'}`)} fallback={t('bulletinTypes.empty.notAssigned')} />
                    </div>
                    <div className="mt-5">
                        <p className="mb-2 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{t('bulletinTypes.table.health')}</p>
                        <PipelineStepBar steps={[
                            { key: 'provider', label: t('bulletinTypes.table.provider'), complete: Boolean(provider) },
                            { key: 'schedule', label: t('bulletinTypes.table.schedule'), complete: Boolean(primarySchedule) },
                            { key: 'execution', label: t('bulletinTypes.show.recentExecutions'), complete: recentExecutions.length > 0 },
                            { key: 'script', label: t('bulletinTypes.show.recentScripts'), complete: recentScripts.length > 0 },
                        ]} />
                        <div className="mt-3 flex flex-wrap gap-2">
                            {health?.warnings?.length ? health.warnings.map((warning: string) => (
                                <StatusBadge key={warning} tone="warning">{t(warning)}</StatusBadge>
                            )) : <StatusBadge tone="success">{t('bulletinTypes.health.ready')}</StatusBadge>}
                        </div>
                    </div>
                </DashboardPanel>

                <DashboardPanel className="p-5">
                    <SectionTitle icon={<Bot className="h-4 w-4" />} title={t('bulletinTypes.show.provider')} />
                    <ProviderBadge provider={provider} missingLabel={t('bulletinTypes.provider.missing')} groundedLabel={t('bulletinTypes.provider.grounded')} />
                    <div className="mt-4 space-y-2 text-sm">
                        <StatusBadge tone={provider?.is_active ? 'success' : 'danger'}>{provider?.is_active ? t('common.active') : t('common.inactive')}</StatusBadge>
                        <p className="text-slate-500 dark:text-slate-400">{t('bulletinTypes.show.providerExplanation')}</p>
                    </div>
                </DashboardPanel>
            </div>

            <DashboardPanel className="mt-6 p-5">
                <SectionTitle icon={<CalendarClock className="h-4 w-4" />} title={t('bulletinTypes.show.schedule')} />
                {primarySchedule ? (
                    <div className="grid gap-4 text-sm md:grid-cols-4">
                        <Info label={t('bulletinTypes.table.status')} value={primarySchedule.is_active ? t('dashboard.state.on') : t('dashboard.state.off')} />
                        <Info label={t('bulletinTypes.form.frequency')} value={t(`frequency.${primarySchedule.run_frequency ?? 'daily'}`)} />
                        <Info label={t('bulletinTypes.form.runTime')} value={(primarySchedule.run_time ?? primarySchedule.scheduled_time ?? '').slice(0, 5)} fallback={t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('bulletinTypes.form.timezone')} value={primarySchedule.timezone} fallback={t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('bulletinTypes.show.nextRun')} value={formatDateTime(primarySchedule.next_run_at)} />
                        <Info label={t('bulletinTypes.show.lastRun')} value={formatDateTime(primarySchedule.last_run_at)} />
                        <Info label={t('bulletinTypes.automation.aiManualApproval')} value={primarySchedule.auto_generate_ai_response ? t('common.no') : t('common.yes')} />
                        <Info label={t('bulletinTypes.automation.pipeline')} value={primarySchedule.auto_run_pipeline ? t('common.yes') : t('common.no')} />
                        <div className="md:col-span-4">
                            <ActionButtonGroup actions={[
                                { key: 'run', label: t('bulletinTypes.action.runNow'), href: runNowUrl, method: 'post', variant: 'default' },
                                { key: 'toggle', label: primarySchedule.is_active ? t('bulletinTypes.action.disableSchedule') : t('bulletinTypes.action.enableSchedule'), href: route('editor.automation.schedules.toggle', primarySchedule.id), method: 'post', variant: 'outline' },
                            ]} />
                        </div>
                    </div>
                ) : (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                        {t('bulletinTypes.show.scheduleMissingDescription')}
                    </div>
                )}
            </DashboardPanel>

            <div className="mt-6 grid gap-6 xl:grid-cols-2">
                <DashboardPanel className="p-5">
                    <SectionTitle icon={<RadioTower className="h-4 w-4" />} title={t('bulletinTypes.show.executions')} />
                    <div className="space-y-3">
                        {recentExecutions.length ? recentExecutions.map((execution: any) => (
                            <div key={execution.id} className="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p className="font-semibold text-slate-950 dark:text-white">{formatDateTime(execution.scheduled_for)}</p>
                                        <StatusBadge tone={statusTone(execution.status)}>{t(`status.${execution.status}`)}</StatusBadge>
                                    </div>
                                    <ActionButtonGroup actions={[
                                        { key: 'view', label: t('dashboard.action.viewRun'), href: execution.url, variant: 'outline' },
                                        { key: 'script', label: t('dashboard.action.viewScript'), href: execution.script_url, variant: 'outline' },
                                    ]} />
                                </div>
                                <div className="mt-3">
                                    <PipelineStepBar steps={[
                                        { key: 'prompt', label: t('editorialRuns.promptGenerated'), complete: execution.prompt_generated },
                                        { key: 'ai', label: t('editorialRuns.aiResponseReceived'), complete: execution.ai_response_received },
                                        { key: 'script', label: t('editorialRuns.scriptCreated'), complete: execution.script_created },
                                    ]} />
                                </div>
                                {execution.error_message && <p className="mt-2 text-sm text-rose-600 dark:text-rose-300">{execution.error_message}</p>}
                            </div>
                        )) : <EmptyLine label={t('bulletinTypes.empty.noRuns')} />}
                    </div>
                </DashboardPanel>

                <DashboardPanel className="p-5">
                    <SectionTitle icon={<FileText className="h-4 w-4" />} title={t('bulletinTypes.show.scripts')} />
                    <div className="space-y-3">
                        {recentScripts.length ? recentScripts.map((script: any) => (
                            <div key={script.id} className="rounded-lg border border-slate-200 p-4 dark:border-slate-800">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <Link className="font-semibold text-cyan-700 hover:underline dark:text-cyan-300" href={script.url}>{script.title}</Link>
                                        <p className="mt-1 text-xs text-slate-500">{formatDateTime(script.created_at)}</p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <StatusBadge tone="info">{t(`status.${script.review_status ?? script.status}`)}</StatusBadge>
                                        <StatusBadge tone="neutral">{t(`status.${script.production_status ?? 'draft'}`)}</StatusBadge>
                                    </div>
                                </div>
                                <div className="mt-3">
                                    <ActionButtonGroup actions={[
                                        { key: 'open', label: t('dashboard.action.viewScript'), href: script.url, variant: 'outline' },
                                        { key: 'review', label: t('dashboard.action.reviewSources'), href: script.review_url, variant: 'outline' },
                                    ]} />
                                </div>
                            </div>
                        )) : <EmptyLine label={t('bulletinTypes.empty.noScripts')} />}
                    </div>
                </DashboardPanel>
            </div>

            <DashboardPanel className="mt-6 p-5">
                <SectionTitle icon={<CheckCircle2 className="h-4 w-4" />} title={t('bulletinTypes.show.promptPreview')} />
                <div className="mb-4 grid gap-3 text-sm md:grid-cols-4">
                    <Info label={t('bulletinTypes.show.nextRun')} value={formatDateTime(promptPreview?.scheduled_for)} />
                    <Info label={t('bulletinTypes.table.location')} value={promptPreview?.location} fallback={t('bulletinTypes.empty.notAssigned')} />
                    <Info label={t('bulletinTypes.table.category')} value={promptPreview?.category} fallback={t('bulletinTypes.empty.notAssigned')} />
                    <Info label={t('bulletinTypes.form.targetDuration')} value={promptPreview?.duration ? `${promptPreview.duration}s` : null} fallback={t('bulletinTypes.empty.notAssigned')} />
                </div>
                <pre className="max-h-[420px] overflow-auto whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                    {promptPreview?.text || t('bulletinTypes.show.promptPreviewUnavailable')}
                </pre>
            </DashboardPanel>

            <DashboardPanel className="mt-6 p-5">
                <SectionTitle icon={<PlayCircle className="h-4 w-4" />} title={t('bulletinTypes.show.futureProduction')} />
                <div className="grid gap-3 md:grid-cols-3">
                    <Future label={t('production.audio')} status={t('production.futurePhase')} />
                    <Future label={t('production.video')} status={t('production.futurePhase')} />
                    <Future label={t('production.publishing')} status={t('production.futurePhase')} />
                </div>
            </DashboardPanel>
        </EditorLayout>
    );
}

function SectionTitle({ icon, title }: { icon: JSX.Element; title: string }): JSX.Element {
    return <h3 className="mb-4 flex items-center gap-2 text-base font-semibold text-slate-950 dark:text-white">{icon}{title}</h3>;
}

function Info({ label, value, fallback = '-' }: { label: string; value?: any; fallback?: string }): JSX.Element {
    return (
        <div>
            <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{label}</p>
            <p className="mt-1 font-medium text-slate-950 dark:text-white">{value || fallback}</p>
        </div>
    );
}

function EmptyLine({ label }: { label: string }): JSX.Element {
    return <p className="rounded-lg border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">{label}</p>;
}

function Future({ label, status }: { label: string; status: string }): JSX.Element {
    return (
        <div className="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
            <p className="font-semibold text-slate-700 dark:text-slate-200">{label}</p>
            <p className="mt-1">&nbsp;</p>
            <StatusBadge tone="neutral">{status}</StatusBadge>
        </div>
    );
}

function statusTone(status?: string | null): 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet' {
    if (!status) return 'neutral';
    if (['script_created', 'completed', 'success', 'ready'].includes(status)) return 'success';
    if (['failed', 'error'].includes(status)) return 'danger';
    if (['prompt_generated', 'prompt_run_created', 'response_received', 'pending'].includes(status)) return 'warning';
    return 'info';
}
