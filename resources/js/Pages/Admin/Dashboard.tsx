import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { useDateFormatter } from '@/lib/useDateFormatter';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { useTranslations } from '@/i18n/useTranslations';

type Props = {
    stats: Record<string, number | null>;
    recentFailedRuns: Array<{ id: number; status: string; error_message: string | null; scheduled_for: string | null; schedule?: { name: string } }>;
    aiOverview: null | { total: number; active: number; defaultProvider: string | null; supportsWebSearch: number; supportsJsonMode: number };
    canOpenEditorRun: boolean;
};

export default function Dashboard({ stats, recentFailedRuns, aiOverview, canOpenEditorRun }: Props): JSX.Element {
    const { formatDateTime } = useDateFormatter();
    const { t } = useTranslations();

    return (
        <AdminLayout>
            <Head title="Admin Dashboard" />
            <AdminPageHeader title="Administration Dashboard" description="Technical/system overview and editorial health indicators." />

            <div className="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                {[
                    ['Total users', stats.users], ['Active users', stats.activeUsers], ['Roles', stats.roles], ['Permissions', stats.permissions], ['Languages', stats.languages],
                    ['Active editorial schedules', stats.activeSchedules], ["Today's editorial runs", stats.todayRuns], ['Failed editorial runs', stats.failedRuns], ['AI providers', stats.aiProviders], ['Active AI providers', stats.activeAiProviders],
                ].map(([label, value]) => (
                    <Card key={String(label)}><CardHeader className="pb-2"><CardDescription>{label}</CardDescription></CardHeader><CardContent><CardTitle>{value ?? '-'}</CardTitle></CardContent></Card>
                ))}
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle>System overview</CardTitle></CardHeader>
                    <CardContent className="space-y-1 text-sm text-slate-700">
                        <p>Users: {stats.users}</p><p>Roles: {stats.roles}</p><p>Permissions: {stats.permissions}</p><p>Languages: {stats.languages}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Editorial system overview</CardTitle></CardHeader>
                    <CardContent className="space-y-1 text-sm text-slate-700">
                        <p>Active schedules: {stats.activeSchedules}</p><p>Runs today: {stats.todayRuns}</p><p>Failed runs: {stats.failedRuns}</p><p>Draft scripts: {stats.draftScripts}</p><p>Planned editions: {stats.plannedEditions}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Recent failed runs</CardTitle></CardHeader>
                    <CardContent className="space-y-2">
                        {recentFailedRuns.length ? recentFailedRuns.map((run) => (
                            <div key={run.id} className="rounded border p-3 text-sm">
                                <div className="flex items-center justify-between"><p className="font-medium">{run.schedule?.name ?? `Run #${run.id}`}</p><Badge variant="danger">{run.status}</Badge></div>
                                <p className="text-slate-600">{formatDateTime(run.scheduled_for)}</p>
                                <p className="text-red-700">{run.error_message || '-'}</p>
                                {canOpenEditorRun && <Link className="text-cyan-700" href={route('editor.editorial-schedule-runs.show', run.id)}>Open run</Link>}
                            </div>
                        )) : <p className="text-sm">No failed runs.</p>}
                    </CardContent>
                </Card>


                <Card>
                    <CardHeader><CardTitle>{t('World Map')}</CardTitle><CardDescription>{t('View configured bulletin locations')}</CardDescription></CardHeader>
                    <CardContent><Link className="text-cyan-700" href={route('admin.world-map.index')}>{t('Open')}</Link></CardContent>
                </Card>

                {aiOverview && (
                    <Card>
                        <CardHeader><CardTitle>AI configuration overview</CardTitle></CardHeader>
                        <CardContent className="space-y-1 text-sm text-slate-700">
                            <p>Default provider: {aiOverview.defaultProvider ?? 'Not configured'}</p>
                            <p>Active providers: {aiOverview.active}</p>
                            <p>Providers supporting web search: {aiOverview.supportsWebSearch}</p>
                            <p>Providers supporting JSON mode: {aiOverview.supportsJsonMode}</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}
