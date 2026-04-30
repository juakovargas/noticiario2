import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';

type ScriptItem = { id:number; title:string; final_title:string|null; short_description:string|null; status:string; production_status:string; review_status:string; created_at:string|null; ready_for_production_at:string|null; execution_date:string|null; origin:string; source_verification_status:string; metadata_complete:boolean; bulletin_type:{id:number;name:string;location:string|null;news_category:string|null;language:string|null;edition_type:string|null}|null; origin_prompt_run:{id:number;title:string;scheduled_for:string|null;editorial_schedule_run_scheduled_for:string|null}|null };

export default function Index({ scripts, filters, filterOptions, summary }: any): JSX.Element {
  const { t } = useTranslations();
  const { formatDateTime } = useDateFormatter();
  const [form, setForm] = useState({ ...filters });

  const grouped = useMemo(() => {
    const key = form.group_by;
    if (!key || key === 'none') return { [t('No grouping')]: scripts.data };
    return (scripts.data as ScriptItem[]).reduce((acc:any, item) => {
      const group = key === 'bulletin_type' ? (item.bulletin_type?.name ?? t('Unknown')) : key === 'status' ? item.status : (item.execution_date?.slice(0,10) ?? t('Unknown'));
      acc[group] = acc[group] || []; acc[group].push(item); return acc;
    }, {});
  }, [scripts.data, form.group_by, t]);

  const submit = (e: FormEvent) => { e.preventDefault(); router.get(route('editor.scripts.index'), form, { preserveState: true, preserveScroll: true }); };

  return <EditorLayout>
    <Head title={t('Scripts control center')} />
    <AdminPageHeader helpKey="editor.scripts.index" title={t('Scripts')} description={t('Scripts control center')} actionLabel={t('Create script')} actionHref={route('editor.scripts.create')} />

    <div className='grid gap-3 md:grid-cols-5 mb-4'>
      <Card><CardContent className='pt-4'><div className='text-xs text-slate-500'>{t('Total scripts')}</div><div className='text-xl font-semibold'>{summary.total}</div></CardContent></Card>
      <Card><CardContent className='pt-4'><div className='text-xs text-slate-500'>{t('Generated today')}</div><div className='text-xl font-semibold'>{summary.generated_today}</div></CardContent></Card>
      <Card><CardContent className='pt-4'><div className='text-xs text-slate-500'>{t('Pending review')}</div><div className='text-xl font-semibold'>{summary.pending_review}</div></CardContent></Card>
      <Card><CardContent className='pt-4'><div className='text-xs text-slate-500'>{t('Ready for production')}</div><div className='text-xl font-semibold'>{summary.ready_for_production}</div></CardContent></Card>
      <Card><CardContent className='pt-4'><div className='text-xs text-slate-500'>{t('Needs attention')}</div><div className='text-xl font-semibold'>{summary.needs_attention}</div></CardContent></Card>
    </div>

    <Card className='mb-4'><CardContent className='pt-6'><form onSubmit={submit} className='grid gap-3 md:grid-cols-3 lg:grid-cols-5'>
      <Input value={form.search ?? ''} onChange={(e)=>setForm((p:any)=>({...p,search:e.target.value}))} placeholder={t('Search')} />
      <select className='rounded-md border px-3 py-2 text-sm' value={form.bulletin_type_id ?? ''} onChange={(e)=>setForm((p:any)=>({...p,bulletin_type_id:e.target.value}))}><option value=''>{t('All informativos')}</option>{filterOptions.bulletinTypes.map((b:any)=><option key={b.id} value={b.id}>{b.name}</option>)}</select>
      <select className='rounded-md border px-3 py-2 text-sm' value={form.execution_mode ?? ''} onChange={(e)=>setForm((p:any)=>({...p,execution_mode:e.target.value}))}><option value=''>{t('All origins')}</option>{filterOptions.executionModes.map((m:string)=><option key={m} value={m}>{t(m === 'automatic' ? 'Automatic' : m === 'manual' ? 'Manual' : m === 'manual_ai' ? 'Manual with AI' : m === 'imported' ? 'Imported' : 'Unknown')}</option>)}</select>
      <Input type='date' value={form.date_from ?? ''} onChange={(e)=>setForm((p:any)=>({...p,date_from:e.target.value}))} />
      <Input type='date' value={form.date_to ?? ''} onChange={(e)=>setForm((p:any)=>({...p,date_to:e.target.value}))} />
      <select className='rounded-md border px-3 py-2 text-sm' value={form.group_by ?? 'none'} onChange={(e)=>setForm((p:any)=>({...p,group_by:e.target.value}))}><option value='none'>{t('No grouping')}</option><option value='bulletin_type'>{t('Group by informativo')}</option><option value='execution_date'>{t('Group by execution date')}</option><option value='status'>{t('Group by status')}</option></select>
      <Button type='submit'>{t('Apply filters')}</Button>
    </form></CardContent></Card>

    {Object.entries(grouped).map(([group, rows]: any) => <Card key={group} className='mb-4'><CardContent className='pt-6 overflow-x-auto'>
      {form.group_by ? <h3 className='font-semibold mb-3'>{group} · {rows.length}</h3> : null}
      <table className='w-full min-w-[1100px] text-sm'><thead><tr className='border-b text-slate-500'><th className='text-left p-2'>{t('Script')}</th><th className='text-left p-2'>{t('Informativo')}</th><th className='text-left p-2'>{t('Execution date')}</th><th className='text-left p-2'>{t('Origin')}</th><th className='text-left p-2'>{t('Editorial status')}</th><th className='text-left p-2'>{t('Production readiness')}</th><th className='text-right p-2'>{t('Actions')}</th></tr></thead>
      <tbody>{rows.length ? rows.map((item: ScriptItem) => <tr key={item.id} className='border-b'><td className='p-2'><div className='font-medium'>{item.final_title || item.title}</div><div className='text-xs text-slate-500'>{item.short_description || '-'}</div><div className='mt-1 flex gap-1'><StatusBadge status={item.status} /><StatusBadge status={item.production_status} /><StatusBadge status={item.review_status} /></div></td><td className='p-2'><div>{item.bulletin_type?.name || t('Unknown')}</div><div className='text-xs text-slate-500'>{[item.bulletin_type?.location,item.bulletin_type?.news_category,item.bulletin_type?.language,item.bulletin_type?.edition_type].filter(Boolean).join(' · ') || t('Not available')}</div></td><td className='p-2'><div>{item.execution_date ? formatDateTime(item.execution_date) : t('Unknown')}</div><div className='text-xs text-slate-500'>{t('Created date')}: {item.created_at ? formatDateTime(item.created_at) : '-'}</div></td><td className='p-2'>{t(item.origin === 'automatic' ? 'Automatic' : item.origin === 'manual_ai' ? 'Manual with AI' : item.origin === 'manual' ? 'Manual' : item.origin === 'imported' ? 'Imported' : 'Unknown origin')}</td><td className='p-2'><div>{t(item.review_status === 'approved' ? 'Review approved' : item.review_status === 'rejected' ? 'Review rejected' : 'Review pending')}</div><div className='text-xs text-slate-500'>{t(item.source_verification_status === 'verified' ? 'Sources verified' : item.source_verification_status === 'weak' ? 'Sources weak' : item.source_verification_status === 'rejected' ? 'Sources rejected' : 'Sources pending')}</div></td><td className='p-2'><div>{item.metadata_complete ? t('Metadata OK') : t('Metadata missing')}</div><div className='text-xs text-slate-500'>{item.ready_for_production_at ? t('Ready for production') : t('Next: Audio')}</div></td><td className='p-2 text-right'><div className='flex gap-2 justify-end'><Button asChild size='sm' variant='outline'><Link href={route('editor.scripts.show', item.id)}>{t('View')}</Link></Button><Button asChild size='sm' variant='outline'><Link href={route('editor.scripts.review', item.id)}>{t('Review')}</Link></Button>{item.origin_prompt_run ? <Button asChild size='sm' variant='outline'><Link href={route('editor.bulletin-prompt-runs.show', item.origin_prompt_run.id)}>{t('Open prompt run')}</Link></Button> : null}</div></td></tr>) : <tr><td colSpan={7} className='p-6 text-center text-slate-500'>{t('No scripts found')} · {t('Try changing filters')} · {t('Run an informativo from Automation')}</td></tr>}</tbody></table>
      <Pagination links={scripts.links} />
    </CardContent></Card>)}
  </EditorLayout>;
}
