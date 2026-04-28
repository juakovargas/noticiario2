import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Show({ schedule, recentRuns }: any): JSX.Element {
  const { formatDateTime } = useDateFormatter();
  const { t } = useTranslations();
  return <EditorLayout><Head title={schedule.name} /><AdminPageHeader helpKey="editor.editorialschedules.index" title={schedule.name} description={t('Schedule runner')} actionLabel={t('Edit Schedule')} actionHref={route('editor.editorial-schedules.edit', schedule.id)} />
  <div className="mb-4 flex gap-2"><Button onClick={()=>router.post(route('editor.editorial-schedules.run-now', schedule.id))}>{t('Run now')}</Button><Button onClick={()=>router.post(route('editor.editorial-schedules.recalculate-next-run', schedule.id))} variant="outline">{t('Recalculate next run')}</Button><Button asChild variant="outline"><Link href={route('editor.editorial-schedule-runs.index')}>{t('View Runs')}</Link></Button></div>
  <Card><CardContent className="space-y-2 pt-6 text-sm"><p><strong>{t('Run frequency')}:</strong> {schedule.run_frequency || schedule.frequency_type}</p><p><strong>{t('Run time')}:</strong> {schedule.run_time || schedule.scheduled_time || '-'}</p><p><strong>{t('Next scheduled run')}:</strong> {formatDateTime(schedule.next_run_at)}</p><p><strong>{t('Last scheduled run')}:</strong> {formatDateTime(schedule.last_run_at)}</p><p><strong>{t('Auto create prompt run')}:</strong> {schedule.auto_create_prompt_run ? t('Yes') : t('No')}</p><p><strong>{t('Auto generate prompt')}:</strong> {schedule.auto_generate_prompt ? t('Yes') : t('No')}</p></CardContent></Card>
  <Card className="mt-4"><CardContent className="pt-6"><h3 className="mb-3 font-semibold">{t('Recently created runs')}</h3><div className="space-y-2">{recentRuns.length ? recentRuns.map((run:any)=><div key={run.id} className="rounded border border-slate-200 p-3 text-sm"><p><strong>{t('Status')}:</strong> {run.status}</p><p><strong>{t('Scheduled for')}:</strong> {formatDateTime(run.scheduled_for)}</p><Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-schedule-runs.show', run.id)}>{t('Open run')}</Link></Button></div>) : <p>{t('No runs yet.')}</p>}</div></CardContent></Card>
  </EditorLayout>;
}
