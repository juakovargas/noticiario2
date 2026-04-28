import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';

interface Option { id:number; name:string; code?:string; native_name?:string|null; flag_emoji?:string|null; }
interface Props { parents:Option[]; types:string[]; languages:Option[]; }

export default function Create({ parents, types, languages }: Props): JSX.Element {
    const { t } = useTranslations();
    const { data, setData, post, processing, errors } = useForm({ parent_id: '', name: '', slug: '', type: 'country', country_code: '', timezone: '', default_language_id: '', latitude: '', longitude: '', map_zoom: '', marker_color: '', marker_label: '', show_on_map: true, is_active: true, sort_order: 0 });
    const submit = (e: React.FormEvent) => { e.preventDefault(); post(route('editor.locations.store')); };

    return <EditorLayout><Head title={t('Create Location')} /><AdminPageHeader title={t('Create Location')} description={t('Add a new location.')} />
        <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4">
            <div><Label>{t('Name')}</Label><Input value={data.name} onChange={(e)=>setData('name', e.target.value)} />{errors.name && <p className="text-sm text-red-600">{errors.name}</p>}</div>
            <div><Label>{t('Slug')}</Label><Input value={data.slug} onChange={(e)=>setData('slug', e.target.value)} /></div>
            <div><Label>{t('Parent')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.parent_id} onChange={(e)=>setData('parent_id', e.target.value)}><option value="">{t('None')}</option>{parents.map((p)=><option key={p.id} value={p.id}>{p.name}</option>)}</select></div>
            <div><Label>{t('Type')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.type} onChange={(e)=>setData('type', e.target.value)}>{types.map((type)=><option key={type} value={type}>{type}</option>)}</select></div>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Country code')}</Label><Input value={data.country_code} onChange={(e)=>setData('country_code', e.target.value)} /></div><div><Label>{t('Timezone')}</Label><Input value={data.timezone} onChange={(e)=>setData('timezone', e.target.value)} /></div></div>
            <div><Label>{t('Default language')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.default_language_id} onChange={(e)=>setData('default_language_id', e.target.value)}><option value="">{t('Not configured')}</option>{languages.map((l)=><option key={l.id} value={l.id}>{l.flag_emoji} {l.native_name || l.name} ({l.code})</option>)}</select></div>
            <p className="text-xs text-slate-500">{t('Coordinates are used to place this location on the world map.')}</p>
            <p className="text-xs text-slate-500">{t('Locations without coordinates will not be shown on the map.')}</p>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Latitude')}</Label><Input value={data.latitude} onChange={(e)=>setData('latitude', e.target.value)} /></div><div><Label>{t('Longitude')}</Label><Input value={data.longitude} onChange={(e)=>setData('longitude', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Map zoom')}</Label><Input type="number" value={data.map_zoom} onChange={(e)=>setData('map_zoom', e.target.value)} /></div><div><Label>{t('Marker color')}</Label><Input value={data.marker_color} onChange={(e)=>setData('marker_color', e.target.value)} placeholder="green"/></div><div><Label>{t('Marker label')}</Label><Input value={data.marker_label} onChange={(e)=>setData('marker_label', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-3"><label className="flex items-center gap-2 pt-8"><input type="checkbox" checked={data.show_on_map} onChange={(e)=>setData('show_on_map', e.target.checked)} />{t('Show on map')}</label><div><Label>{t('Sort order')}</Label><Input type="number" value={data.sort_order} onChange={(e)=>setData('sort_order', Number(e.target.value))} /></div><label className="flex items-center gap-2 pt-8"><input type="checkbox" checked={data.is_active} onChange={(e)=>setData('is_active', e.target.checked)} />{t('Active')}</label></div>
            <div className="flex gap-2"><Button type="submit" disabled={processing}>{t('Save')}</Button><Button asChild variant="secondary"><Link href={route('editor.locations.index')}>{t('Cancel')}</Link></Button></div>
        </form></CardContent></Card></EditorLayout>;
}
