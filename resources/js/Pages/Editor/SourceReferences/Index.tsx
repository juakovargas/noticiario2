import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import UserIdentity from '@/Components/UserIdentity';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Item { id:number; title:string|null; source_name:string|null; source_url:string|null; source_domain:string|null; source_type:string; verification_status:string; trust_level:number|null; checked_by:{ id:number; name:string; email:string | null; avatar_url?:string|null; initials?:string|null }|null; checked_at:string|null; archived_at:string|null; script:{id:number;title:string}|null; news_item:{id:number;title:string}|null; bulletin_prompt_run:{id:number;title:string|null}|null; edition:{id:number;title:string}|null; script_review_item:{id:number;title:string|null}|null; }
interface Props { sourceReferences:{ data:Item[]; links:Array<{url:string|null;label:string;active:boolean}>}; filters: Record<string,string>; verificationStatuses:string[]; sourceTypes:string[]; checkedByUsers:Array<{id:number;name:string}>; }

const badgeClass: Record<string, string> = { pending:'bg-slate-100 text-slate-700', verified:'bg-emerald-100 text-emerald-700', weak:'bg-amber-100 text-amber-800', missing:'bg-orange-100 text-orange-800', broken:'bg-rose-100 text-rose-700', rejected:'bg-red-100 text-red-700', not_required:'bg-slate-200 text-slate-700' };

export default function Index({ sourceReferences, filters, verificationStatuses, sourceTypes, checkedByUsers }: Props): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const [form, setForm] = useState(filters);

    const submit = (e: FormEvent): void => { e.preventDefault(); router.get(route('editor.source-references.index'), form, { preserveState: true, preserveScroll: true }); };

    return <EditorLayout>
        <Head title={t('Source References')} />
        <AdminPageHeader helpKey="editor.sourcereferences.index" title={t('Source References')} description={t('Source verification')} />
        <Card className="mb-4"><CardContent className="pt-6"><form className="grid gap-3 md:grid-cols-6" onSubmit={submit}>
            <input className="rounded border px-3 py-2 text-sm" placeholder={t('Search')} value={form.search ?? ''} onChange={(e)=>setForm((prev)=>({...prev,search:e.target.value}))} />
            <select className="rounded border px-3 py-2 text-sm" value={form.verification_status ?? ''} onChange={(e)=>setForm((prev)=>({...prev,verification_status:e.target.value}))}><option value="">{t('Verification status')}</option>{verificationStatuses.map((item)=><option key={item} value={item}>{t(item === 'not_required' ? 'Not required' : item.charAt(0).toUpperCase()+item.slice(1))}</option>)}</select>
            <select className="rounded border px-3 py-2 text-sm" value={form.source_type ?? ''} onChange={(e)=>setForm((prev)=>({...prev,source_type:e.target.value}))}><option value="">{t('Source type')}</option>{sourceTypes.map((item)=><option key={item} value={item}>{t(`${item.charAt(0).toUpperCase()+item.slice(1)} source`)}</option>)}</select>
            <input className="rounded border px-3 py-2 text-sm" placeholder={t('Trust level')} value={form.trust_level ?? ''} onChange={(e)=>setForm((prev)=>({...prev,trust_level:e.target.value}))} />
            <select className="rounded border px-3 py-2 text-sm" value={form.checked_by ?? ''} onChange={(e)=>setForm((prev)=>({...prev,checked_by:e.target.value}))}><option value="">{t('Checked by')}</option>{checkedByUsers.map((user)=><option key={user.id} value={user.id}>{user.name}</option>)}</select>
            <input className="rounded border px-3 py-2 text-sm" placeholder={t('Source domain')} value={form.source_domain ?? ''} onChange={(e)=>setForm((prev)=>({...prev,source_domain:e.target.value}))} />
            <select className="rounded border px-3 py-2 text-sm" value={form.has_url ?? ''} onChange={(e)=>setForm((prev)=>({...prev,has_url:e.target.value}))}><option value="">{t('Has URL')}</option><option value="yes">{t('Has URL')}</option><option value="no">{t('Without URL')}</option></select>
            <label className="flex items-center gap-2 rounded border px-3 py-2 text-sm"><input type="checkbox" checked={form.show_archived === '1'} onChange={(e)=>setForm((prev)=>({...prev,show_archived:e.target.checked ? '1' : ''}))} /> {t('Show archived')}</label>
            <label className="flex items-center gap-2 rounded border px-3 py-2 text-sm"><input type="checkbox" checked={form.only_archived === '1'} onChange={(e)=>setForm((prev)=>({...prev,only_archived:e.target.checked ? '1' : '',show_archived:e.target.checked ? '1' : prev.show_archived}))} /> {t('Only archived')}</label>
            <div className="flex gap-2"><Button type="submit">{t('Apply filters')}</Button><Button type="button" variant="outline" onClick={()=>router.get(route('editor.source-references.index'))}>{t('Clear filters')}</Button></div>
        </form></CardContent></Card>

        <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[1200px] text-sm"><thead><tr className="border-b text-slate-500">
            <th className="px-2 pb-3 text-left">{t('Source Reference')}</th><th className="px-2 pb-3 text-left">{t('Source URL')}</th><th className="px-2 pb-3 text-left">{t('Source type')}</th><th className="px-2 pb-3 text-left">{t('Verification status')}</th><th className="px-2 pb-3 text-left">{t('Trust level')}</th><th className="px-2 pb-3 text-left">{t('Linked content')}</th><th className="px-2 pb-3 text-left">{t('Checked by')}</th><th className="px-2 pb-3 text-left">{t('Checked at')}</th><th className="px-2 pb-3 text-right">{t('Actions')}</th>
        </tr></thead><tbody>{sourceReferences.data.length ? sourceReferences.data.map((item)=><tr key={item.id} className="border-b border-slate-100"><td className="px-2 py-3"><p className="font-medium">{item.title || item.source_name || '-'}</p><p className="text-xs text-slate-500">{item.source_name || '-'}</p></td><td className="px-2 py-3">{item.source_url ? <a href={item.source_url} target="_blank" rel="noreferrer" className="text-cyan-700 underline">{t('Open source')}</a> : t('No URL provided')}<p className="text-xs text-slate-500">{item.source_domain || '-'}</p></td><td className="px-2 py-3">{t(`${item.source_type.charAt(0).toUpperCase()+item.source_type.slice(1)} source`)}</td><td className="px-2 py-3"><Badge className={badgeClass[item.verification_status] ?? badgeClass.pending}>{item.verification_status === 'not_required' ? t('Not required') : t(item.verification_status.charAt(0).toUpperCase()+item.verification_status.slice(1))}</Badge></td><td className="px-2 py-3">{item.trust_level ?? '-'}</td><td className="px-2 py-3 text-xs">{item.bulletin_prompt_run ? `${t('Linked prompt run')}: ${item.bulletin_prompt_run.title || t('Prompt Run')}` : '-'}<br />{item.script ? `${t('Linked script')}: ${item.script.title}` : ''}<br />{item.news_item ? `${t('Linked news item')}: ${item.news_item.title}` : ''}<br />{item.edition ? `${t('Linked edition')}: ${item.edition.title}` : ''}</td><td className="px-2 py-3">{item.checked_by ? <UserIdentity user={item.checked_by} subtitle={item.checked_by.email} avatarSize="xs" /> : '-'}</td><td className="px-2 py-3">{formatDateTime(item.checked_at)}</td><td className="px-2 py-3 text-right"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="outline"><Link href={route('editor.source-references.show', item.id)}>{t('Open')}</Link></Button>{(item as any).archived_at ? <Button size="sm" variant="outline" onClick={()=>router.post(route('editor.source-references.restore', item.id))}>{t('Restore')}</Button> : <Button size="sm" variant="outline" onClick={()=>router.post(route('editor.source-references.archive', item.id))}>{t('Archive')}</Button>}</div></td></tr>) : <tr><td colSpan={9} className="px-2 py-6 text-center text-slate-500">{t('No source references found')}</td></tr>}</tbody></table><Pagination links={sourceReferences.links} /></CardContent></Card>
    </EditorLayout>;
}
