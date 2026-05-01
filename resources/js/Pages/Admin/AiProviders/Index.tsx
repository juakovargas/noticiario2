import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ providers }: any): JSX.Element {
  const { t } = useTranslations();
  const active = providers.filter((p: any) => p.is_active);
  const inactive = providers.filter((p: any) => !p.is_active);
  const row = (p: any) => <tr key={p.id} className={p.is_default ? 'bg-cyan-50 border-b' : 'border-b'}><td className='py-2'>{p.name}<div className='text-xs'>{p.provider_category}</div></td><td>{p.default_model || '-'}</td><td>{p.env_key_configured ? t('API key configured') : t('API key missing')}</td><td>{p.last_request?.status || '-'}</td><td className='text-right'><div className='flex gap-1 justify-end flex-wrap'>
    {p.is_default && <Badge>{t('Default provider')}</Badge>}
    <Button size='sm' variant='outline' onClick={()=>router.post(route('admin.ai-providers.test',p.id),{test_type:'minimal'})}>{t('Test minimal')}</Button>
    <Button size='sm' variant='outline' onClick={()=>router.post(route('admin.ai-providers.test',p.id),{test_type:'short_script'})}>{t('Test short script')}</Button>
    {p.supports_grounding && <Button size='sm' variant='outline' onClick={()=>router.post(route('admin.ai-providers.test',p.id),{test_type:'grounded'})}>{t('Test grounded search')}</Button>}
    <Button size='sm' variant='outline' onClick={()=>router.post(route('admin.ai-providers.clear-rate-limit-lock',p.id))}>{t('Clear rate limit lock')}</Button>
    <Button asChild size='sm' variant='outline'><Link href={route('admin.ai-providers.show', p.id)}>{t('Show traces')}</Link></Button>
  </div></td></tr>;

  return <AdminLayout><Head title={t('AI Providers')} /><AdminPageHeader helpKey='admin.aiproviders.index' title={t('AI Providers')} description={t('Active providers appear first with diagnostics and direct tests.')} />
  <Card className='mb-4'><CardContent className='pt-6'><h3 className='font-semibold mb-2'>{t('Active providers')}</h3><table className='w-full text-sm'><tbody>{active.map(row)}</tbody></table></CardContent></Card>
  <Card><CardContent className='pt-6'><h3 className='font-semibold mb-2'>{t('Inactive providers')}</h3><table className='w-full text-sm'><tbody>{inactive.map(row)}</tbody></table></CardContent></Card>
  </AdminLayout>;
}
