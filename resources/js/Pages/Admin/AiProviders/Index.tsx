import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ providers }: any): JSX.Element {
  const { t } = useTranslations();
  const flash: any = (usePage().props as any).flash ?? {};
  const trace = flash.provider_test_result;
  const active = providers.filter((p: any) => p.is_active);
  const inactive = providers.filter((p: any) => !p.is_active);

  const row = (p: any) => <tr key={p.id} className={p.is_default ? 'bg-cyan-50 border-b' : 'border-b'}><td className='py-2'>{p.name}{p.is_default && <Badge className='ml-2'>{t('Default provider')}</Badge>}<div className='text-xs'>{p.provider_category}</div></td><td>{p.default_model || '-'}</td><td>{p.env_key_configured ? t('API key configured') : t('API key missing')}</td><td className='text-right'><div className='flex gap-1 justify-end flex-wrap'>
    <Button size='sm' variant='outline' onClick={()=>router.post(route('admin.ai-providers.tests.minimal',p.id))}>{t('Test minimal')}</Button>
    <Button size='sm' variant='outline' onClick={()=>router.post(route('admin.ai-providers.tests.short-script',p.id))}>{t('Test short script')}</Button>
    {p.supports_grounding && <Button size='sm' variant='outline' onClick={()=>router.post(route('admin.ai-providers.tests.grounded',p.id))}>{t('Test grounded search')}</Button>}
    <Button size='sm' variant='outline' onClick={()=>router.post(route('admin.ai-providers.clear-rate-limit',p.id))}>{t('Clear rate limit lock')}</Button>
    <Button asChild size='sm' variant='outline'><Link href={route('admin.ai-providers.traces.latest', p.id)}>{t('Show traces')}</Link></Button>
  </div></td></tr>;

  return <AdminLayout><Head title={t('AI Providers')} /><AdminPageHeader helpKey='admin.aiproviders.index' title={t('AI Providers')} description={t('Active providers appear first with diagnostics and direct tests.')} />
  <Card className='mb-4'><CardContent className='pt-6'><h3 className='font-semibold mb-2'>{t('Active providers')}</h3><table className='w-full text-sm'><tbody>{active.map(row)}</tbody></table></CardContent></Card>
  <Card><CardContent className='pt-6'><h3 className='font-semibold mb-2'>{t('Inactive providers')}</h3><table className='w-full text-sm'><tbody>{inactive.map(row)}</tbody></table></CardContent></Card>
  {trace ? <Card className='mt-4 border-cyan-300'><CardContent className='pt-6 text-sm'><h3 className='font-semibold mb-2'>{t('Provider test traces')}</h3><pre className='whitespace-pre-wrap text-xs bg-slate-100 p-3 rounded'>{JSON.stringify(trace, null, 2)}</pre></CardContent></Card> : null}
  </AdminLayout>;
}
