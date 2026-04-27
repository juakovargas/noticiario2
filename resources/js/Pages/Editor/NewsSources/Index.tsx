import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';

interface Item {
    id: number;
    name: string;
    type: string;
    url: string | null;
    feed_url: string | null;
    language: string | null;
    is_active: boolean;
    is_demo: boolean;
    trust_level: number;
    default_category: string | null;
    default_location: string | null;
    last_checked_at: string | null;
    last_imported_at: string | null;
}

interface Props {
    newsSources: {
        data: Item[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}

export default function Index({ newsSources }: Props): JSX.Element {
    const { t } = useTranslations();

    const destroy = (id: number): void => {
        if (window.confirm('Delete this source?')) {
            router.delete(route('editor.news-sources.destroy', id));
        }
    };

    const canImport = (item: Item): boolean => item.type === 'rss' && Boolean(item.feed_url) && item.is_active;

    return (
        <EditorLayout>
            <Head title={t('News Sources')} />
            <AdminPageHeader title={t('News Sources')} description="Manage source catalog." actionLabel={t('Create source')} actionHref={route('editor.news-sources.create')} />
            <Card>
                <CardContent className="overflow-x-auto pt-6">
                    <table className="w-full min-w-[980px] text-sm">
                        <thead>
                            <tr className="border-b border-slate-200 text-slate-500">
                                <th className="px-2 pb-3">{t('Name')}</th>
                                <th className="px-2 pb-3">{t('Type')}</th>
                                <th className="px-2 pb-3">{t('Feed URL')}</th>
                                <th className="px-2 pb-3">{t('Default category')}</th>
                                <th className="px-2 pb-3">{t('Default location')}</th>
                                <th className="px-2 pb-3">{t('Source language')}</th>
                                <th className="px-2 pb-3">{t('Last checked at')}</th>
                                <th className="px-2 pb-3">{t('Last imported at')}</th>
                                <th className="px-2 pb-3 text-right">{t('Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {newsSources.data.length ? newsSources.data.map((item) => (
                                <tr key={item.id} className="border-b border-slate-100">
                                    <td className="px-2 py-3 font-medium">{item.name} {item.is_demo ? <span className="ml-2 rounded bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700">Demo</span> : null}</td>
                                    <td className="px-2 py-3">{item.type}</td>
                                    <td className="max-w-[16rem] truncate px-2 py-3">{item.feed_url || item.url || '-'}</td>
                                    <td className="px-2 py-3">{item.default_category || '-'}</td>
                                    <td className="px-2 py-3">{item.default_location || '-'}</td>
                                    <td className="px-2 py-3">{item.language || '-'}</td>
                                    <td className="px-2 py-3">{item.last_checked_at || '-'}</td>
                                    <td className="px-2 py-3">{item.last_imported_at || '-'}</td>
                                    <td className="px-2 py-3">
                                        <div className="flex justify-end gap-2">
                                            {canImport(item) ? <Button asChild size="sm" variant="outline"><Link href={route('editor.news-sources.import', item.id)}>{t('Import News')}</Link></Button> : null}
                                            <Button asChild size="sm" variant="secondary"><Link href={route('editor.news-sources.edit', item.id)}>{t('Edit')}</Link></Button>
                                            <Button size="sm" variant="destructive" onClick={() => destroy(item.id)}>{t('Delete')}</Button>
                                        </div>
                                    </td>
                                </tr>
                            )) : <tr><td colSpan={9} className="px-2 py-6 text-center text-slate-500">No sources yet.</td></tr>}
                        </tbody>
                    </table>
                    <Pagination links={newsSources.links} />
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
