import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Source {
    id: number;
    name: string;
    slug: string;
    type: string;
    url: string | null;
    feed_url: string | null;
    default_news_category_id: number | null;
    default_location_id: number | null;
    description: string | null;
    ingestion_notes: string | null;
    language: string | null;
    country_code: string | null;
    is_active: boolean;
    is_demo: boolean;
    trust_level: number;
    last_checked_at: string | null;
}

interface Option { id: number; name: string; }
interface Props { newsSource: Source; types: string[]; categories: Option[]; locations: Option[]; }

export default function Edit({ newsSource, types, categories, locations }: Props): JSX.Element {
    const { t } = useTranslations();
    const { data, setData, put, processing } = useForm({
        name: newsSource.name,
        slug: newsSource.slug,
        type: newsSource.type,
        url: newsSource.url || '',
        feed_url: newsSource.feed_url || '',
        default_news_category_id: newsSource.default_news_category_id ? String(newsSource.default_news_category_id) : '',
        default_location_id: newsSource.default_location_id ? String(newsSource.default_location_id) : '',
        description: newsSource.description || '',
        ingestion_notes: newsSource.ingestion_notes || '',
        language: newsSource.language || '',
        country_code: newsSource.country_code || '',
        is_active: newsSource.is_active,
        is_demo: newsSource.is_demo,
        trust_level: newsSource.trust_level,
        last_checked_at: newsSource.last_checked_at || '',
    });

    const submit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        put(route('editor.news-sources.update', newsSource.id));
    };

    return (
        <EditorLayout>
            <Head title={t('Edit News Source')} />
            <AdminPageHeader title={t('Edit News Source')} description={t('Update source details.')} />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="space-y-4">
                        <div><label>{t('Name')}</label><Input value={data.name} onChange={(e) => setData('name', e.target.value)} /></div>
                        <div><label>{t('Slug')}</label><Input value={data.slug} onChange={(e) => setData('slug', e.target.value)} /></div>
                        <div><label>{t('Type')}</label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.type} onChange={(e) => setData('type', e.target.value)}>{types.map((type) => <option key={type} value={type}>{type}</option>)}</select></div>
                        <div className="grid gap-4 md:grid-cols-2"><div><label>{t('URL')}</label><Input value={data.url} onChange={(e) => setData('url', e.target.value)} /></div><div><label>{t('Feed URL')}</label><Input value={data.feed_url} onChange={(e) => setData('feed_url', e.target.value)} /></div></div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div><label>{t('Default category')}</label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.default_news_category_id} onChange={(e) => setData('default_news_category_id', e.target.value)}><option value="">-</option>{categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select></div>
                            <div><label>{t('Default location')}</label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.default_location_id} onChange={(e) => setData('default_location_id', e.target.value)}><option value="">-</option>{locations.map((location) => <option key={location.id} value={location.id}>{location.name}</option>)}</select></div>
                        </div>
                        <div><label>{t('Description')}</label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.description} onChange={(e) => setData('description', e.target.value)} /></div>
                        <div><label>{t('RSS ingestion')}</label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.ingestion_notes} onChange={(e) => setData('ingestion_notes', e.target.value)} /></div>
                        <div className="grid gap-4 md:grid-cols-3"><div><label>{t('Source language')}</label><Input value={data.language} onChange={(e) => setData('language', e.target.value)} /></div><div><label>{t('Country code')}</label><Input value={data.country_code} onChange={(e) => setData('country_code', e.target.value)} /></div><div><label>{t('Trust level')}</label><Input type="number" min={1} max={5} value={data.trust_level} onChange={(e) => setData('trust_level', Number(e.target.value))} /></div></div>
                        <label className="flex items-center gap-2"><input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />{t('Active')}</label>
                        <label className="flex items-center gap-2"><input type="checkbox" checked={data.is_demo} onChange={(e) => setData('is_demo', e.target.checked)} />{t('Demo source')}</label>
                        <div className="flex gap-2"><Button type="submit" disabled={processing}>{t('Save')}</Button><Button asChild variant="secondary"><Link href={route('editor.news-sources.index')}>{t('Cancel')}</Link></Button></div>
                    </form>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
