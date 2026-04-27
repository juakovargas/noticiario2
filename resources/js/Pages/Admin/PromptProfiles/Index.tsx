import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ profiles }: any): JSX.Element {
 const { t } = useTranslations();
 return <AdminLayout><Head title={t('Prompt Profiles')} /><AdminPageHeader title={t('Prompt Profiles')} description={t('External AI workflow')} />
 <div className="mb-4"><Button asChild><Link href={route('admin.prompt-profiles.create')}>{t('Create Prompt Profile')}</Link></Button></div>
 <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[800px] text-sm"><thead><tr className="border-b"><th className="px-2 pb-3 text-left">{t('Name')}</th><th className="px-2 pb-3 text-left">{t('Status')}</th><th className="px-2 pb-3 text-left">{t('Default')}</th><th className="px-2 pb-3 text-right">{t('Actions')}</th></tr></thead><tbody>{profiles.data.length ? profiles.data.map((item:any)=><tr className="border-b" key={item.id}><td className="px-2 py-3">{item.name}</td><td className="px-2 py-3">{item.is_active ? t('Active') : t('Inactive')}</td><td className="px-2 py-3">{item.is_default ? t('Yes') : t('No')}</td><td className="px-2 py-3 text-right"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="outline"><Link href={route('admin.prompt-profiles.show', item.id)}>{t('Show')}</Link></Button><Button asChild size="sm" variant="outline"><Link href={route('admin.prompt-profiles.edit', item.id)}>{t('Edit')}</Link></Button></div></td></tr>) : <tr><td colSpan={4} className="px-2 py-6 text-center">-</td></tr>}</tbody></table><Pagination links={profiles.links} /></CardContent></Card>
 </AdminLayout>;
}
