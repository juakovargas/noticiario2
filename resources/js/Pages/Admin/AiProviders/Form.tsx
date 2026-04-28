import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Provider {
    id:number; name:string; slug:string; provider_type:string; base_url:string|null; api_key_env_name:string|null; default_model:string|null; organization:string|null;
    is_active:boolean; is_default:boolean; timeout_seconds:number; max_tokens:number|null; temperature:string|null;
    cost_input_per_1k_tokens:string|null; cost_output_per_1k_tokens:string|null; daily_request_limit:number|null; monthly_request_limit:number|null; daily_cost_limit:string|null; monthly_cost_limit:string|null;
}
interface Props { provider?:Provider; providerTypes:string[]; }

export default function AiProviderForm({ provider, providerTypes = [] }: Props): JSX.Element {
    const isEdit = !!provider;
    const { t } = useTranslations();
    const form = useForm({
        name: provider?.name ?? '', slug: provider?.slug ?? '', provider_type: provider?.provider_type ?? 'openrouter', base_url: provider?.base_url ?? '', api_key_env_name: provider?.api_key_env_name ?? '',
        default_model: provider?.default_model ?? '', organization: provider?.organization ?? '', is_active: provider?.is_active ?? true, is_default: provider?.is_default ?? false,
        timeout_seconds: String(provider?.timeout_seconds ?? 60), max_tokens: provider?.max_tokens ? String(provider.max_tokens) : '', temperature: provider?.temperature ?? '',
        cost_input_per_1k_tokens: provider?.cost_input_per_1k_tokens ?? '', cost_output_per_1k_tokens: provider?.cost_output_per_1k_tokens ?? '', daily_request_limit: provider?.daily_request_limit ? String(provider.daily_request_limit) : '', monthly_request_limit: provider?.monthly_request_limit ? String(provider.monthly_request_limit) : '',
        daily_cost_limit: provider?.daily_cost_limit ?? '', monthly_cost_limit: provider?.monthly_cost_limit ?? '',
    });

    const submit = (e: FormEvent<HTMLFormElement>): void => { e.preventDefault(); if (isEdit) form.put(route('admin.ai-providers.update', provider!.id)); else form.post(route('admin.ai-providers.store')); };

    return <AdminLayout><Head title={isEdit ? t('Edit AI Provider') : t('Create AI Provider')} /><AdminPageHeader helpKey="admin.aiproviders.form" title={isEdit ? t('Edit AI Provider') : t('Create AI Provider')} description={t('Technical provider configuration.')} />
        <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4">
            <p className="text-xs text-slate-600 dark:text-slate-300">{t('Costs are estimates')} · {t('API keys are stored in environment variables')}</p>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Name')}</Label><Input value={form.data.name} onChange={(e)=>form.setData('name', e.target.value)} /></div><div><Label>{t('Slug')}</Label><Input value={form.data.slug} onChange={(e)=>form.setData('slug', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Provider type')}</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.provider_type} onChange={(e)=>form.setData('provider_type', e.target.value)}>{providerTypes.map((item)=><option key={item} value={item}>{item}</option>)}</select></div><div><Label>{t('Base URL')}</Label><Input value={form.data.base_url} onChange={(e)=>form.setData('base_url', e.target.value)} /></div><div><Label>{t('API key environment name')}</Label><Input value={form.data.api_key_env_name} onChange={(e)=>form.setData('api_key_env_name', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Default model')}</Label><Input value={form.data.default_model} onChange={(e)=>form.setData('default_model', e.target.value)} /></div><div><Label>{t('Organization')}</Label><Input value={form.data.organization} onChange={(e)=>form.setData('organization', e.target.value)} /></div><div><Label>{t('Timeout seconds')}</Label><Input type="number" min={5} max={300} value={form.data.timeout_seconds} onChange={(e)=>form.setData('timeout_seconds', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Max tokens')}</Label><Input type="number" value={form.data.max_tokens} onChange={(e)=>form.setData('max_tokens', e.target.value)} /></div><div><Label>{t('Temperature')}</Label><Input type="number" step="0.01" min={0} max={2} value={form.data.temperature} onChange={(e)=>form.setData('temperature', e.target.value)} /></div><div><Label>{t('Daily request limit')}</Label><Input type="number" value={form.data.daily_request_limit} onChange={(e)=>form.setData('daily_request_limit', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Input token cost')}</Label><Input type="number" step="0.000001" value={form.data.cost_input_per_1k_tokens} onChange={(e)=>form.setData('cost_input_per_1k_tokens', e.target.value)} /></div><div><Label>{t('Output token cost')}</Label><Input type="number" step="0.000001" value={form.data.cost_output_per_1k_tokens} onChange={(e)=>form.setData('cost_output_per_1k_tokens', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>{t('Monthly request limit')}</Label><Input type="number" value={form.data.monthly_request_limit} onChange={(e)=>form.setData('monthly_request_limit', e.target.value)} /></div><div><Label>{t('Daily cost limit')}</Label><Input type="number" step="0.000001" value={form.data.daily_cost_limit} onChange={(e)=>form.setData('daily_cost_limit', e.target.value)} /></div><div><Label>{t('Monthly cost limit')}</Label><Input type="number" step="0.000001" value={form.data.monthly_cost_limit} onChange={(e)=>form.setData('monthly_cost_limit', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-2"><label className="flex items-center gap-2"><input type="checkbox" checked={form.data.is_active} onChange={(e)=>form.setData('is_active', e.target.checked)} />{t('Active')}</label><label className="flex items-center gap-2"><input type="checkbox" checked={form.data.is_default} onChange={(e)=>form.setData('is_default', e.target.checked)} />{t('Default')}</label></div>
            <div className="flex gap-2"><Button type="submit" disabled={form.processing}>{t('Save')}</Button><Button asChild variant="secondary"><Link href={route('admin.ai-providers.index')}>{t('Cancel')}</Link></Button></div>
        </form></CardContent></Card></AdminLayout>;
}
