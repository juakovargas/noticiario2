import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import { toDateTimeLocalInputValue } from '@/lib/dates';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function Form({ bulletinType, locations = [], categories = [], languages = [], promptProfiles = [], editionTypes = [], coverageModes = [], promptLanguages = [], outputModes = [] }: any): JSX.Element {
    const isEdit = !!bulletinType;
    const { t } = useTranslations();

    const form = useForm({
        name: bulletinType?.name ?? '',
        slug: bulletinType?.slug ?? '',
        description: bulletinType?.description ?? '',
        location_id: bulletinType?.location_id?.toString() ?? '',
        news_category_id: bulletinType?.news_category_id?.toString() ?? '',
        language_id: bulletinType?.language_id?.toString() ?? '',
        default_prompt_profile_id: bulletinType?.default_prompt_profile_id?.toString() ?? '',
        edition_type: bulletinType?.edition_type ?? '',
        target_duration_seconds: bulletinType?.target_duration_seconds?.toString() ?? '',
        default_schedule_time: bulletinType?.default_schedule_time?.slice(0, 5) ?? '',
        default_timezone: bulletinType?.default_timezone ?? '',
        default_run_frequency: bulletinType?.default_run_frequency ?? 'daily',
        default_run_time: bulletinType?.default_run_time?.slice(0, 5) ?? bulletinType?.default_schedule_time?.slice(0, 5) ?? '',
        default_run_days: bulletinType?.default_run_days ?? [],
        default_schedule_is_active: bulletinType?.default_schedule_is_active ?? false,
        default_auto_run_pipeline: bulletinType?.default_auto_run_pipeline ?? false,
        default_auto_generate_ai_response: bulletinType?.default_auto_generate_ai_response ?? false,
        default_auto_create_script: bulletinType?.default_auto_create_script ?? true,
        default_auto_generate_metadata: bulletinType?.default_auto_generate_metadata ?? true,
        default_auto_extract_sources: bulletinType?.default_auto_extract_sources ?? true,
        coverage_mode: bulletinType?.coverage_mode ?? 'previous_period',
        coverage_starts_offset_minutes: bulletinType?.coverage_starts_offset_minutes?.toString() ?? '',
        coverage_ends_offset_minutes: bulletinType?.coverage_ends_offset_minutes?.toString() ?? '',
        coverage_description: bulletinType?.coverage_description ?? '',
        include_future_agenda: bulletinType?.include_future_agenda ?? false,
        include_historical_context: bulletinType?.include_historical_context ?? false,
        min_news_items: bulletinType?.min_news_items?.toString() ?? '',
        max_news_items: bulletinType?.max_news_items?.toString() ?? '',
        prompt_language: bulletinType?.prompt_language ?? 'es',
        output_mode: bulletinType?.output_mode ?? 'structured_script',
        is_active: bulletinType?.is_active ?? true,
        sort_order: bulletinType?.sort_order ?? 0,
    });

    const runForm = useForm({ scheduled_for: toDateTimeLocalInputValue(new Date()) });

    const submit = (e: FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        if (isEdit) form.put(route('editor.bulletin-types.update', bulletinType.id));
        else form.post(route('editor.bulletin-types.store'));
    };

    const createRun = (): void => {
        router.post(route('editor.bulletin-types.prompt-runs.store', bulletinType.id), {
            scheduled_for: runForm.data.scheduled_for ? new Date(runForm.data.scheduled_for).toISOString() : null,
        });
    };

    return <EditorLayout>
        <Head title={isEdit ? t('Edit Bulletin Type') : t('Create Bulletin Type')} />
        <AdminPageHeader title={isEdit ? t('Edit Bulletin Type') : t('Create Bulletin Type')} description={t('Noticiario type')} />
        <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-5">
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Name')}</Label><Input value={form.data.name} onChange={(e)=>form.setData('name', e.target.value)} /></div><div><Label>{t('Slug')}</Label><Input value={form.data.slug} onChange={(e)=>form.setData('slug', e.target.value)} /></div></div>
            <div><Label>{t('Description')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={form.data.description} onChange={(e)=>form.setData('description', e.target.value)} /></div>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Location')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.location_id} onChange={(e)=>form.setData('location_id', e.target.value)}><option value="">{t('Any')}</option>{locations.map((x:any)=><option key={x.id} value={x.id}>{x.name}</option>)}</select></div><div><Label>{t('Category')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.news_category_id} onChange={(e)=>form.setData('news_category_id', e.target.value)}><option value="">{t('Any')}</option>{categories.map((x:any)=><option key={x.id} value={x.id}>{x.name}</option>)}</select></div></div>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Language')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.language_id} onChange={(e)=>form.setData('language_id', e.target.value)}><option value="">{t('Any')}</option>{languages.map((x:any)=><option key={x.id} value={x.id}>{x.name} ({x.code})</option>)}</select></div><div><Label>{t('Default prompt profile')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.default_prompt_profile_id} onChange={(e)=>form.setData('default_prompt_profile_id', e.target.value)}><option value="">{t('Any')}</option>{promptProfiles.map((x:any)=><option key={x.id} value={x.id}>{x.name}</option>)}</select></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Edition type')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.edition_type} onChange={(e)=>form.setData('edition_type', e.target.value)}><option value="">{t('Any')}</option>{editionTypes.map((x:string)=><option key={x} value={x}>{x}</option>)}</select></div><div><Label>{t('Target duration')}</Label><Input type="number" value={form.data.target_duration_seconds} onChange={(e)=>form.setData('target_duration_seconds', e.target.value)} /></div><div><Label>{t('Timezone')}</Label><Input value={form.data.default_timezone} onChange={(e)=>form.setData('default_timezone', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Time')}</Label><Input type="time" value={form.data.default_schedule_time} onChange={(e)=>form.setData('default_schedule_time', e.target.value)} /></div><div><Label>{t('Sort order')}</Label><Input type="number" value={form.data.sort_order} onChange={(e)=>form.setData('sort_order', Number(e.target.value))} /></div><label className="flex items-center gap-2 pt-7"><input type="checkbox" checked={form.data.is_active} onChange={(e)=>form.setData('is_active', e.target.checked)} />{t('Active')}</label></div>


            <div className="space-y-3 rounded-lg border p-4"><h3 className="font-semibold">{t('Recurring schedule')}</h3>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Frequency')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.default_run_frequency} onChange={(e)=>form.setData('default_run_frequency', e.target.value)}>{['daily','weekdays','weekends','selected_days','monthly','custom'].map((x:string)=><option key={x} value={x}>{t(x === 'daily' ? 'Daily' : x === 'weekdays' ? 'Weekdays' : x === 'weekends' ? 'Weekends' : x === 'selected_days' ? 'Selected days' : x === 'monthly' ? 'Monthly' : 'Custom')}</option>)}</select></div><div><Label>{t('Run time')}</Label><Input type="time" value={form.data.default_run_time} onChange={(e)=>form.setData('default_run_time', e.target.value)} /></div><div><Label>{t('Timezone')}</Label><Input value={form.data.default_timezone} onChange={(e)=>form.setData('default_timezone', e.target.value)} /></div></div>
            <div className="flex flex-wrap gap-4"><label><input type="checkbox" checked={form.data.default_schedule_is_active} onChange={(e)=>form.setData('default_schedule_is_active', e.target.checked)} /> {t('Active by default')}</label><label><input type="checkbox" checked={form.data.default_auto_run_pipeline} onChange={(e)=>form.setData('default_auto_run_pipeline', e.target.checked)} /> {t('Auto run pipeline')}</label><label><input type="checkbox" checked={form.data.default_auto_generate_ai_response} onChange={(e)=>form.setData('default_auto_generate_ai_response', e.target.checked)} /> {t('Auto generate AI response')}</label></div>
            </div>

            <div className="space-y-3 rounded-lg border p-4">
                <h3 className="font-semibold">{t('Coverage and prompt timing')}</h3>
                <p className="text-sm text-slate-600">{t('The coverage window defines which news period the AI should cover. Offsets are relative to broadcast time. For an 08:00 morning bulletin, use -1440 to -30 to cover from yesterday 08:00 to today 07:30.')}</p>
                <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Coverage mode')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.coverage_mode} onChange={(e)=>form.setData('coverage_mode', e.target.value)}>{coverageModes.map((x:string)=><option key={x} value={x}>{t(x === 'previous_period' ? 'Previous period' : x === 'today_so_far' ? 'Today so far' : x === 'yesterday' ? 'Yesterday' : x === 'last_24_hours' ? 'Last 24 hours' : x === 'next_24_hours' ? 'Next 24 hours' : x === 'custom' ? 'Custom coverage' : 'No strict coverage')}</option>)}</select></div><div><Label>{t('Coverage description')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={2} value={form.data.coverage_description} onChange={(e)=>form.setData('coverage_description', e.target.value)} /></div></div>
                <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Coverage starts offset')}</Label><Input type="number" value={form.data.coverage_starts_offset_minutes} onChange={(e)=>form.setData('coverage_starts_offset_minutes', e.target.value)} /></div><div><Label>{t('Coverage ends offset')}</Label><Input type="number" value={form.data.coverage_ends_offset_minutes} onChange={(e)=>form.setData('coverage_ends_offset_minutes', e.target.value)} /></div></div>
                <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Minimum news items')}</Label><Input type="number" value={form.data.min_news_items} onChange={(e)=>form.setData('min_news_items', e.target.value)} /></div><div><Label>{t('Maximum news items')}</Label><Input type="number" value={form.data.max_news_items} onChange={(e)=>form.setData('max_news_items', e.target.value)} /></div></div>
                <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Prompt language')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.prompt_language} onChange={(e)=>form.setData('prompt_language', e.target.value)}>{promptLanguages.map((x:string)=><option key={x} value={x}>{x}</option>)}</select></div><div><Label>{t('Output mode')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.output_mode} onChange={(e)=>form.setData('output_mode', e.target.value)}>{outputModes.map((x:string)=><option key={x} value={x}>{t(x === 'plain_script' ? 'Plain script' : 'Structured script')}</option>)}</select></div></div>
                <div className="flex gap-6"><label className="flex items-center gap-2"><input type="checkbox" checked={form.data.include_future_agenda} onChange={(e)=>form.setData('include_future_agenda', e.target.checked)} />{t('Include future agenda')}</label><label className="flex items-center gap-2"><input type="checkbox" checked={form.data.include_historical_context} onChange={(e)=>form.setData('include_historical_context', e.target.checked)} />{t('Include historical context')}</label></div>
            </div>

            <div className="flex gap-2"><Button type="submit" disabled={form.processing}>{t('Save')}</Button><Button asChild variant="secondary"><Link href={route('editor.bulletin-types.index')}>{t('Cancel')}</Link></Button></div>
        </form></CardContent></Card>

        {bulletinType && <Card className="mt-4"><CardContent className="pt-6"><h3 className="mb-3 font-semibold">{t('Create run for date/time')}</h3><p className="mb-3 text-sm text-slate-600">{t('Use current date if no schedule is configured')}</p><div className="flex flex-wrap items-end gap-3"><div><Label>{t('Scheduled date/time')}</Label><Input type="datetime-local" value={runForm.data.scheduled_for} onChange={(e)=>runForm.setData('scheduled_for', e.target.value)} /></div><Button type="button" variant="outline" onClick={createRun}>{t('Create Prompt Run')}</Button></div></CardContent></Card>}
    </EditorLayout>;
}
