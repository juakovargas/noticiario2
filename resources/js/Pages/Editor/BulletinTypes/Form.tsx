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

export default function Form({ bulletinType, locations = [], categories = [], languages = [], promptProfiles = [], scriptProviders = [], editionTypes = [], coverageModes = [], promptLanguages = [], outputModes = [] }: any): JSX.Element {
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
                                        {provider.name} · {provider.default_model || t('bulletinTypes.empty.noModel')} · {t(provider.supports_grounding ? 'bulletinTypes.provider.grounded' : 'bulletinTypes.provider.notGrounded')}
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
                                {['daily', 'weekdays', 'weekends', 'selected_days', 'monthly', 'custom'].map((item) => <option key={item} value={item}>{t(`frequency.${item}`)}</option>)}
                            </Select>
                        </Field>
                        <Field label={t('bulletinTypes.form.runTime')} error={form.errors.default_run_time}><Input type="time" value={form.data.default_run_time} onChange={(e) => form.setData('default_run_time', e.target.value)} /></Field>
                        <Field label={t('bulletinTypes.form.timezone')} error={form.errors.default_timezone}><Input value={form.data.default_timezone} onChange={(e) => form.setData('default_timezone', e.target.value)} /></Field>
                    </div>
                    <div className="mt-4 grid gap-3 md:grid-cols-3">
                        <Toggle label={t('bulletinTypes.form.activeBulletin')} checked={form.data.is_active} onChange={(value) => form.setData('is_active', value)} />
                        <Toggle label={t('bulletinTypes.form.activeSchedule')} checked={form.data.default_schedule_is_active} onChange={(value) => form.setData('default_schedule_is_active', value)} />
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
                        <Field label={t('bulletinTypes.form.editionType')} error={form.errors.edition_type}><Select value={form.data.edition_type} onChange={(value) => form.setData('edition_type', value)}><option value="">{t('bulletinTypes.empty.notAssigned')}</option>{editionTypes.map((item: string) => <option key={item} value={item}>{t(`editionType.${item}`)}</option>)}</Select></Field>
                        <Field label={t('bulletinTypes.form.sortOrder')} error={form.errors.sort_order}><Input type="number" value={form.data.sort_order} onChange={(e) => form.setData('sort_order', Number(e.target.value))} /></Field>
                    </div>
                    <div className="mt-4 grid gap-4 md:grid-cols-2">
                        <Field label={t('bulletinTypes.form.coverageMode')} error={form.errors.coverage_mode}><Select value={form.data.coverage_mode} onChange={(value) => form.setData('coverage_mode', value)}>{coverageModes.map((item: string) => <option key={item} value={item}>{t(`coverage.${item}`)}</option>)}</Select></Field>
                        <Field label={t('bulletinTypes.form.coverageDescription')} error={form.errors.coverage_description}><textarea className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950" rows={2} value={form.data.coverage_description} onChange={(e) => form.setData('coverage_description', e.target.value)} /></Field>
                        <Field label={t('bulletinTypes.form.coverageStarts')} error={form.errors.coverage_starts_offset_minutes}><Input type="number" value={form.data.coverage_starts_offset_minutes} onChange={(e) => form.setData('coverage_starts_offset_minutes', e.target.value)} /></Field>
                        <Field label={t('bulletinTypes.form.coverageEnds')} error={form.errors.coverage_ends_offset_minutes}><Input type="number" value={form.data.coverage_ends_offset_minutes} onChange={(e) => form.setData('coverage_ends_offset_minutes', e.target.value)} /></Field>
                        <Field label={t('bulletinTypes.form.minNewsItems')} error={form.errors.min_news_items}><Input type="number" value={form.data.min_news_items} onChange={(e) => form.setData('min_news_items', e.target.value)} /></Field>
                        <Field label={t('bulletinTypes.form.maxNewsItems')} error={form.errors.max_news_items}><Input type="number" value={form.data.max_news_items} onChange={(e) => form.setData('max_news_items', e.target.value)} /></Field>
                    </div>
                    <div className="mt-4 grid gap-3 md:grid-cols-2">
                        <Toggle label={t('bulletinTypes.form.includeFutureAgenda')} checked={form.data.include_future_agenda} onChange={(value) => form.setData('include_future_agenda', value)} />
                        <Toggle label={t('bulletinTypes.form.includeHistoricalContext')} checked={form.data.include_historical_context} onChange={(value) => form.setData('include_historical_context', value)} />
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

function Field({ label, error, children }: { label: string; error?: string; children: JSX.Element }): JSX.Element {
    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            {children}
            {error && <p className="text-xs text-rose-600 dark:text-rose-300">{error}</p>}
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

function Toggle({ label, checked, onChange }: { label: string; checked: boolean; onChange: (value: boolean) => void }): JSX.Element {
    return (
        <label className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
            <span>{label}</span>
            <input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} />
        </label>
    );
}
