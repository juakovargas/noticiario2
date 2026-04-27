import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslations } from '@/i18n/useTranslations';

type Run = {
    id: number;
    scheduled_for: string | null;
    status: string;
    schedule?: { id: number; name: string; location?: { name: string }; news_category?: { name: string }; language?: { name: string; code: string } };
    script_id?: number | null;
    script?: { id: number; title: string; status: string };
};

type Props = {
    stats: Record<string, number>;
    todayRuns: Run[];
    pendingPromptRuns: Run[];
    waitingResponseRuns: Run[];
    responseReceivedRuns: Run[];
    scriptsNeedingReview: Array<{ id: number; title: string; status: string; review_status?: string }>;
    readyToApprove: Array<{ id: number; title: string; status: string; review_status?: string }>;
    scriptsBlockedBySources: Array<{ id: number; title: string; status: string; review_status?: string }>;
    upcomingSchedules: Array<{ id: number; name: string; scheduled_time: string | null; frequency_type: string }>;
    recentBulletinPromptRuns: Array<{ id:number; status:string; bulletin_type?: { name:string } }> ;
};

const cards: Array<{ key: string; label: string }> = [
    { key: 'activeSchedules', label: 'Active schedules' },
    { key: 'activeBulletinTypes', label: 'Active bulletin types' },
    { key: 'recentPromptRuns', label: 'Recent prompt runs' },
    { key: 'promptRunsWaitingAiResponse', label: 'Prompt runs waiting for AI response' },
    { key: 'promptRunsReadyToCreateScript', label: 'Prompt runs ready to create script' },
    { key: 'todayRuns', label: "Today's runs" },
    { key: 'pendingPrompts', label: 'Pending prompts' },
    { key: 'waitingResponses', label: 'Waiting responses' },
    { key: 'draftScripts', label: 'Draft scripts' },
    { key: 'plannedEditions', label: 'Planned editions' },
    { key: 'scriptsPendingReview', label: 'Scripts pending review' },
    { key: 'scriptsNeedingSources', label: 'Scripts needing sources' },
    { key: 'scriptsApprovedToday', label: 'Scripts approved today' },
    { key: 'sourcesPendingVerification', label: 'Sources pending verification' },
    { key: 'weakSources', label: 'Weak sources' },
    { key: 'missingSources', label: 'Missing sources' },
    { key: 'brokenRejectedSources', label: 'Broken sources' },
    { key: 'scriptsBlockedBySources', label: 'Scripts blocked by sources' },
];

function RunRow({ run }: { run: Run }): JSX.Element {
    const { formatDateTime } = useDateFormatter();
    const quickAction =
        run.status === 'pending'
            ? <Button size="sm" onClick={() => router.post(route('editor.editorial-schedule-runs.generate-prompt', run.id))}>Generate Prompt</Button>
            : run.status === 'response_received'
              ? <Button size="sm" onClick={() => router.post(route('editor.editorial-schedule-runs.create-script', run.id))}>Create Script</Button>
              : <Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-schedule-runs.show', run.id)}>Open run</Link></Button>;

    return (
        <div className="rounded border p-3 text-sm">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="font-medium">{run.schedule?.name ?? `Run #${run.id}`}</p>
                <Badge variant="outline">{run.status}</Badge>
            </div>
            <p className="text-slate-600">{formatDateTime(run.scheduled_for)}</p>
            <div className="mt-2">{quickAction}</div>
        </div>
    );
}

export default function Dashboard({ stats, todayRuns, pendingPromptRuns, waitingResponseRuns, responseReceivedRuns, scriptsNeedingReview, upcomingSchedules, readyToApprove, scriptsBlockedBySources, recentBulletinPromptRuns }: Props): JSX.Element {
    const { t } = useTranslations();

    return (
        <EditorLayout>
            <Head title={t('Dashboard')} />
            <AdminPageHeader title={t('Dashboard')} description="Operational desk for today: create runs, generate prompts, receive AI responses, and create scripts." />

            <div className="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                {cards.map((card) => (
                    <Card key={card.key}>
                        <CardHeader className="pb-2"><CardDescription>{t(card.label)}</CardDescription></CardHeader>
                        <CardContent><CardTitle>{stats[card.key] ?? 0}</CardTitle></CardContent>
                    </Card>
                ))}
            </div>

            <div className="mb-6 flex flex-wrap gap-2">
                <Button asChild><Link href={route('editor.bulletin-types.index')}>{t('Open Bulletin Types')}</Link></Button>
                <Button asChild variant="outline"><Link href={route('editor.bulletin-prompt-runs.index')}>{t('Prompt Runs')}</Link></Button>
                <Button asChild><Link href={route('editor.editorial-schedules.create')}>Create Schedule</Link></Button>
                <Button asChild variant="outline"><Link href={route('editor.editorial-schedule-runs.index')}>Open Editorial Runs</Link></Button>
                <Button asChild variant="outline"><Link href={route('editor.editorial-schedules.index')}>Open Editorial Schedules</Link></Button>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card><CardHeader><CardTitle>Today's runs</CardTitle></CardHeader><CardContent className="space-y-2">{todayRuns.length ? todayRuns.map((run) => <RunRow key={run.id} run={run} />) : <p>No runs scheduled for today</p>}</CardContent></Card>
                <Card><CardHeader><CardTitle>Pending prompt runs</CardTitle></CardHeader><CardContent className="space-y-2">{pendingPromptRuns.length ? pendingPromptRuns.map((run) => <RunRow key={run.id} run={run} />) : <p>No tasks pending</p>}</CardContent></Card>
                <Card><CardHeader><CardTitle>Waiting for AI response</CardTitle></CardHeader><CardContent className="space-y-2">{waitingResponseRuns.length ? waitingResponseRuns.map((run) => <RunRow key={run.id} run={run} />) : <p>No tasks pending</p>}</CardContent></Card>
                <Card><CardHeader><CardTitle>Responses received</CardTitle></CardHeader><CardContent className="space-y-2">{responseReceivedRuns.length ? responseReceivedRuns.map((run) => <RunRow key={run.id} run={run} />) : <p>No tasks pending</p>}</CardContent></Card>
                <Card><CardHeader><CardTitle>{t('Scripts needing review')}</CardTitle></CardHeader><CardContent className="space-y-2">{scriptsNeedingReview.length ? scriptsNeedingReview.map((script) => <div key={script.id} className="rounded border p-3 text-sm"><p className="font-medium">{script.title}</p><p className="text-slate-600">{script.review_status ?? script.status}</p><Button asChild size="sm" variant="outline" className="mt-2"><Link href={route('editor.scripts.review', script.id)}>{t('Review')}</Link></Button></div>) : <p>No tasks pending</p>}</CardContent></Card>
                <Card><CardHeader><CardTitle>{t('Ready to approve')}</CardTitle></CardHeader><CardContent className="space-y-2">{readyToApprove.length ? readyToApprove.map((script) => <div key={script.id} className="rounded border p-3 text-sm"><p className="font-medium">{script.title}</p><p className="text-slate-600">{script.review_status ?? script.status}</p><Button asChild size="sm" variant="outline" className="mt-2"><Link href={route('editor.scripts.review', script.id)}>{t('Approve script')}</Link></Button></div>) : <p>{t('No tasks pending')}</p>}</CardContent></Card>
                <Card><CardHeader><CardTitle>{t('Scripts blocked by sources')}</CardTitle></CardHeader><CardContent className="space-y-2">{scriptsBlockedBySources.length ? scriptsBlockedBySources.map((script) => <div key={script.id} className="rounded border p-3 text-sm"><p className="font-medium">{script.title}</p><p className="text-slate-600">{script.review_status ?? script.status}</p><Button asChild size="sm" variant="outline" className="mt-2"><Link href={route('editor.scripts.review', script.id)}>{t('Review')}</Link></Button></div>) : <p>{t('No tasks pending')}</p>}</CardContent></Card>
                <Card><CardHeader><CardTitle>{t('Recent prompt runs')}</CardTitle></CardHeader><CardContent className="space-y-2">{recentBulletinPromptRuns.length ? recentBulletinPromptRuns.map((run) => <div key={run.id} className="rounded border p-3 text-sm"><p className="font-medium">{run.bulletin_type?.name || `Run #${run.id}`}</p><p className="text-slate-600">{run.status}</p><Button asChild size="sm" variant="outline" className="mt-2"><Link href={route('editor.bulletin-prompt-runs.show', run.id)}>{t('Open')}</Link></Button></div>) : <p>{t('No tasks pending')}</p>}</CardContent></Card>
                <Card><CardHeader><CardTitle>Upcoming active schedules</CardTitle></CardHeader><CardContent className="space-y-2">{upcomingSchedules.length ? upcomingSchedules.map((schedule) => <div key={schedule.id} className="rounded border p-3 text-sm"><p className="font-medium">{schedule.name}</p><p className="text-slate-600">{schedule.frequency_type} · {(schedule.scheduled_time ?? '').slice(0, 5) || '-'}</p><div className="mt-2 flex gap-2"><Button size="sm" onClick={() => router.post(route('editor.editorial-schedules.runs.store', schedule.id))}>Create Run</Button><Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-schedules.show', schedule.id)}>Open</Link></Button></div></div>) : <p>No active schedules.</p>}</CardContent></Card>
            </div>
        </EditorLayout>
    );
}
