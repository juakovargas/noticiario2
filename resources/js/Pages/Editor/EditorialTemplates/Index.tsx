import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';

interface TemplateItem {
    id: number;
    name: string;
    slug: string;
    language: { code: string; name: string; native_name: string | null; flag_emoji: string | null } | null;
    location: { name: string } | null;
    edition_type: string | null;
    target_duration_seconds: number | null;
    is_active: boolean;
    sort_order: number;
}

interface Props {
    templates: { data: TemplateItem[]; links: Array<{ url: string | null; label: string; active: boolean }> };
}

export default function Index({ templates }: Props): JSX.Element {
    const { t } = useTranslations();

    const destroy = (id: number): void => {
        if (window.confirm('Delete this editorial template?')) {
            router.delete(route('editor.editorial-templates.destroy', id));
        }
    };

    return (
        <EditorLayout>
            <Head title={t('Editorial Templates')} />
            <AdminPageHeader title={t('Editorial Templates')} description="Manage reusable script structures." actionLabel={t('Create Editorial Template')} actionHref={route('editor.editorial-templates.create')} />
            <Card>
                <CardContent className="overflow-x-auto pt-6">
                    <table className="w-full min-w-[980px] text-sm">
                        <thead><tr className="border-b border-slate-200 text-slate-500"><th className="px-2 pb-3">{t('Name')}</th><th className="px-2 pb-3">{t('Language')}</th><th className="px-2 pb-3">{t('Location')}</th><th className="px-2 pb-3">{t('Edition type')}</th><th className="px-2 pb-3">{t('Target duration')}</th><th className="px-2 pb-3">{t('Active')}</th><th className="px-2 pb-3">{t('Sort order')}</th><th className="px-2 pb-3 text-right">{t('Actions')}</th></tr></thead>
                        <tbody>
                            {templates.data.length ? templates.data.map((template) => (
                                <tr key={template.id} className="border-b border-slate-100">
                                    <td className="px-2 py-3 font-medium">{template.name}</td>
                                    <td className="px-2 py-3">{template.language ? `${template.language.flag_emoji ?? ''} ${template.language.native_name || template.language.name} (${template.language.code})` : '-'}</td>
                                    <td className="px-2 py-3">{template.location?.name || '-'}</td>
                                    <td className="px-2 py-3">{template.edition_type || '-'}</td>
                                    <td className="px-2 py-3">{template.target_duration_seconds || '-'}</td>
                                    <td className="px-2 py-3">{template.is_active ? t('Yes') : t('No')}</td>
                                    <td className="px-2 py-3">{template.sort_order}</td>
                                    <td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-templates.show', template.id)}>{t('View')}</Link></Button><Button asChild size="sm" variant="secondary"><Link href={route('editor.editorial-templates.edit', template.id)}>{t('Edit')}</Link></Button><Button size="sm" variant="destructive" onClick={() => destroy(template.id)}>{t('Delete')}</Button></div></td>
                                </tr>
                            )) : <tr><td colSpan={8} className="px-2 py-6 text-center text-slate-500">No templates yet.</td></tr>}
                        </tbody>
                    </table>
                    <Pagination links={templates.links} />
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
