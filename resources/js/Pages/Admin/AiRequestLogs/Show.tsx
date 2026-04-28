import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ log }: any): JSX.Element {
    const { t } = useTranslations();
    return <AdminLayout><Head title={`${t('AI request log')} #${log.id}`} /><AdminPageHeader title={`${t('AI request log')} #${log.id}`} description={t('AI Request Logs')} />
        <Card><CardContent className="space-y-2 pt-6 text-sm"><p><strong>{t('AI provider')}:</strong> {log.provider?.name || '-'}</p><p><strong>{t('Model')}:</strong> {log.model || '-'}</p><p><strong>{t('Status')}:</strong> {log.status}</p><p><strong>{t('User')}:</strong> {log.user?.name || '-'}</p><p><strong>{t('Request type')}:</strong> {log.request_type}</p><p><strong>{t('Prompt hash')}:</strong> {log.prompt_hash || '-'}</p><p><strong>{t('Prompt preview')}:</strong> {log.prompt_preview || '-'}</p><p><strong>{t('Response preview')}:</strong> {log.response_preview || '-'}</p><p><strong>{t('Input tokens')}:</strong> {log.input_tokens ?? '-'}</p><p><strong>{t('Output tokens')}:</strong> {log.output_tokens ?? '-'}</p><p><strong>{t('Total tokens')}:</strong> {log.total_tokens ?? '-'}</p><p><strong>{t('Estimated cost')}:</strong> {log.estimated_cost ?? '-'}</p><p><strong>{t('Duration')}:</strong> {log.duration_ms ?? '-'} ms</p><p><strong>{t('Started at')}:</strong> {log.started_at || '-'}</p><p><strong>{t('Completed at')}:</strong> {log.completed_at || '-'}</p><p><strong>{t('Error message')}:</strong> {log.error_message || '-'}</p>{log.bulletin_prompt_run_id ? <p><Link className="text-cyan-700" href={route('editor.bulletin-prompt-runs.show', log.bulletin_prompt_run_id)}>{t('Open')}</Link></p> : null}</CardContent></Card></AdminLayout>;
}
