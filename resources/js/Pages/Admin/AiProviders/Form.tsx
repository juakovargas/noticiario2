import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Provider { id:number; name:string; slug:string; provider_type:string; base_url:string|null; api_key_env:string|null; default_model:string|null; supports_web_search:boolean; supports_json_mode:boolean; is_active:boolean; is_default:boolean; monthly_budget_cents:number|null; cost_per_1k_input_tokens_cents:number|null; cost_per_1k_output_tokens_cents:number|null; notes:string|null; }
interface Props { provider?:Provider; providerTypes:string[]; }

export default function AiProviderForm({ provider, providerTypes = [] }: Props): JSX.Element {
    const isEdit = !!provider;
    const form = useForm({
        name: provider?.name ?? '', slug: provider?.slug ?? '', provider_type: provider?.provider_type ?? 'mock', base_url: provider?.base_url ?? '', api_key_env: provider?.api_key_env ?? '', default_model: provider?.default_model ?? '',
        supports_web_search: provider?.supports_web_search ?? false, supports_json_mode: provider?.supports_json_mode ?? false, is_active: provider?.is_active ?? true, is_default: provider?.is_default ?? false,
        monthly_budget_cents: provider?.monthly_budget_cents?.toString() ?? '', cost_per_1k_input_tokens_cents: provider?.cost_per_1k_input_tokens_cents?.toString() ?? '', cost_per_1k_output_tokens_cents: provider?.cost_per_1k_output_tokens_cents?.toString() ?? '', notes: provider?.notes ?? '',
    });

    const submit = (e: FormEvent<HTMLFormElement>): void => { e.preventDefault(); if (isEdit) form.put(route('admin.ai-providers.update', provider!.id)); else form.post(route('admin.ai-providers.store')); };

    return <AdminLayout><Head title={isEdit ? 'Edit AI Provider' : 'Create AI Provider'} /><AdminPageHeader title={isEdit ? 'Edit AI Provider' : 'Create AI Provider'} description="Technical provider configuration." />
        <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2"><div><Label>Name</Label><Input value={form.data.name} onChange={(e)=>form.setData('name', e.target.value)} /></div><div><Label>Slug</Label><Input value={form.data.slug} onChange={(e)=>form.setData('slug', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>Provider type</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.provider_type} onChange={(e)=>form.setData('provider_type', e.target.value)}>{providerTypes.map((item)=><option key={item} value={item}>{item}</option>)}</select></div><div><Label>Base URL</Label><Input value={form.data.base_url} onChange={(e)=>form.setData('base_url', e.target.value)} /></div><div><Label>API key env</Label><Input value={form.data.api_key_env} onChange={(e)=>form.setData('api_key_env', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>Default model</Label><Input value={form.data.default_model} onChange={(e)=>form.setData('default_model', e.target.value)} /></div><div><Label>Monthly budget</Label><Input type="number" value={form.data.monthly_budget_cents} onChange={(e)=>form.setData('monthly_budget_cents', e.target.value)} /></div><div><Label>Cost per 1K input tokens</Label><Input type="number" value={form.data.cost_per_1k_input_tokens_cents} onChange={(e)=>form.setData('cost_per_1k_input_tokens_cents', e.target.value)} /></div></div>
            <div><Label>Cost per 1K output tokens</Label><Input type="number" value={form.data.cost_per_1k_output_tokens_cents} onChange={(e)=>form.setData('cost_per_1k_output_tokens_cents', e.target.value)} /></div>
            <div><Label>Notes</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={4} value={form.data.notes} onChange={(e)=>form.setData('notes', e.target.value)} /></div>
            <div className="grid gap-4 md:grid-cols-2"><label className="flex items-center gap-2"><input type="checkbox" checked={form.data.supports_web_search} onChange={(e)=>form.setData('supports_web_search', e.target.checked)} />Supports web search</label><label className="flex items-center gap-2"><input type="checkbox" checked={form.data.supports_json_mode} onChange={(e)=>form.setData('supports_json_mode', e.target.checked)} />Supports JSON mode</label><label className="flex items-center gap-2"><input type="checkbox" checked={form.data.is_active} onChange={(e)=>form.setData('is_active', e.target.checked)} />Active</label><label className="flex items-center gap-2"><input type="checkbox" checked={form.data.is_default} onChange={(e)=>form.setData('is_default', e.target.checked)} />Default</label></div>
            <div className="flex gap-2"><Button type="submit" disabled={form.processing}>Save</Button><Button asChild variant="secondary"><Link href={route('admin.ai-providers.index')}>Cancel</Link></Button></div>
        </form></CardContent></Card></AdminLayout>;
}
