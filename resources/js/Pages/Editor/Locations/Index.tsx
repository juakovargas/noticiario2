import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { countryCodeToFlagEmoji } from '@/lib/flags';
import { Head, Link, router } from '@inertiajs/react';

interface LocationItem { id:number; name:string; type:string; country_code:string|null; is_active:boolean; sort_order:number; latitude:number|null; longitude:number|null; marker_color:string|null; marker_label:string|null; show_on_map:boolean; default_language:{code:string; name:string; native_name:string|null; flag_emoji:string|null}|null; }
interface Props { locations:{data:LocationItem[]; links:Array<{url:string|null; label:string; active:boolean}>}; filters: { show_on_map:string; has_coordinates:string; } }

export default function Index({ locations, filters }: Props): JSX.Element {
    const { t } = useTranslations();
    const destroy = (id:number):void => { if (window.confirm('Delete this location?')) router.delete(route('editor.locations.destroy', id)); };

    const onFilter = (name: 'show_on_map' | 'has_coordinates', value: string): void => {
        router.get(route('editor.locations.index'), { ...filters, [name]: value }, { preserveState: true, preserveScroll: true });
    };

    return <EditorLayout><Head title={t('Locations')} /><AdminPageHeader helpKey="editor.locations.index" title={t('Locations')} description={t('Manage geographic scopes for news and editions.')} actionLabel={t('Create')} actionHref={route('editor.locations.create')} />
        <Card><CardContent className="overflow-x-auto pt-6">
            <div className="mb-4 grid gap-3 md:grid-cols-2">
                <select className="rounded-md border border-slate-300 px-3 py-2" value={filters.show_on_map} onChange={(e) => onFilter('show_on_map', e.target.value)}>
                    <option value="">{t('Show on map')}</option>
                    <option value="1">{t('Show on map')}</option>
                    <option value="0">{t('Without coordinates')}</option>
                </select>
                <select className="rounded-md border border-slate-300 px-3 py-2" value={filters.has_coordinates} onChange={(e) => onFilter('has_coordinates', e.target.value)}>
                    <option value="">{t('Has coordinates')}</option>
                    <option value="1">{t('Has coordinates')}</option>
                    <option value="0">{t('Without coordinates')}</option>
                </select>
            </div>
            <table className="w-full min-w-[1100px] text-left text-sm"><thead><tr className="border-b border-slate-200 text-slate-500"><th className="px-2 pb-3">{t('Name')}</th><th className="px-2 pb-3">{t('Type')}</th><th className="px-2 pb-3">Country</th><th className="px-2 pb-3">{t('Latitude')}</th><th className="px-2 pb-3">{t('Longitude')}</th><th className="px-2 pb-3">{t('Marker label')}</th><th className="px-2 pb-3">{t('Marker color')}</th><th className="px-2 pb-3">{t('Show on map')}</th><th className="px-2 pb-3">{t('Status')}</th><th className="px-2 pb-3 text-right">{t('Actions')}</th></tr></thead><tbody>{locations.data.length ? locations.data.map((item)=><tr key={item.id} className="border-b border-slate-100"><td className="px-2 py-3 font-medium">{item.name}</td><td className="px-2 py-3">{item.type}</td><td className="px-2 py-3">{countryCodeToFlagEmoji(item.country_code)} {item.country_code || '-'}</td><td className="px-2 py-3">{item.latitude ?? '-'}</td><td className="px-2 py-3">{item.longitude ?? '-'}</td><td className="px-2 py-3">{item.marker_label || '-'}</td><td className="px-2 py-3">{item.marker_color || '-'}</td><td className="px-2 py-3">{item.show_on_map ? t('Yes') : t('No')}</td><td className="px-2 py-3">{item.is_active ? t('Active') : t('Inactive')}</td><td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="secondary"><Link href={route('editor.locations.edit', item.id)}>{t('Edit')}</Link></Button><Button size="sm" variant="destructive" onClick={()=>destroy(item.id)}>{t('Delete')}</Button></div></td></tr>) : <tr><td colSpan={10} className="px-2 py-6 text-center text-slate-500">{t('No locations yet.')}</td></tr>}</tbody></table><Pagination links={locations.links} /></CardContent></Card></EditorLayout>;
}
