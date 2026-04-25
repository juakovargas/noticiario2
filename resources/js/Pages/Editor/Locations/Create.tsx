import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';

interface Option { id:number; name:string; }
interface Props { parents:Option[]; types:string[]; }

export default function Create({ parents, types }: Props): JSX.Element {
    const { data, setData, post, processing, errors } = useForm({ parent_id: '', name: '', slug: '', type: 'country', country_code: '', timezone: '', is_active: true, sort_order: 0 });
    const submit = (e: any) => { e.preventDefault(); post(route('editor.locations.store')); };
    return <EditorLayout><Head title="Create Location" /><AdminPageHeader title="Create Location" description="Add a new location." />
        <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4">
            <div><Label>Name</Label><Input value={data.name} onChange={(e)=>setData('name', e.target.value)} />{errors.name && <p className="text-sm text-red-600">{errors.name}</p>}</div>
            <div><Label>Slug</Label><Input value={data.slug} onChange={(e)=>setData('slug', e.target.value)} /></div>
            <div><Label>Parent</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.parent_id} onChange={(e)=>setData('parent_id', e.target.value)}><option value="">None</option>{parents.map((p)=><option key={p.id} value={p.id}>{p.name}</option>)}</select></div>
            <div><Label>Type</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.type} onChange={(e)=>setData('type', e.target.value)}>{types.map((type)=><option key={type} value={type}>{type}</option>)}</select></div>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>Country code</Label><Input value={data.country_code} onChange={(e)=>setData('country_code', e.target.value)} /></div><div><Label>Timezone</Label><Input value={data.timezone} onChange={(e)=>setData('timezone', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>Sort order</Label><Input type="number" value={data.sort_order} onChange={(e)=>setData('sort_order', Number(e.target.value))} /></div><label className="flex items-center gap-2 pt-8"><input type="checkbox" checked={data.is_active} onChange={(e)=>setData('is_active', e.target.checked)} />Active</label></div>
            <div className="flex gap-2"><Button type="submit" disabled={processing}>Save</Button><Button asChild variant="secondary"><Link href={route('editor.locations.index')}>Cancel</Link></Button></div>
        </form></CardContent></Card></EditorLayout>;
}
