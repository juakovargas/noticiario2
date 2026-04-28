import { Card, CardContent } from '@/Components/ui/card';
import PageHelp from '@/Components/Help/PageHelp';
import { getPageHelp } from '@/help/pageHelp';
import { useTranslations } from '@/i18n/useTranslations';
import ViewerLayout from '@/Layouts/ViewerLayout';
import { Head } from '@inertiajs/react';

export default function PublishedContent(): JSX.Element {
    const { t } = useTranslations();

    return (
        <ViewerLayout title={t('Published Content')}>
            <Head title={t('Published Content')} />
            <div className="mb-2 flex justify-end"><PageHelp help={getPageHelp(t, 'viewer.published-content')} /></div>

            <Card>
                <CardContent className="pt-6">
                    <p className="text-sm text-slate-700 dark:text-slate-300">{t('Future published content and read-only viewer data will appear in this panel.')}</p>
                </CardContent>
            </Card>
        </ViewerLayout>
    );
}
