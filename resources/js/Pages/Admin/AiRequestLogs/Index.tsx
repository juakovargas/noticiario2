import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ logs, filters, providers, users, statuses, requestTypes }: any): JSX.Element {
    const { t } = useTranslations();
    const onFilter = (key: string, value: string): void => router.get(route('admin.ai-request-logs.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });

    return <AdminLayout><Head title={t('AI Request Logs')} /><AdminPageHeader title={t('AI Request Logs')} description={t('AI request log')} />
        <Card className="mb-4"><CardContent className="grid gap-2 pt-6 md:grid-cols-4"><Input placeholder={t('Search')} value={filters.search} onChange={(e)=>onFilter('search', e.target.value)} /><select className="rounded border px-2" value={filters.provider} onChange={(e)=>onFilter('provider', e.target.value)}><option value="">{t('AI provider')}</option>{providers.map((p:any)=><option key={p.id} value={p.id}>{p.name}</option>)}</select><select className="rounded border px-2" value={filters.status} onChange={(e)=>onFilter('status', e.target.value)}><option value="">{t('Status')}</option>{statuses.map((s:string)=><option key={s} value={s}>{s}</option>)}</select><select className="rounded border px-2" value={filters.user} onChange={(e)=>onFilter('user', e.target.value)}><option value="">{t('User')}</option>{users.map((u:any)=><option key={u.id} value={u.id}>{u.name}</option>)}</select><select className="rounded border px-2" value={filters.request_type} onChange={(e)=>onFilter('request_type', e.target.value)}><option value="">{t('Request type')}</option>{requestTypes.map((r:string)=><option key={r} value={r}>{r}</option>)}</select></CardContent></Card>
        <Card><CardContent className="pt-6"><table className="w-full text-sm"><thead><tr className="border-b"><th className="text-left">{t('AI provider')}</th><th className="text-left">{t('Model')}</th><th className="text-left">{t('Status')}</th><th className="text-left">{t('Estimated cost')}</th><th className="text-left">{t('Duration')}</th><th></th></tr></thead><tbody>{logs.data.map((log:any)=><tr key={log.id} className="border-b"><td>{log.provider?.name || '-'}</td><td>{log.model || '-'}</td><td>{log.status}</td><td>{log.estimated_cost || '-'}</td><td>{log.duration_ms || '-'} ms</td><td className="py-2"><Button asChild size="sm" variant="outline"><Link href={route('admin.ai-request-logs.show', log.id)}>{t('View')}</Link></Button></td></tr>)}</tbody></table><Pagination links={logs.links} /></CardContent></Card></AdminLayout>;
}
