import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';

interface LocationItem { id:number; name:string; type:string; country_code:string|null; is_active:boolean; sort_order:number; }
interface Props { locations:{data:LocationItem[]; links:Array<{url:string|null; label:string; active:boolean}>}; }

export default function Index({ locations }: Props): JSX.Element {
    const destroy = (id:number):void => { if (window.confirm('Delete this location?')) router.delete(route('editor.locations.destroy', id)); };
    return <EditorLayout><Head title="Locations" /><AdminPageHeader title="Locations" description="Manage geographic scopes for news and editions." actionLabel="Create location" actionHref={route('editor.locations.create')} />
        <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[700px] text-left text-sm"><thead><tr className="border-b border-slate-200 text-slate-500"><th className="px-2 pb-3">Name</th><th className="px-2 pb-3">Type</th><th className="px-2 pb-3">Country code</th><th className="px-2 pb-3">Active</th><th className="px-2 pb-3">Sort order</th><th className="px-2 pb-3 text-right">Actions</th></tr></thead><tbody>{locations.data.length ? locations.data.map((item)=><tr key={item.id} className="border-b border-slate-100"><td className="px-2 py-3 font-medium">{item.name}</td><td className="px-2 py-3">{item.type}</td><td className="px-2 py-3">{item.country_code || '-'}</td><td className="px-2 py-3">{item.is_active ? 'Yes' : 'No'}</td><td className="px-2 py-3">{item.sort_order}</td><td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="secondary"><Link href={route('editor.locations.edit', item.id)}>Edit</Link></Button><Button size="sm" variant="destructive" onClick={()=>destroy(item.id)}>Delete</Button></div></td></tr>) : <tr><td colSpan={6} className="px-2 py-6 text-center text-slate-500">No locations yet.</td></tr>}</tbody></table><Pagination links={locations.links} /></CardContent></Card></EditorLayout>;
}
