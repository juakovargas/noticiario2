import AdminPageHeader from '@/Components/AdminPageHeader';
import UserIdentity from '@/Components/UserIdentity';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

interface ReviewItem {
    id: number;
    sort_order: number;
    type: string;
    title: string | null;
    content: string | null;
    source_hints: string[];
    verification_status: string;
    verification_notes: string | null;
    required_action: string | null;
    reviewed_at: string | null;
    reviewed_by: { id: number; name: string; email: string | null; avatar_url?: string | null; initials?: string | null } | null;
    news_item: { id: number; title: string } | null;
    source_references: Array<{ id: number; title: string | null; source_name: string | null; verification_status: string }>;
}

interface ScriptData {
    id: number;
    title: string;
    status: string;
    review_status: string;
    review_notes: string | null;
    fact_check_notes: string | null;
    rejection_reason: string | null;
    language: string | null;
    estimated_duration_seconds: number | null;
    edition: { id: number; title: string } | null;
    reviewed_at: string | null;
    approved_at: string | null;
    rejected_at: string | null;
    reviewed_by: { id: number; name: string; email: string | null; avatar_url?: string | null; initials?: string | null } | null;
    approved_by: { id: number; name: string; email: string | null; avatar_url?: string | null; initials?: string | null } | null;
    rejected_by: { id: number; name: string; email: string | null; avatar_url?: string | null; initials?: string | null } | null;
    review_items: ReviewItem[];
    source_summary: { total: number; missing: number };
}

interface Props {
    script: ScriptData;
    reviewStatuses: string[];
    verificationStatuses: string[];
    requiredActions: string[];
}

const statusClass: Record<string, string> = {
    pending: 'bg-slate-100 text-slate-700',
    in_review: 'bg-blue-100 text-blue-700',
    needs_sources: 'bg-amber-100 text-amber-800',
    needs_source: 'bg-amber-100 text-amber-800',
    needs_changes: 'bg-orange-100 text-orange-800',
    verified: 'bg-emerald-100 text-emerald-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-rose-100 text-rose-700',
    not_applicable: 'bg-slate-200 text-slate-700',
};

function statusLabel(value: string, t: (key: string) => string): string {
    const map: Record<string, string> = {
        pending: t('Pending review'),
        in_review: t('In review'),
        needs_sources: t('Needs sources'),
        needs_source: t('Needs source'),
        needs_changes: t('Needs changes'),
        verified: t('Verified'),
        approved: t('Approved'),
        rejected: t('Rejected'),
        not_applicable: t('Not applicable'),
        add_source: t('Add source'),
        rewrite: t('Rewrite'),
        remove: t('Remove'),
        check_date: t('Check date'),
        check_claim: t('Check claim'),
        none: t('No action'),
    };

    return map[value] ?? value;
}

export default function Review({ script, reviewStatuses, verificationStatuses, requiredActions }: Props): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();

    const reviewForm = useForm({
        review_status: script.review_status,
        review_notes: script.review_notes ?? '',
        fact_check_notes: script.fact_check_notes ?? '',
    });

    const rejectForm = useForm({ rejection_reason: script.rejection_reason ?? '' });

    const counts = {
        total: script.review_items.length,
        pending: script.review_items.filter((item) => item.verification_status === 'pending').length,
        verified: script.review_items.filter((item) => item.verification_status === 'verified').length,
        needs_source: script.review_items.filter((item) => item.verification_status === 'needs_source').length,
        needs_changes: script.review_items.filter((item) => item.verification_status === 'needs_changes').length,
        rejected: script.review_items.filter((item) => item.verification_status === 'rejected').length,
    };

    return (
        <EditorLayout>
            <Head title={`${t('Script Review')}: ${script.title}`} />
            <AdminPageHeader helpKey="editor.scripts.review" title={script.title} description={t('Script Review')} />

            <Card className="mb-4">
                <CardContent className="grid gap-3 pt-6 text-sm md:grid-cols-2 lg:grid-cols-4">
                    <p><strong>{t('Editions')}:</strong> {script.edition?.title ?? '-'}</p>
                    <p><strong>{t('Status')}:</strong> {script.status}</p>
                    <p><strong>{t('Review status')}:</strong> <Badge className={statusClass[script.review_status]}>{statusLabel(script.review_status, t)}</Badge></p>
                    <p><strong>{t('Language')}:</strong> {script.language ?? '-'}</p>
                    <p><strong>{t('Estimated Duration')}:</strong> {script.estimated_duration_seconds ?? '-'}</p>
                    <div><strong>{t('Reviewed by')}:</strong> {script.reviewed_by ? <UserIdentity user={script.reviewed_by} subtitle={script.reviewed_by.email} avatarSize="xs" className="inline-flex ml-2" /> : '-'}</div>
                    <p><strong>{t('Reviewed at')}:</strong> {formatDateTime(script.reviewed_at)}</p>
                    <p><strong>{t('Approved at')}:</strong> {formatDateTime(script.approved_at)}</p>
                    <div className="md:col-span-2 flex flex-wrap gap-2">
                        <Button asChild variant="outline"><Link href={route('editor.scripts.show', script.id)}>{t('Back to script')}</Link></Button>
                        <Button asChild variant="secondary"><Link href={route('editor.scripts.edit', script.id)}>{t('Edit script')}</Link></Button>
                    </div>
                </CardContent>
            </Card>

            <div className="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <Card><CardHeader><CardTitle className="text-sm">{t('Total review items')}</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{counts.total}</CardContent></Card>
                <Card><CardHeader><CardTitle className="text-sm">{t('Pending review')}</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{counts.pending}</CardContent></Card>
                <Card><CardHeader><CardTitle className="text-sm">{t('Verified')}</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{counts.verified}</CardContent></Card>
                <Card><CardHeader><CardTitle className="text-sm">{t('Needs source')}</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{counts.needs_source}</CardContent></Card>
                <Card><CardHeader><CardTitle className="text-sm">{t('Needs changes')}</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{counts.needs_changes}</CardContent></Card>
                <Card><CardHeader><CardTitle className="text-sm">{t('Rejected')}</CardTitle></CardHeader><CardContent className="text-2xl font-bold">{counts.rejected}</CardContent></Card>
            </div>

            <Card className="mb-4">
                <CardHeader><CardTitle>{t('Script Review')}</CardTitle></CardHeader>
                <CardContent>
                    <form
                        className="space-y-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            reviewForm.put(route('editor.scripts.review.update', script.id));
                        }}
                    >
                        <div>
                            <label className="mb-1 block text-sm font-medium">{t('Review status')}</label>
                            <select className="w-full rounded border px-3 py-2" value={reviewForm.data.review_status} onChange={(e) => reviewForm.setData('review_status', e.target.value)}>
                                {reviewStatuses.map((status) => <option key={status} value={status}>{statusLabel(status, t)}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium">{t('Review notes')}</label>
                            <textarea className="w-full rounded border px-3 py-2" rows={3} value={reviewForm.data.review_notes} onChange={(e) => reviewForm.setData('review_notes', e.target.value)} />
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-medium">{t('Fact-check notes')}</label>
                            <textarea className="w-full rounded border px-3 py-2" rows={3} value={reviewForm.data.fact_check_notes} onChange={(e) => reviewForm.setData('fact_check_notes', e.target.value)} />
                        </div>
                        <Button type="submit">{t('Save review notes')}</Button>
                    </form>
                </CardContent>
            </Card>

            <Card className="mb-4">
                <CardHeader><CardTitle>{t('Actions')}</CardTitle></CardHeader>
                <CardContent className="flex flex-wrap gap-2">
                    <Button onClick={() => router.post(route('editor.scripts.review.generate-items', script.id))}>{t('Generate review items')}</Button>
                    <Button variant="outline" onClick={() => router.post(route('editor.scripts.review.mark-in-review', script.id))}>{t('Mark in review')}</Button>
                    <Button variant="outline" onClick={() => router.post(route('editor.scripts.review.mark-verified', script.id))}>{t('Mark verified')}</Button>
                    <Button variant="secondary" onClick={() => router.post(route('editor.scripts.review.approve', script.id))}>{t('Approve script')}</Button>
                    <Button variant="outline" onClick={() => router.post(route('editor.scripts.source-references.extract', script.id))}>{t('Extract source references')}</Button>
                </CardContent>
            </Card>
            {script.source_summary.missing > 0 ? <p className="mb-4 text-sm text-rose-700">{t('This script has unresolved source issues')}</p> : null}

            <Card className="mb-4">
                <CardHeader><CardTitle>{t('Reject script')}</CardTitle></CardHeader>
                <CardContent>
                    <form
                        className="space-y-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            rejectForm.post(route('editor.scripts.review.reject', script.id));
                        }}
                    >
                        <label className="mb-1 block text-sm font-medium">{t('Rejection reason')}</label>
                        <textarea className="w-full rounded border px-3 py-2" rows={3} value={rejectForm.data.rejection_reason} onChange={(e) => rejectForm.setData('rejection_reason', e.target.value)} />
                        <Button type="submit" variant="destructive">{t('Reject script')}</Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>{t('Review items')}</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                    {script.review_items.length === 0 ? (
                        <div className="rounded border border-dashed p-6 text-center text-sm text-slate-600">
                            <p>{t('No review items yet')}</p>
                            <p className="mt-1">{t('Generate review items from script')}</p>
                        </div>
                    ) : (
                        script.review_items.map((item) => (
                            <ReviewItemCard key={item.id} scriptId={script.id} item={item} verificationStatuses={verificationStatuses} requiredActions={requiredActions} />
                        ))
                    )}
                </CardContent>
            </Card>
        </EditorLayout>
    );
}

function ReviewItemCard({ scriptId, item, verificationStatuses, requiredActions }: { scriptId: number; item: ReviewItem; verificationStatuses: string[]; requiredActions: string[]; }): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const form = useForm({
        verification_status: item.verification_status,
        verification_notes: item.verification_notes ?? '',
        required_action: item.required_action ?? 'none',
    });

    return (
        <div className="rounded border p-4">
            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                <p className="font-medium">#{item.sort_order} · {item.title || '-'}</p>
                <div className="flex gap-2">
                    <Badge variant="outline">{item.type}</Badge>
                    <Badge className={statusClass[form.data.verification_status]}>{statusLabel(form.data.verification_status, t)}</Badge>
                </div>
            </div>
            <p className="mb-2 whitespace-pre-wrap text-sm text-slate-700">{item.content || '-'}</p>
            <p className="text-xs text-slate-500"><strong>{t('Linked news item')}:</strong> {item.news_item?.title ?? '-'}</p>
            <div className="text-xs text-slate-500">
                <strong>{t('Reviewed by')}:</strong> {item.reviewed_by ? <UserIdentity user={item.reviewed_by} subtitle={item.reviewed_by.email} avatarSize="xs" className="inline-flex ml-2" /> : '-'} · <strong>{t('Reviewed at')}:</strong> {formatDateTime(item.reviewed_at)}
            </div>
            <div className="mt-2 text-xs text-slate-600"><strong>{t('Source hints')}:</strong> {item.source_hints.length ? item.source_hints.join(' · ') : '-'}</div>
            <div className="mt-2 text-xs text-slate-600">
                <strong>{t('Source References')}:</strong>{' '}
                {item.source_references.length
                    ? item.source_references.map((reference) => (
                        <Link key={reference.id} className="mr-2 text-cyan-700 underline" href={route('editor.source-references.show', reference.id)}>
                            {reference.title || reference.source_name || `#${reference.id}`} ({reference.verification_status})
                        </Link>
                    ))
                    : '-'}
            </div>
            <div className="mt-2">
                <Button size="sm" variant="outline" onClick={() => router.post(route('editor.script-review-items.source-references.extract', item.id))}>{t('Extract sources')}</Button>
            </div>

            <form className="mt-3 grid gap-3 md:grid-cols-3" onSubmit={(e) => { e.preventDefault(); form.put(route('editor.scripts.review-items.update', [scriptId, item.id])); }}>
                <div>
                    <label className="mb-1 block text-sm font-medium">{t('Verification status')}</label>
                    <select className="w-full rounded border px-3 py-2" value={form.data.verification_status} onChange={(e) => form.setData('verification_status', e.target.value)}>
                        {verificationStatuses.map((status) => <option key={status} value={status}>{statusLabel(status, t)}</option>)}
                    </select>
                </div>
                <div>
                    <label className="mb-1 block text-sm font-medium">{t('Required action')}</label>
                    <select className="w-full rounded border px-3 py-2" value={form.data.required_action ?? 'none'} onChange={(e) => form.setData('required_action', e.target.value)}>
                        {requiredActions.map((action) => <option key={action} value={action}>{statusLabel(action, t)}</option>)}
                    </select>
                </div>
                <div className="md:col-span-3">
                    <label className="mb-1 block text-sm font-medium">{t('Verification notes')}</label>
                    <textarea className="w-full rounded border px-3 py-2" rows={2} value={form.data.verification_notes} onChange={(e) => form.setData('verification_notes', e.target.value)} />
                </div>
                <div className="md:col-span-3">
                    <Button type="submit" size="sm">{t('Save item')}</Button>
                </div>
            </form>
        </div>
    );
}
