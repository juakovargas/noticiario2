import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

interface Provider { id:number; name:string; slug:string; provider_type:string; default_model:string|null; is_active:boolean; is_default:boolean; }
interface Props { providers:{ data:Provider[]; links:Array<{url:string|null; label:string; active:boolean}> } }

export default function Index({ providers }: Props): JSX.Element {
    const destroy = (id:number): void => { if (window.confirm('Delete this provider?')) router.delete(route('admin.ai-providers.destroy', id)); };

    return <AdminLayout><Head title="AI Providers" /><AdminPageHeader title="AI Providers" description="Manage provider connections." actionLabel="Create AI Provider" actionHref={route('admin.ai-providers.create')} />
        <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[840px] text-sm"><thead><tr className="border-b"><th className="px-2 pb-3 text-left">Name</th><th className="px-2 pb-3 text-left">Type</th><th className="px-2 pb-3 text-left">Default model</th><th className="px-2 pb-3 text-left">Active</th><th className="px-2 pb-3 text-left">Default</th><th className="px-2 pb-3 text-right">Actions</th></tr></thead><tbody>{providers.data.length ? providers.data.map((provider)=><tr key={provider.id} className="border-b"><td className="px-2 py-3">{provider.name}</td><td className="px-2 py-3">{provider.provider_type}</td><td className="px-2 py-3">{provider.default_model || '-'}</td><td className="px-2 py-3">{provider.is_active ? 'Yes' : 'No'}</td><td className="px-2 py-3">{provider.is_default ? 'Yes' : 'No'}</td><td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="outline"><Link href={route('admin.ai-providers.show', provider.id)}>View</Link></Button><Button asChild size="sm" variant="secondary"><Link href={route('admin.ai-providers.edit', provider.id)}>Edit</Link></Button><Button size="sm" variant="destructive" onClick={()=>destroy(provider.id)}>Delete</Button></div></td></tr>) : <tr><td colSpan={6} className="px-2 py-6 text-center">No AI providers found.</td></tr>}</tbody></table><Pagination links={providers.links} /></CardContent></Card></AdminLayout>;
}
