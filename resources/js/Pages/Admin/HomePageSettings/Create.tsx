import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';
import Form, { HomePageSettingFormData } from './Form';

export default function Create(): JSX.Element {
    const { t } = useTranslations();
    const form = useForm<HomePageSettingFormData>({
        locale: '',
        title: '',
        subtitle: '',
        description: '',
        hero_badge: '',
        primary_button_label: '',
        primary_button_url: '',
        secondary_button_label: '',
        secondary_button_url: '',
        show_latest_noticiarios: true,
        latest_noticiarios_limit: 6,
        show_world_map_preview: true,
        show_platforms_section: true,
        platforms: [],
        seo_title: '',
        seo_description: '',
        is_active: true,
    });

    return <AdminLayout><Head title={t('Home Page Settings')} /><form onSubmit={(e) => { e.preventDefault(); form.post(route('admin.home-page-settings.store')); }} className="space-y-4"><Form data={form.data} setData={form.setData} /><Button type="submit">{t('Save')}</Button></form></AdminLayout>;
}
