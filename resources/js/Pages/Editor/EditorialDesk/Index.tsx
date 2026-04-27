import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ todayRuns, upcomingSchedules, statusCounts }: any): JSX.Element {
 const { formatDateTime } = useDateFormatter();
 return <EditorLayout><Head title="Editorial Desk" /><AdminPageHeader title="Editorial Desk" description="Today's run planning and manual AI workflow." />
 <div className="mb-4 grid gap-3 md:grid-cols-4"><Card><CardContent className="pt-6 text-sm">Runs needing prompt: <strong>{statusCounts.needs_prompt}</strong></CardContent></Card><Card><CardContent className="pt-6 text-sm">Waiting for AI response: <strong>{statusCounts.waiting_ai_response}</strong></CardContent></Card><Card><CardContent className="pt-6 text-sm">Response received: <strong>{statusCounts.response_received}</strong></CardContent></Card><Card><CardContent className="pt-6 text-sm">Script created: <strong>{statusCounts.script_created}</strong></CardContent></Card></div>
 <div className="grid gap-4 md:grid-cols-2"><Card><CardContent className="pt-6"><h3 className="mb-3 font-semibold">Today's runs</h3><div className="space-y-2">{todayRuns.length ? todayRuns.map((run:any)=><div key={run.id} className="rounded border border-slate-200 p-3 text-sm"><p>{run.schedule?.name}</p><p>{formatDateTime(run.scheduled_for)} · {run.status}</p><Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-schedule-runs.show', run.id)}>Open run</Link></Button></div>) : <p>No runs today.</p>}</div></CardContent></Card>
 <Card><CardContent className="pt-6"><h3 className="mb-3 font-semibold">Upcoming schedules</h3><div className="space-y-2">{upcomingSchedules.length ? upcomingSchedules.map((schedule:any)=><div key={schedule.id} className="rounded border border-slate-200 p-3 text-sm"><p className="font-medium">{schedule.name}</p><p>{schedule.frequency_type} · {schedule.scheduled_time?.slice(0,5) || '-'}</p><div className="mt-2 flex gap-2"><Button size="sm" onClick={()=>router.post(route('editor.editorial-schedules.runs.store', schedule.id))}>Create Run</Button><Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-schedules.show', schedule.id)}>Open</Link></Button></div></div>) : <p>No active schedules.</p>}</div></CardContent></Card></div>
 </EditorLayout>;
}
