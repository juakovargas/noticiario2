import AdminPageHeader from '@/Components/AdminPageHeader';
import { ActionButtonGroup, DashboardPanel, ProviderBadge, StatusBadge } from '@/Components/EditorDashboard';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, router } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';

export default function Index({ bulletins }: { bulletins: any[] }): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();

    return (
        <EditorLayout>
            <Head title={t('automation.index.title')} />
            <AdminPageHeader
                helpKey="editor.automation.index"
                title={t('automation.index.title')}
                description={t('automation.index.description')}
            />

            <DashboardPanel accent="cyan" className="mb-6 p-5">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold text-slate-950 dark:text-white">{t('automation.index.flow')}</p>
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{t('automation.index.flowDescription')}</p>
                    </div>
                    <Button type="button" onClick={() => router.post(route('editor.automation.sync-schedules'))}>
                        {t('automation.action.syncAll')}
                    </Button>
                </div>
            </DashboardPanel>

            <DashboardPanel>
                <div className="border-b border-slate-100 p-5 dark:border-slate-800">
                    <h2 className="flex items-center gap-2 text-base font-semibold text-slate-950 dark:text-white">
                        <CalendarClock className="h-4 w-4" />
                        {t('automation.table.title')}
                    </h2>
                </div>
                <div className="grid gap-3 p-5">
                    {bulletins.map((bulletin) => (
                        <article
                            key={bulletin.id}
                            className="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-950 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,0.8fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto]"
                        >
                            <div className="min-w-0">
                                <p className="truncate font-semibold text-slate-950 dark:text-white">{bulletin.name}</p>
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{bulletin.scope_label || '-'}</p>
                            </div>

                            <div>
                                <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{t('automation.table.scheduleState')}</p>
                                <div className="mt-1">
                                    {bulletin.has_schedule ? (
                                        <StatusBadge tone={bulletin.automation_enabled ? 'success' : 'neutral'}>
                                            {bulletin.automation_enabled ? t('dashboard.state.on') : t('dashboard.state.off')}
                                        </StatusBadge>
                                    ) : (
                                        <StatusBadge tone="danger">{t('bulletinTypes.schedule.missing')}</StatusBadge>
                                    )}
                                </div>
                            </div>

                            <div>
                                <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{t('automation.table.schedule')}</p>
                                <p className="mt-1 text-slate-700 dark:text-slate-300">
                                    {[bulletin.frequency_label, bulletin.run_time_label, bulletin.timezone].filter(Boolean).join(' · ') || '-'}
                                </p>
                                {bulletin.days_label && <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{bulletin.days_label}</p>}
                            </div>

                            <div>
                                <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{t('automation.table.nextRun')}</p>
                                <p className="mt-1 text-slate-700 dark:text-slate-300">{formatDateTime(bulletin.next_run_at)}</p>
                                <div className="mt-1">
                                    <StatusBadge tone={nextRunTone(bulletin.next_run_status)}>
                                        {t(bulletin.next_run_status_label_key)}
                                    </StatusBadge>
                                </div>
                                {bulletin.overdue_minutes ? <p className="mt-1 text-xs text-amber-600 dark:text-amber-300">{t('automation.overdueBy')} {bulletin.overdue_minutes}m</p> : null}
                            </div>

                            <div>
                                <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{t('automation.table.lastRun')}</p>
                                <p className="mt-1 text-slate-700 dark:text-slate-300">{formatDateTime(bulletin.last_run_at)}</p>
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{t(bulletin.last_run_status_label_key || 'automation.execution.none')}</p>
                            </div>

                            <div>
                                <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{t('dashboard.table.provider')}</p>
                                <div className="mt-1">
                                    <ProviderBadge
                                        provider={bulletin.provider}
                                        missingLabel={t('bulletinTypes.provider.missing')}
                                        groundedLabel={t('bulletinTypes.provider.grounded')}
                                    />
                                </div>
                                <div className="mt-2 flex flex-wrap gap-1.5">
                                    {bulletin.attention_reason_keys?.length ? (
                                        bulletin.attention_reason_keys.map((key: string) => (
                                            <StatusBadge key={key} tone={bulletin.attention_level === 'critical' ? 'danger' : 'warning'}>{t(key)}</StatusBadge>
                                        ))
                                    ) : (
                                        <StatusBadge tone="success">{t('automation.attention.ok')}</StatusBadge>
                                    )}
                                </div>
                            </div>

                            <ActionButtonGroup actions={automationActions(bulletin, t)} />
                        </article>
                    ))}
                </div>
            </DashboardPanel>
        </EditorLayout>
    );
}

function automationActions(bulletin: any, t: (key: string) => string): any[] {
    if (!bulletin.has_schedule) {
        return [
            { key: 'sync', label: t('automation.action.syncSchedule'), href: route('editor.automation.bulletin-types.sync-schedule', bulletin.id), method: 'post', variant: 'default' },
            { key: 'edit', label: t('bulletinTypes.action.edit'), href: route('editor.bulletin-types.edit', bulletin.id), variant: 'outline' },
        ];
    }

    return [
        { key: 'run', label: t('bulletinTypes.action.runNow'), href: route('editor.editorial-schedules.run-now', bulletin.schedule_id), method: 'post', variant: 'default' },
        { key: 'recalculate', label: t('automation.action.recalculate'), href: route('editor.automation.schedules.recalculate-next-run', bulletin.schedule_id), method: 'post', variant: 'outline' },
        { key: 'executions', label: t('bulletinTypes.action.executions'), href: route('editor.editorial-schedule-runs.index', { schedule_id: bulletin.schedule_id }), variant: 'outline' },
        { key: 'scripts', label: t('bulletinTypes.action.scripts'), href: route('editor.scripts.index', { bulletin_type_id: bulletin.id }), variant: 'outline' },
        { key: 'editSchedule', label: t('automation.action.editSchedule'), href: route('editor.editorial-schedules.edit', bulletin.schedule_id), variant: 'outline' },
        { key: 'toggle', label: bulletin.automation_enabled ? t('bulletinTypes.action.disableSchedule') : t('bulletinTypes.action.enableSchedule'), href: route('editor.automation.schedules.toggle', bulletin.schedule_id), method: 'post', variant: 'outline' },
    ];
}

function nextRunTone(status?: string | null): 'neutral' | 'info' | 'success' | 'warning' | 'danger' {
    if (status === 'scheduled') return 'success';
    if (status === 'overdue') return 'warning';
    if (status === 'missing') return 'danger';
    return 'neutral';
}
