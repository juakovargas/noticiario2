import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { useTranslations } from '@/i18n/useTranslations';
import { useDateFormatter } from '@/lib/useDateFormatter';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ logs, filters, providers, users, statuses, requestTypes, summary }: any): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const onFilter = (key: string, value: string): void => router.get(route('admin.ai-request-logs.index'), { ...filters, [key]: value }, { preserveState: true, replace: true });

    return <AdminLayout><Head title={t('AI Request Logs')} /><AdminPageHeader helpKey="admin.airequestlogs.index" title={t('AI Request Logs')} description={t('AI usage summary')} />
        <div className="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            {[
                ['Requests today', summary.requests_today], ['Successful requests', summary.successful_today], ['Failed requests', summary.failed_today], ['Blocked requests', summary.blocked_today],
                ['Daily estimated cost', summary.estimated_cost_today], ['Monthly estimated cost', summary.estimated_cost_month], ['Average duration', `${summary.average_duration_ms ?? 0} ms`],
            ].map(([label, value]) => <Card key={String(label)}><CardHeader className="pb-2"><CardTitle className="text-sm">{t(String(label))}</CardTitle></CardHeader><CardContent className="text-lg font-semibold">{value ?? '-'}</CardContent></Card>)}
        </div>

        <Card className="mb-4"><CardContent className="grid gap-2 pt-6 md:grid-cols-5">
            <Input placeholder={t('Search')} value={filters.search} onChange={(e)=>onFilter('search', e.target.value)} />
            <Input placeholder={t('Model')} value={filters.model} onChange={(e)=>onFilter('model', e.target.value)} />
            <select className="rounded border px-2" value={filters.provider} onChange={(e)=>onFilter('provider', e.target.value)}><option value="">{t('AI provider')}</option>{providers.map((p:any)=><option key={p.id} value={p.id}>{p.name}</option>)}</select>
            <select className="rounded border px-2" value={filters.status} onChange={(e)=>onFilter('status', e.target.value)}><option value="">{t('Status')}</option>{statuses.map((s:string)=><option key={s} value={s}>{s}</option>)}</select>
            <select className="rounded border px-2" value={filters.user} onChange={(e)=>onFilter('user', e.target.value)}><option value="">{t('User')}</option>{users.map((u:any)=><option key={u.id} value={u.id}>{u.name}</option>)}</select>
            <select className="rounded border px-2" value={filters.request_type} onChange={(e)=>onFilter('request_type', e.target.value)}><option value="">{t('Request type')}</option>{requestTypes.map((r:string)=><option key={r} value={r}>{r}</option>)}</select>
            <select className="rounded border px-2" value={filters.has_error} onChange={(e)=>onFilter('has_error', e.target.value)}><option value="">{t('Has error')}</option><option value="1">{t('Yes')}</option></select>
            <select className="rounded border px-2" value={filters.limit_blocked} onChange={(e)=>onFilter('limit_blocked', e.target.value)}><option value="">{t('Limit blocked')}</option><option value="1">{t('Yes')}</option><option value="0">{t('No')}</option></select>
            <Input type="date" value={filters.date_from} onChange={(e)=>onFilter('date_from', e.target.value)} />
            <Input type="date" value={filters.date_to} onChange={(e)=>onFilter('date_to', e.target.value)} />
        </CardContent></Card>

        <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[1200px] text-sm"><thead><tr className="border-b"><th className="text-left">{t('AI provider')}</th><th className="text-left">{t('Model')}</th><th className="text-left">{t('Status')}</th><th className="text-left">{t('User')}</th><th className="text-left">{t('Request type')}</th><th className="text-left">{t('Total tokens')}</th><th className="text-left">{t('Estimated cost')}</th><th className="text-left">{t('Duration')}</th><th className="text-left">{t('Started at')}</th><th className="text-left">{t('Completed at')}</th><th></th></tr></thead><tbody>{logs.data.map((log:any)=><tr key={log.id} className="border-b"><td>{log.provider?.name || '-'}</td><td>{log.model || '-'}</td><td className="space-x-1"><Badge variant={log.status === 'failed' ? 'danger' : 'outline'}>{log.status}</Badge>{log.limit_blocked ? <Badge variant="outline">{t('Limit reached')}</Badge> : null}</td><td>{log.user?.name || '-'}</td><td>{log.request_type}</td><td>{log.total_tokens ?? '-'}</td><td>{log.estimated_cost ?? '-'}</td><td>{log.duration_ms ? `${log.duration_ms} ms` : '-'}</td><td>{formatDateTime(log.started_at)}</td><td>{formatDateTime(log.completed_at)}</td><td className="py-2"><Button asChild size="sm" variant="outline"><Link href={route('admin.ai-request-logs.show', log.id)}>{t('View')}</Link></Button></td></tr>)}</tbody></table><Pagination links={logs.links} /></CardContent></Card></AdminLayout>;
}
