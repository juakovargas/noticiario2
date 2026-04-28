import AdminPageHeader from '@/Components/AdminPageHeader';
import StatusBadge from '@/Components/StatusBadge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { useDateFormatter } from '@/lib/useDateFormatter';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { useTranslations } from '@/i18n/useTranslations';

type Props = {
    stats: Record<string, number | null>;
    recentFailedRuns: Array<{ id: number; status: string; error_message: string | null; scheduled_for: string | null; schedule?: { name: string } }>;
    aiOverview: null | { total: number; active: number; defaultProvider: string | null };
    canOpenEditorRun: boolean;
};

export default function Dashboard({ stats, recentFailedRuns, aiOverview, canOpenEditorRun }: Props): JSX.Element {
    const { formatDateTime } = useDateFormatter();
    const { t } = useTranslations();

    return (
        <AdminLayout>
            <Head title={t('Admin Dashboard')} />
            <AdminPageHeader title={t('Administration Dashboard')} description={t('Technical/system overview and editorial health indicators.')} />

            <div className="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                {[
                    ['Total users', stats.users], ['Active users', stats.activeUsers], ['Roles', stats.roles], ['Permissions', stats.permissions], ['Languages', stats.languages],
                    ['Active editorial schedules', stats.activeSchedules], ["Today's editorial runs", stats.todayRuns], ['Failed editorial runs', stats.failedRuns], ['AI providers', stats.aiProviders], ['Active AI providers', stats.activeAiProviders], ['AI requests today', stats.aiRequestsToday], ['Failed AI requests', stats.failedAiRequests], ['Estimated cost', stats.aiEstimatedCostToday],
                ].map(([label, value]) => (
                    <Card key={String(label)}><CardHeader className="pb-2"><CardDescription>{t(String(label))}</CardDescription></CardHeader><CardContent><CardTitle>{value ?? '-'}</CardTitle></CardContent></Card>
                ))}
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle>{t('System overview')}</CardTitle></CardHeader>
                    <CardContent className="space-y-1 text-sm text-slate-700">
                        <p>{t('Users')}: {stats.users}</p><p>{t('Roles')}: {stats.roles}</p><p>{t('Permissions')}: {stats.permissions}</p><p>{t('Languages')}: {stats.languages}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>{t('Editorial system overview')}</CardTitle></CardHeader>
                    <CardContent className="space-y-1 text-sm text-slate-700">
                        <p>{t('Active schedules')}: {stats.activeSchedules}</p><p>{t('Runs today')}: {stats.todayRuns}</p><p>{t('Failed runs')}: {stats.failedRuns}</p><p>{t('Draft scripts')}: {stats.draftScripts}</p><p>{t('Planned editions')}: {stats.plannedEditions}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>{t('Recent failed runs')}</CardTitle></CardHeader>
                    <CardContent className="space-y-2">
                        {recentFailedRuns.length ? recentFailedRuns.map((run) => (
                            <div key={run.id} className="rounded border p-3 text-sm">
                                <div className="flex items-center justify-between"><p className="font-medium">{run.schedule?.name ?? `${t('Run')} #${run.id}`}</p><StatusBadge status={run.status} /></div>
                                <p className="text-slate-600">{formatDateTime(run.scheduled_for)}</p>
                                <p className="text-red-700">{run.error_message || '-'}</p>
                                {canOpenEditorRun && <Link className="text-cyan-700" href={route('editor.editorial-schedule-runs.show', run.id)}>{t('Open run')}</Link>}
                            </div>
                        )) : <p className="text-sm">{t('No failed runs.')}</p>}
                    </CardContent>
                </Card>


                <Card>
                    <CardHeader><CardTitle>{t('World Map')}</CardTitle><CardDescription>{t('View configured bulletin locations')}</CardDescription></CardHeader>
                    <CardContent><Link className="text-cyan-700" href={route('admin.world-map.index')}>{t('Open')}</Link></CardContent>
                </Card>

                {aiOverview && (
                    <Card>
                        <CardHeader><CardTitle>{t('AI configuration overview')}</CardTitle></CardHeader>
                        <CardContent className="space-y-1 text-sm text-slate-700">
                            <p>{t('Default provider')}: {aiOverview.defaultProvider ?? t('Not configured')}</p>
                            <p>{t('Active providers')}: {aiOverview.active}</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}
