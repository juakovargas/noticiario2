import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { useDateFormatter } from '@/lib/useDateFormatter';
import ViewerLayout from '@/Layouts/ViewerLayout';
import { Head, Link } from '@inertiajs/react';
import { useTranslations } from '@/i18n/useTranslations';

type Props = {
    recentCompletedScripts: Array<{ id: number; title: string; status: string; updated_at: string | null }>;
    recentCompletedRuns: Array<{ id: number; status: string; schedule?: { name: string }; script?: { title: string }; completed_at: string | null }>;
    upcomingEditions: Array<{ id: number; title: string; status: string; scheduled_for: string | null }>;
};

export default function Dashboard({ recentCompletedScripts, recentCompletedRuns, upcomingEditions }: Props): JSX.Element {
    const { formatDateTime } = useDateFormatter();
    const { t } = useTranslations();

    return (
        <ViewerLayout title="Viewer Dashboard">
            <Head title="Viewer Dashboard" />
            <p className="mb-4 text-sm text-slate-600">Read-only overview</p>

            <div className="grid gap-4 lg:grid-cols-3">
                <Card>
                    <CardHeader><CardTitle>Published Content</CardTitle><CardDescription>Read-only published content area.</CardDescription></CardHeader>
                    <CardContent><Link href={route('viewer.published-content')} className="text-sm font-medium text-emerald-700 hover:text-emerald-900">Open placeholder page</Link></CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>{t('World Map')}</CardTitle><CardDescription>{t('View available content by location')}</CardDescription></CardHeader>
                    <CardContent><Link href={route('viewer.world-map.index')} className="text-sm font-medium text-emerald-700 hover:text-emerald-900">{t('Open')}</Link></CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Recent completed scripts</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        {recentCompletedScripts.length ? recentCompletedScripts.map((script) => (
                            <div key={script.id} className="rounded border p-2"><p className="font-medium">{script.title}</p><p className="text-slate-600">{script.status} · {formatDateTime(script.updated_at)}</p></div>
                        )) : <p>No content yet.</p>}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Upcoming scheduled editions</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        {upcomingEditions.length ? upcomingEditions.map((edition) => (
                            <div key={edition.id} className="rounded border p-2"><p className="font-medium">{edition.title}</p><p className="text-slate-600">{edition.status} · {formatDateTime(edition.scheduled_for)}</p></div>
                        )) : <p>No upcoming editions.</p>}
                    </CardContent>
                </Card>
            </div>

            <Card className="mt-4">
                <CardHeader><CardTitle>Completed editorial runs</CardTitle></CardHeader>
                <CardContent className="space-y-2 text-sm">
                    {recentCompletedRuns.length ? recentCompletedRuns.map((run) => (
                        <div key={run.id} className="rounded border p-2"><p className="font-medium">{run.schedule?.name ?? `Run #${run.id}`}</p><p className="text-slate-600">{run.status}</p><p className="text-slate-500">{run.script?.title ?? 'No script linked'}</p></div>
                    )) : <p>No completed runs yet.</p>}
                </CardContent>
            </Card>
        </ViewerLayout>
    );
}
