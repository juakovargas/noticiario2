import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

type Props = {
    stats: Record<string, number>;
    upcomingSchedules: Array<{ id:number; name:string; next_run_at?:string|null }>;
    recentBulletinPromptRuns: Array<{ id:number; title:string; metadata?:Record<string, unknown>|null }>;
};

export default function Dashboard({ stats, upcomingSchedules, recentBulletinPromptRuns }: Props): JSX.Element {
    const { t } = useTranslations();

    return (
        <EditorLayout>
            <Head title={t('Dashboard')} />
            <AdminPageHeader helpKey="editor.dashboard" title={t('Dashboard')} description={t('What needs attention')} />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>{t('Continue workflow')}</CardTitle>
                    <CardDescription>{t('Open Editorial Workbench')}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button asChild><Link href={route('editor.workbench')}>{t('Editorial Workbench')}</Link></Button>
                </CardContent>
            </Card>

            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <Card><CardHeader><CardDescription>{t('Prompt runs waiting for response')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.promptRunsWaitingAiResponse ?? 0}</CardTitle></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Responses ready to become scripts')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.promptRunsReadyToCreateScript ?? 0}</CardTitle></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Scripts needing review')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.scriptsPendingReview ?? 0}</CardTitle></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Scripts missing metadata')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.scriptsMissingMetadata ?? 0}</CardTitle></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Scripts ready for production')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.scriptsReadyForProduction ?? 0}</CardTitle></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Source issues pending')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.scriptsBlockedBySources ?? 0}</CardTitle></CardContent></Card>
            </div>

            <div className="mt-6 grid gap-3 md:grid-cols-2">
                <Card><CardHeader><CardTitle>{t('Due schedules')}</CardTitle></CardHeader><CardContent className="text-sm space-y-2">{upcomingSchedules.slice(0,5).map((schedule) => <div key={schedule.id} className="border rounded p-2"><p className="font-medium">{schedule.name}</p><p>{t('Next scheduled run')}: {schedule.next_run_at ?? '-'}</p></div>)}</CardContent></Card>
                <Card><CardHeader><CardTitle>{t('Recently created runs')}</CardTitle></CardHeader><CardContent className="text-sm space-y-2">{recentBulletinPromptRuns.slice(0,5).map((run) => <div key={run.id} className="border rounded p-2"><p className="font-medium">{run.title}</p><p>{(run.metadata as Record<string, unknown>)?.created_from_schedule_runner ? t('Created from schedule') : t('Manual')}</p></div>)}</CardContent></Card>
            </div>

        </EditorLayout>
    );
}
