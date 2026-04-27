import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

interface Provider { name:string; slug:string; provider_type:string; base_url:string|null; api_key_env:string|null; default_model:string|null; supports_web_search:boolean; supports_json_mode:boolean; is_active:boolean; is_default:boolean; monthly_budget_cents:number|null; notes:string|null; }

export default function Show({ provider }: { provider: Provider }): JSX.Element {
    return <AdminLayout><Head title={provider.name} /><AdminPageHeader title={provider.name} description="AI provider details." /><Card><CardContent className="space-y-2 pt-6 text-sm"><p><strong>Slug:</strong> {provider.slug}</p><p><strong>Type:</strong> {provider.provider_type}</p><p><strong>Base URL:</strong> {provider.base_url || '-'}</p><p><strong>API key env:</strong> {provider.api_key_env || '-'}</p><p><strong>Default model:</strong> {provider.default_model || '-'}</p><p><strong>Supports web search:</strong> {provider.supports_web_search ? 'Yes' : 'No'}</p><p><strong>Supports JSON mode:</strong> {provider.supports_json_mode ? 'Yes' : 'No'}</p><p><strong>Monthly budget:</strong> {provider.monthly_budget_cents ?? '-'}</p><p><strong>Notes:</strong> {provider.notes || '-'}</p></CardContent></Card></AdminLayout>;
}
