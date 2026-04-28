import WorldBulletinMap, { MapMarker } from '@/Components/Maps/WorldBulletinMap';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import ViewerLayout from '@/Layouts/ViewerLayout';
import { Head } from '@inertiajs/react';

type Props = { markers: MapMarker[]; summary: Record<string, number>; };

export default function Index({ markers, summary }: Props): JSX.Element {
    const { t } = useTranslations();

    return <ViewerLayout title={t('World Map')}>
        <Head title={t('World Map')} />
        <div className="mb-4 grid gap-3 sm:grid-cols-2">
            <Card><CardHeader><CardTitle>{t('Locations on map')}</CardTitle></CardHeader><CardContent>{summary.locations_on_map ?? 0}</CardContent></Card>
            <Card><CardHeader><CardTitle>{t('Available content')}</CardTitle></CardHeader><CardContent>{summary.available_content ?? 0}</CardContent></Card>
        </div>
        <Card><CardContent className="pt-6"><WorldBulletinMap panel="viewer" markers={markers} /></CardContent></Card>
    </ViewerLayout>;
}
