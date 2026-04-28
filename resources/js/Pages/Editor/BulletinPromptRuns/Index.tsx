import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import UserAvatar from '@/Components/UserAvatar';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Item {
    id: number;
    title: string;
    status: string;
    scheduled_for: string | null;
    updated_at: string;
    generated_prompt: string | null;
    ai_response_text: string | null;
    script_id: number | null;
    parsed_response?: { warnings?: string[] } | null;
    bulletin_type?: { id: number; name: string } | null;
    prompt_profile?: { id: number; name: string } | null;
    created_by?: { id: number; name: string; email: string | null; avatar_url?: string | null; initials?: string | null } | null;
}

interface Props {
    runs: { data: Item[]; links: Array<{ url: string | null; label: string; active: boolean }> };
    filters: Record<string, string | boolean>;
    statuses: string[];
    bulletinTypes: Array<{ id: number; name: string }>;
    promptProfiles: Array<{ id: number; name: string }>;
    users: Array<{ id: number; name: string }>;
}

const initialFilters = {
    search: '',
    status: '',
    bulletin_type_id: '',
    prompt_profile_id: '',
    created_by: '',
    scheduled_from: '',
    scheduled_to: '',
    show_archived: false,
    only_archived: false,
    sort: 'updated_at',
    direction: 'desc',
};

export default function Index({ runs, filters, statuses, bulletinTypes, promptProfiles, users }: Props): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const [form, setForm] = useState({ ...initialFilters, ...filters });

    const applyFilters = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        router.get(route('editor.bulletin-prompt-runs.index'), form, { preserveState: true, preserveScroll: true });
    };

    const clearFilters = (): void => {
        setForm(initialFilters);
        router.get(route('editor.bulletin-prompt-runs.index'), {}, { preserveState: true, preserveScroll: true });
    };

    const postAction = (action: string, id: number): void => {
        router.post(route(`editor.bulletin-prompt-runs.${action}`, id), {}, { preserveScroll: true });
    };

    return <EditorLayout>
        <Head title={t('Prompt Runs')} />
        <AdminPageHeader title={t('Prompt Runs')} description={t('External AI workflow')} />

        <Card>
            <CardContent className="pt-6">
                <form onSubmit={applyFilters} className="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                    <Input value={String(form.search ?? '')} onChange={(e) => setForm((p) => ({ ...p, search: e.target.value }))} placeholder={t('Search')} />
                    <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={String(form.status ?? '')} onChange={(e) => setForm((p) => ({ ...p, status: e.target.value }))}>
                        <option value="">{t('Filter by status')}</option>
                        {statuses.map((status) => <option key={status} value={status}>{status}</option>)}
                    </select>
                    <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={String(form.bulletin_type_id ?? '')} onChange={(e) => setForm((p) => ({ ...p, bulletin_type_id: e.target.value }))}>
                        <option value="">{t('Filter by bulletin type')}</option>
                        {bulletinTypes.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                    </select>
                    <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={String(form.prompt_profile_id ?? '')} onChange={(e) => setForm((p) => ({ ...p, prompt_profile_id: e.target.value }))}>
                        <option value="">{t('Filter by prompt profile')}</option>
                        {promptProfiles.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                    </select>
                    <select className="rounded-md border border-slate-300 px-3 py-2 text-sm" value={String(form.created_by ?? '')} onChange={(e) => setForm((p) => ({ ...p, created_by: e.target.value }))}>
                        <option value="">{t('Filter by creator')}</option>
                        {users.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                    </select>
                    <Input type="date" value={String(form.scheduled_from ?? '')} onChange={(e) => setForm((p) => ({ ...p, scheduled_from: e.target.value }))} placeholder={t('Scheduled from')} />
                    <Input type="date" value={String(form.scheduled_to ?? '')} onChange={(e) => setForm((p) => ({ ...p, scheduled_to: e.target.value }))} placeholder={t('Scheduled to')} />
                    <div className="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <label className="flex items-center gap-2"><input type="checkbox" checked={Boolean(form.show_archived)} onChange={(e) => setForm((p) => ({ ...p, show_archived: e.target.checked }))} /> {t('Show archived')}</label>
                        <label className="flex items-center gap-2"><input type="checkbox" checked={Boolean(form.only_archived)} onChange={(e) => setForm((p) => ({ ...p, only_archived: e.target.checked, show_archived: e.target.checked ? true : Boolean(p.show_archived) }))} /> {t('Only archived')}</label>
                    </div>
                    <div className="flex gap-2 lg:col-span-4">
                        <Button type="submit">{t('Apply filters')}</Button>
                        <Button type="button" variant="secondary" onClick={clearFilters}>{t('Clear filters')}</Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardContent className="overflow-x-auto pt-6">
                <table className="w-full min-w-[1100px] text-sm"><thead><tr className="border-b">
                    <th className="px-2 pb-3 text-left">{t('Title')}</th>
                    <th className="px-2 pb-3 text-left">{t('Bulletin Type')}</th>
                    <th className="px-2 pb-3 text-left">{t('Prompt Profile')}</th>
                    <th className="px-2 pb-3 text-left">{t('Status')}</th>
                    <th className="px-2 pb-3 text-left">{t('Scheduled for')}</th>
                    <th className="px-2 pb-3 text-left">{t('Response status')}</th>
                    <th className="px-2 pb-3 text-left">{t('Created by')}</th>
                    <th className="px-2 pb-3 text-left">{t('Updated at')}</th>
                    <th className="px-2 pb-3 text-right">{t('Actions')}</th>
                </tr></thead><tbody>
                    {runs.data.length ? runs.data.map((item) => <tr className="border-b" key={item.id}>
                        <td className="px-2 py-3"><div className="font-medium">{item.title}</div></td>
                        <td className="px-2 py-3">{item.bulletin_type?.name ?? '-'}</td>
                        <td className="px-2 py-3">{item.prompt_profile?.name ?? '-'}</td>
                        <td className="px-2 py-3"><StatusBadge status={item.status} /></td>
                        <td className="px-2 py-3">{formatDateTime(item.scheduled_for)}</td>
                        <td className="px-2 py-3">
                            <div className="space-y-1">
                                <p>{item.ai_response_text ? t('Response saved') : t('No response')}</p>
                                <p className="text-xs text-slate-500">{item.parsed_response ? t('Parsed response available') : t('No parsed response')}</p>
                                {(item.parsed_response?.warnings?.length ?? 0) > 0 && (
                                    <p className="text-xs text-amber-700">{t('Parser warnings')}: {item.parsed_response?.warnings?.length}</p>
                                )}
                            </div>
                        </td>
                        <td className="px-2 py-3">{item.created_by ? <div className="flex items-center gap-2"><UserAvatar user={item.created_by} size="sm" /><span>{item.created_by.name}</span></div> : '-'}</td>
                        <td className="px-2 py-3">{formatDateTime(item.updated_at)}</td>
                        <td className="px-2 py-3 text-right"><div className="flex flex-wrap justify-end gap-2">
                            <Button asChild size="sm" variant="outline"><Link href={route('editor.bulletin-prompt-runs.show', item.id)}>{t('Open')}</Link></Button>
                            {item.status === 'draft' && <Button size="sm" onClick={() => postAction('generate-prompt', item.id)}>{t('Generate Prompt')}</Button>}
                            {item.status === 'prompt_ready' && <Button asChild size="sm" variant="secondary"><Link href={route('editor.bulletin-prompt-runs.show', item.id)}>{t('Save AI Response')}</Link></Button>}
                            {item.status === 'response_received' && <Button size="sm" variant="secondary" onClick={() => postAction('create-script', item.id)}>{t('Create Script')}</Button>}
                            {item.status !== 'archived' ? <Button size="sm" variant="outline" onClick={() => postAction('archive', item.id)}>{t('Archive')}</Button> : <Button size="sm" variant="outline" onClick={() => postAction('restore', item.id)}>{t('Restore')}</Button>}
                            {['response_received', 'script_created'].includes(item.status) && <Button size="sm" variant="outline" onClick={() => postAction('mark-completed', item.id)}>{t('Mark completed')}</Button>}
                            {['draft', 'prompt_ready'].includes(item.status) && !item.script_id && <Button size="sm" variant="outline" onClick={() => postAction('cancel', item.id)}>{t('Cancel')}</Button>}
                        </div></td>
                    </tr>) : <tr><td colSpan={9} className="px-2 py-6 text-center">{Boolean(form.only_archived) ? t('No archived records found') : t('No prompt runs found')}</td></tr>}
                </tbody></table><Pagination links={runs.links} />
            </CardContent>
        </Card>
    </EditorLayout>;
}
