import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import AdminPageHeader from '@/Components/AdminPageHeader';
interface Option { id:number; name:string; }
interface Props { sources:Option[]; categories:Option[]; locations:Option[]; statuses:string[]; }
export default function Create({ sources, categories, locations, statuses }: Props): JSX.Element {
 const { data, setData, post, processing } = useForm({ news_source_id:'', news_category_id:'', location_id:'', title:'', slug:'', summary:'', body:'', source_url:'', author:'', language:'', published_at:'', collected_at:'', status:'draft', editorial_priority:3, is_evergreen:false });
 const submit=(e:any)=>{e.preventDefault();post(route('editor.news-items.store'));};
 return <EditorLayout><Head title="Create News Item" /><AdminPageHeader title="Create News Item" description="Add a news item to the editorial queue." /><Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4">
 <div><Label>Title</Label><Input value={data.title} onChange={(e)=>setData('title', e.target.value)} /></div><div><Label>Slug</Label><Input value={data.slug} onChange={(e)=>setData('slug', e.target.value)} /></div>
 <div className="grid gap-4 md:grid-cols-3"><div><Label>Source</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.news_source_id} onChange={(e)=>setData('news_source_id', e.target.value)}><option value="">None</option>{sources.map((o)=><option key={o.id} value={o.id}>{o.name}</option>)}</select></div><div><Label>Category</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.news_category_id} onChange={(e)=>setData('news_category_id', e.target.value)}><option value="">None</option>{categories.map((o)=><option key={o.id} value={o.id}>{o.name}</option>)}</select></div><div><Label>Location</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.location_id} onChange={(e)=>setData('location_id', e.target.value)}><option value="">None</option>{locations.map((o)=><option key={o.id} value={o.id}>{o.name}</option>)}</select></div></div>
 <div><Label>Summary</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.summary} onChange={(e)=>setData('summary', e.target.value)} /></div><div><Label>Body</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={8} value={data.body} onChange={(e)=>setData('body', e.target.value)} /></div>
 <div className="grid gap-4 md:grid-cols-3"><div><Label>Status</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.status} onChange={(e)=>setData('status', e.target.value)}>{statuses.map((s)=><option key={s} value={s}>{s}</option>)}</select></div><div><Label>Published at</Label><Input type="datetime-local" value={data.published_at} onChange={(e)=>setData('published_at', e.target.value)} /></div><div><Label>Priority</Label><Input type="number" min={1} max={5} value={data.editorial_priority} onChange={(e)=>setData('editorial_priority', Number(e.target.value))} /></div></div>
 <div className="flex gap-2"><Button type="submit" disabled={processing}>Save</Button><Button asChild variant="secondary"><Link href={route('editor.news-items.index')}>Cancel</Link></Button></div>
 </form></CardContent></Card></EditorLayout>;
}
