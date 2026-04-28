import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';

interface NewsItem {
    id: number;
    title: string;
    summary: string | null;
    body: string | null;
    source: string | null;
    category: string | null;
    location: { name: string; country_code: string | null } | null;
    source_url: string | null;
    external_id: string | null;
    metadata: { imported_from?: string } | null;
    status: string;
    published_at: string | null;
    collected_at: string | null;
    source_references: Array<{ id:number; title:string|null; source_name:string|null; source_url:string|null; verification_status:string; source_type:string }>;
}

interface Props { newsItem: NewsItem; }

export default function Show({ newsItem }: Props): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();

    return (
        <EditorLayout>
            <Head title={newsItem.title} />
            <AdminPageHeader title={newsItem.title} description="News item detail." />
            <Card>
                <CardContent className="space-y-3 pt-6 text-sm">
                    <p><strong>{t('Summary')}:</strong> {newsItem.summary || '-'}</p>
                    <p><strong>{t('Body')}:</strong> {newsItem.body || '-'}</p>
                    <p><strong>{t('Source')}:</strong> {newsItem.source || '-'}</p>
                    <p><strong>{t('Category')}:</strong> {newsItem.category || '-'}</p>
                    <p><strong>{t('Location')}:</strong> {newsItem.location?.name || '-'}</p>
                    <p><strong>{t('Source URL')}:</strong> {newsItem.source_url ? <a className="text-blue-600 underline" href={newsItem.source_url} target="_blank" rel="noreferrer">{t('Open original')}</a> : '-'}</p>
                    {newsItem.source_url ? <Button variant="outline" size="sm" onClick={() => router.post(route('editor.news-items.source-references.extract', newsItem.id))}>{t('Extract sources from news item')}</Button> : null}
                    <p><strong>{t('Status')}:</strong> {newsItem.status}</p>
                    <p><strong>{t('Published at')}:</strong> {formatDateTime(newsItem.published_at)}</p>
                    <p><strong>{t('Collected at')}:</strong> {formatDateTime(newsItem.collected_at)}</p>

                    <div className="rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                        <p><strong>{t('External ID')}:</strong> {newsItem.external_id || '-'}</p>
                        <p><strong>{t('Imported from RSS')}:</strong> {newsItem.metadata?.imported_from === 'rss' ? t('Yes') : t('No')}</p>
                    </div>
                    <div className="rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                        <p className="font-semibold">{t('Source References')}</p>
                        {newsItem.source_references.length ? newsItem.source_references.map((source) => <p key={source.id}><Link className="text-cyan-700 underline" href={route('editor.source-references.show', source.id)}>{source.title || source.source_name || t('Source Reference')}</Link> · {source.verification_status}</p>) : <p>{t('No source references found')}</p>}
                    </div>

                    <Button asChild variant="secondary"><Link href={route('editor.news-items.index')}>{t('Back')}</Link></Button>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
