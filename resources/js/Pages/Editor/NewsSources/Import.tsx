import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

interface Source {
    id: number;
    name: string;
    type: string;
    feed_url: string | null;
    language: string | null;
    default_category: string | null;
    default_location: string | null;
    last_checked_at: string | null;
    last_imported_at: string | null;
}

interface FeedItem {
    key: string;
    title: string;
    summary: string | null;
    source_url: string | null;
    author: string | null;
    published_at: string | null;
    external_id: string | null;
    is_duplicate: boolean;
    duplicate_reason: string | null;
}

interface Props {
    source: Source;
    items: FeedItem[];
}

export default function Import({ source, items }: Props): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const initial = useMemo(() => items.filter((item) => !item.is_duplicate).map((item) => item.key), [items]);
    const [selectedKeys, setSelectedKeys] = useState<string[]>(initial);

    const selectedCount = selectedKeys.length;

    const toggle = (key: string, checked: boolean): void => {
        setSelectedKeys((prev) => checked ? Array.from(new Set([...prev, key])) : prev.filter((itemKey) => itemKey !== key));
    };

    const importSelected = (): void => {
        router.post(route('editor.news-sources.import.store', source.id), { selected_keys: selectedKeys });
    };

    return (
        <EditorLayout>
            <Head title={t('Import from source')} />
            <AdminPageHeader title={`${t('Import from source')}: ${source.name}`} description={t('Preview feed')} />

            <Card>
                <CardContent className="space-y-2 pt-6 text-sm text-slate-700">
                    <p><strong>{t('Type')}:</strong> {source.type}</p>
                    <p><strong>{t('Feed URL')}:</strong> {source.feed_url || '-'}</p>
                    <p><strong>{t('Default category')}:</strong> {source.default_category || '-'}</p>
                    <p><strong>{t('Default location')}:</strong> {source.default_location || '-'}</p>
                    <p><strong>{t('Source language')}:</strong> {source.language || '-'}</p>
                    <p><strong>{t('Last checked at')}:</strong> {formatDateTime(source.last_checked_at)}</p>
                    <p><strong>{t('Last imported at')}:</strong> {formatDateTime(source.last_imported_at)}</p>
                    <div className="pt-2">
                        <Button asChild variant="secondary"><Link href={route('editor.news-sources.index')}>{t('Back')}</Link></Button>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="pt-6">
                    {items.length === 0 ? <p className="text-slate-500">{t('No feed items found')}</p> : (
                        <>
                            <div className="mb-4 flex flex-wrap items-center gap-2">
                                <Button type="button" variant="outline" onClick={() => setSelectedKeys(items.filter((item) => !item.is_duplicate).map((item) => item.key))}>{t('Select all new')}</Button>
                                <Button type="button" variant="secondary" onClick={() => setSelectedKeys([])}>{t('Clear selection')}</Button>
                                <span className="text-sm text-slate-600">{t('Selected items')}: {selectedCount}</span>
                                <Button type="button" onClick={importSelected} disabled={selectedCount === 0}>{t('Import selected')}</Button>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[960px] text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-200 text-slate-500">
                                            <th className="px-2 pb-3"></th>
                                            <th className="px-2 pb-3">{t('Title')}</th>
                                            <th className="px-2 pb-3">{t('Description')}</th>
                                            <th className="px-2 pb-3">{t('Source')}</th>
                                            <th className="px-2 pb-3">{t('Author')}</th>
                                            <th className="px-2 pb-3">{t('Published at')}</th>
                                            <th className="px-2 pb-3">{t('Status')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.map((item) => {
                                            const checked = selectedKeys.includes(item.key);

                                            return (
                                                <tr key={item.key} className="border-b border-slate-100 align-top">
                                                    <td className="px-2 py-3">
                                                        <input type="checkbox" checked={checked} disabled={item.is_duplicate} onChange={(event) => toggle(item.key, event.target.checked)} />
                                                    </td>
                                                    <td className="px-2 py-3 font-medium">{item.title}</td>
                                                    <td className="px-2 py-3">{item.summary || '-'}</td>
                                                    <td className="px-2 py-3">{item.source_url ? <a href={item.source_url} className="text-blue-600 underline" target="_blank" rel="noreferrer">{t('Open original')}</a> : '-'}</td>
                                                    <td className="px-2 py-3">{item.author || '-'}</td>
                                                    <td className="px-2 py-3">{formatDateTime(item.published_at)}</td>
                                                    <td className="px-2 py-3">
                                                        {item.is_duplicate ? <span className="rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-700">{t('Duplicate')} ({item.duplicate_reason || t('Skipped duplicate')})</span> : <span className="rounded bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">{t('New item')}</span>}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </>
                    )}
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
