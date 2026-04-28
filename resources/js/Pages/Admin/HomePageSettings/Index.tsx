import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

interface Setting {
    id: number;
    locale: string | null;
    title: string;
    is_active: boolean;
}

export default function Index({ settings }: { settings: Setting[] }): JSX.Element {
    const { t } = useTranslations();
    return (
        <AdminLayout>
            <Head title={t('Home Page Settings')} />
            <div className="space-y-4">
                <Button asChild><Link href={route('admin.home-page-settings.create')}>{t('Configure home page')}</Link></Button>
                <div className="rounded bg-white p-4 dark:bg-slate-900">
                    {settings.map((setting) => (
                        <div key={setting.id} className="flex items-center justify-between border-b py-2">
                            <span>{setting.locale ?? 'default'} - {setting.title}</span>
                            <Button asChild variant="outline" size="sm"><Link href={route('admin.home-page-settings.edit', setting.id)}>{t('Edit')}</Link></Button>
                        </div>
                    ))}
                </div>
            </div>
        </AdminLayout>
    );
}
