import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ log }: any): JSX.Element {
  const { t } = useTranslations();
  return <EditorLayout><Head title={t('AI request details')} />
    <AdminPageHeader title={t('AI request details')} description={t('Safe metadata')} />
    <Card><CardContent className="pt-6 space-y-2 text-sm">
      <p><strong>{t('Status')}:</strong> {log.status}</p>
      <p><strong>{t('AI provider')}:</strong> {log.provider?.name} ({log.provider?.provider_type})</p>
      <p><strong>{t('Model')}:</strong> {log.model || '-'}</p>
      <p><strong>{t('Related prompt run')}:</strong> {log.bulletin_prompt_run ? <Link className="text-cyan-700" href={route('editor.bulletin-prompt-runs.show', log.bulletin_prompt_run.id)}>{log.bulletin_prompt_run.title || `#${log.bulletin_prompt_run.id}`}</Link> : '-'}</p>
      <p><strong>{t('Error code')}:</strong> {log.error_code || '-'}</p>
      <p><strong>{t('Error message')}:</strong> {log.error_message || '-'}</p>
      <p><strong>{t('Provider status code')}:</strong> {log.provider_status_code || '-'}</p>
      <p><strong>{t('Total tokens')}:</strong> {log.total_tokens ?? '-'}</p>
      <p><strong>{t('Estimated cost')}:</strong> {log.estimated_cost ?? '-'}</p>
      <p><strong>{t('Duration')}:</strong> {log.duration_ms ?? '-'} ms</p>
    </CardContent></Card></EditorLayout>;
}
