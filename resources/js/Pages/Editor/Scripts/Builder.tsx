import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { countryCodeToFlagEmoji } from '@/lib/flags';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Edition { id:number; title:string; edition_type:string; location:{name:string; country_code:string|null}|null; scheduled_for:string|null; language:string|null; status:string; }
interface SelectedNewsItem { id:number; sort_order:number; title:string; source:string|null; category:string|null; location:{name:string; country_code:string|null}|null; summary:string|null; editorial_angle:string|null; included_in_script:boolean; }
interface ExistingScript { id:number; title:string; status:string; language:string|null; estimated_duration_seconds:number|null; }
interface TemplateItem { id:number; name:string; language:{code:string; name:string; native_name:string|null; flag_emoji:string|null}|null; location:{name:string}|null; }
interface Prefill { title:string; status:string; language:string|null; intro:string; body:string; outro:string; estimated_duration_seconds:number|null; selected_editorial_template_id:number|null; }
interface Props { edition:Edition; selectedNewsItems:SelectedNewsItem[]; existingScripts:ExistingScript[]; prefill:Prefill; templates:TemplateItem[]; }

export default function Builder({ edition, selectedNewsItems, existingScripts, prefill, templates }: Props): JSX.Element {
    const { t } = useTranslations();
    const form = useForm({ title: prefill.title, status: prefill.status, language: prefill.language ?? '', editorial_template_id: prefill.selected_editorial_template_id ? String(prefill.selected_editorial_template_id) : '', intro: prefill.intro, body: prefill.body, outro: prefill.outro, estimated_duration_seconds: prefill.estimated_duration_seconds ? String(prefill.estimated_duration_seconds) : '' });
    const submit = (event: FormEvent<HTMLFormElement>): void => { event.preventDefault(); form.post(route('editor.editions.script-builder.store', edition.id)); };

    return <EditorLayout><Head title={t('Script Builder')} /><AdminPageHeader title={t('Script Builder')} description={t('Manual script builder')} />
        <div className="space-y-6">
            <Card><CardContent className="grid gap-2 pt-6 text-sm md:grid-cols-2"><p><strong>{t('Title')}:</strong> {edition.title}</p><p><strong>{t('Type')}:</strong> {edition.edition_type}</p><p><strong>{t('Location')}:</strong> {countryCodeToFlagEmoji(edition.location?.country_code)} {edition.location?.name ?? '-'}</p><p><strong>{t('Date')}:</strong> {edition.scheduled_for ?? '-'}</p><p><strong>{t('Language')}:</strong> {edition.language ?? '-'}</p><p><strong>{t('Status')}:</strong> {edition.status}</p></CardContent></Card>
            <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4"><div><Label>{t('Title')}</Label><Input value={form.data.title} onChange={(e)=>form.setData('title', e.target.value)} /></div><div className="grid gap-4 md:grid-cols-4"><div><Label>{t('Status')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.status} onChange={(e)=>form.setData('status', e.target.value)}><option value="draft">{t('Draft')}</option><option value="review">{t('Review')}</option></select></div><div><Label>{t('Editorial language')}</Label><Input value={form.data.language} onChange={(e)=>form.setData('language', e.target.value)} /></div><div><Label>{t('Template')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.editorial_template_id} onChange={(e)=>form.setData('editorial_template_id', e.target.value)}><option value="">{t('No template')}</option>{templates.map((template)=><option key={template.id} value={template.id}>{template.name}{template.language ? ` · ${template.language.flag_emoji ?? ''} ${template.language.code}` : ''}{template.location ? ` · ${template.location.name}` : ''}</option>)}</select></div><div><Label>{t('Estimated Duration')}</Label><Input type="number" min={1} value={form.data.estimated_duration_seconds} onChange={(e)=>form.setData('estimated_duration_seconds', e.target.value)} /></div></div><div><Label>{t('Intro')}</Label><textarea className="w-full rounded-md border border-slate-300 p-2" rows={3} value={form.data.intro} onChange={(e)=>form.setData('intro', e.target.value)} /></div><div><Label>{t('Body')}</Label><textarea className="w-full rounded-md border border-slate-300 p-2" rows={10} value={form.data.body} onChange={(e)=>form.setData('body', e.target.value)} /></div><div><Label>{t('Outro')}</Label><textarea className="w-full rounded-md border border-slate-300 p-2" rows={3} value={form.data.outro} onChange={(e)=>form.setData('outro', e.target.value)} /></div><div className="flex gap-2"><Button type="submit" disabled={form.processing}>{t('Save Draft Script')}</Button><Button asChild variant="secondary"><Link href={route('editor.editions.show', edition.id)}>{t('Back to Edition')}</Link></Button></div></form></CardContent></Card>
            <Card><CardContent className="pt-6"><h2 className="mb-4 text-lg font-semibold">{t('Selected News Items')}</h2>{selectedNewsItems.length ? selectedNewsItems.map((item) => <div key={item.id} className="mb-3 rounded-lg border p-3 text-sm"><p className="font-semibold">#{item.sort_order} {item.title}</p><p>{item.summary || '-'}</p></div>) : <p className="text-sm text-slate-500">No selected news items yet.</p>}</CardContent></Card>
            <Card><CardContent className="pt-6"><h2 className="mb-4 text-lg font-semibold">{t('Existing Scripts')}</h2>{existingScripts.length ? existingScripts.map((script) => <div key={script.id} className="mb-2 flex items-center justify-between rounded border p-3 text-sm"><div>{script.title} · {script.status}</div><div className="flex gap-2"><Link className="text-cyan-700 underline" href={route('editor.scripts.show', script.id)}>{t('View')}</Link></div></div>) : <p className="text-sm text-slate-500">No scripts created for this edition yet.</p>}</CardContent></Card>
        </div>
    </EditorLayout>;
}
