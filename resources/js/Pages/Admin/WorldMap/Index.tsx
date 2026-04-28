import WorldBulletinMap, { MapMarker } from '@/Components/Maps/WorldBulletinMap';
import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

type Props = { markers: MapMarker[]; summary: Record<string, number>; };

export default function Index({ markers, summary }: Props): JSX.Element {
    const { t } = useTranslations();

    return <AdminLayout>
        <Head title={t('World Map')} />
        <AdminPageHeader title={t('World Map')} description={t('View configured bulletin locations')} />

        <div className="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <Card><CardHeader><CardTitle>{t('Mapped locations')}</CardTitle></CardHeader><CardContent>{summary.mapped_locations ?? 0}</CardContent></Card>
            <Card><CardHeader><CardTitle>{t('Active bulletin count')}</CardTitle></CardHeader><CardContent>{summary.active_bulletin_types ?? 0}</CardContent></Card>
            <Card><CardHeader><CardTitle>{t('Pending prompt runs')}</CardTitle></CardHeader><CardContent>{summary.pending_prompt_runs ?? 0}</CardContent></Card>
            <Card><CardHeader><CardTitle>{t('Completed prompt runs')}</CardTitle></CardHeader><CardContent>{summary.completed_prompt_runs ?? 0}</CardContent></Card>
        </div>

        <Card><CardContent className="pt-6"><WorldBulletinMap panel="admin" markers={markers} /></CardContent></Card>

        <Card className="mt-4"><CardContent className="pt-6 overflow-x-auto"><table className="w-full text-sm"><thead><tr className="border-b"><th className="py-2 text-left">{t('Location')}</th><th className="py-2 text-left">{t('Bulletin count')}</th><th className="py-2 text-left">{t('Active bulletin count')}</th><th className="py-2 text-left">{t('Latest status')}</th><th className="py-2 text-left">{t('Actions')}</th></tr></thead><tbody>{markers.map((m) => <tr key={m.location_id} className="border-b"><td className="py-2">{m.location_name}</td><td>{m.bulletin_count}</td><td>{m.active_bulletin_count}/{m.inactive_bulletin_count ?? 0}</td><td>{m.latest_prompt_run_status ?? '-'}</td><td><Link className="text-cyan-700" href={route('editor.locations.index', { show_on_map: 1 })}>{t('Open location')}</Link></td></tr>)}</tbody></table></CardContent></Card>
    </AdminLayout>;
}
