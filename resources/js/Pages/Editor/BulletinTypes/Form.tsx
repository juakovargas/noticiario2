import AdminPageHeader from '@/Components/AdminPageHeader';
import { DashboardPanel, ProviderBadge, StatusBadge } from '@/Components/EditorDashboard';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import type { ReactNode } from 'react';

export default function Form({
    bulletinType,
    scheduleConfig = null,
    locations = [],
    categories = [],
    languages = [],
    promptProfiles = [],
    scriptProviders = [],
    editionTypes = [],
    frequencyTypes = ['once', 'daily', 'weekdays', 'weekends', 'selected_days', 'weekly', 'monthly', 'custom'],
    weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
    coverageModes = [],
    promptLanguages = [],
    outputModes = [],
}: any): JSX.Element {
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
        preferred_ai_provider_id: bulletinType?.preferred_ai_provider_id?.toString() ?? '',
        edition_type: bulletinType?.edition_type ?? '',
        target_duration_seconds: bulletinType?.target_duration_seconds?.toString() ?? '',
        default_schedule_time: scheduleConfig?.times?.[0] ?? bulletinType?.default_schedule_time?.slice(0, 5) ?? '',
        default_timezone: scheduleConfig?.timezone ?? bulletinType?.default_timezone ?? '',
        default_run_frequency: scheduleConfig?.frequency ?? bulletinType?.default_run_frequency ?? 'daily',
        default_run_time: scheduleConfig?.times?.[0] ?? bulletinType?.default_run_time?.slice(0, 5) ?? bulletinType?.default_schedule_time?.slice(0, 5) ?? '',
        default_run_times: scheduleConfig?.times?.length ? scheduleConfig.times : [bulletinType?.default_run_time?.slice(0, 5) ?? bulletinType?.default_schedule_time?.slice(0, 5) ?? '08:00'],
        default_run_days: scheduleConfig?.days ?? bulletinType?.default_run_days ?? [],
        default_month_day: (scheduleConfig?.month_day ?? 1).toString(),
        default_schedule_date: scheduleConfig?.schedule_date ?? '',
        default_schedule_is_active: bulletinType?.default_schedule_is_active ?? true,
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
        output_mode: bulletinType?.output_mode ?? 'plain_final_script',
        is_active: bulletinType?.is_active ?? true,
        sort_order: bulletinType?.sort_order ?? 0,
    });

    const submit = (e: FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        if (isEdit) form.put(route('editor.bulletin-types.update', bulletinType.id));
        else form.post(route('editor.bulletin-types.store'));
    };

    const selectedProvider = scriptProviders.find((provider: any) => String(provider.id) === String(form.data.preferred_ai_provider_id));
    const selectedFrequency = form.data.default_run_frequency;
    const usesSelectedDays = ['selected_days', 'weekly', 'custom'].includes(selectedFrequency);
    const showMonthDay = selectedFrequency === 'monthly';
    const showOnceDate = selectedFrequency === 'once';
    const showCustomNote = selectedFrequency === 'custom';
    const scheduleTimes = form.data.default_run_times.length ? form.data.default_run_times : [''];

    const setScheduleTimes = (times: string[]): void => {
        const cleanTimes = times.length ? times : [''];
        form.setData('default_run_times', cleanTimes);
        form.setData('default_run_time', cleanTimes[0] ?? '');
        form.setData('default_schedule_time', cleanTimes[0] ?? '');
    };

    const updateScheduleTime = (index: number, value: string): void => {
        setScheduleTimes(scheduleTimes.map((time: string, current: number) => (current === index ? value : time)));
    };

    const addScheduleTime = (): void => {
        setScheduleTimes([...scheduleTimes, '']);
    };

    const removeScheduleTime = (index: number): void => {
        setScheduleTimes(scheduleTimes.filter((_: string, current: number) => current !== index));
    };

    const toggleRunDay = (day: string): void => {
        const days = form.data.default_run_days.includes(day)
            ? form.data.default_run_days.filter((item: string) => item !== day)
            : [...form.data.default_run_days, day];

        form.setData('default_run_days', days);
    };

    return (
        <EditorLayout>
            <Head title={isEdit ? t('bulletinTypes.form.editTitle') : t('bulletinTypes.form.createTitle')} />
            <AdminPageHeader
                helpKey={isEdit ? 'editor.bulletintypes.edit' : 'editor.bulletintypes.create'}
                title={isEdit ? t('bulletinTypes.form.editTitle') : t('bulletinTypes.form.createTitle')}
                description={t('bulletinTypes.form.description')}
            />

            <form onSubmit={submit} className="space-y-6">
                <DashboardPanel accent="cyan" className="p-5">
                    <Section title={t('bulletinTypes.form.identitySection')} description={t('bulletinTypes.form.identityHelp')} />
                    <div className="grid gap-4 md:grid-cols-2">
                        <Field label={t('bulletinTypes.form.name')} error={form.errors.name}><Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} /></Field>
                        <Field label={t('bulletinTypes.form.slug')} error={form.errors.slug}><Input value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} /></Field>
                    </div>
                    <Field label={t('bulletinTypes.form.descriptionLabel')} error={form.errors.description}>
                        <textarea className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950" rows={3} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} />
                    </Field>
                    <div className="grid gap-4 md:grid-cols-3">
                        <Field label={t('bulletinTypes.form.location')} error={form.errors.location_id}><Select value={form.data.location_id} onChange={(value) => form.setData('location_id', value)}><option value="">{t('bulletinTypes.empty.notAssigned')}</option>{locations.map((item: any) => <option key={item.id} value={item.id}>{item.name}</option>)}</Select></Field>
                        <Field label={t('bulletinTypes.form.category')} error={form.errors.news_category_id}><Select value={form.data.news_category_id} onChange={(value) => form.setData('news_category_id', value)}><option value="">{t('bulletinTypes.empty.notAssigned')}</option>{categories.map((item: any) => <option key={item.id} value={item.id}>{item.name}</option>)}</Select></Field>
                        <Field label={t('bulletinTypes.form.language')} error={form.errors.language_id}><Select value={form.data.language_id} onChange={(value) => form.setData('language_id', value)}><option value="">{t('bulletinTypes.empty.notAssigned')}</option>{languages.map((item: any) => <option key={item.id} value={item.id}>{item.name} ({item.code})</option>)}</Select></Field>
                    </div>
                </DashboardPanel>

                <DashboardPanel accent="emerald" className="p-5">
                    <Section title={t('bulletinTypes.form.providerSection')} description={t('bulletinTypes.form.providerHelp')} />
                    <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                        <Field label={t('bulletinTypes.form.preferredProvider')} error={form.errors.preferred_ai_provider_id}>
                            <Select value={form.data.preferred_ai_provider_id} onChange={(value) => form.setData('preferred_ai_provider_id', value)}>
                                <option value="">{t('bulletinTypes.provider.missing')}</option>
                                {scriptProviders.map((provider: any) => (
                                    <option key={provider.id} value={provider.id}>
                                        {provider.name} - {provider.default_model || t('bulletinTypes.empty.noModel')} - {t(provider.supports_grounding ? 'bulletinTypes.provider.grounded' : 'bulletinTypes.provider.notGrounded')}
                                    </option>
                                ))}
                            </Select>
                        </Field>
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900">
                            <ProviderBadge provider={selectedProvider} missingLabel={t('bulletinTypes.provider.missing')} groundedLabel={t('bulletinTypes.provider.grounded')} />
                            <p className="mt-3 text-xs text-slate-500 dark:text-slate-400">{t('bulletinTypes.form.providerSelectorNote')}</p>
                        </div>
                    </div>
                </DashboardPanel>

                <DashboardPanel accent="violet" className="p-5">
                    <Section title={t('bulletinTypes.form.scheduleSection')} description={t('bulletinTypes.form.scheduleHelp')} />
                    <div className="grid gap-4 md:grid-cols-3">
                        <Field label={t('bulletinTypes.form.frequency')} error={form.errors.default_run_frequency}>
                            <Select value={form.data.default_run_frequency} onChange={(value) => form.setData('default_run_frequency', value)}>
                                {frequencyTypes.map((item: string) => <option key={item} value={item}>{t(`frequency.${item}`)}</option>)}
                            </Select>
                        </Field>
                        <Field label={t('bulletinTypes.form.timezone')} error={form.errors.default_timezone} help={t('bulletinTypes.form.timezoneHelp')}><Input value={form.data.default_timezone} onChange={(e) => form.setData('default_timezone', e.target.value)} placeholder="Europe/Madrid" /></Field>
                        {showOnceDate && <Field label={t('bulletinTypes.form.onceDate')} error={form.errors.default_schedule_date} help={t('bulletinTypes.form.onceDateHelp')}><Input type="date" value={form.data.default_schedule_date} onChange={(e) => form.setData('default_schedule_date', e.target.value)} /></Field>}
                        {showMonthDay && <Field label={t('bulletinTypes.form.monthDay')} error={form.errors.default_month_day} help={t('bulletinTypes.form.monthDayHelp')}><Input type="number" min={1} max={31} value={form.data.default_month_day} onChange={(e) => form.setData('default_month_day', e.target.value)} /></Field>}
                    </div>
                    {usesSelectedDays && (
                        <Field label={t('bulletinTypes.form.selectedDays')} error={form.errors.default_run_days} help={showCustomNote ? t('bulletinTypes.form.customScheduleHelp') : t('bulletinTypes.form.selectedDaysHelp')}>
                            <div className="flex flex-wrap gap-2">
                                {weekdays.map((day: string) => (
                                    <button
                                        key={day}
                                        type="button"
                                        className={`rounded-full border px-3 py-1.5 text-xs font-semibold transition ${form.data.default_run_days.includes(day) ? 'border-cyan-500 bg-cyan-50 text-cyan-700 dark:border-cyan-800 dark:bg-cyan-950 dark:text-cyan-200' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300'}`}
                                        onClick={() => toggleRunDay(day)}
                                    >
                                        {t(`weekday.${day}`)}
                                    </button>
                                ))}
                            </div>
                        </Field>
                    )}
                    <Field label={t('bulletinTypes.form.scheduleTimes')} error={form.errors.default_run_times} help={t('bulletinTypes.form.scheduleTimesHelp')}>
                        <div className="space-y-2">
                            {scheduleTimes.map((time: string, index: number) => (
                                <div key={`${index}-${time}`} className="flex items-center gap-2">
                                    <Input type="time" value={time} onChange={(e) => updateScheduleTime(index, e.target.value)} />
                                    <Button type="button" variant="outline" size="sm" onClick={() => removeScheduleTime(index)} disabled={scheduleTimes.length === 1}>{t('bulletinTypes.form.removeTime')}</Button>
                                </div>
                            ))}
                            <Button type="button" variant="secondary" size="sm" onClick={addScheduleTime}>{t('bulletinTypes.form.addTime')}</Button>
                        </div>
                    </Field>
                    {showCustomNote && <p className="mt-3 rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-2 text-xs text-cyan-800 dark:border-cyan-900 dark:bg-cyan-950 dark:text-cyan-200">{t('bulletinTypes.form.customScheduleNote')}</p>}
                    <div className="mt-4 grid gap-3 md:grid-cols-3">
                        <Toggle label={t('bulletinTypes.form.activeBulletin')} help={t('bulletinTypes.form.activeBulletinHelp')} checked={form.data.is_active} onChange={(value) => form.setData('is_active', value)} />
                        <Toggle label={t('bulletinTypes.form.createActiveSchedules')} help={t('bulletinTypes.form.createActiveSchedulesHelp')} checked={form.data.default_schedule_is_active} onChange={(value) => form.setData('default_schedule_is_active', value)} />
                        <Toggle label={t('bulletinTypes.form.autoAi')} checked={form.data.default_auto_generate_ai_response} onChange={(value) => form.setData('default_auto_generate_ai_response', value)} />
                        <Toggle label={t('bulletinTypes.form.autoPipeline')} checked={form.data.default_auto_run_pipeline} onChange={(value) => form.setData('default_auto_run_pipeline', value)} />
                        <Toggle label={t('bulletinTypes.form.autoCreateScript')} checked={form.data.default_auto_create_script} onChange={(value) => form.setData('default_auto_create_script', value)} />
                    </div>
                    {!form.data.default_auto_generate_ai_response && <div className="mt-4"><StatusBadge tone="warning">{t('bulletinTypes.automation.aiManualApproval')}</StatusBadge></div>}
                </DashboardPanel>

                <DashboardPanel className="p-5">
                    <Section title={t('bulletinTypes.form.promptSection')} description={t('bulletinTypes.form.promptHelp')} />
                    <div className="grid gap-4 md:grid-cols-3">
                        <Field label={t('bulletinTypes.form.targetDuration')} error={form.errors.target_duration_seconds}><Input type="number" min={15} max={3600} value={form.data.target_duration_seconds} onChange={(e) => form.setData('target_duration_seconds', e.target.value)} /></Field>
                        <Field label={t('bulletinTypes.form.promptLanguage')} error={form.errors.prompt_language}><Select value={form.data.prompt_language} onChange={(value) => form.setData('prompt_language', value)}>{promptLanguages.map((item: string) => <option key={item} value={item}>{item}</option>)}</Select></Field>
                        <Field label={t('bulletinTypes.form.outputMode')} error={form.errors.output_mode}><Select value={form.data.output_mode} onChange={(value) => form.setData('output_mode', value)}>{outputModes.map((item: string) => <option key={item} value={item}>{t(`outputMode.${item}`)}</option>)}</Select></Field>
                        <Field label={t('bulletinTypes.form.promptProfile')} error={form.errors.default_prompt_profile_id}><Select value={form.data.default_prompt_profile_id} onChange={(value) => form.setData('default_prompt_profile_id', value)}><option value="">{t('bulletinTypes.empty.notAssigned')}</option>{promptProfiles.map((item: any) => <option key={item.id} value={item.id}>{item.name}</option>)}</Select></Field>
                        <Field label={t('bulletinTypes.form.editionType')} error={form.errors.edition_type} help={t('bulletinTypes.form.editionTypeHelp')}><Select value={form.data.edition_type} onChange={(value) => form.setData('edition_type', value)}><option value="">{t('bulletinTypes.form.inferFromSchedule')}</option>{editionTypes.map((item: string) => <option key={item} value={item}>{t(`editionType.${item}`)}</option>)}</Select></Field>
                        <Field label={t('bulletinTypes.form.sortOrder')} error={form.errors.sort_order}><Input type="number" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', Number(e.target.value))} /></Field>
                    </div>
                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        <Field label={t('bulletinTypes.form.coverageMode')} error={form.errors.coverage_mode} help={t('bulletinTypes.form.coverageModeHelp')}><Select value={form.data.coverage_mode} onChange={(value) => form.setData('coverage_mode', value)}>{coverageModes.map((item: string) => <option key={item} value={item}>{t(`coverage.${item}`)}</option>)}</Select></Field>
                        <Field label={t('bulletinTypes.form.coverageDescription')} error={form.errors.coverage_description} help={t('bulletinTypes.form.coverageDescriptionHelp')}><textarea className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950" rows={2} value={form.data.coverage_description} onChange={(e) => form.setData('coverage_description', e.target.value)} /></Field>
                        <Field label={t('bulletinTypes.form.coverageStarts')} error={form.errors.coverage_starts_offset_minutes} help={t('bulletinTypes.form.coverageStartsHelp')}><Input type="number" value={form.data.coverage_starts_offset_minutes} onChange={(e) => form.setData('coverage_starts_offset_minutes', e.target.value)} /></Field>
                        <Field label={t('bulletinTypes.form.coverageEnds')} error={form.errors.coverage_ends_offset_minutes} help={t('bulletinTypes.form.coverageEndsHelp')}><Input type="number" value={form.data.coverage_ends_offset_minutes} onChange={(e) => form.setData('coverage_ends_offset_minutes', e.target.value)} /></Field>
                        <Field label={t('bulletinTypes.form.minNewsItems')} error={form.errors.min_news_items} help={t('bulletinTypes.form.minNewsItemsHelp')}><Input type="number" value={form.data.min_news_items} onChange={(e) => form.setData('min_news_items', e.target.value)} /></Field>
                        <Field label={t('bulletinTypes.form.maxNewsItems')} error={form.errors.max_news_items} help={t('bulletinTypes.form.maxNewsItemsHelp')}><Input type="number" value={form.data.max_news_items} onChange={(e) => form.setData('max_news_items', e.target.value)} /></Field>
                    </div>
                    <div className="mt-4 grid gap-3 md:grid-cols-2">
                        <Toggle label={t('bulletinTypes.form.includeFutureAgenda')} help={t('bulletinTypes.form.includeFutureAgendaHelp')} checked={form.data.include_future_agenda} onChange={(value) => form.setData('include_future_agenda', value)} />
                        <Toggle label={t('bulletinTypes.form.includeHistoricalContext')} help={t('bulletinTypes.form.includeHistoricalContextHelp')} checked={form.data.include_historical_context} onChange={(value) => form.setData('include_historical_context', value)} />
                    </div>
                </DashboardPanel>

                <div className="flex flex-wrap gap-2">
                    <Button type="submit" disabled={form.processing}>{t('common.save')}</Button>
                    <Button asChild variant="secondary"><Link href={route('editor.bulletin-types.index')}>{t('common.cancel')}</Link></Button>
                    {isEdit && <Button asChild variant="outline"><Link href={route('editor.bulletin-types.show', bulletinType.id)}>{t('bulletinTypes.action.open')}</Link></Button>}
                </div>
            </form>
        </EditorLayout>
    );
}

function Section({ title, description }: { title: string; description: string }): JSX.Element {
    return (
        <div className="mb-5">
            <h2 className="text-base font-semibold text-slate-950 dark:text-white">{title}</h2>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{description}</p>
        </div>
    );
}

function Field({ label, error, help, children }: { label: string; error?: string; help?: string; children: ReactNode }): JSX.Element {
    const { t } = useTranslations();

    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            {children}
            {help && <p className="text-xs leading-5 text-slate-500 dark:text-slate-400">{help}</p>}
            {error && <p className="text-xs text-rose-600 dark:text-rose-300">{t(error)}</p>}
        </div>
    );
}

function Select({ value, onChange, children }: { value: string; onChange: (value: string) => void; children: ReactNode }): JSX.Element {
    return (
        <select
            className="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 shadow-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
            value={value}
            onChange={(event) => onChange(event.target.value)}
        >
            {children}
        </select>
    );
}

function Toggle({ label, help, checked, onChange }: { label: string; help?: string; checked: boolean; onChange: (value: boolean) => void }): JSX.Element {
    return (
        <label className="flex items-start justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
            <span>
                <span className="block">{label}</span>
                {help && <span className="mt-1 block text-xs font-normal leading-5 text-slate-500 dark:text-slate-400">{help}</span>}
            </span>
            <input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} />
        </label>
    );
}
