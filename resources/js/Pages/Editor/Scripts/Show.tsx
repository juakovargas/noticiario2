import AdminPageHeader from '@/Components/AdminPageHeader';
import UserIdentity from '@/Components/UserIdentity';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ script }: { script: any }): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();

    const nextAction = script.status === 'archived'
        ? t('Restore to continue working')
        : script.source_summary.issues > 0
          ? t('Verify sources')
          : ['pending', 'in_review', 'needs_sources', 'needs_changes'].includes(script.review_status)
            ? t('Review script')
            : !script.metadata_ready
              ? t('Prepare production metadata')
              : script.production_status === 'ready_for_production'
                ? t('Ready for audio/video')
                : t('Open Script');

    return (
        <EditorLayout>
            <Head title={script.title} />
            <AdminPageHeader helpKey="editor.scripts.show" title={script.title} description={t('Workflow')} />

            <Card className="mb-4"><CardContent className="pt-6"><p><strong>{t('Next action')}:</strong> {nextAction}</p></CardContent></Card>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader><CardTitle>{t('Origin')}</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><strong>{t('Created from Prompt Run')}:</strong> {script.origin?.prompt_run ? <Link className="text-cyan-700 underline" href={route('editor.bulletin-prompt-runs.show', script.origin.prompt_run.id)}>{script.origin.prompt_run.title}</Link> : '-'}</p>
                        <p><strong>{t('Bulletin Type')}:</strong> {script.origin?.bulletin_type?.name || '-'}</p>
                        <p><strong>{t('Prompt Profile')}:</strong> {script.origin?.prompt_profile?.name || '-'}</p>
                        <p><strong>{t('Prompt')}:</strong> {formatDateTime(script.origin?.prompt_generated_at)}</p>
                        <p><strong>{t('Response')}:</strong> {formatDateTime(script.origin?.response_received_at)}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>{t('Editorial content')}</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><strong>{t('Status')}:</strong> {script.status}</p>
                        <p><strong>{t('Review status')}:</strong> {script.review_status}</p>
                        <p><strong>{t('Intro')}:</strong> {script.intro || '-'}</p>
                        <p><strong>{t('Body')}:</strong> {script.body || '-'}</p>
                        <p><strong>{t('Outro')}:</strong> {script.outro || '-'}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>{t('Source verification')}</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>{t('Source References')}: {script.source_summary.total}</p>
                        <p>{t('Unresolved source issues')}: {script.source_summary.issues}</p>
                        <Button asChild size="sm" variant="outline"><Link href={route('editor.scripts.review', script.id)}>{t('Verify sources')}</Link></Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>{t('Production metadata')}</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><strong>{t('Final title')}:</strong> {script.final_title || '-'}</p>
                        <p><strong>{t('Production name')}:</strong> {script.production_name || '-'}</p>
                        <p><strong>{t('Public description')}:</strong> {script.public_description || '-'}</p>
                        <p><strong>{t('Hashtags')}:</strong> {(script.hashtags || []).join(', ') || '-'}</p>
                        <p><strong>{t('Target platforms')}:</strong> {(script.target_platforms || []).join(', ') || '-'}</p>
                        <p><strong>{t('Production status')}:</strong> {script.production_status}</p>
                        <p><strong>{t('Ready for production')}:</strong> {formatDateTime(script.ready_for_production_at)}</p>
                        <div><strong>{t('Created by')}:</strong> {script.ready_for_production_by ? <UserIdentity user={script.ready_for_production_by} subtitle={script.ready_for_production_by.email} avatarSize="xs" className="inline-flex ml-2" /> : '-'}</div>
                    </CardContent>
                </Card>
            </div>

            <div className="mt-4 flex flex-wrap gap-2">
                {script.origin?.prompt_run ? <Button asChild variant="outline"><Link href={route('editor.bulletin-prompt-runs.show', script.origin.prompt_run.id)}>{t('Open Prompt Run')}</Link></Button> : null}
                <Button asChild><Link href={route('editor.scripts.production.edit', script.id)}>{t('Prepare production metadata')}</Link></Button>
                <Button asChild variant="outline"><Link href={route('editor.scripts.review', script.id)}>{t('Review script')}</Link></Button>
                <Button asChild variant="secondary"><Link href={route('editor.scripts.index')}>{t('Back')}</Link></Button>
            </div>
        </EditorLayout>
    );
}
