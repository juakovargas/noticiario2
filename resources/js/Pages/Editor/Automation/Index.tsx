import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';

type Bulletin = any;

export default function Index({ bulletins }: { bulletins: Bulletin[] }) {
  const { t } = useTranslations();
  const { formatDateTime } = useDateFormatter();
  const hasRoute = (name: string) => route().has(name);

  return <EditorLayout><Head title={t('Automation control panel')} />
    <div className="space-y-4">
      <AdminPageHeader helpKey="editor.automation.index" title={t('Automation control panel')} description={t('Editorial automation platform')} />
      {bulletins.map((b) => <Card key={b.id}><CardHeader><div className="flex items-start justify-between gap-4"><div>
        <CardTitle>{b.name}</CardTitle>
        <p className='text-sm text-slate-600 dark:text-slate-300'>{b.language ?? '-'} · {b.location ?? '-'} · {b.category ?? '-'} · {b.edition_type ?? '-'}</p>
        <p className='text-sm text-slate-600 dark:text-slate-300'>{t('Schedule count')}: {b.active_schedules_count}/{b.total_schedules_count}</p>
      </div><Button variant="outline" onClick={() => router.post(route('editor.automation.bulletin-types.toggle', b.id))}>{t('Enable all schedules')} / {t('Disable all schedules')}</Button></div></CardHeader>
      <CardContent className='space-y-3'>
        {b.needs_manual_attention && <div className='rounded border border-amber-500 bg-amber-50 p-3 text-sm dark:bg-amber-950/30'><p className='font-semibold'>{t('Needs manual attention')}</p><ul className='ml-4 list-disc'>{(b.attention_reasons || []).map((reason: string) => <li key={reason}>{t(reason)}</li>)}</ul></div>}
        {b.schedules.length === 0 && <div className='rounded border p-3 text-sm'>{t('No schedules configured')} — {t('This bulletin has no schedules configured.')}</div>}
        {b.schedules.map((s: any) => <div key={s.id} className='rounded border p-3 text-sm'><p>{t('Scheduled times')}: {s.run_time ?? '-'} ({s.timezone ?? 'UTC'})</p>
          <p>{t('Next execution')}: {formatDateTime(s.next_run_at)}</p>
          <p>{t('Last execution')}: {formatDateTime(s.last_run_at)}</p>
          <p>{s.is_active ? t('This schedule is active') : t('This schedule is inactive')}</p>
          {s.last_error_message && <p className='text-red-600 dark:text-red-400'>{t('Last error')}: {s.last_error_message}</p>}
          <div className='mt-2 flex flex-wrap gap-2'>
            <Button size="sm" variant="secondary" onClick={() => router.post(route('editor.automation.schedules.toggle', s.id))}>{s.is_active ? t('Turn off') : t('Turn on')}</Button>
            <Button size="sm" variant="outline" onClick={() => router.post(route('editor.automation.schedules.run-now', s.id))}>{t('Run now')}</Button>
            <Button size="sm" variant="outline" onClick={() => router.post(route('editor.automation.schedules.recalculate-next-run', s.id))}>{t('Recalculate next run')}</Button>
            {hasRoute('editor.editorial-schedules.edit') && <Link className='underline' href={route('editor.editorial-schedules.edit', s.id)}>{t('Edit schedule')}</Link>}
          </div></div>)}
        {b.latest_execution?.prompt_run && hasRoute('editor.bulletin-prompt-runs.show') && <Link className='underline text-sm' href={route('editor.bulletin-prompt-runs.show', b.latest_execution.prompt_run.id)}>{t('View latest prompt run')}</Link>}
        {b.latest_execution?.script && hasRoute('editor.scripts.show') && <Link className='underline text-sm' href={route('editor.scripts.show', b.latest_execution.script.id)}>{t('View latest script')}</Link>}
      </CardContent></Card>)}
    </div>
  </EditorLayout>;
}
