import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

type Props = {
    stats: Record<string, number>;
};

export default function Dashboard({ stats }: Props): JSX.Element {
    const { t } = useTranslations();

    return (
        <EditorLayout>
            <Head title={t('Dashboard')} />
            <AdminPageHeader title={t('Dashboard')} description={t('What needs attention')} />

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>{t('Continue workflow')}</CardTitle>
                    <CardDescription>{t('Open Editorial Workbench')}</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button asChild><Link href={route('editor.workbench')}>{t('Editorial Workbench')}</Link></Button>
                </CardContent>
            </Card>

            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <Card><CardHeader><CardDescription>{t('Prompt runs waiting for response')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.promptRunsWaitingAiResponse ?? 0}</CardTitle></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Responses ready to become scripts')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.promptRunsReadyToCreateScript ?? 0}</CardTitle></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Scripts needing review')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.scriptsPendingReview ?? 0}</CardTitle></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Scripts missing metadata')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.scriptsMissingMetadata ?? 0}</CardTitle></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Scripts ready for production')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.scriptsReadyForProduction ?? 0}</CardTitle></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Source issues pending')}</CardDescription></CardHeader><CardContent><CardTitle>{stats.scriptsBlockedBySources ?? 0}</CardTitle></CardContent></Card>
            </div>
        </EditorLayout>
    );
}
