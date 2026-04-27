import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';

interface Script {
    title: string;
    edition: { id: number; title: string } | null;
    status: string;
    language: string | null;
    intro: string | null;
    body: string | null;
    outro: string | null;
    estimated_duration_seconds: number | null;
    approved_at: string | null;
    approved_by: string | null;
}

interface Props {
    script: Script;
}

export default function Show({ script }: Props): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();

    return (
        <EditorLayout>
            <Head title={script.title} />
            <AdminPageHeader title={script.title} description="Script detail." />
            <Card>
                <CardContent className="space-y-3 pt-6 text-sm">
                    <p>
                        <strong>{t('Editions')}:</strong> {script.edition?.title || '-'}
                    </p>
                    <p>
                        <strong>{t('Status')}:</strong> {script.status}
                    </p>
                    <p>
                        <strong>{t('Language')}:</strong> {script.language || '-'}
                    </p>
                    <p>
                        <strong>{t('Intro')}:</strong> {script.intro || '-'}
                    </p>
                    <p>
                        <strong>{t('Body')}:</strong> {script.body || '-'}
                    </p>
                    <p>
                        <strong>{t('Outro')}:</strong> {script.outro || '-'}
                    </p>
                    <p>
                        <strong>{t('Estimated Duration')}:</strong> {script.estimated_duration_seconds || '-'} seconds
                    </p>
                    <p>
                        <strong>{t('Approved at')}:</strong> {formatDateTime(script.approved_at)}
                    </p>
                    <p>
                        <strong>Approved by:</strong> {script.approved_by || '-'}
                    </p>
                    <Button asChild variant="secondary">
                        <Link href={route('editor.scripts.index')}>{t('Back')}</Link>
                    </Button>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
