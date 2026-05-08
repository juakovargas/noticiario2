import AdminPageHeader from '@/Components/AdminPageHeader';
import UserIdentity from '@/Components/UserIdentity';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Download, Wand2 } from 'lucide-react';

interface AudioRender {
    id: number;
    status: string;
    provider: string | null;
    voice_id: string | null;
    model_id: string | null;
    output_format: string | null;
    audio_url: string | null;
    download_url: string | null;
    generated_at: string | null;
    character_count: number | null;
    error_message: string | null;
}

export default function Show({ script, latestAudioRender }: { script: any; latestAudioRender: AudioRender | null }): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const audioForm = useForm({});

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

            <Card className="mt-4">
                <CardHeader><CardTitle>{t('Audio generation')}</CardTitle></CardHeader>
                <CardContent className="space-y-4 text-sm">
                    <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <p><strong>{t('Provider')}:</strong> {t('ElevenLabs')}</p>
                        <p><strong>{t('Latest audio')}:</strong> {latestAudioRender ? `#${latestAudioRender.id}` : t('No audio generated yet')}</p>
                        <p><strong>{t('Audio status')}:</strong> {latestAudioRender ? <Badge variant={audioStatusVariant(latestAudioRender.status)}>{audioStatusLabel(latestAudioRender.status, t)}</Badge> : '-'}</p>
                        <p><strong>{t('Generated at')}:</strong> {formatDateTime(latestAudioRender?.generated_at)}</p>
                        <p><strong>{t('Voice')}:</strong> {latestAudioRender?.voice_id || '-'}</p>
                        <p><strong>{t('Model')}:</strong> {latestAudioRender?.model_id || '-'}</p>
                        <p><strong>{t('Output format')}:</strong> {latestAudioRender?.output_format || '-'}</p>
                        <p><strong>{t('Characters')}:</strong> {latestAudioRender?.character_count ?? '-'}</p>
                    </div>

                    {latestAudioRender?.status === 'failed' && latestAudioRender.error_message ? (
                        <p className="rounded border border-rose-200 bg-rose-50 p-3 text-rose-700">
                            <strong>{t('Audio generation failed.')}:</strong> {t(latestAudioRender.error_message)}
                        </p>
                    ) : null}

                    {latestAudioRender?.audio_url ? (
                        <div className="space-y-2">
                            <p className="font-medium">{t('Latest audio')}</p>
                            <audio className="w-full" controls src={latestAudioRender.audio_url} aria-label={t('Latest audio')} />
                        </div>
                    ) : null}

                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            disabled={audioForm.processing}
                            onClick={() => audioForm.post(route('editor.scripts.audio-renders.store', script.id), { preserveScroll: true })}
                        >
                            <Wand2 className="h-4 w-4" aria-hidden="true" />
                            {audioForm.processing ? t('Generating audio') : t('Generate audio')}
                        </Button>
                        {latestAudioRender?.download_url ? (
                            <Button asChild variant="outline">
                                <a href={latestAudioRender.download_url}>
                                    <Download className="h-4 w-4" aria-hidden="true" />
                                    {t('Download audio')}
                                </a>
                            </Button>
                        ) : null}
                    </div>
                </CardContent>
            </Card>

            <div className="mt-4 flex flex-wrap gap-2">
                {script.origin?.prompt_run ? <Button asChild variant="outline"><Link href={route('editor.bulletin-prompt-runs.show', script.origin.prompt_run.id)}>{t('Open Prompt Run')}</Link></Button> : null}
                <Button asChild><Link href={route('editor.scripts.production.edit', script.id)}>{t('Prepare production metadata')}</Link></Button>
                <Button asChild variant="outline"><Link href={route('editor.scripts.review', script.id)}>{t('Review script')}</Link></Button>
                <Button asChild variant="secondary"><Link href={route('editor.scripts.index')}>{t('Back')}</Link></Button>
            </div>
        </EditorLayout>
    );
}

function audioStatusLabel(status: string, t: (key: string) => string): string {
    const labels: Record<string, string> = {
        pending: t('Pending'),
        completed: t('Completed'),
        failed: t('Failed'),
    };

    return labels[status] ?? status;
}

function audioStatusVariant(status: string): 'default' | 'success' | 'danger' | 'outline' {
    if (status === 'completed') return 'success';
    if (status === 'failed') return 'danger';

    return 'outline';
}
