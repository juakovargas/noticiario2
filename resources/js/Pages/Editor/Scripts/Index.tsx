import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Item {
    id: number;
    title: string;
    edition: string | null;
    edition_id: number;
    status: string;
    language: string | null;
    language_display?: { code:string; name:string; native_name:string | null; flag_emoji:string | null } | null;
    estimated_duration_seconds: number | null;
    approved_at: string | null;
    review_status: string;
}

interface Props {
    scripts: { data: Item[]; links: Array<{ url: string | null; label: string; active: boolean }> };
    filters: Record<string, string>;
    statuses: string[];
    editions: Array<{ id: number; title: string }>;
    languages: Array<{ id:number; code:string; name:string; native_name:string | null; flag_emoji:string | null }>;
}

const initialFilters = {
    search: '',
    status: '',
    edition_id: '',
    language: '',
    approved: '',
    sort: 'created_at',
    direction: 'desc',
};

export default function Index({ scripts, filters, statuses, editions, languages }: Props): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const [form, setForm] = useState({ ...initialFilters, ...filters });

    const onSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        router.get(route('editor.scripts.index'), form, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const onReset = (): void => {
        setForm(initialFilters);

        router.get(route('editor.scripts.index'), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const destroy = (id: number): void => {
        if (window.confirm('Delete this script?')) {
            router.delete(route('editor.scripts.destroy', id));
        }
    };

    return (
        <EditorLayout>
            <Head title={t('Scripts')} />
            <AdminPageHeader title={t('Scripts')} description="Editorial scripts for editions." actionLabel={t('Create script')} actionHref={route('editor.scripts.create')} />

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

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.edition_id} onChange={(e) => setForm((prev) => ({ ...prev, edition_id: e.target.value }))}>
                            <option value="">{t('Any')}</option>
                            {editions.map((edition) => (
                                <option key={edition.id} value={edition.id}>{edition.title}</option>
                            ))}
                        </select>

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.language} onChange={(e) => setForm((prev) => ({ ...prev, language: e.target.value }))}>
                            <option value="">{t('All languages')}</option>
                            {languages.map((language) => (
                                <option key={language.id} value={language.code}>{language.flag_emoji} {language.native_name || language.name} ({language.code})</option>
                            ))}
                        </select>

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.approved} onChange={(e) => setForm((prev) => ({ ...prev, approved: e.target.value }))}>
                            <option value="">{t('Any')}</option>
                            <option value="yes">{t('Approved')}</option>
                            <option value="no">{t('Not approved')}</option>
                        </select>

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.sort} onChange={(e) => setForm((prev) => ({ ...prev, sort: e.target.value }))}>
                            <option value="created_at">{t('Sort by')} {t('Created at')}</option>
                            <option value="title">{t('Sort by')} {t('Title')}</option>
                            <option value="status">{t('Sort by')} {t('Status')}</option>
                            <option value="language">{t('Sort by')} {t('Language')}</option>
                            <option value="estimated_duration_seconds">{t('Sort by')} {t('Estimated Duration')}</option>
                            <option value="approved_at">{t('Sort by')} {t('Approved')}</option>
                        </select>

                        <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={form.direction} onChange={(e) => setForm((prev) => ({ ...prev, direction: e.target.value }))}>
                            <option value="desc">{t('Descending')}</option>
                            <option value="asc">{t('Ascending')}</option>
                        </select>

                        <div className="flex gap-2 lg:col-span-4">
                            <Button type="submit">{t('Apply filters')}</Button>
                            <Button type="button" variant="secondary" onClick={onReset}>{t('Reset filters')}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="overflow-x-auto pt-6">
                    <table className="w-full min-w-[900px] text-sm">
                        <thead>
                            <tr className="border-b border-slate-200 text-slate-500">
                                <th className="px-2 pb-3">{t('Title')}</th>
                                <th className="px-2 pb-3">{t('Editions')}</th>
                                <th className="px-2 pb-3">{t('Status')}</th>
                                <th className="px-2 pb-3">{t('Language')}</th>
                                <th className="px-2 pb-3">{t('Estimated Duration')}</th>
                                <th className="px-2 pb-3">{t('Review status')}</th>
                                <th className="px-2 pb-3">{t('Approved at')}</th>
                                <th className="px-2 pb-3 text-right">{t('Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {scripts.data.length ? (
                                scripts.data.map((item) => (
                                    <tr key={item.id} className="border-b border-slate-100">
                                        <td className="px-2 py-3 font-medium">{item.title}</td>
                                        <td className="px-2 py-3">{item.edition || '-'}</td>
                                        <td className="px-2 py-3">{item.status}</td>
                                        <td className="px-2 py-3">{item.language_display ? `${item.language_display.flag_emoji ?? ""} ${item.language_display.native_name || item.language_display.name} (${item.language_display.code})` : item.language || '-'}</td>
                                        <td className="px-2 py-3">{item.estimated_duration_seconds || '-'}</td>
                                        <td className="px-2 py-3">{item.review_status}</td>
                                        <td className="px-2 py-3">{item.approved_at ? formatDateTime(item.approved_at) : t('Not approved')}</td>
                                        <td className="px-2 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Button asChild size="sm" variant="outline"><Link href={route('editor.scripts.show', item.id)}>{t('View')}</Link></Button>
                                                <Button asChild size="sm" variant="secondary"><Link href={route('editor.scripts.edit', item.id)}>{t('Edit')}</Link></Button>
                                                <Button asChild size="sm" variant="outline"><Link href={route('editor.scripts.review', item.id)}>{t('Review')}</Link></Button>
                                                <Button size="sm" variant="destructive" onClick={() => destroy(item.id)}>{t('Delete')}</Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={8} className="px-2 py-6 text-center text-slate-500">{t('No scripts found')}</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                    <Pagination links={scripts.links} />
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
