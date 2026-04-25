import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
interface Option { id:number; name:string; }
interface Props { parents:Option[]; }
export default function Create({ parents }: Props): JSX.Element {
 const { data, setData, post, processing } = useForm({ parent_id:'', name:'', slug:'', description:'', name_es:'', description_es:'', color:'#94a3b8', icon:'', is_active:true, sort_order:0 });
 const submit=(e:any)=>{e.preventDefault();post(route('editor.news-categories.store'));};
 return <EditorLayout><Head title="Create News Category" /><AdminPageHeader title="Create News Category" description="Add a thematic category." />
 <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4">
 <div><Label>Name</Label><Input value={data.name} onChange={(e)=>setData('name', e.target.value)} /></div><div><Label>Slug</Label><Input value={data.slug} onChange={(e)=>setData('slug', e.target.value)} /></div>
 <div><Label>Parent</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.parent_id} onChange={(e)=>setData('parent_id', e.target.value)}><option value="">None</option>{parents.map((p)=><option key={p.id} value={p.id}>{p.name}</option>)}</select></div>
 <div><Label>Description</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.description} onChange={(e)=>setData('description', e.target.value)} /></div>
 <div className="grid gap-4 md:grid-cols-2"><div><Label>Spanish name</Label><Input value={data.name_es} onChange={(e)=>setData('name_es', e.target.value)} /></div><div><Label>Spanish description</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={2} value={data.description_es} onChange={(e)=>setData('description_es', e.target.value)} /></div></div>
 <div className="grid gap-4 md:grid-cols-2"><div><Label>Color</Label><div className="flex gap-2"><Input type="color" value={data.color || '#94a3b8'} onChange={(e)=>setData('color', e.target.value)} className="h-10 w-14 p-1" /><Input value={data.color} onChange={(e)=>setData('color', e.target.value)} /></div></div><div><Label>Icon</Label><Input value={data.icon} onChange={(e)=>setData('icon', e.target.value)} /></div></div>
 <div className="grid gap-4 md:grid-cols-2"><div><Label>Sort order</Label><Input type="number" value={data.sort_order} onChange={(e)=>setData('sort_order', Number(e.target.value))} /></div><label className="flex items-center gap-2 pt-8"><input type="checkbox" checked={data.is_active} onChange={(e)=>setData('is_active', e.target.checked)} />Active</label></div>
 <div className="flex gap-2"><Button type="submit" disabled={processing}>Save</Button><Button asChild variant="secondary"><Link href={route('editor.news-categories.index')}>Cancel</Link></Button></div></form></CardContent></Card></EditorLayout>;
}
