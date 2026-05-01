import Modal from '@/Components/Modal';
import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

export default function Index({ providers }: any): JSX.Element {
  const { t } = useTranslations();
  const [loadingKey, setLoadingKey] = useState<string | null>(null);
  const [trace, setTrace] = useState<any | null>(null);
  const [open, setOpen] = useState(false);
  const [diagProvider, setDiagProvider] = useState<any | null>(null);
  const [actionMenuProviderId, setActionMenuProviderId] = useState<number | null>(null);
  const active = providers.filter((p: any) => p.is_active);
  const inactive = providers.filter((p: any) => !p.is_active);

  const runTest = async (provider: any, testType: 'minimal' | 'short-script' | 'grounded') => {
    const key = `${provider.id}-${testType}`;
    setLoadingKey(key);
    setDiagProvider(provider);
    try {
      const url = testType === 'minimal' ? route('admin.ai-providers.tests.minimal', provider.id) : testType === 'short-script' ? route('admin.ai-providers.tests.short-script', provider.id) : route('admin.ai-providers.tests.grounded', provider.id);
      const response = await axios.post(url);
      setTrace(response.data);
      setOpen(true);
    } catch (error: any) {
      setTrace(error?.response?.data ?? { ok: false, safe_error_message: error?.message ?? t('Unknown error') });
      setOpen(true);
    } finally {
      setLoadingKey(null);
    }
  };

  const copyJson = async () => {
    if (!trace) return;
    try { await navigator.clipboard.writeText(JSON.stringify(trace, null, 2)); alert(t('Trace copied')); }
    catch { alert(t('Unable to copy trace')); }
  };

  const table = (list: any[]) => <table className='w-full text-sm'><thead><tr className='text-left border-b'><th>{t('Name')}</th><th>{t('Tipo')}</th><th>{t('Driver')}</th><th>{t('Categoría')}</th><th>{t('Default model')}</th><th>{t('Status')}</th><th>{t('Environment key status')}</th><th className='text-right'>{t('Actions')}</th></tr></thead><tbody>{list.map((p:any)=><tr key={p.id} className='border-b align-top'><td className='py-2'>{p.name} {p.is_default && <Badge className='ml-1'>{t('Default')}</Badge>}<div className='text-xs text-slate-500'>{p.provider_category}</div></td><td>{p.provider_type}</td><td>{p.execution_driver_label}</td><td>{p.provider_category}</td><td>{p.default_model || '-'}</td><td>{p.is_active ? t('Active') : t('Inactive')}</td><td>{p.env_key_configured ? t('Configured') : t('Environment key is not configured')}</td><td><div className='flex flex-wrap gap-1 justify-end items-start overflow-visible'><Button size='sm' variant='outline' asChild><Link href={route('admin.ai-providers.show', p.id)}>{t('View')}</Link></Button><Button size='sm' variant='outline' asChild><Link href={route('admin.ai-providers.edit', p.id)}>{t('Edit')}</Link></Button><div className='relative z-30'><Button size='sm' onClick={()=>setActionMenuProviderId(actionMenuProviderId === p.id ? null : p.id)}>{t('Diagnostics')}</Button>{actionMenuProviderId === p.id && <div className='absolute right-0 z-[120] mt-1 w-56 rounded border bg-white p-1 shadow-lg space-y-1'><Button className='w-full justify-start' size='sm' variant='ghost' onClick={()=>{setActionMenuProviderId(null); router.post(route('admin.ai-providers.toggle-active', p.id));}}>{p.is_active ? t('Deactivate') : t('Activate')}</Button><Button className='w-full justify-start' size='sm' variant='ghost' onClick={()=>{setActionMenuProviderId(null); router.post(route('admin.ai-providers.make-default', p.id));}}>{t('Set as default')}</Button><Button className='w-full justify-start' size='sm' variant='ghost' disabled={!!loadingKey} onClick={()=>{setActionMenuProviderId(null); runTest(p,'minimal');}}>{t('Test minimal')}</Button><Button className='w-full justify-start' size='sm' variant='ghost' disabled={!!loadingKey} onClick={()=>{setActionMenuProviderId(null); runTest(p,'short-script');}}>{t('Test short script')}</Button>{p.supports_grounding && <Button className='w-full justify-start' size='sm' variant='ghost' disabled={!!loadingKey} onClick={()=>{setActionMenuProviderId(null); runTest(p,'grounded');}}>{t('Test grounded search')}</Button>}{p.rate_limited_until && <Button className='w-full justify-start' size='sm' variant='ghost' onClick={()=>{setActionMenuProviderId(null); router.post(route('admin.ai-providers.clear-rate-limit',p.id));}}>{t('Clear rate limit lock')}</Button>}</div>}</div></div></td></tr>)}</tbody></table>;

  return <AdminLayout><Head title={t('AI Providers')} /><AdminPageHeader helpKey='admin.aiproviders.index' title={t('AI Providers')} description={t('admin.aiProviders.index.description')} />
  <Card className='mb-4 overflow-visible'><CardContent className='pt-6 overflow-visible'><h3 className='font-semibold mb-2'>{t('Active providers')}</h3>{table(active)}</CardContent></Card>
  <Card className='overflow-visible'><CardContent className='pt-6 overflow-visible'><h3 className='font-semibold mb-2'>{t('Inactive providers')}</h3>{table(inactive)}</CardContent></Card>
  <Modal show={open} onClose={()=>setOpen(false)} maxWidth='6xl'><div className='w-[95vw] max-w-6xl max-h-[90vh] flex flex-col'><div className='sticky top-0 bg-white p-4 border-b flex items-start justify-between gap-3'><div><h3 className='text-lg font-semibold'>{t('Diagnostics')}</h3><p className='text-xs text-slate-600'>{diagProvider?.name} · {trace?.summary?.execution_driver_label ?? '-'}</p></div><Button size='sm' variant='outline' onClick={()=>setOpen(false)}>{t('Close')}</Button></div><div className='p-4 overflow-y-auto space-y-4 text-sm'>
    <section><h4 className='font-semibold mb-1'>{t('Summary')}</h4><pre className='text-xs bg-slate-100 p-2 rounded overflow-x-auto'>{JSON.stringify(trace?.summary ?? {}, null, 2)}</pre></section>
    <section><h4 className='font-semibold mb-1'>{t('Rate limiter')}</h4><pre className='text-xs bg-slate-100 p-2 rounded overflow-x-auto'>{JSON.stringify(trace?.rate_limiter ?? {}, null, 2)}</pre></section>
    <section><h4 className='font-semibold mb-1'>{t('Request')}</h4><pre className='text-xs bg-slate-100 p-2 rounded overflow-x-auto'>{JSON.stringify(trace?.request ?? {}, null, 2)}</pre></section>
    <section><h4 className='font-semibold mb-1'>{t('Response')}</h4><pre className='text-xs bg-slate-100 p-2 rounded overflow-x-auto'>{JSON.stringify(trace?.response ?? {}, null, 2)}</pre></section>
    <section><h4 className='font-semibold mb-1'>{t('AI request log')}</h4>{trace?.ai_request_log ? <pre className='text-xs bg-slate-100 p-2 rounded overflow-x-auto'>{JSON.stringify(trace.ai_request_log, null, 2)}</pre> : <p className='text-xs text-slate-600'>{t('No AI request log was created for this test.')}</p>}</section>
    <section><h4 className='font-semibold mb-1'>{t('Raw safe trace')}</h4>{trace && <pre className='text-xs bg-slate-100 p-2 rounded overflow-x-auto'>{JSON.stringify(trace, null, 2)}</pre>}</section>
  </div><div className='sticky bottom-0 bg-white border-t p-3 flex justify-end gap-2'><Button size='sm' variant='outline' onClick={copyJson}>{t('Copy JSON trace')}</Button><Button size='sm' onClick={()=>setOpen(false)}>{t('Close')}</Button></div></div></Modal>
  </AdminLayout>;
}
