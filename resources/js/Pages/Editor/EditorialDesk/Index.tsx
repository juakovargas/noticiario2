import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';

type Run = { id: number; status: string; scheduled_for: string | null; schedule?: { id: number; name: string }; script_id?: number | null; script?: { id: number; title: string } };
type Props = {
    todayRuns: Run[];
    promptReadyRuns: Run[];
    waitingAiResponseRuns: Run[];
    responseReceivedRuns: Run[];
    scriptCreatedRuns: Run[];
    upcomingSchedules: Array<{ id: number; name: string; frequency_type: string; scheduled_time: string | null }>;
};

function RunList({ runs, title }: { runs: Run[]; title: string }): JSX.Element {
    const { formatDateTime } = useDateFormatter();
    return (
        <Card>
            <CardHeader><CardTitle>{title}</CardTitle></CardHeader>
            <CardContent className="space-y-2">
                {runs.length ? runs.map((run) => (
                    <div key={run.id} className="rounded border p-3 text-sm">
                        <div className="flex items-center justify-between gap-2"><p className="font-medium">{run.schedule?.name ?? `Run #${run.id}`}</p><Badge variant="outline">{run.status}</Badge></div>
                        <p className="text-slate-600">{formatDateTime(run.scheduled_for)}</p>
                        <div className="mt-2 flex flex-wrap gap-2">
                            <Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-schedule-runs.show', run.id)}>Open run</Link></Button>
                            {run.status === 'pending' && <Button size="sm" onClick={() => router.post(route('editor.editorial-schedule-runs.generate-prompt', run.id))}>Generate Prompt</Button>}
                            {run.status === 'response_received' && <Button size="sm" onClick={() => router.post(route('editor.editorial-schedule-runs.create-script', run.id))}>Create Script</Button>}
                            {!!run.script_id && <Button asChild size="sm"><Link href={route('editor.scripts.show', run.script_id)}>Open Script</Link></Button>}
                        </div>
                    </div>
                )) : <p>No tasks pending</p>}
            </CardContent>
        </Card>
    );
}

export default function Index({ todayRuns, promptReadyRuns, waitingAiResponseRuns, responseReceivedRuns, scriptCreatedRuns, upcomingSchedules }: Props): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Editorial Desk" />
            <AdminPageHeader title="Editorial Desk" description="Focused page for manual AI workflow operations." />

            <div className="mb-6">
                <Card>
                    <CardHeader><CardTitle>Upcoming active schedules</CardTitle></CardHeader>
                    <CardContent className="space-y-2">
                        {upcomingSchedules.length ? upcomingSchedules.map((schedule) => (
                            <div key={schedule.id} className="rounded border p-3 text-sm">
                                <p className="font-medium">{schedule.name}</p>
                                <p className="text-slate-600">{schedule.frequency_type} · {(schedule.scheduled_time ?? '').slice(0, 5) || '-'}</p>
                                <div className="mt-2 flex gap-2">
                                    <Button size="sm" onClick={() => router.post(route('editor.editorial-schedules.runs.store', schedule.id))}>Create Run</Button>
                                    <Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-schedules.show', schedule.id)}>Open</Link></Button>
                                </div>
                            </div>
                        )) : <p>No active schedules.</p>}
                    </CardContent>
                </Card>
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <RunList title="Today's runs" runs={todayRuns} />
                <RunList title="Prompt ready" runs={promptReadyRuns} />
                <RunList title="Waiting for AI response" runs={waitingAiResponseRuns} />
                <RunList title="Response received" runs={responseReceivedRuns} />
                <RunList title="Script created/completed" runs={scriptCreatedRuns} />
            </div>
        </EditorLayout>
    );
}
