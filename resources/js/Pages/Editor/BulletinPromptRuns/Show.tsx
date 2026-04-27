import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

export default function Show({ run }: any): JSX.Element {
 const { t } = useTranslations(); const { formatDateTime } = useDateFormatter();
 const form = useForm({ response_text: run.ai_response_text ?? '' });
 const copyPrompt = async (): Promise<void> => { if (run.generated_prompt && navigator?.clipboard) await navigator.clipboard.writeText(run.generated_prompt); };
 return <EditorLayout><Head title={`${t('Prompt Run')} #${run.id}`} /><AdminPageHeader title={`${t('Prompt Run')} #${run.id}`} description={t('Copy this prompt into your preferred AI tool')} />
 <div className="mb-4 flex flex-wrap gap-2"><Button onClick={()=>router.post(route('editor.bulletin-prompt-runs.generate-prompt', run.id))}>{t('Generate Prompt')}</Button><Button variant="secondary" onClick={copyPrompt}>{t('Copy Prompt')}</Button><Button onClick={()=>form.post(route('editor.bulletin-prompt-runs.save-response', run.id))}>{t('Save AI Response')}</Button><Button variant="outline" onClick={()=>router.post(route('editor.bulletin-prompt-runs.create-script', run.id))}>{t('Create Script')}</Button>{run.script_id && <Button asChild variant="ghost"><Link href={route('editor.scripts.show', run.script_id)}>{t('Open Script')}</Link></Button>}</div>
 <Card><CardContent className="space-y-2 pt-6 text-sm"><p><strong>{t('Bulletin Type')}:</strong> {run.bulletin_type?.name}</p><p><strong>{t('Prompt Profile')}:</strong> {run.prompt_profile?.name ?? '-'}</p><p><strong>{t('Status')}:</strong> {run.status}</p><p><strong>{t('Scheduled for')}:</strong> {formatDateTime(run.scheduled_for)}</p>{run.edition_id && <p><strong>{t('Editions')}:</strong> <Link className="text-cyan-700" href={route('editor.editions.show', run.edition_id)}>{run.edition?.title || `#${run.edition_id}`}</Link></p>}</CardContent></Card>
 <Card className="mt-4"><CardContent className="pt-6"><h3 className="mb-2 font-semibold">{t('Generated Prompt')}</h3><pre className="whitespace-pre-wrap rounded bg-slate-100 p-3 text-xs">{run.generated_prompt || '-'}</pre></CardContent></Card>
 <Card className="mt-4"><CardContent className="pt-6"><h3 className="mb-2 font-semibold">{t('AI Response')}</h3><textarea className="min-h-52 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder={t('Paste the AI response here')} value={form.data.response_text} onChange={(e)=>form.setData('response_text', e.target.value)} /></CardContent></Card>
 {(run.parsed_response?.items?.length ?? 0) > 0 && <Card className="mt-4"><CardContent className="space-y-2 pt-6"><h3 className="font-semibold">{t('Parsed response preview')}</h3>{run.parsed_response.items.map((item:any, index:number)=><div key={index} className="rounded border p-3 text-sm"><p><strong>{t('Headline')}:</strong> {item.headline || '-'}</p><p><strong>{t('Summary')}:</strong> {item.summary || '-'}</p><p><strong>{t('Editorial angle')}:</strong> {item.editorial_angle || '-'}</p><p><strong>{t('Source hints')}:</strong> {(item.source_hints || []).join(' · ') || '-'}</p></div>)}</CardContent></Card>}
 </EditorLayout>;
}
