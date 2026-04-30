import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';

const badgeClass: Record<string, string> = {
  scheduled: 'bg-emerald-100 text-emerald-800', overdue: 'bg-amber-100 text-amber-800', inactive: 'bg-slate-200 text-slate-700', missing: 'bg-rose-100 text-rose-800',
};

export default function Index({ bulletins }: { bulletins: any[] }) {
  const { t } = useTranslations();
  const { formatDateTime } = useDateFormatter();

  return <EditorLayout><Head title={t('Editorial automation')} />
    <div className="space-y-4">
      <AdminPageHeader helpKey="editor.automation.index" title={t('Editorial automation')} description={t('Control recurring bulletin automation operationally.')} />
      <div className='flex gap-2'><Button onClick={() => router.post(route('editor.automation.sync-schedules'))}>{t('Sync schedules')}</Button></div>
      <div className='overflow-x-auto rounded border'>
        <table className='min-w-full text-sm'>
          <thead><tr className='bg-slate-100 dark:bg-slate-800'>
            <th className='p-2 text-left'>{t('Informativo')}</th><th className='p-2 text-left'>{t('Status')}</th><th className='p-2 text-left'>{t('Scheduling')}</th><th className='p-2 text-left'>{t('Next execution')}</th><th className='p-2 text-left'>{t('Last execution')}</th><th className='p-2 text-left'>{t('Production')}</th><th className='p-2 text-left'>{t('Attention')}</th><th className='p-2 text-left'>{t('Actions')}</th>
          </tr></thead>
          <tbody>{bulletins.map((b) => <tr key={b.id} className='border-t align-top'>
            <td className='p-2'><div className='font-medium'>{b.name}</div><div className='text-xs text-slate-500'>{b.scope_label}</div></td>
            <td className='p-2'>{b.has_schedule ? <button className={`rounded-full px-3 py-1 text-xs ${b.automation_enabled ? 'bg-emerald-600 text-white' : 'bg-slate-300 text-slate-900'}`} onClick={() => router.post(route('editor.automation.schedules.toggle', b.schedule_id))}>{b.automation_enabled ? t('On') : t('Off')}</button> : <Button size='sm' onClick={() => router.post(route('editor.automation.bulletin-types.sync-schedule', b.id))}>{t('Sync schedule')}</Button>}</td>
            <td className='p-2 text-xs'><div>{b.frequency_label || '-'}</div><div>{b.days_label || '-'}</div><div>{b.run_time_label || '-'} <span className='text-slate-500'>{b.timezone || ''}</span></div></td>
            <td className='p-2 text-xs'><div>{formatDateTime(b.next_run_at) || '-'}</div><span className={`rounded px-2 py-0.5 ${badgeClass[b.next_run_status] || badgeClass.missing}`}>{t(b.next_run_status_label_key)}</span>{b.overdue_minutes ? <div className='text-amber-700'>{t('Overdue by')} {b.overdue_minutes}m</div> : null}</td>
            <td className='p-2 text-xs'><div>{formatDateTime(b.last_run_at) || t('No executions yet')}</div><div className='text-slate-500'>{t(b.last_run_status_label_key || 'No executions yet')}</div></td>
            <td className='p-2 text-xs'><div>{t('Prompt runs')}: {b.prompt_runs_count}</div><div>{t('Scripts count')}: {b.scripts_count}</div>{b.latest_script ? <Link className='underline' href={route('editor.scripts.show', b.latest_script.id)}>{t('Latest script')}</Link> : <span className='text-slate-500'>{t('No latest script')}</span>}</td>
            <td className='p-2 text-xs'>{b.attention_reason_keys?.length ? b.attention_reason_keys.map((k: string) => <div key={k}>{t(k)}</div>) : t('Correct')}</td>
            <td className='p-2'><div className='flex flex-col gap-1'>
              {b.has_schedule && b.next_run_status === 'overdue' ? <Button size='sm' onClick={() => router.post(route('editor.automation.schedules.run-overdue-now', b.schedule_id))}>{t('Run now and schedule next')}</Button> : b.has_schedule ? <Button size='sm' variant='outline' onClick={() => router.post(route('editor.automation.schedules.run-now', b.schedule_id))}>{t('Run now')}</Button> : null}
              {b.has_schedule && <Button size='sm' variant='outline' onClick={() => router.post(route('editor.automation.schedules.recalculate-next-run', b.schedule_id))}>{t('Recalculate')}</Button>}
              <Link className='underline' href={route('editor.bulletin-prompt-runs.index', { bulletin_type_id: b.id })}>{t('View executions')}</Link>
              <Link className='underline' href={route('editor.scripts.index', { bulletin_type_id: b.id })}>{t('View scripts')}</Link>
              <Link className='underline' href={route('editor.bulletin-types.edit', b.id)}>{t('Edit bulletin')}</Link>
            </div></td>
          </tr>)}</tbody>
        </table>
      </div>
    </div>
  </EditorLayout>;
}
