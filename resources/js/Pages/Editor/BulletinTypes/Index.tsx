import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router } from '@inertiajs/react';
import { Bot, CalendarClock, Edit3, Eye, Filter, PlayCircle } from 'lucide-react';

export default function Index({ bulletinTypes, filters = {}, locations = [], providerOptions = [] }: any): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();

    const updateFilter = (key: string, value: string): void => {
        router.get(route('editor.bulletin-types.index'), { ...filters, [key]: value }, { preserveScroll: true, preserveState: true, replace: true });
    };

    return (
        <EditorLayout>
            <Head title={t('bulletinTypes.index.title')} />
            <AdminPageHeader
                helpKey="editor.bulletintypes.index"
                title={t('bulletinTypes.index.title')}
                description={t('bulletinTypes.index.description')}
            />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-wrap items-center gap-2">
                    <Button asChild>
                        <Link href={route('editor.bulletin-types.create')}>{t('bulletinTypes.action.create')}</Link>
                    </Button>
                    <Button asChild variant="outline">
                        <Link href={route('editor.bulletin-prompt-runs.index')}>{t('bulletinTypes.action.promptRuns')}</Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2 text-sm">
                    <Filter className="h-4 w-4 text-slate-500" />
                    <select
                        className="h-9 rounded-lg border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-900"
                        value={filters.location_id ?? ''}
                        onChange={(event) => updateFilter('location_id', event.target.value)}
                    >
                        <option value="">{t('bulletinTypes.filter.allLocations')}</option>
                        {locations.map((location: any) => (
                            <option key={location.id} value={location.id}>{location.name}</option>
                        ))}
                    </select>
                    <select
                        className="h-9 rounded-lg border border-slate-300 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-900"
                        value={filters.provider ?? ''}
                        onChange={(event) => updateFilter('provider', event.target.value)}
                    >
                        <option value="">{t('bulletinTypes.filter.allProviders')}</option>
                        <option value="missing">{t('bulletinTypes.provider.missing')}</option>
                        {providerOptions.map((provider: any) => (
                            <option key={provider.id} value={provider.id}>{provider.name}</option>
                        ))}
                    </select>
                </div>
            </div>

            <Card className="rounded-lg">
                <CardContent className="overflow-x-auto pt-6">
                    <table className="w-full min-w-[1120px] text-sm">
                        <thead>
                            <tr className="border-b border-slate-200 text-left text-xs uppercase text-slate-500 dark:border-slate-800">
                                <th className="px-3 pb-3">{t('bulletinTypes.table.name')}</th>
                                <th className="px-3 pb-3">{t('bulletinTypes.table.location')}</th>
                                <th className="px-3 pb-3">{t('bulletinTypes.table.category')}</th>
                                <th className="px-3 pb-3">{t('bulletinTypes.table.schedule')}</th>
                                <th className="px-3 pb-3">{t('bulletinTypes.table.status')}</th>
                                <th className="px-3 pb-3">{t('bulletinTypes.table.provider')}</th>
                                <th className="px-3 pb-3">{t('bulletinTypes.table.grounded')}</th>
                                <th className="px-3 pb-3 text-right">{t('bulletinTypes.table.actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {bulletinTypes.data.length ? bulletinTypes.data.map((item: any) => {
                                const provider = item.preferred_ai_provider;
                                const schedule = item.primary_schedule;

                                return (
                                    <tr className="border-b border-slate-100 align-top dark:border-slate-800" key={item.id}>
                                        <td className="px-3 py-4">
                                            <Link className="font-semibold text-slate-900 hover:underline dark:text-slate-100" href={route('editor.bulletin-types.show', item.id)}>
                                                {item.name}
                                            </Link>
                                            <p className="mt-1 text-xs text-slate-500">{item.language?.name ?? t('bulletinTypes.empty.notAssigned')}</p>
                                        </td>
                                        <td className="px-3 py-4">{item.location?.name ?? t('bulletinTypes.empty.notAssigned')}</td>
                                        <td className="px-3 py-4">{item.news_category?.name ?? t('bulletinTypes.empty.notAssigned')}</td>
                                        <td className="px-3 py-4">
                                            {schedule ? (
                                                <div className="space-y-1">
                                                    <p className="font-medium">{schedule.run_frequency ?? t('bulletinTypes.empty.notAssigned')} · {(schedule.run_time ?? schedule.scheduled_time ?? '').slice(0, 5) || t('bulletinTypes.empty.notAssigned')}</p>
                                                    <p className="text-xs text-slate-500">{formatDateTime(schedule.next_run_at)}</p>
                                                </div>
                                            ) : (
                                                <Badge variant="outline">{t('bulletinTypes.schedule.missing')}</Badge>
                                            )}
                                        </td>
                                        <td className="px-3 py-4">
                                            <Badge variant={item.is_active ? 'success' : 'outline'}>{item.is_active ? 'ON' : 'OFF'}</Badge>
                                        </td>
                                        <td className="px-3 py-4">
                                            {provider ? (
                                                <div className="space-y-1">
                                                    <p className="font-medium">{provider.name}</p>
                                                    <p className="text-xs text-slate-500">{provider.default_model ?? t('bulletinTypes.empty.noModel')}</p>
                                                </div>
                                            ) : (
                                                <Badge variant="danger">{t('bulletinTypes.provider.missing')}</Badge>
                                            )}
                                        </td>
                                        <td className="px-3 py-4">
                                            <Badge variant={provider?.supports_grounding ? 'success' : 'outline'}>
                                                {provider?.supports_grounding ? t('common.yes') : t('common.no')}
                                            </Badge>
                                        </td>
                                        <td className="px-3 py-4">
                                            <div className="flex justify-end gap-2">
                                                <Button size="icon" title={t('bulletinTypes.action.runNow')} onClick={() => router.post(route('editor.bulletin-types.prompt-runs.store', item.id))}>
                                                    <PlayCircle className="h-4 w-4" />
                                                </Button>
                                                <Button asChild size="icon" variant="outline" title={t('bulletinTypes.action.open')}>
                                                    <Link href={route('editor.bulletin-types.show', item.id)}><Eye className="h-4 w-4" /></Link>
                                                </Button>
                                                <Button asChild size="icon" variant="outline" title={t('bulletinTypes.action.promptRuns')}>
                                                    <Link href={route('editor.bulletin-prompt-runs.index', { bulletin_type_id: item.id })}><CalendarClock className="h-4 w-4" /></Link>
                                                </Button>
                                                <Button asChild size="icon" variant="outline" title={t('bulletinTypes.action.edit')}>
                                                    <Link href={route('editor.bulletin-types.edit', item.id)}><Edit3 className="h-4 w-4" /></Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            }) : (
                                <tr>
                                    <td colSpan={8} className="px-3 py-10 text-center text-slate-500">
                                        <Bot className="mx-auto mb-2 h-5 w-5" />
                                        {t('bulletinTypes.empty.none')}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                    <Pagination links={bulletinTypes.links} />
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
