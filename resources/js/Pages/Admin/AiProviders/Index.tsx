import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

const TABS = ['all', 'script', 'grounded', 'audio_video', 'local_testing'] as const;
const categoryLabel = (t: (k: string) => string, category?: string): string => t(category === 'grounded_text' ? 'News with search and sources' : category === 'text' ? 'Scripts / Text' : category === 'audio' ? 'Audio / Voice' : category === 'video' ? 'Video' : category === 'media' ? 'Media processing' : category === 'local' ? 'Local / Offline' : category === 'testing' ? 'Testing' : category === 'moderation' ? 'Review / Moderation' : category === 'publishing' ? 'Publishing' : 'Other');
const capabilityLabel = (t: (k: string) => string, x: string): string => t(x === 'script_generation' ? 'Script generation' : x === 'news_grounding' ? 'News grounding' : x === 'google_search_grounding' ? 'Google Search grounding' : x === 'citations' ? 'Citations' : x === 'tts' ? 'Text to speech' : x === 'speech_synthesis' ? 'Speech synthesis' : x === 'video_render' ? 'Video rendering' : x === 'media_processing' ? 'Media processing' : x === 'local_processing' ? 'Local processing' : x === 'fast_inference' ? 'Fast inference' : x);

export default function Index({ providers }: any): JSX.Element {
    const { t } = useTranslations();
    const tab = new URLSearchParams(window.location.search).get('tab') ?? 'all';
    const filtered = providers.filter((p: any) => {
        const caps = p.capabilities ?? [];
        if (tab === 'script') return caps.includes('script_generation') || caps.includes('news_grounding');
        if (tab === 'grounded') return p.provider_category === 'grounded_text' || caps.includes('news_grounding');
        if (tab === 'audio_video') return ['audio', 'video', 'media'].includes(p.provider_category);
        if (tab === 'local_testing') return p.is_local || p.is_testing || ['local', 'testing'].includes(p.provider_category);
        return true;
    });

    return <AdminLayout><Head title={t('AI Providers')} /><AdminPageHeader helpKey="admin.aiproviders.index" title={t('AI Providers')} description={t('Providers are grouped by what they do and keep limits/cost visibility.')} actionLabel={t('Create AI Provider')} actionHref={route('admin.ai-providers.create')} />
        <div className='mb-3 flex flex-wrap gap-2'>{TABS.map((x) => <Button key={x} variant={x === tab ? 'default' : 'outline'} size='sm' onClick={() => router.get(route('admin.ai-providers.index'), { tab: x }, { preserveState: true })}>{t(x === 'all' ? 'All providers' : x === 'script' ? 'Scripts / Text' : x === 'grounded' ? 'News with search and sources' : x === 'audio_video' ? 'Video / Media' : 'Local / Testing')}</Button>)}</div>
        <Card><CardContent className='overflow-x-auto pt-6'>
            <table className='w-full min-w-[1200px] text-sm'><thead><tr className='border-b'><th className='py-2 text-left'>{t('Name')}</th><th className='text-left'>{t('Provider category')}</th><th className='text-left'>{t('Model')}</th><th className='text-left'>{t('Request usage')}</th><th className='text-left'>{t('Token costs')}</th><th className='text-left'>{t('Config')}</th><th className='text-right'>{t('Actions')}</th></tr></thead><tbody>
                {filtered.map((p: any) => <tr key={p.id} className='border-b align-top'><td className='py-3'><p className='font-semibold'>{p.name} {p.is_default && <Badge>{t('Default provider')}</Badge>} {p.is_testing && <Badge variant='outline'>{t('Mock provider is only for testing')}</Badge>}</p><div className='mt-1 flex flex-wrap gap-1'>{(p.capabilities ?? []).map((c: string) => <Badge key={c} variant='outline'>{capabilityLabel(t, c)}</Badge>)}</div></td><td className='py-3'>{categoryLabel(t, p.provider_category)}</td><td className='py-3'>{p.default_model || '-'}<div className='text-xs text-slate-500'>max={p.max_tokens ?? '-'} · temp={p.temperature ?? '-'} · timeout={p.timeout_seconds ?? '-'}</div></td><td className='py-3'>{t('Daily request limit')}: {p.daily_request_limit ?? '-'}<br />{t('Monthly request limit')}: {p.monthly_request_limit ?? '-'}<br />{t('Used today')}: {p.usage_summary?.daily_requests ?? '-'}<br />{t('Remaining today')}: {p.usage_summary?.remaining_daily_requests ?? '-'}</td><td className='py-3'>{t('Input token cost')}: {p.cost_input_per_1k_tokens ?? '-'}<br />{t('Output token cost')}: {p.cost_output_per_1k_tokens ?? '-'}<br />{t('Estimated cost')}: {p.usage_summary?.daily_estimated_cost ?? '-'}</td><td className='py-3'>{p.env_key_configured ? <Badge>{t('Environment key configured')}</Badge> : <Badge variant='outline'>{t('Environment key missing')}</Badge>}<div className='mt-1 text-xs text-slate-500'>{p.api_key_env_name || '-'}</div></td><td className='py-3 text-right'><div className='flex justify-end gap-2'><Button size='sm' variant={p.is_active ? 'secondary' : 'outline'} onClick={() => router.post(route('admin.ai-providers.toggle-active', p.id))}>{p.is_active ? t('Active provider') : t('Inactive provider')}</Button><Button size='sm' variant={p.is_default ? 'default' : 'outline'} onClick={() => router.post(route('admin.ai-providers.make-default', p.id))}>{t('Make default')}</Button><Button asChild size='sm' variant='outline'><Link href={route('admin.ai-providers.show', p.id)}>{t('Show')}</Link></Button><Button asChild size='sm' variant='outline'><Link href={route('admin.ai-providers.edit', p.id)}>{t('Edit')}</Link></Button></div></td></tr>)}
            </tbody></table>
        </CardContent></Card>
    </AdminLayout>;
}
