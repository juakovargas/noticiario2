import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { countryCodeToFlagEmoji } from '@/lib/flags';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Edition { id:number; title:string; edition_type:string; location:{name:string; country_code:string|null}|null; scheduled_for:string|null; language:string|null; status:string; target_duration_seconds:number|null; description:string|null; }
interface NewsItemPivot { id:number; title:string; source:string|null; category:string|null; location:{name:string; country_code:string|null}|null; status:string; summary:string|null; sort_order:number; editorial_angle:string|null; included_in_script:boolean; }
interface Props { edition:Edition; newsItems:NewsItemPivot[]; availableNewsItems:Array<{id:number; title:string}>; scripts:Array<{id:number; title:string; status:string; language:string|null; estimated_duration_seconds:number|null}>; }

export default function Show({ edition, newsItems, availableNewsItems, scripts }: Props): JSX.Element {
    const [editingItemId, setEditingItemId] = useState<number | null>(null);
    const { t } = useTranslations();

    const addForm = useForm({ news_item_id: availableNewsItems[0]?.id?.toString() ?? '', sort_order: '0', editorial_angle: '', included_in_script: true });
    const editForm = useForm({ sort_order: '0', editorial_angle: '', included_in_script: true });

    const submitAdd = (e: FormEvent<HTMLFormElement>): void => { e.preventDefault(); addForm.post(route('editor.editions.news-items.store', edition.id), { preserveScroll: true }); };
    const submitEdit = (e: FormEvent<HTMLFormElement>, newsItemId: number): void => { e.preventDefault(); editForm.put(route('editor.editions.news-items.update', [edition.id, newsItemId]), { preserveScroll: true, onSuccess: () => setEditingItemId(null) }); };

    return <EditorLayout><Head title={edition.title} /><AdminPageHeader title={edition.title} description="Edition detail." />
      <div className="space-y-6">
        <Card><CardContent className="space-y-3 pt-6 text-sm">
          <p><strong>{t('Type')}:</strong> {edition.edition_type}</p>
          <p><strong>{t('Location')}:</strong> {countryCodeToFlagEmoji(edition.location?.country_code)} {edition.location?.name || '-'}</p>
          <p><strong>{t('Date')}:</strong> {edition.scheduled_for || '-'}</p>
          <p><strong>{t('Language')}:</strong> {edition.language || '-'}</p>
          <p><strong>{t('Status')}:</strong> {edition.status}</p>
          <p><strong>{t('Estimated Duration')}:</strong> {edition.target_duration_seconds || '-'} seconds</p>
          <p><strong>{t('Description')}:</strong> {edition.description || '-'}</p>
          <div className="flex flex-wrap gap-2">
            <Button asChild><Link href={route('editor.editions.script-builder', edition.id)}>{t('Open Script Builder')}</Link></Button>
            <Button asChild variant="outline"><Link href={`${route('editor.scripts.create')}?edition_id=${edition.id}`}>{t('Create Script')}</Link></Button>
            <Button asChild variant="secondary"><Link href={route('editor.editions.index')}>{t('Back')}</Link></Button>
          </div>
        </CardContent></Card>

        <Card><CardContent className="pt-6"><h2 className="mb-4 text-lg font-semibold">Add News Item to Edition</h2>
          {availableNewsItems.length === 0 ? <p className="text-sm text-slate-500">All available news items are already selected for this edition.</p> :
          <form onSubmit={submitAdd} className="grid gap-4 md:grid-cols-2"><div className="md:col-span-2"><Label>News Item</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={addForm.data.news_item_id} onChange={(e) => addForm.setData('news_item_id', e.target.value)}>{availableNewsItems.map((item) => <option key={item.id} value={item.id}>{item.title}</option>)}</select></div>
          <div><Label>Sort order</Label><Input type="number" min={0} value={addForm.data.sort_order} onChange={(e)=>addForm.setData('sort_order', e.target.value)} /></div>
          <div className="flex items-end"><label className="inline-flex items-center gap-2 text-sm"><input type="checkbox" checked={addForm.data.included_in_script} onChange={(e)=>addForm.setData('included_in_script', e.target.checked)} />{t('Included in Script')}</label></div>
          <div className="md:col-span-2"><Label>{t('Editorial Angle')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={2} value={addForm.data.editorial_angle} onChange={(e)=>addForm.setData('editorial_angle', e.target.value)} /></div>
          <div className="md:col-span-2"><Button type="submit" disabled={addForm.processing}>Add</Button></div></form>}
        </CardContent></Card>

        <Card><CardContent className="pt-6"><h2 className="mb-4 text-lg font-semibold">{t('Selected News Items')}</h2>
          {newsItems.length === 0 ? <p className="text-slate-500">No selected news items yet.</p> : <div className="space-y-4">{newsItems.map((item) => <div key={item.id} className="rounded-lg border border-slate-200 p-4">{editingItemId===item.id ? <form onSubmit={(e)=>submitEdit(e, item.id)} className="space-y-3"><p className="font-semibold">{item.title}</p><div className="grid gap-3 md:grid-cols-2"><div><Label>Sort order</Label><Input type="number" min={0} value={editForm.data.sort_order} onChange={(e)=>editForm.setData('sort_order', e.target.value)} /></div><label className="inline-flex items-center gap-2 pt-7 text-sm"><input type="checkbox" checked={editForm.data.included_in_script} onChange={(e)=>editForm.setData('included_in_script', e.target.checked)} />{t('Included in Script')}</label></div><div><Label>{t('Editorial Angle')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={2} value={editForm.data.editorial_angle} onChange={(e)=>editForm.setData('editorial_angle', e.target.value)} /></div><div className="flex gap-2"><Button type="submit" size="sm">{t('Save')}</Button><Button type="button" variant="secondary" size="sm" onClick={()=>setEditingItemId(null)}>{t('Cancel')}</Button></div></form> : <>
          <p className="font-semibold">#{item.sort_order} {item.title}</p><p className="text-sm text-slate-600">{t('Source')}: {item.source || '-'} | {t('Category')}: {item.category || '-'} | {t('Location')}: {countryCodeToFlagEmoji(item.location?.country_code)} {item.location?.name || '-'} | {t('Status')}: {item.status}</p><p className="text-sm"><strong>{t('Description')}:</strong> {item.summary || '-'}</p><p className="text-sm"><strong>{t('Editorial Angle')}:</strong> {item.editorial_angle || '-'}</p><p className="text-sm"><strong>{t('Included in Script')}:</strong> {item.included_in_script ? t('Yes') : t('No')}</p><div className="mt-2 flex gap-2"><Button type="button" variant="secondary" size="sm" onClick={()=>{setEditingItemId(item.id);editForm.setData({sort_order:String(item.sort_order),editorial_angle:item.editorial_angle ?? '',included_in_script:item.included_in_script});}}>{t('Edit')}</Button><Button type="button" variant="destructive" size="sm" onClick={()=>{ if(confirm('Remove this news item from the edition?')) { editForm.delete(route('editor.editions.news-items.destroy', [edition.id, item.id]), { preserveScroll: true }); } }}>{t('Delete')}</Button></div></>}</div>)}</div>}
        </CardContent></Card>

        <Card><CardContent className="pt-6"><h2 className="mb-4 text-lg font-semibold">{t('Existing Scripts')}</h2>{scripts.length===0 ? <p className="text-sm text-slate-500">No scripts created for this edition yet.</p> : <ul className="space-y-2 text-sm">{scripts.map((script)=><li key={script.id} className="flex items-center justify-between rounded-md border border-slate-200 p-3"><div><p className="font-medium">{script.title}</p><p className="text-slate-500">{script.status} · {script.language || '-'} · {script.estimated_duration_seconds || '-'} seconds</p></div><div className="flex gap-2"><Link href={route('editor.scripts.show', script.id)} className="text-cyan-700 underline">{t('View')}</Link><Link href={route('editor.scripts.edit', script.id)} className="text-cyan-700 underline">{t('Edit')}</Link></div></li>)}</ul>}</CardContent></Card>
      </div>
    </EditorLayout>;
}
