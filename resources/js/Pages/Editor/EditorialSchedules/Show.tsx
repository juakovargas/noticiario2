import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Show({ schedule, recentRuns }: any): JSX.Element {
  const { formatDateTime } = useDateFormatter();
  return <EditorLayout><Head title={schedule.name} /><AdminPageHeader title={schedule.name} description="Editorial schedule details." actionLabel="Edit Schedule" actionHref={route('editor.editorial-schedules.edit', schedule.id)} />
  <div className="mb-4 flex gap-2"><Button onClick={()=>router.post(route('editor.editorial-schedules.runs.store', schedule.id))}>Create Run</Button><Button asChild variant="outline"><Link href={route('editor.editorial-schedule-runs.index')}>View Runs</Link></Button></div>
  <Card><CardContent className="space-y-2 pt-6 text-sm"><p><strong>Frequency:</strong> {schedule.frequency_type}</p><p><strong>Edition type:</strong> {schedule.edition_type}</p><p><strong>Scheduled time:</strong> {schedule.scheduled_time?.slice(0,5) || '-'}</p><p><strong>Scheduled date:</strong> {schedule.scheduled_date?.slice(0,10) || '-'}</p><p><strong>Weekdays:</strong> {schedule.weekdays?.join(', ') || '-'}</p><p><strong>Timezone:</strong> {schedule.timezone || '-'}</p><p><strong>Tone:</strong> {schedule.tone || '-'}</p></CardContent></Card>
  <Card className="mt-4"><CardContent className="pt-6"><h3 className="mb-3 font-semibold">Recent runs</h3><div className="space-y-2">{recentRuns.length ? recentRuns.map((run:any)=><div key={run.id} className="rounded border border-slate-200 p-3 text-sm"><p><strong>Status:</strong> {run.status}</p><p><strong>Scheduled for:</strong> {formatDateTime(run.scheduled_for)}</p><Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-schedule-runs.show', run.id)}>Open run</Link></Button></div>) : <p>No runs yet.</p>}</div></CardContent></Card>
  </EditorLayout>;
}
