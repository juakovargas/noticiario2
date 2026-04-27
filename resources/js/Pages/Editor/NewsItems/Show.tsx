import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link } from '@inertiajs/react';

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
                    <p><strong>{t('Status')}:</strong> {newsItem.status}</p>
                    <p><strong>{t('Published at')}:</strong> {formatDateTime(newsItem.published_at)}</p>
                    <p><strong>{t('Collected at')}:</strong> {formatDateTime(newsItem.collected_at)}</p>

                    <div className="rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
                        <p><strong>{t('External ID')}:</strong> {newsItem.external_id || '-'}</p>
                        <p><strong>{t('Imported from RSS')}:</strong> {newsItem.metadata?.imported_from === 'rss' ? t('Yes') : t('No')}</p>
                    </div>

                    <Button asChild variant="secondary"><Link href={route('editor.news-items.index')}>{t('Back')}</Link></Button>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
