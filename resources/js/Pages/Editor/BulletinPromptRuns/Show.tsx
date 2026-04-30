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

function extractUrl(value: string): string | null {
    const match = value.match(/https?:\/\/\S+/i);

    return match?.[0] ?? null;
}

function needsVerification(value: string): boolean {
    return /needs verification|requiere verificación|weak|fuente débil/i.test(value);
}

export default function Show({ run, promptContext, sourceReferences = [], sourceSummary = null, aiProviders = [], defaultAiProviderId = null, latestAiLog = null, pipelineStatus = null }: any): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime, formatDate, formatTime } = useDateFormatter();
    const form = useForm({ response_text: run.ai_response_text ?? '' });
    const aiForm = useForm({ ai_provider_id: run.ai_provider_id ?? defaultAiProviderId ?? '', model: '' });
    const scheduleForm = useForm({ scheduled_for: toDateTimeLocalInputValue(run.scheduled_for ?? new Date()) });
    const parsed = (run.parsed_response ?? null) as ParsedResponse | null;
    const promptLength = (run.generated_prompt ?? '').length;
    const approxTokens = Math.ceil(promptLength / 4);
    const outputModeLabel = run.bulletin_type?.output_mode === 'final_plain_script' ? t('Simple final script') : run.bulletin_type?.output_mode === 'plain_script' ? t('Plain script') : t('Structured script');


    const copyPrompt = async (): Promise<void> => {
        if (run.generated_prompt && navigator?.clipboard) {
            await navigator.clipboard.writeText(run.generated_prompt);
        }
    };

    return (
        <EditorLayout>
            <Head title={run.title || t('Prompt Run')} />
            <AdminPageHeader helpKey="editor.bulletinpromptruns.show" title={run.title || t('Prompt Run')} description={t('Copy this prompt into your preferred AI tool')} />

            {run.status === 'archived' && (
                <div className="mb-4 rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
                    {t('This prompt run is archived')}. {t('Restore to continue working')}.
                </div>
            )}

            <div className="mb-4 flex flex-wrap gap-2">
                <Button disabled={run.status === 'archived'} onClick={() => router.post(route('editor.bulletin-prompt-runs.generate-prompt', run.id))}>{t('Generate Prompt')}</Button>
                <Button variant="secondary" onClick={copyPrompt}>{t('Copy Prompt')}</Button>
                {run.status !== 'archived' ? (
                    <Button variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.archive', run.id))}>{t('Archive')}</Button>
                ) : (
                    <Button variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.restore', run.id))}>{t('Restore')}</Button>
                )}
                <Button disabled={run.status === 'archived'} variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.mark-completed', run.id))}>{t('Mark completed')}</Button>
                <Button disabled={run.status === 'archived'} variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.cancel', run.id))}>{t('Cancel')}</Button>
            </div>



            <Card className="mb-4">
                <CardContent className="space-y-3 pt-6 text-sm">
                    <h3 className="font-semibold">{t('Automated pipeline')}</h3>
                    <p className="text-slate-600">{t('This can send the generated prompt to the configured AI provider')}</p>
                    <p><strong>{t('Current pipeline status')}:</strong> {pipelineStatus?.status ?? run.pipeline_status ?? t('not_started')}</p>
                    {pipelineStatus?.failed_step ? <p><strong>{t('Failed step')}:</strong> {pipelineStatus.failed_step}</p> : null}
                    {pipelineStatus?.metadata?.pipeline_metadata?.provider_name ? <p><strong>{t('AI provider')}:</strong> {pipelineStatus.metadata.pipeline_metadata.provider_name}</p> : null}
                    {pipelineStatus?.metadata?.pipeline_metadata?.retry_after_seconds ? <p><strong>{t('Retry after')}:</strong> {pipelineStatus.metadata.pipeline_metadata.retry_after_seconds} {t('seconds')}</p> : null}
                    {pipelineStatus?.metadata?.pipeline_metadata?.rate_limited_until ? <p><strong>{t('Rate limited until')}:</strong> {formatDateTime(pipelineStatus.metadata.pipeline_metadata.rate_limited_until)}</p> : null}
                    {run.pipeline_error_message ? <p className="text-red-700"><strong>{t('Last pipeline error')}:</strong> {run.pipeline_error_message}</p> : null}
                    <div className="flex flex-wrap gap-2">
                        <Button onClick={() => router.post(route('editor.bulletin-prompt-runs.run-pipeline', run.id), { generate_metadata: true, extract_sources: true, allow_ai_call: true }, { preserveScroll: true })}>{t('Run full pipeline')}</Button>
                        <Button variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.retry-pipeline', run.id), {}, { preserveScroll: true })}>{t('Retry pipeline')}</Button>
                    </div>
                </CardContent>
            </Card>

            <Card className="mb-4">
                <CardContent className="space-y-3 pt-6 text-sm">
                    <h3 className="font-semibold">{t('Workflow')}</h3>
                    <div className="flex flex-wrap gap-2">
                        <Badge variant="outline">{t('Prompt')}: {run.generated_prompt ? t('Ready') : t('Pending')}</Badge>
                        <Badge variant="outline">{t('Response')}: {run.ai_response_text ? t('Ready') : t('Pending')}</Badge>
                        <Badge variant="outline">{t('Script')}: {run.script_id ? t('Ready') : t('Pending')}</Badge>
                        <Badge variant="outline">{t('Review')}: {run.script?.review_status ?? t('Pending')}</Badge>
                        <Badge variant="outline">{t('Production metadata')}: {run.script?.metadata_ready ? t('Ready') : t('Missing metadata')}</Badge>
                    </div>
                    {run.script ? (
                        <div className="rounded border border-cyan-200 bg-cyan-50 p-3">
                            <p><strong>{t('Continue with Script')}:</strong> {run.script.title}</p>
                            <p>{t('Status')}: {run.script.status} · {t('Review status')}: {run.script.review_status || '-'}</p>
                            <p>{t('Production metadata')}: {run.script.metadata_ready ? t('Ready for production') : t('Missing metadata')}</p>
                            <div className="mt-2 flex flex-wrap gap-2">
                                <Button asChild size="sm"><Link href={route('editor.scripts.show', run.script.id)}>{t('Open Script')}</Link></Button>
                                <Button asChild size="sm" variant="outline"><Link href={route('editor.scripts.production.edit', run.script.id)}>{t('Prepare production metadata')}</Link></Button>
                            </div>
                        </div>
                    ) : null}
                </CardContent>
            </Card>
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
                        <p><strong>{t('Output mode')}:</strong> {outputModeLabel}</p>
                        <p><strong>{t('Prompt length')}:</strong> {promptLength} {t('characters')} · {t('Approximate tokens')}: {approxTokens}</p>
                        {promptLength > 1800 ? <p className="text-amber-700">{t('This prompt is long')}. {t('Use simple final script to reduce tokens')}.</p> : null}
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
                <CardContent className="space-y-3 pt-6 text-sm">
                    <h3 className="font-semibold">{t('Manual fallback')}</h3>
                    <p className="text-slate-600">{t('Debug actions')}</p>
                    <p className="text-slate-600">{t('This will send the generated prompt to the configured AI provider.')}</p>
                    <p className="text-slate-600">{t('Manual copy and paste workflow is still available.')}</p>
                    <p className="text-slate-600">{t('Use Groq for fast manual AI testing.')}</p>
                    <p className="text-slate-600">{t('This will consume one provider request.')}</p>
                    <p className="text-slate-600">{t('The API key is read from the server environment and is never shown.')}</p>
                    {aiProviders.length === 0 ? (
                        <p className="text-amber-700">{t('No active AI provider configured.')}</p>
                    ) : (
                        <>
                            <div className="grid gap-3 md:grid-cols-2">
                                <div>
                                    <Label>{t('AI provider')}</Label>
                                    <select className="w-full rounded-md border border-slate-300 px-3 py-2" value={aiForm.data.ai_provider_id} onChange={(e) => aiForm.setData('ai_provider_id', e.target.value)} >
                                        {aiProviders.map((provider: any) => <option key={provider.id} value={provider.id}>{provider.name} ({provider.provider_type})</option>)}
                                    </select>
                                </div>
                                <div>
                                    <Label>{t('Model')}</Label>
                                    <Input value={aiForm.data.model} onChange={(e) => aiForm.setData('model', e.target.value)} placeholder={t('Default model')} />
                                </div>
                            </div>
                            <div className="text-xs text-slate-600">
                                {(() => {
                                    const selected = aiProviders.find((provider: any) => String(provider.id) === String(aiForm.data.ai_provider_id));
                                    if (!selected) return null;
                                    return <>
                                        <p>{t('AI provider')}: {selected.name}</p>
                                        {!selected.is_active && <p className="text-amber-700">{t('Provider is inactive')}</p>}
                                        {!selected.env_key_configured && <p className="text-amber-700">{t('Environment key is not configured')}</p>}
                                    </>;
                                })()}
                            </div>
                            <Button disabled={run.status === 'archived'} onClick={() => aiForm.post(route('editor.bulletin-prompt-runs.generate-ai-response', run.id), { preserveScroll: true })}>{t('Generate AI Response')}</Button>
                        </>
                    )}
                    {latestAiLog && (
                        <div className="rounded border bg-slate-50 p-3 text-xs">
                            <p><strong>{t('AI request log')}:</strong> {latestAiLog.status}</p>
                            {latestAiLog.status === 'failed' && <p className="text-red-700">{t('The request failed before a response was saved')}. {t('Check provider configuration, model, limits and numeric options')}.</p>}
                            <p><strong>{t('AI provider')}:</strong> {latestAiLog.provider?.name || '-'}</p>
                            <p><strong>{t('Model')}:</strong> {latestAiLog.model || '-'}</p>
                            <p><strong>{t('Duration')}:</strong> {latestAiLog.duration_ms || '-'} ms</p>
                            <p><strong>{t('Total tokens')}:</strong> {latestAiLog.total_tokens ?? '-'}</p>
                            <p><strong>{t('Estimated cost')}:</strong> {latestAiLog.estimated_cost ?? '-'}</p>
                            <Link className="text-cyan-700" href={route('editor.ai-request-logs.show', latestAiLog.id)}>{t('Open editor AI request log')}</Link>
                        </div>
                    )}
                </CardContent>
            </Card>

            <Card className="mt-4">
                <CardContent className="space-y-3 pt-6 text-sm">
                    <h3 className="font-semibold">{t('Source verification')}</h3>
                    <p>{t('Source References')}: {sourceSummary?.total ?? 0} · {t('Pending source')}: {sourceSummary?.pending ?? 0} · {t('Verified source')}: {sourceSummary?.verified ?? 0}</p>
                    <p>{t('Weak source')}: {sourceSummary?.weak ?? 0} · {t('Missing source')}: {sourceSummary?.missing ?? 0} · {t('Broken source')}: {sourceSummary?.broken ?? 0} · {t('Rejected source')}: {sourceSummary?.rejected ?? 0}</p>
                    {(sourceSummary?.unresolved ?? 0) > 0 ? <p className="text-amber-700">{t('Unresolved source issues')}: {sourceSummary.unresolved}</p> : null}
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.source-references.extract', run.id))}>{t('Extract source references')}</Button>
                        <Button asChild variant="outline"><Link href={route('editor.source-references.index', { bulletin_prompt_run_id: run.id })}>{t('Manage sources')}</Link></Button>
                    </div>
                    {sourceReferences.length ? (
                        <ul className="list-disc pl-5 text-xs">
                            {sourceReferences.map((item: any) => <li key={item.id}>{item.title || item.source_name || t('Source Reference')} · {item.verification_status}</li>)}
                        </ul>
                    ) : <p className="text-slate-500">{t('No source references found')}</p>}
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
                                                        {extractUrl(hint) ? <a href={extractUrl(hint) as string} className="text-cyan-700 underline" target="_blank" rel="noreferrer">{hint}</a> : hint}
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

                    {parsed?.warnings?.includes('no_script_blocks') && (
                        <div className="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">{t('No script blocks detected')}. {t('The final script may use the full response fallback')}.</div>
                    )}

                    {parsed?.warnings?.includes('missing_sources') && (
                        <div className="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">{t('No source hints provided')}.</div>
                    )}

                    <div className="flex flex-wrap gap-2">
                        {!run.script_id ? (
                            <Button disabled={run.status === 'archived'} variant="outline" onClick={() => router.post(route('editor.bulletin-prompt-runs.create-script', run.id))}>{t('Create Script')}</Button>
                        ) : (
                            <Button asChild variant="outline">
                                <Link href={route('editor.scripts.show', run.script_id)}>{t('Continue with Script')}</Link>
                            </Button>
                        )}
                    </div>


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

