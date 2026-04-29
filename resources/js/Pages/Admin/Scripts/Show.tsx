import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link } from '@inertiajs/react';

export default function Show({ script, returnToMessages }: any) {
  const { t } = useTranslations();
  const { formatDateTime } = useDateFormatter();

  return <AdminLayout>
    <Head title={t('Admin script viewer')} />
    <div className='space-y-4'>
      <Card>
        <CardHeader><CardTitle>{t('Read-only admin viewer')}</CardTitle></CardHeader>
        <CardContent className='text-sm space-y-2'>
          <p>{t('This is a read-only admin view')}</p>
          <p>{t('Admin can inspect this item without entering the editor panel')}</p>
          <p>{t('Use impersonation only when intentionally testing as another user')}</p>
          <p>{t('Cross-panel links are not allowed')}</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>{script.final_title ?? script.title}</CardTitle></CardHeader>
        <CardContent className='grid gap-2 text-sm'>
          <p><strong>{t('Title')}:</strong> {script.title ?? '-'}</p>
          <p><strong>{t('Final title')}:</strong> {script.final_title ?? '-'}</p>
          <p><strong>{t('Production name')}:</strong> {script.production_name ?? '-'}</p>
          <p><strong>{t('Status')}:</strong> {script.status ?? '-'}</p>
          <p><strong>{t('Production status')}:</strong> {script.production_status ?? '-'}</p>
          <p><strong>{t('Language')}:</strong> {script.language ?? '-'}</p>
          <p><strong>{t('Intro')}:</strong> {script.intro ?? '-'}</p>
          <p><strong>{t('Body')}:</strong> {script.body ?? '-'}</p>
          <p><strong>{t('Outro')}:</strong> {script.outro ?? '-'}</p>
          <p><strong>{t('Related prompt run')}:</strong> {script.bulletin_prompt_run?.title ?? '-'}</p>
          <p><strong>{t('Edition')}:</strong> {script.edition?.name ?? '-'}</p>
          <p><strong>{t('Created by')}:</strong> {script.bulletin_prompt_run?.created_by?.name ?? '-'}</p>
          <p><strong>{t('Review status')}:</strong> {script.review_status ?? '-'}</p>
          <p><strong>{t('Source verification summary')}:</strong> {script.fact_check_notes ?? '-'}</p>
          <p><strong>{t('Production metadata')}:</strong> {script.metadata ? JSON.stringify(script.metadata) : '-'}</p>
          <p><strong>{t('Created at')}:</strong> {formatDateTime(script.created_at)}</p>
          <p><strong>{t('Updated at')}:</strong> {formatDateTime(script.updated_at)}</p>
        </CardContent>
      </Card>
      {returnToMessages && <Link className='underline text-sm' href={route('messages.index')}>{t('Open messages')}</Link>}
    </div>
  </AdminLayout>;
}
