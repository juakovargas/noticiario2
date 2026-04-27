import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Option {
    id: number;
    name: string;
}

interface Props {
    types: string[];
    categories: Option[];
    locations: Option[];
}

export default function Create({ types, categories, locations }: Props): JSX.Element {
    const { t } = useTranslations();
    const { data, setData, post, processing } = useForm({
        name: '',
        slug: '',
        type: 'manual',
        url: '',
        feed_url: '',
        default_news_category_id: '',
        default_location_id: '',
        description: '',
        ingestion_notes: '',
        language: '',
        country_code: '',
        is_active: true,
        is_demo: false,
        trust_level: 3,
        last_checked_at: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        post(route('editor.news-sources.store'));
    };

    return (
        <EditorLayout>
            <Head title={t('Create News Source')} />
            <AdminPageHeader title={t('Create News Source')} description={t('Add a news source.')} />

            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="space-y-4">
                        <div><label>{t('Name')}</label><Input value={data.name} onChange={(e) => setData('name', e.target.value)} /></div>
                        <div><label>{t('Slug')}</label><Input value={data.slug} onChange={(e) => setData('slug', e.target.value)} /></div>

                        <div>
                            <label>{t('Type')}</label>
                            <select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.type} onChange={(e) => setData('type', e.target.value)}>
                                {types.map((type) => <option key={type} value={type}>{type}</option>)}
                            </select>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div><label>{t('URL')}</label><Input value={data.url} onChange={(e) => setData('url', e.target.value)} /></div>
                            <div><label>{t('Feed URL')}</label><Input value={data.feed_url} onChange={(e) => setData('feed_url', e.target.value)} /></div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label>{t('Default category')}</label>
                                <select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.default_news_category_id} onChange={(e) => setData('default_news_category_id', e.target.value)}>
                                    <option value="">-</option>
                                    {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
                                </select>
                            </div>
                            <div>
                                <label>{t('Default location')}</label>
                                <select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.default_location_id} onChange={(e) => setData('default_location_id', e.target.value)}>
                                    <option value="">-</option>
                                    {locations.map((location) => <option key={location.id} value={location.id}>{location.name}</option>)}
                                </select>
                            </div>
                        </div>

                        <div><label>{t('Description')}</label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.description} onChange={(e) => setData('description', e.target.value)} /></div>
                        <div><label>{t('RSS ingestion')}</label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.ingestion_notes} onChange={(e) => setData('ingestion_notes', e.target.value)} placeholder={t('Ingestion notes')} /></div>

                        <div className="grid gap-4 md:grid-cols-3">
                            <div><label>{t('Source language')}</label><Input value={data.language} onChange={(e) => setData('language', e.target.value)} /></div>
                            <div><label>{t('Country code')}</label><Input value={data.country_code} onChange={(e) => setData('country_code', e.target.value)} /></div>
                            <div><label>{t('Trust level')}</label><Input type="number" min={1} max={5} value={data.trust_level} onChange={(e) => setData('trust_level', Number(e.target.value))} /></div>
                        </div>

                        <label className="flex items-center gap-2"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />{t('Active')}</label>
                        <label className="flex items-center gap-2"><input type="checkbox" checked={data.is_demo} onChange={(e) => setData('is_demo', e.target.checked)} />{t('Demo source')}</label>

                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>{t('Save')}</Button>
                            <Button asChild variant="secondary"><Link href={route('editor.news-sources.index')}>{t('Cancel')}</Link></Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
