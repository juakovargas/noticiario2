import AdminPageHeader from '@/Components/AdminPageHeader';
import {
    ActionButtonGroup,
    DashboardPanel,
    OperationalTable,
    PipelineStepBar,
    ProviderBadge,
    StatusBadge,
} from '@/Components/EditorDashboard';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, router } from '@inertiajs/react';
import { Filter, RadioTower } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

export default function Index({ runs, filters, statuses, schedules }: any): JSX.Element {
    const { formatDateTime } = useDateFormatter();
    const { t } = useTranslations();
    const [form, setForm] = useState(filters ?? {});
    const rows = runs.data ?? [];

    const submit = (e: FormEvent): void => {
        e.preventDefault();
        router.get(route('editor.editorial-schedule-runs.index'), form, { preserveState: true, preserveScroll: true });
    };

    const postArchiveAction = (action: 'archive' | 'restore', id: number): void => {
        router.post(route(`editor.editorial-schedule-runs.${action}`, id));
    };

    return (
        <EditorLayout>
            <Head title={t('editorialRuns.index.title')} />
            <AdminPageHeader
                helpKey="editor.editorialscheduleruns.index"
                title={t('editorialRuns.index.title')}
                description={t('editorialRuns.index.description')}
            />

            <DashboardPanel className="mb-6 p-5">
                <div className="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                    <Filter className="h-4 w-4 text-slate-500" />
                    {t('editorialRuns.filters.title')}
                </div>
                <form onSubmit={submit} className="grid gap-3 md:grid-cols-4">
                    <input
                        className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-950"
                        placeholder={t('editorialRuns.filters.search')}
                        value={form.search ?? ''}
                        onChange={(e) => setForm((previous: any) => ({ ...previous, search: e.target.value }))}
                    />
                    <Select value={form.status ?? ''} onChange={(value) => setForm((previous: any) => ({ ...previous, status: value }))}>
                        <option value="">{t('editorialRuns.filters.allStatuses')}</option>
                        {statuses.map((status: string) => <option key={status} value={status}>{t(`status.${status}`)}</option>)}
                    </Select>
                    <Select value={form.schedule_id ?? ''} onChange={(value) => setForm((previous: any) => ({ ...previous, schedule_id: value }))}>
                        <option value="">{t('editorialRuns.filters.allSchedules')}</option>
                        {schedules.map((schedule: any) => <option key={schedule.id} value={schedule.id}>{schedule.name}</option>)}
                    </Select>
                    <div className="flex flex-wrap items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <label className="flex items-center gap-2">
                            <input type="checkbox" checked={Boolean(form.show_archived)} onChange={(e) => setForm((previous: any) => ({ ...previous, show_archived: e.target.checked }))} />
                            {t('editorialRuns.filters.showArchived')}
                        </label>
                        <label className="flex items-center gap-2">
                            <input type="checkbox" checked={Boolean(form.only_archived)} onChange={(e) => setForm((previous: any) => ({ ...previous, only_archived: e.target.checked, show_archived: e.target.checked ? true : Boolean(previous.show_archived) }))} />
                            {t('editorialRuns.filters.onlyArchived')}
                        </label>
                    </div>
                    <div className="flex gap-2 md:col-span-4">
                        <Button type="submit">{t('common.applyFilters')}</Button>
                        <Button type="button" variant="outline" onClick={() => router.get(route('editor.editorial-schedule-runs.index'))}>{t('common.clearFilters')}</Button>
                    </div>
                </form>
            </DashboardPanel>

            <DashboardPanel>
                <OperationalTable minWidth="1240px">
                    <thead>
                        <tr className="border-b border-slate-200 text-left text-xs font-semibold uppercase text-slate-500 dark:border-slate-800">
                            <th className="px-5 py-4">{t('editorialRuns.table.execution')}</th>
                            <th className="px-5 py-4">{t('editorialRuns.table.informativo')}</th>
                            <th className="px-5 py-4">{t('editorialRuns.table.provider')}</th>
                            <th className="px-5 py-4">{t('editorialRuns.table.status')}</th>
                            <th className="px-5 py-4">{t('editorialRuns.table.pipeline')}</th>
                            <th className="px-5 py-4 text-right">{t('editorialRuns.table.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length ? rows.map((item: any) => (
                            <tr key={item.id} className="border-b border-slate-100 align-top transition hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900/70">
                                <td className="px-5 py-4">
                                    <p className="font-semibold text-slate-950 dark:text-white">#{item.id}</p>
                                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{formatDateTime(item.scheduled_for)}</p>
                                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{scheduleLabel(item.schedule, t)}</p>
                                </td>
                                <td className="px-5 py-4">
                                    <p className="font-semibold text-slate-950 dark:text-white">{item.schedule?.bulletin_type?.name ?? item.schedule?.name ?? t('bulletinTypes.empty.notAssigned')}</p>
                                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {[item.schedule?.bulletin_type?.location, item.schedule?.bulletin_type?.category, item.schedule?.bulletin_type?.language].filter(Boolean).join(' · ') || t('bulletinTypes.empty.notAssigned')}
                                    </p>
                                </td>
                                <td className="px-5 py-4">
                                    <ProviderBadge provider={item.provider} missingLabel={t('bulletinTypes.provider.missing')} groundedLabel={t('bulletinTypes.provider.grounded')} />
                                    {item.ai_manual_approval_required && <div className="mt-2"><StatusBadge tone="warning">{t('bulletinTypes.automation.aiManualApproval')}</StatusBadge></div>}
                                </td>
                                <td className="px-5 py-4">
                                    <StatusBadge tone={statusTone(item.status)}>{t(`status.${item.status}`)}</StatusBadge>
                                    {item.error_message && <p className="mt-2 max-w-xs text-xs text-rose-600 dark:text-rose-300">{item.error_message}</p>}
                                </td>
                                <td className="px-5 py-4">
                                    <PipelineStepBar steps={[
                                        { key: 'prompt', label: t('editorialRuns.promptGenerated'), complete: item.prompt_generated },
                                        { key: 'ai', label: t('editorialRuns.aiResponseReceived'), complete: item.ai_response_received },
                                        { key: 'script', label: t('editorialRuns.scriptCreated'), complete: item.script_created },
                                    ]} />
                                    <div className="mt-2 flex flex-wrap gap-1.5">
                                        <StatusBadge tone={item.prompt_generated ? 'success' : 'warning'}>{t('editorialRuns.promptGenerated')}</StatusBadge>
                                        <StatusBadge tone={item.ai_response_received ? 'success' : 'warning'}>{t('editorialRuns.aiResponseReceived')}</StatusBadge>
                                        <StatusBadge tone={item.script_created ? 'success' : 'warning'}>{t('editorialRuns.scriptCreated')}</StatusBadge>
                                    </div>
                                </td>
                                <td className="px-5 py-4">
                                    <div className="flex flex-col items-end gap-2">
                                        <ActionButtonGroup actions={nextActions(item, t)} />
                                        {item.status !== 'archived' ? (
                                            <Button size="sm" variant="ghost" onClick={() => postArchiveAction('archive', item.id)}>{t('common.archive')}</Button>
                                        ) : (
                                            <Button size="sm" variant="ghost" onClick={() => postArchiveAction('restore', item.id)}>{t('common.restore')}</Button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan={6} className="px-5 py-14 text-center text-slate-500">
                                    <RadioTower className="mx-auto mb-3 h-8 w-8 text-slate-400" />
                                    {t('editorialRuns.empty.none')}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </OperationalTable>
                <div className="border-t border-slate-100 px-5 py-4 dark:border-slate-800">
                    <Pagination links={runs.links} />
                </div>
            </DashboardPanel>
        </EditorLayout>
    );
}

function Select({ value, onChange, children }: { value: string; onChange: (value: string) => void; children: ReactNode }): JSX.Element {
    return (
        <select
            className="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
            value={value}
            onChange={(event) => onChange(event.target.value)}
        >
            {children}
        </select>
    );
}

function nextActions(item: any, t: (key: string) => string): any[] {
    if (!item.prompt_generated) {
        return [
            { key: 'generate-prompt', label: t('editorialRuns.action.generatePrompt'), href: item.urls?.generate_prompt, method: 'post', variant: 'default' },
            { key: 'open', label: t('dashboard.action.viewRun'), href: item.urls?.show, variant: 'outline' },
        ];
    }

    if (!item.ai_response_received) {
        return [
            { key: 'run-ai', label: t('editorialRuns.action.runAiCreateScript'), href: item.urls?.run_pipeline, method: 'post', variant: 'default' },
            { key: 'open', label: t('dashboard.action.viewRun'), href: item.urls?.show, variant: 'outline' },
            { key: 'prompt-run', label: t('editorialRuns.action.openPromptRun'), href: item.urls?.prompt_run, variant: 'outline' },
        ];
    }

    if (!item.script_created) {
        return [
            { key: 'create-script', label: t('editorialRuns.action.createScript'), href: item.urls?.create_script_from_prompt_run ?? item.urls?.create_script, method: 'post', variant: 'default' },
            { key: 'open', label: t('dashboard.action.viewRun'), href: item.urls?.show, variant: 'outline' },
        ];
    }

    return [
        { key: 'script', label: t('dashboard.action.viewScript'), href: item.urls?.script, variant: 'default' },
        { key: 'open', label: t('dashboard.action.viewRun'), href: item.urls?.show, variant: 'outline' },
    ];
}

function scheduleLabel(schedule: any, t: (key: string) => string): string {
    if (!schedule) return t('bulletinTypes.schedule.missing');
    const time = String(schedule.run_time ?? '').slice(0, 5);
    return [t(`frequency.${schedule.run_frequency ?? 'daily'}`), time || null, schedule.timezone].filter(Boolean).join(' · ');
}

function statusTone(status?: string | null): 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet' {
    if (!status) return 'neutral';
    if (['script_created', 'completed', 'success', 'ready'].includes(status)) return 'success';
    if (['failed', 'error', 'cancelled'].includes(status)) return 'danger';
    if (['prompt_generated', 'prompt_ready', 'prompt_run_created', 'waiting_ai_response', 'response_received', 'pending'].includes(status)) return 'warning';
    return 'info';
}
