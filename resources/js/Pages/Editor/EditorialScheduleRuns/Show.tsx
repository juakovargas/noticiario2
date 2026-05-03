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
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Bot, CalendarClock, Clipboard, FileText, RadioTower, Wand2 } from 'lucide-react';

export default function Show({ run }: any): JSX.Element {
    const { formatDateTime } = useDateFormatter();
    const { t } = useTranslations();
    const form = useForm({ response_text: run.ai_response_text ?? '' });
    const sources = sourceStatus(run.sources, t);

    const copyPrompt = async (): Promise<void> => {
        if (run.generated_prompt && navigator?.clipboard) {
            await navigator.clipboard.writeText(run.generated_prompt);
        }
    };

    const saveManualResponse = (): void => {
        if (run.urls?.receive_response) {
            form.post(run.urls.receive_response);
        }
    };

    return (
        <EditorLayout>
            <Head title={`${t('editorialRuns.show.title')} #${run.id}`} />
            <AdminPageHeader
                helpKey="editor.editorialscheduleruns.show"
                title={`${t('editorialRuns.show.title')} #${run.id}`}
                description={t('editorialRuns.show.description')}
            />

            <DashboardPanel accent={run.script_created ? 'emerald' : run.error_message ? 'rose' : 'cyan'} className="mb-6 p-6">
                <div className="flex flex-wrap items-start justify-between gap-5">
                    <div>
                        <p className="text-sm font-semibold text-cyan-700 dark:text-cyan-300">{t('editorialRuns.show.flowEyebrow')}</p>
                        <h2 className="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{run.schedule?.bulletin_type?.name ?? run.schedule?.name ?? `#${run.id}`}</h2>
                        <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">{formatDateTime(run.scheduled_for)}</p>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <StatusBadge tone={statusTone(run.status)}>{t(`status.${run.status}`)}</StatusBadge>
                            {run.ai_manual_approval_required && <StatusBadge tone="warning">{t('bulletinTypes.automation.aiManualApproval')}</StatusBadge>}
                            {run.provider?.supports_grounding && <StatusBadge tone="success">{t('bulletinTypes.provider.grounded')}</StatusBadge>}
                        </div>
                    </div>
                    <ActionButtonGroup actions={nextActions(run, t)} />
                </div>
                {run.error_message && <p className="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200">{run.error_message}</p>}
            </DashboardPanel>

            <div className="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <DashboardMetricCard label={t('editorialRuns.promptGenerated')} value={run.prompt_generated ? t('common.yes') : t('common.no')} icon={<Clipboard className="h-5 w-5" />} tone={run.prompt_generated ? 'success' : 'warning'} />
                <DashboardMetricCard label={t('editorialRuns.aiResponseReceived')} value={run.ai_response_received ? t('common.yes') : t('common.no')} icon={<Bot className="h-5 w-5" />} tone={run.ai_response_received ? 'success' : 'warning'} />
                <DashboardMetricCard label={t('editorialRuns.scriptCreated')} value={run.script_created ? t('common.yes') : t('common.no')} icon={<FileText className="h-5 w-5" />} tone={run.script_created ? 'success' : 'warning'} />
                <DashboardMetricCard label={t('sourceStatus.sources')} value={sources.label} icon={<FileText className="h-5 w-5" />} tone={sources.tone} helper={run.sources?.total ? `${run.sources.pending}/${run.sources.total}` : undefined} />
                <DashboardMetricCard label={t('editorialRuns.table.provider')} value={run.provider?.name ?? t('bulletinTypes.provider.missing')} icon={<Wand2 className="h-5 w-5" />} tone={run.provider ? 'info' : 'danger'} helper={run.provider?.model} />
            </div>

            <div className="grid gap-6 xl:grid-cols-3">
                <DashboardPanel className="p-5 xl:col-span-2">
                    <SectionTitle icon={<RadioTower className="h-4 w-4" />} title={t('editorialRuns.show.execution')} />
                    <div className="grid gap-4 text-sm md:grid-cols-2">
                        <Info label={t('editorialRuns.table.execution')} value={`#${run.id}`} />
                        <Info label={t('editorialRuns.table.scheduledFor')} value={formatDateTime(run.scheduled_for)} />
                        <Info label={t('editorialRuns.table.informativo')} value={run.schedule?.bulletin_type?.name ?? run.schedule?.name} fallback={t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('bulletinTypes.table.location')} value={run.schedule?.bulletin_type?.location} fallback={t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('bulletinTypes.table.category')} value={run.schedule?.bulletin_type?.category} fallback={t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('bulletinTypes.form.language')} value={run.schedule?.bulletin_type?.language} fallback={t('bulletinTypes.empty.notAssigned')} />
                    </div>
                    <div className="mt-5">
                        <PipelineStepBar steps={[
                            { key: 'prompt', label: t('editorialRuns.promptGenerated'), complete: run.prompt_generated },
                            { key: 'ai', label: t('editorialRuns.aiResponseReceived'), complete: run.ai_response_received },
                            { key: 'script', label: t('editorialRuns.scriptCreated'), complete: run.script_created },
                            { key: 'sources', label: sources.label, complete: run.sources?.status === 'verified', tone: sources.tone },
                        ]} />
                    </div>
                </DashboardPanel>

                <DashboardPanel className="p-5">
                    <SectionTitle icon={<Bot className="h-4 w-4" />} title={t('editorialRuns.table.provider')} />
                    <ProviderBadge provider={run.provider} missingLabel={t('bulletinTypes.provider.missing')} groundedLabel={t('bulletinTypes.provider.grounded')} />
                    {!run.provider && <p className="mt-3 text-sm text-rose-600 dark:text-rose-300">{t('editorialRuns.show.providerMissing')}</p>}
                    {run.provider && !run.provider.is_active && <p className="mt-3 text-sm text-rose-600 dark:text-rose-300">{t('editorialRuns.show.providerInactive')}</p>}
                </DashboardPanel>
            </div>

            <DashboardPanel className="mt-6 p-5">
                <SectionTitle icon={<Clipboard className="h-4 w-4" />} title={t('editorialRuns.show.prompt')} />
                <div className="mb-3 flex flex-wrap gap-2">
                    <ActionButtonGroup align="start" actions={[
                        { key: 'generate-prompt', label: t('editorialRuns.action.generatePrompt'), href: !run.prompt_generated ? run.urls?.generate_prompt : null, method: 'post', variant: 'default' },
                        { key: 'prompt-run', label: t('editorialRuns.action.openPromptRun'), href: run.urls?.prompt_run, variant: 'outline' },
                    ]} />
                    {run.generated_prompt && <Button type="button" variant="outline" onClick={copyPrompt}>{t('editorialRuns.action.copyPrompt')}</Button>}
                </div>
                <pre className="max-h-[460px] overflow-auto whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                    {run.generated_prompt || t('editorialRuns.show.promptMissing')}
                </pre>
            </DashboardPanel>

            <div className="mt-6 grid gap-6 xl:grid-cols-2">
                <DashboardPanel className="p-5">
                    <SectionTitle icon={<Wand2 className="h-4 w-4" />} title={t('editorialRuns.show.nextAction')} />
                    <p className="mb-4 text-sm text-slate-500 dark:text-slate-400">{nextActionDescription(run, t)}</p>
                    <ActionButtonGroup actions={nextActions(run, t)} />
                </DashboardPanel>

                <DashboardPanel className="p-5">
                    <SectionTitle icon={<FileText className="h-4 w-4" />} title={t('editorialRuns.show.script')} />
                    {run.urls?.script ? (
                        <Button asChild>
                            <Link href={run.urls.script}>{t('dashboard.action.viewScript')}</Link>
                        </Button>
                    ) : (
                        <p className="text-sm text-slate-500 dark:text-slate-400">{t('editorialRuns.show.scriptMissing')}</p>
                    )}
                </DashboardPanel>
            </div>

            <DashboardPanel className="mt-6 p-5">
                <SectionTitle icon={<CalendarClock className="h-4 w-4" />} title={t('editorialRuns.show.manualResponse')} />
                <p className="mb-3 text-sm text-slate-500 dark:text-slate-400">{t('editorialRuns.show.manualResponseHelp')}</p>
                <textarea
                    className="min-h-52 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"
                    value={form.data.response_text}
                    onChange={(e) => form.setData('response_text', e.target.value)}
                />
                <div className="mt-3 flex flex-wrap gap-2">
                    <Button type="button" variant="outline" disabled={form.processing || !run.urls?.receive_response} onClick={saveManualResponse}>{t('editorialRuns.action.saveResponse')}</Button>
                    {run.ai_response_text && <Button asChild variant="outline"><Link href={run.urls?.prompt_run ?? '#'}>{t('editorialRuns.action.openPromptRun')}</Link></Button>}
                </div>
                {run.ai_response_text && (
                    <pre className="mt-4 max-h-[360px] overflow-auto whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                        {run.ai_response_text}
                    </pre>
                )}
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

function nextActions(run: any, t: (key: string) => string): any[] {
    const sourceAction = run.sources?.total ? [{ key: 'sources', label: t('dashboard.action.reviewSources'), href: run.urls?.sources, variant: 'outline' }] : [];

    if (!run.prompt_generated) {
        return [
            { key: 'generate-prompt', label: t('editorialRuns.action.generatePrompt'), href: run.urls?.generate_prompt, method: 'post', variant: 'default' },
            ...sourceAction,
        ];
    }

    if (!run.ai_response_received) {
        return [
            { key: 'run-ai', label: t('editorialRuns.action.runAiCreateScript'), href: run.urls?.run_pipeline, method: 'post', variant: 'default' },
            ...sourceAction,
        ];
    }

    if (!run.script_created) {
        return [
            { key: 'create-script', label: t('editorialRuns.action.createScript'), href: run.urls?.create_script_from_prompt_run ?? run.urls?.create_script, method: 'post', variant: 'default' },
            ...sourceAction,
        ];
    }

    return [
        { key: 'script', label: t('dashboard.action.viewScript'), href: run.urls?.script, variant: 'default' },
        ...sourceAction,
    ];
}

function nextActionDescription(run: any, t: (key: string) => string): string {
    if (!run.prompt_generated) return t('editorialRuns.next.generatePrompt');
    if (!run.ai_response_received) return run.ai_manual_approval_required ? t('editorialRuns.next.manualAi') : t('editorialRuns.next.runAi');
    if (!run.script_created) return t('editorialRuns.next.createScript');
    return t('editorialRuns.next.reviewScript');
}

function sourceStatus(sources: any, t: (key: string) => string): { label: string; tone: 'neutral' | 'success' | 'warning' | 'danger' | 'violet' } {
    const status = sources?.status ?? 'none';
    const map: Record<string, { key: string; tone: 'neutral' | 'success' | 'warning' | 'danger' | 'violet' }> = {
        none: { key: 'sourceStatus.none', tone: 'neutral' },
        pending: { key: 'sourceStatus.pending', tone: 'warning' },
        verified: { key: 'sourceStatus.verified', tone: 'success' },
        rejected: { key: 'sourceStatus.rejected', tone: 'danger' },
        mixed: { key: 'sourceStatus.mixed', tone: 'violet' },
    };
    const item = map[status] ?? map.none;

    return { label: t(item.key), tone: item.tone };
}

function statusTone(status?: string | null): 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet' {
    if (!status) return 'neutral';
    if (['script_created', 'completed', 'success', 'ready'].includes(status)) return 'success';
    if (['failed', 'error', 'cancelled'].includes(status)) return 'danger';
    if (['prompt_generated', 'prompt_ready', 'prompt_run_created', 'waiting_ai_response', 'response_received', 'pending'].includes(status)) return 'warning';
    return 'info';
}
