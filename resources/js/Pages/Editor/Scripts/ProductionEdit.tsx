import AdminPageHeader from '@/Components/AdminPageHeader';
import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

type Props = {
    script: any;
    productionStatuses: string[];
    targetPlatforms: string[];
};

export default function ProductionEdit({ script, productionStatuses, targetPlatforms }: Props): JSX.Element {
    const { t } = useTranslations();
    const { data, setData, put, processing, errors } = useForm({
        production_name: script.production_name ?? '',
        final_title: script.final_title ?? '',
        public_description: script.public_description ?? '',
        short_description: script.short_description ?? '',
        hashtags: (script.hashtags ?? []).join(', '),
        social_copy: script.social_copy ?? '',
        target_platforms: script.target_platforms ?? [],
        seo_title: script.seo_title ?? '',
        seo_description: script.seo_description ?? '',
        production_status: script.production_status ?? 'draft',
        mark_ready_for_production: false,
    });

    const togglePlatform = (platform: string): void => {
        const exists = data.target_platforms.includes(platform);
        setData('target_platforms', exists ? data.target_platforms.filter((item: string) => item !== platform) : [...data.target_platforms, platform]);
    };

    const submit = (event: FormEvent): void => {
        event.preventDefault();
        put(route('editor.scripts.production.update', script.id));
    };

    return (
        <EditorLayout>
            <Head title={t('Production metadata')} />
            <AdminPageHeader title={t('Production metadata')} description={t('Prepare publishing metadata')} />
            <Card>
                <CardContent className="pt-6">
                    <form className="space-y-4" onSubmit={submit}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div><Label>{t('Production name')}</Label><Input value={data.production_name} onChange={(e) => setData('production_name', e.target.value)} /><InputError message={errors.production_name} /></div>
                            <div><Label>{t('Final title')}</Label><Input value={data.final_title} onChange={(e) => setData('final_title', e.target.value)} /><InputError message={errors.final_title} /></div>
                        </div>
                        <div><Label>{t('Public description')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={4} value={data.public_description} onChange={(e) => setData('public_description', e.target.value)} /><InputError message={errors.public_description} /></div>
                        <div><Label>{t('Short description')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={2} value={data.short_description} onChange={(e) => setData('short_description', e.target.value)} /></div>
                        <div><Label>{t('Hashtags')}</Label><Input value={data.hashtags} onChange={(e) => setData('hashtags', e.target.value)} placeholder="#news, #world" /><InputError message={errors.hashtags} /></div>
                        <div><Label>{t('Social copy')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.social_copy} onChange={(e) => setData('social_copy', e.target.value)} /></div>
                        <div>
                            <Label>{t('Target platforms')}</Label>
                            <div className="mt-2 grid gap-2 md:grid-cols-3">
                                {targetPlatforms.map((platform) => (
                                    <label key={platform} className="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" checked={data.target_platforms.includes(platform)} onChange={() => togglePlatform(platform)} />
                                        {t(platform === 'x_twitter' ? 'X/Twitter' : platform.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()))}
                                    </label>
                                ))}
                            </div>
                            <InputError message={errors.target_platforms} />
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div><Label>{t('SEO title')}</Label><Input value={data.seo_title} onChange={(e) => setData('seo_title', e.target.value)} /></div>
                            <div><Label>{t('SEO description')}</Label><Input value={data.seo_description} onChange={(e) => setData('seo_description', e.target.value)} /></div>
                        </div>
                        <div>
                            <Label>{t('Production status')}</Label>
                            <select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.production_status} onChange={(e) => setData('production_status', e.target.value)}>
                                {productionStatuses.map((status) => <option key={status} value={status}>{status}</option>)}
                            </select>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" disabled={processing}>{t('Save metadata')}</Button>
                            <Button type="button" variant="outline" onClick={() => { setData('mark_ready_for_production', true); put(route('editor.scripts.production.update', script.id)); }}>{t('Mark ready for production')}</Button>
                            <Button asChild variant="secondary"><Link href={route('editor.scripts.show', script.id)}>{t('Back')}</Link></Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
