import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ bulletinTypes }: any): JSX.Element {
 const { t } = useTranslations();
 return <EditorLayout><Head title={t('Bulletin Types')} /><AdminPageHeader title={t('Bulletin Types')} description={t('Noticiario type')} />
 <div className="mb-4 flex gap-2"><Button asChild><Link href={route('editor.bulletin-types.create')}>{t('Create Bulletin Type')}</Link></Button><Button asChild variant="outline"><Link href={route('editor.bulletin-prompt-runs.index')}>{t('Prompt Runs')}</Link></Button></div>
 <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[900px] text-sm"><thead><tr className="border-b"><th className="px-2 pb-3 text-left">{t('Name')}</th><th className="px-2 pb-3 text-left">{t('Category')}</th><th className="px-2 pb-3 text-left">{t('Language')}</th><th className="px-2 pb-3 text-left">{t('Default prompt profile')}</th><th className="px-2 pb-3 text-right">{t('Actions')}</th></tr></thead><tbody>{bulletinTypes.data.length ? bulletinTypes.data.map((item:any)=><tr className="border-b" key={item.id}><td className="px-2 py-3">{item.name}</td><td className="px-2 py-3">{item.news_category?.name ?? '-'}</td><td className="px-2 py-3">{item.language?.name ?? '-'}</td><td className="px-2 py-3">{item.prompt_profile?.name ?? '-'}</td><td className="px-2 py-3 text-right"><div className="flex justify-end gap-2"><Button size="sm" onClick={()=>router.post(route('editor.bulletin-types.prompt-runs.store', item.id))}>{t('Create Prompt Run')}</Button><Button asChild size="sm" variant="outline"><Link href={route('editor.bulletin-types.show', item.id)}>{t('Show')}</Link></Button><Button asChild size="sm" variant="outline"><Link href={route('editor.bulletin-types.edit', item.id)}>{t('Edit')}</Link></Button></div></td></tr>) : <tr><td colSpan={5} className="px-2 py-6 text-center">-</td></tr>}</tbody></table><Pagination links={bulletinTypes.links} /></CardContent></Card>
 </EditorLayout>;
}
