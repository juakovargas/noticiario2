import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Show({ bulletinType }: any): JSX.Element {
 const { t } = useTranslations();
 return <EditorLayout><Head title={bulletinType.name} /><AdminPageHeader title={bulletinType.name} description={t('Bulletin Type')} />
 <div className="mb-4 flex gap-2"><Button onClick={()=>router.post(route('editor.bulletin-types.prompt-runs.store', bulletinType.id))}>{t('Create Prompt Run')}</Button><Button asChild variant="outline"><Link href={route('editor.bulletin-types.edit', bulletinType.id)}>{t('Edit')}</Link></Button></div>
 <Card><CardContent className="space-y-2 pt-6 text-sm"><p><strong>{t('Description')}:</strong> {bulletinType.description || '-'}</p><p><strong>{t('Location')}:</strong> {bulletinType.location?.name || '-'}</p><p><strong>{t('Category')}:</strong> {bulletinType.news_category?.name || '-'}</p><p><strong>{t('Language')}:</strong> {bulletinType.language?.name || '-'}</p><p><strong>{t('Default prompt profile')}:</strong> {bulletinType.prompt_profile?.name || '-'}</p></CardContent></Card>
 </EditorLayout>;
}
