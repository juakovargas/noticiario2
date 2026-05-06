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
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Bot, CalendarClock, Filter, RadioTower, Settings2 } from 'lucide-react';
import type { ReactNode } from 'react';

export default function Index({ bulletinTypes, activeBulletins = [], inactiveBulletins = [], filters = {}, locations = [], providerOptions = [], languages = [], categories = [] }: any): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const rows = [...(activeBulletins.length || inactiveBulletins.length ? [...activeBulletins, ...inactiveBulletins] : (bulletinTypes?.data ?? []))];

    const updateFilter = (key: string, value: string): void => {
        router.get(route('editor.bulletin-types.index'), { ...filters, [key]: value }, { preserveScroll: true, preserveState: true, replace: true });
    };

    const activeCount = activeBulletins.length;
    const missingProviderCount = rows.filter((item: any) => !item.preferred_ai_provider).length;
    const missingScheduleCount = rows.filter((item: any) => !item.primary_schedule).length;
    const readyCount = rows.filter((item: any) => item.is_on).length;

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
                        <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">{t('bulletinTypes.coreFlow')}</p>
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
                    <Select value={filters.active ?? ''} onChange={(value) => updateFilter('active', value)}>
                        <option value="">{t('bulletinTypes.filter.allStates')}</option>
                        <option value="active">{t('common.active')}</option>
                        <option value="inactive">{t('common.inactive')}</option>
                    </Select>
                    <Select value={filters.provider ?? ''} onChange={(value) => updateFilter('provider', value)}>
                        <option value="">{t('bulletinTypes.filter.allProviders')}</option>
                        {providerOptions.map((provider: any) => <option key={provider.id} value={provider.id}>{provider.name}</option>)}
                    </Select>
                    <Select value={filters.language_id ?? ''} onChange={(value) => updateFilter('language_id', value)}>
                        <option value="">{t('bulletinTypes.filter.allLanguages')}</option>
                        {languages.map((language: any) => <option key={language.id} value={language.id}>{language.name}</option>)}
                    </Select>
                    <Select value={filters.category_id ?? ''} onChange={(value) => updateFilter('category_id', value)}>
                        <option value="">{t('bulletinTypes.filter.allCategories')}</option>
                        {categories.map((category: any) => <option key={category.id} value={category.id}>{category.name}</option>)}
                    </Select>
                    <Select value={filters.health ?? ''} onChange={(value) => updateFilter('health', value)}>
                        <option value="">{t('bulletinTypes.filter.allHealth')}</option>
                        <option value="ok">{t('bulletinTypes.health.ok')}</option>
                        <option value="missing_provider">{t('bulletinTypes.health.missingProvider')}</option>
                        <option value="missing_schedule">{t('bulletinTypes.health.missingSchedule')}</option>
                        <option value="next_run_today">{t('bulletinTypes.health.nextRunToday')}</option>
                        <option value="last_execution_failed">{t('bulletinTypes.health.lastExecutionFailed')}</option>
                    </Select>
                </div>
            </DashboardPanel>

            {rows.length ? (
                <div className="space-y-6">
                    <BulletinTableSection
                        formatDateTime={formatDateTime}
                        rows={activeBulletins}
                        t={t}
                        title={t('bulletinTypes.section.active')}
                    />
                    <BulletinTableSection
                        formatDateTime={formatDateTime}
                        rows={inactiveBulletins}
                        t={t}
                        title={t('bulletinTypes.section.inactive')}
                    />
                </div>
            ) : (
                <DashboardPanel className="px-5 py-14 text-center text-slate-500">
                    <Settings2 className="mx-auto mb-3 h-8 w-8 text-slate-400" />
                    {t('bulletinTypes.empty.none')}
                </DashboardPanel>
            )}
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

function BulletinTableSection({
    rows,
    title,
    t,
    formatDateTime,
}: {
    rows: any[];
    title: string;
    t: (key: string) => string;
    formatDateTime: (value?: string | null) => string;
}): JSX.Element {
    return (
        <DashboardPanel>
            <div className="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 className="text-base font-semibold text-slate-950 dark:text-white">{title}</h2>
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{rows.length} {t('bulletinTypes.section.items')}</p>
            </div>
            <OperationalTable minWidth="0">
                <thead>
                    <tr className="border-b border-slate-200 text-left text-xs font-semibold uppercase text-slate-500 dark:border-slate-800">
                        <th className="px-4 py-3">{t('bulletinTypes.table.onOff')}</th>
                        <th className="px-4 py-3">{t('bulletinTypes.table.name')}</th>
                        <th className="hidden px-4 py-3 lg:table-cell">{t('bulletinTypes.table.location')}</th>
                        <th className="hidden px-4 py-3 xl:table-cell">{t('bulletinTypes.table.category')}</th>
                        <th className="hidden px-4 py-3 xl:table-cell">{t('bulletinTypes.table.language')}</th>
                        <th className="px-4 py-3">{t('bulletinTypes.table.schedule')}</th>
                        <th className="px-4 py-3">{t('bulletinTypes.table.provider')}</th>
                        <th className="px-4 py-3">{t('bulletinTypes.table.status')}</th>
                        <th className="px-4 py-3 text-right">{t('bulletinTypes.table.actions')}</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.length ? rows.map((item: any) => (
                        <BulletinRow key={item.id} item={item} t={t} formatDateTime={formatDateTime} />
                    )) : (
                        <tr>
                            <td colSpan={9} className="px-5 py-10 text-center text-sm text-slate-500">
                                {t('bulletinTypes.empty.none')}
                            </td>
                        </tr>
                    )}
                </tbody>
            </OperationalTable>
        </DashboardPanel>
    );
}

function BulletinRow({ item, t, formatDateTime }: { item: any; t: (key: string) => string; formatDateTime: (value?: string | null) => string }): JSX.Element {
    const missing = item.health?.warnings ?? item.missing_configuration ?? [];
    const isOn = Boolean(item.is_on);
    const toggleLabel = isOn ? t('bulletinTypes.action.turnOff') : t('bulletinTypes.action.turnOn');
    const actions = [
        item.is_runnable ? { key: 'run', label: t('bulletinTypes.action.runNow'), href: item.urls?.run_now, method: 'post' as const, variant: 'default' as const } : null,
        { key: 'show', label: t('dashboard.action.view'), href: item.urls?.show, variant: 'outline' as const },
        { key: 'edit', label: t('bulletinTypes.action.edit'), href: item.urls?.edit, variant: 'outline' as const },
        { key: 'toggle', label: toggleLabel, href: item.urls?.toggle_active, method: 'post' as const, variant: isOn ? 'outline' as const : 'default' as const },
        { key: 'executions', label: t('bulletinTypes.action.executions'), href: item.urls?.executions, variant: 'outline' as const },
        { key: 'scripts', label: t('bulletinTypes.action.scripts'), href: item.urls?.scripts, variant: 'outline' as const },
    ].filter(Boolean) as any[];

    return (
        <tr className="border-b border-slate-100 align-top transition hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-900/70">
            <td className="px-4 py-3">
                <Button asChild size="sm" variant={isOn ? 'outline' : 'default'} className="h-8 px-2.5 text-xs">
                    <Link
                        href={item.urls?.toggle_active ?? '#'}
                        method="post"
                        as="button"
                        preserveScroll
                        aria-label={`${toggleLabel}: ${item.name}`}
                    >
                        {isOn ? t('dashboard.state.on') : t('dashboard.state.off')}
                    </Link>
                </Button>
            </td>
            <td className="px-4 py-3">
                <Link className="font-semibold text-slate-950 hover:text-cyan-700 dark:text-white dark:hover:text-cyan-300" href={item.urls?.show ?? route('editor.bulletin-types.show', item.id)}>
                    {item.name}
                </Link>
                <p className="mt-1 line-clamp-1 max-w-sm text-xs text-slate-500 dark:text-slate-400">{item.description || t('bulletinTypes.empty.noDescription')}</p>
                <div className="mt-2 flex flex-wrap gap-1.5">
                    {item.target_duration_seconds ? <StatusBadge tone="info">{item.target_duration_seconds}s</StatusBadge> : null}
                    {item.primary_schedule?.auto_generate_ai_response === false ? <StatusBadge tone="warning">{t('dashboard.ai.manualApproval')}</StatusBadge> : null}
                </div>
            </td>
            <td className="hidden px-4 py-3 lg:table-cell">
                <p className="font-medium text-slate-900 dark:text-slate-100">{item.location?.name ?? t('bulletinTypes.empty.notAssigned')}</p>
            </td>
            <td className="hidden px-4 py-3 xl:table-cell">
                <p className="font-medium text-slate-900 dark:text-slate-100">{item.news_category?.name ?? t('bulletinTypes.empty.notAssigned')}</p>
            </td>
            <td className="hidden px-4 py-3 xl:table-cell">
                <p className="font-medium text-slate-900 dark:text-slate-100">{item.language?.name ?? t('bulletinTypes.empty.notAssigned')}</p>
            </td>
            <td className="px-4 py-3">
                {item.primary_schedule ? (
                    <div>
                        <p className="font-medium text-slate-900 dark:text-slate-100">{scheduleLabel(item.primary_schedule, t)}</p>
                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{formatDateTime(item.primary_schedule.next_run_at)}</p>
                    </div>
                ) : (
                    <StatusBadge tone="danger">{t('bulletinTypes.health.missingSchedule')}</StatusBadge>
                )}
            </td>
            <td className="px-4 py-3">
                <ProviderBadge provider={item.preferred_ai_provider} missingLabel={t('bulletinTypes.health.missingProvider')} groundedLabel={t('bulletinTypes.provider.grounded')} />
            </td>
            <td className="px-4 py-3">
                <div className="space-y-2">
                    <PipelineStepBar steps={[
                        { key: 'provider', label: t('bulletinTypes.table.provider'), complete: Boolean(item.preferred_ai_provider) },
                        { key: 'schedule', label: t('bulletinTypes.table.schedule'), complete: Boolean(item.primary_schedule) },
                        { key: 'run', label: t('bulletinTypes.table.latestRun'), complete: Boolean(item.latest_execution) },
                        { key: 'script', label: t('bulletinTypes.table.latestScript'), complete: Boolean(item.latest_script) },
                    ]} />
                    <div className="flex max-w-xs flex-wrap gap-1.5">
                        {missing.length ? (
                            missing.slice(0, 6).map((warning: string) => <StatusBadge key={warning} tone="warning">{t(warning)}</StatusBadge>)
                        ) : (
                            <StatusBadge tone="success">{t('bulletinTypes.health.ready')}</StatusBadge>
                        )}
                    </div>
                </div>
            </td>
            <td className="px-4 py-3">
                <div className="flex justify-end">
                    <ActionButtonGroup actions={actions} />
                </div>
            </td>
        </tr>
    );
}

function scheduleLabel(schedule: any, t: (key: string) => string): string {
    const time = String(schedule.run_time ?? schedule.scheduled_time ?? '').slice(0, 5);
    return [t(`frequency.${schedule.run_frequency ?? 'daily'}`), time || null, schedule.timezone].filter(Boolean).join(' · ');
}
