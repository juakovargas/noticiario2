import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function Form({ bulletinType, locations = [], categories = [], languages = [], promptProfiles = [], editionTypes = [] }: any): JSX.Element {
    const isEdit = !!bulletinType;
    const { t } = useTranslations();
    const form = useForm({
        name: bulletinType?.name ?? '', slug: bulletinType?.slug ?? '', description: bulletinType?.description ?? '', location_id: bulletinType?.location_id?.toString() ?? '',
        news_category_id: bulletinType?.news_category_id?.toString() ?? '', language_id: bulletinType?.language_id?.toString() ?? '', default_prompt_profile_id: bulletinType?.default_prompt_profile_id?.toString() ?? '',
        edition_type: bulletinType?.edition_type ?? '', target_duration_seconds: bulletinType?.target_duration_seconds?.toString() ?? '',
        default_schedule_time: bulletinType?.default_schedule_time?.slice(0, 5) ?? '', default_timezone: bulletinType?.default_timezone ?? '', is_active: bulletinType?.is_active ?? true, sort_order: bulletinType?.sort_order ?? 0,
    });

    const submit = (e: FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        if (isEdit) form.put(route('editor.bulletin-types.update', bulletinType.id));
        else form.post(route('editor.bulletin-types.store'));
    };

    return <EditorLayout><Head title={isEdit ? t('Edit Bulletin Type') : t('Create Bulletin Type')} /><AdminPageHeader title={isEdit ? t('Edit Bulletin Type') : t('Create Bulletin Type')} description={t('Noticiario type')} />
        <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Name')}</Label><Input value={form.data.name} onChange={(e)=>form.setData('name', e.target.value)} /></div><div><Label>{t('Slug')}</Label><Input value={form.data.slug} onChange={(e)=>form.setData('slug', e.target.value)} /></div></div>
            <div><Label>{t('Description')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={form.data.description} onChange={(e)=>form.setData('description', e.target.value)} /></div>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Location')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.location_id} onChange={(e)=>form.setData('location_id', e.target.value)}><option value="">{t('Any')}</option>{locations.map((x:any)=><option key={x.id} value={x.id}>{x.name}</option>)}</select></div><div><Label>{t('Category')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.news_category_id} onChange={(e)=>form.setData('news_category_id', e.target.value)}><option value="">{t('Any')}</option>{categories.map((x:any)=><option key={x.id} value={x.id}>{x.name}</option>)}</select></div></div>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Language')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.language_id} onChange={(e)=>form.setData('language_id', e.target.value)}><option value="">{t('Any')}</option>{languages.map((x:any)=><option key={x.id} value={x.id}>{x.name} ({x.code})</option>)}</select></div><div><Label>{t('Default prompt profile')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.default_prompt_profile_id} onChange={(e)=>form.setData('default_prompt_profile_id', e.target.value)}><option value="">{t('Any')}</option>{promptProfiles.map((x:any)=><option key={x.id} value={x.id}>{x.name}</option>)}</select></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Edition type')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.edition_type} onChange={(e)=>form.setData('edition_type', e.target.value)}><option value="">{t('Any')}</option>{editionTypes.map((x:string)=><option key={x} value={x}>{x}</option>)}</select></div><div><Label>{t('Target duration')}</Label><Input type="number" value={form.data.target_duration_seconds} onChange={(e)=>form.setData('target_duration_seconds', e.target.value)} /></div><div><Label>{t('Timezone')}</Label><Input value={form.data.default_timezone} onChange={(e)=>form.setData('default_timezone', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Time')}</Label><Input type="time" value={form.data.default_schedule_time} onChange={(e)=>form.setData('default_schedule_time', e.target.value)} /></div><div><Label>{t('Sort order')}</Label><Input type="number" value={form.data.sort_order} onChange={(e)=>form.setData('sort_order', Number(e.target.value))} /></div><label className="flex items-center gap-2 pt-7"><input type="checkbox" checked={form.data.is_active} onChange={(e)=>form.setData('is_active', e.target.checked)} />{t('Active')}</label></div>
            <div className="flex gap-2"><Button type="submit" disabled={form.processing}>{t('Save')}</Button><Button asChild variant="secondary"><Link href={route('editor.bulletin-types.index')}>{t('Cancel')}</Link></Button>{bulletinType && <Button type="button" variant="outline" onClick={()=>router.post(route('editor.bulletin-types.prompt-runs.store', bulletinType.id))}>{t('Create Prompt Run')}</Button>}</div>
        </form></CardContent></Card>
    </EditorLayout>;
}
