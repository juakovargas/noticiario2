import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

export default function Index({ runs, filters, statuses, schedules }: any): JSX.Element {
  const { formatDateTime } = useDateFormatter();
  const { t } = useTranslations();
  const [form, setForm] = useState(filters ?? {});

  const submit = (e: FormEvent): void => {
    e.preventDefault();
    router.get(route('editor.editorial-schedule-runs.index'), form, { preserveState: true, preserveScroll: true });
  };

  const postAction = (action: 'archive' | 'restore', id: number): void => {
    router.post(route(`editor.editorial-schedule-runs.${action}`, id));
  };

  return <EditorLayout><Head title="Editorial Runs" /><AdminPageHeader helpKey="editor.editorialscheduleruns.index" title="Editorial Runs" description="Manual AI run desk." />
    <Card className="mb-4"><CardContent className="pt-6"><form onSubmit={submit} className="grid gap-3 md:grid-cols-4">
      <input className="rounded border px-3 py-2 text-sm" placeholder={t('Search')} value={form.search ?? ''} onChange={(e) => setForm((p:any) => ({ ...p, search: e.target.value }))} />
      <select className="rounded border px-3 py-2 text-sm" value={form.status ?? ''} onChange={(e) => setForm((p:any) => ({ ...p, status: e.target.value }))}><option value="">{t('All statuses')}</option>{statuses.map((s:string) => <option key={s} value={s}>{s}</option>)}</select>
      <select className="rounded border px-3 py-2 text-sm" value={form.schedule_id ?? ''} onChange={(e) => setForm((p:any) => ({ ...p, schedule_id: e.target.value }))}><option value="">All schedules</option>{schedules.map((s:any) => <option key={s.id} value={s.id}>{s.name}</option>)}</select>
      <div className="flex items-center gap-3"><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={Boolean(form.show_archived)} onChange={(e) => setForm((p:any) => ({ ...p, show_archived: e.target.checked }))} /> {t('Show archived')}</label><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={Boolean(form.only_archived)} onChange={(e) => setForm((p:any) => ({ ...p, only_archived: e.target.checked, show_archived: e.target.checked ? true : Boolean(p.show_archived) }))} /> {t('Only archived')}</label></div>
      <div className="md:col-span-4 flex gap-2"><Button type="submit">{t('Apply filters')}</Button><Button type="button" variant="outline" onClick={() => router.get(route('editor.editorial-schedule-runs.index'))}>{t('Clear filters')}</Button></div>
    </form></CardContent></Card>
    <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[800px] text-sm"><thead><tr className="border-b"><th className="px-2 pb-3 text-left">Run</th><th className="px-2 pb-3 text-left">Schedule</th><th className="px-2 pb-3 text-left">Scheduled for</th><th className="px-2 pb-3 text-left">Status</th><th className="px-2 pb-3 text-right">Actions</th></tr></thead><tbody>{runs.data.length ? runs.data.map((item:any)=><tr key={item.id} className="border-b"><td className="px-2 py-3">{item.schedule?.name || `Run ${item.id}`}</td><td className="px-2 py-3">{item.schedule?.name || '-'}</td><td className="px-2 py-3">{formatDateTime(item.scheduled_for)}</td><td className="px-2 py-3">{item.status}</td><td className="px-2 py-3 text-right"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-schedule-runs.show', item.id)}>Open</Link></Button>{item.status !== 'archived' ? <Button size="sm" variant="outline" onClick={() => postAction('archive', item.id)}>{t('Archive')}</Button> : <Button size="sm" variant="outline" onClick={() => postAction('restore', item.id)}>{t('Restore')}</Button>}</div></td></tr>) : <tr><td colSpan={5} className="px-2 py-6 text-center">{t('No records found')}</td></tr>}</tbody></table><Pagination links={runs.links} /></CardContent></Card></EditorLayout>;
}
