import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

type Props = {
    stats: Record<string, number>;
};

export default function Dashboard({ stats }: Props): JSX.Element {
    const { t } = useTranslations();

    const cards = [
        { label: t('Active informativos'), value: stats.activeBulletinTypes ?? 0 },
        { label: t('Due today'), value: stats.dueSchedules ?? 0 },
        { label: t('Generated today'), value: stats.generatedToday ?? stats.runsCreatedToday ?? 0 },
        { label: t('Failed today'), value: stats.failedToday ?? stats.failedRunsToday ?? 0 },
        { label: t('Scripts pending review'), value: stats.scriptsPendingReview ?? 0 },
        { label: t('Sources pending verification'), value: stats.scriptsBlockedBySources ?? 0 },
        { label: t('Ready for production'), value: stats.scriptsReadyForProduction ?? 0 },
    ];

    return (
        <EditorLayout>
            <Head title={t('Panel de trabajo')} />
            <AdminPageHeader helpKey="editor.dashboard" title={t('Panel de trabajo')} description={t('Editorial workspace focused on today\'s priorities.')} />

            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                {cards.map((card) => (
                    <Card key={card.label}>
                        <CardHeader>
                            <CardDescription>{card.label}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <CardTitle>{card.value}</CardTitle>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>{t('Next recommended actions')}</CardTitle>
                </CardHeader>
                <CardContent className="grid gap-2 text-sm md:grid-cols-2">
                    <Link className="underline" href={route('editor.automation.index')}>{t('Open Automation')}</Link>
                    <Link className="underline" href={route('editor.bulletin-prompt-runs.index')}>{t('Review failed executions')}</Link>
                    <Link className="underline" href={route('editor.scripts.review')}>{t('Review scripts')}</Link>
                    <Link className="underline" href={route('editor.source-references.index')}>{t('Verify sources')}</Link>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
