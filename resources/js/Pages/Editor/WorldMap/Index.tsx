import WorldBulletinMap, { MapMarker } from '@/Components/Maps/WorldBulletinMap';
import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

type Props = { markers: MapMarker[]; summary: Record<string, number>; };

export default function Index({ markers, summary }: Props): JSX.Element {
    const { t } = useTranslations();

    return <EditorLayout>
        <Head title={t('World Map')} />
        <AdminPageHeader title={t('World Map')} description={t('View noticiario coverage by country')} />
        <div className="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <Card><CardHeader><CardTitle>{t('Active locations')}</CardTitle></CardHeader><CardContent>{summary.active_locations ?? 0}</CardContent></Card>
            <Card><CardHeader><CardTitle>{t('Active bulletin count')}</CardTitle></CardHeader><CardContent>{summary.active_bulletin_types ?? 0}</CardContent></Card>
            <Card><CardHeader><CardTitle>{t('Pending prompt runs')}</CardTitle></CardHeader><CardContent>{summary.pending_prompt_runs ?? 0}</CardContent></Card>
            <Card><CardHeader><CardTitle>{t('Completed prompt runs')}</CardTitle></CardHeader><CardContent>{summary.completed_prompt_runs ?? 0}</CardContent></Card>
        </div>
        <Card><CardContent className="pt-6"><WorldBulletinMap panel="editor" markers={markers} /></CardContent></Card>
        <Card className="mt-4"><CardContent className="pt-6 overflow-x-auto"><table className="w-full text-sm"><thead><tr className="border-b"><th className="py-2 text-left">{t('Location')}</th><th>{t('Bulletin Types')}</th><th>{t('Pending prompt runs')}</th><th>{t('Latest status')}</th><th>{t('Actions')}</th></tr></thead><tbody>{markers.map((m) => <tr key={m.location_id} className="border-b"><td className="py-2">{m.location_name}</td><td>{m.bulletin_count}</td><td>{m.pending_prompt_runs_count}</td><td>{m.latest_prompt_run_status ?? '-'}</td><td className="space-x-2"><Link className="text-cyan-700" href={route('editor.bulletin-types.index', { location_id: m.location_id })}>{t('Open bulletin type')}</Link><Link className="text-cyan-700" href={route('editor.bulletin-prompt-runs.index', { location_id: m.location_id })}>{t('Open prompt runs')}</Link></td></tr>)}</tbody></table></CardContent></Card>
    </EditorLayout>;
}
