import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';
import Form, { HomePageSettingFormData } from './Form';

export default function Edit({ setting }: { setting: HomePageSettingFormData & { id: number } }): JSX.Element {
    const { t } = useTranslations();
    const form = useForm<HomePageSettingFormData>({
        ...setting,
        platforms: setting.platforms ?? [],
    });

    return <AdminLayout><Head title={t('Home Page Settings')} /><form onSubmit={(e) => { e.preventDefault(); form.put(route('admin.home-page-settings.update', setting.id)); }} className="space-y-4"><Form data={form.data} setData={form.setData} /><Button type="submit">{t('Save')}</Button></form></AdminLayout>;
}
