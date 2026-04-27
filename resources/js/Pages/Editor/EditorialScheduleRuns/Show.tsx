import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

interface ParsedItem {
    headline?: string | null;
    summary?: string | null;
    script?: string | null;
    editorial_angle?: string | null;
    source_hints?: string[];
    raw?: string;
}

interface ParsedResponse {
    title?: string | null;
    intro?: string | null;
    body?: string | null;
    outro?: string | null;
    notes?: string | null;
    items?: ParsedItem[];
    raw?: string;
}

export default function Show({ run }: any): JSX.Element {
    const { formatDateTime } = useDateFormatter();
    const { t } = useTranslations();
    const form = useForm({ response_text: run.ai_response_text ?? '' });
    const parsed = (run.parsed_response ?? null) as ParsedResponse | null;

    const hasStructuredPreview = Boolean(
        parsed && (parsed.title || parsed.intro || parsed.outro || parsed.notes || (parsed.items?.length ?? 0) > 0),
    );

    const copyPrompt = async (): Promise<void> => {
        if (!run.generated_prompt) {
            return;
        }

        if (navigator?.clipboard) {
            await navigator.clipboard.writeText(run.generated_prompt);
        }
    };

    return (
        <EditorLayout>
            <Head title={`Run #${run.id}`} />
            <AdminPageHeader title={`Editorial Run #${run.id}`} description="Manual AI copy/paste workflow." />

            <div className="mb-4 flex flex-wrap gap-2">
                <Button onClick={() => router.post(route('editor.editorial-schedule-runs.generate-prompt', run.id))}>{t('Generate Prompt')}</Button>
                <Button variant="secondary" onClick={copyPrompt}>{t('Copy Prompt')}</Button>
                <Button onClick={() => form.post(route('editor.editorial-schedule-runs.receive-response', run.id))}>{t('Save Response')}</Button>
                <Button variant="outline" onClick={() => router.post(route('editor.editorial-schedule-runs.create-script', run.id))}>{t('Create Script')}</Button>
                {run.script_id && (
                    <Button asChild variant="ghost">
                        <Link href={route('editor.scripts.show', run.script_id)}>{t('Open Script')}</Link>
                    </Button>
                )}
            </div>

            <Card>
                <CardContent className="space-y-3 pt-6 text-sm">
                    <p><strong>{t('Scheduled for')}:</strong> {formatDateTime(run.scheduled_for)}</p>
                    <p><strong>{t('Status')}:</strong> {run.status}</p>
                    <p><strong>{t('Editorial Schedule')}:</strong> {run.schedule?.name}</p>
                    {run.edition_id && (
                        <p>
                            <strong>{t('Editions')}:</strong>{' '}
                            <Link className="text-cyan-700" href={route('editor.editions.show', run.edition_id)}>
                                {run.edition?.title ?? `#${run.edition_id}`}
                            </Link>
                        </p>
                    )}
                    {run.error_message && <p className="text-red-600"><strong>Error:</strong> {run.error_message}</p>}
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardContent className="pt-6">
                    <h3 className="mb-2 font-semibold">{t('Generated Prompt')}</h3>
                    <pre className="whitespace-pre-wrap rounded bg-slate-100 p-3 text-xs">{run.generated_prompt || '-'}</pre>
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardContent className="pt-6">
                    <h3 className="mb-2 font-semibold">{t('AI Response')}</h3>
                    <textarea
                        className="min-h-52 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                        value={form.data.response_text}
                        onChange={(e) => form.setData('response_text', e.target.value)}
                    />
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardContent className="space-y-3 pt-6 text-sm">
                    <h3 className="font-semibold">{t('Parsed response preview')}</h3>
                    {!hasStructuredPreview && (
                        <>
                            <p className="text-amber-700">{t('No structured sections detected')}</p>
                            <p className="text-slate-600">{t('The full response will be used as the script body')}</p>
                        </>
                    )}

                    {run.parser_warnings?.length > 0 && (
                        <div>
                            <p className="font-medium">{t('Parser warnings')}</p>
                            <ul className="list-disc pl-5">
                                {run.parser_warnings.map((warning: string, idx: number) => <li key={idx}>{warning}</li>)}
                            </ul>
                        </div>
                    )}

                    {parsed?.title && <p><strong>{t('Parsed title')}:</strong> {parsed.title}</p>}
                    {parsed?.intro && <p><strong>{t('Parsed intro')}:</strong> {parsed.intro}</p>}

                    {(parsed?.items?.length ?? 0) > 0 && (
                        <div className="space-y-3">
                            <p className="font-medium">{t('Parsed news items')}: {parsed?.items?.length}</p>
                            {parsed?.items?.map((item, index) => (
                                <div key={index} className="rounded border border-slate-200 p-3">
                                    <p><strong>{t('Headline')}:</strong> {item.headline || '-'}</p>
                                    <p><strong>{t('Summary')}:</strong> {item.summary || '-'}</p>
                                    <p><strong>{t('Body')}:</strong> {item.script || '-'}</p>
                                    <p><strong>{t('Editorial angle')}:</strong> {item.editorial_angle || '-'}</p>
                                    <div>
                                        <strong>{t('Source hints')}:</strong>{' '}
                                        {(item.source_hints?.length ?? 0) === 0
                                            ? t('No source hints')
                                            : item.source_hints?.join(' · ')}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {parsed?.body && <p><strong>{t('Parsed body')}:</strong> {parsed.body}</p>}
                    {parsed?.outro && <p><strong>{t('Parsed outro')}:</strong> {parsed.outro}</p>}
                    {parsed?.notes && <p><strong>{t('Notes')}:</strong> {parsed.notes}</p>}

                    <div>
                        <p className="font-medium">{t('Raw response')}</p>
                        <pre className="whitespace-pre-wrap rounded bg-slate-100 p-3 text-xs">{run.ai_response_text || '-'}</pre>
                    </div>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
