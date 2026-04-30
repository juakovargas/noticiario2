import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

const tabs = ['all','text','grounded_text','audio','video','media','local','testing'];
export default function Index({ providers }: any): JSX.Element {
 const { t } = useTranslations();
 const tab = new URLSearchParams(window.location.search).get('tab') ?? 'all';
 const filtered = providers.filter((p:any)=> tab==='all' ? true : tab==='video'?['video','media'].includes(p.provider_category): p.provider_category===tab || (tab==='local'&&p.is_local));
 return <AdminLayout><Head title={t('AI Providers')} /><AdminPageHeader helpKey='admin.aiproviders.index' title={t('AI Providers')} description={t('Providers are grouped by what they do.')} actionLabel={t('Create AI Provider')} actionHref={route('admin.ai-providers.create')} />
 <div className='flex gap-2 flex-wrap mb-3'>{tabs.map(x=><Button key={x} variant={x===tab?'default':'outline'} size='sm' onClick={()=>router.get(route('admin.ai-providers.index'),{tab:x},{preserveState:true})}>{t(x==='all'?'All providers':x==='text'?'Scripts':x==='grounded_text'?'Grounded news':x==='audio'?'Audio':x==='video'?'Video/media':x==='media'?'Video/media':x==='local'?'Local/testing':'Local/testing')}</Button>)}</div>
 <Card><CardContent className='pt-6 space-y-3'>{filtered.map((p:any)=><div key={p.id} className='border rounded p-3 flex items-center justify-between gap-3'><div><p className='font-semibold'>{p.name} <Badge variant='outline'>{p.provider_category || 'other'}</Badge> {p.is_testing && <Badge variant='danger'>{t('Testing only')}</Badge>}</p><p className='text-xs text-slate-500'>{p.default_model || '-'} · {p.api_key_env_name || '-'} · {p.capabilities?.join(', ')}</p></div><div className='flex gap-2'><Button size='sm' variant={p.is_active?'secondary':'outline'} onClick={()=>router.post(route('admin.ai-providers.toggle-active', p.id))}>{p.is_active?t('Active provider'):t('Inactive provider')}</Button><Button size='sm' variant={p.is_default?'default':'outline'} onClick={()=>router.post(route('admin.ai-providers.make-default', p.id))}>{t('Make default')}</Button><Button asChild size='sm' variant='outline'><Link href={route('admin.ai-providers.edit', p.id)}>{t('Edit')}</Link></Button></div></div>)}</CardContent></Card></AdminLayout>;
}
