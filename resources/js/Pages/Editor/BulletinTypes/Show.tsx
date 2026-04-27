import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import { toDateTimeLocalInputValue } from '@/lib/dates';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

export default function Show({ bulletinType }: any): JSX.Element {
 const { t } = useTranslations();
 const runForm = useForm({ scheduled_for: toDateTimeLocalInputValue(new Date()) });

 const createRun = (): void => {
  router.post(route('editor.bulletin-types.prompt-runs.store', bulletinType.id), {
   scheduled_for: runForm.data.scheduled_for ? new Date(runForm.data.scheduled_for).toISOString() : null,
  });
 };

 return <EditorLayout><Head title={bulletinType.name} /><AdminPageHeader title={bulletinType.name} description={t('Bulletin Type')} />
 <div className="mb-4 flex gap-2"><Button onClick={createRun}>{t('Create Prompt Run')}</Button><Button asChild variant="outline"><Link href={route('editor.bulletin-types.edit', bulletinType.id)}>{t('Edit')}</Link></Button></div>
 <Card><CardContent className="space-y-2 pt-6 text-sm"><p><strong>{t('Description')}:</strong> {bulletinType.description || '-'}</p><p><strong>{t('Location')}:</strong> {bulletinType.location?.name || '-'}</p><p><strong>{t('Category')}:</strong> {bulletinType.news_category?.name || '-'}</p><p><strong>{t('Language')}:</strong> {bulletinType.language?.name || '-'}</p><p><strong>{t('Default prompt profile')}:</strong> {bulletinType.prompt_profile?.name || '-'}</p><p><strong>{t('Coverage mode')}:</strong> {t(bulletinType.coverage_mode === 'previous_period' ? 'Previous period' : bulletinType.coverage_mode === 'today_so_far' ? 'Today so far' : bulletinType.coverage_mode === 'yesterday' ? 'Yesterday' : bulletinType.coverage_mode === 'last_24_hours' ? 'Last 24 hours' : bulletinType.coverage_mode === 'next_24_hours' ? 'Next 24 hours' : bulletinType.coverage_mode === 'custom' ? 'Custom coverage' : 'No strict coverage')}</p><p><strong>{t('Coverage starts offset')}:</strong> {bulletinType.coverage_starts_offset_minutes ?? '-'}</p><p><strong>{t('Coverage ends offset')}:</strong> {bulletinType.coverage_ends_offset_minutes ?? '-'}</p><p><strong>{t('Prompt language')}:</strong> {bulletinType.prompt_language ?? '-'}</p><p><strong>{t('Output mode')}:</strong> {bulletinType.output_mode ?? '-'}</p></CardContent></Card>
 <Card className="mt-4"><CardContent className="pt-6"><h3 className="mb-3 font-semibold">{t('Create run for date/time')}</h3><div className="flex items-end gap-3"><div><Label>{t('Scheduled date/time')}</Label><Input type="datetime-local" value={runForm.data.scheduled_for} onChange={(e)=>runForm.setData('scheduled_for', e.target.value)} /></div><Button variant="outline" onClick={createRun}>{t('Create Prompt Run')}</Button></div></CardContent></Card>
 </EditorLayout>;
}
