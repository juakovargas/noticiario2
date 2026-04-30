import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

const CAPABILITIES = ['script_generation','news_grounding','google_search_grounding','citations','tts','speech_synthesis','video_render','media_processing','local_processing','mock'];

export default function AiProviderForm({ provider, providerTypes = [] }: any): JSX.Element {
  const isEdit = !!provider; const { t } = useTranslations();
  const form = useForm({ ...provider, name: provider?.name ?? '', slug: provider?.slug ?? '', provider_type: provider?.provider_type ?? 'groq', provider_category: provider?.provider_category ?? 'text', capabilities: provider?.capabilities ?? [], timeout_seconds: String(provider?.timeout_seconds ?? 60), api_key_env_name: provider?.api_key_env_name ?? '', default_model: provider?.default_model ?? '', base_url: provider?.base_url ?? '', is_active: provider?.is_active ?? true, is_default: provider?.is_default ?? false, is_testing: provider?.is_testing ?? false, is_local: provider?.is_local ?? false, supports_grounding: provider?.supports_grounding ?? false, supports_citations: provider?.supports_citations ?? false, supports_streaming: provider?.supports_streaming ?? false });
  const error=(k:string)=>form.errors[k] ? <p className='text-xs text-red-600'>{form.errors[k]}</p>:null;
  return <AdminLayout><Head title={isEdit?t('Edit AI Provider'):t('Create AI Provider')} /><AdminPageHeader helpKey='admin.aiproviders.form' title={isEdit?t('Edit AI Provider'):t('Create AI Provider')} description={t('Validation errors')} />
  <Card><CardContent className='pt-6'><form className='space-y-4' onSubmit={e=>{e.preventDefault(); isEdit?form.put(route('admin.ai-providers.update', provider.id)):form.post(route('admin.ai-providers.store'));}}>
  <div className='grid md:grid-cols-2 gap-4'><div><Label>{t('Name')} *</Label><Input value={form.data.name} onChange={e=>form.setData('name', e.target.value)} />{error('name')}</div><div><Label>{t('Slug')} *</Label><Input value={form.data.slug} onChange={e=>form.setData('slug', e.target.value)} />{error('slug')}</div></div>
  <div className='grid md:grid-cols-3 gap-4'><div><Label>{t('Provider type')} *</Label><select className='w-full border rounded-md px-3 py-2' value={form.data.provider_type} onChange={e=>form.setData('provider_type',e.target.value)}>{providerTypes.map((x:string)=><option key={x} value={x}>{x}</option>)}</select>{error('provider_type')}</div><div><Label>{t('Category')} *</Label><Input value={form.data.provider_category} onChange={e=>form.setData('provider_category', e.target.value)} />{error('provider_category')}</div><div><Label>{t('Default model')} *</Label><Input value={form.data.default_model} onChange={e=>form.setData('default_model', e.target.value)} />{error('default_model')}</div></div>
  <div className='grid md:grid-cols-2 gap-4'><div><Label>{t('Base URL')}</Label><Input value={form.data.base_url ?? ''} onChange={e=>form.setData('base_url', e.target.value)} />{error('base_url')}</div><div><Label>{t('API key environment name')}</Label><Input value={form.data.api_key_env_name ?? ''} onChange={e=>form.setData('api_key_env_name', e.target.value)} />{error('api_key_env_name')}</div></div>
  <div><Label>{t('Capabilities')}</Label><div className='grid grid-cols-2 md:grid-cols-4 gap-2 mt-2'>{CAPABILITIES.map((c)=><label key={c} className='text-sm flex gap-2 items-center'><input type='checkbox' checked={form.data.capabilities.includes(c)} onChange={e=>form.setData('capabilities', e.target.checked?[...form.data.capabilities,c]:form.data.capabilities.filter((x:string)=>x!==c))} />{c}</label>)}</div>{error('capabilities')}</div>
  <div className='grid md:grid-cols-4 gap-3'><label><input type='checkbox' checked={form.data.is_active} onChange={e=>form.setData('is_active', e.target.checked)} /> {t('Active')}</label><label><input type='checkbox' checked={form.data.is_default} onChange={e=>form.setData('is_default', e.target.checked)} /> {t('Default')}</label><label><input type='checkbox' checked={form.data.supports_grounding} onChange={e=>form.setData('supports_grounding', e.target.checked)} /> {t('Supports grounding')}</label><label><input type='checkbox' checked={form.data.supports_citations} onChange={e=>form.setData('supports_citations', e.target.checked)} /> {t('Supports citations')}</label></div>
  {form.errors.provider ? <p className='text-sm text-red-600'>{form.errors.provider}</p>:null}
  <div className='flex gap-2'><Button type='submit'>{t('Save')}</Button><Button asChild variant='secondary'><Link href={route('admin.ai-providers.index')}>{t('Cancel')}</Link></Button></div>
  </form></CardContent></Card></AdminLayout>;
}
