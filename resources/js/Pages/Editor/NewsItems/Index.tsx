import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { countryCodeToFlagEmoji } from '@/lib/flags';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';

interface Item {
    id: number;
    title: string;
    source: string | null;
    source_id: number | null;
    category: string | null;
    category_id: number | null;
    category_color: string | null;
    location: { name: string; country_code: string | null; type?: string | null } | null;
    status: string;
    published_at: string | null;
    editorial_priority: number;
}

interface OptionItem {
    id: number;
    name?: string;
    display_name?: string;
    type?: string;
    color?: string | null;
}

interface Props {
    newsItems: { data: Item[]; links: Array<{ url: string | null; label: string; active: boolean }> };
    filters: Record<string, string>;
    statuses: string[];
    languages: Array<{ id:number; code:string; name:string; native_name:string | null; flag_emoji:string | null }>;
    priorities: number[];
    sources: OptionItem[];
    categories: OptionItem[];
    locations: OptionItem[];
}

const initialFilters = {
    search: '',
    status: '',
    news_source_id: '',
    news_category_id: '',
    location_id: '',
    language: '',
    editorial_priority: '',
    published_from: '',
    published_to: '',
    sort: 'published_at',
    direction: 'desc',
    show_archived: '',
};

export default function Index({ newsItems, filters, statuses, languages, priorities, sources, categories, locations }: Props): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const [form, setForm] = useState({ ...initialFilters, ...filters });

    const priorityClasses = useMemo<Record<number, string>>(
        () => ({
            1: 'bg-emerald-100 text-emerald-700',
            2: 'bg-cyan-100 text-cyan-700',
            3: 'bg-slate-100 text-slate-700',
            4: 'bg-amber-100 text-amber-800',
            5: 'bg-rose-100 text-rose-700',
        }),
        [],
    );

    const onSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        router.get(route('editor.news-items.index'), form, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const onReset = (): void => {
        setForm(initialFilters);

        router.get(route('editor.news-items.index'), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const postAction = (action: string, id: number): void => {
        router.post(route(`editor.news-items.${action}`, id), {}, { preserveScroll: true });
    };

    return (
        <EditorLayout>
            <Head title={t('News Items')} />

            <AdminPageHeader
                title={t('News Items')}
                description="Manage collected and manual news."
                actionLabel={t('Create')}
                actionHref={route('editor.news-items.create')}
            />

            <Card>
                <CardContent className="pt-6">
                    <h2 className="mb-4 text-base font-semibold text-slate-700">{t('Filters')}</h2>

                    <form onSubmit={onSubmit} className="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <Input value={form.search} onChange={(e) => setForm((prev) => ({ ...prev, search: e.target.value }))} placeholder={t('Search')} />

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.status} onChange={(e) => setForm((prev) => ({ ...prev, status: e.target.value }))}>
                            <option value="">{t('All statuses')}</option>
                            {statuses.map((status) => (
                                <option key={status} value={status}>{status}</option>
                            ))}
                        </select>

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.news_source_id} onChange={(e) => setForm((prev) => ({ ...prev, news_source_id: e.target.value }))}>
                            <option value="">{t('All sources')}</option>
                            {sources.map((source) => (
                                <option key={source.id} value={source.id}>{source.name}</option>
                            ))}
                        </select>

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.news_category_id} onChange={(e) => setForm((prev) => ({ ...prev, news_category_id: e.target.value }))}>
                            <option value="">{t('All categories')}</option>
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>{category.display_name}</option>
                            ))}
                        </select>

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.location_id} onChange={(e) => setForm((prev) => ({ ...prev, location_id: e.target.value }))}>
                            <option value="">{t('All locations')}</option>
                            {locations.map((location) => (
                                <option key={location.id} value={location.id}>{location.name}</option>
                            ))}
                        </select>

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.language} onChange={(e) => setForm((prev) => ({ ...prev, language: e.target.value }))}>
                            <option value="">{t('All languages')}</option>
                            {languages.map((language) => (
                                <option key={language.id} value={language.code}>{language.flag_emoji} {language.native_name || language.name} ({language.code})</option>
                            ))}
                        </select>

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.editorial_priority} onChange={(e) => setForm((prev) => ({ ...prev, editorial_priority: e.target.value }))}>
                            <option value="">{t('All priorities')}</option>
                            {priorities.map((priority) => (
                                <option key={priority} value={priority}>{priority}</option>
                            ))}
                        </select>

                        <Input type="date" value={form.published_from} onChange={(e) => setForm((prev) => ({ ...prev, published_from: e.target.value }))} placeholder={t('Published from')} />
                        <Input type="date" value={form.published_to} onChange={(e) => setForm((prev) => ({ ...prev, published_to: e.target.value }))} placeholder={t('Published to')} />

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.sort} onChange={(e) => setForm((prev) => ({ ...prev, sort: e.target.value }))}>
                            <option value="published_at">{t('Sort by')} Published at</option>
                            <option value="title">{t('Sort by')} {t('Title')}</option>
                            <option value="status">{t('Sort by')} {t('Status')}</option>
                            <option value="editorial_priority">{t('Sort by')} {t('Priority')}</option>
                            <option value="collected_at">{t('Sort by')} Collected at</option>
                            <option value="created_at">{t('Sort by')} {t('Created at')}</option>
                        </select>

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.direction} onChange={(e) => setForm((prev) => ({ ...prev, direction: e.target.value }))}>
                            <option value="desc">{t('Descending')}</option>
                            <option value="asc">{t('Ascending')}</option>
                        </select>

                        <label className="flex items-center gap-2 rounded-md border border-slate-300 px-3 py-2 text-sm"><input type="checkbox" checked={form.show_archived === '1'} onChange={(e) => setForm((prev) => ({ ...prev, show_archived: e.target.checked ? '1' : '' }))} /> {t('Show archived')}</label>

                        <div className="flex gap-2 lg:col-span-4">
                            <Button type="submit">{t('Apply filters')}</Button>
                            <Button type="button" variant="secondary" onClick={onReset}>{t('Reset filters')}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="overflow-x-auto pt-6">
                    <table className="w-full min-w-[980px] text-sm">
                        <thead>
                            <tr className="border-b border-slate-200 text-slate-500">
                                <th className="px-2 pb-3">{t('Title')}</th>
                                <th className="px-2 pb-3">{t('Source')}</th>
                                <th className="px-2 pb-3">{t('Category')}</th>
                                <th className="px-2 pb-3">{t('Location')}</th>
                                <th className="px-2 pb-3">{t('Status')}</th>
                                <th className="px-2 pb-3">{t('Published at')}</th>
                                <th className="px-2 pb-3">{t('Priority')}</th>
                                <th className="px-2 pb-3 text-right">{t('Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {newsItems.data.length ? (
                                newsItems.data.map((item) => (
                                    <tr key={item.id} className="border-b border-slate-100">
                                        <td className="px-2 py-3 font-medium">{item.title}</td>
                                        <td className="px-2 py-3">{item.source || '-'}</td>
                                        <td className="px-2 py-3">
                                            <span className="inline-flex items-center gap-2">
                                                {item.category_color ? <span className="h-3 w-3 rounded-full border" style={{ backgroundColor: item.category_color }} /> : null}
                                                {item.category || '-'}
                                            </span>
                                        </td>
                                        <td className="px-2 py-3">{countryCodeToFlagEmoji(item.location?.country_code)} {item.location?.name || '-'}</td>
                                        <td className="px-2 py-3"><StatusBadge status={item.status} /></td>
                                        <td className="px-2 py-3">{formatDateTime(item.published_at)}</td>
                                        <td className="px-2 py-3">
                                            <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${priorityClasses[item.editorial_priority] || 'bg-slate-100 text-slate-700'}`}>
                                                {item.editorial_priority}
                                            </span>
                                        </td>
                                        <td className="px-2 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Button asChild size="sm" variant="outline"><Link href={route('editor.news-items.show', item.id)}>{t('View')}</Link></Button>
                                                <Button asChild size="sm" variant="secondary"><Link href={route('editor.news-items.edit', item.id)}>{t('Edit')}</Link></Button>
                                                {item.status !== 'archived'
                                                    ? <Button size="sm" variant="outline" onClick={() => postAction('archive', item.id)}>{t('Archive')}</Button>
                                                    : <Button size="sm" variant="outline" onClick={() => postAction('restore', item.id)}>{t('Restore')}</Button>}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={8} className="px-2 py-6 text-center text-slate-500">{t('No news items found')}</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                    <Pagination links={newsItems.links} />
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
