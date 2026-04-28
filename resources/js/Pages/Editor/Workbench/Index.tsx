import AdminPageHeader from '@/Components/AdminPageHeader';
import StatusBadge from '@/Components/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

type Item = { id: number; title: string; status: string; bulletin_type?: { name: string } | null };

type Props = {
    cards: Record<string, number>;
    steps: {
        activeBulletinTypes: Array<{ id: number; name: string }>;
        draftRuns: Item[];
    };
    lists: {
        readyForResponseRuns: Item[];
        readyForScriptRuns: Item[];
        scriptsNeedingReview: Item[];
        scriptsMissingMetadata: Item[];
        scriptsReadyForProduction: Item[];
    };
};

function ListCard({ title, items, empty, ctaLabel, ctaRoute }: { title: string; items: Item[]; empty: string; ctaLabel: string; ctaRoute: string }): JSX.Element {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {items.length === 0 ? <p className="text-sm text-slate-500">{empty}</p> : items.map((item) => (
                    <div key={item.id} className="rounded border p-3 text-sm">
                        <p className="font-medium">{item.title}</p>
                        <StatusBadge status={item.status} className="mt-1" />
                        <div className="mt-2">
                            <Button asChild size="sm" variant="outline"><Link href={route(ctaRoute, item.id)}>{ctaLabel}</Link></Button>
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}

export default function Index({ cards, steps, lists }: Props): JSX.Element {
    const { t } = useTranslations();

    return (
        <EditorLayout>
            <Head title={t('Editorial Workbench')} />
            <AdminPageHeader title={t('Editorial Workbench')} description={t('What needs attention')} />

            <div className="mb-6 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                <Card><CardHeader><CardDescription>{t('Prompt runs waiting for response')}</CardDescription></CardHeader><CardContent><p className="text-2xl font-bold">{cards.prompt_runs_waiting_for_response ?? 0}</p></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Responses ready to become scripts')}</CardDescription></CardHeader><CardContent><p className="text-2xl font-bold">{cards.responses_ready_to_become_scripts ?? 0}</p></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Scripts needing review')}</CardDescription></CardHeader><CardContent><p className="text-2xl font-bold">{cards.scripts_needing_review ?? 0}</p></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Scripts missing metadata')}</CardDescription></CardHeader><CardContent><p className="text-2xl font-bold">{cards.scripts_missing_metadata ?? 0}</p></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Scripts ready for production')}</CardDescription></CardHeader><CardContent><p className="text-2xl font-bold">{cards.scripts_ready_for_production ?? 0}</p></CardContent></Card>
                <Card><CardHeader><CardDescription>{t('Source issues pending')}</CardDescription></CardHeader><CardContent><p className="text-2xl font-bold">{cards.source_issues_pending ?? 0}</p></CardContent></Card>
            </div>

            <Card className="mb-6">
                <CardHeader><CardTitle>{t('Continue workflow')}</CardTitle></CardHeader>
                <CardContent className="space-y-3 text-sm">
                    <p><strong>{t('Step 1')}:</strong> {t('Choose noticiario / Bulletin Type')}</p>
                    <p><strong>{t('Step 2')}:</strong> {t('Generate prompt')}</p>
                    <p><strong>{t('Step 3')}:</strong> {t('Get AI response')}</p>
                    <p><strong>{t('Step 4')}:</strong> {t('Create script')}</p>
                    <p><strong>{t('Step 5')}:</strong> {t('Review and verify')}</p>
                    <p><strong>{t('Step 6')}:</strong> {t('Approve and prepare for production')}</p>
                    <div className="flex gap-2 pt-1">
                        <Button asChild><Link href={route('editor.bulletin-types.index')}>{t('Manage Bulletin Types')}</Link></Button>
                        <Button asChild variant="outline"><Link href={route('editor.bulletin-prompt-runs.index')}>{t('Prompt Runs')}</Link></Button>
                    </div>
                </CardContent>
            </Card>

            <div className="grid gap-4 lg:grid-cols-2">
                <ListCard title={t('Step 3: Get AI response')} items={lists.readyForResponseRuns} empty={t('No tasks pending')} ctaLabel={t('Open Prompt Run')} ctaRoute="editor.bulletin-prompt-runs.show" />
                <ListCard title={t('Step 4: Create script')} items={lists.readyForScriptRuns} empty={t('No tasks pending')} ctaLabel={t('Create Script')} ctaRoute="editor.bulletin-prompt-runs.show" />
                <ListCard title={t('Step 5: Review and verify')} items={lists.scriptsNeedingReview} empty={t('No tasks pending')} ctaLabel={t('Review script')} ctaRoute="editor.scripts.review" />
                <ListCard title={t('Step 6: Prepare publishing metadata')} items={lists.scriptsMissingMetadata} empty={t('No tasks pending')} ctaLabel={t('Prepare production metadata')} ctaRoute="editor.scripts.production.edit" />
                <ListCard title={t('Ready for audio/video')} items={lists.scriptsReadyForProduction} empty={t('No tasks pending')} ctaLabel={t('Open Script')} ctaRoute="editor.scripts.show" />
            </div>
        </EditorLayout>
    );
}
