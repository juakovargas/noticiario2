import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ bulletins }: { bulletins: any[] }) {
  const { t } = useTranslations();
  const { formatDateTime } = useDateFormatter();

  return <EditorLayout><Head title={t('Editorial automation')} />
    <div className="space-y-4">
      <AdminPageHeader helpKey="editor.automation.index" title={t('Editorial automation')} description={t('Control which informativos are active and see recent results.')} />
      <div className='flex gap-2'><Button onClick={() => router.post(route('editor.automation.sync-schedules'))}>{t('Sync schedules')}</Button></div>
      <div className='overflow-x-auto rounded border'>
        <table className='min-w-full text-sm'>
          <thead><tr className='bg-slate-100 dark:bg-slate-800'><th>Informativo</th><th>{t('Status')}</th><th>{t('Time')}</th><th>{t('Frequency')}</th><th>{t('Next run')}</th><th>{t('Last run')}</th><th>{t('Last result')}</th><th>{t('Latest script')}</th><th>{t('Attention')}</th><th>{t('Actions')}</th></tr></thead>
          <tbody>{bulletins.map((b) => {
            const s = b.primary_schedule ?? b.schedules?.[0];
            return <tr key={b.id} className='border-t align-top'>
              <td className='p-2'><div className='font-medium'>{b.name}</div><div className='text-xs text-slate-500'>{b.location} · {b.category} · {b.language} · {b.edition_type}</div></td>
              <td className='p-2'>{s ? <Button size='sm' variant='outline' onClick={()=>router.post(route('editor.automation.schedules.toggle', s.id))}>{s.is_active ? t('Automation enabled') : t('Automation disabled')}</Button> : t('Missing schedule')}</td>
              <td className='p-2'>{s ? `${s.run_time ?? '-'} ${s.timezone ?? ''}` : '-'}</td>
              <td className='p-2'>{s?.frequency ?? '-'}</td>
              <td className='p-2'>{formatDateTime(s?.next_run_at)}</td><td className='p-2'>{formatDateTime(s?.last_run_at)}</td>
              <td className='p-2'>{b.latest_execution?.schedule_run?.status ?? '-'}</td>
              <td className='p-2'>{b.latest_execution?.script ? <Link className='underline' href={route('editor.scripts.show', b.latest_execution.script.id)}>{t('View script')}</Link> : '-'}</td>
              <td className='p-2'>{b.attention_reasons?.length ? b.attention_reasons.join(', ') : '-'}</td>
              <td className='p-2 flex flex-col gap-1'>{!s && <Button size='sm' onClick={()=>router.post(route('editor.automation.bulletin-types.sync-schedule', b.id))}>{t('Sync schedule')}</Button>}{s && <><Button size='sm' variant='outline' onClick={()=>router.post(route('editor.automation.schedules.run-now', s.id))}>{t('Run now')}</Button><Button size='sm' variant='outline' onClick={()=>router.post(route('editor.automation.schedules.recalculate-next-run', s.id))}>{t('Recalculate')}</Button></>}<Link className='underline' href={route('editor.bulletin-types.edit', b.id)}>{t('Edit bulletin')}</Link></td>
            </tr>;
          })}</tbody>
        </table>
      </div>
    </div>
  </EditorLayout>;
}
