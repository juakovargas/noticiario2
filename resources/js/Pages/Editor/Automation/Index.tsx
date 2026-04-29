import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useTranslations } from '@/i18n/useTranslations';

export default function Index({ bulletins }: { bulletins: any[] }) {
  const { t } = useTranslations();
  return <EditorLayout><Head title={t('Automation control panel')} />
    <div className='space-y-4'>
      <h1 className='text-2xl font-semibold'>{t('Automation control panel')}</h1>
      {bulletins.map((b) => <div key={b.id} className='rounded border p-4'>
        <div className='flex justify-between'><div>
          <p className='font-semibold'>{b.name}</p>
          <p className='text-sm'>{b.language} · {b.location} · {b.edition_type}</p>
        </div>
        <button className='text-sm underline' onClick={() => router.post(route('editor.automation.bulletin-types.toggle', b.id))}>{t('Turn on')} / {t('Turn off')}</button>
        </div>
        {b.schedules.map((s:any)=><div key={s.id} className='mt-2 border-t pt-2 text-sm'>
          <p>{t('Scheduled times')}: {s.run_time} ({s.timezone})</p>
          <p>{t('Next execution')}: {s.next_run_at ?? '-'}</p>
          <p>{t('Last execution')}: {s.last_run_at ?? '-'}</p>
          <div className='flex gap-3'>
            <button className='underline' onClick={() => router.post(route('editor.automation.schedules.toggle', s.id))}>{s.is_active ? t('Turn off') : t('Turn on')}</button>
            <button className='underline' onClick={() => router.post(route('editor.automation.schedules.run-now', s.id))}>{t('Run now')}</button>
            <Link className='underline' href={route('editor.editorial-schedules.edit', s.id)}>{t('Edit schedule')}</Link>
          </div>
        </div>)}
      </div>)}
    </div>
  </EditorLayout>
}
