import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
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
    edition_type: string;
    location: { name: string; country_code: string | null } | null;
    scheduled_for: string | null;
    language: string | null;
    language_display?: { code:string; name:string; native_name:string | null; flag_emoji:string | null } | null;
    status: string;
    target_duration_seconds: number | null;
    news_items_count: number;
    scripts_count: number;
}

interface Props {
    editions: { data: Item[]; links: Array<{ url: string | null; label: string; active: boolean }> };
    filters: Record<string, string>;
    statuses: string[];
    editionTypes: string[];
    languages: Array<{ id:number; code:string; name:string; native_name:string | null; flag_emoji:string | null }>;
    locations: Array<{ id: number; name: string }>;
}

const initialFilters = {
    search: '',
    status: '',
    edition_type: '',
    location_id: '',
    language: '',
    scheduled_from: '',
    scheduled_to: '',
    sort: 'scheduled_for',
    direction: 'desc',
    show_archived: '',
};

export default function Index({ editions, filters, statuses, editionTypes, languages, locations }: Props): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const [form, setForm] = useState({ ...initialFilters, ...filters });

    const statusClasses = useMemo<Record<string, string>>(
        () => ({
            draft: 'bg-slate-100 text-slate-700',
            planning: 'bg-blue-100 text-blue-700',
            scripting: 'bg-cyan-100 text-cyan-700',
            approved: 'bg-emerald-100 text-emerald-700',
            archived: 'bg-amber-100 text-amber-800',
        }),
        [],
    );

    const typeClasses = useMemo<Record<string, string>>(
        () => ({
            morning: 'bg-yellow-100 text-yellow-800',
            afternoon: 'bg-orange-100 text-orange-700',
            night: 'bg-indigo-100 text-indigo-700',
            special: 'bg-fuchsia-100 text-fuchsia-700',
        }),
        [],
    );

    const onSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        router.get(route('editor.editions.index'), form, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const onReset = (): void => {
        setForm(initialFilters);

        router.get(route('editor.editions.index'), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const destroy = (id: number): void => {
        if (window.confirm('Delete this edition?')) {
            router.delete(route('editor.editions.destroy', id));
        }
    };

    return (
        <EditorLayout>
            <Head title={t('Editions')} />

            <AdminPageHeader
                title={t('Editions')}
                description="Plan bulletin editions."
                actionLabel={t('Create edition')}
                actionHref={route('editor.editions.create')}
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

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.edition_type} onChange={(e) => setForm((prev) => ({ ...prev, edition_type: e.target.value }))}>
                            <option value="">{t('All types')}</option>
                            {editionTypes.map((type) => (
                                <option key={type} value={type}>{type}</option>
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

                        <Input type="date" value={form.scheduled_from} onChange={(e) => setForm((prev) => ({ ...prev, scheduled_from: e.target.value }))} placeholder={t('Scheduled from')} />
                        <Input type="date" value={form.scheduled_to} onChange={(e) => setForm((prev) => ({ ...prev, scheduled_to: e.target.value }))} placeholder={t('Scheduled to')} />

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.sort} onChange={(e) => setForm((prev) => ({ ...prev, sort: e.target.value }))}>
                            <option value="scheduled_for">{t('Sort by')} {t('Scheduled for')}</option>
                            <option value="title">{t('Sort by')} {t('Title')}</option>
                            <option value="status">{t('Sort by')} {t('Status')}</option>
                            <option value="edition_type">{t('Sort by')} {t('Edition type')}</option>
                            <option value="target_duration_seconds">{t('Sort by')} {t('Target duration')}</option>
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
                                <th className="px-2 pb-3">{t('Type')}</th>
                                <th className="px-2 pb-3">{t('Location')}</th>
                                <th className="px-2 pb-3">{t('Scheduled for')}</th>
                                <th className="px-2 pb-3">{t('Language')}</th>
                                <th className="px-2 pb-3">{t('Status')}</th>
                                <th className="px-2 pb-3">{t('Target duration')}</th>
                                <th className="px-2 pb-3">{t('Selected news')}</th>
                                <th className="px-2 pb-3">{t('Scripts count')}</th>
                                <th className="px-2 pb-3 text-right">{t('Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {editions.data.length ? (
                                editions.data.map((item) => (
                                    <tr key={item.id} className="border-b border-slate-100">
                                        <td className="px-2 py-3 font-medium">{item.title}</td>
                                        <td className="px-2 py-3"><span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${typeClasses[item.edition_type] || 'bg-slate-100 text-slate-700'}`}>{item.edition_type}</span></td>
                                        <td className="px-2 py-3">{countryCodeToFlagEmoji(item.location?.country_code)} {item.location?.name || '-'}</td>
                                        <td className="px-2 py-3">{formatDateTime(item.scheduled_for)}</td>
                                        <td className="px-2 py-3">{item.language_display ? `${item.language_display.flag_emoji ?? ""} ${item.language_display.native_name || item.language_display.name} (${item.language_display.code})` : item.language || '-'}</td>
                                        <td className="px-2 py-3"><span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${statusClasses[item.status] || 'bg-slate-100 text-slate-700'}`}>{item.status}</span></td>
                                        <td className="px-2 py-3">{item.target_duration_seconds || '-'}</td>
                                        <td className="px-2 py-3">{item.news_items_count}</td>
                                        <td className="px-2 py-3">{item.scripts_count}</td>
                                        <td className="px-2 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Button asChild size="sm" variant="outline"><Link href={route('editor.editions.show', item.id)}>{t('View')}</Link></Button>
                                                <Button asChild size="sm" variant="secondary"><Link href={route('editor.editions.edit', item.id)}>{t('Edit')}</Link></Button>
                                                <Button size="sm" variant="destructive" onClick={() => destroy(item.id)}>{t('Delete')}</Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={10} className="px-2 py-6 text-center text-slate-500">{t('No editions found')}</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                    <Pagination links={editions.links} />
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
