import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
interface Option { id:number; name:string; }
interface Category { id:number; parent_id:number|null; name:string; slug:string; description:string|null; color:string|null; icon:string|null; is_active:boolean; sort_order:number; }
interface Props { newsCategory:Category; parents:Option[]; }
export default function Edit({ newsCategory, parents }: Props): JSX.Element {
 const { data, setData, put, processing } = useForm({ parent_id:newsCategory.parent_id?.toString() || '', name:newsCategory.name, slug:newsCategory.slug, description:newsCategory.description || '', color:newsCategory.color || '', icon:newsCategory.icon || '', is_active:newsCategory.is_active, sort_order:newsCategory.sort_order });
 const submit=(e:any)=>{e.preventDefault();put(route('editor.news-categories.update', newsCategory.id));};
 return <EditorLayout><Head title="Edit News Category" /><AdminPageHeader title="Edit News Category" description="Update thematic category." />
 <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4">
 <div><Label>Name</Label><Input value={data.name} onChange={(e)=>setData('name', e.target.value)} /></div><div><Label>Slug</Label><Input value={data.slug} onChange={(e)=>setData('slug', e.target.value)} /></div>
 <div><Label>Parent</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.parent_id} onChange={(e)=>setData('parent_id', e.target.value)}><option value="">None</option>{parents.map((p)=><option key={p.id} value={p.id}>{p.name}</option>)}</select></div>
 <div><Label>Description</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.description} onChange={(e)=>setData('description', e.target.value)} /></div>
 <div className="grid gap-4 md:grid-cols-2"><div><Label>Color</Label><Input value={data.color} onChange={(e)=>setData('color', e.target.value)} /></div><div><Label>Icon</Label><Input value={data.icon} onChange={(e)=>setData('icon', e.target.value)} /></div></div>
 <div className="grid gap-4 md:grid-cols-2"><div><Label>Sort order</Label><Input type="number" value={data.sort_order} onChange={(e)=>setData('sort_order', Number(e.target.value))} /></div><label className="flex items-center gap-2 pt-8"><input type="checkbox" checked={data.is_active} onChange={(e)=>setData('is_active', e.target.checked)} />Active</label></div>
 <div className="flex gap-2"><Button type="submit" disabled={processing}>Save</Button><Button asChild variant="secondary"><Link href={route('editor.news-categories.index')}>Cancel</Link></Button></div></form></CardContent></Card></EditorLayout>;
}
