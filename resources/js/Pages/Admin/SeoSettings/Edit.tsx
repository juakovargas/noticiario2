import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface SeoSettingsForm {
    site_name: string;
    default_title: string;
    title_suffix: string;
    default_description: string;
    default_keywords: string;
    canonical_base_url: string;
    default_robots: string;
    default_og_image: string;
    default_twitter_card: string;
    google_site_verification: string;
    bing_site_verification: string;
    google_tag_manager_id: string;
    google_analytics_id: string;
    microsoft_clarity_id: string;
    meta_pixel_id: string;
    tiktok_pixel_id: string;
    custom_head_scripts: string;
    custom_body_start_scripts: string;
    custom_body_end_scripts: string;
    enable_tracking: boolean;
    enable_custom_scripts: boolean;
    enable_indexing: boolean;
}

export default function Edit({ seoSettings }: { seoSettings: Partial<SeoSettingsForm> | null }): JSX.Element {
    const { t } = useTranslations();
    const form = useForm<SeoSettingsForm>({
        site_name: seoSettings?.site_name ?? '',
        default_title: seoSettings?.default_title ?? '',
        title_suffix: seoSettings?.title_suffix ?? '',
        default_description: seoSettings?.default_description ?? '',
        default_keywords: seoSettings?.default_keywords ?? '',
        canonical_base_url: seoSettings?.canonical_base_url ?? '',
        default_robots: seoSettings?.default_robots ?? 'index,follow',
        default_og_image: seoSettings?.default_og_image ?? '',
        default_twitter_card: seoSettings?.default_twitter_card ?? 'summary_large_image',
        google_site_verification: seoSettings?.google_site_verification ?? '',
        bing_site_verification: seoSettings?.bing_site_verification ?? '',
        google_tag_manager_id: seoSettings?.google_tag_manager_id ?? '',
        google_analytics_id: seoSettings?.google_analytics_id ?? '',
        microsoft_clarity_id: seoSettings?.microsoft_clarity_id ?? '',
        meta_pixel_id: seoSettings?.meta_pixel_id ?? '',
        tiktok_pixel_id: seoSettings?.tiktok_pixel_id ?? '',
        custom_head_scripts: seoSettings?.custom_head_scripts ?? '',
        custom_body_start_scripts: seoSettings?.custom_body_start_scripts ?? '',
        custom_body_end_scripts: seoSettings?.custom_body_end_scripts ?? '',
        enable_tracking: seoSettings?.enable_tracking ?? false,
        enable_custom_scripts: seoSettings?.enable_custom_scripts ?? false,
        enable_indexing: seoSettings?.enable_indexing ?? true,
    });

    const submit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        form.put(route('admin.seo-settings.update'));
    };

    return (
        <AdminLayout>
            <Head title={t('SEO Settings')} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-slate-900">{t('SEO Settings')}</h1>
                    <Button asChild variant="outline">
                        <Link href={route('admin.dashboard')}>{t('Back to admin dashboard')}</Link>
                    </Button>
                </div>

                <form className="space-y-6" onSubmit={submit}>
                    <Card>
                        <CardHeader><CardTitle>{t('General SEO')}</CardTitle></CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <Field label={t('Site name')} value={form.data.site_name} onChange={(value)=>form.setData('site_name', value)} error={form.errors.site_name} />
                            <Field label={t('Default title')} value={form.data.default_title} onChange={(value)=>form.setData('default_title', value)} error={form.errors.default_title} />
                            <Field label={t('Title suffix')} value={form.data.title_suffix} onChange={(value)=>form.setData('title_suffix', value)} error={form.errors.title_suffix} />
                            <Field label={t('Canonical base URL')} value={form.data.canonical_base_url} onChange={(value)=>form.setData('canonical_base_url', value)} error={form.errors.canonical_base_url} />
                            <Field label={t('Default robots')} value={form.data.default_robots} onChange={(value)=>form.setData('default_robots', value)} error={form.errors.default_robots} />
                            <Field label={t('Default OG image')} value={form.data.default_og_image} onChange={(value)=>form.setData('default_og_image', value)} error={form.errors.default_og_image} />
                            <Field label={t('Default Twitter card')} value={form.data.default_twitter_card} onChange={(value)=>form.setData('default_twitter_card', value)} error={form.errors.default_twitter_card} />
                            <div className="md:col-span-2">
                                <Label>{t('Default description')}</Label>
                                <textarea className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" rows={3} value={form.data.default_description} onChange={(event)=>form.setData('default_description', event.target.value)} />
                                {form.errors.default_description && <p className="mt-1 text-sm text-red-600">{form.errors.default_description}</p>}
                            </div>
                            <div className="md:col-span-2">
                                <Label>{t('Default keywords')}</Label>
                                <textarea className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" rows={2} value={form.data.default_keywords} onChange={(event)=>form.setData('default_keywords', event.target.value)} />
                                {form.errors.default_keywords && <p className="mt-1 text-sm text-red-600">{form.errors.default_keywords}</p>}
                            </div>
                            <CheckboxField label={t('Enable indexing')} checked={form.data.enable_indexing} onChange={(checked)=>form.setData('enable_indexing', checked)} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>{t('Search engine verification')}</CardTitle></CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <Field label={t('Google site verification')} value={form.data.google_site_verification} onChange={(value)=>form.setData('google_site_verification', value)} error={form.errors.google_site_verification} helper={`${t('Verification token only')}. ${t('Do not paste the full meta tag')}.`} />
                            <Field label={t('Bing site verification')} value={form.data.bing_site_verification} onChange={(value)=>form.setData('bing_site_verification', value)} error={form.errors.bing_site_verification} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>{t('Tracking and analytics')}</CardTitle></CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <CheckboxField label={t('Enable tracking')} checked={form.data.enable_tracking} onChange={(checked)=>form.setData('enable_tracking', checked)} helper={t('Tracking is disabled by default')} />
                            <Field label={t('Google Tag Manager ID')} value={form.data.google_tag_manager_id} onChange={(value)=>form.setData('google_tag_manager_id', value)} error={form.errors.google_tag_manager_id} helper={`${t('Example')}: GTM-XXXXXXX`} />
                            <Field label={t('Google Analytics ID')} value={form.data.google_analytics_id} onChange={(value)=>form.setData('google_analytics_id', value)} error={form.errors.google_analytics_id} helper={`${t('Example')}: G-XXXXXXXXXX`} />
                            <Field label={t('Microsoft Clarity ID')} value={form.data.microsoft_clarity_id} onChange={(value)=>form.setData('microsoft_clarity_id', value)} error={form.errors.microsoft_clarity_id} helper={`${t('Example')}: abc123xyz`} />
                            <Field label={t('Meta Pixel ID')} value={form.data.meta_pixel_id} onChange={(value)=>form.setData('meta_pixel_id', value)} error={form.errors.meta_pixel_id} />
                            <Field label={t('TikTok Pixel ID')} value={form.data.tiktok_pixel_id} onChange={(value)=>form.setData('tiktok_pixel_id', value)} error={form.errors.tiktok_pixel_id} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>{t('Custom scripts')}</CardTitle></CardHeader>
                        <CardContent className="space-y-4">
                            <CheckboxField label={t('Enable custom scripts')} checked={form.data.enable_custom_scripts} onChange={(checked)=>form.setData('enable_custom_scripts', checked)} helper={`${t('Custom scripts are injected only when enabled')}. ${t('Only trusted administrators should edit custom scripts')}.`} />
                            <ScriptField label={t('Custom head scripts')} value={form.data.custom_head_scripts} onChange={(value)=>form.setData('custom_head_scripts', value)} error={form.errors.custom_head_scripts} />
                            <ScriptField label={t('Custom body start scripts')} value={form.data.custom_body_start_scripts} onChange={(value)=>form.setData('custom_body_start_scripts', value)} error={form.errors.custom_body_start_scripts} />
                            <ScriptField label={t('Custom body end scripts')} value={form.data.custom_body_end_scripts} onChange={(value)=>form.setData('custom_body_end_scripts', value)} error={form.errors.custom_body_end_scripts} />
                        </CardContent>
                    </Card>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={form.processing}>{t('Save settings')}</Button>
                        <Button asChild variant="secondary"><Link href={route('admin.dashboard')}>{t('Back to admin dashboard')}</Link></Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

function Field({ label, value, onChange, error, helper }: { label: string; value: string; onChange: (value: string) => void; error?: string; helper?: string }): JSX.Element {
    return <div><Label>{label}</Label><Input value={value} onChange={(event)=>onChange(event.target.value)} />{helper && <p className="mt-1 text-xs text-slate-500">{helper}</p>}{error && <p className="mt-1 text-sm text-red-600">{error}</p>}</div>;
}

function CheckboxField({ label, checked, onChange, helper }: { label: string; checked: boolean; onChange: (checked: boolean) => void; helper?: string }): JSX.Element {
    return <div className="md:col-span-2"><label className="flex items-center gap-2 text-sm font-medium text-slate-800"><input type="checkbox" checked={checked} onChange={(event)=>onChange(event.target.checked)} />{label}</label>{helper && <p className="mt-1 text-xs text-slate-500">{helper}</p>}</div>;
}

function ScriptField({ label, value, onChange, error }: { label: string; value: string; onChange: (value: string) => void; error?: string }): JSX.Element {
    return <div><Label>{label}</Label><textarea className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-xs" rows={6} value={value} onChange={(event)=>onChange(event.target.value)} />{error && <p className="mt-1 text-sm text-red-600">{error}</p>}</div>;
}
