import AdminPageHeader from '@/Components/AdminPageHeader';
import {
    ActionButtonGroup,
    DashboardMetricCard,
    DashboardPanel,
    OperationalTable,
    PipelineStepBar,
    ProviderBadge,
    StatusBadge,
} from '@/Components/EditorDashboard';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Bot, CalendarClock, Filter, RadioTower, Settings2 } from 'lucide-react';
import type { ReactNode } from 'react';

export default function Index({ bulletinTypes, filters = {}, locations = [], providerOptions = [] }: any): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const rows = bulletinTypes.data ?? [];

    const updateFilter = (key: string, value: string): void => {
        router.get(route('editor.bulletin-types.index'), { ...filters, [key]: value }, { preserveScroll: true, preserveState: true, replace: true });
    };

    const activeCount = rows.filter((item: any) => item.is_active).length;
    const missingProviderCount = rows.filter((item: any) => !item.preferred_ai_provider).length;
    const missingScheduleCount = rows.filter((item: any) => !item.primary_schedule).length;
    const readyCount = rows.filter((item: any) => item.health?.status === 'ready').length;

    return (
        <EditorLayout>
            <Head title={t('bulletinTypes.index.title')} />
            <AdminPageHeader
                helpKey="editor.bulletintypes.index"
                title={t('bulletinTypes.index.title')}
                description={t('bulletinTypes.index.description')}
            />

            <div className="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <DashboardMetricCard label={t('bulletinTypes.metrics.active')} value={activeCount} icon={<RadioTower className="h-5 w-5" />} tone="success" />
                <DashboardMetricCard label={t('bulletinTypes.metrics.ready')} value={readyCount} icon={<Bot className="h-5 w-5" />} tone="info" />
                <DashboardMetricCard label={t('bulletinTypes.metrics.missingProvider')} value={missingProviderCount} icon={<AlertTriangle className="h-5 w-5" />} tone={missingProviderCount ? 'danger' : 'neutral'} />
                <DashboardMetricCard label={t('bulletinTypes.metrics.missingSchedule')} value={missingScheduleCount} icon={<CalendarClock className="h-5 w-5" />} tone={missingScheduleCount ? 'warning' : 'neutral'} />
            </div>

            <DashboardPanel accent="cyan" className="mb-6 p-5">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p className="text-sm font-semibold text-cyan-700 dark:text-cyan-300">{t('bulletinTypes.flow.title')}</p>
                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">{t('bulletinTypes.flow.description')}</p>
                    </div>
                    <Button asChild>
                        <Link href={route('editor.bulletin-types.create')}>{t('bulletinTypes.action.create')}</Link>
                    </Button>
                </div>
            </DashboardPanel>

            <DashboardPanel className="mb-6 p-5">
                <div className="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                    <Filter className="h-4 w-4 text-slate-500" />
                    {t('bulletinTypes.filters.title')}
                </div>
                <div className="grid gap-3 md:grid-cols-4">
                    <Select value={filters.location_id ?? ''} onChange={(value) => updateFilter('location_id', value)}>
                        <option value="">{t('bulletinTypes.filter.allLocations')}</option>
                        {locations.map((location: any) => (
                            <option key={location.id} value={location.id}>{location.name}</option>
                        ))}
                    </Select>
                    <Select value={filters.provider ?? ''} onChange={(value) => updateFilter('provider', value)}>
                        <option value="">{t('bulletinTypes.filter.allProviders')}</option>
                        <option value="missing">{t('bulletinTypes.provider.missing')}</option>
                        {providerOptions.map((provider: any) => (
                            <option key={provider.id} value={provider.id}>{provider.name}</option>
                        ))}
                    </Select>
                    <Select value={filters.active ?? ''} onChange={(value) => updateFilter('active', value)}>
                        <option value="">{t('bulletinTypes.filter.allStates')}</option>
                        <option value="1">{t('dashboard.state.on')}</option>
                        <option value="0">{t('dashboard.state.off')}</option>
                    </Select>
                    <Select value={filters.health ?? ''} onChange={(value) => updateFilter('health', value)}>
                        <option value="">{t('bulletinTypes.filter.allHealth')}</option>
                        <option value="missing_provider">{t('bulletinTypes.health.missingProvider')}</option>
                        <option value="missing_schedule">{t('bulletinTypes.health.missingSchedule')}</option>
                    </Select>
                </div>
            </DashboardPanel>

            <DashboardPanel>
                <OperationalTable minWidth="0">
                    <thead>
                        <tr className="border-b border-slate-200 text-left text-xs font-semibold uppercase text-slate-500 dark:border-slate-800">
                            <th className="px-5 py-4">{t('bulletinTypes.table.name')}</th>
                            <th className="hidden px-5 py-4 lg:table-cell">{t('bulletinTypes.table.location')}</th>
                            <th className="px-5 py-4">{t('bulletinTypes.table.schedule')}</th>
                            <th className="px-5 py-4">{t('bulletinTypes.table.provider')}</th>
                            <th className="px-5 py-4">{t('bulletinTypes.table.latestRun')}</th>
                            <th className="hidden px-5 py-4 xl:table-cell">{t('bulletinTypes.table.latestScript')}</th>
                            <th className="px-5 py-4">{t('bulletinTypes.table.health')}</th>
                            <th className="px-5 py-4 text-right">{t('bulletinTypes.table.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length ? rows.map((item: any) => (
                            <tr key={item.id} className="border-b border-slate-100 align-top transition hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900/70">
                                <td className="px-5 py-4">
                                    <Link className="font-semibold text-slate-950 hover:text-cyan-700 dark:text-white dark:hover:text-cyan-300" href={item.urls?.show ?? route('editor.bulletin-types.show', item.id)}>
                                        {item.name}
                                    </Link>
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        <StatusBadge tone={item.is_active ? 'success' : 'neutral'}>{item.is_active ? t('dashboard.state.on') : t('dashboard.state.off')}</StatusBadge>
                                        {item.target_duration_seconds && <StatusBadge tone="info">{item.target_duration_seconds}s</StatusBadge>}
                                        <StatusBadge tone="neutral">{item.language?.name ?? t('bulletinTypes.empty.notAssigned')}</StatusBadge>
                                    </div>
                                    <p className="mt-2 text-xs text-slate-500 dark:text-slate-400 lg:hidden">{[item.location?.name, item.news_category?.name].filter(Boolean).join(' · ') || t('bulletinTypes.empty.notAssigned')}</p>
                                    <p className="mt-2 line-clamp-2 max-w-sm text-xs text-slate-500 dark:text-slate-400">{item.description || t('bulletinTypes.empty.noDescription')}</p>
                                </td>
                                <td className="hidden px-5 py-4 lg:table-cell">
                                    <p className="font-medium text-slate-900 dark:text-slate-100">{item.location?.name ?? t('bulletinTypes.empty.notAssigned')}</p>
                                    <p className="text-xs text-slate-500 dark:text-slate-400">{item.news_category?.name ?? t('bulletinTypes.empty.notAssigned')}</p>
                                </td>
                                <td className="px-5 py-4">
                                    {item.primary_schedule ? (
                                        <div className="space-y-2">
                                            <p className="font-medium text-slate-900 dark:text-slate-100">{scheduleLabel(item.primary_schedule, t)}</p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">{formatDateTime(item.primary_schedule.next_run_at)}</p>
                                            <StatusBadge tone={item.primary_schedule.auto_generate_ai_response ? 'success' : 'warning'}>
                                                {item.primary_schedule.auto_generate_ai_response ? t('bulletinTypes.automation.autoAi') : t('bulletinTypes.automation.aiManualApproval')}
                                            </StatusBadge>
                                        </div>
                                    ) : (
                                        <StatusBadge tone="danger">{t('bulletinTypes.schedule.missing')}</StatusBadge>
                                    )}
                                </td>
                                <td className="px-5 py-4">
                                    <ProviderBadge provider={item.preferred_ai_provider} missingLabel={t('bulletinTypes.provider.missing')} groundedLabel={t('bulletinTypes.provider.grounded')} />
                                </td>
                                <td className="px-5 py-4">
                                    {item.latest_execution ? (
                                        <div className="space-y-2">
                                            <StatusBadge tone={statusTone(item.latest_execution.status)}>{t(`status.${item.latest_execution.status}`)}</StatusBadge>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">{formatDateTime(item.latest_execution.scheduled_for)}</p>
                                        </div>
                                    ) : (
                                        <span className="text-sm text-slate-500">{t('bulletinTypes.empty.noRuns')}</span>
                                    )}
                                </td>
                                <td className="hidden px-5 py-4 xl:table-cell">
                                    {item.latest_script ? (
                                        <Link className="font-medium text-cyan-700 hover:underline dark:text-cyan-300" href={item.latest_script.url}>
                                            {item.latest_script.title || t('bulletinTypes.table.latestScript')}
                                        </Link>
                                    ) : (
                                        <span className="text-sm text-slate-500">{t('bulletinTypes.empty.noScripts')}</span>
                                    )}
                                </td>
                                <td className="px-5 py-4">
                                    <div className="space-y-3">
                                        <PipelineStepBar steps={[
                                            { key: 'provider', label: t('bulletinTypes.table.provider'), complete: Boolean(item.preferred_ai_provider) },
                                            { key: 'schedule', label: t('bulletinTypes.table.schedule'), complete: Boolean(item.primary_schedule) },
                                            { key: 'run', label: t('bulletinTypes.table.latestRun'), complete: Boolean(item.latest_execution) },
                                            { key: 'script', label: t('bulletinTypes.table.latestScript'), complete: Boolean(item.latest_script) },
                                        ]} />
                                        {item.health?.warnings?.length ? (
                                            <div className="flex max-w-xs flex-wrap gap-1.5">
                                                {item.health.warnings.map((warning: string) => <StatusBadge key={warning} tone="warning">{t(warning)}</StatusBadge>)}
                                            </div>
                                        ) : (
                                            <StatusBadge tone="success">{t('bulletinTypes.health.ready')}</StatusBadge>
                                        )}
                                    </div>
                                </td>
                                <td className="px-5 py-4">
                                    <div className="flex justify-end">
                                        <ActionButtonGroup actions={[
                                            { key: 'run', label: t('bulletinTypes.action.runNow'), href: item.urls?.run_now, method: 'post', variant: 'default' },
                                            { key: 'show', label: t('bulletinTypes.action.open'), href: item.urls?.show, variant: 'outline' },
                                            { key: 'executions', label: t('bulletinTypes.action.executions'), href: item.urls?.executions, variant: 'outline' },
                                            { key: 'scripts', label: t('bulletinTypes.action.scripts'), href: item.urls?.scripts, variant: 'outline' },
                                            { key: 'edit', label: t('bulletinTypes.action.edit'), href: item.urls?.edit, variant: 'ghost' },
                                        ]} />
                                    </div>
                                </td>
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan={8} className="px-5 py-14 text-center text-slate-500">
                                    <Settings2 className="mx-auto mb-3 h-8 w-8 text-slate-400" />
                                    {t('bulletinTypes.empty.none')}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </OperationalTable>
                <div className="border-t border-slate-100 px-5 py-4 dark:border-slate-800">
                    <Pagination links={bulletinTypes.links} />
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

function scheduleLabel(schedule: any, t: (key: string) => string): string {
    const time = String(schedule.run_time ?? schedule.scheduled_time ?? '').slice(0, 5);
    return [t(`frequency.${schedule.run_frequency ?? 'daily'}`), time || null, schedule.timezone].filter(Boolean).join(' · ');
}

function statusTone(status?: string | null): 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet' {
    if (!status) return 'neutral';
    if (['script_created', 'completed', 'success'].includes(status)) return 'success';
    if (['failed', 'error'].includes(status)) return 'danger';
    if (['prompt_generated', 'prompt_run_created', 'response_received', 'pending'].includes(status)) return 'warning';
    return 'info';
}
