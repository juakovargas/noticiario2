import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import { Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Option { id:number; name:string; code?:string }
interface Schedule { id:number; name:string; slug:string|null; description:string|null; location_id:number|null; news_category_id:number|null; language_id:number|null; bulletin_type_id:number|null; edition_type:string; frequency_type:string; run_frequency?:string|null; scheduled_time:string|null; run_time?:string|null; scheduled_date:string|null; weekdays:string[]|null; run_days?:string[]|null; timezone:string|null; next_run_at?:string|null; target_duration_seconds:number|null; tone:string|null; manual_ai_mode:boolean; is_active:boolean; auto_create_prompt_run?:boolean; auto_generate_prompt?:boolean; auto_generate_ai_response?:boolean; editorial_instructions:string|null; output_instructions:string|null; }

export default function Form({ schedule, locations, categories, languages, bulletinTypes, frequencyTypes, editionTypes }: { schedule?:Schedule; locations:Option[]; categories:Option[]; languages:Option[]; bulletinTypes:Option[]; frequencyTypes:string[]; editionTypes:string[] }): JSX.Element {
  const isEdit = !!schedule; const { t } = useTranslations();
  const form = useForm({
    name: schedule?.name ?? '', slug: schedule?.slug ?? '', description: schedule?.description ?? '',
    location_id: schedule?.location_id ? String(schedule.location_id) : '', news_category_id: schedule?.news_category_id ? String(schedule.news_category_id) : '', language_id: schedule?.language_id ? String(schedule.language_id) : '', bulletin_type_id: schedule?.bulletin_type_id ? String(schedule.bulletin_type_id) : '',
    edition_type: schedule?.edition_type ?? 'morning', frequency_type: schedule?.frequency_type ?? 'daily', run_frequency: schedule?.run_frequency ?? schedule?.frequency_type ?? 'daily',
    scheduled_time: schedule?.scheduled_time ? schedule.scheduled_time.slice(0,5) : '', run_time: schedule?.run_time ? schedule.run_time.slice(0,5) : '',
    scheduled_date: schedule?.scheduled_date ? schedule.scheduled_date.slice(0,10) : '', weekdays: schedule?.weekdays ?? [], run_days: schedule?.run_days ?? schedule?.weekdays ?? [], timezone: schedule?.timezone ?? '', next_run_at: schedule?.next_run_at ? schedule.next_run_at.slice(0,16) : '',
    target_duration_seconds: schedule?.target_duration_seconds ? String(schedule.target_duration_seconds) : '', tone: schedule?.tone ?? '',
    manual_ai_mode: schedule?.manual_ai_mode ?? true, is_active: schedule?.is_active ?? true, auto_create_prompt_run: schedule?.auto_create_prompt_run ?? true, auto_generate_prompt: schedule?.auto_generate_prompt ?? true, auto_generate_ai_response: schedule?.auto_generate_ai_response ?? false,
    editorial_instructions: schedule?.editorial_instructions ?? '', output_instructions: schedule?.output_instructions ?? '',
  });
  const submit = (e:FormEvent)=>{e.preventDefault(); isEdit ? form.put(route('editor.editorial-schedules.update', schedule!.id)) : form.post(route('editor.editorial-schedules.store'));};

  return <form onSubmit={submit} className="space-y-4">
    <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Name')}</Label><Input value={form.data.name} onChange={(e)=>form.setData('name', e.target.value)} /></div><div><Label>{t('Slug')}</Label><Input value={form.data.slug} onChange={(e)=>form.setData('slug', e.target.value)} /></div></div>
    <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Location')}</Label><select className="w-full rounded-md border px-3 py-2" value={form.data.location_id} onChange={(e)=>form.setData('location_id', e.target.value)}><option value="">-</option>{locations.map(o=><option key={o.id} value={o.id}>{o.name}</option>)}</select></div><div><Label>{t('Category')}</Label><select className="w-full rounded-md border px-3 py-2" value={form.data.news_category_id} onChange={(e)=>form.setData('news_category_id', e.target.value)}><option value="">-</option>{categories.map(o=><option key={o.id} value={o.id}>{o.name}</option>)}</select></div><div><Label>{t('Language')}</Label><select className="w-full rounded-md border px-3 py-2" value={form.data.language_id} onChange={(e)=>form.setData('language_id', e.target.value)}><option value="">-</option>{languages.map(o=><option key={o.id} value={o.id}>{o.name}</option>)}</select></div></div>
    <div><Label>{t('Bulletin Types')}</Label><select className="w-full rounded-md border px-3 py-2" value={form.data.bulletin_type_id} onChange={(e)=>form.setData('bulletin_type_id', e.target.value)}><option value="">-</option>{bulletinTypes.map(o=><option key={o.id} value={o.id}>{o.name}</option>)}</select></div>
    <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Run frequency')}</Label><select className="w-full rounded-md border px-3 py-2" value={form.data.run_frequency} onChange={(e)=>{form.setData('run_frequency', e.target.value);form.setData('frequency_type', e.target.value);}}>{frequencyTypes.map(item=><option key={item} value={item}>{t(item.charAt(0).toUpperCase()+item.slice(1))}</option>)}</select></div><div><Label>{t('Run time')}</Label><Input type="time" value={form.data.run_time || form.data.scheduled_time} onChange={(e)=>{form.setData('run_time', e.target.value);form.setData('scheduled_time', e.target.value);}} /></div><div><Label>{t('Next scheduled run')}</Label><Input type="datetime-local" value={form.data.next_run_at} onChange={(e)=>form.setData('next_run_at', e.target.value)} /></div></div>
    <div className="flex flex-wrap gap-4"><label><input type="checkbox" checked={form.data.is_active} onChange={(e)=>form.setData('is_active', e.target.checked)} /> {t('Active')}</label><label><input type="checkbox" checked={form.data.auto_create_prompt_run} onChange={(e)=>form.setData('auto_create_prompt_run', e.target.checked)} /> {t('Auto create prompt run')}</label><label><input type="checkbox" checked={form.data.auto_generate_prompt} onChange={(e)=>form.setData('auto_generate_prompt', e.target.checked)} /> {t('Auto generate prompt')}</label><label><input type="checkbox" checked={form.data.auto_generate_ai_response} disabled onChange={()=>null} /> {t('Auto generate AI response')}</label></div>
    <p className="text-xs text-amber-600">{t('Automatic AI generation is disabled by default')}</p>
    <div className="flex gap-2"><Button disabled={form.processing} type="submit">{t('Save')}</Button><Button asChild variant="secondary"><Link href={route('editor.editorial-schedules.index')}>{t('Cancel')}</Link></Button></div>
  </form>;
}
