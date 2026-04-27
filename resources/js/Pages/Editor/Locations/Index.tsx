import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { countryCodeToFlagEmoji } from '@/lib/flags';
import { Head, Link, router } from '@inertiajs/react';

interface LocationItem { id:number; name:string; type:string; country_code:string|null; is_active:boolean; sort_order:number; default_language:{code:string; name:string; native_name:string|null; flag_emoji:string|null}|null; }
interface Props { locations:{data:LocationItem[]; links:Array<{url:string|null; label:string; active:boolean}>}; }

export default function Index({ locations }: Props): JSX.Element {
    const { t } = useTranslations();
    const destroy = (id:number):void => { if (window.confirm('Delete this location?')) router.delete(route('editor.locations.destroy', id)); };
    return <EditorLayout><Head title={t('Locations')} /><AdminPageHeader title={t('Locations')} description="Manage geographic scopes for news and editions." actionLabel={t('Create')} actionHref={route('editor.locations.create')} />
        <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[900px] text-left text-sm"><thead><tr className="border-b border-slate-200 text-slate-500"><th className="px-2 pb-3">{t('Name')}</th><th className="px-2 pb-3">{t('Type')}</th><th className="px-2 pb-3">Country</th><th className="px-2 pb-3">{t('Default language')}</th><th className="px-2 pb-3">{t('Status')}</th><th className="px-2 pb-3">Sort</th><th className="px-2 pb-3 text-right">{t('Actions')}</th></tr></thead><tbody>{locations.data.length ? locations.data.map((item)=><tr key={item.id} className="border-b border-slate-100"><td className="px-2 py-3 font-medium">{item.name}</td><td className="px-2 py-3">{item.type}</td><td className="px-2 py-3">{countryCodeToFlagEmoji(item.country_code)} {item.country_code || '-'}</td><td className="px-2 py-3">{item.default_language ? `${item.default_language.flag_emoji ?? ''} ${item.default_language.native_name || item.default_language.name} (${item.default_language.code})` : t('Not configured')}</td><td className="px-2 py-3">{item.is_active ? t('Active') : t('Inactive')}</td><td className="px-2 py-3">{item.sort_order}</td><td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="secondary"><Link href={route('editor.locations.edit', item.id)}>{t('Edit')}</Link></Button><Button size="sm" variant="destructive" onClick={()=>destroy(item.id)}>{t('Delete')}</Button></div></td></tr>) : <tr><td colSpan={7} className="px-2 py-6 text-center text-slate-500">No locations yet.</td></tr>}</tbody></table><Pagination links={locations.links} /></CardContent></Card></EditorLayout>;
}
