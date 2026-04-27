import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';

interface Script {
    id: number;
    title: string;
    edition: { id: number; title: string } | null;
    status: string;
    review_status: string;
    language: string | null;
    intro: string | null;
    body: string | null;
    outro: string | null;
    estimated_duration_seconds: number | null;
    reviewed_at: string | null;
    reviewed_by: string | null;
    approved_at: string | null;
    approved_by: string | null;
    rejected_at: string | null;
    rejected_by: string | null;
    rejection_reason: string | null;
    review_items_count: number;
    source_summary: { total: number; verified: number; pending: number; weak: number; issues: number };
}

interface Props {
    script: Script;
}

export default function Show({ script }: Props): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();

    return (
        <EditorLayout>
            <Head title={script.title} />
            <AdminPageHeader title={script.title} description="Script detail." />
            <Card>
                <CardContent className="space-y-3 pt-6 text-sm">
                    <p><strong>{t('Editions')}:</strong> {script.edition?.title || '-'}</p>
                    <p><strong>{t('Status')}:</strong> {script.status}</p>
                    <p><strong>{t('Review status')}:</strong> {script.review_status}</p>
                    <p><strong>{t('Language')}:</strong> {script.language || '-'}</p>
                    <p><strong>{t('Intro')}:</strong> {script.intro || '-'}</p>
                    <p><strong>{t('Body')}:</strong> {script.body || '-'}</p>
                    <p><strong>{t('Outro')}:</strong> {script.outro || '-'}</p>
                    <p><strong>{t('Estimated Duration')}:</strong> {script.estimated_duration_seconds || '-'} seconds</p>
                    <p><strong>{t('Reviewed by')}:</strong> {script.reviewed_by || '-'}</p>
                    <p><strong>{t('Reviewed at')}:</strong> {formatDateTime(script.reviewed_at)}</p>
                    <p><strong>{t('Approved by')}:</strong> {script.approved_by || '-'}</p>
                    <p><strong>{t('Approved at')}:</strong> {formatDateTime(script.approved_at)}</p>
                    <p><strong>{t('Rejected by')}:</strong> {script.rejected_by || '-'}</p>
                    <p><strong>{t('Rejected at')}:</strong> {formatDateTime(script.rejected_at)}</p>
                    <p><strong>{t('Rejection reason')}:</strong> {script.rejection_reason || '-'}</p>
                    <p><strong>{t('Total review items')}:</strong> {script.review_items_count}</p>
                    <div className="rounded border border-slate-200 bg-slate-50 p-3">
                        <p><strong>{t('Source verification')}:</strong></p>
                        <p>{t('Source References')}: {script.source_summary.total}</p>
                        <p>{t('Verified')}: {script.source_summary.verified} · {t('Pending')}: {script.source_summary.pending} · {t('Weak source')}: {script.source_summary.weak}</p>
                        <p>{t('Missing sources')}: {script.source_summary.issues}</p>
                        {script.source_summary.issues > 0 ? <p className="text-rose-700">{t('This script has unresolved source issues')}</p> : null}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="secondary"><Link href={route('editor.scripts.index')}>{t('Back')}</Link></Button>
                        <Button asChild variant="outline"><Link href={route('editor.scripts.review', script.id)}>{t('Review')}</Link></Button>
                        <Button variant="outline" onClick={() => router.post(route('editor.scripts.source-references.extract', script.id))}>{t('Extract sources')}</Button>
                        <Button asChild variant="outline"><Link href={route('editor.source-references.index', { script_id: script.id })}>{t('Manage sources')}</Link></Button>
                    </div>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
