import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import { toDateTimeLocalInputValue } from '@/lib/dates';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Bot, CalendarClock, CheckCircle2, PlayCircle } from 'lucide-react';

export default function Show({ bulletinType }: any): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const runForm = useForm({ scheduled_for: toDateTimeLocalInputValue(new Date()) });
    const provider = bulletinType.preferred_ai_provider;
    const primarySchedule = bulletinType.schedules?.[0];

    const createRun = (): void => {
        router.post(route('editor.bulletin-types.prompt-runs.store', bulletinType.id), {
            scheduled_for: runForm.data.scheduled_for ? new Date(runForm.data.scheduled_for).toISOString() : null,
        });
    };

    return (
        <EditorLayout>
            <Head title={bulletinType.name} />
            <AdminPageHeader helpKey="editor.bulletintypes.show" title={bulletinType.name} description={t('bulletinTypes.show.description')} />

            <div className="mb-5 flex flex-wrap gap-2">
                <Button onClick={createRun}><PlayCircle className="h-4 w-4" />{t('bulletinTypes.action.runNow')}</Button>
                <Button asChild variant="outline"><Link href={route('editor.bulletin-types.edit', bulletinType.id)}>{t('bulletinTypes.action.edit')}</Link></Button>
                <Button asChild variant="outline"><Link href={route('editor.bulletin-prompt-runs.index', { bulletin_type_id: bulletinType.id })}>{t('bulletinTypes.action.promptRuns')}</Link></Button>
            </div>

            <div className="grid gap-4 xl:grid-cols-3">
                <Card className="rounded-lg xl:col-span-2">
                    <CardHeader>
                        <CardTitle>{t('bulletinTypes.show.configuration')}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 text-sm md:grid-cols-2">
                        <Info label={t('Description')} value={bulletinType.description || t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('Location')} value={bulletinType.location?.name || t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('Category')} value={bulletinType.news_category?.name || t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('Language')} value={bulletinType.language?.name || t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('Default prompt profile')} value={bulletinType.prompt_profile?.name || t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('Coverage mode')} value={t(`coverage.${bulletinType.coverage_mode || 'none'}`)} />
                        <Info label={t('Prompt language')} value={bulletinType.prompt_language || t('bulletinTypes.empty.notAssigned')} />
                        <Info label={t('Output mode')} value={t(`outputMode.${bulletinType.output_mode || 'plain_final_script'}`)} />
                    </CardContent>
                </Card>

                <Card className="rounded-lg">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><Bot className="h-4 w-4" />{t('bulletinTypes.show.provider')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        {provider ? (
                            <>
                                <p className="text-lg font-semibold">{provider.name}</p>
                                <p className="text-slate-500 dark:text-slate-400">{provider.default_model || t('bulletinTypes.empty.noModel')}</p>
                                <div className="flex flex-wrap gap-2">
                                    <Badge variant={provider.supports_grounding ? 'success' : 'outline'}>{provider.supports_grounding ? t('bulletinTypes.provider.grounded') : t('bulletinTypes.provider.notGrounded')}</Badge>
                                    <Badge variant={provider.is_active ? 'success' : 'danger'}>{provider.is_active ? t('Active') : t('Inactive')}</Badge>
                                </div>
                            </>
                        ) : (
                            <Badge variant="danger">{t('bulletinTypes.provider.missing')}</Badge>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card className="mt-4 rounded-lg">
                <CardHeader>
                    <CardTitle className="flex items-center gap-2"><CalendarClock className="h-4 w-4" />{t('bulletinTypes.show.schedule')}</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-4 text-sm md:grid-cols-4">
                    <Info label={t('bulletinTypes.table.status')} value={primarySchedule?.is_active ? 'ON' : 'OFF'} />
                    <Info label={t('Frequency')} value={primarySchedule?.run_frequency || t('bulletinTypes.empty.notAssigned')} />
                    <Info label={t('Time')} value={(primarySchedule?.run_time || primarySchedule?.scheduled_time || '').slice(0, 5) || t('bulletinTypes.empty.notAssigned')} />
                    <Info label={t('Next execution')} value={formatDateTime(primarySchedule?.next_run_at)} />
                    <Info label={t('bulletinTypes.automation.aiManualApproval')} value={primarySchedule?.auto_generate_ai_response ? t('common.no') : t('common.yes')} />
                    <Info label={t('bulletinTypes.automation.pipeline')} value={primarySchedule?.auto_run_pipeline ? t('common.yes') : t('common.no')} />
                    <Info label={t('Coverage starts offset')} value={bulletinType.coverage_starts_offset_minutes ?? t('bulletinTypes.empty.notAssigned')} />
                    <Info label={t('Coverage ends offset')} value={bulletinType.coverage_ends_offset_minutes ?? t('bulletinTypes.empty.notAssigned')} />
                </CardContent>
            </Card>

            <Card className="mt-4 rounded-lg">
                <CardContent className="pt-6">
                    <h3 className="mb-3 flex items-center gap-2 font-semibold"><CheckCircle2 className="h-4 w-4" />{t('bulletinTypes.show.createRun')}</h3>
                    <div className="flex flex-wrap items-end gap-3">
                        <div>
                            <Label>{t('Scheduled date/time')}</Label>
                            <Input type="datetime-local" value={runForm.data.scheduled_for} onChange={(e) => runForm.setData('scheduled_for', e.target.value)} />
                        </div>
                        <Button variant="outline" onClick={createRun}>{t('bulletinTypes.action.createPromptRun')}</Button>
                    </div>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}

function Info({ label, value }: { label: string; value: any }): JSX.Element {
    return (
        <div>
            <p className="text-xs uppercase text-slate-500">{label}</p>
            <p className="mt-1 font-medium text-slate-900 dark:text-slate-100">{value ?? '-'}</p>
        </div>
    );
}
