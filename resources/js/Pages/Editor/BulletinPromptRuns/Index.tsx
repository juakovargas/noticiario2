import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ runs }: any): JSX.Element {
 const { t } = useTranslations(); const { formatDateTime } = useDateFormatter();
 return <EditorLayout><Head title={t('Prompt Runs')} /><AdminPageHeader title={t('Prompt Runs')} description={t('External AI workflow')} />
 <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[900px] text-sm"><thead><tr className="border-b"><th className="px-2 pb-3 text-left">ID</th><th className="px-2 pb-3 text-left">{t('Bulletin Type')}</th><th className="px-2 pb-3 text-left">{t('Prompt Profile')}</th><th className="px-2 pb-3 text-left">{t('Status')}</th><th className="px-2 pb-3 text-left">{t('Scheduled for')}</th><th className="px-2 pb-3 text-right">{t('Actions')}</th></tr></thead><tbody>{runs.data.length ? runs.data.map((item:any)=><tr className="border-b" key={item.id}><td className="px-2 py-3">#{item.id}</td><td className="px-2 py-3">{item.bulletin_type?.name}</td><td className="px-2 py-3">{item.prompt_profile?.name ?? '-'}</td><td className="px-2 py-3">{item.status}</td><td className="px-2 py-3">{formatDateTime(item.scheduled_for)}</td><td className="px-2 py-3 text-right"><Button asChild size="sm" variant="outline"><Link href={route('editor.bulletin-prompt-runs.show', item.id)}>{t('Open')}</Link></Button></td></tr>) : <tr><td colSpan={6} className="px-2 py-6 text-center">-</td></tr>}</tbody></table><Pagination links={runs.links} /></CardContent></Card>
 </EditorLayout>;
}
