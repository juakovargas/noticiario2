import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
interface Req { id:number; title:string; status:string; ai_provider?:{name:string}|null; ai_prompt_template?:{name:string}|null; }
export default function Index({ requests, filters, statuses, locations, categories, languages }: any): JSX.Element {
    const { t } = useTranslations();
    const [form, setForm] = useState(filters ?? {});
    const destroy = (id:number): void => { if (window.confirm('Delete this request?')) router.delete(route('editor.editorial-requests.destroy', id)); };
    const submit = (e: FormEvent): void => { e.preventDefault(); router.get(route('editor.editorial-requests.index'), form, { preserveState: true, preserveScroll: true }); };
    const postAction = (action: 'archive' | 'restore', id: number): void => router.post(route(`editor.editorial-requests.${action}`, id));

    return <EditorLayout><Head title="Editorial Requests" /><AdminPageHeader title="Editorial Requests" description="AI-assisted editorial planning requests." actionLabel="Create Editorial Request" actionHref={route('editor.editorial-requests.create')} />
      <Card className="mb-4"><CardContent className="pt-6"><form className="grid gap-3 md:grid-cols-4" onSubmit={submit}>
        <input className="rounded border px-3 py-2 text-sm" placeholder={t('Search')} value={form.search ?? ''} onChange={(e)=>setForm((p:any)=>({...p,search:e.target.value}))} />
        <select className="rounded border px-3 py-2 text-sm" value={form.status ?? ''} onChange={(e)=>setForm((p:any)=>({...p,status:e.target.value}))}><option value="">{t('All statuses')}</option>{statuses.map((s:string)=><option key={s} value={s}>{s}</option>)}</select>
        <select className="rounded border px-3 py-2 text-sm" value={form.location_id ?? ''} onChange={(e)=>setForm((p:any)=>({...p,location_id:e.target.value}))}><option value="">{t('All locations')}</option>{locations.map((s:any)=><option key={s.id} value={s.id}>{s.name}</option>)}</select>
        <select className="rounded border px-3 py-2 text-sm" value={form.news_category_id ?? ''} onChange={(e)=>setForm((p:any)=>({...p,news_category_id:e.target.value}))}><option value="">{t('All categories')}</option>{categories.map((s:any)=><option key={s.id} value={s.id}>{s.name}</option>)}</select>
        <select className="rounded border px-3 py-2 text-sm" value={form.language_id ?? ''} onChange={(e)=>setForm((p:any)=>({...p,language_id:e.target.value}))}><option value="">{t('All languages')}</option>{languages.map((s:any)=><option key={s.id} value={s.id}>{s.name}</option>)}</select>
        <div className="flex items-center gap-3 text-sm"><label className="flex items-center gap-2"><input type="checkbox" checked={Boolean(form.show_archived)} onChange={(e)=>setForm((p:any)=>({...p,show_archived:e.target.checked}))} />{t('Show archived')}</label><label className="flex items-center gap-2"><input type="checkbox" checked={Boolean(form.only_archived)} onChange={(e)=>setForm((p:any)=>({...p,only_archived:e.target.checked,show_archived:e.target.checked ? true : Boolean(p.show_archived)}))} />{t('Only archived')}</label></div>
        <div className="md:col-span-4 flex gap-2"><Button type="submit">{t('Apply filters')}</Button><Button type="button" variant="outline" onClick={() => router.get(route('editor.editorial-requests.index'))}>{t('Clear filters')}</Button></div>
      </form></CardContent></Card>
      <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[800px] text-sm"><thead><tr className="border-b"><th className="px-2 pb-3 text-left">Title</th><th className="px-2 pb-3 text-left">Status</th><th className="px-2 pb-3 text-left">Provider</th><th className="px-2 pb-3 text-left">Template</th><th className="px-2 pb-3 text-right">Actions</th></tr></thead><tbody>{requests.data.length ? requests.data.map((item:Req)=><tr key={item.id} className="border-b"><td className="px-2 py-3">{item.title}</td><td className="px-2 py-3">{item.status}</td><td className="px-2 py-3">{item.ai_provider?.name || '-'}</td><td className="px-2 py-3">{item.ai_prompt_template?.name || '-'}</td><td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-requests.show', item.id)}>View</Link></Button><Button asChild size="sm" variant="secondary"><Link href={route('editor.editorial-requests.edit', item.id)}>Edit</Link></Button>{item.status !== 'archived' ? <Button size="sm" variant="outline" onClick={()=>postAction('archive', item.id)}>{t('Archive')}</Button> : <Button size="sm" variant="outline" onClick={()=>postAction('restore', item.id)}>{t('Restore')}</Button>}<Button size="sm" variant="destructive" onClick={()=>destroy(item.id)}>Delete</Button></div></td></tr>) : <tr><td colSpan={5} className="px-2 py-6 text-center">{t('No records found')}</td></tr>}</tbody></table><Pagination links={requests.links} /></CardContent></Card></EditorLayout>;
}
