import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';

export default function Show({ run }: any): JSX.Element {
 const { formatDateTime } = useDateFormatter();
 const form = useForm({ response_text: run.ai_response_text ?? '' });
 const copyPrompt = async (): Promise<void> => { if (!run.generated_prompt) return; if (navigator?.clipboard) await navigator.clipboard.writeText(run.generated_prompt); };
 return <EditorLayout><Head title={`Run #${run.id}`} /><AdminPageHeader title={`Editorial Run #${run.id}`} description="Manual AI copy/paste workflow." />
 <div className="mb-4 flex flex-wrap gap-2"><Button onClick={()=>router.post(route('editor.editorial-schedule-runs.generate-prompt', run.id))}>Generate Prompt</Button><Button variant="secondary" onClick={copyPrompt}>Copy Prompt</Button><Button onClick={()=>form.post(route('editor.editorial-schedule-runs.receive-response', run.id))}>Save Response</Button><Button variant="outline" onClick={()=>router.post(route('editor.editorial-schedule-runs.create-script', run.id))}>Create Script</Button>{run.script_id && <Button asChild variant="ghost"><Link href={route('editor.scripts.show', run.script_id)}>Open Script</Link></Button>}</div>
 <Card><CardContent className="space-y-3 pt-6 text-sm"><p><strong>Scheduled for:</strong> {formatDateTime(run.scheduled_for)}</p><p><strong>Status:</strong> {run.status}</p><p><strong>Schedule:</strong> {run.schedule?.name}</p>{run.edition_id && <p><strong>Edition:</strong> <Link className="text-cyan-700" href={route('editor.editions.show', run.edition_id)}>{run.edition?.title ?? `#${run.edition_id}`}</Link></p>}{run.error_message && <p className="text-red-600"><strong>Error:</strong> {run.error_message}</p>}</CardContent></Card>
 <Card className="mt-4"><CardContent className="pt-6"><h3 className="mb-2 font-semibold">Generated Prompt</h3><pre className="whitespace-pre-wrap rounded bg-slate-100 p-3 text-xs">{run.generated_prompt || '-'}</pre></CardContent></Card>
 <Card className="mt-4"><CardContent className="pt-6"><h3 className="mb-2 font-semibold">AI Response</h3><textarea className="min-h-52 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.data.response_text} onChange={(e)=>form.setData('response_text', e.target.value)} /></CardContent></Card>
 </EditorLayout>;
}
