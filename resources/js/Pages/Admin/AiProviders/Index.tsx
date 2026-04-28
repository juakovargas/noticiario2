import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

interface Provider { id:number; name:string; provider_type:string; default_model:string|null; is_active:boolean; is_default:boolean; env_key_configured:boolean; }
interface Props { providers:{ data:Provider[]; links:Array<{url:string|null; label:string; active:boolean}> } }

export default function Index({ providers }: Props): JSX.Element {
    const { t } = useTranslations();
    const destroy = (id:number): void => { if (window.confirm(t('Delete this provider?'))) router.delete(route('admin.ai-providers.destroy', id)); };

    return <AdminLayout><Head title={t('AI Providers')} /><AdminPageHeader title={t('AI Providers')} description={t('Manage provider connections.')} actionLabel={t('Create AI Provider')} actionHref={route('admin.ai-providers.create')} />
        <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[840px] text-sm"><thead><tr className="border-b"><th className="px-2 pb-3 text-left">{t('Name')}</th><th className="px-2 pb-3 text-left">{t('Provider type')}</th><th className="px-2 pb-3 text-left">{t('Default model')}</th><th className="px-2 pb-3 text-left">{t('Status')}</th><th className="px-2 pb-3 text-left">{t('Actions')}</th></tr></thead><tbody>{providers.data.length ? providers.data.map((provider)=><tr key={provider.id} className="border-b"><td className="px-2 py-3">{provider.name}</td><td className="px-2 py-3">{provider.provider_type}</td><td className="px-2 py-3">{provider.default_model || '-'}</td><td className="px-2 py-3 space-x-1">{provider.is_active ? <Badge>{t('Active')}</Badge> : <Badge variant="danger">{t('Provider is inactive')}</Badge>}{provider.is_default ? <Badge variant="outline">{t('Default')}</Badge> : null}{!provider.env_key_configured ? <Badge variant="outline">{t('Missing environment key')}</Badge> : null}</td><td className="px-2 py-3"><div className="flex gap-2"><Button asChild size="sm" variant="outline"><Link href={route('admin.ai-providers.show', provider.id)}>{t('View')}</Link></Button><Button asChild size="sm" variant="outline"><Link href={route('admin.ai-providers.edit', provider.id)}>{t('Edit')}</Link></Button><Button size="sm" variant="destructive" onClick={()=>destroy(provider.id)}>{t('Delete')}</Button></div></td></tr>) : <tr><td colSpan={5} className="px-2 py-6 text-center">{t('No AI providers found.')}</td></tr>}</tbody></table><Pagination links={providers.links} /></CardContent></Card></AdminLayout>;
}
