import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

interface LanguageItem { id:number; name:string; native_name:string|null; code:string; flag_emoji:string|null; is_active:boolean; is_default:boolean; sort_order:number; }
interface Props { languages:{data:LanguageItem[]; links:Array<{url:string|null; label:string; active:boolean}>}; }

export default function Index({ languages }: Props): JSX.Element {
    const { t } = useTranslations();
    const destroy = (id:number): void => { if (window.confirm(t('Delete this language?'))) router.delete(route('admin.languages.destroy', id)); };

    return <AdminLayout><Head title={t('Languages')} /><AdminPageHeader title={t('Languages')} description={t('System administration')} actionLabel={t('Create')} actionHref={route('admin.languages.create')} />
        <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[760px] text-sm"><thead><tr className="border-b text-slate-500"><th className="px-2 pb-3">{t('Name')}</th><th className="px-2 pb-3">{t('Code')}</th><th className="px-2 pb-3">{t('Status')}</th><th className="px-2 pb-3">{t('Default')}</th><th className="px-2 pb-3">Sort</th><th className="px-2 pb-3 text-right">{t('Actions')}</th></tr></thead>
        <tbody>{languages.data.map((language)=><tr key={language.id} className="border-b"><td className="px-2 py-3">{language.flag_emoji || '🌐'} {language.name} {language.native_name ? `(${language.native_name})` : ''}</td><td className="px-2 py-3">{language.code}</td><td className="px-2 py-3">{language.is_active ? t('Active') : t('Inactive')}</td><td className="px-2 py-3">{language.is_default ? t('Yes') : t('No')}</td><td className="px-2 py-3">{language.sort_order}</td><td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="secondary"><Link href={route('admin.languages.edit', language.id)}>{t('Edit')}</Link></Button><Button size="sm" variant="destructive" onClick={()=>destroy(language.id)}>{t('Delete')}</Button></div></td></tr>)}</tbody></table><Pagination links={languages.links} /></CardContent></Card></AdminLayout>;
}
