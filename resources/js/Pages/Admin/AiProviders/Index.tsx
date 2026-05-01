import Modal from '@/Components/Modal';
import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

export default function Index({ providers }: any): JSX.Element {
  const { t } = useTranslations();
  const [loadingKey, setLoadingKey] = useState<string | null>(null);
  const [trace, setTrace] = useState<any | null>(null);
  const [open, setOpen] = useState(false);
  const active = providers.filter((p: any) => p.is_active);
  const inactive = providers.filter((p: any) => !p.is_active);

  const runTest = async (providerId: number, testType: 'minimal'|'short-script'|'grounded') => {
    const key = `${providerId}-${testType}`;
    setLoadingKey(key);
    setOpen(true);
    setTrace(null);
    try {
      const url = testType === 'minimal' ? route('admin.ai-providers.tests.minimal', providerId) : testType === 'short-script' ? route('admin.ai-providers.tests.short-script', providerId) : route('admin.ai-providers.tests.grounded', providerId);
      const response = await axios.post(url);
      setTrace(response.data);
    } catch (error: any) {
      setTrace(error?.response?.data ?? { ok: false, safe_error_message: error?.message ?? 'Unknown error' });
    } finally {
      setLoadingKey(null);
    }
  };

  const row = (p: any) => <tr key={p.id} className={p.is_default ? 'bg-cyan-50 border-b' : 'border-b'}><td className='py-2'>{p.name}{p.is_default && <Badge className='ml-2'>{t('Default provider')}</Badge>}<div className='text-xs'>{p.provider_category}</div></td><td>{p.default_model || '-'}</td><td>{p.env_key_configured ? t('API key configured') : t('API key missing')}</td><td className='text-right'><div className='flex gap-1 justify-end flex-wrap'>
    <Button size='sm' variant='outline' disabled={!!loadingKey} onClick={()=>runTest(p.id,'minimal')}>{loadingKey===`${p.id}-minimal`?t('admin.aiProviders.tests.modal.loading'):t('Test minimal')}</Button>
    <Button size='sm' variant='outline' disabled={!!loadingKey} onClick={()=>runTest(p.id,'short-script')}>{loadingKey===`${p.id}-short-script`?t('admin.aiProviders.tests.modal.loading'):t('Test short script')}</Button>
    {p.supports_grounding && <Button size='sm' variant='outline' disabled={!!loadingKey} onClick={()=>runTest(p.id,'grounded')}>{t('Test grounded search')}</Button>}
    <Button size='sm' variant='outline' onClick={()=>router.post(route('admin.ai-providers.clear-rate-limit',p.id))}>{t('Clear rate limit lock')}</Button>
  </div></td></tr>;

  const block = (title:string, data:any) => <div><h4 className='font-semibold'>{title}</h4><pre className='whitespace-pre-wrap text-xs bg-slate-100 p-3 rounded'>{JSON.stringify(data, null, 2)}</pre></div>;

  return <AdminLayout><Head title={t('AI Providers')} /><AdminPageHeader helpKey='admin.aiproviders.index' title={t('AI Providers')} description={t('Active providers appear first with diagnostics and direct tests.')} />
  <Card className='mb-4'><CardContent className='pt-6'><h3 className='font-semibold mb-2'>{t('Active providers')}</h3><table className='w-full text-sm'><tbody>{active.map(row)}</tbody></table></CardContent></Card>
  <Card><CardContent className='pt-6'><h3 className='font-semibold mb-2'>{t('Inactive providers')}</h3><table className='w-full text-sm'><tbody>{inactive.map(row)}</tbody></table></CardContent></Card>
  <Modal show={open} onClose={()=>setOpen(false)}><div className='p-6 space-y-3'><h3 className='text-lg font-semibold'>{t('admin.aiProviders.tests.modal.title')}</h3>{!trace ? <p>{t('admin.aiProviders.tests.modal.loading')}</p> : <>
    {block(t('admin.aiProviders.tests.modal.summary'), trace.summary)}
    {block(t('admin.aiProviders.tests.modal.rateLimiter'), trace.rate_limiter)}
    {block(t('admin.aiProviders.tests.modal.request'), trace.request)}
    {block(t('admin.aiProviders.tests.modal.response'), trace.response)}
    {block(t('admin.aiProviders.tests.modal.aiRequestLog'), trace.ai_request_log)}
    {block(t('admin.aiProviders.tests.modal.rawTrace'), trace.trace ?? trace)}
    </>}
    <div className='text-right'><Button variant='outline' onClick={()=>setOpen(false)}>{t('admin.aiProviders.tests.modal.close')}</Button></div></div></Modal>
  </AdminLayout>;
}
