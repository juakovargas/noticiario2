import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import { toDateTimeLocalInputValue } from '@/lib/dates';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import StatusBadge from '@/Components/StatusBadge';

interface ParsedItem {
    headline?: string | null;
    summary?: string | null;
    script?: string | null;
    editorial_angle?: string | null;
    source_hints?: string[];
}

interface ParsedResponse {
    title?: string | null;
    intro?: string | null;
    outro?: string | null;
    notes?: string | null;
    body?: string | null;
    items?: ParsedItem[];
    warnings?: string[];
}

const warningLabels: Record<string, string> = {
    missing_title: 'Missing title',
    missing_intro: 'Missing intro',
    no_script_blocks: 'No script blocks',
    missing_sources: 'Missing sources',
    unstructured_response: 'Unstructured response',
};

function hasUrl(value: string): boolean {
    return /^https?:\/\//i.test(value.trim());
}

function needsVerification(value: string): boolean {
    return /needs verification|requiere verificación/i.test(value);
}

export default function Show({ run, promptContext }: any): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime, formatDate, formatTime } = useDateFormatter();
    const form = useForm({ response_text: run.ai_response_text ?? '' });
    const scheduleForm = useForm({ scheduled_for: toDateTimeLocalInputValue(run.scheduled_for ?? new Date()) });
    const parsed = (run.parsed_response ?? null) as ParsedResponse | null;

    const copyPrompt = async (): Promise<void> => {
        if (run.generated_prompt && navigator?.clipboard) {
            await navigator.clipboard.writeText(run.generated_prompt);
        }
    };

    return (
        <EditorLayout>
            <Head title={`${t('Prompt Run')} #${run.id}`} />
            <AdminPageHeader title={`${t('Prompt Run')} #${run.id}`} description={t('Copy this prompt into your preferred AI tool')} />

            {run.status === 'archived' && (
                <div className="mb-4 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
                    {t('This prompt run is archived')}. {t('Restore to continue working')}.
                </div>
            )}

            <div className="mb-4 flex flex-wrap gap-2">
                <Button disabled={run.status === 'archived'} onClick={() => router.post(route('editor.bulletin-prompt-runs.generate-prompt', run.id))}>{t('Generate Prompt')}</Button>
                <Button variant="secondary" onClick={copyPrompt}>{t('Copy Prompt')}</Button>
                <Button disabled={run.status === 'archived'} variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.create-script', run.id))}>{t('Create Script')}</Button>
                {run.script_id && (
                    <Button asChild variant="ghost">
                        <Link href={route('editor.scripts.show', run.script_id)}>{t('Open Script')}</Link>
                    </Button>
                )}
                {run.status !== 'archived' ? (
                    <Button variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.archive', run.id))}>{t('Archive')}</Button>
                ) : (
                    <Button variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.restore', run.id))}>{t('Restore')}</Button>
                )}
                <Button disabled={run.status === 'archived'} variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.mark-completed', run.id))}>{t('Mark completed')}</Button>
                <Button disabled={run.status === 'archived'} variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.cancel', run.id))}>{t('Cancel')}</Button>
            </div>

            <Card>
                <CardContent className="space-y-2 pt-6 text-sm">
                    <p><strong>{t('Bulletin Type')}:</strong> {run.bulletin_type?.name}</p>
                    <p><strong>{t('Prompt Profile')}:</strong> {run.prompt_profile?.name ?? '-'}</p>
                    <p><strong>{t('Status')}:</strong> <StatusBadge status={run.status} className="ml-2" /></p>
                    <p><strong>{t('Scheduled date/time')}:</strong> {formatDateTime(run.scheduled_for)}</p>
                    {run.edition_id && (
                        <p>
                            <strong>{t('Editions')}:</strong>{' '}
                            <Link className="text-cyan-700" href={route('editor.editions.show', run.edition_id)}>{run.edition?.title || `#${run.edition_id}`}</Link>
                        </p>
                    )}
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardContent className="pt-6">
                    <h3 className="mb-2 font-semibold">{t('Prompt context')}</h3>
                    <div className="grid gap-2 text-sm md:grid-cols-2">
                        <p><strong>{t('Broadcast date')}:</strong> {formatDate(promptContext.scheduled_for)}</p>
                        <p><strong>{t('Broadcast time')}:</strong> {formatTime(promptContext.scheduled_for)}</p>
                        <p><strong>{t('Timezone')}:</strong> {promptContext.timezone}</p>
                        <p><strong>{t('Coverage mode')}:</strong> {promptContext.coverage_mode}</p>
                        <p><strong>{t('Coverage from')}:</strong> {formatDateTime(promptContext.coverage_from)}</p>
                        <p><strong>{t('Coverage to')}:</strong> {formatDateTime(promptContext.coverage_to)}</p>
                        <p><strong>{t('Output mode')}:</strong> {run.bulletin_type?.output_mode ?? '-'}</p>
                        <p><strong>{t('Prompt language')}:</strong> {run.bulletin_type?.prompt_language ?? '-'}</p>
                        <p><strong>{t('Minimum news items')}:</strong> {run.bulletin_type?.min_news_items ?? '-'}</p>
                        <p><strong>{t('Maximum news items')}:</strong> {run.bulletin_type?.max_news_items ?? '-'}</p>
                        <p><strong>{t('Include future agenda')}:</strong> {run.bulletin_type?.include_future_agenda ? t('Yes') : t('No')}</p>
                        <p><strong>{t('Include historical context')}:</strong> {run.bulletin_type?.include_historical_context ? t('Yes') : t('No')}</p>
                    </div>
                    <p className="mt-3 text-sm text-slate-600">{t('Use current date if no schedule is configured')}</p>
                    <div className="mt-2 flex items-end gap-3">
                        <div>
                            <Label>{t('Create run for date/time')}</Label>
                            <Input type="datetime-local" value={scheduleForm.data.scheduled_for} onChange={(e) => scheduleForm.setData('scheduled_for', e.target.value)} />
                        </div>
                        <Button variant="outline" onClick={() => scheduleForm.put(route('editor.bulletin-prompt-runs.update-schedule', run.id), { preserveScroll: true })}>{t('Save')}</Button>
                    </div>
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardContent className="pt-6">
                    <h3 className="mb-2 font-semibold">{t('Generated Prompt')}</h3>
                    <pre className="whitespace-pre-wrap rounded bg-slate-100 p-3 text-xs">{run.generated_prompt || '-'}</pre>
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardContent className="space-y-3 pt-6">
                    <h3 className="mb-2 font-semibold">{t('AI Response')}</h3>
                    <p className="text-sm text-slate-600">{t('Paste the external AI response here and save it to process the script preview')}</p>
                    <textarea disabled={run.status === 'archived'} className="min-h-52 w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder={t('Paste the AI response here')} value={form.data.response_text} onChange={(e) => form.setData('response_text', e.target.value)} />
                    <div className="flex flex-wrap items-center gap-2">
                        <Button disabled={run.status === 'archived'} onClick={() => form.post(route('editor.bulletin-prompt-runs.save-response', run.id), { preserveScroll: true })}>{t('Save AI Response')}</Button>
                        {run.response_received_at && <span className="text-xs text-slate-500">{t('Response saved at')}: {formatDateTime(run.response_received_at)}</span>}
                    </div>
                    {run.script_id && <p className="text-sm text-amber-700">{t('Updating the response will not overwrite the existing script')}</p>}
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardContent className="space-y-4 pt-6">
                    <h3 className="font-semibold">{t('Parsed response preview')}</h3>

                    {(parsed?.warnings?.length ?? 0) > 0 && (
                        <div className="space-y-2">
                            <p className="text-sm font-medium">{t('Parser warnings')}</p>
                            <div className="flex flex-wrap gap-2">
                                {parsed?.warnings?.map((warning) => (
                                    <Badge key={warning} variant="outline" className="border-amber-400 text-amber-700">{t(warningLabels[warning] ?? warning)}</Badge>
                                ))}
                            </div>
                        </div>
                    )}

                    {parsed?.title && <p><strong>{t('Parsed bulletin title')}:</strong> {parsed.title}</p>}

                    <div>
                        <h4 className="font-medium">{t('Intro')}</h4>
                        <p className="rounded border bg-slate-50 p-3 text-sm">{parsed?.intro || '-'}</p>
                    </div>

                    {((parsed?.items?.length ?? 0) > 0) ? (
                        <div className="space-y-3">
                            <h4 className="font-medium">{t('Parsed news items')}</h4>
                            {parsed?.items?.map((item, index) => (
                                <div key={index} className="rounded border p-3 text-sm">
                                    <p><strong>{t('Headline')}:</strong> {item.headline || '-'}</p>
                                    <p><strong>{t('Summary')}:</strong> {item.summary || '-'}</p>
                                    <div className="mt-2 rounded border border-cyan-200 bg-cyan-50 p-3">
                                        <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-cyan-700">{t('Narration script')}</p>
                                        <p className="whitespace-pre-wrap">{item.script || '-'}</p>
                                    </div>
                                    <p className="mt-2"><strong>{t('Editorial angle')}:</strong> {item.editorial_angle || '-'}</p>
                                    <div className="mt-2">
                                        <p><strong>{t('Source hints')}:</strong></p>
                                        {(item.source_hints?.length ?? 0) > 0 ? (
                                            <ul className="ml-5 list-disc">
                                                {item.source_hints?.map((hint, hintIndex) => (
                                                    <li key={hintIndex}>
                                                        {hasUrl(hint) ? <a href={hint} className="text-cyan-700 underline" target="_blank" rel="noreferrer">{hint}</a> : hint}
                                                        {needsVerification(hint) && <Badge className="ml-2" variant="danger">{t('Needs verification')}</Badge>}
                                                    </li>
                                                ))}
                                            </ul>
                                        ) : (
                                            <p className="text-slate-500">{t('No source hints provided')}</p>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
                            {t('No structured sections detected')}. {t('The full response will be used as the script body')}.
                        </div>
                    )}

                    <div>
                        <h4 className="font-medium">{t('Outro')}</h4>
                        <p className="rounded border bg-slate-50 p-3 text-sm">{parsed?.outro || '-'}</p>
                    </div>

                    <div>
                        <h4 className="font-medium">{t('Notes')}</h4>
                        <p className="rounded border bg-slate-50 p-3 text-sm whitespace-pre-wrap">{parsed?.notes || '-'}</p>
                    </div>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
